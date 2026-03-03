<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FfsGroup;
use App\Models\User;

echo "=== SIMULATING IP MANAGER DATA ACCESS ===\n\n";

// Simulate the IP manager's view
$ipId = 1; // Cruz Wise's ip_id

// 1. Dashboard KPI - Groups
$totalGroups = FfsGroup::where('ip_id', $ipId)->count();
$activeGroups = FfsGroup::where('status', 'Active')->where('ip_id', $ipId)->count();
echo "Dashboard KPI Groups: total={$totalGroups}, active={$activeGroups}\n";

// 2. Dashboard KPI - Members  
$totalMembers = User::whereNotNull('group_id')->where('group_id', '!=', '')->where('ip_id', $ipId)->count();
echo "Dashboard KPI Members: total={$totalMembers}\n";

// 3. FfsGroupController grid query
$groupQuery = FfsGroup::where('ip_id', $ipId);
$groups = $groupQuery->get();
echo "\nFfsGroupController grid: {$groups->count()} groups\n";
if ($groups->count() > 0) {
    echo "  Sample groups:\n";
    foreach ($groups->take(5) as $g) {
        echo "    #{$g->id} {$g->name} | type={$g->type} | status={$g->status}\n";
    }
}

// 4. MemberController grid query
$memberQuery = User::where('ip_id', $ipId);
$members = $memberQuery->get();
echo "\nMemberController grid (by users.ip_id): {$members->count()} members\n";
if ($members->count() > 0) {
    echo "  Sample members:\n";
    foreach ($members->take(5) as $m) {
        echo "    #{$m->id} {$m->name} | group_id={$m->group_id}\n";
    }
}

// 5. Check if the member grid has additional conditions
$membersWithGroup = User::where('ip_id', $ipId)->whereNotNull('group_id')->where('group_id', '!=', '')->get();
echo "\nMembers with group_id (filtered): {$membersWithGroup->count()}\n";

// 6. Check group types
echo "\nGroup type breakdown (ip_id=1):\n";
$types = FfsGroup::where('ip_id', $ipId)->groupBy('type')->selectRaw('type, count(*) as cnt')->get();
foreach ($types as $t) {
    echo "  {$t->type}: {$t->cnt}\n";
}

// 7. Check if any member has ip_id=NULL but group has ip_id=1
$orphanMembers = DB::select("
    SELECT u.id, u.name, u.ip_id as user_ip, u.group_id, g.ip_id as group_ip
    FROM users u
    LEFT JOIN ffs_groups g ON u.group_id = g.id
    WHERE g.ip_id = 1 AND (u.ip_id IS NULL OR u.ip_id != 1)
    LIMIT 10
");
echo "\nMembers in ip_id=1 groups but with different user.ip_id: " . count($orphanMembers) . "\n";
foreach ($orphanMembers as $om) {
    echo "  user #{$om->id} {$om->name} | user_ip_id={$om->user_ip} | group_ip_id={$om->group_ip}\n";
}

// 8. Check if MemberController uses ip_id from users table or via group relationship
echo "\n=== CRITICAL: Member ip_id source check ===\n";
$membersDirectIp = User::where('ip_id', $ipId)->count();
$membersViaGroup = User::whereHas('group', function($q) use ($ipId) { $q->where('ip_id', $ipId); })->count();
echo "Members by users.ip_id = 1: {$membersDirectIp}\n";
echo "Members via group.ip_id = 1: {$membersViaGroup}\n";

// 9. Check all admin users and what they'd see
echo "\n=== ALL ADMIN USERS & WHAT THEY'D SEE ===\n";
$adminUsers = DB::table('admin_role_users')
    ->join('users', 'admin_role_users.user_id', '=', 'users.id')
    ->join('admin_roles', 'admin_role_users.role_id', '=', 'admin_roles.id')
    ->select('users.id', 'users.name', 'users.ip_id', 'admin_roles.slug as role')
    ->get();
foreach ($adminUsers as $au) {
    $ipId = $au->ip_id;
    $groupCount = $ipId ? FfsGroup::where('ip_id', $ipId)->count() : FfsGroup::count();
    $memberCount = $ipId ? User::where('ip_id', $ipId)->count() : User::count();
    $isSuperAdmin = ($au->role === 'super_admin');
    $effectiveIp = $isSuperAdmin ? 'null (sees all)' : ($ipId ?? 'null (sees nothing?!)');
    echo "#{$au->id} {$au->name} | role={$au->role} | ip_id={$au->ip_id} | effective_ip={$effectiveIp} | groups={$groupCount} | members={$memberCount}\n";
}
