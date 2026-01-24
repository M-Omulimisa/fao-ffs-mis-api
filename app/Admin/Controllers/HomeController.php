<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\Project;
use App\Models\AccountTransaction;
use App\Models\VslaLoan;
use App\Models\SocialFundTransaction;
use App\Models\VslaMeeting;
use App\Models\AdvisoryPost;
use Carbon\Carbon;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        // Add Chart.js CDN and custom styles
        Admin::script('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
        
        Admin::style('
            /* Flat, Compact Design - Square Corners */
            .info-box { border: none; background: #05179F; color: white; }
            .small-box { border: none; box-shadow: none; border-radius: 0; }
            .box { border: 1px solid #ddd; border-radius: 0; box-shadow: none; }
            .box-header { background: #05179F; color: white; border-bottom: 1px solid #ddd; border-radius: 0; padding: 10px 15px; }
            .box-header .box-title { color: white; font-weight: 600; font-size: 14px; }
            .box-body { padding: 15px; }
            
            /* Compact Stat Cards */
            .stat-card {
                background: white;
                border: 1px solid #ddd;
                padding: 12px;
                margin-bottom: 0;
            }
            .stat-card-header {
                display: flex;
                align-items: center;
                margin-bottom: 6px;
            }
            .stat-icon {
                width: 36px;
                height: 36px;
                background: #05179F;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                margin-right: 10px;
                flex-shrink: 0;
            }
            .stat-content {
                flex: 1;
                min-width: 0;
            }
            .stat-number {
                font-size: 24px;
                font-weight: 700;
                color: #05179F;
                line-height: 1.1;
            }
            .stat-label {
                font-size: 11px;
                color: #666;
                font-weight: 600;
                text-transform: uppercase;
                margin-top: 3px;
                line-height: 1.2;
            }
            .stat-detail {
                font-size: 10px;
                color: #999;
                margin-top: 4px;
                line-height: 1.3;
            }
        ');
        
        return $content
            ->title('📊 FAO FFS-MIS Dashboard')
            ->description('Farmer Field School Management Information System - Karamoja Region')
            ->row(function (Row $row) {
                $this->addKPICards($row);
            })
            ->row(function (Row $row) {
                $this->addVSLAFinancialOverview($row);
            })
            ->row(function (Row $row) {
                $this->addCharts($row);
            })
            ->row(function (Row $row) {
                $this->addActivitySummary($row);
            })
            ->row(function (Row $row) {
                $this->addRecentActivitiesTimeline($row);
            });
    }

    /**
     * KPI Cards - Key Performance Indicators (REAL DATA)
     */
    private function addKPICards(Row $row)
    {
        // FFS Groups
        $row->column(3, function (Column $column) {
            $totalGroups = FfsGroup::count();
            $activeGroups = FfsGroup::where('status', 'Active')->count();
            $lastMonthGroups = FfsGroup::whereMonth('created_at', now()->subMonth()->month)->count();
            
            $content = $this->renderModernKPICard(
                'fa-users',
                $totalGroups,
                'FFS Groups',
                'Active in 9 districts'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // Registered Members
        $row->column(3, function (Column $column) {
            $totalMembers = User::whereNotNull('group_id')->where('group_id', '!=', '')->count();
            $femaleMembers = User::whereNotNull('group_id')->where('sex', 'Female')->count();
            $maleMembers = User::whereNotNull('group_id')->where('sex', 'Male')->count();
            $femalePercent = $totalMembers > 0 ? round(($femaleMembers / $totalMembers) * 100) : 0;
            $malePercent = $totalMembers > 0 ? round(($maleMembers / $totalMembers) * 100) : 0;
            
            $content = $this->renderModernKPICard(
                'fa-user',
                $totalMembers,
                'Registered Members',
                "{$femalePercent}% Female | {$malePercent}% Male"
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // VSLA Groups & Savings
        $row->column(3, function (Column $column) {
            $vslaGroups = FfsGroup::where('type', 'VSLA')->count();
            $totalSavings = AccountTransaction::where('account_type', 'share')->sum('amount');
            
            $content = $this->renderModernKPICard(
                'fa-piggy-bank',
                $vslaGroups,
                'VSLA Groups',
                'UGX ' . number_format($totalSavings) . ' total savings'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // Advisory Posts
        $row->column(3, function (Column $column) {
            $totalPosts = AdvisoryPost::count();
            $publishedPosts = AdvisoryPost::where('status', 'published')->count();
            
            $content = $this->renderModernKPICard(
                'fa-newspaper',
                $publishedPosts,
                'Advisory Posts',
                "{$totalPosts} total posts"
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });
    }

    /**
     * VSLA Financial Overview (REAL DATA)
     */
    private function addVSLAFinancialOverview(Row $row)
    {
        $row->column(12, function (Column $column) {
            $totalSavings = AccountTransaction::where('account_type', 'share')->sum('amount');
            $activeLoans = VslaLoan::where('status', 'active')->sum('loan_amount');
            $socialFund = SocialFundTransaction::sum('amount');
            $totalDisbursed = VslaLoan::sum('loan_amount');
            $totalRepaid = VslaLoan::sum('amount_paid');
            $repaymentRate = $totalDisbursed > 0 ? round(($totalRepaid / $totalDisbursed) * 100, 1) : 0;
            $activeCycles = Project::where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')->count();
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white;'>
                    <div class='row' style='margin: 0;'>
                        <div class='col-md-3' style='padding: 10px; border-right: 1px solid rgba(255,255,255,0.2);'>
                            <div style='text-align: center;'>
                                <i class='fa fa-piggy-bank' style='font-size: 24px;'></i>
                                <h3 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 24px;'>UGX " . number_format($totalSavings) . "</h3>
                                <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Total Savings</p>
                            </div>
                        </div>
                        <div class='col-md-3' style='padding: 10px; border-right: 1px solid rgba(255,255,255,0.2);'>
                            <div style='text-align: center;'>
                                <i class='fa fa-hand-holding-usd' style='font-size: 24px;'></i>
                                <h3 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 24px;'>UGX " . number_format($activeLoans) . "</h3>
                                <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Active Loans</p>
                            </div>
                        </div>
                        <div class='col-md-3' style='padding: 10px; border-right: 1px solid rgba(255,255,255,0.2);'>
                            <div style='text-align: center;'>
                                <i class='fa fa-heart' style='font-size: 24px;'></i>
                                <h3 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 24px;'>UGX " . number_format($socialFund) . "</h3>
                                <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Social Fund</p>
                            </div>
                        </div>
                        <div class='col-md-3' style='padding: 10px;'>
                            <div style='text-align: center;'>
                                <i class='fa fa-percentage' style='font-size: 24px;'></i>
                                <h3 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 24px;'>{$repaymentRate}%</h3>
                                <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Repayment Rate</p>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);'>
                        <span style='font-size: 11px; margin-right: 15px;'><i class='fa fa-sync-alt'></i> {$activeCycles} Active Cycles</span>
                        <span style='font-size: 11px; margin-right: 15px;'><i class='fa fa-arrow-up'></i> UGX " . number_format($totalDisbursed) . " Disbursed</span>
                        <span style='font-size: 11px;'><i class='fa fa-check-circle'></i> UGX " . number_format($totalRepaid) . " Repaid</span>
                    </div>
                </div>
            ";
            
            $box = new Box('💰 VSLA Financial Overview', $content);
            $column->append($box);
        });
    }

    /**
     * Charts Section (REAL DATA)
     */
    private function addCharts(Row $row)
    {
        // VSLA Activity Trend
        $row->column(8, function (Column $column) {
            // Get last 6 months data
            $months = [];
            $meetingsData = [];
            $loansData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                $meetingsData[] = VslaMeeting::whereYear('meeting_date', $date->year)
                    ->whereMonth('meeting_date', $date->month)
                    ->count();
                $loansData[] = VslaLoan::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
            }
            
            $content = "
                <canvas id='activityChart' height='80'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('activityChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: " . json_encode($months) . ",
                                datasets: [{
                                    label: 'VSLA Meetings',
                                    data: " . json_encode($meetingsData) . ",
                                    borderColor: '#05179F',
                                    backgroundColor: 'rgba(5, 23, 159, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4
                                }, {
                                    label: 'Loans Disbursed',
                                    data: " . json_encode($loansData) . ",
                                    borderColor: '#4caf50',
                                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: {
                                            font: { size: 14, weight: 'bold' },
                                            padding: 15,
                                            usePointStyle: true
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'VSLA Activity Trend (Last 6 Months)',
                                        font: { size: 16, weight: 'bold' }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('📈 Activity Trend', $content);
            $column->append($box);
        });

        // Group Types Distribution
        $row->column(4, function (Column $column) {
            $vslaGroups = FfsGroup::where('type', 'VSLA')->count();
            $ffsGroups = FfsGroup::where('type', 'FFS')->count();
            $fbsGroups = FfsGroup::where('type', 'FBS')->count();
            $otherGroups = FfsGroup::whereNotIn('type', ['VSLA', 'FFS', 'FBS'])->count();
            
            $types = ['VSLA', 'FFS', 'FBS', 'Other'];
            $counts = [$vslaGroups, $ffsGroups, $fbsGroups, $otherGroups];
            $colors = ['#05179F', '#4caf50', '#ff9800', '#9e9e9e'];
            
            $content = "
                <canvas id='groupTypeChart' height='200'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('groupTypeChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($types) . ",
                                datasets: [{
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode($colors) . ",
                                    borderWidth: 3,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 12 },
                                            padding: 12,
                                            usePointStyle: true
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Groups by Type',
                                        font: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('📊 Group Distribution', $content);
            $column->append($box);
        });
    }

    /**
     * Activity Summary (REAL DATA)
     */
    private function addActivitySummary(Row $row)
    {
        // Meeting Statistics
        $row->column(4, function (Column $column) {
            $totalMeetings = VslaMeeting::count();
            $thisMonthMeetings = VslaMeeting::whereMonth('meeting_date', now()->month)->count();
            $thisWeekMeetings = VslaMeeting::whereBetween('meeting_date', [now()->startOfWeek(), now()->endOfWeek()])->count();
            $activeCycles = Project::where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')->count();
            
            $content = "
                <div style='padding: 15px;'>
                    <div style='background: #05179F; color: white; padding: 15px; text-align: center; margin-bottom: 15px;'>
                        <i class='fa fa-calendar-alt' style='font-size: 24px;'></i>
                        <h3 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 28px;'>{$totalMeetings}</h3>
                        <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Total Meetings</p>
                    </div>
                    
                    <table class='table' style='margin-bottom: 0;'>
                        <tbody>
                            <tr>
                                <td style='padding: 8px; border-top: none;'><i class='fa fa-calendar-day' style='color: #05179F;'></i> This Week</td>
                                <td class='text-right' style='padding: 8px; border-top: none;'><strong>{$thisWeekMeetings}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px;'><i class='fa fa-calendar-week' style='color: #4caf50;'></i> This Month</td>
                                <td class='text-right' style='padding: 8px;'><strong>{$thisMonthMeetings}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px;'><i class='fa fa-sync-alt' style='color: #ff9800;'></i> Active Cycles</td>
                                <td class='text-right' style='padding: 8px;'><strong>{$activeCycles}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            ";
            
            $box = new Box('📅 Meeting Statistics', $content);
            $column->append($box);
        });

        // Loan Portfolio
        $row->column(4, function (Column $column) {
            $activeLoansCount = VslaLoan::where('status', 'active')->count();
            $totalDisbursed = VslaLoan::sum('loan_amount');
            $totalRepaid = VslaLoan::sum('amount_paid');
            $activeLoansAmount = VslaLoan::where('status', 'active')->sum('loan_amount');
            $repaymentRate = $totalDisbursed > 0 ? round(($totalRepaid / $totalDisbursed) * 100, 1) : 0;
            
            $content = "
                <div style='padding: 15px;'>
                    <div style='background: #4caf50; color: white; padding: 15px; text-align: center; margin-bottom: 15px;'>
                        <i class='fa fa-hand-holding-usd' style='font-size: 24px;'></i>
                        <h3 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 28px;'>{$activeLoansCount}</h3>
                        <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Active Loans</p>
                    </div>
                    
                    <table class='table' style='margin-bottom: 0;'>
                        <tbody>
                            <tr>
                                <td style='padding: 8px; border-top: none;'><i class='fa fa-money-bill-wave' style='color: #4caf50;'></i> Portfolio</td>
                                <td class='text-right' style='padding: 8px; border-top: none;'><strong>UGX " . number_format($activeLoansAmount) . "</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px;'><i class='fa fa-arrow-up' style='color: #4caf50;'></i> Disbursed</td>
                                <td class='text-right' style='padding: 8px;'><strong>UGX " . number_format($totalDisbursed) . "</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px;'><i class='fa fa-percentage' style='color: #4caf50;'></i> Repayment</td>
                                <td class='text-right' style='padding: 8px;'><strong>{$repaymentRate}%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            ";
            
            $box = new Box('💳 Loan Portfolio', $content);
            $column->append($box);
        });

        // System Overview
        $row->column(4, function (Column $column) {
            $totalGroups = FfsGroup::count();
            $totalMembers = User::whereNotNull('group_id')->where('group_id', '!=', '')->count();
            $totalPosts = AdvisoryPost::count();
            $activeCycles = Project::where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')->count();
            
            $content = "
                <div style='padding: 15px;'>
                    <div style='background: #ff9800; color: white; padding: 20px; text-align: center; margin-bottom: 15px;'>
                        <i class='fa fa-chart-line' style='font-size: 28px;'></i>
                        <h2 style='margin: 8px 0 4px 0; color: white; font-weight: 700; font-size: 32px;'>{$totalGroups}</h2>
                        <p style='margin: 0; font-size: 11px; text-transform: uppercase;'>Total Groups</p>
                    </div>
                    
                    <table class='table' style='margin-bottom: 0;'>
                        <tbody>
                            <tr>
                                <td style='padding: 8px; border-top: none;'><i class='fa fa-users' style='color: #ff9800;'></i> Members</td>
                                <td class='text-right' style='padding: 8px; border-top: none;'><strong>{$totalMembers}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px;'><i class='fa fa-newspaper' style='color: #2196f3;'></i> Advisory Posts</td>
                                <td class='text-right' style='padding: 8px;'><strong>{$totalPosts}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px;'><i class='fa fa-sync-alt' style='color: #4caf50;'></i> Active Cycles</td>
                                <td class='text-right' style='padding: 8px;'><strong>{$activeCycles}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            ";
            
            $box = new Box('📊 System Overview', $content);
            $column->append($box);
        });
    }

    /**
     * Recent Activities Timeline (REAL DATA)
     */
    private function addRecentActivitiesTimeline(Row $row)
    {
        $row->column(12, function (Column $column) {
            // Get recent activities from different sources
            $recentMeetings = VslaMeeting::with('group')->orderBy('created_at', 'desc')->limit(3)->get();
            $recentLoans = VslaLoan::with('borrower')->orderBy('created_at', 'desc')->limit(3)->get();
            $recentPosts = AdvisoryPost::orderBy('created_at', 'desc')->limit(3)->get();
            
            $activities = [];
            
            foreach ($recentMeetings as $meeting) {
                $activities[] = [
                    'time' => $meeting->created_at->diffForHumans(),
                    'icon' => 'fa-calendar-check',
                    'color' => '#05179F',
                    'title' => 'VSLA Meeting Recorded',
                    'description' => ($meeting->group->name ?? 'Group') . ' conducted a meeting',
                    'category' => 'Meeting'
                ];
            }
            
            foreach ($recentLoans as $loan) {
                $activities[] = [
                    'time' => $loan->created_at->diffForHumans(),
                    'icon' => 'fa-hand-holding-usd',
                    'color' => '#4caf50',
                    'title' => 'Loan Disbursed',
                    'description' => 'UGX ' . number_format($loan->loan_amount) . ' to ' . ($loan->borrower->name ?? 'Member'),
                    'category' => 'VSLA Finance'
                ];
            }
            
            foreach ($recentPosts as $post) {
                $activities[] = [
                    'time' => $post->created_at->diffForHumans(),
                    'icon' => 'fa-newspaper',
                    'color' => '#2196f3',
                    'title' => 'Advisory Post Published',
                    'description' => $post->title,
                    'category' => 'Advisory'
                ];
            }
            
            // Sort by timestamp
            usort($activities, function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
            
            $content = "
                <style>
                    .activity-timeline { padding: 0; margin: 0; }
                    .activity-item {
                        display: flex;
                        align-items: flex-start;
                        padding: 12px 15px;
                        border-bottom: 1px solid #e0e0e0;
                    }
                    .activity-item:last-child { border-bottom: none; }
                    .activity-icon {
                        width: 36px;
                        height: 36px;
                        background: #05179F;
                        color: white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        margin-right: 12px;
                        flex-shrink: 0;
                    }
                    .activity-content { flex: 1; }
                    .activity-title {
                        font-weight: 600;
                        font-size: 13px;
                        margin-bottom: 4px;
                        color: #333;
                    }
                    .activity-description {
                        font-size: 12px;
                        color: #666;
                        margin-bottom: 4px;
                    }
                    .activity-meta {
                        font-size: 11px;
                        color: #999;
                    }
                    .activity-badge {
                        display: inline-block;
                        padding: 2px 8px;
                        background: #f5f5f5;
                        font-size: 10px;
                        font-weight: 600;
                        margin-left: 8px;
                        color: #666;
                    }
                </style>
                <div class='activity-timeline'>";
            
            foreach ($activities as $activity) {
                $badgeColors = [
                    'Meeting' => 'background: #e3f2fd; color: #2196f3;',
                    'VSLA Finance' => 'background: #e8f5e9; color: #4caf50;',
                    'Advisory' => 'background: #fff3e0; color: #ff9800;'
                ];
                $badgeStyle = $badgeColors[$activity['category']] ?? 'background: #f5f5f5; color: #666;';
                
                $content .= "
                    <div class='activity-item'>
                        <div class='activity-icon' style='background: {$activity['color']};'>
                            <i class='fa {$activity['icon']}'></i>
                        </div>
                        <div class='activity-content'>
                            <div class='activity-title'>{$activity['title']}</div>
                            <div class='activity-description'>{$activity['description']}</div>
                            <div class='activity-meta'>
                                <i class='fa fa-clock'></i> {$activity['time']}
                                <span class='activity-badge' style='{$badgeStyle}'>{$activity['category']}</span>
                            </div>
                        </div>
                    </div>";
            }
            
            $content .= "</div>";
            
            $box = new Box('🕐 Recent Activities', $content);
            $column->append($box);
        });
    }
    
    /**
     * Render modern KPI card
     */
    private function renderModernKPICard($icon, $number, $label, $detail)
    {
        return "
            <div class='stat-card'>
                <div class='stat-card-header'>
                    <div class='stat-icon'>
                        <i class='fa {$icon}'></i>
                    </div>
                    <div class='stat-content'>
                        <div class='stat-number'>{$number}</div>
                        <div class='stat-label'>{$label}</div>
                    </div>
                </div>
                <div class='stat-detail'>{$detail}</div>
            </div>
        ";
    }

    /**
     * Adjust color brightness
     */
    private function adjustBrightness($hex, $steps)
    {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        $color_parts = str_split($hex, 2);
        $return = '#';
        
        foreach ($color_parts as $color) {
            $color = hexdec($color);
            $color = max(0, min(255, $color + $steps));
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
        }
        
        return $return;
    }
}
