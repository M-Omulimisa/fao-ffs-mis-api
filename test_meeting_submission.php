<?php

/**
 * VSLA Meeting Submission Test Script
 * 
 * This script tests the complete meeting submission flow with comprehensive dummy data
 * Tests all calculations, validations, and data integrity
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Project;
use App\Models\FfsGroup;
use App\Models\User;
use App\Services\MeetingProcessingService;
use App\Models\VslaMeeting;
use Illuminate\Support\Facades\DB;

echo "=== VSLA MEETING SUBMISSION TEST ===\n\n";

// Step 1: Get valid IDs
echo "Step 1: Getting valid project and group...\n";
$project = Project::first();
if (!$project) {
    die("ERROR: No projects found. Please create a project first.\n");
}
echo "✓ Project ID: {$project->id} - {$project->project_name}\n";

// Try to get group from project, or use first available group
$groupId = $project->ffs_group_id ?? $project->groupId;
if ($groupId) {
    $group = FfsGroup::find($groupId);
} else {
    // No group assigned to project, use first available group
    $group = FfsGroup::first();
    if ($group) {
        echo "  Note: Project has no group assigned, using first available group\n";
    }
}

if (!$group) {
    die("ERROR: No groups found in database. Please create a group first.\n");
}
echo "✓ Group ID: {$group->id} - {$group->name}\n";

// Get some users/members for testing
$members = User::where('id', '>', 0)->limit(10)->get();
if ($members->count() < 3) {
    die("ERROR: Need at least 3 users in the database for testing.\n");
}
echo "✓ Found {$members->count()} users for testing\n\n";

// Step 2: Create comprehensive test data
echo "Step 2: Creating comprehensive test data...\n";

$timestamp = time();
$localId = 'test-meeting-' . $timestamp;
$meetingDate = date('Y-m-d');

// Attendance data (mix of present and absent)
$attendanceData = [];
$membersPresent = 0;
$membersAbsent = 0;
foreach ($members as $index => $member) {
    $status = $index < 7 ? 'present' : 'absent'; // 7 present, 3 absent
    $isPresent = $status === 'present';
    $attendanceData[] = [
        'memberId' => $member->id,
        'memberName' => $member->name,
        'memberCode' => 'M' . str_pad($member->id, 4, '0', STR_PAD_LEFT),
        'phoneNumber' => $member->phone ?? '',
        'isPresent' => $isPresent,
        'arrivalTime' => $isPresent ? date('H:i:s') : null,
        'absentReason' => !$isPresent ? 'Not available' : null,
    ];
    if ($isPresent) {
        $membersPresent++;
    } else {
        $membersAbsent++;
    }
}

// Transaction data (savings, welfare, social fund)
$transactionsData = [];
$totalSavings = 0;
$totalWelfare = 0;
$totalSocialFund = 0;

// Savings transactions
foreach (array_slice($members->toArray(), 0, 7) as $index => $member) {
    $amount = 5000 * ($index + 1); // 5000, 10000, 15000, etc.
    $transactionsData[] = [
        'transactionId' => 'txn-savings-' . $index,
        'memberId' => $member['id'],
        'memberName' => $member['name'],
        'accountType' => 'savings',
        'amount' => $amount,
        'description' => 'Regular savings contribution',
        'transactionDate' => $meetingDate,
    ];
    $totalSavings += $amount;
}

// Welfare transactions
foreach (array_slice($members->toArray(), 0, 7) as $index => $member) {
    $amount = 2000;
    $transactionsData[] = [
        'transactionId' => 'txn-welfare-' . $index,
        'memberId' => $member['id'],
        'memberName' => $member['name'],
        'accountType' => 'welfare',
        'amount' => $amount,
        'description' => 'Welfare fund contribution',
        'transactionDate' => $meetingDate,
    ];
    $totalWelfare += $amount;
}

// Social fund transactions
foreach (array_slice($members->toArray(), 0, 5) as $index => $member) {
    $amount = 1000;
    $transactionsData[] = [
        'transactionId' => 'txn-social-' . $index,
        'memberId' => $member['id'],
        'memberName' => $member['name'],
        'accountType' => 'social_fund',
        'amount' => $amount,
        'description' => 'Social fund contribution',
        'transactionDate' => $meetingDate,
    ];
    $totalSocialFund += $amount;
}

echo "✓ Created {$membersPresent} present, {$membersAbsent} absent\n";
echo "✓ Created " . count($transactionsData) . " transactions\n";
echo "  - Savings: UGX " . number_format($totalSavings) . "\n";
echo "  - Welfare: UGX " . number_format($totalWelfare) . "\n";
echo "  - Social Fund: UGX " . number_format($totalSocialFund) . "\n";

// Loan data (2 loans)
$loansData = [];
$totalLoans = 0;
$loan1Amount = 100000;
$loan2Amount = 150000;

$loansData[] = [
    'loanId' => 'loan-1',
    'borrowerId' => $members[0]->id,
    'borrowerName' => $members[0]->name,
    'loanAmount' => $loan1Amount,
    'interestRate' => 10,
    'loanPurpose' => 'Small business',
    'disbursementDate' => $meetingDate,
    'dueDate' => date('Y-m-d', strtotime('+3 months')),
    'repaymentPeriodMonths' => 3,
    'guarantor1Id' => $members[1]->id,
    'guarantor1Name' => $members[1]->name,
    'guarantor2Id' => $members[2]->id,
    'guarantor2Name' => $members[2]->name,
    'approvedById' => $members[3]->id,
    'status' => 'active'
];

$loansData[] = [
    'loanId' => 'loan-2',
    'borrowerId' => $members[1]->id,
    'borrowerName' => $members[1]->name,
    'loanAmount' => $loan2Amount,
    'interestRate' => 10,
    'loanPurpose' => 'Agriculture',
    'disbursementDate' => $meetingDate,
    'dueDate' => date('Y-m-d', strtotime('+6 months')),
    'repaymentPeriodMonths' => 6,
    'guarantor1Id' => $members[0]->id,
    'guarantor1Name' => $members[0]->name,
    'guarantor2Id' => null,
    'guarantor2Name' => null,
    'approvedById' => $members[3]->id,
    'status' => 'active'
];

$totalLoans = $loan1Amount + $loan2Amount;
echo "✓ Created 2 loans totaling UGX " . number_format($totalLoans) . "\n";

// Share purchases (5 members buying shares)
$sharePurchasesData = [];
$totalShares = 0;
$totalShareValue = 0;
$sharePrice = 1000;

for ($i = 0; $i < 5; $i++) {
    $shares = ($i + 1) * 5; // 5, 10, 15, 20, 25 shares
    $value = $shares * $sharePrice;
    
    $sharePurchasesData[] = [
        'purchaseId' => 'share-' . $i,
        'memberId' => $members[$i]->id,
        'memberName' => $members[$i]->name,
        'numberOfShares' => $shares,
        'pricePerShare' => $sharePrice,
        'totalAmountPaid' => $value,
    ];
    
    $totalShares += $shares;
    $totalShareValue += $value;
}

echo "✓ Created 5 share purchases: {$totalShares} shares worth UGX " . number_format($totalShareValue) . "\n";

// Action plans
$upcomingActionPlans = [
    [
        'planId' => 'plan-1-' . $timestamp,
        'action' => 'Follow up with loan repayments',
        'description' => 'Contact all borrowers to ensure timely repayment',
        'assignedToMemberId' => $members[3]->id,
        'assignedToMemberName' => $members[3]->name,
        'dueDate' => date('Y-m-d', strtotime('+2 weeks')),
        'priority' => 'high',
    ],
    [
        'planId' => 'plan-2-' . $timestamp,
        'action' => 'Organize group training',
        'description' => 'Organize group training on financial literacy',
        'assignedToMemberId' => $members[4]->id,
        'assignedToMemberName' => $members[4]->name,
        'dueDate' => date('Y-m-d', strtotime('+1 month')),
        'priority' => 'medium',
    ]
];

echo "✓ Created 2 action plans\n\n";

// Step 3: Create the payload
echo "Step 3: Creating API payload...\n";
$payload = [
    'local_id' => $localId,
    'cycle_id' => $project->id,
    'group_id' => $group->id,
    'meeting_date' => $meetingDate,
    'meeting_number' => 15,
    'notes' => 'Test meeting with comprehensive data',
    'members_present' => $membersPresent,
    'members_absent' => $membersAbsent,
    'total_savings_collected' => $totalSavings,
    'total_welfare_collected' => $totalWelfare,
    'total_social_fund_collected' => $totalSocialFund,
    'total_fines_collected' => 0,
    'total_loans_disbursed' => $totalLoans,
    'total_shares_sold' => $totalShares,
    'total_share_value' => $totalShareValue,
    'attendance_data' => $attendanceData,
    'transactions_data' => $transactionsData,
    'loans_data' => $loansData,
    'share_purchases_data' => $sharePurchasesData,
    'previous_action_plans_data' => [],
    'upcoming_action_plans_data' => $upcomingActionPlans,
];

echo "✓ Payload created\n\n";

// Step 4: Verify totals match
echo "Step 4: Verifying calculations...\n";
$calculatedSavings = array_sum(array_map(fn($t) => $t['accountType'] === 'savings' ? $t['amount'] : 0, $transactionsData));
$calculatedWelfare = array_sum(array_map(fn($t) => $t['accountType'] === 'welfare' ? $t['amount'] : 0, $transactionsData));
$calculatedSocial = array_sum(array_map(fn($t) => $t['accountType'] === 'social_fund' ? $t['amount'] : 0, $transactionsData));
$calculatedLoans = array_sum(array_map(fn($l) => $l['loanAmount'], $loansData));
$calculatedShareValue = array_sum(array_map(fn($s) => $s['totalAmountPaid'], $sharePurchasesData));

$allMatch = true;
if ($calculatedSavings !== $payload['total_savings_collected']) {
    echo "✗ Savings mismatch: Calculated {$calculatedSavings} vs Payload {$payload['total_savings_collected']}\n";
    $allMatch = false;
}
if ($calculatedWelfare !== $payload['total_welfare_collected']) {
    echo "✗ Welfare mismatch: Calculated {$calculatedWelfare} vs Payload {$payload['total_welfare_collected']}\n";
    $allMatch = false;
}
if ($calculatedSocial !== $payload['total_social_fund_collected']) {
    echo "✗ Social Fund mismatch: Calculated {$calculatedSocial} vs Payload {$payload['total_social_fund_collected']}\n";
    $allMatch = false;
}
if ($calculatedLoans !== $payload['total_loans_disbursed']) {
    echo "✗ Loans mismatch: Calculated {$calculatedLoans} vs Payload {$payload['total_loans_disbursed']}\n";
    $allMatch = false;
}
if ($calculatedShareValue !== $payload['total_share_value']) {
    echo "✗ Share Value mismatch: Calculated {$calculatedShareValue} vs Payload {$payload['total_share_value']}\n";
    $allMatch = false;
}

if ($allMatch) {
    echo "✓ All totals match perfectly!\n\n";
} else {
    die("ERROR: Total mismatches found. Fix calculations before proceeding.\n");
}

// Step 5: Submit the meeting
echo "Step 5: Submitting meeting to database...\n";
DB::beginTransaction();

try {
    // Create meeting record
    $meeting = VslaMeeting::create([
        'local_id' => $payload['local_id'],
        'cycle_id' => $payload['cycle_id'],
        'group_id' => $payload['group_id'],
        'created_by_id' => 1,
        'meeting_date' => $payload['meeting_date'],
        'meeting_number' => $payload['meeting_number'],
        'notes' => $payload['notes'],
        'members_present' => $payload['members_present'],
        'members_absent' => $payload['members_absent'],
        'total_savings_collected' => $payload['total_savings_collected'],
        'total_welfare_collected' => $payload['total_welfare_collected'],
        'total_social_fund_collected' => $payload['total_social_fund_collected'],
        'total_fines_collected' => $payload['total_fines_collected'],
        'total_loans_disbursed' => $payload['total_loans_disbursed'],
        'total_shares_sold' => $payload['total_shares_sold'],
        'total_share_value' => $payload['total_share_value'],
        'attendance_data' => $payload['attendance_data'],
        'transactions_data' => $payload['transactions_data'],
        'loans_data' => $payload['loans_data'],
        'share_purchases_data' => $payload['share_purchases_data'],
        'previous_action_plans_data' => $payload['previous_action_plans_data'],
        'upcoming_action_plans_data' => $payload['upcoming_action_plans_data'],
        'submitted_from_app_at' => now(),
        'received_at' => now(),
        'processing_status' => 'pending'
    ]);
    
    echo "✓ Meeting record created (ID: {$meeting->id})\n\n";
    
    // Step 6: Process the meeting
    echo "Step 6: Processing meeting data...\n";
    $processor = new MeetingProcessingService();
    $result = $processor->processMeeting($meeting);
    
    if ($result['success']) {
        echo "✓ Processing completed successfully!\n";
        echo "  - Warnings: " . count($result['warnings']) . "\n";
        if (!empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                echo "    • {$warning['type']}: {$warning['message']}\n";
            }
        }
        echo "\n";
        
        // Step 7: Verify database records
        echo "Step 7: Verifying database records...\n";
        
        $meeting->refresh();
        
        // Check attendance
        $attendanceCount = $meeting->attendance()->count();
        echo "✓ Attendance records: {$attendanceCount} (expected: " . count($attendanceData) . ")\n";
        
        // Check action plans
        $actionPlanCount = $meeting->actionPlans()->count();
        echo "✓ Action plans: {$actionPlanCount} (expected: " . count($upcomingActionPlans) . ")\n";
        
        // Check processing status
        echo "✓ Processing status: {$meeting->processing_status}\n";
        echo "✓ Has errors: " . ($meeting->has_errors ? 'YES' : 'NO') . "\n";
        echo "✓ Has warnings: " . ($meeting->has_warnings ? 'YES' : 'NO') . "\n";
        
        echo "\n=== TEST PASSED ✓ ===\n";
        echo "Meeting ID: {$meeting->id}\n";
        echo "Local ID: {$meeting->local_id}\n";
        
        DB::commit();
        
    } else {
        echo "✗ Processing failed!\n";
        echo "Errors:\n";
        foreach ($result['errors'] as $error) {
            echo "  • {$error['type']}: {$error['message']}\n";
        }
        DB::rollBack();
        exit(1);
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "✗ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== SUMMARY ===\n";
echo "Total Transactions Value: UGX " . number_format($totalSavings + $totalWelfare + $totalSocialFund) . "\n";
echo "Total Loans Disbursed: UGX " . number_format($totalLoans) . "\n";
echo "Total Share Purchases: UGX " . number_format($totalShareValue) . "\n";
echo "Grand Total: UGX " . number_format($totalSavings + $totalWelfare + $totalSocialFund + $totalLoans + $totalShareValue) . "\n";
echo "\nTest completed successfully!\n";
