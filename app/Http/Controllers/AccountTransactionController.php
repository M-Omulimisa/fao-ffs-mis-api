<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountTransactionController extends Controller
{
    /**
     * Get all account transactions with filters
     */
    public function index(Request $request)
    {
        try {
            $query = AccountTransaction::with(['user', 'creator', 'relatedDisbursement']);

            // Filter by user
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by source
            if ($request->has('source') && $request->source) {
                $query->where('source', $request->source);
            }

            // Filter by type (credit/debit)
            if ($request->has('type') && $request->type) {
                if ($request->type === 'credit') {
                    $query->where('amount', '>=', 0);
                } elseif ($request->type === 'debit') {
                    $query->where('amount', '<', 0);
                }
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->where('transaction_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date) {
                $query->where('transaction_date', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'transaction_date');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Calculate summary BEFORE pagination (using the same filters)
            // Clone the query to calculate totals with all applied filters
            $summaryQuery = clone $query;
            
            $totalCredit = (clone $summaryQuery)->where('amount', '>=', 0)->sum('amount');
            $totalDebit = abs((clone $summaryQuery)->where('amount', '<', 0)->sum('amount'));
            $balance = $totalCredit - $totalDebit;
            
            // If filtering by specific user, also get their overall balance
            $userBalance = null;
            if ($request->has('user_id') && $request->user_id) {
                $userBalance = $this->calculateUserBalance($request->user_id);
            }

            $summary = [
                'total_credit' => $totalCredit,
                'total_debit' => $totalDebit,
                'balance' => $balance,
                'user_balance' => $userBalance, // Overall user balance if filtered by user
            ];

            // Pagination
            $perPage = $request->input('per_page', 20);
            $transactions = $query->paginate($perPage);

            // Format transactions
            $formattedTransactions = $transactions->map(function ($transaction) {
                return $this->formatTransaction($transaction);
            });

            return Utils::success([
                'transactions' => $formattedTransactions,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ], 'Account transactions retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve account transactions: ' . $e->getMessage());
        }
    }

    /**
     * Create a new account transaction (withdrawal/deposit)
     */
    public function store(Request $request)
    {
        // Check if user is admin
        $currentUser = Utils::get_user_from_request($request);
        if (!$currentUser || $currentUser->user_type !== 'Admin') {
            return Utils::error('Only administrators can create account transactions');
        }

        // Log ALL incoming data for debugging
        \Log::info('=== ACCOUNT TRANSACTION CREATE REQUEST ===');
        \Log::info('All request data:', $request->all());
        \Log::info('user_id type: ' . gettype($request->user_id));
        \Log::info('created_by_id type: ' . gettype($request->created_by_id));
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|not_in:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'source' => 'required|in:withdrawal,deposit',
            'created_by_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return Utils::error($validator->errors()->first());
        }

        try {
            // Log processed data before insertion
            \Log::info('Creating transaction with:', [
                'user_id' => $request->user_id,
                'created_by_id' => $request->created_by_id,
                'amount' => $request->amount,
                'source' => $request->source,
            ]);

            // For withdrawals, ensure amount is negative and check balance
            $amount = $request->amount;
            if ($request->source === 'withdrawal') {
                $amount = -abs($amount);
                
                // Check if user has sufficient balance
                $currentBalance = $this->calculateUserBalance($request->user_id);
                if ($currentBalance + $amount < 0) {
                    return Utils::error("Insufficient balance. Current balance: UGX " . number_format($currentBalance, 2));
                }
            } else {
                // For deposits, ensure amount is positive
                $amount = abs($amount);
            }

            // Create account transaction
            $transaction = AccountTransaction::create([
                'user_id' => $request->user_id,
                'amount' => $amount,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'source' => $request->source,
                'created_by_id' => $request->created_by_id,
            ]);

            \Log::info('Transaction created successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'created_by_id' => $transaction->created_by_id,
            ]);

            $transaction->load(['user', 'creator']);

            return Utils::success(
                $this->formatTransaction($transaction),
                'Account transaction created successfully'
            );
        } catch (\Exception $e) {
            return Utils::error('Failed to create account transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get a single account transaction
     */
    public function show($id)
    {
        try {
            $transaction = AccountTransaction::with(['user', 'creator', 'relatedDisbursement'])
                ->findOrFail($id);

            return Utils::success(
                $this->formatTransaction($transaction),
                'Account transaction retrieved successfully'
            );
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve account transaction: ' . $e->getMessage());
        }
    }

    /**
     * Delete an account transaction (only manual ones)
     */
    public function destroy($id)
    {
        try {
            $transaction = AccountTransaction::findOrFail($id);

            // Prevent deletion of disbursement-related transactions
            if ($transaction->source === 'disbursement') {
                return Utils::error('Cannot delete disbursement-related transactions');
            }

            $transaction->delete();

            return Utils::success(null, 'Account transaction deleted successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to delete account transaction: ' . $e->getMessage());
        }
    }

    /**
     * Calculate user's account balance
     */
    private function calculateUserBalance($userId = null)
    {
        if (!$userId) {
            return 0;
        }

        return AccountTransaction::where('user_id', $userId)->sum('amount');
    }

    /**
     * Format transaction for API response
     */
    private function formatTransaction($transaction)
    {
        return [
            'id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'user_name' => $transaction->user->name ?? 'N/A',
            'amount' => $transaction->amount,
            'formatted_amount' => $transaction->formatted_amount,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'formatted_date' => $transaction->formatted_date,
            'description' => $transaction->description,
            'source' => $transaction->source,
            'source_label' => $transaction->source_label,
            'type' => $transaction->type,
            'related_disbursement_id' => $transaction->related_disbursement_id,
            'created_by' => $transaction->creator->name ?? 'N/A',
            'created_by_id' => $transaction->created_by_id,
            'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            'can_delete' => $transaction->source !== 'disbursement',
        ];
    }
}
