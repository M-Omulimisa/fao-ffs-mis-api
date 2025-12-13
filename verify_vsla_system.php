<?php

/**
 * VSLA System Verification Script
 * 
 * This script performs a comprehensive check of the VSLA system:
 * 1. Database structure
 * 2. Double-entry balance integrity
 * 3. Loan balance accuracy
 * 4. Meeting processing status
 * 5. Data consistency
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "          VSLA SYSTEM COMPREHENSIVE VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// 1. Database Statistics
echo "1. DATABASE STATISTICS\n";
echo "   ──────────────────────────────────────────────────────────\n";

$stats = [
    'VSLA Groups' => DB::table('ffs_groups')->where('type', 'VSLA')->count(),
    'VSLA Cycles' => DB::table('projects')->where('is_vsla_cycle', 'Yes')->count(),
    'Active Cycles' => DB::table('projects')->where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')->count(),
    'Meetings' => DB::table('vsla_meetings')->count(),
    'Loans' => DB::table('vsla_loans')->count(),
    'Shares' => DB::table('project_shares')->count(),
    'Account Transactions' => DB::table('account_transactions')->count(),
    'Loan Transactions' => DB::table('loan_transactions')->count(),
];

foreach ($stats as $label => $count) {
    echo sprintf("   %-25s %s\n", $label . ':', $count);
}

// 2. Double-Entry Balance Integrity
echo "\n2. DOUBLE-ENTRY BALANCE INTEGRITY\n";
echo "   ──────────────────────────────────────────────────────────\n";

$balanceCheck = DB::select("
    SELECT 
        source,
        SUM(CASE WHEN user_id IS NULL THEN amount ELSE 0 END) as group_total,
        SUM(CASE WHEN user_id IS NOT NULL THEN amount ELSE 0 END) as members_total,
        SUM(CASE WHEN user_id IS NULL THEN amount ELSE 0 END) - 
        SUM(CASE WHEN user_id IS NOT NULL THEN amount ELSE 0 END) as difference
    FROM account_transactions
    GROUP BY source
");

$allBalanced = true;
foreach ($balanceCheck as $row) {
    $status = abs($row->difference) < 0.01 ? '✓' : '✗';
    $statusText = abs($row->difference) < 0.01 ? 'BALANCED' : 'IMBALANCED!';
    
    if (abs($row->difference) >= 0.01) {
        $allBalanced = false;
    }
    
    echo sprintf("   %-20s Group: %10.2f | Members: %10.2f | %s %s\n", 
        ucwords(str_replace('_', ' ', $row->source)) . ':', 
        $row->group_total,
        $row->members_total,
        $status,
        $statusText
    );
}

echo "   ──────────────────────────────────────────────────────────\n";
echo sprintf("   Overall Status: %s\n", $allBalanced ? '✓ ALL BALANCED' : '✗ IMBALANCE DETECTED!');

// 3. Overall Balance Summary
echo "\n3. OVERALL BALANCE SUMMARY\n";
echo "   ──────────────────────────────────────────────────────────\n";

$groupBalance = DB::table('account_transactions')->whereNull('user_id')->sum('amount');
$membersBalance = DB::table('account_transactions')->whereNotNull('user_id')->sum('amount');

echo sprintf("   Group Balance:         %10.2f\n", $groupBalance);
echo sprintf("   Members Total Balance: %10.2f\n", $membersBalance);
echo sprintf("   Difference:            %10.2f %s\n", 
    $groupBalance - $membersBalance,
    abs($groupBalance - $membersBalance) < 0.01 ? '✓' : '✗'
);

// 4. Loan Balance Accuracy
echo "\n4. LOAN BALANCE VERIFICATION\n";
echo "   ──────────────────────────────────────────────────────────\n";

$loans = DB::select("
    SELECT 
        vl.id,
        vl.loan_amount,
        vl.interest_rate,
        vl.total_amount_due,
        COALESCE(SUM(lt.amount), 0) as calculated_balance,
        vl.status
    FROM vsla_loans vl
    LEFT JOIN loan_transactions lt ON vl.id = lt.loan_id
    GROUP BY vl.id, vl.loan_amount, vl.interest_rate, vl.total_amount_due, vl.status
    ORDER BY vl.id
    LIMIT 10
");

foreach ($loans as $loan) {
    $statusIcon = $loan->calculated_balance == 0 ? '✓' : '○';
    $statusText = $loan->calculated_balance == 0 ? 'PAID' : 'ACTIVE';
    
    echo sprintf("   Loan #%-3d Amount: %8.2f | Due: %8.2f | Balance: %8.2f %s %s\n",
        $loan->id,
        $loan->loan_amount,
        $loan->total_amount_due,
        $loan->calculated_balance,
        $statusIcon,
        $statusText
    );
}

// 5. Meeting Processing Status
echo "\n5. MEETING PROCESSING STATUS\n";
echo "   ──────────────────────────────────────────────────────────\n";

$meetings = DB::select("
    SELECT 
        id,
        processing_status,
        has_errors,
        has_warnings,
        total_shares_sold,
        total_share_value,
        total_savings_collected,
        total_loans_disbursed
    FROM vsla_meetings
    ORDER BY id DESC
    LIMIT 5
");

foreach ($meetings as $meeting) {
    $status = $meeting->processing_status == 'completed' ? '✓' : 
              ($meeting->processing_status == 'failed' ? '✗' : '○');
    
    echo sprintf("   Meeting #%-3d Status: %-12s %s | Shares: %2d | Loans: %8.2f\n",
        $meeting->id,
        strtoupper($meeting->processing_status),
        $status,
        $meeting->total_shares_sold ?? 0,
        $meeting->total_loans_disbursed ?? 0
    );
}

// 6. Data Consistency Checks
echo "\n6. DATA CONSISTENCY CHECKS\n";
echo "   ──────────────────────────────────────────────────────────\n";

// Check for orphan transactions
$orphanAccountTransactions = DB::table('account_transactions')
    ->whereNotNull('user_id')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('users')
              ->whereRaw('users.id = account_transactions.user_id');
    })
    ->count();

echo sprintf("   Orphan Account Transactions: %d %s\n", 
    $orphanAccountTransactions,
    $orphanAccountTransactions == 0 ? '✓' : '✗'
);

// Check for orphan loan transactions
$orphanLoanTransactions = DB::table('loan_transactions')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('vsla_loans')
              ->whereRaw('vsla_loans.id = loan_transactions.loan_id');
    })
    ->count();

echo sprintf("   Orphan Loan Transactions:    %d %s\n", 
    $orphanLoanTransactions,
    $orphanLoanTransactions == 0 ? '✓' : '✗'
);

// Check for loans without loan transactions
$loansWithoutTransactions = DB::select("
    SELECT COUNT(*) as count
    FROM vsla_loans vl
    LEFT JOIN loan_transactions lt ON vl.id = lt.loan_id
    WHERE lt.id IS NULL
")[0]->count;

echo sprintf("   Loans Missing Transactions:  %d %s\n", 
    $loansWithoutTransactions,
    $loansWithoutTransactions == 0 ? '✓' : '⚠'
);

// Final Verdict
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($allBalanced && 
    $orphanAccountTransactions == 0 && 
    $orphanLoanTransactions == 0 &&
    abs($groupBalance - $membersBalance) < 0.01) {
    
    echo "                  ✓ SYSTEM STATUS: HEALTHY                    \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\nAll checks passed! VSLA system is production ready.\n";
} else {
    echo "                  ⚠ SYSTEM STATUS: NEEDS ATTENTION            \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\nSome issues detected. Review the report above.\n";
}

echo "\n";
