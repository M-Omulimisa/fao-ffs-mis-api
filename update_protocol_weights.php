<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductionProtocol;

echo "🔍 ANALYZING PROTOCOL WEIGHTS FROM CONTENT" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL . PHP_EOL;

$protocols = ProductionProtocol::all();
$updated = 0;
$totalWeight = 0;

foreach ($protocols as $protocol) {
    // Extract weight from (+), (++), (+++), (++++), (+++++), etc.
    $content = $protocol->activity_description;
    
    // Find all occurrences of (+), (++), (+++), etc.
    preg_match_all('/\((\+{1,5})\)/', $content, $matches);
    
    if (!empty($matches[1])) {
        // Get the maximum number of + signs found (represents highest importance)
        $maxPlusCount = 0;
        foreach ($matches[1] as $plusString) {
            $count = strlen($plusString);
            if ($count > $maxPlusCount) {
                $maxPlusCount = $count;
            }
        }
        
        // Calculate average weight (could also use max or other logic)
        $totalPlus = 0;
        $occurrences = count($matches[1]);
        foreach ($matches[1] as $plusString) {
            $totalPlus += strlen($plusString);
        }
        
        // Use average rounded, but minimum 1
        $averageWeight = max(1, round($totalPlus / $occurrences));
        
        // Update the protocol weight
        $oldWeight = $protocol->weight;
        $protocol->weight = $averageWeight;
        $protocol->save();
        
        $updated++;
        $totalWeight += $averageWeight;
        
        echo "✓ Protocol #{$protocol->id}: {$protocol->activity_name}" . PHP_EOL;
        echo "  Enterprise: {$protocol->enterprise->name}" . PHP_EOL;
        echo "  Found {$occurrences} weight indicators" . PHP_EOL;
        echo "  Weight: {$oldWeight} → {$averageWeight}" . PHP_EOL;
        echo "  Max importance: " . str_repeat('+', $maxPlusCount) . PHP_EOL;
        echo PHP_EOL;
    } else {
        // No weight indicators found, keep default weight 1
        echo "ℹ Protocol #{$protocol->id}: {$protocol->activity_name}" . PHP_EOL;
        echo "  Enterprise: {$protocol->enterprise->name}" . PHP_EOL;
        echo "  No weight indicators found - keeping default weight: {$protocol->weight}" . PHP_EOL;
        echo PHP_EOL;
    }
}

echo str_repeat("=", 70) . PHP_EOL;
echo "✅ WEIGHT UPDATE COMPLETE!" . PHP_EOL;
echo str_repeat("=", 70) . PHP_EOL;
echo "Total Protocols Analyzed: " . $protocols->count() . PHP_EOL;
echo "Protocols Updated: {$updated}" . PHP_EOL;
echo "Protocols with Default Weight: " . ($protocols->count() - $updated) . PHP_EOL;
if ($updated > 0) {
    echo "Average Weight: " . round($totalWeight / $updated, 2) . PHP_EOL;
}
echo str_repeat("=", 70) . PHP_EOL;

// Show weight distribution
echo PHP_EOL . "WEIGHT DISTRIBUTION:" . PHP_EOL;
echo str_repeat("-", 70) . PHP_EOL;
$weightGroups = ProductionProtocol::selectRaw('weight, COUNT(*) as count')
    ->groupBy('weight')
    ->orderBy('weight')
    ->get();

foreach ($weightGroups as $group) {
    $bar = str_repeat('█', $group->count);
    echo "Weight {$group->weight}: {$bar} ({$group->count} protocols)" . PHP_EOL;
}
echo str_repeat("=", 70) . PHP_EOL;
