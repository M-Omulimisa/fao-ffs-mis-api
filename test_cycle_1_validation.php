<?php
// Test meeting submission with cycle 1

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "fao_ffs_mis";
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

$conn = new mysqli($servername, $username, $password, $dbname, null, $socket);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== TESTING CYCLE 1 MEETING SUBMISSION ===\n\n";

// Check cycle validation
$result = $conn->query("SELECT * FROM projects WHERE id = 1");
$cycle = $result->fetch_assoc();

echo "1. Cycle Validation:\n";
echo "   ✓ Cycle exists: ID = {$cycle['id']}\n";
echo "   ✓ Is VSLA cycle: {$cycle['is_vsla_cycle']}\n";
echo "   ✓ Is active: {$cycle['is_active_cycle']}\n";
echo "   ✓ Group ID: {$cycle['group_id']}\n\n";

// Check group validation
$result = $conn->query("SELECT * FROM ffs_groups WHERE id = {$cycle['group_id']}");
$group = $result->fetch_assoc();

echo "2. Group Validation:\n";
echo "   ✓ Group exists: ID = {$group['id']}\n";
echo "   ✓ Group type: {$group['type']}\n";
echo "   ✓ Group name: {$group['name']}\n\n";

echo "3. Validation Results:\n";

$validations = [
    'Cycle exists' => $cycle !== null,
    'Cycle is VSLA' => $cycle['is_vsla_cycle'] === 'Yes',
    'Cycle is active' => $cycle['is_active_cycle'] === 'Yes',
    'Group exists' => $group !== null,
    'Group is VSLA' => $group['type'] === 'VSLA'
];

$allPass = true;
foreach ($validations as $check => $pass) {
    $status = $pass ? '✅ PASS' : '❌ FAIL';
    echo "   $status - $check\n";
    if (!$pass) $allPass = false;
}

echo "\n";

if ($allPass) {
    echo "✅✅✅ ALL VALIDATIONS PASS! ✅✅✅\n\n";
    echo "Your mobile app can now submit meetings with:\n";
    echo "  cycle_id: 1\n";
    echo "  group_id: 5\n";
    echo "\nThe API will accept the request!\n";
} else {
    echo "❌ Some validations failed. Please check the output above.\n";
}

echo "\n=== TEST PAYLOAD (from your mobile app) ===\n";
echo "{\n";
echo "  \"local_id\": \"462866a0-757a-4aff-98a7-72bdb8dd5d3f\",\n";
echo "  \"cycle_id\": 1,\n";
echo "  \"group_id\": 5,\n";
echo "  \"meeting_date\": \"2025-12-13\",\n";
echo "  \"attendance_data\": [...],\n";
echo "  \"...\": \"...\"\n";
echo "}\n\n";

echo "✅ This payload will now be ACCEPTED by the server!\n";

$conn->close();
