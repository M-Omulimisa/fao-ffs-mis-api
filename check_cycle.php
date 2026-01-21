<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;

$cycle = Project::find(13);
if (!$cycle) {
    echo "❌ Cycle 13 NOT FOUND\n";
    exit(1);
}

echo "✅ Cycle found: {$cycle->name}\n";
echo "   is_vsla_cycle: {$cycle->is_vsla_cycle}\n";
echo "   is_active_cycle: {$cycle->is_active_cycle}\n";
echo "   status: {$cycle->status}\n";
