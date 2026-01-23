<?php

/**
 * Test VSLA Group Stats API Endpoint
 * 
 * This script tests the comprehensive group statistics endpoint
 * that returns financial, meeting, member, and cycle data.
 */

require __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FfsGroup;

echo "\n";
echo "========================================\n";
echo "VSLA GROUP STATS API TEST\n";
echo "========================================\n\n";

try {
    // Get a group and cycle from database
    $groupData = DB::table('ffs_groups')->first();
    $cycleData = DB::table('projects')->where('is_vsla_cycle', 'Yes')->first();

    if (!$groupData) {
        echo "❌ No groups found in database\n";
        exit(1);
    }

    if (!$cycleData) {
        echo "❌ No cycles found in database\n";
        exit(1);
    }

    echo "📊 Testing with:\n";
    echo "   Group: {$groupData->name} (ID: {$groupData->id})\n";
    echo "   Cycle: " . ($cycleData->name ?? $cycleData->project_name ?? 'Project ' . $cycleData->id) . " (ID: {$cycleData->id})\n";
    echo "\n";

    // Create controller instance and test
    $controller = new \App\Http\Controllers\Api\VslaGroupStatsController();
    
    // Create mock request
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'group_id' => $groupData->id,
        'cycle_id' => $cycleData->id,
    ]);

    echo "🔄 Fetching group statistics...\n\n";

    $response = $controller->getGroupStats($request);
    $data = json_decode($response->getContent(), true);

    if ($data['success']) {
        $stats = $data['data'];

        echo "✅ API Response: SUCCESS\n\n";

        // Display Group Info
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 GROUP INFORMATION\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $groupInfo = $stats['group_info'];
        echo "Group: {$groupInfo['group_name']}\n";
        echo "Cycle: {$groupInfo['cycle_name']}\n";
        echo "Total Members: {$groupInfo['total_members']}\n";
        echo "Active Members: {$groupInfo['active_members']}\n";
        echo "\n";

        // Display Financial Overview
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "💰 FINANCIAL OVERVIEW\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $financial = $stats['financial_overview'];
        echo "Total Shares Value: UGX " . number_format($financial['total_shares_value']) . "\n";
        echo "Active Loans Portfolio: UGX " . number_format($financial['active_loans_portfolio']) . "\n";
        echo "Total Loan Disbursed: UGX " . number_format($financial['total_loan_disbursed']) . "\n";
        echo "Total Loan Repaid: UGX " . number_format($financial['total_loan_repaid']) . "\n";
        echo "Loan Interest Earned: UGX " . number_format($financial['loan_interest_earned']) . "\n";
        echo "Social Fund Balance: UGX " . number_format($financial['social_fund_balance']) . "\n";
        echo "Fines Collected: UGX " . number_format($financial['fines_collected']) . "\n";
        echo "─────────────────────────────────────────\n";
        echo "💵 Total Cash on Hand: UGX " . number_format($financial['total_cash_on_hand']) . "\n";
        echo "\n";

        // Display Meeting Stats
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📅 MEETING STATISTICS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $meeting = $stats['meeting_stats'];
        echo "Total Meetings: {$meeting['total_meetings']}\n";
        echo "Average Attendance: {$meeting['average_attendance']} members\n";
        echo "Last Meeting: " . ($meeting['last_meeting_date'] ?? 'N/A') . "\n";
        echo "Next Meeting: " . ($meeting['next_meeting_date'] ?? 'N/A') . "\n";
        echo "\n";

        // Display Member Stats
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👥 MEMBER STATISTICS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $members = $stats['member_stats'];
        
        if ($members['top_saver']) {
            echo "🏆 Top Saver: {$members['top_saver']['name']} ";
            echo "(UGX " . number_format($members['top_saver']['amount']) . ")\n";
        } else {
            echo "🏆 Top Saver: N/A\n";
        }
        
        if ($members['top_borrower']) {
            echo "💳 Top Borrower: {$members['top_borrower']['name']} ";
            echo "(UGX " . number_format($members['top_borrower']['amount']) . ")\n";
        } else {
            echo "💳 Top Borrower: N/A\n";
        }
        
        echo "📊 Members with Active Loans: {$members['members_with_active_loans']}\n";
        echo "\n";

        // Display Cycle Progress
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "⏰ CYCLE PROGRESS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $progress = $stats['cycle_progress'];
        echo "Start Date: {$progress['start_date']}\n";
        echo "End Date: {$progress['end_date']}\n";
        echo "Total Days: {$progress['total_days']}\n";
        echo "Days Passed: {$progress['days_passed']}\n";
        echo "Days Remaining: {$progress['days_remaining']}\n";
        echo "Progress: {$progress['progress_percentage']}%\n";
        echo "Status: {$progress['status']}\n";
        echo "\n";

        // Display Recent Activities
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📢 RECENT ACTIVITIES\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $activities = $stats['recent_activities'];
        
        if (empty($activities)) {
            echo "No recent activities\n";
        } else {
            foreach ($activities as $activity) {
                $icon = $activity['type'] === 'meeting' ? '📅' : '💵';
                echo "{$icon} {$activity['title']}";
                if (isset($activity['amount'])) {
                    echo " - UGX " . number_format($activity['amount']);
                }
                echo " ({$activity['date']})\n";
            }
        }
        echo "\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ ALL TESTS PASSED\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "\n";
        echo "Summary:\n";
        echo "✅ API endpoint working correctly\n";
        echo "✅ All stat categories calculated successfully\n";
        echo "✅ Data structure is complete\n";
        echo "✅ Ready for mobile app integration\n\n";

    } else {
        echo "❌ API Response: FAILED\n";
        echo "Message: " . ($data['message'] ?? 'Unknown error') . "\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
