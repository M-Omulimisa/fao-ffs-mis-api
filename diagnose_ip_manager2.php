<?php
/**
 * Check admin operation log for IP manager user #219 and test Admin::user() auth
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== IP MANAGER ACCESS LOG ===\n\n";

// Check recent operation logs for user 219
echo "--- Recent Operation Logs for user #219 ---\n";
$logs = DB::table('admin_operation_log')
    ->where('user_id', 219)
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

if ($logs->isEmpty()) {
    echo "  NO LOGS for user #219. This user has NEVER logged in or logs are empty.\n";
} else {
    foreach ($logs as $log) {
        echo "  [{$log->created_at}] {$log->method} {$log->path} (IP: {$log->ip})\n";
        if ($log->input && $log->input !== '[]') {
            echo "    Input: " . substr($log->input, 0, 100) . "\n";
        }
    }
}

// Check ALL recent admin logins
echo "\n--- Recent Admin Operation Logs (last 30 entries) ---\n";
$recentLogs = DB::table('admin_operation_log')
    ->orderBy('id', 'desc')
    ->limit(30)
    ->get();
foreach ($recentLogs as $log) {
    $user = DB::table('users')->where('id', $log->user_id)->first();
    $userName = $user ? $user->name : "Unknown(#{$log->user_id})";
    $ipIdStr = $user ? "ip_id=" . ($user->ip_id ?? 'NULL') : '';
    echo "  [{$log->created_at}] User: {$userName} ({$ipIdStr}) | {$log->method} {$log->path}\n";
}

// Check all admin users with their roles
echo "\n--- All Admin Users (with roles and ip_id) ---\n";
$adminUsers = DB::table('admin_role_users')
    ->join('users', 'users.id', '=', 'admin_role_users.user_id')
    ->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
    ->select('users.id', 'users.name', 'users.email', 'users.ip_id', 'users.group_id', 'users.password',
            'admin_roles.slug as role_slug', 'admin_roles.name as role_name')
    ->get();

foreach ($adminUsers as $u) {
    $hasPassword = !empty($u->password) ? 'YES' : 'NO';
    echo "  User #{$u->id}: {$u->name} (email={$u->email}, ip_id=" . ($u->ip_id ?? 'NULL') . 
         ", group_id=" . ($u->group_id ?? 'NULL') . ", role={$u->role_slug}, has_password={$hasPassword})\n";
}

// Check if the isRole method works via the model directly
echo "\n--- Testing isRole on Administrator model ---\n";
$admin = \App\Models\Administrator::find(219);
if ($admin) {
    echo "  Admin #219 found: {$admin->name}\n";
    echo "  ip_id: " . var_export($admin->ip_id, true) . "\n";
    echo "  isRole('super_admin'): " . var_export($admin->isRole('super_admin'), true) . "\n";
    echo "  isRole('ip_manager'): " . var_export($admin->isRole('ip_manager'), true) . "\n";
    
    // Check roles
    echo "  roles(): ";
    foreach ($admin->roles as $role) {
        echo "{$role->slug}, ";
    }
    echo "\n";
    
    // Check $admin->ip_id column directly from DB
    $rawUser = DB::table('users')->where('id', 219)->first();
    echo "  Raw DB ip_id: " . var_export($rawUser->ip_id, true) . "\n";
    echo "  Raw DB ip_id type: " . gettype($rawUser->ip_id) . "\n";
} else {
    echo "  Admin #219 NOT FOUND via Administrator model!\n";
}

// Test the super admin user too
$admin1 = \App\Models\Administrator::find(1);
if ($admin1) {
    echo "\n  Admin #1 ({$admin1->name}):\n";
    echo "    ip_id: " . var_export($admin1->ip_id, true) . "\n";
    echo "    isRole('super_admin'): " . var_export($admin1->isRole('super_admin'), true) . "\n";
}

// Check the laravel-admin auth guard config
echo "\n--- Auth Configuration ---\n";
$authConfig = config('auth');
echo "  Guards: " . json_encode(array_keys($authConfig['guards'] ?? []), JSON_PRETTY_PRINT) . "\n";
if (isset($authConfig['guards']['admin'])) {
    echo "  admin guard: " . json_encode($authConfig['guards']['admin']) . "\n";
}
if (isset($authConfig['providers']['admin'])) {
    echo "  admin provider: " . json_encode($authConfig['providers']['admin']) . "\n";
}

echo "\n=== DONE ===\n";
