<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VslaLoan;
use App\Models\LoanTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserLoanController extends Controller
{
    /**
     * Get all loans for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            \Log::info('========== USER LOANS INDEX REQUEST ==========');
            \Log::info('Request Parameters:', $request->all());
            
            $userId = Auth::id();
            \Log::info('User ID:', ['user_id' => $userId]);
            
            $query = VslaLoan::with(['cycle', 'meeting', 'loanTransactions'])
                ->where('borrower_id', $userId);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
                \Log::info('Filtering by status:', ['status' => $request->status]);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            \Log::info('Sorting:', ['sort_by' => $sortBy, 'sort_order' => $sortOrder]);

            $loans = $query->get();
            \Log::info('Loans Retrieved:', ['count' => $loans->count()]);
            
            // Add computed fields for mobile app
            $loans->each(function ($loan) {
                $loan->formatted_loan_amount = 'UGX ' . number_format($loan->loan_amount, 2);
                $loan->formatted_total_due = 'UGX ' . number_format($loan->total_amount_due, 2);
                $loan->formatted_amount_paid = 'UGX ' . number_format($loan->amount_paid, 2);
                $loan->formatted_balance = 'UGX ' . number_format($loan->balance, 2);
                $loan->progress_percentage = $loan->total_amount_due > 0 
                    ? round(($loan->amount_paid / $loan->total_amount_due) * 100, 2) 
                    : 0;
                $loan->is_overdue = $loan->due_date && $loan->due_date->isPast() && $loan->status === 'active';
                $loan->days_remaining = $loan->due_date ? now()->diffInDays($loan->due_date, false) : null;
            });

            \Log::info('========== USER LOANS INDEX COMPLETED ==========');

            return response()->json([
                'code' => 1,
                'message' => 'Loans retrieved successfully',
                'data' => $loans,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('========== USER LOANS INDEX FAILED ==========');
            \Log::error('Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve loans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single loan by ID
     */
    public function show($id)
    {
        try {
            \Log::info('========== USER LOAN SHOW REQUEST ==========');
            \Log::info('Loan ID:', ['id' => $id]);
            
            $userId = Auth::id();
            $loan = VslaLoan::with(['cycle', 'meeting', 'borrower', 'loanTransactions'])
                ->where('id', $id)
                ->where('borrower_id', $userId)
                ->first();

            if (!$loan) {
                \Log::warning('Loan not found or unauthorized:', ['id' => $id, 'user_id' => $userId]);
                return response()->json([
                    'code' => 0,
                    'message' => 'Loan not found',
                ], 404);
            }

            // Add computed fields
            $loan->formatted_loan_amount = 'UGX ' . number_format($loan->loan_amount, 2);
            $loan->formatted_total_due = 'UGX ' . number_format($loan->total_amount_due, 2);
            $loan->formatted_amount_paid = 'UGX ' . number_format($loan->amount_paid, 2);
            $loan->formatted_balance = 'UGX ' . number_format($loan->balance, 2);
            $loan->progress_percentage = $loan->total_amount_due > 0 
                ? round(($loan->amount_paid / $loan->total_amount_due) * 100, 2) 
                : 0;
            $loan->is_overdue = $loan->due_date && $loan->due_date->isPast() && $loan->status === 'active';
            $loan->days_remaining = $loan->due_date ? now()->diffInDays($loan->due_date, false) : null;

            // Format transactions
            $loan->loanTransactions->each(function ($transaction) {
                $transaction->formatted_amount = 'UGX ' . number_format(abs($transaction->amount), 2);
            });

            \Log::info('Loan Found:', [
                'id' => $loan->id,
                'loan_amount' => $loan->loan_amount,
                'status' => $loan->status,
                'transactions_count' => $loan->loanTransactions->count()
            ]);
            \Log::info('========== USER LOAN SHOW COMPLETED ==========');

            return response()->json([
                'code' => 1,
                'message' => 'Loan retrieved successfully',
                'data' => $loan,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('========== USER LOAN SHOW FAILED ==========');
            \Log::error('Error:', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve loan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan statistics for the authenticated user
     */
    public function statistics()
    {
        try {
            \Log::info('========== USER LOAN STATISTICS REQUEST ==========');
            
            $userId = Auth::id();
            \Log::info('User ID:', ['user_id' => $userId]);

            $activeLoans = VslaLoan::where('borrower_id', $userId)
                ->where('status', 'active')
                ->count();

            $paidLoans = VslaLoan::where('borrower_id', $userId)
                ->where('status', 'paid')
                ->count();

            $totalBorrowed = VslaLoan::where('borrower_id', $userId)
                ->sum('loan_amount');

            $totalPaid = VslaLoan::where('borrower_id', $userId)
                ->sum('amount_paid');

            $totalBalance = VslaLoan::where('borrower_id', $userId)
                ->where('status', 'active')
                ->sum('balance');

            $overdueLoans = VslaLoan::where('borrower_id', $userId)
                ->where('status', 'active')
                ->whereDate('due_date', '<', now())
                ->count();

            $statistics = [
                'active_loans' => $activeLoans,
                'paid_loans' => $paidLoans,
                'overdue_loans' => $overdueLoans,
                'total_loans' => $activeLoans + $paidLoans,
                'total_borrowed' => (float) $totalBorrowed,
                'total_paid' => (float) $totalPaid,
                'total_balance' => (float) $totalBalance,
                'formatted_total_borrowed' => 'UGX ' . number_format($totalBorrowed, 2),
                'formatted_total_paid' => 'UGX ' . number_format($totalPaid, 2),
                'formatted_total_balance' => 'UGX ' . number_format($totalBalance, 2),
            ];

            \Log::info('Statistics Calculated:', $statistics);
            \Log::info('========== USER LOAN STATISTICS COMPLETED ==========');

            return response()->json([
                'code' => 1,
                'message' => 'Statistics retrieved successfully',
                'data' => $statistics,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('========== USER LOAN STATISTICS FAILED ==========');
            \Log::error('Error:', ['message' => $e->getMessage()]);
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan transactions for a specific loan
     */
    public function transactions($loanId)
    {
        try {
            \Log::info('========== USER LOAN TRANSACTIONS REQUEST ==========');
            \Log::info('Loan ID:', ['loan_id' => $loanId]);
            
            $userId = Auth::id();
            
            // Verify loan belongs to user
            $loan = VslaLoan::where('id', $loanId)
                ->where('borrower_id', $userId)
                ->first();

            if (!$loan) {
                \Log::warning('Loan not found or unauthorized:', ['loan_id' => $loanId, 'user_id' => $userId]);
                return response()->json([
                    'code' => 0,
                    'message' => 'Loan not found',
                ], 404);
            }

            $transactions = LoanTransaction::where('loan_id', $loanId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format transactions
            $transactions->each(function ($transaction) {
                $transaction->formatted_amount = 'UGX ' . number_format(abs($transaction->amount), 2);
                $transaction->is_positive = $transaction->amount > 0;
            });

            \Log::info('Transactions Retrieved:', ['count' => $transactions->count()]);
            \Log::info('========== USER LOAN TRANSACTIONS COMPLETED ==========');

            return response()->json([
                'code' => 1,
                'message' => 'Transactions retrieved successfully',
                'data' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('========== USER LOAN TRANSACTIONS FAILED ==========');
            \Log::error('Error:', ['loan_id' => $loanId, 'message' => $e->getMessage()]);
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve transactions: ' . $e->getMessage(),
            ], 500);
        }
    }
}
