<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\VslaMeeting;
use App\Services\MeetingProcessingService;
use Illuminate\Support\Facades\DB;

echo "=== REPROCESSING MEETING TO TEST SHARES FIX ===\n\n";

// Get the latest meeting
$meeting = VslaMeeting::orderBy('id', 'desc')->first();

if (!$meeting) {
    echo "❌ No meetings found\n";
    exit(1);
}

echo "Meeting Details:\n";
echo "  ID: {$meeting->id}\n";
echo "  Meeting Number: {$meeting->meeting_number}\n";
echo "  Cycle ID: {$meeting->cycle_id}\n";
echo "  Total Shares Sold: {$meeting->total_shares_sold}\n";
echo "  Total Share Value: UGX {$meeting->total_share_value}\n";
echo "  Current Status: {$meeting->processing_status}\n\n";

// Show share purchases data
$sharesData = $meeting->share_purchases_data ?? [];
echo "Share Purchases Data from Meeting:\n";
if (empty($sharesData)) {
    echo "  ⚠️ EMPTY - No shares to process\n\n";
    exit(0);
}

foreach ($sharesData as $i => $share) {
    $investorId = $share['investor_id'] ?? $share['memberId'] ?? 'N/A';
    $investorName = $share['investor_name'] ?? $share['memberName'] ?? 'Unknown';
    $numShares = $share['number_of_shares'] ?? $share['numberOfShares'] ?? 0;
    $amount = $share['total_amount_paid'] ?? $share['totalAmountPaid'] ?? 0;
    
    echo "  " . ($i+1) . ". {$investorName} (ID: {$investorId}) - {$numShares} shares @ UGX {$amount}\n";
}
echo "\n";

// Delete existing shares for this meeting (to test reprocessing)
echo "1. Clearing existing share records...\n";
$deletedCount = DB::table('project_shares')
    ->where('project_id', $meeting->cycle_id)
    ->whereDate('purchase_date', $meeting->meeting_date)
    ->delete();
echo "   Deleted {$deletedCount} existing share records\n\n";

// Reprocess shares
echo "2. Reprocessing shares...\n";
$processor = new MeetingProcessingService();

DB::beginTransaction();
try {
    // Call processSharePurchases using reflection (it's protected)
    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('processSharePurchases');
    $method->setAccessible(true);
    
    $result = $method->invoke($processor, $meeting);
    
    if ($result['success']) {
        DB::commit();
        echo "   ✅ Shares processed successfully!\n\n";
        
        if (!empty($result['warnings'])) {
            echo "   Warnings:\n";
            foreach ($result['warnings'] as $warning) {
                echo "     - {$warning['message']}\n";
            }
            echo "\n";
        }
    } else {
        DB::rollBack();
        echo "   ❌ Failed to process shares\n\n";
        
        if (!empty($result['errors'])) {
            echo "   Errors:\n";
            foreach ($result['errors'] as $error) {
                echo "     - {$error['message']}\n";
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    DB::rollBack();
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

// Verify created shares
echo "3. Verification:\n";
$createdShares = DB::table('project_shares')
    ->where('project_id', $meeting->cycle_id)
    ->whereDate('purchase_date', $meeting->meeting_date)
    ->get();

echo "   Shares created: " . count($createdShares) . "\n";

if (count($createdShares) > 0) {
    echo "\n   Share Records:\n";
    foreach ($createdShares as $share) {
        echo "     - Investor ID: {$share->investor_id}, Shares: {$share->number_of_shares}, Amount: UGX {$share->total_amount_paid}\n";
    }
    echo "\n";
}

$createdTransactions = DB::table('project_transactions')
    ->where('project_id', $meeting->cycle_id)
    ->where('source', 'share_purchase')
    ->whereDate('transaction_date', $meeting->meeting_date)
    ->get();

echo "   Share transactions created: " . count($createdTransactions) . "\n";

if (count($createdTransactions) > 0) {
    echo "\n   Transaction Records:\n";
    foreach ($createdTransactions as $txn) {
        echo "     - Type: {$txn->type}, Amount: UGX {$txn->amount}, Description: {$txn->description}\n";
    }
}

echo "\n";

if (count($createdShares) == count($sharesData)) {
    echo "✅ SUCCESS! All " . count($sharesData) . " share purchases were processed correctly!\n";
} else {
    echo "⚠️ MISMATCH: Expected " . count($sharesData) . " shares but created " . count($createdShares) . "\n";
}
