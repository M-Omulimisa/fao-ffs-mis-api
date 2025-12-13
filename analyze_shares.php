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

echo "=== ANALYZING LATEST MEETING'S SHARE DATA ===\n\n";

// Get latest meeting
$result = $conn->query("SELECT * FROM vsla_meetings ORDER BY id DESC LIMIT 1");

if ($result->num_rows > 0) {
    $meeting = $result->fetch_assoc();
    
    echo "Meeting ID: {$meeting['id']}\n";
    echo "Meeting Number: {$meeting['meeting_number']}\n";
    echo "Cycle ID: {$meeting['cycle_id']}\n";
    echo "Total Shares Sold: {$meeting['total_shares_sold']}\n";
    echo "Total Share Value: {$meeting['total_share_value']}\n";
    echo "Processing Status: {$meeting['processing_status']}\n\n";
    
    // Decode share purchases data
    $shares = json_decode($meeting['share_purchases_data'], true);
    
    echo "=== SHARE PURCHASES DATA ===\n";
    if (empty($shares)) {
        echo "⚠️ EMPTY ARRAY - No share purchase data submitted from mobile app!\n\n";
        echo "This means the mobile app sent:\n";
        echo "  share_purchases_data: []\n\n";
        echo "Expected format:\n";
        echo "  share_purchases_data: [\n";
        echo "    {\n";
        echo "      memberId: 214,\n";
        echo "      memberName: 'John Doe',\n";
        echo "      numberOfShares: 5,\n";
        echo "      sharePriceAtPurchase: 5000,\n";
        echo "      totalAmountPaid: 25000\n";
        echo "    }\n";
        echo "  ]\n";
    } else {
        echo "Share purchases data received:\n";
        print_r($shares);
    }
    
    echo "\n=== CHECKING PROJECT_SHARES TABLE ===\n";
    $sharesResult = $conn->query("SELECT * FROM project_shares WHERE meeting_id = {$meeting['id']}");
    echo "Shares created for this meeting: {$sharesResult->num_rows}\n\n";
    
    if ($sharesResult->num_rows > 0) {
        echo "Share records:\n";
        while ($row = $sharesResult->fetch_assoc()) {
            echo "  - Investor: {$row['investor_id']}, Shares: {$row['number_of_shares']}, Amount: {$row['total_amount_paid']}\n";
        }
    }
    
    echo "\n=== ROOT CAUSE ANALYSIS ===\n";
    if (empty($shares) && $meeting['total_shares_sold'] > 0) {
        echo "❌ ISSUE IDENTIFIED:\n";
        echo "   Mobile app reported {$meeting['total_shares_sold']} shares sold\n";
        echo "   But share_purchases_data array is EMPTY\n";
        echo "   Mobile app is NOT sending individual share purchase records!\n\n";
        
        echo "FIX NEEDED:\n";
        echo "   1. Check mobile app code (VslaMeetingSyncService.dart)\n";
        echo "   2. Ensure _prepareSharePurchasesData() is working correctly\n";
        echo "   3. Verify meeting.sharePurchases has data before submission\n";
    } elseif (!empty($shares) && $sharesResult->num_rows == 0) {
        echo "❌ ISSUE IDENTIFIED:\n";
        echo "   Share data WAS sent from mobile app\n";
        echo "   But processSharePurchases() did NOT create records\n";
        echo "   Server-side processing failed!\n\n";
        
        echo "FIX NEEDED:\n";
        echo "   1. Check MeetingProcessingService::processSharePurchases()\n";
        echo "   2. Check for errors in meeting's error_log\n";
        echo "   3. Verify data format matches expected structure\n";
    } elseif (!empty($shares) && $sharesResult->num_rows > 0) {
        echo "✅ Shares are working correctly!\n";
    }
    
} else {
    echo "No meetings found in database\n";
}

$conn->close();
