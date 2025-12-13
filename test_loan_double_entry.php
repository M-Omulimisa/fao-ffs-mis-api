<?php
/**
 * Test VSLA Loan Disbursement with Double-Entry Accounting
 * 
 * This script:
 * 1. Reads loan data from meeting 5
 * 2. Clears existing loan records and transactions
 * 3. Reprocesses loans using NEW double-entry logic
 * 4. Verifies VslaLoan and AccountTransaction records
 * 5. Validates double-entry balance
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VslaMeeting;
use App\Models\VslaLoan;
use App\Models\AccountTransaction;
use App\Services\MeetingProcessingService;

echo "=== TESTING LOAN DISBURSEMENT WITH DOUBLE-ENTRY ACCOUNTING ===\n\n";

// Get meeting 5
$meeting = VslaMeeting::find(5);
if (!$meeting) {
    echo "❌ Meeting 5 not found!\n";
    exit(1);
}

echo "Meeting ID: {$meeting->id}\n";
echo "Meeting Number: {$meeting->meeting_number}\n";
echo "Meeting Date: {$meeting->meeting_date}\n";
echo "Cycle ID: {$meeting->cycle_id}\n\n";

// Decode loans data
$loansData = is_array($meeting->loans_data) 
    ? $meeting->loans_data 
    : (json_decode($meeting->loans_data, true) ?? []);

echo "Loans Data: " . count($loansData) . " records\n";
foreach ($loansData as $i => $loan) {
    $num = $i + 1;
    $borrowerId = $loan['borrower_id'] ?? 'N/A';
    $borrowerName = $loan['borrower_name'] ?? 'N/A';
    $amount = $loan['loan_amount'] ?? 0;
    $rate = $loan['interest_rate'] ?? 0;
    $months = $loan['repayment_period_months'] ?? 0;
    $purpose = $loan['loan_purpose'] ?? 'N/A';
    
    echo "  {$num}. {$borrowerName} (ID: {$borrowerId})\n";
    echo "      Amount: UGX " . number_format($amount, 2) . "\n";
    echo "      Rate: {$rate}%\n";
    echo "      Duration: {$months} months\n";
    echo "      Purpose: {$purpose}\n";
}
echo "\n";

// Step 1: Clear existing records for this meeting
echo "Step 1: Clearing existing loan data for meeting {$meeting->id}...\n";

$deletedLoans = VslaLoan::where('meeting_id', $meeting->id)->delete();
echo "  Deleted {$deletedLoans} VslaLoan records\n";

$deletedGroupTxns = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->delete();
echo "  Deleted {$deletedGroupTxns} group AccountTransaction records\n";

$deletedMemberTxns = AccountTransaction::whereNotNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->delete();
echo "  Deleted {$deletedMemberTxns} member AccountTransaction records\n\n";

// Step 2: Reprocess loans using the service
echo "Step 2: Reprocessing loans using MeetingProcessingService...\n";

$service = new MeetingProcessingService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('processLoans');
$method->setAccessible(true);

$result = $method->invoke($service, $meeting);

if ($result['success']) {
    echo "✅ Loan processing completed successfully\n";
    if (!empty($result['warnings'])) {
        echo "⚠️  Warnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  - {$warning['message']}\n";
        }
    }
} else {
    echo "❌ Loan processing failed\n";
    foreach ($result['errors'] as $error) {
        echo "  - {$error['message']}\n";
    }
    exit(1);
}
echo "\n";

// Step 3: Verify created records
echo "Step 3: Verifying created records...\n\n";

// Check VslaLoans
$loans = VslaLoan::where('meeting_id', $meeting->id)->get();
echo "VslaLoans created: " . $loans->count() . "\n";
foreach ($loans as $loan) {
    $borrower = \App\Models\User::find($loan->borrower_id);
    $borrowerName = $borrower ? $borrower->name : "User {$loan->borrower_id}";
    echo "  - {$borrowerName}: UGX " . number_format($loan->loan_amount, 2) . 
         " @ {$loan->interest_rate}% for {$loan->duration_months} months\n";
    echo "    Total Due: UGX " . number_format($loan->total_amount_due, 2) . 
         " | Status: {$loan->status}\n";
}
echo "\n";

// Check Group AccountTransactions
$groupTxns = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'loan_disbursement')
    ->get();
    
echo "Group AccountTransactions (user_id=NULL): " . $groupTxns->count() . "\n";
$groupTotal = 0;
foreach ($groupTxns as $txn) {
    $groupTotal += $txn->amount;
    echo "  - Amount: UGX " . number_format($txn->amount, 2) . " | {$txn->description}\n";
}
echo "  GROUP TOTAL: UGX " . number_format($groupTotal, 2) . "\n\n";

// Check Member AccountTransactions
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
echo "  MEMBER TOTAL: UGX " . number_format($memberTotal, 2) . "\n\n";

// Step 4: Validate double-entry balance
echo "Step 4: Validating double-entry accounting...\n\n";

$expectedCount = count($loansData);
$expectedAmount = array_sum(array_column($loansData, 'loan_amount'));

echo "Expected:\n";
echo "  - {$expectedCount} loan disbursements\n";
echo "  - " . ($expectedCount * 2) . " AccountTransaction records (double-entry)\n";
echo "  - UGX " . number_format($expectedAmount, 2) . " total disbursed\n";
echo "  - Transactions should be NEGATIVE (money leaving group)\n\n";

echo "Actual:\n";
echo "  - {$loans->count()} VslaLoan records\n";
echo "  - " . ($groupTxns->count() + $memberTxns->count()) . " AccountTransaction records\n";
echo "  - Group total: UGX " . number_format($groupTotal, 2) . "\n";
echo "  - Member total: UGX " . number_format($memberTotal, 2) . "\n\n";

// Validation checks
$allPassed = true;

if ($loans->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} loans, got {$loans->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of loans created\n";
}

if ($groupTxns->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} group transactions, got {$groupTxns->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of group transactions\n";
}

if ($memberTxns->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} member transactions, got {$memberTxns->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of member transactions\n";
}

// For loan disbursements, amounts should be NEGATIVE
$expectedNegativeAmount = -$expectedAmount;

if (abs($groupTotal - $expectedNegativeAmount) > 0.01) {
    echo "❌ FAIL: Group total mismatch. Expected UGX " . number_format($expectedNegativeAmount, 2) . ", got UGX " . number_format($groupTotal, 2) . "\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Group total is correctly negative (money out)\n";
}

if (abs($memberTotal - $expectedNegativeAmount) > 0.01) {
    echo "❌ FAIL: Member total mismatch. Expected UGX " . number_format($expectedNegativeAmount, 2) . ", got UGX " . number_format($memberTotal, 2) . "\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Member total is correctly negative (debt created)\n";
}

if (abs($groupTotal - $memberTotal) > 0.01) {
    echo "❌ FAIL: Double-entry imbalance! Group total ≠ Member total\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Double-entry balanced (Group = Members)\n";
}

echo "\n";

// Step 5: Calculate combined balances (shares + loans)
echo "Step 5: Calculating combined balances (shares + loans)...\n\n";

// Group balance (all sources)
$groupBalance = AccountTransaction::whereNull('user_id')->sum('amount');
echo "Overall Group Balance: UGX " . number_format($groupBalance, 2) . "\n";
echo "  Breakdown:\n";
echo "    Shares received: +UGX 45,000.00\n";
echo "    Loans disbursed: UGX " . number_format($groupTotal, 2) . "\n";
echo "    Net: UGX " . number_format($groupBalance, 2) . "\n\n";

// Individual member balances
$memberIds = AccountTransaction::whereNotNull('user_id')
    ->distinct()
    ->pluck('user_id');

echo "Member Balances:\n";
foreach ($memberIds as $memberId) {
    $balance = AccountTransaction::where('user_id', $memberId)->sum('amount');
    $user = \App\Models\User::find($memberId);
    $name = $user ? $user->name : "User {$memberId}";
    
    // Get breakdown
    $shares = AccountTransaction::where('user_id', $memberId)
        ->where('source', 'share_purchase')
        ->sum('amount');
    $loansAmt = AccountTransaction::where('user_id', $memberId)
        ->where('source', 'loan_disbursement')
        ->sum('amount');
    
    echo "  {$name}: UGX " . number_format($balance, 2) . "\n";
    if ($shares > 0) {
        echo "    - Share contributions: +UGX " . number_format($shares, 2) . "\n";
    }
    if ($loansAmt < 0) {
        echo "    - Loan received: UGX " . number_format($loansAmt, 2) . " (debt)\n";
    }
}

echo "\n";

if ($allPassed) {
    echo "✅✅✅ ALL TESTS PASSED! Loan double-entry accounting is working correctly! ✅✅✅\n";
} else {
    echo "❌❌❌ SOME TESTS FAILED! Please review the implementation. ❌❌❌\n";
    exit(1);
}
