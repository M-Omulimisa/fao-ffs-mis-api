<?php
// Fix cycle 1 and group 5 for mobile app testing

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "fao_ffs_mis";
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

$conn = new mysqli($servername, $username, $password, $dbname, null, $socket);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== FIXING CYCLE 1 FOR MOBILE APP ===\n\n";

// Check current status
$result = $conn->query("SELECT id, title, group_id, is_vsla_cycle, is_active_cycle FROM projects WHERE id = 1");
$cycle = $result->fetch_assoc();

echo "Current Cycle 1 Status:\n";
echo "  Title: {$cycle['title']}\n";
echo "  Group ID: {$cycle['group_id']}\n";
echo "  Is VSLA: {$cycle['is_vsla_cycle']}\n";
echo "  Is Active: {$cycle['is_active_cycle']}\n\n";

// Update cycle 1 to be VSLA and active
$sql = "UPDATE projects SET 
        is_vsla_cycle = 'Yes',
        is_active_cycle = 'Yes',
        share_value = 5000,
        meeting_frequency = 'Weekly',
        loan_interest_rate = 10,
        monthly_loan_interest_rate = 10,
        weekly_loan_interest_rate = 2.5,
        minimum_loan_amount = 25000,
        maximum_loan_multiple = 5,
        cycle_name = 'Cycle 1 - Dec 2025'
        WHERE id = 1";

$conn->query($sql);
echo "✓ Updated cycle 1 to active VSLA cycle\n\n";

// Check if group 5 exists
$result = $conn->query("SELECT id, name, type FROM ffs_groups WHERE id = 5");
if ($result->num_rows > 0) {
    $group = $result->fetch_assoc();
    echo "Group 5 exists: {$group['name']} (Type: {$group['type']})\n";
    
    // Update to VSLA type if not already
    if ($group['type'] !== 'VSLA') {
        $conn->query("UPDATE ffs_groups SET type = 'VSLA' WHERE id = 5");
        echo "✓ Updated group 5 to VSLA type\n";
    } else {
        echo "✓ Group 5 is already VSLA type\n";
    }
} else {
    echo "Group 5 doesn't exist. Creating it...\n";
    
    // Get a user for created_by
    $userResult = $conn->query("SELECT id FROM users LIMIT 1");
    $user = $userResult->fetch_assoc();
    $userId = $user['id'];
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO ffs_groups (id, name, type, code, status, total_members, created_by_id, created_at, updated_at)
            VALUES (5, 'Test VSLA Group 5', 'VSLA', 'VSLA-TEST05', 'Active', 25, $userId, '$now', '$now')";
    
    $conn->query($sql);
    echo "✓ Created group 5 as VSLA group\n";
}

echo "\n=== VERIFICATION ===\n\n";

// Verify changes
$result = $conn->query("SELECT id, title, is_vsla_cycle, is_active_cycle, share_value FROM projects WHERE id = 1");
$cycle = $result->fetch_assoc();

echo "Cycle 1 Final Status:\n";
echo "  ID: {$cycle['id']}\n";
echo "  Title: {$cycle['title']}\n";
echo "  Is VSLA: {$cycle['is_vsla_cycle']}\n";
echo "  Is Active: {$cycle['is_active_cycle']}\n";
echo "  Share Value: UGX {$cycle['share_value']}\n\n";

$result = $conn->query("SELECT id, name, type FROM ffs_groups WHERE id = 5");
$group = $result->fetch_assoc();

echo "Group 5 Final Status:\n";
echo "  ID: {$group['id']}\n";
echo "  Name: {$group['name']}\n";
echo "  Type: {$group['type']}\n\n";

echo "✅ MOBILE APP CAN NOW SUBMIT MEETINGS WITH CYCLE 1!\n";
echo "\nThe app will:\n";
echo "1. Fetch cycle 1 (now a valid VSLA cycle)\n";
echo "2. Extract group_id = 5 (now a valid VSLA group)\n";
echo "3. Submit meeting successfully\n";

$conn->close();
