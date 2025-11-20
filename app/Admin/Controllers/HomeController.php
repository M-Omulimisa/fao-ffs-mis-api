<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Admin\Helpers\RoleBasedDashboard;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\Disbursement;
use App\Models\AccountTransaction;
use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\MedicalServiceRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\UniversalPayment;
use App\Models\MembershipPayment;
use Carbon\Carbon;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use RoleBasedDashboard;

    public function index(Content $content)
    {
        // Add Chart.js CDN
        Admin::script('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
        
        $content = $content
            ->title($this->getDashboardTitle())
            ->description($this->getDashboardDescription())
            ->row(function (Row $row) {
                // SECTION 1: KEY PERFORMANCE INDICATORS (4 columns) - ALL USERS
                $this->addKPISection($row);
            });

        // ADMIN ONLY SECTIONS - Detailed financial and analytics data
        if ($this->canSeeDetailedAnalytics()) {
            $content->row(function (Row $row) {
                // SECTION 2: REVENUE & FINANCIAL TRENDS (Chart)
                $this->addRevenueCharts($row);
            });
        }

        if ($this->canSeeFinancialDetails()) {
            $content->row(function (Row $row) {
                // SECTION 3: FINANCIAL OVERVIEW (2 columns)
                $this->addFinancialOverview($row);
            })
            ->row(function (Row $row) {
                // SECTION 4: PROJECT ANALYTICS WITH CHARTS (2 columns)
                $this->addProjectAnalytics($row);
            });
        }

        // SECTION 5: Basic Insurance Overview - ALL USERS (but simplified for managers)
        $content->row(function (Row $row) {
            $this->addInsuranceOverview($row);
        });

        // ADMIN ONLY SECTIONS - Payment gateway and detailed analytics
        if ($this->canSeePaymentDetails()) {
            $content->row(function (Row $row) {
                // SECTION 6: PAYMENT GATEWAY STATISTICS (Full width)
                $this->addPaymentGatewayStats($row);
            });
        }

        // SECTION 7: User & Order Analytics - ALL USERS (but simplified for managers)
        $content->row(function (Row $row) {
            $this->addUserOrderAnalytics($row);
        });

        // ADMIN ONLY SECTION - Recent Activities
        if ($this->canSeeDetailedAnalytics()) {
            $content->row(function (Row $row) {
                // SECTION 8: RECENT ACTIVITIES (Full width)
                $this->addRecentActivities($row);
            });
        }

        return $content;
    }

    /**
     * SECTION 1: Key Performance Indicators
     */
    private function addKPISection(Row $row)
    {
        // Total Revenue (All Income)
        $row->column(3, function (Column $column) {
            // Calculate accurate total income from all sources
            $totalRevenue = ProjectTransaction::where('type', 'income')->sum('amount');
            
            $monthRevenue = ProjectTransaction::where('type', 'income')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('amount');
            
            $lastMonthRevenue = ProjectTransaction::where('type', 'income')
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->sum('amount');
            
            $growth = $lastMonthRevenue > 0 
                ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) 
                : 0;
            
            $growthColor = $growth >= 0 ? '#4caf50' : '#f44336';
            $growthIcon = $growth >= 0 ? 'arrow-up' : 'arrow-down';
            $growthHtml = "<div style='color: {$growthColor}; margin-top: 5px; font-size: 14px;'><i class='fa fa-{$growthIcon}'></i> " . abs($growth) . "% vs last month</div>";
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 160px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <div style='opacity: 0.2; font-size: 48px; text-align: center;'>
                        <i class='fa fa-chart-line'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 32px; color: white; font-weight: bold;'>UGX " . number_format($totalRevenue, 0) . "</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95); font-size: 13px;'>This Month: UGX " . number_format($monthRevenue, 0) . "</p>
                        {$growthHtml}
                    </div>
                </div>
            ";
            
            $box = new Box('💰 Total Revenue', $content);
            $column->append($box);
        });

        // Active Projects with Accurate Balance
        $row->column(3, function (Column $column) {
            $activeProjects = Project::where('status', 'ongoing')->count();
            $totalProjects = Project::count();
            $completedProjects = Project::where('status', 'completed')->count();
            
            // Calculate accurate total available funds (balance)
            $projects = Project::where('status', 'ongoing')->get();
            $totalBalance = 0;
            foreach ($projects as $project) {
                $income = ProjectTransaction::where('project_id', $project->id)
                    ->where('type', 'income')
                    ->sum('amount');
                $expenses = ProjectTransaction::where('project_id', $project->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                $totalBalance += ($income - $expenses);
            }
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 160px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <div style='opacity: 0.2; font-size: 48px; text-align: center;'>
                        <i class='fa fa-project-diagram'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 32px; color: white; font-weight: bold;'>{$activeProjects} Active</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95); font-size: 13px;'>{$completedProjects} Completed | {$totalProjects} Total</p>
                        <div style='color: #fff; margin-top: 5px; font-size: 14px;'>Balance: UGX " . number_format($totalBalance, 0) . "</div>
                    </div>
                </div>
            ";
            
            $box = new Box('📊 Projects', $content);
            $column->append($box);
        });

        // Total Investors with Accurate Data
        $row->column(3, function (Column $column) {
            $totalInvestors = ProjectShare::distinct('investor_id')->count('investor_id');
            $newThisMonth = ProjectShare::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->distinct('investor_id')
                ->count('investor_id');
            
            // Accurate total investment (sum of all shares purchased)
            $totalInvestment = ProjectShare::sum('total_amount_paid');
            
            // Total disbursed to investors
            $totalDisbursed = Disbursement::sum('amount');
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 160px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <div style='opacity: 0.2; font-size: 48px; text-align: center;'>
                        <i class='fa fa-users'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 32px; color: white; font-weight: bold;'>{$totalInvestors}</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95); font-size: 13px;'>Invested: UGX " . number_format($totalInvestment, 0) . "</p>
                        <div style='color: #fff; margin-top: 5px; font-size: 14px;'>Disbursed: UGX " . number_format($totalDisbursed, 0) . "</div>
                    </div>
                </div>
            ";
            
            $box = new Box('👥 Investors', $content);
            $column->append($box);
        });

        // Insurance Subscribers with Accurate Financials
        $row->column(3, function (Column $column) {
            $activeSubscribers = InsuranceSubscription::where('status', 'active')->count();
            $totalSubscribers = InsuranceSubscription::count();
            
            // Accurate calculation of collected premiums
            $totalCollected = InsuranceSubscriptionPayment::where('payment_status', 'COMPLETED')
                ->sum('amount');
            
            // Pending payments
            $totalPending = InsuranceSubscriptionPayment::whereIn('payment_status', ['PENDING', 'PROCESSING'])
                ->sum('amount');
            
            $content = "
                <div style='background: #05179F; padding: 20px; color: white; min-height: 160px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <div style='opacity: 0.2; font-size: 48px; text-align: center;'>
                        <i class='fa fa-shield-alt'></i>
                    </div>
                    <div style='margin-top: -40px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 32px; color: white; font-weight: bold;'>{$activeSubscribers} Active</h2>
                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.95); font-size: 13px;'>Collected: UGX " . number_format($totalCollected, 0) . "</p>
                        <div style='color: #fff; margin-top: 5px; font-size: 14px;'>Pending: UGX " . number_format($totalPending, 0) . "</div>
                    </div>
                </div>
            ";
            
            $box = new Box('🏥 Insurance', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 2: Revenue & Financial Trends (Charts)
     */
    private function addRevenueCharts(Row $row)
    {
        // Revenue Trend Chart (Last 6 months)
        $row->column(8, function (Column $column) {
            $months = [];
            $income = [];
            $expenses = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthIncome = ProjectTransaction::where('type', 'income')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount');
                $income[] = $monthIncome;
                
                $monthExpenses = ProjectTransaction::where('type', 'expense')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount');
                $expenses[] = $monthExpenses;
            }
            
            $content = "
                <canvas id='revenueChart' height='80'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('revenueChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: " . json_encode($months) . ",
                                datasets: [{
                                    label: 'Income',
                                    data: " . json_encode($income) . ",
                                    borderColor: '#05179F',
                                    backgroundColor: 'rgba(5, 23, 159, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4
                                }, {
                                    label: 'Expenses',
                                    data: " . json_encode($expenses) . ",
                                    borderColor: '#f44336',
                                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
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
                                        text: 'Revenue vs Expenses Trend (Last 6 Months)',
                                        font: { size: 16, weight: 'bold' }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'UGX ' + value.toLocaleString();
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
            
            $box = new Box('📈 Financial Trend Analysis', $content);
            $column->append($box);
        });

        // Project Status Distribution (Doughnut Chart)
        $row->column(4, function (Column $column) {
            $statusCounts = Project::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();
            
            $statuses = [];
            $counts = [];
            $colors = [
                'pending' => '#ff9800',
                'ongoing' => '#05179F',
                'completed' => '#4caf50',
                'cancelled' => '#f44336'
            ];
            $bgColors = [];
            
            foreach ($statusCounts as $status) {
                $statuses[] = ucfirst($status->status);
                $counts[] = $status->count;
                $bgColors[] = $colors[$status->status] ?? '#9e9e9e';
            }
            
            $content = "
                <canvas id='projectStatusChart' height='200'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('projectStatusChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($statuses) . ",
                                datasets: [{
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode($bgColors) . ",
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
                                        text: 'Project Status Distribution',
                                        font: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('📊 Projects Overview', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 3: Financial Overview (ACCURATE CALCULATIONS)
     */
    private function addFinancialOverview(Row $row)
    {
        $row->column(6, function (Column $column) {
            // ACCURATE FINANCIAL DATA
            $totalIncome = ProjectTransaction::where('type', 'income')->sum('amount');
            $totalExpenses = ProjectTransaction::where('type', 'expense')->sum('amount');
            $currentBalance = $totalIncome - $totalExpenses;
            
            // Investment from shares
            $totalInvestment = ProjectShare::sum('total_amount_paid');
            
            // Total disbursed
            $totalDisbursed = Disbursement::sum('amount');
            
            // Available for disbursement (balance - already disbursed)
            $availableForDisbursement = max(0, $currentBalance - $totalDisbursed);
            
            $balanceColor = $currentBalance >= 0 ? '#4caf50' : '#f44336';
            
            $content = "
                <table class='table table-bordered table-striped' style='margin-bottom:0; font-size: 14px;'>
                    <thead style='background: #05179F; color: white;'>
                        <tr>
                            <th>Financial Metric</th>
                            <th class='text-right'>Amount (UGX)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style='background: #e8f5e9;'>
                            <td><i class='fa fa-arrow-up text-success'></i> <strong>Total Income</strong></td>
                            <td class='text-right'><strong style='color: #4caf50;'>" . number_format($totalIncome, 0) . "</strong></td>
                        </tr>
                        <tr style='background: #ffebee;'>
                            <td><i class='fa fa-arrow-down text-danger'></i> <strong>Total Expenses</strong></td>
                            <td class='text-right'><strong style='color: #f44336;'>" . number_format($totalExpenses, 0) . "</strong></td>
                        </tr>
                        <tr style='background: #e3f2fd; font-size: 16px;'>
                            <td><i class='fa fa-equals'></i> <strong>Current Balance</strong></td>
                            <td class='text-right'><strong style='color: {$balanceColor}; font-size: 18px;'>" . number_format($currentBalance, 0) . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-users text-info'></i> Total Investment (Shares)</td>
                            <td class='text-right'>" . number_format($totalInvestment, 0) . "</td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-hand-holding-usd text-success'></i> Disbursed to Investors</td>
                            <td class='text-right'>" . number_format($totalDisbursed, 0) . "</td>
                        </tr>
                        <tr style='background: #fff3e0;'>
                            <td><i class='fa fa-wallet text-warning'></i> <strong>Available for Disbursement</strong></td>
                            <td class='text-right'><strong style='color: #ff9800;'>" . number_format($availableForDisbursement, 0) . "</strong></td>
                        </tr>
                    </tbody>
                    <tfoot style='background: #f5f5f5; font-weight: bold;'>
                        <tr>
                            <td colspan='2' class='text-center' style='padding: 10px;'>
                                <small style='color: #666;'>✓ Formula: Balance = Income - Expenses</small>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            ";
            
            $box = new Box('💰 Investment & Financial Overview', $content);
            $column->append($box);
        });

        $row->column(6, function (Column $column) {
            // ACCURATE INSURANCE FINANCIALS
            $totalCompleted = InsuranceSubscriptionPayment::where('payment_status', 'COMPLETED')->sum('amount');
            $totalPending = InsuranceSubscriptionPayment::whereIn('payment_status', ['PENDING', 'PROCESSING'])->sum('amount');
            $totalFailed = InsuranceSubscriptionPayment::where('payment_status', 'FAILED')->sum('amount');
            $totalExpected = $totalCompleted + $totalPending + $totalFailed;
            
            $collectionRate = $totalExpected > 0 ? round(($totalCompleted / $totalExpected) * 100, 1) : 0;
            
            // Count of payments by status
            $completedCount = InsuranceSubscriptionPayment::where('payment_status', 'COMPLETED')->count();
            $pendingCount = InsuranceSubscriptionPayment::whereIn('payment_status', ['PENDING', 'PROCESSING'])->count();
            $failedCount = InsuranceSubscriptionPayment::where('payment_status', 'FAILED')->count();
            
            $medicalPending = MedicalServiceRequest::whereIn('status', ['pending', 'processing'])->count();
            
            $content = "
                <table class='table table-bordered table-striped' style='margin-bottom:0; font-size: 14px;'>
                    <thead style='background: #05179F; color: white;'>
                        <tr>
                            <th>Insurance Metric</th>
                            <th class='text-right'>Amount (UGX)</th>
                            <th class='text-center'>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style='background: #e8f5e9;'>
                            <td><i class='fa fa-check-circle text-success'></i> <strong>Completed</strong></td>
                            <td class='text-right'><strong style='color: #4caf50;'>" . number_format($totalCompleted, 0) . "</strong></td>
                            <td class='text-center'><span class='badge bg-success'>{$completedCount}</span></td>
                        </tr>
                        <tr style='background: #fff3e0;'>
                            <td><i class='fa fa-hourglass-half text-warning'></i> <strong>Pending</strong></td>
                            <td class='text-right'><strong style='color: #ff9800;'>" . number_format($totalPending, 0) . "</strong></td>
                            <td class='text-center'><span class='badge bg-warning'>{$pendingCount}</span></td>
                        </tr>
                        <tr style='background: #ffebee;'>
                            <td><i class='fa fa-times-circle text-danger'></i> Failed</td>
                            <td class='text-right' style='color: #f44336;'>" . number_format($totalFailed, 0) . "</td>
                            <td class='text-center'><span class='badge bg-danger'>{$failedCount}</span></td>
                        </tr>
                        <tr style='background: #e3f2fd; font-size: 15px;'>
                            <td><i class='fa fa-file-invoice-dollar text-primary'></i> <strong>Total Expected</strong></td>
                            <td class='text-right'><strong style='font-size: 16px;'>" . number_format($totalExpected, 0) . "</strong></td>
                            <td class='text-center'><span class='badge bg-primary'>" . ($completedCount + $pendingCount + $failedCount) . "</span></td>
                        </tr>
                        <tr style='background: #f1f8e9; font-size: 16px;'>
                            <td colspan='2'><i class='fa fa-percentage text-success'></i> <strong>Collection Rate</strong></td>
                            <td class='text-center'><strong style='color: #4caf50; font-size: 18px;'>" . $collectionRate . "%</strong></td>
                        </tr>
                        <tr>
                            <td colspan='2'><i class='fa fa-hand-holding-medical text-info'></i> Medical Requests (Pending)</td>
                            <td class='text-center'><span class='badge bg-info'>{$medicalPending}</span></td>
                        </tr>
                    </tbody>
                </table>
            ";
            
            $box = new Box('🏥 Insurance Financial Overview', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 4: Project & Investment Analytics WITH CHARTS
     */
    private function addProjectAnalytics(Row $row)
    {
        $row->column(6, function (Column $column) {
            $projects = Project::withCount('shares')
                ->orderBy('shares_count', 'desc')
                ->limit(5)
                ->get();
            
            // Calculate accurate balance for each project
            foreach ($projects as $project) {
                $income = ProjectTransaction::where('project_id', $project->id)
                    ->where('type', 'income')
                    ->sum('amount');
                $expenses = ProjectTransaction::where('project_id', $project->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                $project->accurate_balance = $income - $expenses;
            }
            
            $content = "
                <table class='table table-hover' style='margin-bottom:0; font-size: 13px;'>
                    <thead style='background: #05179F; color: white;'>
                        <tr>
                            <th>Project</th>
                            <th class='text-center'>Status</th>
                            <th class='text-right'>Balance</th>
                            <th class='text-right'>Investors</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($projects as $project) {
                $statusClass = [
                    'pending' => 'warning',
                    'ongoing' => 'primary',
                    'completed' => 'success',
                    'cancelled' => 'danger'
                ][$project->status] ?? 'default';
                
                $balanceColor = $project->accurate_balance >= 0 ? '#4caf50' : '#f44336';
                
                $content .= "
                    <tr>
                        <td><strong>" . \Illuminate\Support\Str::limit($project->title ?? 'Untitled Project', 30) . "</strong></td>
                        <td class='text-center'><span class='label label-{$statusClass}'>" . ucfirst($project->status) . "</span></td>
                        <td class='text-right'><strong style='color: {$balanceColor};'>UGX " . number_format($project->accurate_balance, 0) . "</strong></td>
                        <td class='text-center'><span class='badge bg-info'>" . $project->shares_count . "</span></td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                </table>
            ";
            
            $box = new Box('📊 Top 5 Projects by Investors', $content);
            $column->append($box);
        });

        // Investment Distribution Chart
        $row->column(6, function (Column $column) {
            $topProjects = Project::withCount('shares')
                ->orderBy('shares_count', 'desc')
                ->limit(6)
                ->get();
            
            $projectNames = [];
            $investmentAmounts = [];
            
            foreach ($topProjects as $project) {
                $projectNames[] = \Illuminate\Support\Str::limit($project->title ?? 'Untitled Project', 20);
                $totalInvested = ProjectShare::where('project_id', $project->id)
                    ->sum('total_amount_paid');
                $investmentAmounts[] = $totalInvested;
            }
            
            $content = "
                <canvas id='investmentChart' height='250'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('investmentChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($projectNames) . ",
                                datasets: [{
                                    label: 'Total Investment (UGX)',
                                    data: " . json_encode($investmentAmounts) . ",
                                    backgroundColor: [
                                        'rgba(5, 23, 159, 0.9)',
                                        'rgba(5, 23, 159, 0.8)',
                                        'rgba(5, 23, 159, 0.7)',
                                        'rgba(5, 23, 159, 0.6)',
                                        'rgba(5, 23, 159, 0.5)',
                                        'rgba(5, 23, 159, 0.4)'
                                    ],
                                    borderColor: '#fff',
                                    borderWidth: 2
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
                                        text: 'Investment Distribution by Project',
                                        font: { size: 14, weight: 'bold' }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                if (context.parsed !== null) {
                                                    label += 'UGX ' + context.parsed.toLocaleString();
                                                }
                                                return label;
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
            
            $box = new Box('💼 Investment Distribution', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 6: Payment Gateway Statistics
     */
    private function addPaymentGatewayStats(Row $row)
    {
        // Universal Payments Overview
        $row->column(4, function (Column $column) {
            $paymentStats = [
                'total' => UniversalPayment::count(),
                'completed' => UniversalPayment::where('status', 'COMPLETED')->count(),
                'pending' => UniversalPayment::where('status', 'PENDING')->count(),
                'failed' => UniversalPayment::where('status', 'FAILED')->count(),
                'total_amount' => UniversalPayment::sum('amount'),
                'completed_amount' => UniversalPayment::where('status', 'COMPLETED')->sum('amount'),
            ];
            
            $content = "
                <div style='padding: 10px;'>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #e0e0e0; margin-bottom: 10px; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #4caf50;'>" . $paymentStats['completed'] . "</h2>
                                <small>Completed</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #e0e0e0; margin-bottom: 10px; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #ff9800;'>" . $paymentStats['pending'] . "</h2>
                                <small>Pending</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #ffebee; border: 1px solid #e0e0e0; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #f44336;'>" . $paymentStats['failed'] . "</h2>
                                <small>Failed</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e3f2fd; border: 1px solid #e0e0e0; border-radius: 4px;'>
                                <h2 style='margin: 0; color: #2196f3;'>" . $paymentStats['total'] . "</h2>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: center; margin-top: 15px; padding: 15px; background: #05179F; border-radius: 4px; color: white;'>
                        <h3 style='margin: 0;'>UGX " . number_format($paymentStats['completed_amount'], 0) . "</h3>
                        <small>Total Collected</small>
                    </div>
                </div>
            ";
            
            $box = new Box('💳 Universal Payments', $content);
            $column->append($box);
        });

        // Payment Gateway Distribution (Pie Chart)
        $row->column(4, function (Column $column) {
            $gatewayStats = UniversalPayment::select('payment_gateway', DB::raw('count(*) as count'))
                ->groupBy('payment_gateway')
                ->get();
            
            $gateways = [];
            $counts = [];
            $colors = [
                'rgba(5, 23, 159, 0.9)',
                'rgba(5, 23, 159, 0.7)',
                'rgba(5, 23, 159, 0.5)',
                'rgba(5, 23, 159, 0.3)',
                '#ff9800',
                '#f44336'
            ];
            
            foreach ($gatewayStats as $index => $gateway) {
                $gateways[] = ucfirst($gateway->payment_gateway ?? 'Unknown');
                $counts[] = $gateway->count;
            }
            
            $content = "
                <canvas id='gatewayChart' height='250'></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('gatewayChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: " . json_encode($gateways) . ",
                                datasets: [{
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode(array_slice($colors, 0, count($gateways))) . ",
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
                                        text: 'Payment Gateway Distribution',
                                        font: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
            ";
            
            $box = new Box('🌐 Payment Gateways', $content);
            $column->append($box);
        });

        // Payment Type Distribution
        $row->column(4, function (Column $column) {
            $typeStats = UniversalPayment::select('payment_type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->where('status', 'COMPLETED')
                ->groupBy('payment_type')
                ->get();
            
            $content = "
                <table class='table table-sm table-hover' style='margin-bottom:0; font-size: 13px;'>
                    <thead style='background: #f5f5f5;'>
                        <tr>
                            <th>Payment Type</th>
                            <th class='text-center'>Count</th>
                            <th class='text-right'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($typeStats as $type) {
                $typeLabel = str_replace('_', ' ', $type->payment_type);
                $content .= "
                    <tr>
                        <td><strong>" . ucwords(strtolower($typeLabel)) . "</strong></td>
                        <td class='text-center'><span class='badge bg-primary'>" . $type->count . "</span></td>
                        <td class='text-right'><strong>UGX " . number_format($type->total, 0) . "</strong></td>
                    </tr>";
            }
            
            $total_count = $typeStats->sum('count');
            $total_amount = $typeStats->sum('total');
            
            $content .= "
                    </tbody>
                    <tfoot style='background: #f5f5f5; font-weight: bold;'>
                        <tr>
                            <td>Total</td>
                            <td class='text-center'><span class='badge bg-success'>{$total_count}</span></td>
                            <td class='text-right'>UGX " . number_format($total_amount, 0) . "</td>
                        </tr>
                    </tfoot>
                </table>
            ";
            
            $box = new Box('📊 Payment Types', $content);
            $column->append($box);
        });
    }

    /**
     * SECTION 5: Insurance System Overview
     */
    private function addInsuranceOverview(Row $row)
    {
        $row->column(4, function (Column $column) {
            $programs = InsuranceProgram::withCount('subscriptions')
                ->orderBy('subscriptions_count', 'desc')
                ->get();
            
            $content = "
                <table class='table table-sm table-hover' style='margin-bottom:0;'>
                    <thead style='background: #f0f0f0;'>
                        <tr>
                            <th>Program</th>
                            <th class='text-center'>Status</th>
                            <th class='text-right'>Subscribers</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($programs as $program) {
                $statusClass = $program->status === 'Active' ? 'success' : 'default';
                $content .= "
                    <tr>
                        <td><strong>" . $program->name . "</strong><br><small>UGX " . number_format($program->premium_amount, 0) . "/" . $program->billing_frequency . "</small></td>
                        <td class='text-center'><span class='label label-{$statusClass}'>" . $program->status . "</span></td>
                        <td class='text-right'><span class='badge bg-info'>" . $program->subscriptions_count . "</span></td>
                    </tr>";
            }
            
            $content .= "
                    </tbody>
                </table>
            ";
            
            $box = new Box('🏥 Insurance Programs', $content);
            $box->style('warning');
            $column->append($box);
        });

        $row->column(4, function (Column $column) {
            $stats = [
                'active' => InsuranceSubscription::where('status', 'active')->count(),
                'suspended' => InsuranceSubscription::where('status', 'suspended')->count(),
                'cancelled' => InsuranceSubscription::where('status', 'cancelled')->count(),
                'expired' => InsuranceSubscription::where('status', 'expired')->count(),
            ];
            
            $total = array_sum($stats);
            
            $content = "
                <div style='padding: 10px;'>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h2 style='margin: 0; color: #4caf50;'>" . $stats['active'] . "</h2>
                                <small>Active</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h2 style='margin: 0; color: #ff9800;'>" . $stats['suspended'] . "</h2>
                                <small>Suspended</small>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #ffebee; border: 1px solid #e0e0e0;'>
                                <h2 style='margin: 0; color: #f44336;'>" . $stats['cancelled'] . "</h2>
                                <small>Cancelled</small>
                            </div>
                        </div>
                        <div class='col-xs-6'>
                            <div style='text-align: center; padding: 15px; background: #f5f5f5; border: 1px solid #e0e0e0;'>
                                <h2 style='margin: 0; color: #9e9e9e;'>" . $stats['expired'] . "</h2>
                                <small>Expired</small>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: center; margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;'>
                        <h3 style='margin: 0;'>" . $total . "</h3>
                        <small>Total Subscriptions</small>
                    </div>
                </div>
            ";
            
            $box = new Box('📋 Subscription Status', $content);
            $box->style('info');
            $column->append($box);
        });

        $row->column(4, function (Column $column) {
            $requests = MedicalServiceRequest::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            $content = "
                <div style='padding: 10px;'>
                    <table class='table table-sm' style='margin-bottom: 0;'>
                        <tbody>
                            <tr>
                                <td><i class='fa fa-clock text-warning'></i> Pending</td>
                                <td class='text-right'><span class='badge bg-warning'>" . ($requests['pending'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-spinner text-primary'></i> Processing</td>
                                <td class='text-right'><span class='badge bg-primary'>" . ($requests['processing'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-calendar-check text-info'></i> Scheduled</td>
                                <td class='text-right'><span class='badge bg-info'>" . ($requests['scheduled'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-check-circle text-success'></i> Completed</td>
                                <td class='text-right'><span class='badge bg-success'>" . ($requests['completed'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-times-circle text-danger'></i> Cancelled</td>
                                <td class='text-right'><span class='badge bg-danger'>" . ($requests['cancelled'] ?? 0) . "</span></td>
                            </tr>
                            <tr>
                                <td><i class='fa fa-ban text-secondary'></i> Rejected</td>
                                <td class='text-right'><span class='badge bg-secondary'>" . ($requests['rejected'] ?? 0) . "</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style='text-align: center; margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;'>
                        <h3 style='margin: 0;'>" . array_sum($requests) . "</h3>
                        <small>Total Medical Requests</small>
                    </div>
                </div>
            ";
            
            $box = new Box('🏥 Medical Service Requests', $content);
            $box->style('danger');
            $column->append($box);
        });
    }

    /**
     * SECTION 7: User & Order Analytics
     */
    private function addUserOrderAnalytics(Row $row)
    {
        $row->column(6, function (Column $column) {
            $stats = [
                'total_users' => User::count(),
                'new_this_month' => User::whereMonth('created_at', Carbon::now()->month)->count(),
                'insurance_users' => User::where('user_type', 'Customer')->count(),
            ];
            
            // Admin sees detailed breakdown, Manager sees basic stats only
            if ($this->canSeeUserDetails()) {
                $stats['admins'] = User::where('user_type', 'admin')->count();
                $stats['vendors'] = User::where('user_type', 'vendor')->count();
                $stats['customers'] = User::where('user_type', 'customer')->count();
            }
            
            $content = "
                <div class='row' style='padding: 10px;'>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-users' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['total_users'] . "</h2>
                            <small>Total Users</small>
                        </div>
                    </div>";
            
            // Only show detailed user types to admin
            if ($this->canSeeUserDetails()) {
                $content .= "
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-user-shield' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['admins'] . "</h2>
                            <small>Administrators</small>
                        </div>
                    </div>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-store' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['vendors'] . "</h2>
                            <small>Vendors</small>
                        </div>
                    </div>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-shopping-bag' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['customers'] . "</h2>
                            <small>Customers</small>
                        </div>
                    </div>";
            }
            
            $content .= "
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-heartbeat' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['insurance_users'] . "</h2>
                            <small>Insurance Users</small>
                        </div>
                    </div>
                    <div class='col-md-4 col-sm-6'>
                        <div style='text-align: center; padding: 20px; background: #05179F; color: white; border: 1px solid #e0e0e0; margin-bottom: 15px;'>
                            <i class='fa fa-user-plus' style='font-size: 24px;'></i>
                            <h2 style='margin: 10px 0;'>" . $stats['new_this_month'] . "</h2>
                            <small>New This Month</small>
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('👥 User Statistics', $content);
            $box->style('primary');
            $column->append($box);
        });

        $row->column(6, function (Column $column) {
            $orderStats = [
                'total' => Order::count(),
                'pending' => Order::where('order_state', 0)->count(),
                'processing' => Order::where('order_state', 1)->count(),
                'completed' => Order::where('order_state', 2)->count(),
                'cancelled' => Order::whereIn('order_state', [3, 4])->count(), // canceled + failed
                'total_value' => Order::sum('order_total'),
            ];
            
            $products = Product::count();
            
            $content = "
                <div style='padding: 10px;'>
                    <div class='row'>
                        <div class='col-xs-4'>
                            <div style='text-align: center; padding: 15px; background: #e3f2fd; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h3 style='margin: 0; color: #1976d2;'>" . $orderStats['total'] . "</h3>
                                <small>Total Orders</small>
                            </div>
                        </div>
                        <div class='col-xs-4'>
                            <div style='text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h3 style='margin: 0; color: #f57c00;'>" . $orderStats['pending'] . "</h3>
                                <small>Pending</small>
                            </div>
                        </div>
                        <div class='col-xs-4'>
                            <div style='text-align: center; padding: 15px; background: #e8f5e9; border: 1px solid #e0e0e0; margin-bottom: 10px;'>
                                <h3 style='margin: 0; color: #388e3c;'>" . $orderStats['completed'] . "</h3>
                                <small>Completed</small>
                            </div>
                        </div>
                    </div>
                    <div style='background: #05179F; padding: 20px; border: 1px solid #e0e0e0; color: white; text-align: center; margin: 15px 0;'>
                        <h2 style='margin: 0;'>UGX " . number_format($orderStats['total_value'], 0) . "</h2>
                        <p style='margin: 5px 0 0 0;'>Total Order Value</p>
                    </div>
                    <table class='table table-sm' style='margin-bottom: 0;'>
                        <tr>
                            <td><i class='fa fa-box text-primary'></i> Total Products</td>
                            <td class='text-right'><strong>" . $products . "</strong></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-spinner text-info'></i> Processing</td>
                            <td class='text-right'><span class='badge bg-info'>" . $orderStats['processing'] . "</span></td>
                        </tr>
                        <tr>
                            <td><i class='fa fa-times-circle text-danger'></i> Cancelled</td>
                            <td class='text-right'><span class='badge bg-danger'>" . $orderStats['cancelled'] . "</span></td>
                        </tr>
                    </table>
                </div>
            ";
            
            $box = new Box('🛒 Order & Product Statistics', $content);
            $box->style('success');
            $column->append($box);
        });
    }

    /**
     * SECTION 8: Recent Activities
     */
    private function addRecentActivities(Row $row)
    {
        $row->column(12, function (Column $column) {
            // Recent Transactions
            $recentTransactions = ProjectTransaction::with(['project', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Recent Disbursements
            $recentDisbursements = Disbursement::with(['project', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $content = "
                <div class='row'>
                    <div class='col-md-8'>
                        <h4><i class='fa fa-exchange-alt'></i> Recent Project Transactions</h4>
                        <div style='max-height: 400px; overflow-y: auto;'>
                            <table class='table table-hover table-sm'>
                                <thead style='background: #f5f5f5;'>
                                    <tr>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>Type</th>
                                        <th class='text-right'>Amount</th>
                                        <th>By</th>
                                    </tr>
                                </thead>
                                <tbody>";
            
            foreach ($recentTransactions as $trans) {
                $typeClass = $trans->type === 'income' ? 'success' : 'danger';
                $icon = $trans->type === 'income' ? 'arrow-up' : 'arrow-down';
                
                $content .= "
                    <tr>
                        <td><small>" . $trans->created_at->format('d M, H:i') . "</small></td>
                        <td><strong>" . ($trans->project->title ?? 'N/A') . "</strong></td>
                        <td><span class='label label-{$typeClass}'><i class='fa fa-{$icon}'></i> " . ucfirst($trans->type) . "</span></td>
                        <td class='text-right'><strong>UGX " . number_format($trans->amount, 0) . "</strong></td>
                        <td><small>" . ($trans->creator->name ?? 'System') . "</small></td>
                    </tr>";
            }
            
            $content .= "
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class='col-md-4'>
                        <h4><i class='fa fa-hand-holding-usd'></i> Recent Disbursements</h4>
                        <div style='max-height: 400px; overflow-y: auto;'>";
            
            foreach ($recentDisbursements as $disbursement) {
                $content .= "
                    <div style='padding: 10px; margin-bottom: 10px; background: #f9f9f9; border-left: 4px solid #4caf50; border: 1px solid #e0e0e0;'>
                        <div><strong>" . ($disbursement->project->title ?? 'N/A') . "</strong></div>
                        <div style='color: #4caf50; font-size: 16px; font-weight: bold; margin: 5px 0;'>
                            UGX " . number_format($disbursement->amount, 0) . "
                        </div>
                        <div><small><i class='fa fa-calendar'></i> " . $disbursement->created_at->format('d M Y, H:i') . "</small></div>
                        <div><small><i class='fa fa-user'></i> " . ($disbursement->creator->name ?? 'System') . "</small></div>
                    </div>";
            }
            
            $content .= "
                        </div>
                    </div>
                </div>
            ";
            
            $box = new Box('🔄 Recent Activities', $content);
            $box->style('default');
            $column->append($box);
        });
    }

    /**
     * Helper: Create KPI Card
     */
    private function createKPICard($mainValue, $subText, $growth, $colorClass, $icon)
    {
        $growthHtml = $growth ? "<div style='color: #4caf50; margin-top: 5px;'><i class='fa fa-arrow-up'></i> {$growth}</div>" : '';
        
        return "
            <div style='text-align: center; padding: 15px;'>
                <div style='font-size: 48px; color: white; opacity: 0.2;'>
                    <i class='fa {$icon}'></i>
                </div>
                <div style='margin-top: -40px;'>
                    <h2 style='margin: 0; font-size: 28px; color: white;'>{$mainValue}</h2>
                    <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.9);'>{$subText}</p>
                    {$growthHtml}
                </div>
            </div>
        ";
    }

}
