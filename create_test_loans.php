<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaLoan;
use App\Models\Project;

echo "═══════════════════════════════════════════════════════════\n";
echo "   CREATING TEST LOANS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get cycle
$cycle = Project::where('is_vsla_cycle', 'Yes')
    ->where('is_active_cycle', 'Yes')
    ->first();

if (!$cycle) {
    echo "❌ No active cycle found\n";
    exit(1);
}

echo "Active Cycle: {$cycle->name} (ID: {$cycle->id})\n";
echo "Group ID: {$cycle->group_id}\n\n";

// Get users from this group
$users = User::where('group_id', $cycle->group_id)
    ->where('status', 'Active')
    ->limit(3)
    ->get();

if ($users->count() < 1) {
    echo "❌ No active users found in group\n";
    exit(1);
}

echo "Creating loans for {$users->count()} users...\n\n";

foreach ($users as $index => $user) {
    $loanAmount = 100000 + ($index * 50000); // 100k, 150k, 200k
    $interestRate = 10; // 10%
    $durationMonths = 3;
    $totalAmountDue = $loanAmount + ($loanAmount * $interestRate / 100);
    
    $loan = VslaLoan::create([
        'cycle_id' => $cycle->id,
        'borrower_id' => $user->id,
        'loan_amount' => $loanAmount,
        'interest_rate' => $interestRate,
        'total_amount_due' => $totalAmountDue,
        'balance' => $totalAmountDue,
        'amount_paid' => 0,
        'disbursement_date' => now(),
        'due_date' => now()->addMonths($durationMonths),
        'duration_months' => $durationMonths,
        'purpose' => 'Test loan ' . ($index + 1),
        'status' => $index === 2 ? 'paid' : 'active', // Last one is paid
    ]);
    
    echo "✅ Loan #{$loan->id} created:\n";
    echo "   Borrower: {$user->name}\n";
    echo "   Amount: " . number_format($loanAmount) . " UGX\n";
    echo "   Total Due: " . number_format($totalAmountDue) . " UGX\n";
    echo "   Status: {$loan->status}\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ Test loans created successfully!\n";
echo "═══════════════════════════════════════════════════════════\n";
