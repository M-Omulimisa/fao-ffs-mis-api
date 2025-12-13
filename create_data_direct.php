<?php
// Run: php -f create_data_direct.php

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "fao_ffs_mis";
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

$conn = new mysqli($servername, $username, $password, $dbname, null, $socket);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== CREATING TEST VSLA DATA ===\n\n";

// Get user
$result = $conn->query("SELECT id, name FROM users LIMIT 1");
$user = $result->fetch_assoc();
echo "Using user: {$user['name']} (ID: {$user['id']})\n\n";

$userId = $user['id'];
$groupIds = [];
$locations = ['Kampala', 'Entebbe', 'Jinja', 'Mbale', 'Gulu', 'Lira', 'Mbarara'];
$types = ['Women', 'Youth', 'Farmers', 'Vendors', 'Mixed'];

echo "1. Creating 100 VSLA Groups...\n";

for ($i = 1; $i <= 100; $i++) {
    $loc = $locations[array_rand($locations)];
    $type = $types[array_rand($types)];
    $code = 'VSLA-' . strtoupper(substr(md5(uniqid()), 0, 6));
    $members = rand(15, 50);
    $daysAgo = rand(30, 365);
    $created = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO ffs_groups (name, type, code, status, total_members, created_by_id, created_at, updated_at) 
            VALUES ('$type - $loc Group $i', 'VSLA', '$code', 'Active', $members, $userId, '$created', '$now')";
    
    $conn->query($sql);
    $groupIds[] = $conn->insert_id;
    
    if ($i % 20 == 0) echo "   Created $i groups...\n";
}

echo "✓ Created 100 groups (IDs: {$groupIds[0]} - {$groupIds[99]})\n\n";

echo "2. Creating 100 VSLA Cycles...\n";

$cycleIds = [];

for ($i = 1; $i <= 100; $i++) {
    $isActive = $i <= 50;
    $groupId = $groupIds[array_rand($groupIds)];
    
    $daysAgo = rand(0, 180);
    $start = date('Y-m-d', strtotime("-$daysAgo days"));
    $end = date('Y-m-d', strtotime("$start +12 months"));
    
    $shareValue = rand(1, 10) * 1000;
    $status = $isActive ? 'ongoing' : 'completed';
    $active = $isActive ? 'Yes' : 'No';
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO projects (title, description, start_date, end_date, status, group_id, 
            is_vsla_cycle, is_active_cycle, share_value, meeting_frequency, loan_interest_rate, 
            monthly_loan_interest_rate, created_by_id, created_at, updated_at) 
            VALUES ('VSLA Cycle $i - 2025', 'Test cycle for mobile app testing', '$start', '$end', 
            '$status', $groupId, 'Yes', '$active', $shareValue, 'Weekly', 10, 10, $userId, '$start 00:00:00', '$now')";
    
    $conn->query($sql);
    $cycleIds[] = $conn->insert_id;
    
    if ($i % 20 == 0) echo "   Created $i cycles...\n";
}

echo "✓ Created 100 cycles (IDs: {$cycleIds[0]} - {$cycleIds[99]})\n";
echo "   First 50 (IDs {$cycleIds[0]} - {$cycleIds[49]}) are ACTIVE\n\n";

echo "3. Verification...\n";

$groups = $conn->query("SELECT COUNT(*) as c FROM ffs_groups WHERE type='VSLA'")->fetch_assoc()['c'];
$cycles = $conn->query("SELECT COUNT(*) as c FROM projects WHERE is_vsla_cycle='Yes'")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) as c FROM projects WHERE is_vsla_cycle='Yes' AND is_active_cycle='Yes'")->fetch_assoc()['c'];

echo "   VSLA Groups: $groups\n";
echo "   VSLA Cycles: $cycles\n";
echo "   Active Cycles: $active\n\n";

echo "4. Sample Active Cycles:\n";
$samples = $conn->query("SELECT id, title, group_id, share_value FROM projects WHERE is_vsla_cycle='Yes' AND is_active_cycle='Yes' LIMIT 5");
while ($row = $samples->fetch_assoc()) {
    echo "   • ID: {$row['id']} | {$row['title']}\n";
    echo "     Group: {$row['group_id']} | Share: UGX {$row['share_value']}\n";
}

echo "\n✅ ALL TEST DATA CREATED!\n";
echo "\n=== SUMMARY ===\n";
echo "✓ 100 VSLA groups created\n";
echo "✓ 100 VSLA cycles created (50 active, 50 inactive)\n";
echo "✓ Ready for mobile app testing!\n";

$conn->close();
