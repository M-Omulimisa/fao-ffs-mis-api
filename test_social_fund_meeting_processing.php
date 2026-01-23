<?php

/**
 * Social Fund Meeting Processing Test Script
 * 
 * Tests:
 * 1. Meeting submission with social fund contributions
 * 2. Backend parsing of social_fund_contributions_data
 * 3. MeetingProcessingService::processSocialFundContributions
 * 4. SocialFundTransaction creation
 * 5. Data validation and error handling
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\VslaMeeting;
use App\Models\Project;
use App\Models\SocialFundTransaction;
use App\Models\User;
use App\Services\MeetingProcessingService;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "SOCIAL FUND MEETING PROCESSING TEST\n";
echo "========================================\n\n";

// Test Configuration
$testGroupId = 1; // Change to your test group ID

try {
    // Step 1: Find test group and active cycle
    echo "1. Finding test group and active cycle...\n";
    
    // Find an active VSLA cycle
    $cycle = Project::where('is_vsla_cycle', 1)
        ->where('status', 'active')
        ->where('group_id', $testGroupId)
        ->first();

    if (!$cycle) {
        // Try any cycle for this group
        $cycle = Project::where('is_vsla_cycle', 1)
            ->where('group_id', $testGroupId)
            ->first();
        
        if (!$cycle) {
            throw new Exception("No cycle found for group {$testGroupId}");
        }
    }

    $cycleId = $cycle->id;
    echo "   ✓ Group ID: {$testGroupId}\n";
    echo "   ✓ Cycle ID: {$cycleId}\n";
    echo "   ✓ Cycle Name: {$cycle->title}\n\n";

    // Step 2: Get test members
    echo "2. Getting test members (using any available users)...\n";
    $members = User::orderBy('id')->limit(3)->get();

    if ($members->count() < 2) {
        throw new Exception("Need at least 2 users in the database for testing");
    }

    echo "   ✓ Found {$members->count()} members for testing\n";
    foreach ($members as $member) {
        echo "     - {$member->name} (ID: {$member->id})\n";
    }
    echo "\n";

    // Step 3: Create test meeting with social fund data
    echo "3. Creating test meeting with social fund contributions...\n";
    
    DB::beginTransaction();
    
    $meetingData = [
        'group_id' => $testGroupId,
        'cycle_id' => $cycleId,
        'meeting_date' => now()->format('Y-m-d'),
        'meeting_number' => rand(100, 999),
        'location' => 'Test Location - Social Fund',
        'notes' => 'Test meeting for social fund processing',
        'created_by_id' => $members->first()->id,
        'status' => 'pending',
    ];

    // Prepare social fund contributions data
    $socialFundContributions = [];
    foreach ($members as $index => $member) {
        $socialFundContributions[] = [
            'member_id' => $member->id,
            'member_name' => $member->name,
            'contributed' => $index < 2, // First 2 members contribute
            'amount' => $index < 2 ? ($index + 1) * 5000 : 0, // 5000, 10000, 0
            'notes' => $index < 2 ? "Social fund contribution #{$index}" : null,
        ];
    }

    $meetingData['social_fund_contributions_data'] = $socialFundContributions;

    // Prepare attendance data (required)
    $attendanceData = [];
    foreach ($members as $member) {
        $attendanceData[] = [
            'memberId' => $member->id,
            'memberName' => $member->name,
            'status' => 'present',
        ];
    }
    $meetingData['attendance_data'] = $attendanceData;

    $meeting = VslaMeeting::create($meetingData);
    
    echo "   ✓ Meeting created (ID: {$meeting->id})\n";
    echo "   ✓ Meeting Number: {$meeting->meeting_number}\n";
    echo "   ✓ Social Fund Contributions Data:\n";
    foreach ($socialFundContributions as $contrib) {
        $status = $contrib['contributed'] ? '✓ Contributed' : '✗ Not contributed';
        echo "     - {$contrib['member_name']}: {$status} - UGX " . number_format($contrib['amount']) . "\n";
    }
    echo "\n";

    // Step 4: Process the meeting
    echo "4. Processing meeting with MeetingProcessingService...\n";
    $service = new MeetingProcessingService();
    $result = $service->processMeeting($meeting);

    if ($result['success']) {
        echo "   ✓ Meeting processed successfully\n";
    } else {
        echo "   ✗ Meeting processing failed\n";
        if (!empty($result['errors'])) {
            echo "   Errors:\n";
            foreach ($result['errors'] as $error) {
                echo "     - {$error['type']}: {$error['message']}\n";
            }
        }
    }

    if (!empty($result['warnings'])) {
        echo "   Warnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "     - {$warning['type']}: {$warning['message']}\n";
        }
    }
    echo "\n";

    // Step 5: Verify social fund transactions were created
    echo "5. Verifying social fund transactions...\n";
    $transactions = SocialFundTransaction::where('meeting_id', $meeting->id)->get();

    echo "   ✓ Found {$transactions->count()} social fund transactions\n";
    
    $expectedCount = count(array_filter($socialFundContributions, fn($c) => $c['contributed']));
    if ($transactions->count() !== $expectedCount) {
        throw new Exception("Expected {$expectedCount} transactions, found {$transactions->count()}");
    }

    $totalAmount = 0;
    foreach ($transactions as $transaction) {
        echo "     - Member: {$transaction->member->name}\n";
        echo "       Amount: UGX " . number_format($transaction->amount) . "\n";
        echo "       Type: {$transaction->transaction_type}\n";
        echo "       Date: {$transaction->transaction_date}\n";
        echo "       Description: {$transaction->description}\n";
        if ($transaction->reason) {
            echo "       Reason: {$transaction->reason}\n";
        }
        echo "\n";
        $totalAmount += $transaction->amount;
    }

    echo "   ✓ Total Contributions: UGX " . number_format($totalAmount) . "\n\n";

    // Step 6: Verify data structure
    echo "6. Verifying meeting data structure...\n";
    $meeting->refresh();
    
    if (!$meeting->social_fund_contributions_data) {
        throw new Exception("social_fund_contributions_data is null");
    }

    if (!is_array($meeting->social_fund_contributions_data)) {
        throw new Exception("social_fund_contributions_data is not an array");
    }

    echo "   ✓ social_fund_contributions_data is properly stored\n";
    echo "   ✓ Data type: " . gettype($meeting->social_fund_contributions_data) . "\n";
    echo "   ✓ Number of contributions: " . count($meeting->social_fund_contributions_data) . "\n\n";

    // Step 7: Verify database schema
    echo "7. Verifying database schema...\n";
    $columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'vsla_meetings' AND column_name = 'social_fund_contributions_data'");
    
    if (empty($columns)) {
        throw new Exception("social_fund_contributions_data column not found in vsla_meetings table");
    }

    echo "   ✓ Column exists in vsla_meetings table\n";
    echo "   ✓ Data type: {$columns[0]->data_type}\n\n";

    $sfColumns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'social_fund_transactions' ORDER BY ordinal_position");
    echo "   ✓ social_fund_transactions table columns:\n";
    foreach ($sfColumns as $col) {
        echo "     - {$col->column_name}\n";
    }
    echo "\n";

    // Commit transaction
    DB::commit();

    echo "========================================\n";
    echo "✓ ALL TESTS PASSED SUCCESSFULLY\n";
    echo "========================================\n\n";

    echo "Summary:\n";
    echo "- Meeting ID: {$meeting->id}\n";
    echo "- Meeting Number: {$meeting->meeting_number}\n";
    echo "- Total Social Fund Contributions: UGX " . number_format($totalAmount) . "\n";
    echo "- Transactions Created: {$transactions->count()}\n";
    echo "- Status: {$meeting->status}\n\n";

} catch (Exception $e) {
    DB::rollback();
    echo "\n✗ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "Test completed successfully!\n\n";
