<?php

// Script to truncate VSLA-related data from shared tables

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Starting VSLA data truncation...\n\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0');

// Clear VSLA groups
$vslaGroupCount = DB::table('ffs_groups')->where('type', 'VSLA')->count();
DB::table('ffs_groups')->where('type', 'VSLA')->delete();
echo "✓ Deleted $vslaGroupCount VSLA groups\n";

// Clear VSLA cycles
$vslaCycleCount = DB::table('projects')->where('is_vsla_cycle', 'Yes')->count();
$vslaCycleIds = DB::table('projects')->where('is_vsla_cycle', 'Yes')->pluck('id')->toArray();
DB::table('projects')->where('is_vsla_cycle', 'Yes')->delete();
echo "✓ Deleted $vslaCycleCount VSLA cycles\n";

// Clear VSLA transactions (check if column exists first)
$hasVslaColumn = DB::getSchemaBuilder()->hasColumn('project_transactions', 'vsla_account_id');
if ($hasVslaColumn) {
    $vslaTransCount = DB::table('project_transactions')->whereNotNull('vsla_account_id')->count();
    DB::table('project_transactions')->whereNotNull('vsla_account_id')->delete();
    echo "✓ Deleted $vslaTransCount VSLA transactions\n";
} else {
    echo "✓ No vsla_account_id column in project_transactions\n";
}

// Clear project shares for VSLA cycles
if (!empty($vslaCycleIds)) {
    $vslaSharesCount = DB::table('project_shares')->whereIn('project_id', $vslaCycleIds)->count();
    DB::table('project_shares')->whereIn('project_id', $vslaCycleIds)->delete();
    echo "✓ Deleted $vslaSharesCount VSLA project shares\n";
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "\n✅ All VSLA-related data truncated successfully!\n";
