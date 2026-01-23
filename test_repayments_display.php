<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VslaLoan;

echo "Testing Loan Repayments Display\n";
echo "================================\n\n";

// Find loan with ID 1
$loan = VslaLoan::with('loanTransactions')->find(1);

if (!$loan) {
    echo "Loan not found\n";
    exit(1);
}

echo "Loan: LN-" . str_pad($loan->id, 5, '0', STR_PAD_LEFT) . "\n";
echo "Total Due: " . number_format($loan->total_amount_due) . " UGX\n";
echo "Amount Paid: " . number_format($loan->amount_paid ?? 0) . " UGX\n";
echo "Balance: " . number_format($loan->balance) . " UGX\n\n";

echo "Loan Transactions:\n";
echo "==================\n";

foreach ($loan->loanTransactions as $transaction) {
    echo "ID: {$transaction->id}\n";
    echo "  Type: {$transaction->type}\n";
    echo "  Transaction Type: {$transaction->transaction_type}\n";
    echo "  Amount: {$transaction->amount}\n";
    echo "  Date: {$transaction->transaction_date}\n";
    echo "  Payment Method: {$transaction->payment_method}\n";
    echo "  Description: {$transaction->description}\n\n";
}

// Get repayments only
$repayments = $loan->loanTransactions()
    ->where('transaction_type', 'repayment')
    ->orderBy('transaction_date', 'asc')
    ->get();

echo "\nRepayments Only:\n";
echo "================\n";
echo "Count: " . $repayments->count() . "\n\n";

foreach ($repayments as $repayment) {
    echo "Repayment #{$repayment->id}\n";
    echo "  Amount: {$repayment->amount} UGX\n";
    echo "  Method: {$repayment->payment_method}\n";
    echo "  Date: {$repayment->transaction_date}\n\n";
}

echo "\n✅ Test Complete\n";
