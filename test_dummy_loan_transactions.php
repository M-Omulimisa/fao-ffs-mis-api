<?php
/**
 * Test Dummy Loan Transactions
 * 
 * Creates realistic loan lifecycle scenarios:
 * 1. Loan payment (reduces balance)
 * 2. Penalty (increases balance)
 * 3. Partial payment
 * 4. Full payment
 * 5. Verify balance calculations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VslaLoan;
use App\Models\LoanTransaction;
use App\Models\AccountTransaction;

echo "=== TESTING LOAN LIFECYCLE WITH DUMMY TRANSACTIONS ===\n\n";

// Get the most recent loan
$loan = VslaLoan::orderBy('id', 'desc')->first();

if (!$loan) {
    echo "❌ No loan found! Run test_loan_transactions.php first\n";
    exit(1);
}

$borrower = \App\Models\User::find($loan->borrower_id);

echo "Working with Loan ID: {$loan->id}\n";
echo "Borrower: {$borrower->name}\n";
echo "Principal: UGX " . number_format($loan->loan_amount, 2) . "\n";
echo "Interest Rate: {$loan->interest_rate}%\n";
echo "Total Due: UGX " . number_format($loan->total_amount_due, 2) . "\n\n";

// Initial balance
$initialBalance = LoanTransaction::calculateLoanBalance($loan->id);
echo "Initial Loan Balance: UGX " . number_format($initialBalance, 2) . "\n";
echo "(Negative = amount owed)\n\n";

// Scenario 1: First partial payment (UGX 1,500)
echo "=== SCENARIO 1: Partial Payment (UGX 1,500) ===\n";
LoanTransaction::create([
    'loan_id' => $loan->id,
    'amount' => 1500, // Positive = payment reduces debt
    'transaction_date' => now()->addDays(7),
    'description' => 'First installment payment',
    'type' => LoanTransaction::TYPE_PAYMENT,
    'created_by_id' => $loan->created_by_id,
]);

// Create corresponding AccountTransactions (double-entry)
AccountTransaction::create([
    'user_id' => null, // Group receives money
    'amount' => 1500, // Positive = group receives cash
    'transaction_date' => now()->addDays(7),
    'description' => "{$borrower->name} paid loan installment",
    'source' => 'loan_repayment',
    'related_disbursement_id' => $loan->id,
    'created_by_id' => $loan->created_by_id,
]);

AccountTransaction::create([
    'user_id' => $borrower->id, // Member pays money
    'amount' => 1500, // Positive = reduces member's debt
    'transaction_date' => now()->addDays(7),
    'description' => 'Loan payment installment 1',
    'source' => 'loan_repayment',
    'related_disbursement_id' => $loan->id,
    'created_by_id' => $loan->created_by_id,
]);

$balanceAfterPayment1 = LoanTransaction::calculateLoanBalance($loan->id);
echo "Balance after payment: UGX " . number_format($balanceAfterPayment1, 2) . "\n";
echo "Amount still owed: UGX " . number_format(abs($balanceAfterPayment1), 2) . "\n\n";

// Scenario 2: Late payment penalty (UGX 200)
echo "=== SCENARIO 2: Late Payment Penalty (UGX 200) ===\n";
LoanTransaction::create([
    'loan_id' => $loan->id,
    'amount' => -200, // Negative = penalty increases debt
    'transaction_date' => now()->addDays(14),
    'description' => 'Late payment penalty - 2 weeks overdue',
    'type' => LoanTransaction::TYPE_PENALTY,
    'created_by_id' => $loan->created_by_id,
]);

$balanceAfterPenalty = LoanTransaction::calculateLoanBalance($loan->id);
echo "Balance after penalty: UGX " . number_format($balanceAfterPenalty, 2) . "\n";
echo "Amount still owed: UGX " . number_format(abs($balanceAfterPenalty), 2) . "\n\n";

// Scenario 3: Second partial payment (UGX 2,000)
echo "=== SCENARIO 3: Second Partial Payment (UGX 2,000) ===\n";
LoanTransaction::create([
    'loan_id' => $loan->id,
    'amount' => 2000,
    'transaction_date' => now()->addDays(21),
    'description' => 'Second installment payment',
    'type' => LoanTransaction::TYPE_PAYMENT,
    'created_by_id' => $loan->created_by_id,
]);

AccountTransaction::create([
    'user_id' => null,
    'amount' => 2000,
    'transaction_date' => now()->addDays(21),
    'description' => "{$borrower->name} paid loan installment 2",
    'source' => 'loan_repayment',
    'related_disbursement_id' => $loan->id,
    'created_by_id' => $loan->created_by_id,
]);

AccountTransaction::create([
    'user_id' => $borrower->id,
    'amount' => 2000,
    'transaction_date' => now()->addDays(21),
    'description' => 'Loan payment installment 2',
    'source' => 'loan_repayment',
    'related_disbursement_id' => $loan->id,
    'created_by_id' => $loan->created_by_id,
]);

$balanceAfterPayment2 = LoanTransaction::calculateLoanBalance($loan->id);
echo "Balance after payment: UGX " . number_format($balanceAfterPayment2, 2) . "\n";
echo "Amount still owed: UGX " . number_format(abs($balanceAfterPayment2), 2) . "\n\n";

// Scenario 4: Final payment to clear loan
$remainingBalance = abs($balanceAfterPayment2);
echo "=== SCENARIO 4: Final Payment (UGX " . number_format($remainingBalance, 2) . ") ===\n";
LoanTransaction::create([
    'loan_id' => $loan->id,
    'amount' => $remainingBalance,
    'transaction_date' => now()->addDays(28),
    'description' => 'Final payment - loan cleared',
    'type' => LoanTransaction::TYPE_PAYMENT,
    'created_by_id' => $loan->created_by_id,
]);

AccountTransaction::create([
    'user_id' => null,
    'amount' => $remainingBalance,
    'transaction_date' => now()->addDays(28),
    'description' => "{$borrower->name} paid final loan installment",
    'source' => 'loan_repayment',
    'related_disbursement_id' => $loan->id,
    'created_by_id' => $loan->created_by_id,
]);

AccountTransaction::create([
    'user_id' => $borrower->id,
    'amount' => $remainingBalance,
    'transaction_date' => now()->addDays(28),
    'description' => 'Loan payment - FINAL (loan cleared)',
    'source' => 'loan_repayment',
    'related_disbursement_id' => $loan->id,
    'created_by_id' => $loan->created_by_id,
]);

$finalBalance = LoanTransaction::calculateLoanBalance($loan->id);
echo "Final loan balance: UGX " . number_format($finalBalance, 2) . "\n";

if (abs($finalBalance) < 0.01) {
    echo "✅ Loan fully paid! Balance is zero.\n\n";
} else {
    echo "⚠️  Remaining balance: UGX " . number_format(abs($finalBalance), 2) . "\n\n";
}

// Full loan history
echo "=== COMPLETE LOAN HISTORY ===\n";
$history = LoanTransaction::getLoanHistory($loan->id);
echo "Total transactions: " . $history->count() . "\n\n";

$runningBalance = 0;
foreach ($history as $txn) {
    $runningBalance += $txn->amount;
    $sign = $txn->amount >= 0 ? '+' : '';
    echo $txn->transaction_date->format('Y-m-d') . " | ";
    echo str_pad($txn->type, 12) . " | ";
    echo str_pad($sign . number_format($txn->amount, 2), 15) . " | ";
    echo "Balance: " . str_pad(number_format($runningBalance, 2), 15) . " | ";
    echo $txn->description . "\n";
}

echo "\n";
echo "=== VERIFICATION ===\n";
echo "Manual calculation: " . number_format($runningBalance, 2) . "\n";
echo "Model calculation: " . number_format($finalBalance, 2) . "\n";

if (abs($runningBalance - $finalBalance) < 0.01) {
    echo "✅ Balance calculations match!\n\n";
} else {
    echo "❌ Balance mismatch!\n\n";
}

// Summary
echo "=== SUMMARY ===\n";
echo "Initial debt: UGX " . number_format(abs($initialBalance), 2) . "\n";
echo "Payments made: UGX " . number_format($remainingBalance + 1500 + 2000, 2) . "\n";
echo "Penalties: UGX 200.00\n";
echo "Final balance: UGX " . number_format($finalBalance, 2) . "\n\n";

echo "✅✅✅ Loan lifecycle testing complete! ✅✅✅\n";
