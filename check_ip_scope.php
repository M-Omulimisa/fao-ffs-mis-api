<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== IP SCOPING INVESTIGATION ===\n\n";

// 1. Members in IP1 groups with NULL member ip_id
$c1 = DB::table("users")
    ->join("ffs_groups", "users.group_id", "=", "ffs_groups.id")
    ->where("ffs_groups.ip_id", 1)
    ->whereNull("users.ip_id")
    ->count();
echo "Members in ip_id=1 groups but member.ip_id is NULL: $c1\n";

// 2. Total members in IP1 groups
$c2 = DB::table("users")
    ->join("ffs_groups", "users.group_id", "=", "ffs_groups.id")
    ->where("ffs_groups.ip_id", 1)
    ->count();
echo "Total members in ip_id=1 groups: $c2\n";

// 3. Members with ip_id=1 set directly
$c3 = DB::table("users")->where("ip_id", 1)->count();
echo "Members with ip_id=1 directly: $c3\n";

// 4. Members in IP2 groups
$c4 = DB::table("users")
    ->join("ffs_groups", "users.group_id", "=", "ffs_groups.id")
    ->where("ffs_groups.ip_id", 2)
    ->count();
echo "Total members in ip_id=2 groups: $c4\n";

// 5. Members with no group
$c5 = DB::table("users")->whereNull("group_id")->count();
echo "Members with no group_id: $c5\n";

// 6. Total users
$c6 = DB::table("users")->count();
echo "Total users: $c6\n";

// 7. IPs
$ips = DB::table("implementing_partners")->select("id", "name")->get();
echo "\nImplementing Partners:\n";
foreach ($ips as $ip) {
    echo "  #{$ip->id} {$ip->name}\n";
}

// 8. IP distribution of members
echo "\nMember ip_id distribution:\n";
$dist = DB::table("users")->select(DB::raw("ip_id, count(*) as cnt"))->groupBy("ip_id")->get();
foreach ($dist as $d) {
    echo "  ip_id=" . ($d->ip_id ?? 'NULL') . " => " . $d->cnt . " members\n";
}

echo "\nDone.\n";
