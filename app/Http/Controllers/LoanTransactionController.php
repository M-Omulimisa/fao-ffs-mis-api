<?php

namespace App\Http\Controllers;

use App\Models\LoanTransaction;
use App\Models\VslaLoan;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * LoanTransaction API Controller
 * 
 * Handles loan payment, penalty, and waiver operations with double-entry accounting.
 * When a loan payment is made, creates:
 * - 1 LoanTransaction (payment record)
 * - 2 AccountTransactions (group receives cash + member pays cash)
 */
class LoanTransactionController extends Controller
{
    /**
     * Get loan transaction history
     * 
     * @param int $loanId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($loanId)
    {
        $loan = VslaLoan::find($loanId);
        
        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found',
            ], 404);
        }

        $transactions = LoanTransaction::getLoanHistory($loanId);
        $balance = LoanTransaction::calculateLoanBalance($loanId);

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => [
                    'id' => $loan->id,
                    'borrower' => $loan->borrower->name ?? 'Unknown',
                    'loan_amount' => $loan->loan_amount,
                    'total_due' => $loan->total_amount_due,
                    'balance' => $balance,
                    'status' => $loan->status,
                ],
                'transactions' => $transactions,
            ],
        ]);
    }

    /**
     * Get loan balance
     * 
     * @param int $loanId
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance($loanId)
    {
        $loan = VslaLoan::find($loanId);
        
        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found',
            ], 404);
        }

        $balance = LoanTransaction::calculateLoanBalance($loanId);
        $isPaid = abs($balance) < 0.01;

        return response()->json([
            'success' => true,
            'data' => [
                'loan_id' => $loanId,
                'balance' => $balance,
                'is_paid' => $isPaid,
                'status' => $loan->status,
            ],
        ]);
    }

    /**
     * Create loan payment
     * 
     * Creates:
     * - 1 LoanTransaction (payment)
     * - 2 AccountTransactions (group + member, double-entry)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:vsla_loans,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $loan = VslaLoan::findOrFail($request->loan_id);
            $amount = abs(floatval($request->amount));
            $description = $request->description ?? 'Loan repayment';

            // 1. Create LoanTransaction (payment)
            $loanTransaction = LoanTransaction::create([
                'loan_id' => $loan->id,
                'amount' => $amount, // Positive reduces debt
                'transaction_date' => $request->transaction_date,
                'description' => $description,
                'type' => LoanTransaction::TYPE_PAYMENT,
                'created_by_id' => auth()->id() ?? $loan->created_by_id,
            ]);

            // 2. Create AccountTransaction for Group (receives cash)
            AccountTransaction::create([
                'user_id' => null, // Group account
                'amount' => $amount, // Positive = cash in
                'transaction_date' => $request->transaction_date,
                'description' => "Loan payment from " . ($loan->borrower->name ?? 'member'),
                'source' => 'loan_repayment',
                'related_disbursement_id' => $loan->id,
                'created_by_id' => auth()->id() ?? $loan->created_by_id,
            ]);

            // 3. Create AccountTransaction for Member (pays cash)
            AccountTransaction::create([
                'user_id' => $loan->borrower_id,
                'amount' => $amount, // Positive = debt reduced
                'transaction_date' => $request->transaction_date,
                'description' => 'Loan payment - ' . $description,
                'source' => 'loan_repayment',
                'related_disbursement_id' => $loan->id,
                'created_by_id' => auth()->id() ?? $loan->created_by_id,
            ]);

            // 4. Update loan balance
            $newBalance = LoanTransaction::calculateLoanBalance($loan->id);
            $loan->balance = abs($newBalance);
            
            // Update status if fully paid
            if (abs($newBalance) < 0.01) {
                $loan->status = 'paid';
            }
            
            $loan->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'transaction' => $loanTransaction,
                    'new_balance' => $newBalance,
                    'loan_status' => $loan->status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add penalty to loan
     * 
     * Creates LoanTransaction (penalty) only, no AccountTransactions.
     * Penalties don't involve cash movement, just increase debt.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPenalty(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:vsla_loans,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $loan = VslaLoan::findOrFail($request->loan_id);
            $amount = abs(floatval($request->amount));
            $description = $request->description ?? 'Late payment penalty';

            // Create LoanTransaction (penalty increases debt)
            $loanTransaction = LoanTransaction::create([
                'loan_id' => $loan->id,
                'amount' => -$amount, // Negative increases debt
                'transaction_date' => $request->transaction_date,
                'description' => $description,
                'type' => LoanTransaction::TYPE_PENALTY,
                'created_by_id' => auth()->id() ?? $loan->created_by_id,
            ]);

            // Update loan balance
            $newBalance = LoanTransaction::calculateLoanBalance($loan->id);
            $loan->balance = abs($newBalance);
            $loan->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penalty added successfully',
                'data' => [
                    'transaction' => $loanTransaction,
                    'new_balance' => $newBalance,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add penalty: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add waiver (debt forgiveness)
     * 
     * Creates LoanTransaction (waiver) only, no AccountTransactions.
     * Waivers reduce debt without cash movement.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWaiver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:vsla_loans,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $loan = VslaLoan::findOrFail($request->loan_id);
            $amount = abs(floatval($request->amount));
            $description = $request->description ?? 'Debt waiver';

            // Create LoanTransaction (waiver reduces debt)
            $loanTransaction = LoanTransaction::create([
                'loan_id' => $loan->id,
                'amount' => $amount, // Positive reduces debt
                'transaction_date' => $request->transaction_date,
                'description' => $description,
                'type' => LoanTransaction::TYPE_WAIVER,
                'created_by_id' => auth()->id() ?? $loan->created_by_id,
            ]);

            // Update loan balance
            $newBalance = LoanTransaction::calculateLoanBalance($loan->id);
            $loan->balance = abs($newBalance);
            
            // Update status if fully paid after waiver
            if (abs($newBalance) < 0.01) {
                $loan->status = 'paid';
            }
            
            $loan->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Waiver applied successfully',
                'data' => [
                    'transaction' => $loanTransaction,
                    'new_balance' => $newBalance,
                    'loan_status' => $loan->status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply waiver: ' . $e->getMessage(),
            ], 500);
        }
    }
}
