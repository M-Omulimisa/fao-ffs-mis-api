<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SIMULATING ADMIN CONTEXT ===\n\n";

// Load user #219 (Cruz Wise, IP Manager) as Administrator model
$adminModel = config('admin.database.users_model');
echo "Admin model class: {$adminModel}\n";
$adminTable = config('admin.database.users_table');
echo "Admin table: {$adminTable}\n\n";

$user = $adminModel::find(219);
if (!$user) {
    echo "User 219 not found!\n";
    exit;
}

echo "User loaded: #{$user->id} {$user->name}\n";
echo "ip_id (raw): " . var_export($user->ip_id, true) . "\n";
echo "ip_id type: " . gettype($user->ip_id) . "\n";
echo "Has attribute ip_id: " . (array_key_exists('ip_id', $user->getAttributes()) ? 'YES' : 'NO') . "\n";

// Check role
$roles = $user->roles;
echo "\nRoles:\n";
foreach ($roles as $role) {
    echo "  - {$role->slug} ({$role->name})\n";
}

echo "\nisRole('super_admin'): " . ($user->isRole('super_admin') ? 'true' : 'false') . "\n";
echo "isRole('ip_manager'): " . ($user->isRole('ip_manager') ? 'true' : 'false') . "\n";

// Now simulate getAdminIpId logic
$isSuperAdmin = $user->isRole('super_admin');
echo "\nisSuperAdmin: " . ($isSuperAdmin ? 'true' : 'false') . "\n";

if ($isSuperAdmin) {
    $ipId = null;
} else {
    $ipId = $user->ip_id;
}
echo "getAdminIpId result: " . var_export($ipId, true) . "\n";

// Simulate what the grid would show
echo "\n=== WHAT IP MANAGER WOULD SEE ===\n";
echo "Groups with ip_id={$ipId}: " . DB::table("ffs_groups")->where("ip_id", $ipId)->count() . "\n";
echo "Members with ip_id={$ipId}: " . DB::table("users")->where("ip_id", $ipId)->count() . "\n";
echo "Members with ip_id={$ipId} AND group_id set: " . DB::table("users")->where("ip_id", $ipId)->whereNotNull("group_id")->count() . "\n";

// Also check User #1 (Super Admin)
echo "\n=== SUPER ADMIN (User #1) ===\n";
$superAdmin = $adminModel::find(1);
echo "User: #{$superAdmin->id} {$superAdmin->name}\n";
echo "ip_id: " . var_export($superAdmin->ip_id, true) . "\n";
echo "isRole('super_admin'): " . ($superAdmin->isRole('super_admin') ? 'true' : 'false') . "\n";

echo "\nDone.\n";
