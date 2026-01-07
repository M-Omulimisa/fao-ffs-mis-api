<?php
/**
 * Copy Locations from Omulimisa to FAO FFS MIS Database
 * This script copies all location records from mulimisa.locations to fao_ffs_mis.locations
 * It handles the UUID to INT conversion and maintains parent-child relationships
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "LOCATION DATA MIGRATION\n";
echo "From: mulimisa.locations (UUID-based)\n";
echo "To: fao_ffs_mis.locations (INT-based)\n";
echo "========================================\n\n";

try {
    // Step 1: Count source records
    $sourceCount = DB::connection('mysql')->select("SELECT COUNT(*) as count FROM mulimisa.locations")[0]->count;
    echo "📊 Found {$sourceCount} locations in Omulimisa database\n\n";

    if ($sourceCount == 0) {
        echo "❌ No locations found in source database. Exiting.\n";
        exit(1);
    }

    // Step 2: Backup existing FAO locations
    $faoCount = DB::table('locations')->count();
    echo "📊 Current FAO database has {$faoCount} locations\n";

    if ($faoCount > 0) {
        echo "⚠️  Backing up existing FAO locations...\n";
        DB::statement("DROP TABLE IF EXISTS locations_backup_" . date('Ymd_His'));
        DB::statement("CREATE TABLE locations_backup_" . date('Ymd_His') . " AS SELECT * FROM locations");
        echo "✅ Backup created: locations_backup_" . date('Ymd_His') . "\n\n";
    }

    // Step 3: Truncate FAO locations table
    echo "🗑️  Clearing FAO locations table...\n";
    DB::table('locations')->delete();
    DB::statement("ALTER TABLE locations AUTO_INCREMENT = 1");
    echo "✅ FAO locations table cleared\n\n";

    // Step 4: Fetch all locations from Omulimisa
    echo "📥 Fetching all locations from Omulimisa...\n";
    $omulimisaLocations = DB::connection('mysql')->select("
        SELECT 
            id,
            country_id,
            name,
            parent_id,
            longitude,
            latitude,
            created_at,
            updated_at
        FROM mulimisa.locations
        ORDER BY created_at ASC
    ");
    echo "✅ Fetched {$sourceCount} locations\n\n";

    // Step 5: Create UUID to INT mapping
    echo "🔄 Creating UUID to INT ID mapping...\n";
    $uuidToIntMap = [];
    $intId = 1;

    foreach ($omulimisaLocations as $location) {
        $uuidToIntMap[$location->id] = $intId++;
    }
    echo "✅ Mapping created for {$sourceCount} locations\n\n";

    // Step 6: Insert locations with transformed IDs
    echo "💾 Inserting locations into FAO database...\n";
    $inserted = 0;
    $errors = [];

    DB::beginTransaction();

    foreach ($omulimisaLocations as $location) {
        try {
            $newId = $uuidToIntMap[$location->id];
            $newParentId = null;

            // Map parent_id if it exists
            if ($location->parent_id !== null && isset($uuidToIntMap[$location->parent_id])) {
                $newParentId = $uuidToIntMap[$location->parent_id];
            }

            // Determine type based on parent hierarchy
            $type = 'Country';
            if ($newParentId !== null) {
                // Check if parent is a region (has parent_id = null in original)
                $parentOriginal = collect($omulimisaLocations)->firstWhere('id', $location->parent_id);
                if ($parentOriginal) {
                    if ($parentOriginal->parent_id === null) {
                        $type = 'District'; // Child of region = district
                    } else {
                        // Check grandparent
                        $grandparentOriginal = collect($omulimisaLocations)->firstWhere('id', $parentOriginal->parent_id);
                        if ($grandparentOriginal && $grandparentOriginal->parent_id === null) {
                            $type = 'Sub-county'; // Child of district = sub-county
                        } else {
                            $type = 'Parish'; // Child of sub-county = parish
                        }
                    }
                }
            } elseif (strpos(strtolower($location->name), 'region') !== false) {
                $type = 'Region';
            }

            DB::table('locations')->insert([
                'id' => $newId,
                'name' => $location->name,
                'parent' => $newParentId ?? 0,
                'photo' => null,
                'detail' => "Migrated from Omulimisa (UUID: {$location->id})",
                'order' => $newId,
                'code' => null,
                'locked_down' => 0,
                'type' => $type,
                'processed' => 'Yes',
                'farm_count' => 0,
                'cattle_count' => 0,
                'goat_count' => 0,
                'sheep_count' => 0,
                'created_at' => $location->created_at,
                'updated_at' => $location->updated_at,
            ]);

            $inserted++;

            if ($inserted % 100 == 0) {
                echo "   ✓ Inserted {$inserted} locations...\n";
            }
        } catch (\Exception $e) {
            $errors[] = "Error inserting location '{$location->name}': " . $e->getMessage();
        }
    }

    DB::commit();

    echo "✅ Successfully inserted {$inserted} locations\n\n";

    // Step 7: Reset AUTO_INCREMENT to next available ID
    $maxId = DB::table('locations')->max('id');
    $nextId = $maxId + 1;
    DB::statement("ALTER TABLE locations AUTO_INCREMENT = {$nextId}");
    echo "✅ AUTO_INCREMENT reset to {$nextId}\n\n";

    // Step 8: Display statistics
    echo "========================================\n";
    echo "MIGRATION STATISTICS\n";
    echo "========================================\n";
    echo "Source Records: {$sourceCount}\n";
    echo "Inserted: {$inserted}\n";
    echo "Errors: " . count($errors) . "\n";

    if (count($errors) > 0) {
        echo "\n⚠️  ERRORS:\n";
        foreach ($errors as $error) {
            echo "   - {$error}\n";
        }
    }

    // Display sample locations
    echo "\n========================================\n";
    echo "SAMPLE LOCATIONS (First 10)\n";
    echo "========================================\n";

    $samples = DB::table('locations')->limit(10)->get();
    foreach ($samples as $sample) {
        $parentName = 'None';
        if ($sample->parent > 0) {
            $parent = DB::table('locations')->where('id', $sample->parent)->first();
            $parentName = $parent ? $parent->name : 'Unknown';
        }
        echo sprintf("ID: %d | Name: %s | Parent: %s | Type: %s\n", 
            $sample->id, 
            $sample->name, 
            $parentName,
            $sample->type
        );
    }

    // Display type breakdown
    echo "\n========================================\n";
    echo "LOCATION TYPE BREAKDOWN\n";
    echo "========================================\n";

    $typeBreakdown = DB::table('locations')
        ->select('type', DB::raw('COUNT(*) as count'))
        ->groupBy('type')
        ->get();

    foreach ($typeBreakdown as $type) {
        echo sprintf("%s: %d\n", $type->type, $type->count);
    }

    echo "\n========================================\n";
    echo "✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n\n";

    // Create UUID mapping file for reference
    $mappingFile = __DIR__ . '/storage/location_uuid_to_int_mapping_' . date('Ymd_His') . '.json';
    file_put_contents($mappingFile, json_encode($uuidToIntMap, JSON_PRETTY_PRINT));
    echo "📄 UUID to INT mapping saved to: {$mappingFile}\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ MIGRATION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}
