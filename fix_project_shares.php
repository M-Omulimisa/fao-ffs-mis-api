<?php

/**
 * Fix Project Total Shares
 * Updates the total_shares field for existing projects
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "FIXING PROJECT TOTAL SHARES\n";
echo "========================================\n\n";

$updates = [
    'Medicine Distribution Partnership' => 20,
    'Farm-to-Profit Initiative' => 20,
    'Property Wealth Builder' => 50000,
    'Motorcycle Taxi Fleet' => 200,
];

DB::beginTransaction();

try {
    $updatedCount = 0;
    
    foreach ($updates as $title => $totalShares) {
        $project = Project::where('title', $title)->first();
        
        if ($project) {
            $oldShares = $project->total_shares;
            $project->total_shares = $totalShares;
            $project->save();
            
            echo "✓ Updated: {$project->title}\n";
            echo "  Old: {$oldShares} shares → New: {$totalShares} shares\n";
            echo "  Status: {$project->status}\n";
            echo "  Share Price: UGX " . number_format($project->share_price, 0) . "\n\n";
            
            $updatedCount++;
        } else {
            echo "⚠ Not found: {$title}\n\n";
        }
    }
    
    DB::commit();
    
    echo "========================================\n";
    echo "✓ Updated {$updatedCount} projects\n";
    echo "========================================\n\n";
    
    // Verify results
    echo "Current Projects in Database:\n";
    echo "----------------------------------------\n";
    $projects = Project::all(['id', 'title', 'status', 'total_shares', 'shares_sold']);
    foreach ($projects as $p) {
        $available = $p->total_shares - $p->shares_sold;
        echo "{$p->id}. {$p->title}\n";
        echo "   Status: {$p->status} | Shares: {$p->shares_sold}/{$p->total_shares} (Available: {$available})\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "✅ Done!\n";
