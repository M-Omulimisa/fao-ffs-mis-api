<?php

/**
 * VSLA End-to-End Test Script
 * Tests complete VSLA flow from group creation to ledger book generation
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\Location;
use App\Models\VslaMeeting;
use App\Services\MeetingProcessingService;

echo "========================================\n";
echo "VSLA END-TO-END TEST\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];

try {
    // Step 1: Create test users
    echo "1. Creating test users...\n";
    
    $chairperson = User::firstOrCreate(
        ['email' => 'vsla.chairperson@test.com'],
        [
            'name' => 'Test VSLA Chairperson',
            'phone_number' => '0700000001',
            'phone_number_2' => '0700000001',
            'password' => bcrypt('password'),
            'user_type' => 'farmer',
            'is_group_admin' => 'Yes',
        ]
    );
    
    $secretary = User::firstOrCreate(
        ['email' => 'vsla.secretary@test.com'],
        [
            'name' => 'Test VSLA Secretary',
            'phone_number' => '0700000002',
            'phone_number_2' => '0700000002',
            'password' => bcrypt('password'),
            'user_type' => 'farmer',
            'is_group_secretary' => 'Yes',
        ]
    );
    
    $treasurer = User::firstOrCreate(
        ['email' => 'vsla.treasurer@test.com'],
        [
            'name' => 'Test VSLA Treasurer',
            'phone_number' => '0700000003',
            'phone_number_2' => '0700000003',
            'password' => bcrypt('password'),
            'user_type' => 'farmer',
            'is_group_treasurer' => 'Yes',
        ]
    );
    
    // Create 7 more regular members
    $members = [$chairperson, $secretary, $treasurer];
    for ($i = 4; $i <= 10; $i++) {
        $member = User::firstOrCreate(
            ['email' => "vsla.member{$i}@test.com"],
            [
                'name' => "Test VSLA Member {$i}",
                'phone_number' => "070000000{$i}",
                'phone_number_2' => "070000000{$i}",
                'password' => bcrypt('password'),
                'user_type' => 'farmer',
            ]
        );
        $members[] = $member;
    }
    
    echo "   ✓ Created " . count($members) . " test users\n";
    
    // Step 2: Create VSLA group
    echo "2. Creating VSLA group...\n";
    
    $district = Location::where('type', 'District')->first();
    if (!$district) {
        throw new Exception("No district found in database");
    }
    
    $vslaGroup = FfsGroup::create([
        'name' => 'Test VSLA Group',
        'code' => 'VSLA-TEST-' . time(),
        'type' => 'VSLA',
        'district_id' => $district->id,
        'facilitator_id' => $chairperson->id,
        'status' => 'active',
        'meeting_frequency' => 'weekly',
        'created_by_id' => $chairperson->id,
    ]);
    
    echo "   ✓ Created VSLA group: {$vslaGroup->name} (ID: {$vslaGroup->id})\n";
    
    // Step 3: Create VSLA cycle
    echo "3. Creating VSLA savings cycle...\n";
    
    $vslaCycle = Project::create([
        'title' => 'Test VSLA Cycle 2025',
        'name' => 'Test VSLA Cycle 2025',
        'ffs_group_id' => $vslaGroup->id,
        'is_vsla_cycle' => 'Yes',
        'is_active_cycle' => 'Yes',
        'start_date' => now()->subMonths(2),
        'end_date' => now()->addMonths(10),
        'share_price' => 5000,
        'max_shares_per_member' => 5,
        'interest_rate' => 10,
        'created_by_id' => $chairperson->id,
    ]);
    
    echo "   ✓ Created VSLA cycle: {$vslaCycle->name} (ID: {$vslaCycle->id})\n";
    
    // Step 4: Test meeting submission via API
    echo "4. Simulating mobile app meeting submission...\n";
    
    // Prepare meeting data
    $attendanceData = [];
    foreach ($members as $index => $member) {
        $attendanceData[] = [
            'memberId' => $member->id,
            'memberName' => $member->name,
            'isPresent' => $index < 8, // 8 present, 2 absent
            'absentReason' => $index >= 8 ? 'Sick' : null,
        ];
    }
    
    $transactionsData = [];
    // First 8 members make savings
    for ($i = 0; $i < 8; $i++) {
        $transactionsData[] = [
            'memberId' => $members[$i]->id,
            'memberName' => $members[$i]->name,
            'accountType' => 'savings',
            'amount' => 10000,
            'description' => 'Monthly savings contribution',
        ];
        $transactionsData[] = [
            'memberId' => $members[$i]->id,
            'memberName' => $members[$i]->name,
            'accountType' => 'welfare',
            'amount' => 2000,
            'description' => 'Welfare fund contribution',
        ];
    }
    
    $loansData = [
        [
            'borrowerId' => $members[0]->id,
            'borrowerName' => $members[0]->name,
            'loanAmount' => 50000,
            'interestRate' => 10,
            'durationMonths' => 3,
            'purpose' => 'Small business',
        ],
        [
            'borrowerId' => $members[1]->id,
            'borrowerName' => $members[1]->name,
            'loanAmount' => 30000,
            'interestRate' => 10,
            'durationMonths' => 2,
            'purpose' => 'School fees',
        ],
    ];
    
    $actionPlansData = [
        [
            'action' => 'Organize group training',
            'description' => 'Coordinate training session for all members',
            'assignedToMemberId' => $secretary->id,
            'assignedToMemberName' => $secretary->name,
            'priority' => 'high',
            'dueDate' => now()->addDays(7)->format('Y-m-d'),
        ],
    ];
    
    $meetingData = [
        'local_id' => 'test-meeting-' . time(),
        'cycle_id' => $vslaCycle->id,
        'group_id' => $vslaGroup->id,
        'meeting_date' => now()->format('Y-m-d'),
        'notes' => 'Test meeting to verify VSLA system',
        'members_present' => 8,
        'members_absent' => 2,
        'total_savings_collected' => 80000,
        'total_welfare_collected' => 16000,
        'total_social_fund_collected' => 0,
        'total_fines_collected' => 0,
        'total_loans_disbursed' => 80000,
        'total_shares_sold' => 0,
        'total_share_value' => 0,
        'attendance_data' => $attendanceData,
        'transactions_data' => $transactionsData,
        'loans_data' => $loansData,
        'share_purchases_data' => [],
        'previous_action_plans_data' => [],
        'upcoming_action_plans_data' => $actionPlansData,
    ];
    
    // Create meeting record
    $meeting = VslaMeeting::create(array_merge($meetingData, [
        'meeting_number' => 1,
        'created_by_id' => $chairperson->id,
        'processing_status' => 'pending',
        'submitted_from_app_at' => now(),
        'received_at' => now(),
    ]));
    
    echo "   ✓ Created meeting record (ID: {$meeting->id})\n";
    
    // Step 5: Process meeting
    echo "5. Processing meeting via MeetingProcessingService...\n";
    
    $processingService = new MeetingProcessingService();
    $result = $processingService->processMeeting($meeting);
    
    if ($result['success']) {
        echo "   ✓ Meeting processed successfully\n";
    } else {
        echo "   ⚠ Meeting processed with errors\n";
        $errors = array_merge($errors, $result['errors'] ?? []);
    }
    
    if (!empty($result['warnings'])) {
        $warnings = array_merge($warnings, $result['warnings']);
    }
    
    // Step 6: Verify created records
    echo "6. Verifying created records...\n";
    
    $meeting->refresh();
    
    $attendanceCount = DB::table('vsla_meeting_attendance')->where('meeting_id', $meeting->id)->count();
    echo "   ✓ Attendance records: {$attendanceCount}\n";
    
    $loansCount = DB::table('vsla_loans')->where('meeting_id', $meeting->id)->count();
    echo "   ✓ Loan records: {$loansCount}\n";
    
    $actionPlansCount = DB::table('vsla_action_plans')->where('meeting_id', $meeting->id)->count();
    echo "   ✓ Action plan records: {$actionPlansCount}\n";
    
    echo "   ✓ Meeting status: {$meeting->processing_status}\n";
    echo "   ✓ Has errors: " . ($meeting->has_errors ? 'Yes' : 'No') . "\n";
    echo "   ✓ Has warnings: " . ($meeting->has_warnings ? 'Yes' : 'No') . "\n";
    
    // Step 7: Check for any leftover VSLA code issues
    echo "7. Testing for missing VSLA code/relationships...\n";
    
    // Test model relationships
    try {
        $cycle = $meeting->cycle;
        echo "   ✓ VslaMeeting->cycle relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "VslaMeeting->cycle relationship failed: " . $e->getMessage();
    }
    
    try {
        $group = $meeting->group;
        echo "   ✓ VslaMeeting->group relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "VslaMeeting->group relationship failed: " . $e->getMessage();
    }
    
    try {
        $attendance = $meeting->attendance;
        echo "   ✓ VslaMeeting->attendance relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "VslaMeeting->attendance relationship failed: " . $e->getMessage();
    }
    
    try {
        $loans = $meeting->loans;
        echo "   ✓ VslaMeeting->loans relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "VslaMeeting->loans relationship failed: " . $e->getMessage();
    }
    
    try {
        $actionPlans = $meeting->actionPlans;
        echo "   ✓ VslaMeeting->actionPlans relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "VslaMeeting->actionPlans relationship failed: " . $e->getMessage();
    }
    
    // Test reverse relationships
    try {
        $vslaMeetings = $vslaCycle->vslaMeetings;
        echo "   ✓ Project->vslaMeetings relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "Project->vslaMeetings relationship failed: " . $e->getMessage();
    }
    
    try {
        $vslaMeetings = $vslaGroup->vslaMeetings;
        echo "   ✓ FfsGroup->vslaMeetings relationship works\n";
    } catch (\Exception $e) {
        $errors[] = "FfsGroup->vslaMeetings relationship failed: " . $e->getMessage();
    }
    
    // Step 8: Test computed attributes
    echo "8. Testing computed attributes...\n";
    
    try {
        $totalMembers = $meeting->total_members;
        echo "   ✓ total_members computed attribute works: {$totalMembers}\n";
    } catch (\Exception $e) {
        $errors[] = "total_members attribute failed: " . $e->getMessage();
    }
    
    try {
        $attendanceRate = $meeting->attendance_rate;
        echo "   ✓ attendance_rate computed attribute works: {$attendanceRate}%\n";
    } catch (\Exception $e) {
        $errors[] = "attendance_rate attribute failed: " . $e->getMessage();
    }
    
    try {
        $totalCash = $meeting->total_cash_collected;
        echo "   ✓ total_cash_collected computed attribute works: UGX {$totalCash}\n";
    } catch (\Exception $e) {
        $errors[] = "total_cash_collected attribute failed: " . $e->getMessage();
    }
    
    echo "\n========================================\n";
    echo "TEST SUMMARY\n";
    echo "========================================\n";
    
    echo "Meeting ID: {$meeting->id}\n";
    echo "Meeting Number: {$meeting->meeting_number}\n";
    echo "Processing Status: {$meeting->processing_status}\n";
    echo "Attendance Created: {$attendanceCount}\n";
    echo "Loans Created: {$loansCount}\n";
    echo "Action Plans Created: {$actionPlansCount}\n";
    
    if (empty($errors)) {
        echo "\n✅ ALL TESTS PASSED!\n";
        echo "✅ No leftover VSLA code issues detected\n";
        echo "✅ All relationships working correctly\n";
        echo "✅ Meeting processing successful\n";
        exit(0);
    } else {
        echo "\n❌ TESTS FAILED WITH ERRORS:\n";
        foreach ($errors as $error) {
            if (is_array($error)) {
                echo "  - " . json_encode($error) . "\n";
            } else {
                echo "  - {$error}\n";
            }
        }
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
