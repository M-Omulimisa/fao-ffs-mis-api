<?php
/**
 * Diagnose what IP manager sees - simulate dashboard queries
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\AccountTransaction;
use App\Models\VslaLoan;
use App\Models\VslaMeeting;
use App\Models\ImplementingPartner;
use Illuminate\Support\Facades\DB;

echo "=== IP MANAGER DIAGNOSTIC ===\n\n";

// 1. Check the IP manager user
echo "--- IP Manager User (Cruz Wise #219) ---\n";
$user = User::find(219);
if (!$user) {
    echo "User #219 NOT FOUND!\n";
    // Try finding any ip_manager
    $ipManagers = DB::table('users')
        ->join('admin_role_users', 'users.id', '=', 'admin_role_users.user_id')
        ->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
        ->where('admin_roles.slug', 'ip_manager')
        ->select('users.id', 'users.name', 'users.email', 'users.ip_id', 'users.group_id')
        ->get();
    echo "All IP managers:\n";
    foreach ($ipManagers as $m) {
        echo "  User #{$m->id}: {$m->name} (email={$m->email}, ip_id={$m->ip_id}, group_id={$m->group_id})\n";
    }
} else {
    echo "  ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  ip_id: " . var_export($user->ip_id, true) . "\n";
    echo "  group_id: " . var_export($user->group_id, true) . "\n";
    echo "  Type of ip_id: " . gettype($user->ip_id) . "\n";
    
    // Check roles
    echo "  Roles: ";
    $roles = DB::table('admin_role_users')
        ->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
        ->where('admin_role_users.user_id', 219)
        ->select('admin_roles.slug', 'admin_roles.name')
        ->get();
    foreach ($roles as $r) {
        echo "{$r->slug} ({$r->name}), ";
    }
    echo "\n";
    
    // Check isRole
    echo "  isRole('super_admin'): " . var_export($user->isRole('super_admin'), true) . "\n";
    echo "  isRole('ip_manager'): " . var_export($user->isRole('ip_manager'), true) . "\n";
}

echo "\n--- All IPs ---\n";
$ips = ImplementingPartner::all();
foreach ($ips as $ip) {
    echo "  IP #{$ip->id}: {$ip->name} ({$ip->short_name})\n";
}

echo "\n--- Database Column Check ---\n";
// Check if ip_id is actually in the users table
$columns = DB::select("SHOW COLUMNS FROM users WHERE Field = 'ip_id'");
echo "  users.ip_id column exists: " . (count($columns) > 0 ? 'YES' : 'NO') . "\n";
if (count($columns) > 0) {
    echo "  Type: {$columns[0]->Type}, Null: {$columns[0]->Null}, Default: " . var_export($columns[0]->Default, true) . "\n";
}

echo "\n--- Simulating Dashboard Queries with ip_id=1 ---\n";
$ipId = 1;

// Groups
$totalGroups = FfsGroup::where('ip_id', $ipId)->count();
$activeGroups = FfsGroup::where('status', 'Active')->where('ip_id', $ipId)->count();
echo "  FFS Groups (ip_id={$ipId}): total={$totalGroups}, active={$activeGroups}\n";

// Members
$totalMembers = User::whereNotNull('group_id')->where('group_id', '!=', '')->where('ip_id', $ipId)->count();
echo "  Registered Members (ip_id={$ipId}): {$totalMembers}\n";

// Try without group_id filter
$totalMembersNoGroupFilter = User::where('ip_id', $ipId)->count();
echo "  All Users (ip_id={$ipId}): {$totalMembersNoGroupFilter}\n";

// VSLA Groups
$vslaGroups = FfsGroup::where('type', 'VSLA')->where('ip_id', $ipId)->count();
echo "  VSLA Groups (ip_id={$ipId}): {$vslaGroups}\n";

// Savings - check if the relationship works
echo "\n--- AccountTransaction IP Scoping ---\n";
$totalTxns = AccountTransaction::count();
echo "  Total AccountTransactions: {$totalTxns}\n";

// Check if AccountTransaction has a 'group' relationship
try {
    $txnWithGroup = AccountTransaction::whereHas('group', fn($g) => $g->where('ip_id', $ipId))->count();
    echo "  AccountTransactions via group.ip_id={$ipId}: {$txnWithGroup}\n";
} catch (\Exception $e) {
    echo "  ERROR with 'group' relationship: {$e->getMessage()}\n";
}

// Check AccountTransaction columns
$txnCols = DB::select("SHOW COLUMNS FROM account_transactions");
echo "  AccountTransaction columns: ";
$colNames = array_map(fn($c) => $c->Field, $txnCols);
echo implode(', ', $colNames) . "\n";

// Check if group_id exists
if (in_array('group_id', $colNames)) {
    echo "  group_id column EXISTS\n";
    $txnByGroup = AccountTransaction::where('account_type', 'share')
        ->whereIn('group_id', FfsGroup::where('ip_id', $ipId)->pluck('id'))
        ->count();
    echo "  Share transactions for IP {$ipId} groups: {$txnByGroup}\n";
    $sumByGroup = AccountTransaction::where('account_type', 'share')
        ->whereIn('group_id', FfsGroup::where('ip_id', $ipId)->pluck('id'))
        ->sum('amount');
    echo "  Share amount for IP {$ipId} groups: {$sumByGroup}\n";
} else {
    echo "  group_id column MISSING - this will break IP scoping!\n";
}

// VslaLoan check
echo "\n--- VslaLoan IP Scoping ---\n";
$totalLoans = VslaLoan::count();
echo "  Total VslaLoans: {$totalLoans}\n";

try {
    $loansByIp = VslaLoan::whereHas('meeting', fn($m) => $m->where('ip_id', $ipId))->count();
    echo "  VslaLoans via meeting.ip_id={$ipId}: {$loansByIp}\n";
} catch (\Exception $e) {
    echo "  ERROR with VslaLoan->meeting: {$e->getMessage()}\n";
}

// Check VslaLoan columns
$loanCols = DB::select("SHOW COLUMNS FROM vsla_loans");
$loanColNames = array_map(fn($c) => $c->Field, $loanCols);
echo "  VslaLoan columns: " . implode(', ', $loanColNames) . "\n";

// VslaMeeting check  
echo "\n--- VslaMeeting IP Scoping ---\n";
$totalMeetings = VslaMeeting::count();
echo "  Total VslaMeetings: {$totalMeetings}\n";
$meetingsWithIp = VslaMeeting::where('ip_id', $ipId)->count();
echo "  VslaMeetings with ip_id={$ipId}: {$meetingsWithIp}\n";

// Check if VslaMeeting even has ip_id
$meetCols = DB::select("SHOW COLUMNS FROM vsla_meetings WHERE Field = 'ip_id'");
echo "  vsla_meetings.ip_id column exists: " . (count($meetCols) > 0 ? 'YES' : 'NO') . "\n";

// Check cycle (Project)
echo "\n--- Project (Cycles) IP Scoping ---\n";
$totalCycles = Project::where('is_vsla_cycle', 'Yes')->count();
echo "  Total VSLA Cycles: {$totalCycles}\n";

try {
    $cyclesByIp = Project::where('is_vsla_cycle', 'Yes')
        ->whereHas('group', fn($g) => $g->where('ip_id', $ipId))
        ->count();
    echo "  Cycles via group.ip_id={$ipId}: {$cyclesByIp}\n";
} catch (\Exception $e) {
    echo "  ERROR with Project->group: {$e->getMessage()}\n";
}

// Check SocialFundTransaction
echo "\n--- SocialFundTransaction IP Scoping ---\n";
try {
    $socialTotal = \App\Models\SocialFundTransaction::count();
    echo "  Total SocialFundTransactions: {$socialTotal}\n";
    $socialByIp = \App\Models\SocialFundTransaction::whereHas('meeting', fn($m) => $m->where('ip_id', $ipId))->count();
    echo "  Via meeting.ip_id={$ipId}: {$socialByIp}\n";
} catch (\Exception $e) {
    echo "  ERROR: {$e->getMessage()}\n";
}

// Check AdvisoryPost
echo "\n--- AdvisoryPost IP Scoping ---\n";
try {
    $advTotal = \App\Models\AdvisoryPost::count();
    echo "  Total AdvisoryPosts: {$advTotal}\n";
    $advByIp = \App\Models\AdvisoryPost::where('ip_id', $ipId)->count();
    echo "  With ip_id={$ipId}: {$advByIp}\n";
} catch (\Exception $e) {
    echo "  ERROR: {$e->getMessage()}\n";
}

// Check FfsTrainingSession
echo "\n--- FfsTrainingSession IP Scoping ---\n";
try {
    $sessTotal = \App\Models\FfsTrainingSession::count();
    echo "  Total FfsTrainingSessions: {$sessTotal}\n";
    $sessByIp = \App\Models\FfsTrainingSession::where('ip_id', $ipId)->count();
    echo "  With ip_id={$ipId}: {$sessByIp}\n";
} catch (\Exception $e) {
    echo "  ERROR: {$e->getMessage()}\n";
}

echo "\n--- Admin Auth Config ---\n";
$config = config('admin');
echo "  users_table: {$config['database']['users_table']}\n";
echo "  users_model: {$config['database']['users_model']}\n";
echo "  roles_table: {$config['database']['roles_table']}\n";
echo "  role_users_table: {$config['database']['role_users_table']}\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
