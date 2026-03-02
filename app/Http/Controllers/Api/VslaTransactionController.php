<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VslaTransactionService;
use App\Models\ProjectTransaction;
use App\Models\AccountTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * VSLA Transaction API Controller
 * 
 * Handles all VSLA financial transaction endpoints using double-entry accounting
 */
class VslaTransactionController extends Controller
{
    protected $transactionService;

    public function __construct(VslaTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Record a savings transaction
     * 
     * POST /api/vsla/transactions/saving
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordSaving(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->transactionService->recordSaving($request->all());

        if ($result['success']) {
            return response()->json([
                'code' => 1,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 201);
        }

        return response()->json([
            'code' => 0,
            'message' => $result['message'],
            'data' => null,
        ], 400);
    }

    /**
     * Disburse a loan to a member
     * 
     * POST /api/vsla/transactions/loan-disbursement
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disburseLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->transactionService->disburseLoan($request->all());

        if ($result['success']) {
            return response()->json([
                'code' => 1,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 201);
        }

        return response()->json([
            'code' => 0,
            'message' => $result['message'],
            'data' => null,
        ], 400);
    }

    /**
     * Record a loan repayment
     * 
     * POST /api/vsla/transactions/loan-repayment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordLoanRepayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->transactionService->recordLoanRepayment($request->all());

        if ($result['success']) {
            return response()->json([
                'code' => 1,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 201);
        }

        return response()->json([
            'code' => 0,
            'message' => $result['message'],
            'data' => null,
        ], 400);
    }

    /**
     * Record a fine or penalty
     * 
     * POST /api/vsla/transactions/fine
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordFine(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->transactionService->recordFine($request->all());

        if ($result['success']) {
            return response()->json([
                'code' => 1,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 201);
        }

        return response()->json([
            'code' => 0,
            'message' => $result['message'],
            'data' => null,
        ], 400);
    }

    /**
     * Get member balance for a specific user
     * 
     * GET /api/vsla/transactions/member-balance/{user_id}
     * 
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMemberBalance(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $projectId = $request->query('project_id');

            if ($projectId) {
                Project::findOrFail($projectId);
            }

            $balances = ProjectTransaction::calculateUserBalances($userId, $projectId);

            return response()->json([
                'code' => 1,
                'message' => 'Member balance retrieved successfully',
                'data' => [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'project_id' => $projectId,
                    'balances' => $balances,
                    'formatted' => [
                        'savings' => 'UGX ' . number_format($balances['savings'], 2),
                        'loans' => 'UGX ' . number_format($balances['loans'], 2),
                        'fines' => 'UGX ' . number_format($balances['fines'], 2),
                        'interest' => 'UGX ' . number_format($balances['interest'], 2),
                        'net_position' => 'UGX ' . number_format($balances['net_position'], 2),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }

    /**
     * Get group balance
     * 
     * GET /api/vsla/transactions/group-balance/{group_id}
     * 
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupBalance(Request $request, $groupId)
    {
        try {
            $projectId = $request->query('project_id');

            if ($projectId) {
                Project::findOrFail($projectId);
            }

            $balances = ProjectTransaction::calculateGroupBalances($groupId, $projectId);

            // Verify accounting equation
            $verification = $projectId ? ProjectTransaction::verifyAccountingBalance($projectId) : null;

            return response()->json([
                'code' => 1,
                'message' => 'Group balance retrieved successfully',
                'data' => [
                    'group_id' => $groupId,
                    'project_id' => $projectId,
                    'balances' => $balances,
                    'formatted' => [
                        'cash' => 'UGX ' . number_format($balances['cash'], 2),
                        'total_savings' => 'UGX ' . number_format($balances['total_savings'], 2),
                        'loans_outstanding' => 'UGX ' . number_format($balances['loans_outstanding'], 2),
                        'fines_collected' => 'UGX ' . number_format($balances['fines_collected'], 2),
                    ],
                    'accounting_verification' => $verification,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }

    /**
     * Get member statement (transaction history)
     * 
     * GET /api/vsla/transactions/member-statement
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMemberStatement(Request $request)
    {
        try {
            $userId = $request->query('user_id');
            $projectId = $request->query('project_id');
            $accountType = $request->query('account_type');
            $limit = $request->query('limit', 50);

            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'user_id is required',
                    'data' => null,
                ], 422);
            }

            $query = ProjectTransaction::userTransactions($userId)
                ->with(['project', 'contraEntry'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc');

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            if ($accountType) {
                $query->where('account_type', $accountType);
            }

            $transactions = $query->limit($limit)->get();
            $balances = ProjectTransaction::calculateUserBalances($userId, $projectId);

            return response()->json([
                'code' => 1,
                'message' => 'Member statement retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'balances' => $balances,
                    'count' => $transactions->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Get group statement (transaction history)
     * 
     * GET /api/vsla/transactions/group-statement
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupStatement(Request $request)
    {
        try {
            $groupId = $request->query('group_id');
            $projectId = $request->query('project_id');
            $accountType = $request->query('account_type');
            $limit = $request->query('limit', 50);

            if (!$groupId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'group_id is required',
                    'data' => null,
                ], 422);
            }

            $query = ProjectTransaction::groupTransactions($groupId)
                ->with(['project', 'contraEntry'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc');

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            if ($accountType) {
                $query->where('account_type', $accountType);
            }

            $transactions = $query->limit($limit)->get();
            $balances = ProjectTransaction::calculateGroupBalances($groupId, $projectId);

            return response()->json([
                'code' => 1,
                'message' => 'Group statement retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'balances' => $balances,
                    'count' => $transactions->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Get recent transactions for dashboard
     * 
     * GET /api/vsla/transactions/recent
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentTransactions(Request $request)
    {
        try {
            $groupId = $request->query('group_id');
            $projectId = $request->query('project_id');
            $type = $request->query('type'); // savings, loans, transactions
            $limit = $request->query('limit', 10);

            if (!$groupId && !$projectId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'group_id or project_id is required',
                    'data' => null,
                ], 422);
            }

            $query = ProjectTransaction::query()
                ->with(['project', 'creator', 'contraEntry'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc');

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            // Filter by type
            if ($type === 'savings') {
                $query->where('account_type', 'savings')
                    ->where('owner_type', 'user');
            } elseif ($type === 'loans') {
                $query->where('account_type', 'loan');
            } elseif ($type === 'transactions') {
                $query->where('owner_type', 'group');
            }

            $transactions = $query->limit($limit)->get()->map(function ($transaction) {
                $user = User::find($transaction->owner_id);
                
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'amount_signed' => $transaction->amount_signed,
                    'formatted_amount' => 'UGX ' . number_format($transaction->amount, 0),
                    'description' => $transaction->description,
                    'account_type' => $transaction->account_type,
                    'owner_type' => $transaction->owner_type,
                    'owner_name' => $user ? $user->name : 'Group',
                    'transaction_date' => $transaction->transaction_date?->format('M d, Y'),
                    'type' => $transaction->type,
                    'is_contra_entry' => $transaction->is_contra_entry,
                ];
            });

            return response()->json([
                'code' => 1,
                'message' => 'Recent transactions retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'count' => $transactions->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Get dashboard summary for VSLA admin
     * 
     * GET /api/vsla/transactions/dashboard-summary
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary(Request $request)
    {
        try {
            $groupId = $request->query('group_id');
            $projectId = $request->query('project_id');

            if (!$groupId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'group_id is required',
                    'data' => null,
                ], 422);
            }

            // Get group balances
            $groupBalances = ProjectTransaction::calculateGroupBalances($groupId, $projectId);

            // Count members with savings
            $membersWithSavings = ProjectTransaction::where('account_type', 'savings')
                ->where('owner_type', 'user');
            
            if ($projectId) {
                $membersWithSavings->where('project_id', $projectId);
            }
            
            $totalMembers = $membersWithSavings->distinct('owner_id')->count('owner_id');

            // Count active loans
            $activeLoans = ProjectTransaction::where('account_type', 'loan')
                ->where('owner_type', 'user')
                ->where('amount_signed', '>', 0);
            
            if ($projectId) {
                $activeLoans->where('project_id', $projectId);
            }
            
            $activeLoanCount = $activeLoans->distinct('owner_id')->count('owner_id');

            // Get cycle progress
            $cycleProgress = null;
            if ($projectId) {
                $project = Project::find($projectId);
                if ($project) {
                    $start = $project->vsla_cycle_start_date ? \Carbon\Carbon::parse($project->vsla_cycle_start_date) : null;
                    $end = $project->vsla_cycle_end_date ? \Carbon\Carbon::parse($project->vsla_cycle_end_date) : null;
                    
                    if ($start && $end) {
                        $now = now();
                        $totalDays = $start->diffInDays($end);
                        $elapsedDays = $start->diffInDays($now);
                        $percentage = $totalDays > 0 ? min(100, round(($elapsedDays / $totalDays) * 100)) : 0;
                        
                        $cycleProgress = [
                            'start_date' => $start->format('M d, Y'),
                            'end_date' => $end->format('M d, Y'),
                            'elapsed_weeks' => round($elapsedDays / 7),
                            'total_weeks' => round($totalDays / 7),
                            'percentage' => $percentage,
                        ];
                    }
                }
            }

            return response()->json([
                'code' => 1,
                'message' => 'Dashboard summary retrieved successfully',
                'data' => [
                    'overview' => [
                        'total_savings' => $groupBalances['total_savings'],
                        'formatted_savings' => 'UGX ' . number_format($groupBalances['total_savings'], 0),
                        'active_loans' => $activeLoanCount,
                        'loans_outstanding' => $groupBalances['loans_outstanding'],
                        'formatted_loans' => 'UGX ' . number_format($groupBalances['loans_outstanding'], 0),
                        'total_members' => $totalMembers,
                        'cash_balance' => $groupBalances['cash'],
                        'formatted_cash' => 'UGX ' . number_format($groupBalances['cash'], 0),
                        'fines_collected' => $groupBalances['fines_collected'],
                    ],
                    'cycle_progress' => $cycleProgress,
                    'group_id' => $groupId,
                    'project_id' => $projectId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Get all members of a VSLA group
     * 
     * GET /api/vsla/group-members
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupMembers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $projectId = $request->input('project_id');
            
            // Get project to verify it exists
            $project = Project::find($projectId);
            if (!$project) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Project not found',
                    'data' => null,
                ], 404);
            }

            // Get all users who have shares in this project (investors/members)
            $members = User::whereHas('projectShares', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->where('status', 1)
                ->select('id', 'name', 'member_code', 'phone_number', 'email')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Group members retrieved successfully',
                'data' => $members,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Universal transaction creation endpoint
     * Handles all transaction types: saving, fine, withdrawal, charge, etc.
     * 
     * POST /api/vsla/transactions/create
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:projects,id',
            'transaction_type' => 'required|string|in:saving,fine,loan_repayment,charge,welfare,social_fund,share_out,other',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transactionType = $request->input('transaction_type');
            $data = $request->all();

            // Route to appropriate service method based on transaction type
            $result = null;
            
            switch ($transactionType) {
                case 'saving':
                    $result = $this->transactionService->recordSaving($data);
                    break;
                    
                case 'fine':
                case 'charge':
                case 'welfare':
                case 'social_fund':
                    $result = $this->transactionService->recordFine($data);
                    break;
                    
                case 'loan_repayment':
                    $result = $this->transactionService->recordLoanRepayment($data);
                    break;
                    
                case 'share_out':
                case 'other':
                    // For generic transactions, use the fine method (member pays to group)
                    $result = $this->transactionService->recordFine($data);
                    break;
                    
                default:
                    return response()->json([
                        'code' => 0,
                        'message' => 'Invalid transaction type',
                        'data' => null,
                    ], 400);
            }

            if ($result && $result['success']) {
                return response()->json([
                    'code' => 1,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ], 201);
            }

            return response()->json([
                'code' => 0,
                'message' => $result['message'] ?? 'Transaction failed',
                'data' => null,
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Transaction failed: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get group savings overview — organized by cash inflow and outflow.
     *
     * Shows ONLY group-level double-entry transactions for the active cycle,
     * categorized into inflow (money coming in) and outflow (money going out).
     *
     * Query params:
     *   - group_id (required)
     *   - project_id (cycle) — optional, filters to a specific cycle
     *   - limit — max transactions per category (default 100)
     */
    public function getGroupSavings(Request $request)
    {
        try {
            $groupId = $request->query('group_id');
            $cycleId = $request->query('project_id'); // project_id = cycle_id in account_transactions
            $limit = (int) $request->query('limit', 200);

            if (!$groupId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'group_id is required',
                    'data' => null,
                ], 422);
            }

            // Query the account_transactions table (where meeting-based workflow writes)
            $query = AccountTransaction::where('group_id', $groupId)
                ->where('owner_type', 'group')
                ->where(function ($q) {
                    $q->where('is_contra_entry', false)
                      ->orWhereNull('is_contra_entry');
                })
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc');

            if ($cycleId) {
                $query->where('cycle_id', $cycleId);
            }

            $allTransactions = $query->limit($limit)->get();

            // Categorize into inflow and outflow
            $inflow = [];
            $outflow = [];
            $totalInflow = 0.0;
            $totalOutflow = 0.0;
            $inflowByCategory = [];
            $outflowByCategory = [];

            foreach ($allTransactions as $t) {
                $amount = (float) $t->amount;

                // Look up the related member from the contra entry
                $memberName = null;
                if ($t->contra_entry_id) {
                    $contra = AccountTransaction::find($t->contra_entry_id);
                    if ($contra && $contra->owner_type === 'member') {
                        $member = User::find($contra->user_id);
                        $memberName = $member ? $member->name : null;
                    }
                }

                $entry = [
                    'id'               => $t->id,
                    'amount'           => abs($amount),
                    'amount_signed'    => $amount,
                    'formatted_amount' => $t->formatted_amount ?? ('UGX ' . number_format(abs($amount))),
                    'description'      => $t->description ?? '',
                    'account_type'     => strtolower($t->account_type ?? ''),
                    'source'           => $t->source ?? '',
                    'source_label'     => $t->source_label ?? ucfirst(str_replace('_', ' ', $t->source ?? '')),
                    'type'             => $amount >= 0 ? 'credit' : 'debit',
                    'type_label'       => $amount >= 0 ? 'Credit' : 'Debit',
                    'transaction_date' => $t->transaction_date ? $t->transaction_date->format('Y-m-d') : null,
                    'formatted_date'   => $t->transaction_date ? $t->transaction_date->format('M d, Y') : null,
                    'owner_type'       => $t->owner_type,
                    'owner_id'         => $t->user_id,
                    'owner_name'       => $memberName ?? 'Group',
                    'created_by'       => $t->creator ? $t->creator->name : null,
                    'is_contra_entry'  => (bool) $t->is_contra_entry,
                    'contra_entry_id'  => $t->contra_entry_id,
                ];

                $acctType = strtolower($t->account_type ?? '');
                $source   = $t->source ?? '';

                if ($amount > 0) {
                    $inflow[] = $entry;
                    $totalInflow += $amount;
                    $cat = $this->_getCategoryLabel($source, $acctType, 'inflow');
                    $inflowByCategory[$cat] = ($inflowByCategory[$cat] ?? 0.0) + $amount;
                } elseif ($amount < 0) {
                    $outflow[] = $entry;
                    $totalOutflow += abs($amount);
                    $cat = $this->_getCategoryLabel($source, $acctType, 'outflow');
                    $outflowByCategory[$cat] = ($outflowByCategory[$cat] ?? 0.0) + abs($amount);
                }
            }

            // Build category breakdowns
            $inflowCategories = [];
            foreach ($inflowByCategory as $label => $amt) {
                $inflowCategories[] = [
                    'label'      => $label,
                    'amount'     => round($amt, 2),
                    'percentage' => $totalInflow > 0 ? round(($amt / $totalInflow) * 100, 1) : 0,
                ];
            }
            usort($inflowCategories, fn($a, $b) => $b['amount'] <=> $a['amount']);

            $outflowCategories = [];
            foreach ($outflowByCategory as $label => $amt) {
                $outflowCategories[] = [
                    'label'      => $label,
                    'amount'     => round($amt, 2),
                    'percentage' => $totalOutflow > 0 ? round(($amt / $totalOutflow) * 100, 1) : 0,
                ];
            }
            usort($outflowCategories, fn($a, $b) => $b['amount'] <=> $a['amount']);

            // Group balances from account_transactions
            $balancesQuery = AccountTransaction::where('group_id', $groupId)
                ->where('owner_type', 'group')
                ->where(function ($q) {
                    $q->where('is_contra_entry', false)
                      ->orWhereNull('is_contra_entry');
                });
            if ($cycleId) {
                $balancesQuery->where('cycle_id', $cycleId);
            }

            $cashBalance   = (float) (clone $balancesQuery)->sum('amount');
            $sharesTotal   = (float) (clone $balancesQuery)->whereRaw('LOWER(account_type) = ?', ['share'])->sum('amount');
            $savingsTotal  = (float) (clone $balancesQuery)->whereRaw('LOWER(account_type) IN (?, ?)', ['deposit', 'saving'])->sum('amount');
            $loansOut      = (float) (clone $balancesQuery)->whereRaw('LOWER(account_type) = ?', ['loan'])->where('amount', '<', 0)->sum('amount');
            $finesTotal    = (float) (clone $balancesQuery)->whereRaw('LOWER(account_type) = ?', ['fine'])->sum('amount');
            $welfareTotal  = (float) (clone $balancesQuery)->whereRaw('LOWER(account_type) LIKE ?', ['welfare%'])->sum('amount');
            $socialFund    = (float) (clone $balancesQuery)->whereRaw('LOWER(account_type) = ?', ['social_fund'])->sum('amount');

            return response()->json([
                'code' => 1,
                'message' => 'Group savings retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_inflow'  => round($totalInflow, 2),
                        'total_outflow' => round($totalOutflow, 2),
                        'net_cash_flow' => round($totalInflow - $totalOutflow, 2),
                        'inflow_count'  => count($inflow),
                        'outflow_count' => count($outflow),
                        'total_count'   => count($inflow) + count($outflow),
                    ],
                    'balances' => [
                        'cash'              => round($cashBalance, 2),
                        'total_savings'     => round($sharesTotal + $savingsTotal, 2),
                        'loans_outstanding' => round(abs($loansOut), 2),
                        'fines_collected'   => round($finesTotal, 2),
                        'welfare'           => round($welfareTotal, 2),
                        'social_fund'       => round($socialFund, 2),
                    ],
                    'inflow' => [
                        'transactions' => $inflow,
                        'categories'   => $inflowCategories,
                    ],
                    'outflow' => [
                        'transactions' => $outflow,
                        'categories'   => $outflowCategories,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    /**
     * Map source/account_type into user-friendly category labels.
     * account_type values: share, savings, saving, deposit, loan, loan_repayment, fine, welfare, social_fund, etc.
     */
    private function _getCategoryLabel(string $source, string $accountType, string $flowDirection): string
    {
        $at = strtolower($accountType);

        if ($flowDirection === 'inflow') {
            return match (true) {
                $at === 'share'                          => 'Share Purchases',
                in_array($at, ['saving', 'savings', 'deposit']) => 'Savings Deposits',
                $at === 'loan_repayment'                 => 'Loan Repayments',
                $at === 'fine'                           => 'Fines & Penalties',
                $at === 'interest'                       => 'Interest Income',
                str_starts_with($at, 'welfare')          => 'Welfare Contributions',
                $at === 'social_fund'                    => 'Social Fund',
                default                                  => 'Other Income',
            };
        }

        return match (true) {
            $at === 'loan'                             => 'Loan Disbursements',
            in_array($at, ['share_out', 'shareout'])   => 'Share-out Distributions',
            str_starts_with($at, 'welfare')            => 'Welfare Payments',
            $at === 'social_fund'                      => 'Social Fund Payouts',
            default                                    => 'Other Expenses',
        };
    }
}
