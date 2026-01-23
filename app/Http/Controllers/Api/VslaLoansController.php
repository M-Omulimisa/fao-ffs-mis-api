<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VslaLoan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * VSLA Loans Controller
 * Simple loan management - loans are created via meeting submission
 */
class VslaLoansController extends Controller
{
    /**
     * Get loans for a cycle
     * GET /api/vsla/loans?cycle_id=X
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'code' => 0,
                ], 401);
            }

            // Get cycle_id from request (required) or project_id (legacy)
            $cycleId = $request->cycle_id ?? $request->project_id;

            // Validate that the cycle belongs to the user's group
            if ($cycleId) {
                $cycle = Project::find($cycleId);
                if ($cycle && $cycle->group_id != $user->group_id) {
                    // Cycle doesn't belong to user's group, use their active cycle instead
                    $cycleId = null;
                }
            }

            if (!$cycleId) {
                // Try to get active cycle from user's group
                if ($user->group_id) {
                    $cycle = Project::where('group_id', $user->group_id)
                        ->where('is_vsla_cycle', 'Yes')
                        ->where('is_active_cycle', 'Yes')
                        ->first();
                    
                    if ($cycle) {
                        $cycleId = $cycle->id;
                    }
                }

                if (!$cycleId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active cycle found',
                        'code' => 0,
                        'data' => []
                    ]);
                }
            }

            // Check if user is VSLA admin (Chairman, Secretary, or Treasurer)
            $isAdmin = $user->isVslaGroupAdmin();

            // Get loans for the cycle
            // If user is admin, get all loans in the cycle
            // If user is regular member, get only their own loans
            $query = VslaLoan::where('cycle_id', $cycleId);
            
            if (!$isAdmin) {
                // Regular members only see their own loans
                $query->where('borrower_id', $user->id);
            }
            
            $query->with(['borrower', 'meeting'])
                ->orderBy('created_at', 'desc');

            // Optional filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('borrower_id')) {
                $query->where('borrower_id', $request->borrower_id);
            }

            $loans = $query->get();

            // Transform data for mobile app
            $loansData = $loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'loan_number' => 'LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT),
                    'borrower_id' => $loan->borrower_id,
                    'borrower_name' => $loan->borrower ? $loan->borrower->name : 'Unknown',
                    'loan_amount' => $loan->loan_amount,
                    'interest_rate' => $loan->interest_rate,
                    'total_amount_due' => $loan->total_amount_due,
                    'amount_paid' => $loan->amount_paid ?? 0,
                    'balance' => $loan->balance,
                    'disbursement_date' => $loan->disbursement_date ? $loan->disbursement_date->format('Y-m-d') : null,
                    'due_date' => $loan->due_date ? $loan->due_date->format('Y-m-d') : null,
                    'purpose' => $loan->purpose,
                    'status' => $loan->status,
                    'duration_months' => $loan->duration_months,
                    'created_at' => $loan->created_at ? $loan->created_at->format('Y-m-d H:i:s') : null,
                    'meeting_date' => $loan->meeting && $loan->meeting->meeting_date ? $loan->meeting->meeting_date->format('Y-m-d') : null,
                    'is_overdue' => $loan->due_date && $loan->due_date->isPast() && $loan->status === 'active',
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Loans retrieved successfully',
                'code' => 1,
                'is_admin' => $isAdmin,
                'data' => $loansData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving loans: ' . $e->getMessage(),
                'code' => 0,
                'data' => []
            ], 500);
        }
    }

    /**
     * Get loan details
     * GET /api/vsla/loans/{id}
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'code' => 0,
                ], 401);
            }

            $loan = VslaLoan::with(['borrower', 'meeting', 'cycle', 'loanTransactions'])
                ->find($id);

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found',
                    'code' => 0,
                ], 404);
            }

            // Get repayment transactions only
            $repaymentTransactions = $loan->loanTransactions()
                ->where('transaction_type', 'repayment')
                ->orderBy('transaction_date', 'asc')
                ->get();

            // Calculate repayment details
            $runningBalance = $loan->total_amount_due;
            $repayments = [];
            $paymentNumber = 1;

            foreach ($repaymentTransactions as $transaction) {
                $balanceBefore = $runningBalance;
                $paymentAmount = abs($transaction->amount);
                
                // Simple allocation: interest first, then principal
                $interestDue = $loan->total_amount_due - $loan->loan_amount;
                $interestPaid = min($paymentAmount, max(0, $interestDue - ($loan->amount_paid - $paymentAmount)));
                $principalPaid = $paymentAmount - $interestPaid;
                
                $runningBalance -= $paymentAmount;
                
                $repayments[] = [
                    'id' => $transaction->id,
                    'loan_id' => $loan->id,
                    'payment_number' => $paymentNumber++,
                    'payment_method' => $transaction->payment_method ?? 'cash',
                    'amount' => $paymentAmount,
                    'principal_paid' => $principalPaid,
                    'interest_paid' => $interestPaid,
                    'late_fee_paid' => 0,
                    'payment_date' => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : null,
                    'balance_before' => $balanceBefore,
                    'balance_after' => max(0, $runningBalance),
                    'receipt_number' => 'RCP-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                ];
            }

            $loanData = [
                'id' => $loan->id,
                'loan_number' => 'LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT),
                'borrower_id' => $loan->borrower_id,
                'borrower_name' => $loan->borrower ? $loan->borrower->name : 'Unknown',
                'borrower_phone' => $loan->borrower ? $loan->borrower->phone : null,
                'loan_amount' => $loan->loan_amount,
                'interest_rate' => $loan->interest_rate,
                'total_amount_due' => $loan->total_amount_due,
                'amount_paid' => $loan->amount_paid ?? 0,
                'balance' => $loan->balance,
                'disbursement_date' => $loan->disbursement_date ? $loan->disbursement_date->format('Y-m-d') : null,
                'due_date' => $loan->due_date ? $loan->due_date->format('Y-m-d') : null,
                'purpose' => $loan->purpose,
                'status' => $loan->status,
                'duration_months' => $loan->duration_months,
                'cycle_name' => $loan->cycle ? $loan->cycle->name : null,
                'meeting_date' => $loan->meeting && $loan->meeting->meeting_date ? $loan->meeting->meeting_date->format('Y-m-d') : null,
                'meeting_number' => $loan->meeting ? $loan->meeting->meeting_number : null,
                'created_at' => $loan->created_at ? $loan->created_at->format('Y-m-d H:i:s') : null,
                'is_overdue' => $loan->due_date && $loan->due_date->isPast() && $loan->status === 'active',
                'days_overdue' => $loan->due_date && $loan->due_date->isPast() && $loan->status === 'active' 
                    ? $loan->due_date->diffInDays(now()) : 0,
                'repayments' => $repayments,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Loan details retrieved successfully',
                'code' => 1,
                'data' => $loanData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving loan details: ' . $e->getMessage(),
                'code' => 0,
            ], 500);
        }
    }

    /**
     * Record loan repayment
     * POST /api/vsla/loans/{id}/repayments
     */
    public function recordRepayment(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'code' => 0,
                ], 401);
            }

            $loan = VslaLoan::find($id);
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found',
                    'code' => 0,
                ], 404);
            }

            // Validate input
            $request->validate([
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'payment_date' => 'required|date',
            ]);

            $amount = floatval($request->amount);
            
            // Check if amount exceeds balance
            if ($amount > $loan->balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds remaining balance',
                    'code' => 0,
                ], 400);
            }

            // Update loan
            $loan->amount_paid = ($loan->amount_paid ?? 0) + $amount;
            $loan->balance = $loan->total_amount_due - $loan->amount_paid;
            
            // Mark as paid if fully repaid
            if ($loan->balance <= 0) {
                $loan->status = 'paid';
                $loan->balance = 0;
            }
            
            $loan->save();

            // Create transaction record for the repayment
            $transaction = LoanTransaction::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'transaction_date' => $request->payment_date,
                'transaction_type' => 'repayment',
                'payment_method' => $request->payment_method,
                'description' => 'Loan repayment via ' . $request->payment_method,
                'created_by_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'code' => 1,
                'data' => [
                    'loan_id' => $loan->id,
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'payment_date' => $request->payment_date,
                    'new_balance' => $loan->balance,
                    'amount_paid' => $loan->amount_paid,
                    'status' => $loan->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording payment: ' . $e->getMessage(),
                'code' => 0,
            ], 500);
        }
    }
}
