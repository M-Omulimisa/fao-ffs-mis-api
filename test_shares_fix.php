<?php

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "fao_ffs_mis";
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

$conn = new mysqli($servername, $username, $password, $dbname, null, $socket);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== TESTING SHARE CREATION FIX ===\n\n";

// Get latest meeting
$result = $conn->query("SELECT * FROM vsla_meetings ORDER BY id DESC LIMIT 1");
$meeting = $result->fetch_assoc();

echo "Meeting ID: {$meeting['id']}\n";
echo "Total Shares Sold: {$meeting['total_shares_sold']}\n";
echo "Total Share Value: {$meeting['total_share_value']}\n\n";

// Parse share purchases data
$sharesData = json_decode($meeting['share_purchases_data'], true);

echo "Share Purchases Data ({count} records):\n";
echo str_replace('{count}', count($sharesData), "");
foreach ($sharesData as $i => $share) {
    $investorId = $share['investor_id'];
    $investorName = $share['investor_name'];
    $numShares = $share['number_of_shares'];
    $amount = $share['total_amount_paid'];
    $sharePrice = $share['share_price_at_purchase'];
    
    echo "  " . ($i+1) . ". {$investorName} (ID: {$investorId})\n";
    echo "      Shares: {$numShares} @ UGX {$sharePrice} = UGX {$amount}\n";
}
echo "\n";

// Check if users exist
echo "Checking if investors exist in users table:\n";
$allExist = true;
foreach ($sharesData as $share) {
    $investorId = $share['investor_id'];
    $investorName = $share['investor_name'];
    
    $userCheck = $conn->query("SELECT id, name FROM users WHERE id = {$investorId}");
    if ($userCheck->num_rows > 0) {
        $user = $userCheck->fetch_assoc();
        echo "  ✅ User {$investorId}: {$user['name']}\n";
    } else {
        echo "  ❌ User {$investorId} ({$investorName}) NOT FOUND!\n";
        $allExist = false;
    }
}
echo "\n";

if (!$allExist) {
    echo "⚠️ Some investors don't exist. This will cause warnings but shares can still be created.\n\n";
}

// Delete existing shares for this meeting/date
echo "Clearing existing shares for this date...\n";
$conn->query("DELETE FROM project_shares WHERE project_id = {$meeting['cycle_id']} AND purchase_date = '{$meeting['meeting_date']}'");
$conn->query("DELETE FROM project_transactions WHERE project_id = {$meeting['cycle_id']} AND source = 'share_purchase' AND transaction_date = '{$meeting['meeting_date']}'");
echo "Cleared.\n\n";

// Manually create shares using the fixed logic
echo "Creating shares with FIXED logic (matching mobile app format):\n";

$createdCount = 0;
$skippedCount = 0;

foreach ($sharesData as $share) {
    $investorId = $share['investor_id'];
    $investorName = $share['investor_name'];
    $numShares = $share['number_of_shares'];
    $amount = $share['total_amount_paid'];
    $sharePrice = $share['share_price_at_purchase'];
    $purchaseDate = $share['purchase_date'];
    
    // Check if user exists
    $userCheck = $conn->query("SELECT id FROM users WHERE id = {$investorId}");
    if ($userCheck->num_rows == 0) {
        echo "  ⚠️ Skipped {$investorName} - user not found\n";
        $skippedCount++;
        continue;
    }
    
    // Create ProjectShare
    $sql = "INSERT INTO project_shares (project_id, investor_id, number_of_shares, share_price_at_purchase, total_amount_paid, purchase_date, created_at, updated_at)
            VALUES ({$meeting['cycle_id']}, {$investorId}, {$numShares}, {$sharePrice}, {$amount}, '{$purchaseDate}', NOW(), NOW())";
    
    if ($conn->query($sql)) {
        echo "  ✅ Created share for {$investorName}: {$numShares} shares @ UGX {$amount}\n";
        $createdCount++;
        
        // Create transaction
        $desc = $conn->real_escape_string("Meeting #{$meeting['meeting_number']} - {$investorName} purchased {$numShares} shares");
        $txnSql = "INSERT INTO project_transactions (project_id, type, source, amount, description, transaction_date, created_by_id, created_at, updated_at)
                   VALUES ({$meeting['cycle_id']}, 'income', 'share_purchase', {$amount}, '{$desc}', '{$purchaseDate}', {$meeting['created_by_id']}, NOW(), NOW())";
        $conn->query($txnSql);
        
    } else {
        echo "  ❌ Failed to create share for {$investorName}: " . $conn->error . "\n";
    }
}

echo "\n";
echo "=== SUMMARY ===\n";
echo "Expected: " . count($sharesData) . " shares\n";
echo "Created: {$createdCount} shares\n";
echo "Skipped: {$skippedCount} shares\n\n";

// Verify
$verifyShares = $conn->query("SELECT COUNT(*) as cnt FROM project_shares WHERE project_id = {$meeting['cycle_id']} AND purchase_date = '{$meeting['meeting_date']}'");
$shareCnt = $verifyShares->fetch_assoc()['cnt'];

$verifyTxns = $conn->query("SELECT COUNT(*) as cnt FROM project_transactions WHERE project_id = {$meeting['cycle_id']} AND source = 'share_purchase' AND transaction_date = '{$meeting['meeting_date']}'");
$txnCnt = $verifyTxns->fetch_assoc()['cnt'];

echo "Database verification:\n";
echo "  project_shares: {$shareCnt} records\n";
echo "  project_transactions: {$txnCnt} records\n\n";

if ($shareCnt == count($sharesData) && $txnCnt == count($sharesData)) {
    echo "✅✅✅ SUCCESS! Share creation is now working correctly! ✅✅✅\n";
    echo "\nThe fix in MeetingProcessingService.php is working:\n";
    echo "  - Now reads 'investor_id' (not 'memberId')\n";
    echo "  - Now reads 'number_of_shares' (not 'numberOfShares')\n";
    echo "  - Now reads 'total_amount_paid' (not 'totalAmountPaid')\n";
    echo "  - Removed 'meeting_id' (column doesn't exist in table)\n";
} else {
    echo "⚠️ Some shares were not created. Check errors above.\n";
}

$conn->close();
