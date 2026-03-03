<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ADMIN PERMISSIONS & MENU INVESTIGATION ===\n\n";

// 1. Admin roles
echo "--- Admin Roles ---\n";
$roles = DB::table("admin_roles")->get();
foreach ($roles as $r) {
    echo "  Role #{$r->id} slug={$r->slug} name={$r->name}\n";
}

// 2. Admin permissions
echo "\n--- Admin Permissions ---\n";
$perms = DB::table("admin_permissions")->get();
foreach ($perms as $p) {
    echo "  Perm #{$p->id} slug={$p->slug} name={$p->name} http_method=[{$p->http_method}] http_path=[{$p->http_path}]\n";
}

// 3. Role-Permission mappings
echo "\n--- Role-Permission Mappings ---\n";
$rp = DB::table("admin_role_permissions")
    ->join("admin_roles", "admin_roles.id", "=", "admin_role_permissions.role_id")
    ->join("admin_permissions", "admin_permissions.id", "=", "admin_role_permissions.permission_id")
    ->select("admin_roles.slug as role", "admin_permissions.slug as perm", "admin_permissions.http_path")
    ->get();
foreach ($rp as $item) {
    echo "  {$item->role} => {$item->perm} [{$item->http_path}]\n";
}

// 4. Admin menu items
echo "\n--- Admin Menu ---\n";
$menu = DB::table("admin_menu")->orderBy("order")->get();
foreach ($menu as $m) {
    $indent = str_repeat("  ", $m->parent_id > 0 ? 1 : 0);
    echo "  {$indent}#{$m->id} parent={$m->parent_id} title={$m->title} uri={$m->uri} icon={$m->icon} order={$m->order}\n";
}

// 5. Role-Menu mappings
echo "\n--- Role-Menu Mappings ---\n";
if (DB::getSchemaBuilder()->hasTable('admin_role_menu')) {
    $rm = DB::table("admin_role_menu")
        ->join("admin_roles", "admin_roles.id", "=", "admin_role_menu.role_id")
        ->join("admin_menu", "admin_menu.id", "=", "admin_role_menu.menu_id")
        ->select("admin_roles.slug as role", "admin_menu.title as menu_title", "admin_menu.uri")
        ->get();
    foreach ($rm as $item) {
        echo "  {$item->role} => {$item->menu_title} [{$item->uri}]\n";
    }
} else {
    echo "  admin_role_menu table does not exist\n";
}

// 6. Check if there's permission middleware
echo "\n--- Config admin permissions ---\n";
$middleware = config('admin.route.middleware');
echo "  Middleware: " . json_encode($middleware) . "\n";

$excepts = config('admin.auth.excepts', []);
echo "  Auth excepts: " . json_encode($excepts) . "\n";

$permCheck = config('admin.check_route_permission', true);
echo "  Check route permission: " . ($permCheck ? 'true' : 'false') . "\n";

$permCheckMenu = config('admin.check_menu_roles', true);
echo "  Check menu roles: " . ($permCheckMenu ? 'true' : 'false') . "\n";

echo "\nDone.\n";
