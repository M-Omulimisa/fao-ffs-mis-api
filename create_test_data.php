<?php

/**
 * Create Test VSLA Groups and Cycles
 * Creates 100 VSLA groups and 100 VSLA cycles for testing
 * First 50 cycles will be active, remaining 50 will be inactive
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== CREATING TEST VSLA DATA ===\n\n";

// Get an admin user for created_by
$adminUser = DB::table('users')->first();
if (!$adminUser) {
    echo "❌ No users found. Please create at least one user first.\n";
    exit(1);
}

echo "Using admin user: {$adminUser->name} (ID: {$adminUser->id})\n\n";

// Arrays for realistic names
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

// Start transaction
DB::beginTransaction();

try {
    echo "1. Creating 100 VSLA Groups...\n";
    
    $groups = [];
    $groupIds = [];
    
    for ($i = 1; $i <= 100; $i++) {
        $location = $locations[array_rand($locations)];
        $groupType = $groupTypes[array_rand($groupTypes)];
        $createdDate = now()->subDays(rand(30, 365));
        
        $groupId = DB::table('ffs_groups')->insertGetId([
            'name' => "{$groupType} - {$location} Group {$i}",
            'type' => 'VSLA',
            'code' => 'VSLA-' . strtoupper(Str::random(6)),
            'description' => "A {$groupType} VSLA group operating in {$location} district, focused on financial inclusion and community development.",
            'status' => 'Active',
            'contact_person_phone' => '0' . rand(700, 799) . rand(100000, 999999),
            'total_members' => rand(15, 50),
            'created_by_id' => $adminUser->id,
            'created_at' => $createdDate,
            'updated_at' => now(),
        ]);
        
        $groups[] = (object)[
            'id' => $groupId,
            'name' => "{$groupType} - {$location} Group {$i}",
            'location' => $location
        ];
        $groupIds[] = $groupId;
        
        if ($i % 10 == 0) {
            echo "   Created {$i} groups...\n";
        }
    }
    
    echo "✓ Created 100 VSLA groups (IDs: {$groupIds[0]} - {$groupIds[99]})\n\n";
    
    echo "2. Creating 100 VSLA Cycles (Projects)...\n";
    
    $cycles = [];
    $cycleIds = [];
    
    for ($i = 1; $i <= 100; $i++) {
        // First 50 cycles are active, rest are inactive
        $isActive = $i <= 50;
        
        // Random group from our created groups
        $group = $groups[array_rand($groups)];
        
        // Random start date in the past
        $startDate = now()->subDays(rand(0, 180));
        
        // Cycle duration: 9-12 months
        $durationMonths = rand(9, 12);
        $endDate = (clone $startDate)->addMonths($durationMonths);
        
        // Share value: 1,000 - 10,000 UGX
        $shareValue = rand(1, 10) * 1000;
        
        // Meeting frequency
        $frequencies = ['Weekly', 'Bi-Weekly', 'Monthly'];
        $meetingFrequency = $frequencies[array_rand($frequencies)];
        
        // Interest rates
        $monthlyInterestRate = rand(5, 15); // 5-15%
        $weeklyInterestRate = round($monthlyInterestRate / 4, 2); // Approximate weekly
        
        // Loan parameters
        $minLoanAmount = $shareValue * 5;
        $maxLoanMultiple = rand(3, 10);
        
        $cycleId = DB::table('projects')->insertGetId([
            'title' => "VSLA Cycle {$i} - {$group->location} " . now()->year,
            'description' => "Savings cycle for {$group->name}. Members save together and access affordable loans to grow their businesses and improve their livelihoods.",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $isActive ? 'ongoing' : 'completed',
            'group_id' => $group->id,
            
            // VSLA specific fields
            'is_vsla_cycle' => 'Yes',
            'is_active_cycle' => $isActive ? 'Yes' : 'No',
            'cycle_name' => "Cycle {$i} - " . $startDate->format('M Y'),
            'share_value' => $shareValue,
            'meeting_frequency' => $meetingFrequency,
            'loan_interest_rate' => $monthlyInterestRate,
            'interest_frequency' => 'Monthly',
            'weekly_loan_interest_rate' => $weeklyInterestRate,
            'monthly_loan_interest_rate' => $monthlyInterestRate,
            'minimum_loan_amount' => $minLoanAmount,
            'maximum_loan_multiple' => $maxLoanMultiple,
            
            // Standard project fields
            'created_by_id' => $adminUser->id,
            'created_at' => $startDate,
            'updated_at' => now(),
        ]);
        
        $cycles[] = (object)['id' => $cycleId];
        $cycleIds[] = $cycleId;
        
        if ($i % 10 == 0) {
            echo "   Created {$i} cycles...\n";
        }
    }
    
    echo "✓ Created 100 VSLA cycles (IDs: {$cycleIds[0]} - {$cycleIds[99]})\n";
    echo "   - First 50 cycles (IDs {$cycleIds[0]} - {$cycleIds[49]}) are ACTIVE\n";
    echo "   - Last 50 cycles (IDs {$cycleIds[50]} - {$cycleIds[99]}) are INACTIVE\n\n";
    
    DB::commit();
    
    echo "3. Verifying created data...\n";
    
    // Verify groups
    $vslaGroupsCount = DB::table('ffs_groups')->where('type', 'VSLA')->count();
    echo "   Total VSLA groups: {$vslaGroupsCount}\n";
    
    // Verify cycles
    $vslaCyclesCount = DB::table('projects')->where('is_vsla_cycle', 'Yes')->count();
    $activeCyclesCount = DB::table('projects')
        ->where('is_vsla_cycle', 'Yes')
        ->where('is_active_cycle', 'Yes')
        ->count();
    $inactiveCyclesCount = DB::table('projects')
        ->where('is_vsla_cycle', 'Yes')
        ->where('is_active_cycle', 'No')
        ->count();
    
    echo "   Total VSLA cycles: {$vslaCyclesCount}\n";
    echo "   Active cycles: {$activeCyclesCount}\n";
    echo "   Inactive cycles: {$inactiveCyclesCount}\n\n";
    
    echo "4. Sample Active Cycles for Testing:\n";
    $sampleCycles = DB::table('projects')
        ->where('is_vsla_cycle', 'Yes')
        ->where('is_active_cycle', 'Yes')
        ->take(5)
        ->get(['id', 'title', 'group_id', 'share_value', 'meeting_frequency']);
    
    foreach ($sampleCycles as $sample) {
        echo "   • ID: {$sample->id} | {$sample->title}\n";
        echo "     Group ID: {$sample->group_id} | Share Value: UGX {$sample->share_value} | Frequency: {$sample->meeting_frequency}\n";
    }
    
    echo "\n✅ ALL TEST DATA CREATED SUCCESSFULLY!\n";
    echo "\n=== SUMMARY ===\n";
    echo "✓ 100 VSLA groups created\n";
    echo "✓ 100 VSLA cycles created (50 active, 50 inactive)\n";
    echo "✓ All data properly linked and validated\n";
    echo "\nYou can now test meeting submissions with any of the active cycle IDs!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nRolling back all changes...\n";
    
    exit(1);
}
