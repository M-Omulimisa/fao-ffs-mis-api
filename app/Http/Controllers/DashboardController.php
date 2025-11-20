<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\MedicalServiceRequest;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get main dashboard data for logged-in user
     * This endpoint provides all data needed for the home tab
     */
    public function getMainDashboard(Request $request)
    {
        try {
            // First try to get user from JWT token
            $user = auth('api')->user();
            
            // Fallback to User-Id header
            if (!$user) {
                $userId = Utils::get_user_id();
                if ($userId && $userId > 0) {
                    $user = User::find($userId);
                }
            }
            
            if (!$user) {
                return Utils::error('Authentication required. Please log in.');
            }
            
            $userId = $user->id;

            // 1. Financial Overview
            $financialOverview = $this->getFinancialOverview($userId);

            // 2. Recent Medical Services (last 20 from any user)
            $recentMedicalServices = $this->getRecentMedicalServices();

            // 3. User's Quick Stats
            $quickStats = $this->getQuickStats($userId);

            return Utils::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'avatar' => $user->avatar,
                ],
                'financial_overview' => $financialOverview,
                'recent_medical_services' => $recentMedicalServices,
                'quick_stats' => $quickStats,
            ], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            return Utils::error('Failed to retrieve dashboard data: ' . $e->getMessage());
        }
    }

    /**
     * Get financial overview for user
     */
    private function getFinancialOverview($userId)
    {
        // Account Balance
        $accountBalance = AccountTransaction::where('user_id', $userId)->sum('amount');

        // Total Investments (sum of all share purchases by this user)
        $totalInvestments = ProjectShare::where('investor_id', $userId)
            ->sum('total_amount_paid');

        // Pending Insurance Payments
        $pendingInsurancePayments = InsuranceSubscriptionPayment::where('user_id', $userId)
            ->whereIn('payment_status', ['Pending', 'Partial', 'Overdue'])
            ->sum('amount');

        return [
            'account_balance' => $accountBalance,
            'formatted_account_balance' => 'UGX ' . number_format($accountBalance, 2),
            'total_investments' => $totalInvestments,
            'formatted_total_investments' => 'UGX ' . number_format($totalInvestments, 2),
            'pending_insurance_payments' => $pendingInsurancePayments,
            'formatted_pending_insurance_payments' => 'UGX ' . number_format($pendingInsurancePayments, 2),
        ];
    }

    /**
     * Get recent medical services from all users
     */
    private function getRecentMedicalServices()
    {
        $services = MedicalServiceRequest::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_name' => $service->service_name,
                    'service_type' => $service->service_type,
                    'status' => $service->status,
                    'status_label' => $service->status_label,
                    'user_name' => $service->user ? $service->user->name : 'N/A',
                    'user_phone' => $service->user ? $service->user->phone_number : 'N/A',
                    'cost' => $service->cost,
                    'formatted_cost' => 'UGX ' . number_format($service->cost, 2),
                    'description' => $service->description,
                    'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $service->created_at->format('d M Y'),
                ];
            });

        return $services;
    }

    /**
     * Get quick stats for user
     */
    private function getQuickStats($userId)
    {
        return [
            'total_transactions' => AccountTransaction::where('user_id', $userId)->count(),
            'active_subscriptions' => InsuranceSubscription::where('user_id', $userId)
                ->where('status', 'active')
                ->count(),
            'total_medical_requests' => MedicalServiceRequest::where('user_id', $userId)->count(),
            'active_investments' => ProjectShare::where('investor_id', $userId)->count(),
        ];
    }

    /**
     * Get pending insurance transactions for user
     */
    public function getPendingInsuranceTransactions(Request $request)
    {
        try {
            // First try to get user from JWT token
            $user = auth('api')->user();
            
            // Fallback to User-Id header
            if (!$user) {
                $userId = Utils::get_user_id();
                if ($userId && $userId > 0) {
                    $user = User::find($userId);
                }
            }
            
            if (!$user) {
                return Utils::error('Authentication required. Please log in.');
            }
            
            $userId = $user->id;

            $transactions = InsuranceSubscriptionPayment::where('user_id', $userId)
                ->with(['subscription.program'])
                ->whereIn('payment_status', ['Pending', 'Partial', 'Overdue'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'subscription_id' => $payment->insurance_subscription_id,
                        'program_name' => $payment->subscription && $payment->subscription->program 
                            ? $payment->subscription->program->name 
                            : 'N/A',
                        'amount' => $payment->amount,
                        'formatted_amount' => 'UGX ' . number_format($payment->amount, 2),
                        'period_name' => $payment->period_name,
                        'due_date' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                        'status' => $payment->payment_status,
                        'status_label' => $payment->payment_status,
                        'description' => $payment->period_name,
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                        'formatted_date' => $payment->created_at->format('d M Y'),
                        'can_retry' => in_array($payment->payment_status, ['Pending', 'Overdue']),
                    ];
                });

            $totalPending = $transactions->sum('amount');

            return Utils::success([
                'transactions' => $transactions,
                'total_pending' => $totalPending,
                'formatted_total_pending' => 'UGX ' . number_format($totalPending, 2),
                'count' => $transactions->count(),
            ], 'Pending insurance transactions retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Pending insurance transactions error: ' . $e->getMessage());
            return Utils::error('Failed to retrieve pending transactions: ' . $e->getMessage());
        }
    }

    /**
     * Get insurance overview for user
     * Returns total amount invested in insurance and pending payments
     */
    public function getInsuranceOverview(Request $request)
    {
        try {
            // First try to get user from JWT token
            $user = auth('api')->user();
            
            // Fallback to User-Id header
            if (!$user) {
                $userId = Utils::get_user_id();
                if ($userId && $userId > 0) {
                    $user = User::find($userId);
                }
            }
            
            if (!$user) {
                return Utils::error('Authentication required. Please log in.');
            }
            
            $userId = $user->id;

            // Get active subscriptions
            $activeSubscriptions = InsuranceSubscription::where('user_id', $userId)
                ->where('status', 'active')
                ->with(['insuranceProgram'])
                ->get();

            // Calculate total amount invested (all paid payments)
            $totalInvested = InsuranceSubscriptionPayment::where('user_id', $userId)
                ->where('payment_status', 'Paid')
                ->sum('paid_amount');

            // Calculate pending payments
            $pendingPayments = InsuranceSubscriptionPayment::where('user_id', $userId)
                ->whereIn('payment_status', ['Pending', 'Partial', 'Overdue'])
                ->sum('amount');

            // Get recent medical service requests by user
            $recentRequests = MedicalServiceRequest::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'service_name' => $service->service_name,
                        'service_type' => $service->service_type,
                        'status' => $service->status,
                        'status_label' => $service->status_label,
                        'cost' => $service->cost,
                        'formatted_cost' => 'UGX ' . number_format($service->cost ?? 0, 2),
                        'description' => $service->description,
                        'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                        'formatted_date' => $service->created_at->format('d M Y'),
                    ];
                });

            // Get statistics
            $stats = [
                'total_requests' => MedicalServiceRequest::where('user_id', $userId)->count(),
                'pending_requests' => MedicalServiceRequest::where('user_id', $userId)
                    ->where('status', 'pending')
                    ->count(),
                'approved_requests' => MedicalServiceRequest::where('user_id', $userId)
                    ->where('status', 'approved')
                    ->count(),
                'completed_requests' => MedicalServiceRequest::where('user_id', $userId)
                    ->where('status', 'completed')
                    ->count(),
            ];

            return Utils::success([
                'active_subscriptions_count' => $activeSubscriptions->count(),
                'total_invested' => $totalInvested,
                'formatted_total_invested' => 'UGX ' . number_format($totalInvested, 2),
                'pending_payments' => $pendingPayments,
                'formatted_pending_payments' => 'UGX ' . number_format($pendingPayments, 2),
                'active_subscriptions' => $activeSubscriptions->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'program_name' => $subscription->insuranceProgram ? $subscription->insuranceProgram->name : 'N/A',
                        'start_date' => $subscription->start_date ? $subscription->start_date->format('d M Y') : 'N/A',
                        'end_date' => $subscription->end_date ? $subscription->end_date->format('d M Y') : 'N/A',
                        'status' => $subscription->status,
                    ];
                }),
                'recent_medical_requests' => $recentRequests,
                'statistics' => $stats,
            ], 'Insurance overview retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Insurance overview error: ' . $e->getMessage());
            return Utils::error('Failed to retrieve insurance overview: ' . $e->getMessage());
        }
    }

    /**
     * Get investments overview for user
     * Returns comprehensive investment portfolio data
     */
    public function getInvestmentsOverview(Request $request)
    {
        try {
            // First try to get user from JWT token
            $user = auth('api')->user();
            
            // Fallback to User-Id header
            if (!$user) {
                $userId = Utils::get_user_id();
                if ($userId && $userId > 0) {
                    $user = User::find($userId);
                }
            }
            
            if (!$user) {
                return Utils::error('Authentication required. Please log in.');
            }
            
            $userId = $user->id;

            // Get all user's shares
            $userShares = ProjectShare::where('investor_id', $userId)
                ->with(['project'])
                ->get();

            // Calculate total investment (sum of all share purchases)
            $totalInvestment = $userShares->sum('total_amount_paid');

            // Get all project transactions related to user's investments (returns/disbursements)
            $projectIds = $userShares->pluck('project_id')->unique();
            
            // Calculate user's share of returns from their projects
            $totalReturns = 0;
            $activeProjects = 0;
            $completedProjects = 0;
            $projectsData = [];

            foreach ($projectIds as $projectId) {
                $project = Project::find($projectId);
                if (!$project) continue;

                // Get user's shares in this project
                $userSharesInProject = $userShares->where('project_id', $projectId);
                $userShareCount = $userSharesInProject->sum('number_of_shares');
                $userInvestmentInProject = $userSharesInProject->sum('total_amount_paid');

                // Calculate user's ownership percentage
                $ownershipPercentage = $project->total_shares > 0 
                    ? ($userShareCount / $project->total_shares) * 100 
                    : 0;

                // Calculate user's share of profits (proportional to ownership)
                $userShareOfProfits = ($project->total_profits ?? 0) * ($ownershipPercentage / 100);
                $totalReturns += $userShareOfProfits;

                // Count project status
                if ($project->status === 'active' || $project->status === 'Active') {
                    $activeProjects++;
                } elseif ($project->status === 'completed' || $project->status === 'Completed') {
                    $completedProjects++;
                }

                $projectsData[] = [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status,
                    'status_label' => $project->status_label ?? ucfirst($project->status),
                    'user_shares' => $userShareCount,
                    'user_investment' => $userInvestmentInProject,
                    'formatted_user_investment' => 'UGX ' . number_format($userInvestmentInProject, 2),
                    'ownership_percentage' => round($ownershipPercentage, 2),
                    'user_share_of_profits' => $userShareOfProfits,
                    'formatted_user_profits' => 'UGX ' . number_format($userShareOfProfits, 2),
                    'project_roi' => $project->roi_percentage ?? 0,
                    'start_date' => $project->start_date ? $project->start_date->format('d M Y') : 'N/A',
                    'end_date' => $project->end_date ? $project->end_date->format('d M Y') : 'N/A',
                ];
            }

            // Calculate current portfolio value (investment + returns)
            $currentPortfolioValue = $totalInvestment + $totalReturns;

            // Calculate overall ROI
            $overallROI = $totalInvestment > 0 
                ? (($totalReturns / $totalInvestment) * 100) 
                : 0;

            // Get recent share purchases
            $recentPurchases = ProjectShare::where('investor_id', $userId)
                ->with(['project'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($share) {
                    return [
                        'id' => $share->id,
                        'project_name' => $share->project ? $share->project->title : 'N/A',
                        'number_of_shares' => $share->number_of_shares,
                        'amount_paid' => $share->total_amount_paid,
                        'formatted_amount' => 'UGX ' . number_format($share->total_amount_paid, 2),
                        'share_price' => $share->share_price_at_purchase,
                        'formatted_share_price' => 'UGX ' . number_format($share->share_price_at_purchase, 2),
                        'purchase_date' => $share->purchase_date ? $share->purchase_date->format('d M Y') : 'N/A',
                        'created_at' => $share->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Get available projects (all active/ongoing projects)
            $availableProjects = Project::whereIn('status', ['active', 'ongoing', 'Active', 'Ongoing'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($project) {
                    $availableShares = $project->total_shares - $project->shares_sold;
                    $progressPercentage = $project->total_shares > 0 
                        ? ($project->shares_sold / $project->total_shares) * 100 
                        : 0;

                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'description' => $project->description,
                        'share_price' => $project->share_price,
                        'formatted_share_price' => 'UGX ' . number_format($project->share_price, 2),
                        'total_shares' => $project->total_shares,
                        'shares_sold' => $project->shares_sold,
                        'available_shares' => $availableShares,
                        'progress_percentage' => round($progressPercentage, 1),
                        'total_investment' => $project->total_investment,
                        'formatted_total_investment' => 'UGX ' . number_format($project->total_investment, 2),
                        'roi_percentage' => $project->roi_percentage ?? 0,
                        'status' => $project->status,
                        'status_label' => $project->status_label ?? ucfirst($project->status),
                        'start_date' => $project->start_date ? $project->start_date->format('d M Y') : 'N/A',
                        'end_date' => $project->end_date ? $project->end_date->format('d M Y') : 'N/A',
                    ];
                });

            // Statistics
            $statistics = [
                'total_projects_invested' => $projectIds->count(),
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'total_shares_owned' => $userShares->sum('number_of_shares'),
                'total_available_projects' => Project::whereIn('status', ['active', 'ongoing'])
                    ->whereRaw('shares_sold < total_shares')
                    ->count(),
            ];

            return Utils::success([
                'total_investment' => $totalInvestment,
                'formatted_total_investment' => 'UGX ' . number_format($totalInvestment, 2),
                'total_returns' => $totalReturns,
                'formatted_total_returns' => 'UGX ' . number_format($totalReturns, 2),
                'current_portfolio_value' => $currentPortfolioValue,
                'formatted_portfolio_value' => 'UGX ' . number_format($currentPortfolioValue, 2),
                'overall_roi' => round($overallROI, 2),
                'formatted_roi' => round($overallROI, 2) . '%',
                'user_projects' => $projectsData,
                'recent_purchases' => $recentPurchases,
                'available_projects' => $availableProjects,
                'statistics' => $statistics,
            ], 'Investments overview retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Investments overview error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return Utils::error('Failed to retrieve investments overview: ' . $e->getMessage());
        }
    }

    /**
     * Get comprehensive My Account overview
     * Returns: Account balance, transactions, investments, insurance, and profile info
     */
    public function getMyAccountOverview(Request $request)
    {
        try {
            // Get authenticated user
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required');
            }

            $user = User::findOrFail($userId);

            // === ACCOUNT BALANCE & TRANSACTIONS ===
            $accountBalance = AccountTransaction::where('user_id', $userId)->sum('amount');
            
            $totalDisbursements = AccountTransaction::where('user_id', $userId)
                ->where('source', 'disbursement')
                ->sum('amount');

            $totalDeposits = AccountTransaction::where('user_id', $userId)
                ->where('source', 'deposit')
                ->sum('amount');

            $totalWithdrawals = abs(AccountTransaction::where('user_id', $userId)
                ->where('source', 'withdrawal')
                ->sum('amount'));

            $totalAccountTransactions = AccountTransaction::where('user_id', $userId)->count();

            // Recent account transactions (last 5)
            $recentTransactions = AccountTransaction::where('user_id', $userId)
                ->with(['creator', 'relatedDisbursement'])
                ->orderBy('transaction_date', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'formatted_amount' => 'UGX ' . number_format($transaction->amount, 2),
                        'transaction_date' => $transaction->transaction_date->format('d M Y'),
                        'description' => $transaction->description,
                        'source' => $transaction->source,
                        'source_label' => ucfirst(str_replace('_', ' ', $transaction->source)),
                        'type' => $transaction->type,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // === INVESTMENT SUMMARY ===
            $userShares = ProjectShare::where('investor_id', $userId)->get();
            $totalInvestment = $userShares->sum('total_amount_paid');
            
            // Calculate returns from all projects
            $totalReturns = 0;
            $projectIds = $userShares->pluck('project_id')->unique();
            $activeInvestments = 0;
            $completedInvestments = 0;
            
            foreach ($projectIds as $projectId) {
                $project = Project::find($projectId);
                if (!$project) continue;

                // Count active/completed
                if ($project->status == 'active' || $project->status == 'ongoing') {
                    $activeInvestments++;
                } elseif ($project->status == 'completed') {
                    $completedInvestments++;
                }

                // Calculate user's share of profits
                $projectShares = $userShares->where('project_id', $projectId);
                $userShareCount = $projectShares->sum('number_of_shares');
                $totalProjectShares = $project->total_shares;
                
                if ($totalProjectShares > 0) {
                    $ownershipPercentage = ($userShareCount / $totalProjectShares) * 100;
                    $userShareOfProfits = ($project->profits * $ownershipPercentage) / 100;
                    $totalReturns += $userShareOfProfits;
                }
            }

            $portfolioValue = $totalInvestment + $totalReturns;
            $investmentROI = $totalInvestment > 0 ? ($totalReturns / $totalInvestment) * 100 : 0;

            // === INSURANCE SUMMARY ===
            $activeSubscriptions = InsuranceSubscription::where('user_id', $userId)
                ->where('status', 'Active')
                ->count();

            $totalInsuranceInvested = InsuranceSubscriptionPayment::where('user_id', $userId)
                ->sum('amount');

            $pendingInsurancePayments = InsuranceSubscriptionPayment::where('user_id', $userId)
                ->where('payment_status', 'pending')
                ->count();

            $medicalServicesRequested = MedicalServiceRequest::where('user_id', $userId)->count();
            $approvedMedicalServices = MedicalServiceRequest::where('user_id', $userId)
                ->where('status', 'approved')
                ->count();

            // === USER PROFILE INFO ===
            $userInfo = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? 'N/A',
                'user_type' => $user->user_type ?? 'Customer',
                'avatar' => $user->avatar ?? null,
                'member_since' => $user->created_at->format('M Y'),
                'account_age_days' => $user->created_at->diffInDays(now()),
            ];

            // === QUICK STATISTICS ===
            $quickStats = [
                'total_projects_invested' => $projectIds->count(),
                'total_shares_owned' => $userShares->sum('number_of_shares'),
                'active_subscriptions' => $activeSubscriptions,
                'total_transactions' => $totalAccountTransactions,
            ];

            // === ACTIVITY SUMMARY (Last 30 days) ===
            $lastMonthTransactions = AccountTransaction::where('user_id', $userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $lastMonthInvestments = ProjectShare::where('investor_id', $userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $activitySummary = [
                'last_30_days_transactions' => $lastMonthTransactions,
                'last_30_days_investments' => $lastMonthInvestments,
                'last_login' => now()->format('d M Y H:i'),
            ];

            return Utils::success([
                // User Information
                'user' => $userInfo,
                
                // Account Balance Overview
                'account_balance' => $accountBalance,
                'formatted_balance' => 'UGX ' . number_format($accountBalance, 2),
                
                // Account Transaction Summary
                'account_transactions' => [
                    'total_disbursements' => $totalDisbursements,
                    'formatted_disbursements' => 'UGX ' . number_format($totalDisbursements, 2),
                    'total_deposits' => $totalDeposits,
                    'formatted_deposits' => 'UGX ' . number_format($totalDeposits, 2),
                    'total_withdrawals' => $totalWithdrawals,
                    'formatted_withdrawals' => 'UGX ' . number_format($totalWithdrawals, 2),
                    'total_count' => $totalAccountTransactions,
                ],
                
                // Investment Summary
                'investments' => [
                    'total_investment' => $totalInvestment,
                    'formatted_investment' => 'UGX ' . number_format($totalInvestment, 2),
                    'total_returns' => $totalReturns,
                    'formatted_returns' => 'UGX ' . number_format($totalReturns, 2),
                    'portfolio_value' => $portfolioValue,
                    'formatted_portfolio' => 'UGX ' . number_format($portfolioValue, 2),
                    'roi' => round($investmentROI, 2),
                    'formatted_roi' => round($investmentROI, 2) . '%',
                    'active_projects' => $activeInvestments,
                    'completed_projects' => $completedInvestments,
                    'total_shares' => $userShares->sum('number_of_shares'),
                ],
                
                // Insurance Summary
                'insurance' => [
                    'active_subscriptions' => $activeSubscriptions,
                    'total_invested' => $totalInsuranceInvested,
                    'formatted_invested' => 'UGX ' . number_format($totalInsuranceInvested, 2),
                    'pending_payments' => $pendingInsurancePayments,
                    'medical_services_requested' => $medicalServicesRequested,
                    'medical_services_approved' => $approvedMedicalServices,
                ],
                
                // Recent Transactions
                'recent_transactions' => $recentTransactions,
                
                // Quick Stats
                'quick_stats' => $quickStats,
                
                // Activity Summary
                'activity_summary' => $activitySummary,
            ], 'My account overview retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('My account overview error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return Utils::error('Failed to retrieve account overview: ' . $e->getMessage());
        }
    }
}
