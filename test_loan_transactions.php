<?php
/**
 * Test VSLA Loan System with LoanTransactions
 * 
 * Tests:
 * 1. Loan disbursement creates VslaLoan
 * 2. Loan disbursement creates 2 LoanTransactions (principal + interest)
 * 3. Loan disbursement creates 2 AccountTransactions (group + member)
 * 4. Loan balance calculation works correctly
 * 5. Integration between LoanTransaction and AccountTransaction
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VslaMeeting;
use App\Models\VslaLoan;
use App\Models\LoanTransaction;
use App\Models\AccountTransaction;
use App\Services\MeetingProcessingService;

echo "=== TESTING LOAN SYSTEM WITH LOANTRANSACTIONS ===\n\n";

// Get meeting 5
$meeting = VslaMeeting::find(5);
if (!$meeting) {
    echo "❌ Meeting 5 not found!\n";
    exit(1);
}

$loansData = is_array($meeting->loans_data) 
    ? $meeting->loans_data 
    : (json_decode($meeting->loans_data, true) ?? []);

echo "Meeting #{$meeting->meeting_number} - Date: {$meeting->meeting_date}\n";
echo "Loans in meeting: " . count($loansData) . "\n\n";

foreach ($loansData as $i => $loan) {
    $num = $i + 1;
    echo "Loan {$num}:\n";
    echo "  Borrower: {$loan['borrower_name']} (ID: {$loan['borrower_id']})\n";
    echo "  Amount: UGX " . number_format($loan['loan_amount'], 2) . "\n";
    echo "  Rate: {$loan['interest_rate']}%\n";
    echo "  Duration: {$loan['repayment_period_months']} months\n";
    echo "  Purpose: {$loan['loan_purpose']}\n\n";
}

// Step 1: Clear existing records
echo "Step 1: Clearing existing loan data for meeting {$meeting->id}...\n";

$deletedLoans = VslaLoan::where('meeting_id', $meeting->id)->delete();
echo "  Deleted {$deletedLoans} VslaLoan records\n";

$deletedLoanTxns = LoanTransaction::whereIn('loan_id', function($query) use ($meeting) {
    $query->select('id')->from('vsla_loans')->where('meeting_id', $meeting->id);
})->delete();
echo "  Deleted {$deletedLoanTxns} LoanTransaction records\n";

$deletedGroupAccTxns = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->delete();
echo "  Deleted {$deletedGroupAccTxns} group AccountTransaction records\n";

$deletedMemberAccTxns = AccountTransaction::whereNotNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->delete();
echo "  Deleted {$deletedMemberAccTxns} member AccountTransaction records\n\n";

// Step 2: Reprocess loans
echo "Step 2: Reprocessing loans with NEW LoanTransaction system...\n";

$service = new MeetingProcessingService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('processLoans');
$method->setAccessible(true);

$result = $method->invoke($service, $meeting);

if ($result['success']) {
    echo "✅ Loan processing completed successfully\n\n";
} else {
    echo "❌ Loan processing failed\n";
    foreach ($result['errors'] as $error) {
        echo "  - {$error['message']}\n";
    }
    exit(1);
}

// Step 3: Verify created records
echo "Step 3: Verifying created records...\n\n";

$loans = VslaLoan::where('meeting_id', $meeting->id)->get();
echo "=== VSLA LOANS ===\n";
echo "Count: " . $loans->count() . "\n";
foreach ($loans as $loan) {
    $borrower = \App\Models\User::find($loan->borrower_id);
    echo "Loan ID {$loan->id}:\n";
    echo "  Borrower: {$borrower->name}\n";
    echo "  Principal: UGX " . number_format($loan->loan_amount, 2) . "\n";
    echo "  Rate: {$loan->interest_rate}%\n";
    echo "  Total Due: UGX " . number_format($loan->total_amount_due, 2) . "\n";
    echo "  Status: {$loan->status}\n\n";
    
    // Get LoanTransactions for this loan
    $loanTxns = LoanTransaction::where('loan_id', $loan->id)->get();
    echo "  === LOAN TRANSACTIONS (for Loan #{$loan->id}) ===\n";
    echo "  Count: " . $loanTxns->count() . "\n";
    $loanBalance = 0;
    foreach ($loanTxns as $txn) {
        $loanBalance += $txn->amount;
        echo "  - {$txn->type}: UGX " . number_format($txn->amount, 2) . " | {$txn->description}\n";
    }
    echo "  LOAN BALANCE: UGX " . number_format($loanBalance, 2) . "\n";
    echo "  (Negative = member owes this amount)\n\n";
}

echo "=== ACCOUNT TRANSACTIONS (Group/Member Cash Flow) ===\n\n";

$groupTxns = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->get();
    
echo "Group AccountTransactions: " . $groupTxns->count() . "\n";
$groupTotal = 0;
foreach ($groupTxns as $txn) {
    $groupTotal += $txn->amount;
    echo "  - UGX " . number_format($txn->amount, 2) . " | {$txn->description}\n";
}
echo "GROUP TOTAL: UGX " . number_format($groupTotal, 2) . "\n\n";

$memberTxns = AccountTransaction::whereNotNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->get();
    
echo "Member AccountTransactions: " . $memberTxns->count() . "\n";
$memberTotal = 0;
foreach ($memberTxns as $txn) {
    $memberTotal += $txn->amount;
    echo "  - User {$txn->user_id}: UGX " . number_format($txn->amount, 2) . " | {$txn->description}\n";
}
echo "MEMBER TOTAL: UGX " . number_format($memberTotal, 2) . "\n\n";

// Step 4: Validation
echo "Step 4: Validating loan system...\n\n";

$expectedCount = count($loansData);
$expectedPrincipal = array_sum(array_column($loansData, 'loan_amount'));

$allPassed = true;

// Check VslaLoans
if ($loans->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} loans, got {$loans->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of loans created\n";
}

// Check LoanTransactions (2 per loan: principal + interest)
$expectedLoanTxns = $expectedCount * 2;
$actualLoanTxns = LoanTransaction::whereIn('loan_id', $loans->pluck('id'))->count();
if ($actualLoanTxns !== $expectedLoanTxns) {
    echo "❌ FAIL: Expected {$expectedLoanTxns} LoanTransactions (principal + interest), got {$actualLoanTxns}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of LoanTransactions (principal + interest for each loan)\n";
}

// Check AccountTransactions
if ($groupTxns->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} group AccountTransactions, got {$groupTxns->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of group AccountTransactions\n";
}

if ($memberTxns->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} member AccountTransactions, got {$memberTxns->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of member AccountTransactions\n";
}

// Check amounts
$expectedNegativeAmount = -$expectedPrincipal;
if (abs($groupTotal - $expectedNegativeAmount) > 0.01) {
    echo "❌ FAIL: Group AccountTransaction total mismatch\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Group AccountTransaction total correct (negative)\n";
}

if (abs($memberTotal - $expectedNegativeAmount) > 0.01) {
    echo "❌ FAIL: Member AccountTransaction total mismatch\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Member AccountTransaction total correct (negative)\n";
}

// Check double-entry balance
if (abs($groupTotal - $memberTotal) > 0.01) {
    echo "❌ FAIL: AccountTransaction double-entry imbalance\n";
    $allPassed = false;
} else {
    echo "✅ PASS: AccountTransaction double-entry balanced\n";
}

// Check LoanTransaction balance matches total_due
foreach ($loans as $loan) {
    $loanBalance = LoanTransaction::where('loan_id', $loan->id)->sum('amount');
    $expectedBalance = -$loan->total_amount_due; // Negative because member owes
    if (abs($loanBalance - $expectedBalance) > 0.01) {
        echo "❌ FAIL: Loan #{$loan->id} balance mismatch. Expected " . number_format($expectedBalance, 2) . ", got " . number_format($loanBalance, 2) . "\n";
        $allPassed = false;
    } else {
        echo "✅ PASS: Loan #{$loan->id} LoanTransaction balance matches total_due\n";
    }
}

echo "\n";

// Step 5: Overall balances
echo "Step 5: Overall balances (Shares + Loans)...\n\n";

$groupBalance = AccountTransaction::whereNull('user_id')->sum('amount');
echo "Group Balance: UGX " . number_format($groupBalance, 2) . "\n";
echo "  (Shares: +45,000, Loans: {$groupTotal} = {$groupBalance})\n\n";

$memberIds = AccountTransaction::whereNotNull('user_id')->distinct()->pluck('user_id');
foreach ($memberIds as $memberId) {
    $balance = AccountTransaction::where('user_id', $memberId)->sum('amount');
    $user = \App\Models\User::find($memberId);
    echo "{$user->name}: UGX " . number_format($balance, 2) . "\n";
}

echo "\n";

if ($allPassed) {
    echo "✅✅✅ ALL TESTS PASSED! Loan system with LoanTransactions working correctly! ✅✅✅\n";
} else {
    echo "❌❌❌ SOME TESTS FAILED! ❌❌❌\n";
    exit(1);
}
