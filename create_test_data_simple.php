<?php

/**
 * Create Test VSLA Groups and Cycles - Direct DB Approach
 * Creates 100 VSLA groups and 100 VSLA cycles for testing
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap minimal Laravel for DB only
$app = require_once __DIR__ . '/bootstrap/app.php';

// Set up database connection
$capsule = new \Illuminate\Database\Capsule\Manager;
$config = require __DIR__ . '/config/database.php';
$dbConfig = $config['connections'][$config['default']];

$capsule->addConnection($dbConfig);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$db = $capsule->connection();

echo "=== CREATING TEST VSLA DATA ===\n\n";

// Get admin user
$adminUser = $db->table('users')->first();
if (!$adminUser) {
    echo "❌ No users found\n";
    exit(1);
}

echo "Using user: {$adminUser->name} (ID: {$adminUser->id})\n\n";

// Location and group type arrays
$locations = [
    'Kampala', 'Entebbe', 'Jinja', 'Mbale', 'Gulu', 'Lira', 'Mbarara', 'Fort Portal',
    'Masaka', 'Kasese', 'Kabale', 'Soroti', 'Arua', 'Hoima', 'Mukono', 'Wakiso',
    'Mityana', 'Kiboga', 'Mubende', 'Kyenjojo', 'Bundibugyo', 'Ntungamo', 'Bushenyi',
    'Rukungiri', 'Kanungu', 'Kisoro', 'Tororo', 'Busia', 'Iganga', 'Bugiri'
];

$groupTypes = [
    'Women Empowerment', 'Youth Development', 'Farmers Cooperative', 'Market Vendors',
    'Community Development', 'Agricultural', 'Small Business', 'Rural Development',
    'Urban Development', 'Mixed Gender', 'Widows Support', 'Single Mothers',
    'Disabled Persons', 'Elders Care', 'School Teachers', 'Health Workers'
];

$db->beginTransaction();

try {
    echo "1. Creating 100 VSLA Groups...\n";
    
    $groups = [];
    $groupIds = [];
    
    for ($i = 1; $i <= 100; $i++) {
        $location = $locations[array_rand($locations)];
        $groupType = $groupTypes[array_rand($groupTypes)];
        $createdDate = date('Y-m-d H:i:s', strtotime('-' . rand(30, 365) . ' days'));
        
        $groupId = $db->table('ffs_groups')->insertGetId([
            'name' => "{$groupType} - {$location} Group {$i}",
            'type' => 'VSLA',
            'code' => 'VSLA-' . strtoupper(substr(md5(uniqid()), 0, 6)),
            'description' => "A {$groupType} VSLA group operating in {$location} district.",
            'status' => 'Active',
            'contact_person_phone' => '0' . rand(700, 799) . rand(100000, 999999),
            'total_members' => rand(15, 50),
            'created_by_id' => $adminUser->id,
            'created_at' => $createdDate,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $groups[] = ['id' => $groupId, 'name' => "{$groupType} - {$location} Group {$i}", 'location' => $location];
        $groupIds[] = $groupId;
        
        if ($i % 20 == 0) {
            echo "   Created {$i} groups...\n";
        }
    }
    
    echo "✓ Created 100 VSLA groups (IDs: {$groupIds[0]} - {$groupIds[99]})\n\n";
    
    echo "2. Creating 100 VSLA Cycles...\n";
    
    $cycleIds = [];
    
    for ($i = 1; $i <= 100; $i++) {
        $isActive = $i <= 50; // First 50 are active
        $group = $groups[array_rand($groups)];
        
        $daysAgo = rand(0, 180);
        $startDate = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $durationMonths = rand(9, 12);
        $endDate = date('Y-m-d', strtotime($startDate . " +{$durationMonths} months"));
        
        $shareValue = rand(1, 10) * 1000;
        $frequencies = ['Weekly', 'Bi-Weekly', 'Monthly'];
        $meetingFrequency = $frequencies[array_rand($frequencies)];
        
        $monthlyInterestRate = rand(5, 15);
        $weeklyInterestRate = round($monthlyInterestRate / 4, 2);
        $minLoanAmount = $shareValue * 5;
        $maxLoanMultiple = rand(3, 10);
        
        $cycleId = $db->table('projects')->insertGetId([
            'title' => "VSLA Cycle {$i} - {$group['location']} " . date('Y'),
            'description' => "Savings cycle for {$group['name']}. Members save together and access affordable loans.",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $isActive ? 'ongoing' : 'completed',
            'group_id' => $group['id'],
            'is_vsla_cycle' => 'Yes',
            'is_active_cycle' => $isActive ? 'Yes' : 'No',
            'cycle_name' => "Cycle {$i} - " . date('M Y', strtotime($startDate)),
            'share_value' => $shareValue,
            'meeting_frequency' => $meetingFrequency,
            'loan_interest_rate' => $monthlyInterestRate,
            'interest_frequency' => 'Monthly',
            'weekly_loan_interest_rate' => $weeklyInterestRate,
            'monthly_loan_interest_rate' => $monthlyInterestRate,
            'minimum_loan_amount' => $minLoanAmount,
            'maximum_loan_multiple' => $maxLoanMultiple,
            'created_by_id' => $adminUser->id,
            'created_at' => $startDate . ' 00:00:00',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $cycleIds[] = $cycleId;
        
        if ($i % 20 == 0) {
            echo "   Created {$i} cycles...\n";
        }
    }
    
    echo "✓ Created 100 VSLA cycles (IDs: {$cycleIds[0]} - {$cycleIds[99]})\n";
    echo "   - First 50 cycles (IDs {$cycleIds[0]} - {$cycleIds[49]}) are ACTIVE\n";
    echo "   - Last 50 cycles (IDs {$cycleIds[50]} - {$cycleIds[99]}) are INACTIVE\n\n";
    
    $db->commit();
    
    echo "3. Verifying created data...\n";
    $vslaGroupsCount = $db->table('ffs_groups')->where('type', 'VSLA')->count();
    $vslaCyclesCount = $db->table('projects')->where('is_vsla_cycle', 'Yes')->count();
    $activeCyclesCount = $db->table('projects')
        ->where('is_vsla_cycle', 'Yes')
        ->where('is_active_cycle', 'Yes')
        ->count();
    
    echo "   Total VSLA groups: {$vslaGroupsCount}\n";
    echo "   Total VSLA cycles: {$vslaCyclesCount}\n";
    echo "   Active cycles: {$activeCyclesCount}\n\n";
    
    echo "4. Sample Active Cycles for Testing:\n";
    $samples = $db->table('projects')
        ->where('is_vsla_cycle', 'Yes')
        ->where('is_active_cycle', 'Yes')
        ->take(5)
        ->get(['id', 'title', 'group_id', 'share_value', 'meeting_frequency']);
    
    foreach ($samples as $s) {
        echo "   • ID: {$s->id} | {$s->title}\n";
        echo "     Group: {$s->group_id} | Share: UGX {$s->share_value} | Freq: {$s->meeting_frequency}\n";
    }
    
    echo "\n✅ ALL TEST DATA CREATED SUCCESSFULLY!\n";
    echo "\n=== SUMMARY ===\n";
    echo "✓ 100 VSLA groups created\n";
    echo "✓ 100 VSLA cycles created (50 active, 50 inactive)\n";
    echo "✓ Ready for testing!\n";
    
} catch (\Exception $e) {
    $db->rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}
