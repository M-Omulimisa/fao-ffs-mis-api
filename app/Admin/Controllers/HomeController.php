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
            .info-box { border: none; background: #05179F; color: white; }
            .small-box { border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; }
            .box { border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            .box-header { background: linear-gradient(135deg, #05179F 0%, #0652DD 100%); color: white; border-bottom: none; border-radius: 8px 8px 0 0; }
            .box-header .box-title { color: white; font-weight: 600; }
            .box-body { padding: 20px; }
            .stat-card {
                background: white;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.07);
                transition: all 0.3s ease;
                border: 1px solid #e8e8e8;
            }
            .stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            }
            .stat-number {
                font-size: 36px;
                font-weight: 700;
                margin: 10px 0;
                background: linear-gradient(135deg, #05179F, #0652DD);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .stat-label {
                font-size: 14px;
                color: #666;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .stat-detail {
                font-size: 13px;
                color: #999;
                margin-top: 8px;
            }
            .stat-icon {
                width: 56px;
                height: 56px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: white;
                margin-bottom: 12px;
            }
            .trend-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                margin-top: 8px;
            }
            .trend-up { background: #e8f5e9; color: #4caf50; }
            .trend-down { background: #ffebee; color: #f44336; }
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
                $totalGroups,
                'FFS Groups',
                'Active in 9 districts',
                'fa-users',
                $lastMonthGroups > 0 ? "+{$lastMonthGroups} last month" : 'No change',
                '#05179F'
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
                $totalMembers,
                'Registered Members',
                "{$femalePercent}% Female | {$malePercent}% Male",
                'fa-user',
                'Active members',
                '#4caf50'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // VSLA Groups & Savings
        $row->column(3, function (Column $column) {
            $vslaGroups = FfsGroup::where('type', 'VSLA')->count();
            $totalSavings = AccountTransaction::where('account_type', 'share')->sum('amount');
            
            $content = $this->renderModernKPICard(
                $vslaGroups,
                'VSLA Groups',
                'UGX ' . number_format($totalSavings) . ' total savings',
                'fa-piggy-bank',
                'Financial inclusion',
                '#ff9800'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // Advisory Posts
        $row->column(3, function (Column $column) {
            $totalPosts = AdvisoryPost::count();
            $publishedPosts = AdvisoryPost::where('status', 'published')->count();
            
            $content = $this->renderModernKPICard(
                $publishedPosts,
                'Advisory Posts',
                "{$totalPosts} total posts",
                'fa-newspaper',
                'Knowledge sharing',
                '#2196f3'
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
                <div style='background: linear-gradient(135deg, #05179F 0%, #0652DD 100%); padding: 30px; border-radius: 12px; color: white; margin-bottom: 20px;'>
                    <div class='row'>
                        <div class='col-md-3'>
                            <div style='text-align: center;'>
                                <i class='fa fa-piggy-bank' style='font-size: 32px; opacity: 0.9;'></i>
                                <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>UGX " . number_format($totalSavings) . "</h2>
                                <p style='margin: 0; opacity: 0.9; font-size: 13px;'>Total Savings</p>
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div style='text-align: center; border-left: 1px solid rgba(255,255,255,0.2);'>
                                <i class='fa fa-hand-holding-usd' style='font-size: 32px; opacity: 0.9;'></i>
                                <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>UGX " . number_format($activeLoans) . "</h2>
                                <p style='margin: 0; opacity: 0.9; font-size: 13px;'>Active Loans Portfolio</p>
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div style='text-align: center; border-left: 1px solid rgba(255,255,255,0.2);'>
                                <i class='fa fa-heart' style='font-size: 32px; opacity: 0.9;'></i>
                                <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>UGX " . number_format($socialFund) . "</h2>
                                <p style='margin: 0; opacity: 0.9; font-size: 13px;'>Social Fund Balance</p>
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div style='text-align: center; border-left: 1px solid rgba(255,255,255,0.2);'>
                                <i class='fa fa-percentage' style='font-size: 32px; opacity: 0.9;'></i>
                                <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>{$repaymentRate}%</h2>
                                <p style='margin: 0; opacity: 0.9; font-size: 13px;'>Loan Repayment Rate</p>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);'>
                        <p style='margin: 0; font-size: 14px; opacity: 0.9;'>
                            <i class='fa fa-sync-alt'></i> {$activeCycles} Active Cycles | 
                            <i class='fa fa-calendar'></i> UGX " . number_format($totalDisbursed) . " Total Disbursed | 
                            <i class='fa fa-check-circle'></i> UGX " . number_format($totalRepaid) . " Repaid
                        </p>
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
                <div style='text-align: center; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #05179F, #0652DD); color: white; padding: 30px; border-radius: 12px; margin-bottom: 20px;'>
                        <i class='fa fa-calendar-alt' style='font-size: 36px; opacity: 0.9;'></i>
                        <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>{$totalMeetings}</h2>
                        <p style='margin: 0; opacity: 0.9;'>Total Meetings</p>
                    </div>
                    
                    <table class='table table-borderless' style='margin-bottom: 0;'>
                        <tbody>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td style='padding: 12px;'><i class='fa fa-calendar-day' style='color: #05179F;'></i> <strong>This Week</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #e3f2fd; color: #2196f3; padding: 4px 12px; border-radius: 12px; font-weight: 600;'>{$thisWeekMeetings}</span></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td style='padding: 12px;'><i class='fa fa-calendar-week' style='color: #05179F;'></i> <strong>This Month</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #e8f5e9; color: #4caf50; padding: 4px 12px; border-radius: 12px; font-weight: 600;'>{$thisMonthMeetings}</span></td>
                            </tr>
                            <tr>
                                <td style='padding: 12px;'><i class='fa fa-sync-alt' style='color: #05179F;'></i> <strong>Active Cycles</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #fff3e0; color: #ff9800; padding: 4px 12px; border-radius: 12px; font-weight: 600;'>{$activeCycles}</span></td>
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
                <div style='text-align: center; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #4caf50, #8bc34a); color: white; padding: 30px; border-radius: 12px; margin-bottom: 20px;'>
                        <i class='fa fa-hand-holding-usd' style='font-size: 36px; opacity: 0.9;'></i>
                        <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>{$activeLoansCount}</h2>
                        <p style='margin: 0; opacity: 0.9;'>Active Loans</p>
                    </div>
                    
                    <table class='table table-borderless' style='margin-bottom: 0;'>
                        <tbody>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td style='padding: 12px;'><i class='fa fa-money-bill-wave' style='color: #4caf50;'></i> <strong>Portfolio Value</strong></td>
                                <td class='text-right' style='padding: 12px; font-weight: 600;'>UGX " . number_format($activeLoansAmount) . "</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td style='padding: 12px;'><i class='fa fa-arrow-up' style='color: #4caf50;'></i> <strong>Total Disbursed</strong></td>
                                <td class='text-right' style='padding: 12px;'>UGX " . number_format($totalDisbursed) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px;'><i class='fa fa-percentage' style='color: #4caf50;'></i> <strong>Repayment Rate</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #e8f5e9; color: #4caf50; padding: 4px 12px; border-radius: 12px; font-weight: 700;'>{$repaymentRate}%</span></td>
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
                <div style='text-align: center; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #ff9800, #ffc107); color: white; padding: 30px; border-radius: 12px; margin-bottom: 20px;'>
                        <i class='fa fa-chart-line' style='font-size: 36px; opacity: 0.9;'></i>
                        <h2 style='margin: 15px 0 5px 0; color: white; font-weight: 700;'>{$totalGroups}</h2>
                        <p style='margin: 0; opacity: 0.9;'>Total Groups</p>
                    </div>
                    
                    <table class='table table-borderless' style='margin-bottom: 0;'>
                        <tbody>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td style='padding: 12px;'><i class='fa fa-users' style='color: #ff9800;'></i> <strong>Members</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #fff3e0; color: #ff9800; padding: 4px 12px; border-radius: 12px; font-weight: 600;'>{$totalMembers}</span></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td style='padding: 12px;'><i class='fa fa-newspaper' style='color: #ff9800;'></i> <strong>Advisory Posts</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #fff3e0; color: #ff9800; padding: 4px 12px; border-radius: 12px; font-weight: 600;'>{$totalPosts}</span></td>
                            </tr>
                            <tr>
                                <td style='padding: 12px;'><i class='fa fa-sync-alt' style='color: #ff9800;'></i> <strong>Active Cycles</strong></td>
                                <td class='text-right' style='padding: 12px;'><span style='background: #fff3e0; color: #ff9800; padding: 4px 12px; border-radius: 12px; font-weight: 600;'>{$activeCycles}</span></td>
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
                    .activity-timeline {
                        padding: 0;
                        margin: 0;
                    }
                    .activity-item {
                        display: flex;
                        align-items: flex-start;
                        padding: 20px;
                        border-left: 3px solid #e0e0e0;
                        margin-left: 20px;
                        position: relative;
                        transition: all 0.3s ease;
                    }
                    .activity-item:hover {
                        background-color: #f9f9f9;
                        border-left-color: #05179F;
                    }
                    .activity-item:before {
                        content: '';
                        position: absolute;
                        left: -8px;
                        top: 20px;
                        width: 12px;
                        height: 12px;
                        border-radius: 50%;
                        background: white;
                        border: 3px solid #e0e0e0;
                    }
                    .activity-item:hover:before {
                        border-color: #05179F;
                        background: #05179F;
                    }
                    .activity-icon {
                        width: 48px;
                        height: 48px;
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                        color: white;
                        margin-right: 20px;
                        flex-shrink: 0;
                    }
                    .activity-content {
                        flex: 1;
                    }
                    .activity-title {
                        font-weight: 600;
                        font-size: 15px;
                        margin-bottom: 4px;
                        color: #333;
                    }
                    .activity-description {
                        font-size: 13px;
                        color: #666;
                        margin-bottom: 8px;
                    }
                    .activity-meta {
                        font-size: 12px;
                        color: #999;
                    }
                    .activity-badge {
                        display: inline-block;
                        padding: 4px 10px;
                        border-radius: 12px;
                        font-size: 11px;
                        font-weight: 600;
                        margin-left: 8px;
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
     * Render Modern KPI Card
     */
    private function renderModernKPICard($number, $label, $detail, $icon, $trend, $color)
    {
        return "
            <div class='stat-card'>
                <div class='stat-icon' style='background: linear-gradient(135deg, {$color}, " . $this->adjustBrightness($color, 30) . ");'>
                    <i class='fa {$icon}'></i>
                </div>
                <div class='stat-number'>{$number}</div>
                <div class='stat-label'>{$label}</div>
                <div class='stat-detail'>{$detail}</div>
                <div class='trend-badge trend-up'><i class='fa fa-arrow-up'></i> {$trend}</div>
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
