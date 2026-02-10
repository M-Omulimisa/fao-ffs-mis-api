<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update 'All Sessions' (id=28) to point to ffs-training-sessions
DB::table('admin_menu')->where('id', 28)->update(['uri' => 'ffs-training-sessions']);

// Update 'Schedule New Session' (id=29) to point to ffs-training-sessions/create
DB::table('admin_menu')->where('id', 29)->update(['uri' => 'ffs-training-sessions/create']);

// Update 'Attendance Records' (id=31) to point to ffs-session-participants
DB::table('admin_menu')->where('id', 31)->update(['uri' => 'ffs-session-participants']);

// Update 'Session Reports' (id=32) to point to ffs-session-resolutions (Meeting Resolutions/GAP)
DB::table('admin_menu')->where('id', 32)->update([
    'uri' => 'ffs-session-resolutions',
    'title' => 'Meeting Resolutions (GAP)',
]);

echo "Menu items updated successfully!\n";

// Verify
$items = DB::table('admin_menu')->whereIn('id', [28, 29, 31, 32])->get(['id', 'title', 'uri']);
foreach ($items as $i) {
    echo $i->id . ' | ' . $i->title . ' | ' . $i->uri . "\n";
}
