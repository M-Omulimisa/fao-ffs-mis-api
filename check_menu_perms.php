<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check menu items with permissions
$menus = DB::table('admin_menu')->select('id', 'title', 'uri', 'permission')->get();
echo "=== MENU ITEMS ===\n";
foreach ($menus as $m) {
    $perm = $m->permission ?: '(none)';
    echo "#{$m->id} {$m->title} | uri={$m->uri} | perm={$perm}\n";
}

// Check permission slugs
echo "\n=== PERMISSIONS ===\n";
$perms = DB::table('admin_permissions')->select('id', 'slug', 'name', 'http_method', 'http_path')->get();
foreach ($perms as $p) {
    $method = $p->http_method ?: '(any)';
    $path = $p->http_path ?: '(none)';
    echo "#{$p->id} slug={$p->slug} | name={$p->name} | method={$method} | path={$path}\n";
}

// Check role-permission assignments
echo "\n=== ROLE-PERMISSION ASSIGNMENTS ===\n";
$rps = DB::table('admin_role_permissions')
    ->join('admin_roles', 'admin_role_permissions.role_id', '=', 'admin_roles.id')
    ->join('admin_permissions', 'admin_role_permissions.permission_id', '=', 'admin_permissions.id')
    ->select('admin_roles.slug as role', 'admin_permissions.slug as perm')
    ->get();
foreach ($rps as $rp) {
    echo "role={$rp->role} => perm={$rp->perm}\n";
}

// Check role-user assignments  
echo "\n=== ROLE-USER ASSIGNMENTS ===\n";
$rus = DB::table('admin_role_users')
    ->join('admin_roles', 'admin_role_users.role_id', '=', 'admin_roles.id')
    ->join('users', 'admin_role_users.user_id', '=', 'users.id')
    ->select('users.id as uid', 'users.name', 'admin_roles.slug as role')
    ->get();
foreach ($rus as $ru) {
    echo "user #{$ru->uid} {$ru->name} => role={$ru->role}\n";
}

// Check if user has direct permissions
echo "\n=== USER-PERMISSION (direct) ===\n";
$ups = DB::table('admin_user_permissions')->get();
echo 'Count: ' . count($ups) . "\n";
foreach ($ups as $up) {
    echo "user_id={$up->user_id} perm_id={$up->permission_id}\n";
}

// Simulate the menu visibility check for IP manager (user 219)
echo "\n=== SIMULATING MENU VISIBILITY FOR IP MANAGER ===\n";
$user = \App\Models\Administrator::find(219);
if ($user) {
    echo "User: {$user->name}, IP ID: {$user->ip_id}\n";
    echo "isRole('administrator'): " . ($user->isRole('administrator') ? 'true' : 'false') . "\n";
    echo "isRole('super_admin'): " . ($user->isRole('super_admin') ? 'true' : 'false') . "\n";
    echo "isRole('ip_manager'): " . ($user->isRole('ip_manager') ? 'true' : 'false') . "\n";
    
    $allPerms = $user->allPermissions();
    echo "All permissions: " . $allPerms->pluck('slug')->implode(', ') . "\n";
    
    // Simulate can() for various abilities
    foreach (['', null, '*', 'groups', 'ffs-all-groups', 'dashboard'] as $ability) {
        $abilityStr = var_export($ability, true);
        $result = $user->can($ability) ? 'CAN' : 'CANNOT';
        echo "  can({$abilityStr}): {$result}\n";
    }
    
    // Simulate visible() for each menu
    echo "\n  Menu visibility:\n";
    $menuModel = new (config('admin.database.menu_model'));
    $nodes = $menuModel->allNodes();
    foreach ($nodes as $node) {
        $roles = $node['roles'] ?? [];
        $perm = $node['permission'] ?? null;
        $visibleResult = $user->visible(is_array($roles) ? $roles : $roles->toArray()) ? 'VISIBLE' : 'HIDDEN';
        $canResult = $user->can($perm) ? 'CAN' : 'CANNOT';
        $overall = ($visibleResult === 'VISIBLE' && $canResult === 'CAN') ? 'SHOW' : 'HIDE';
        echo "  [{$overall}] #{$node['id']} {$node['title']} | visible={$visibleResult} can({$perm})={$canResult}\n";
    }
} else {
    echo "User 219 not found!\n";
}
