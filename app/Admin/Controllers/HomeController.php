<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        // Add Chart.js CDN
        Admin::script('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
        
        // Add custom CSS for flat design
        Admin::style('
            .info-box { border: none; background: #05179F; color: white; }
            .small-box { border: none; box-shadow: none; }
            .box { border: 1px solid #d2d6de; }
            .box-header { background: #f7f7f7; border-bottom: 1px solid #d2d6de; }
        ');
        
        return $content
            ->title('FAO FFS-MIS Dashboard')
            ->description('Farmer Field School Management Information System - Karamoja Region')
            ->row(function (Row $row) {
                $this->addKPICards($row);
            })
            ->row(function (Row $row) {
                $this->addCharts($row);
            })
            ->row(function (Row $row) {
                $this->addActivitySummary($row);
            })
            ->row(function (Row $row) {
                $this->addRecentActivities($row);
            });
    }

    /**
     * KPI Cards - Key Performance Indicators
     */
    private function addKPICards(Row $row)
    {
        // FFS Groups
        $row->column(3, function (Column $column) {
            $content = $this->renderKPICard(
                '247',
                'FFS Groups',
                'Active in 9 districts',
                'fa-users',
                '+12 this month'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // Registered Farmers
        $row->column(3, function (Column $column) {
            $content = $this->renderKPICard(
                '5,834',
                'Registered Farmers',
                '62% Female | 38% Male',
                'fa-user',
                '+324 this month'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // Training Sessions
        $row->column(3, function (Column $column) {
            $content = $this->renderKPICard(
                '1,456',
                'Training Sessions',
                '89% attendance rate',
                'fa-book',
                '+87 this week'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });

        // VSLA Groups
        $row->column(3, function (Column $column) {
            $content = $this->renderKPICard(
                '183',
                'VSLA Groups',
                'UGX 456M total savings',
                'fa-money',
                '+8 this month'
            );
            $box = new Box('', $content);
            $column->append($box->style('solid'));
        });
    }

    /**
     * Charts Section
     */
    private function addCharts(Row $row)
    {
        // Group Growth Trend
        $row->column(8, function (Column $column) {
            $months = ['Jun 2024', 'Jul 2024', 'Aug 2024', 'Sep 2024', 'Oct 2024', 'Nov 2024'];
            $ffsGroups = [198, 210, 218, 225, 237, 247];
            $vslaGroups = [145, 152, 161, 168, 177, 183];
            
            $content = "
                <canvas id='growthChart' height='80'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('growthChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: " . json_encode($months) . ",
                                datasets: [{
                                    label: 'FFS Groups',
                                    data: " . json_encode($ffsGroups) . ",
                                    borderColor: '#05179F',
                                    backgroundColor: 'rgba(5, 23, 159, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4
                                }, {
                                    label: 'VSLA Groups',
                                    data: " . json_encode($vslaGroups) . ",
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
                                            padding: 15
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Group Formation Trend (Last 6 Months)',
                                        font: { size: 16, weight: 'bold' }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return value + ' groups';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('Group Growth Trend', $content);
            $column->append($box);
        });

        // Value Chain Distribution
        $row->column(4, function (Column $column) {
            $valueChains = ['Maize', 'Beans', 'Sorghum', 'Groundnuts', 'Vegetables', 'Livestock'];
            $counts = [78, 62, 45, 32, 18, 12];
            $colors = ['#05179F', '#0652DD', '#3867d6', '#4b7bec', '#74b9ff', '#a29bfe'];
            
            $content = "
                <canvas id='valueChainChart' height='200'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('valueChainChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($valueChains) . ",
                                datasets: [{
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode($colors) . ",
                                    borderWidth: 2,
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
                                            padding: 10
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Groups by Value Chain',
                                        font: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('Value Chain Distribution', $content);
            $column->append($box);
        });
    }

    /**
     * Activity Summary
     */
    private function addActivitySummary(Row $row)
    {
        // District Coverage
        $row->column(4, function (Column $column) {
            $districts = [
                ['name' => 'Moroto', 'groups' => 34, 'farmers' => 782],
                ['name' => 'Kotido', 'groups' => 28, 'farmers' => 645],
                ['name' => 'Kaabong', 'groups' => 31, 'farmers' => 723],
                ['name' => 'Abim', 'groups' => 22, 'farmers' => 512],
                ['name' => 'Napak', 'groups' => 26, 'farmers' => 598],
                ['name' => 'Nakapiripirit', 'groups' => 29, 'farmers' => 672],
                ['name' => 'Amudat', 'groups' => 18, 'farmers' => 421],
                ['name' => 'Nabilatuk', 'groups' => 24, 'farmers' => 556],
                ['name' => 'Karenga', 'groups' => 35, 'farmers' => 925],
            ];
            
            $content = "
                <table class='table table-hover' style='margin-bottom:0; font-size: 13px;'>
                    <thead style='background: #f7f7f7;'>
                        <tr>
                            <th>District</th>
                            <th class='text-center'>Groups</th>
                            <th class='text-right'>Farmers</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($districts as $district) {
                $content .= "
                    <tr>
                        <td><i class='fa fa-map-marker text-primary'></i> <strong>" . $district['name'] . "</strong></td>
                        <td class='text-center'><span class='badge' style='background: #05179F;'>" . $district['groups'] . "</span></td>
                        <td class='text-right'>" . number_format($district['farmers']) . "</td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                    <tfoot style='background: #f7f7f7; font-weight: bold;'>
                        <tr>
                            <td>Total (9 Districts)</td>
                            <td class='text-center'><span class='badge' style='background: #4caf50;'>247</span></td>
                            <td class='text-right'>5,834</td>
                        </tr>
                    </tfoot>
                </table>
            ";
            
            $box = new Box('District Coverage', $content);
            $column->append($box);
        });

        // VSLA Financial Summary
        $row->column(4, function (Column $column) {
            $content = "
                <div style='padding: 15px;'>
                    <table class='table table-borderless' style='margin-bottom: 0;'>
                        <tbody>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-piggy-bank' style='color: #05179F;'></i> <strong>Total Savings</strong></td>
                                <td class='text-right'><strong style='color: #4caf50; font-size: 16px;'>UGX 456,234,500</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-hand-holding-usd' style='color: #05179F;'></i> Share Purchases</td>
                                <td class='text-right'>UGX 298,450,000</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-coins' style='color: #05179F;'></i> Loan Portfolio</td>
                                <td class='text-right'>UGX 187,890,000</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-wallet' style='color: #05179F;'></i> Group Funds</td>
                                <td class='text-right'>UGX 89,456,000</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-percentage' style='color: #05179F;'></i> Loan Repayment Rate</td>
                                <td class='text-right'><span style='color: #4caf50; font-weight: bold;'>94.5%</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-calendar' style='color: #05179F;'></i> Active Savings Cycles</td>
                                <td class='text-right'><strong>156</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style='background: #05179F; padding: 15px; text-align: center; color: white; margin-top: 15px;'>
                    <h3 style='margin: 0; font-size: 20px;'>183 Active Groups</h3>
                    <p style='margin: 5px 0 0 0; font-size: 13px;'>2,847 Members Participating</p>
                </div>
            ";
            
            $box = new Box('VSLA Financial Summary', $content);
            $column->append($box);
        });

        // Training & Field Activities
        $row->column(4, function (Column $column) {
            $content = "
                <div style='padding: 15px;'>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #c8e6c9;'>
                                <h2 style='margin: 0; color: #4caf50;'>1,456</h2>
                                <small style='color: #666;'>Training Sessions</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e3f2fd; border: 1px solid #bbdefb;'>
                                <h2 style='margin: 0; color: #2196f3;'>892</h2>
                                <small style='color: #666;'>AESA Records</small>
                            </div>
                        </div>
                    </div>
                    
                    <table class='table table-sm' style='margin-top: 15px; margin-bottom: 0;'>
                        <tbody>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-chalkboard-teacher' style='color: #05179F;'></i> Active Facilitators</td>
                                <td class='text-right'><strong>42</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-book-open' style='color: #05179F;'></i> Training Materials</td>
                                <td class='text-right'><strong>156</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-mobile-alt' style='color: #05179F;'></i> Tablets Deployed</td>
                                <td class='text-right'><strong>40</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-sync' style='color: #05179F;'></i> Data Sync Status</td>
                                <td class='text-right'><span style='color: #4caf50;'><i class='fa fa-check-circle'></i> Online</span></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-chart-line' style='color: #05179F;'></i> Avg Attendance</td>
                                <td class='text-right'><strong style='color: #4caf50;'>89%</strong></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-calendar-check' style='color: #05179F;'></i> Sessions This Week</td>
                                <td class='text-right'><strong>87</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            ";
            
            $box = new Box('Training & Field Activities', $content);
            $column->append($box);
        });
    }

    /**
     * Recent Activities
     */
    private function addRecentActivities(Row $row)
    {
        $row->column(6, function (Column $column) {
            $activities = [
                ['time' => '12 minutes ago', 'icon' => 'fa-users', 'color' => '#05179F', 'text' => 'New FFS Group registered in Moroto District', 'user' => 'John Okello', 'category' => 'Group Management'],
                ['time' => '1 hour ago', 'icon' => 'fa-book', 'color' => '#4caf50', 'text' => 'Training session completed: Maize Production Best Practices', 'user' => 'Mary Akello', 'category' => 'Training'],
                ['time' => '2 hours ago', 'icon' => 'fa-money', 'color' => '#ff9800', 'text' => 'VSLA meeting recorded: UGX 2,450,000 saved', 'user' => 'Peter Lokodo', 'category' => 'VSLA Finance'],
                ['time' => '3 hours ago', 'icon' => 'fa-mobile', 'color' => '#2196f3', 'text' => 'Tablet TB-023 synced 47 records successfully', 'user' => 'System', 'category' => 'System'],
                ['time' => '5 hours ago', 'icon' => 'fa-leaf', 'color' => '#8bc34a', 'text' => 'AESA record submitted: Pest observation in Kotido', 'user' => 'Sarah Longok', 'category' => 'Field Activity'],
                ['time' => 'Yesterday', 'icon' => 'fa-user-plus', 'color' => '#00bcd4', 'text' => '34 new farmers registered across 3 groups', 'user' => 'James Lomonyang', 'category' => 'Member Registration'],
                ['time' => 'Yesterday', 'icon' => 'fa-hand-holding-usd', 'color' => '#4caf50', 'text' => 'Loan disbursed: UGX 850,000 to 5 VSLA members', 'user' => 'Grace Akori', 'category' => 'VSLA Finance'],
                ['time' => '2 days ago', 'icon' => 'fa-file', 'color' => '#673ab7', 'text' => 'Monthly report generated for Napak District', 'user' => 'Admin User', 'category' => 'Reports'],
            ];
            
            $content = "
                <style>
                    .activity-timeline {
                        max-height: 450px;
                        overflow-y: auto;
                        padding: 0;
                        margin: 0;
                    }
                    .activity-item {
                        display: flex;
                        align-items: flex-start;
                        padding: 15px;
                        border-bottom: 1px solid #e8e8e8;
                        transition: background-color 0.2s ease;
                    }
                    .activity-item:hover {
                        background-color: #f5f7fa;
                    }
                    .activity-item:last-child {
                        border-bottom: none;
                    }
                    .activity-icon-wrapper {
                        width: 40px;
                        height: 40px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        margin-right: 15px;
                    }
                    .activity-icon {
                        width: 40px;
                        height: 40px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        color: white;
                    }
                    .activity-content {
                        flex: 1;
                        min-width: 0;
                    }
                    .activity-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 6px;
                    }
                    .activity-category {
                        display: inline-block;
                        padding: 2px 8px;
                        font-size: 10px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        background: #f0f0f0;
                        color: #666;
                    }
                    .activity-time {
                        font-size: 11px;
                        color: #999;
                        white-space: nowrap;
                    }
                    .activity-text {
                        margin: 0 0 8px 0;
                        font-size: 14px;
                        color: #333;
                        line-height: 1.5;
                    }
                    .activity-user {
                        display: flex;
                        align-items: center;
                        font-size: 12px;
                        color: #777;
                    }
                    .activity-user i {
                        margin-right: 5px;
                        font-size: 11px;
                    }
                </style>
                
                <div class='activity-timeline'>";
            
            foreach ($activities as $activity) {
                $content .= "
                    <div class='activity-item'>
                        <div class='activity-icon-wrapper'>
                            <div class='activity-icon' style='background: {$activity['color']};'>
                                <i class='fa {$activity['icon']}'></i>
                            </div>
                        </div>
                        <div class='activity-content'>
                            <div class='activity-header'>
                                <span class='activity-category'>{$activity['category']}</span>
                                <span class='activity-time'>{$activity['time']}</span>
                            </div>
                            <p class='activity-text'>{$activity['text']}</p>
                            <div class='activity-user'>
                                <i class='fa fa-user'></i>
                                <span>{$activity['user']}</span>
                            </div>
                        </div>
                    </div>";
            }
            
            $content .= "
                </div>
            ";
            
            $box = new Box('Recent System Activities', $content);
            $column->append($box);
        });

        // Gender & Impact Statistics
        $row->column(6, function (Column $column) {
            $content = "
                <div style='padding: 15px;'>
                    <h4 style='margin-top: 0; color: #333;'><i class='fa fa-venus-mars'></i> Gender Distribution</h4>
                    <div class='row' style='margin-bottom: 20px;'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 20px; background: #e8f5e9; border: 1px solid #c8e6c9;'>
                                <i class='fa fa-female' style='font-size: 32px; color: #4caf50;'></i>
                                <h2 style='margin: 10px 0 0 0; color: #4caf50;'>62%</h2>
                                <small style='color: #666;'>Female Farmers (3,619)</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 20px; background: #e3f2fd; border: 1px solid #bbdefb;'>
                                <i class='fa fa-male' style='font-size: 32px; color: #2196f3;'></i>
                                <h2 style='margin: 10px 0 0 0; color: #2196f3;'>38%</h2>
                                <small style='color: #666;'>Male Farmers (2,215)</small>
                            </div>
                        </div>
                    </div>
                    
                    <h4 style='margin-top: 20px; color: #333;'><i class='fa fa-chart-pie'></i> Impact Indicators</h4>
                    <table class='table table-sm' style='margin-bottom: 0;'>
                        <tbody>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-arrow-up' style='color: #4caf50;'></i> Yield Increase</td>
                                <td class='text-right'><strong style='color: #4caf50;'>+28%</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-money-bill' style='color: #4caf50;'></i> Income Increase</td>
                                <td class='text-right'><strong style='color: #4caf50;'>+34%</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-users' style='color: #05179F;'></i> Women Leadership</td>
                                <td class='text-right'><strong>58% of group leaders</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-graduation-cap' style='color: #ff9800;'></i> Knowledge Retention</td>
                                <td class='text-right'><strong style='color: #4caf50;'>87%</strong></td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e0e0e0;'>
                                <td><i class='fa fa-seedling' style='color: #4caf50;'></i> Practice Adoption</td>
                                <td class='text-right'><strong style='color: #4caf50;'>76%</strong></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-star' style='color: #ffc107;'></i> Farmer Satisfaction</td>
                                <td class='text-right'><strong style='color: #4caf50;'>4.6/5.0</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style='background: #05179F; padding: 15px; text-align: center; color: white; margin-top: 20px;'>
                        <h4 style='margin: 0;'>Program Coverage</h4>
                        <p style='margin: 5px 0; font-size: 18px; font-weight: bold;'>9 Districts | 247 Groups | 5,834 Farmers</p>
                        <small>FAO FFS-MIS Karamoja Digital Management System</small>
                    </div>
                </div>
            ";
            
            $box = new Box('Gender & Impact Statistics', $content);
            $column->append($box);
        });
    }

    /**
     * Helper: Render KPI Card
     */
    private function renderKPICard($mainValue, $title, $subtitle, $icon, $trend)
    {
        return "
            <div style='background: #05179F; padding: 20px; color: white; min-height: 160px;'>
                <div style='opacity: 0.2; font-size: 48px; text-align: center;'>
                    <i class='fa {$icon}'></i>
                </div>
                <div style='margin-top: -40px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 32px; color: white; font-weight: bold;'>{$mainValue}</h2>
                    <p style='margin: 10px 0 5px 0; color: white; font-size: 15px; font-weight: 600;'>{$title}</p>
                    <p style='margin: 0; color: rgba(255,255,255,0.85); font-size: 12px;'>{$subtitle}</p>
                    <div style='color: #4caf50; margin-top: 8px; font-size: 13px;'>
                        <i class='fa fa-arrow-up'></i> {$trend}
                    </div>
                </div>
            </div>
        ";
    }
}
