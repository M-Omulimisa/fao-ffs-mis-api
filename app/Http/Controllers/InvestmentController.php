<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentController extends Controller
{
    /**
     * Get comprehensive investment dashboard for user
     * GET /api/investments/dashboard
     */
    public function getDashboard(Request $request)
    {
        try {
            $userId = $request->user_id ?? Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID required',
                ], 400);
            }

            // Get user's shares with project details
            $shares = ProjectShare::where('investor_id', $userId)
                ->with(['project' => function ($query) {
                    $query->select('id', 'title', 'status', 'share_price', 'total_shares', 'shares_sold', 'total_investment', 'total_profits', 'total_expenses', 'image');
                }])
                ->get();

            // Calculate totals
            $totalInvested = $shares->sum('total_amount_paid');
            $totalShares = $shares->sum('number_of_shares');
            $totalProjects = $shares->unique('project_id')->count();

            // Calculate current value and projected returns
            $currentValue = 0;
            $projectedProfits = 0;
            $realizedProfits = 0;

            $portfolioBreakdown = [];

            foreach ($shares as $share) {
                $project = $share->project;
                
                if ($project) {
                    // Current share value (may have appreciated)
                    $currentShareValue = $project->share_price * $share->number_of_shares;
                    $currentValue += $currentShareValue;

                    // Calculate this investment's share of project profits
                    // Share of profits = (user's shares / total shares) * project's net profit
                    if ($project->total_shares > 0) {
                        $profitShare = ($share->number_of_shares / $project->total_shares) * ($project->total_profits - $project->total_expenses);
                        
                        if ($project->status === 'completed') {
                            $realizedProfits += $profitShare;
                        } else {
                            $projectedProfits += $profitShare;
                        }
                    }

                    // Portfolio breakdown
                    $investmentAmount = $share->total_amount_paid;
                    $percentage = $totalInvested > 0 ? ($investmentAmount / $totalInvested) * 100 : 0;

                    $portfolioBreakdown[] = [
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'project_status' => $project->status,
                        'project_image' => $project->image,
                        'shares_owned' => $share->number_of_shares,
                        'amount_invested' => floatval($investmentAmount),
                        'current_value' => floatval($currentShareValue),
                        'percentage_of_portfolio' => round($percentage, 2),
                        'profit_share' => floatval($profitShare ?? 0),
                        'status_label' => $project->status_label,
                    ];
                }
            }

            // Calculate total returns
            $totalReturns = $realizedProfits + $projectedProfits;
            $totalProfitLoss = ($currentValue + $realizedProfits) - $totalInvested;

            // Calculate ROI
            $roi = $totalInvested > 0 ? (($totalProfitLoss / $totalInvested) * 100) : 0;

            // Get recent transactions
            $recentTransactions = ProjectTransaction::whereHas('project.shares', function ($query) use ($userId) {
                $query->where('investor_id', $userId);
            })
                ->with(['project:id,title,image', 'creator:id,name'])
                ->orderBy('transaction_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'project_id' => $transaction->project_id,
                        'project_title' => $transaction->project ? $transaction->project->title : 'N/A',
                        'project_image' => $transaction->project ? $transaction->project->image : null,
                        'amount' => floatval($transaction->amount),
                        'type' => $transaction->type ?? 'expense',
                        'type_label' => $transaction->type_label ?? 'Expense',
                        'source' => $transaction->source ?? 'manual',
                        'source_label' => $transaction->source_label ?? 'Manual',
                        'description' => $transaction->description ?? '',
                        'transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : now()->format('Y-m-d'),
                        'formatted_amount' => $transaction->formatted_amount ?? '0',
                        'is_user_transaction' => false,
                        'created_by' => $transaction->creator ? $transaction->creator->name : 'System',
                    ];
                });

            // Summary statistics
            $summary = [
                'total_invested' => floatval($totalInvested),
                'current_value' => floatval($currentValue),
                'total_shares' => $totalShares,
                'total_projects' => $totalProjects,
                'realized_profits' => floatval($realizedProfits),
                'projected_profits' => floatval($projectedProfits),
                'total_returns' => floatval($totalReturns),
                'total_profit_loss' => floatval($totalProfitLoss),
                'roi_percentage' => round($roi, 2),
                'active_projects' => $shares->where('project.status', 'ongoing')->unique('project_id')->count(),
                'completed_projects' => $shares->where('project.status', 'completed')->unique('project_id')->count(),
            ];

            // Performance metrics
            $performance = [
                'best_performing' => null,
                'worst_performing' => null,
                'average_roi' => 0,
            ];

            if (count($portfolioBreakdown) > 0) {
                $performanceData = collect($portfolioBreakdown)->map(function ($item) {
                    $invested = $item['amount_invested'];
                    $current = $item['current_value'] + $item['profit_share'];
                    $roi = $invested > 0 ? (($current - $invested) / $invested) * 100 : 0;
                    return [
                        'project_title' => $item['project_title'],
                        'roi' => $roi,
                    ];
                });

                $performance['best_performing'] = $performanceData->sortByDesc('roi')->first();
                $performance['worst_performing'] = $performanceData->sortBy('roi')->first();
                $performance['average_roi'] = round($performanceData->avg('roi'), 2);
            }

            Log::info('Investment dashboard generated', [
                'user_id' => $userId,
                'total_invested' => $totalInvested,
                'roi' => $roi,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'portfolio_breakdown' => $portfolioBreakdown,
                    'recent_transactions' => $recentTransactions,
                    'performance' => $performance,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate investment dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load investment dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's project shares
     * GET /api/investments/shares
     */
    public function getMyShares(Request $request)
    {
        try {
            $userId = $request->user_id ?? Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID required',
                ], 400);
            }

            $shares = ProjectShare::where('investor_id', $userId)
                ->with([
                    'project' => function ($query) {
                        $query->select('id', 'title', 'description', 'status', 'share_price', 'total_shares', 'shares_sold', 'total_profits', 'total_expenses', 'image', 'start_date', 'end_date');
                    },
                    'payment' => function ($query) {
                        $query->select('id', 'payment_reference', 'amount', 'status', 'payment_date');
                    }
                ])
                ->orderBy('purchase_date', 'desc')
                ->get()
                ->map(function ($share) {
                    $project = $share->project;
                    
                    // Calculate current value
                    $currentValue = $project ? ($project->share_price * $share->number_of_shares) : 0;
                    $profitLoss = $currentValue - $share->total_amount_paid;
                    $roi = $share->total_amount_paid > 0 ? (($profitLoss / $share->total_amount_paid) * 100) : 0;

                    // Calculate profit share from project
                    $profitShare = 0;
                    if ($project && $project->total_shares > 0) {
                        $netProfit = $project->total_profits - $project->total_expenses;
                        $profitShare = ($share->number_of_shares / $project->total_shares) * $netProfit;
                    }

                    return [
                        'id' => $share->id,
                        'project_id' => $share->project_id,
                        'project_title' => $project ? $project->title : 'N/A',
                        'project_status' => $project ? $project->status : 'unknown',
                        'project_image' => $project ? $project->image : null,
                        'purchase_date' => $share->purchase_date->format('Y-m-d'),
                        'number_of_shares' => $share->number_of_shares,
                        'purchase_price' => floatval($share->share_price_at_purchase),
                        'current_price' => $project ? floatval($project->share_price) : 0,
                        'amount_invested' => floatval($share->total_amount_paid),
                        'current_value' => floatval($currentValue),
                        'profit_loss' => floatval($profitLoss),
                        'profit_share' => floatval($profitShare),
                        'total_return' => floatval($profitLoss + $profitShare),
                        'roi_percentage' => round($roi, 2),
                        'payment_reference' => $share->payment ? $share->payment->payment_reference : null,
                        'project_start_date' => $project && $project->start_date ? $project->start_date->format('Y-m-d') : null,
                        'project_end_date' => $project && $project->end_date ? $project->end_date->format('Y-m-d') : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $shares,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch user shares', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load shares',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's project transactions
     * GET /api/investments/transactions
     */
    public function getMyTransactions(Request $request)
    {
        try {
            $userId = $request->user_id ?? Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID required',
                ], 400);
            }

            // Get transactions for projects where user has shares
            $userProjectIds = ProjectShare::where('investor_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $transactions = ProjectTransaction::whereIn('project_id', $userProjectIds)
                ->with([
                    'project' => function ($query) {
                        $query->select('id', 'title', 'image', 'status');
                    },
                    'creator' => function ($query) {
                        $query->select('id', 'name', 'email');
                    },
                    'relatedShare' => function ($query) {
                        $query->select('id', 'investor_id', 'number_of_shares');
                    }
                ])
                ->orderBy('transaction_date', 'desc')
                ->get()
                ->map(function ($transaction) use ($userId) {
                    // Check if this transaction is related to user's share
                    $isUserTransaction = false;
                    if ($transaction->relatedShare && $transaction->relatedShare->investor_id == $userId) {
                        $isUserTransaction = true;
                    }

                    return [
                        'id' => $transaction->id,
                        'project_id' => $transaction->project_id,
                        'project_title' => $transaction->project ? $transaction->project->title : 'N/A',
                        'project_image' => $transaction->project ? $transaction->project->image : null,
                        'amount' => floatval($transaction->amount),
                        'type' => $transaction->type ?? 'expense',
                        'type_label' => $transaction->type_label ?? 'Expense',
                        'source' => $transaction->source ?? 'manual',
                        'source_label' => $transaction->source_label ?? 'Manual',
                        'description' => $transaction->description ?? '',
                        'transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : now()->format('Y-m-d'),
                        'formatted_amount' => $transaction->formatted_amount ?? '0',
                        'is_user_transaction' => $isUserTransaction,
                        'created_by' => $transaction->creator ? $transaction->creator->name : 'System',
                    ];
                });

            // Summary
            $summary = [
                'total_transactions' => $transactions->count(),
                'total_income' => floatval($transactions->where('type', 'income')->sum('amount')),
                'total_expenses' => floatval($transactions->where('type', 'expense')->sum('amount')),
                'net_amount' => floatval($transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount')),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'summary' => $summary,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch user transactions', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed share information
     * GET /api/investments/shares/{id}
     */
    public function getShareDetails($id)
    {
        try {
            $share = ProjectShare::with([
                'project',
                'investor',
                'payment'
            ])->findOrFail($id);

            $project = $share->project;

            // Calculate metrics
            $currentValue = $project ? ($project->share_price * $share->number_of_shares) : 0;
            $profitLoss = $currentValue - $share->total_amount_paid;
            $roi = $share->total_amount_paid > 0 ? (($profitLoss / $share->total_amount_paid) * 100) : 0;

            // Calculate profit share
            $profitShare = 0;
            if ($project && $project->total_shares > 0) {
                $netProfit = $project->total_profits - $project->total_expenses;
                $profitShare = ($share->number_of_shares / $project->total_shares) * $netProfit;
            }

            // Ownership percentage
            $ownershipPercentage = $project && $project->total_shares > 0 
                ? ($share->number_of_shares / $project->total_shares) * 100 
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'share' => $share,
                    'metrics' => [
                        'current_value' => floatval($currentValue),
                        'profit_loss' => floatval($profitLoss),
                        'profit_share' => floatval($profitShare),
                        'total_return' => floatval($profitLoss + $profitShare),
                        'roi_percentage' => round($roi, 2),
                        'ownership_percentage' => round($ownershipPercentage, 2),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch share details', [
                'share_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Share not found',
            ], 404);
        }
    }
}
