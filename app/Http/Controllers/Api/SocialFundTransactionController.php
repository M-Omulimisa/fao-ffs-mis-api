<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialFundTransaction;
use App\Models\FfsGroup;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Social Fund Transaction API Controller
 * 
 * Handles API requests for VSLA Social Fund:
 * - List transactions
 * - Create contribution/withdrawal
 * - Get group balance
 */
class SocialFundTransactionController extends Controller
{
    use ApiResponser;

    /**
     * Get social fund transactions for a group
     * GET /api/social-fund/transactions
     * 
     * Query params:
     * - group_id (required)
     * - cycle_id (optional)
     * - type (optional): 'contribution' | 'withdrawal'
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|integer|exists:ffs_groups,id',
                'cycle_id' => 'nullable|integer|exists:projects,id',
                'type' => 'nullable|in:contribution,withdrawal',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, [
                    'errors' => $validator->errors()
                ]);
            }

            $groupId = $request->group_id;
            $cycleId = $request->cycle_id;
            $type = $request->type;
            $perPage = $request->per_page ?? 50;

            // Build query
            $query = SocialFundTransaction::with(['member', 'creator', 'meeting'])
                ->where('group_id', $groupId);

            if ($cycleId) {
                $query->where('cycle_id', $cycleId);
            }

            if ($type) {
                $query->where('transaction_type', $type);
            }

            $transactions = $query->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Calculate balance
            $balanceQuery = SocialFundTransaction::where('group_id', $groupId);
            if ($cycleId) {
                $balanceQuery->where('cycle_id', $cycleId);
            }
            $balance = $balanceQuery->sum('amount');

            return response()->json([
                'success' => true,
                'message' => 'Transactions retrieved successfully',
                'data' => [
                    'transactions' => $transactions->items(),
                    'balance' => (float) $balance,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'last_page' => $transactions->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get social fund balance for a group
     * GET /api/social-fund/balance
     * 
     * Query params:
     * - group_id (required)
     * - cycle_id (optional)
     */
    public function getBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|integer|exists:ffs_groups,id',
                'cycle_id' => 'nullable|integer|exists:projects,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, [
                    'errors' => $validator->errors()
                ]);
            }

            $groupId = $request->group_id;
            $cycleId = $request->cycle_id;

            $balance = SocialFundTransaction::getGroupBalance($groupId, $cycleId);

            // Get summary statistics
            $query = SocialFundTransaction::where('group_id', $groupId);
            if ($cycleId) {
                $query->where('cycle_id', $cycleId);
            }

            $totalContributions = (clone $query)->where('transaction_type', 'contribution')->sum('amount');
            $totalWithdrawals = abs((clone $query)->where('transaction_type', 'withdrawal')->sum('amount'));
            $transactionCount = (clone $query)->count();

            return response()->json([
                'success' => true,
                'message' => 'Balance retrieved successfully',
                'data' => [
                    'balance' => (float) $balance,
                    'total_contributions' => (float) $totalContributions,
                    'total_withdrawals' => (float) $totalWithdrawals,
                    'transaction_count' => $transactionCount,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to get balance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new social fund transaction
     * POST /api/social-fund/transactions
     * 
     * Used for manual entries (e.g., withdrawals by admin)
     * Contributions from meetings are created automatically by meeting processor
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|integer|exists:ffs_groups,id',
                'cycle_id' => 'nullable|integer|exists:projects,id',
                'member_id' => 'nullable|integer|exists:users,id',
                'transaction_type' => 'required|in:contribution,withdrawal',
                'amount' => 'required|numeric|min:0.01',
                'transaction_date' => 'required|date',
                'description' => 'nullable|string|max:1000',
                'reason' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, [
                    'errors' => $validator->errors()
                ]);
            }

            // For withdrawals, amount should be negative
            $amount = $request->amount;
            if ($request->transaction_type === 'withdrawal') {
                $amount = -abs($amount);
                
                // Check if sufficient balance
                $currentBalance = SocialFundTransaction::getGroupBalance(
                    $request->group_id,
                    $request->cycle_id
                );
                
                if ($currentBalance + $amount < 0) {
                    return $this->error('Insufficient social fund balance', 422, [
                        'current_balance' => $currentBalance,
                        'requested_withdrawal' => abs($amount),
                    ]);
                }
            }

            $transaction = SocialFundTransaction::create([
                'group_id' => $request->group_id,
                'cycle_id' => $request->cycle_id,
                'member_id' => $request->member_id,
                'transaction_type' => $request->transaction_type,
                'amount' => $amount,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'reason' => $request->reason,
                'created_by_id' => Auth::id() ?? $request->member_id ?? 1,
            ]);

            // Load relationships
            $transaction->load(['member', 'creator']);

            // Get updated balance
            $newBalance = SocialFundTransaction::getGroupBalance(
                $request->group_id,
                $request->cycle_id
            );

            return response()->json([
                'success' => true,
                'message' => ucfirst($request->transaction_type) . ' recorded successfully',
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => (float) $newBalance,
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->error('Failed to create transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get transaction details
     * GET /api/social-fund/transactions/{id}
     */
    public function show($id)
    {
        try {
            $transaction = SocialFundTransaction::with(['member', 'creator', 'meeting', 'group', 'cycle'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Transaction retrieved successfully',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return $this->error('Transaction not found', 404);
        }
    }
}
