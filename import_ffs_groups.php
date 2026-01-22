<?php

/**
 * FFS Groups CSV Import Script
 * 
 * Imports group data from CSV files into the ffs_groups table
 * 
 * CSV Fields Mapping:
 * - ID → original_id
 * - LoA → loa
 * - IPName → ip_name
 * - Project → project_code
 * - District → district_text, district_id (lookup)
 * - Subcounty → subcounty_text
 * - Parish → parish_text
 * - Village → village
 * - FFS Name → name
 * - GroupStatus → status (Old→Active, New→Active)
 * - Formation → establishment_date
 * - GPS_Eastings → longitude
 * - GPS_Northings → latitude
 * - Facilitator → contact_person_name
 * - Contact → contact_person_phone
 * - Sex → facilitator_sex
 * - MaleMembers → male_members
 * - FemaleMembers → female_members
 * - PWD Males → pwd_male_members
 * - PWD Females → pwd_female_members
 * - TotalMembership → total_members
 * - VC_Enterprise_Activity1 → primary_value_chain
 * - VC_Enterprise_Activity2-4 → secondary_value_chains (JSON)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FfsGroup;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

echo "==============================================\n";
echo "FFS GROUPS CSV IMPORT SCRIPT\n";
echo "==============================================\n\n";

// Configuration
$csvFiles = [
    'datasets/1. UNJP-UGA-068-FFS Data KADP.csv',
    'datasets/2. UNJP-UGA-068- FFS Data ECO ).csv',
    'datasets/3. UNJP-UGA-068- FFS Data GARD.csv',
];

$stats = [
    'total_rows' => 0,
    'imported' => 0,
    'skipped' => 0,
    'errors' => [],
    'files_processed' => 0,
];

// District cache for lookups
$districtCache = [];

/**
 * Parse CSV file and return array of rows
 */
function parseCSV($filePath) {
    $rows = [];
    if (!file_exists($filePath)) {
        echo "   ⚠ File not found: $filePath\n";
        return $rows;
    }
    
    $handle = fopen($filePath, 'r');
    $headers = fgetcsv($handle);
    
    // Clean headers
    $headers = array_map(function($h) {
        return trim($h);
    }, $headers);
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) >= count($headers)) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($data[$i]) ? trim($data[$i]) : '';
            }
            $rows[] = $row;
        }
    }
    fclose($handle);
    
    return $rows;
}

/**
 * Look up district ID by name
 */
function getDistrictId($districtName, &$cache) {
    if (empty($districtName)) return null;
    
    $districtName = strtoupper(trim($districtName));
    
    if (isset($cache[$districtName])) {
        return $cache[$districtName];
    }
    
    // Try to find district in locations table
    $district = DB::table('locations')
        ->whereRaw('UPPER(name) = ?', [$districtName])
        ->where('type', 'District')
        ->first();
    
    if ($district) {
        $cache[$districtName] = $district->id;
        return $district->id;
    }
    
    // Try with LIKE
    $district = DB::table('locations')
        ->whereRaw('UPPER(name) LIKE ?', ['%' . $districtName . '%'])
        ->where('type', 'District')
        ->first();
        
    if ($district) {
        $cache[$districtName] = $district->id;
        return $district->id;
    }
    
    $cache[$districtName] = null;
    return null;
}

/**
 * Map CSV status to database status
 */
function mapStatus($csvStatus) {
    $status = strtolower(trim($csvStatus));
    switch ($status) {
        case 'old':
        case 'new':
        case 'active':
            return 'Active';
        case 'inactive':
            return 'Inactive';
        case 'graduated':
            return 'Graduated';
        case 'suspended':
            return 'Suspended';
        default:
            return 'Active';
    }
}

/**
 * Parse year to date
 */
function parseFormationDate($year) {
    if (empty($year)) return null;
    $year = intval($year);
    if ($year >= 2000 && $year <= 2030) {
        return "$year-01-01";
    }
    return null;
}

/**
 * Clean numeric value
 */
function cleanNumeric($value) {
    if (empty($value)) return 0;
    return intval(preg_replace('/[^0-9]/', '', $value));
}

/**
 * Clean coordinate value
 */
function cleanCoordinate($value) {
    if (empty($value)) return null;
    $clean = preg_replace('/[^0-9.\-]/', '', $value);
    if (is_numeric($clean)) {
        return floatval($clean);
    }
    return null;
}

/**
 * Build secondary value chains JSON
 */
function buildSecondaryValueChains($row) {
    $chains = [];
    $fields = ['VC_Enterprise_Activity2', 'VC_Enterprise_Activity3', 'VC_Enterprise_Act 4'];
    
    foreach ($fields as $field) {
        if (!empty($row[$field])) {
            $chains[] = trim($row[$field]);
        }
    }
    
    return !empty($chains) ? json_encode($chains) : null;
}

// Step 1: Truncate existing table
echo "Step 1: Truncating existing ffs_groups table...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::table('ffs_groups')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "   ✓ Table truncated successfully\n\n";
} catch (\Exception $e) {
    echo "   ⚠ Could not truncate (may have foreign key constraints): " . $e->getMessage() . "\n";
    echo "   Proceeding with delete instead...\n";
    DB::table('ffs_groups')->delete();
    echo "   ✓ All records deleted\n\n";
}

// Step 2: Process each CSV file
echo "Step 2: Processing CSV files...\n\n";

foreach ($csvFiles as $csvFile) {
    $fullPath = __DIR__ . '/' . $csvFile;
    $fileName = basename($csvFile);
    
    echo "Processing: $fileName\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!file_exists($fullPath)) {
        echo "   ⚠ File not found, skipping\n\n";
        continue;
    }
    
    $rows = parseCSV($fullPath);
    $fileImported = 0;
    $fileSkipped = 0;
    
    echo "   Found " . count($rows) . " rows\n";
    
    foreach ($rows as $index => $row) {
        $stats['total_rows']++;
        
        // Skip empty rows
        if (empty($row['FFS Name']) && empty($row['Village'])) {
            $fileSkipped++;
            $stats['skipped']++;
            continue;
        }
        
        try {
            // Build group name
            $name = !empty($row['FFS Name']) ? trim($row['FFS Name']) : 'Unnamed Group';
            
            // Check for duplicate names (shouldn't happen after truncate, but just in case)
            $existingCount = FfsGroup::where('name', $name)->count();
            if ($existingCount > 0) {
                $name = $name . ' (' . ($existingCount + 1) . ')';
            }
            
            // Get district ID
            $districtId = getDistrictId($row['District'] ?? '', $districtCache);
            
            // Build the record
            $groupData = [
                'name' => $name,
                'type' => 'FFS',
                'code' => null,
                
                // New import fields
                'original_id' => cleanNumeric($row['ID'] ?? ''),
                'loa' => !empty($row['LoA']) ? trim($row['LoA']) : null,
                'ip_name' => !empty($row['IPName']) ? trim($row['IPName']) : null,
                'project_code' => !empty($row['Project']) ? trim($row['Project']) : null,
                'source_file' => $fileName,
                
                // Location
                'district_id' => $districtId,
                'district_text' => !empty($row['District']) ? trim($row['District']) : null,
                'subcounty_text' => !empty($row['Subcounty']) ? trim($row['Subcounty']) : null,
                'parish_text' => !empty($row['Parish']) ? trim($row['Parish']) : null,
                'village' => !empty($row['Village']) ? trim($row['Village']) : null,
                
                // GPS
                'latitude' => cleanCoordinate($row['GPS_Northings'] ?? ''),
                'longitude' => cleanCoordinate($row['GPS_Eastings'] ?? ''),
                
                // Status and dates
                'status' => mapStatus($row['GroupStatus'] ?? 'Active'),
                'establishment_date' => parseFormationDate($row['Formation'] ?? ''),
                
                // Contact
                'contact_person_name' => !empty($row['Facilitator']) ? trim($row['Facilitator']) : null,
                'contact_person_phone' => !empty($row['Contact']) ? trim($row['Contact']) : null,
                'facilitator_sex' => !empty($row['Sex']) ? trim($row['Sex']) : null,
                
                // Membership
                'male_members' => cleanNumeric($row['MaleMembers'] ?? ''),
                'female_members' => cleanNumeric($row['FemaleMembers'] ?? ''),
                'pwd_male_members' => cleanNumeric($row['PWD Males'] ?? ''),
                'pwd_female_members' => cleanNumeric($row['PWD Females'] ?? ''),
                'total_members' => cleanNumeric($row['TotalMembership'] ?? ''),
                'pwd_members' => cleanNumeric($row['PWD Males'] ?? '') + cleanNumeric($row['PWD Females'] ?? ''),
                
                // Value chains
                'primary_value_chain' => !empty($row['VC_Enterprise_Activity1']) ? trim($row['VC_Enterprise_Activity1']) : null,
                'secondary_value_chains' => buildSecondaryValueChains($row),
                
                // Defaults
                'meeting_frequency' => 'Weekly',
                'cycle_number' => 1,
                
                // Timestamps
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Insert the record
            FfsGroup::create($groupData);
            
            $fileImported++;
            $stats['imported']++;
            
        } catch (\Exception $e) {
            $stats['errors'][] = [
                'file' => $fileName,
                'row' => $index + 2, // +2 for header and 0-index
                'name' => $row['FFS Name'] ?? 'Unknown',
                'error' => $e->getMessage(),
            ];
            $fileSkipped++;
            $stats['skipped']++;
        }
    }
    
    echo "   ✓ Imported: $fileImported\n";
    echo "   ⚠ Skipped: $fileSkipped\n\n";
    $stats['files_processed']++;
}

// Step 3: Generate Summary
echo "==============================================\n";
echo "IMPORT SUMMARY\n";
echo "==============================================\n\n";

echo "Files Processed: " . $stats['files_processed'] . "\n";
echo "Total Rows: " . $stats['total_rows'] . "\n";
echo "Successfully Imported: " . $stats['imported'] . "\n";
echo "Skipped/Failed: " . $stats['skipped'] . "\n";

if (!empty($stats['errors'])) {
    echo "\nErrors (" . count($stats['errors']) . "):\n";
    foreach (array_slice($stats['errors'], 0, 10) as $error) {
        echo "  - {$error['file']} Row {$error['row']}: {$error['name']} - {$error['error']}\n";
    }
    if (count($stats['errors']) > 10) {
        echo "  ... and " . (count($stats['errors']) - 10) . " more errors\n";
    }
}

// Step 4: Verify import
echo "\nVerification:\n";
$totalGroups = FfsGroup::count();
$groupsByIp = FfsGroup::selectRaw('ip_name, COUNT(*) as count')
    ->groupBy('ip_name')
    ->get();

echo "  Total groups in database: $totalGroups\n";
echo "  Groups by Implementing Partner:\n";
foreach ($groupsByIp as $ip) {
    $ipName = $ip->ip_name ?: 'Unknown';
    echo "    - $ipName: {$ip->count}\n";
}

$groupsByStatus = FfsGroup::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();
echo "  Groups by Status:\n";
foreach ($groupsByStatus as $status) {
    echo "    - {$status->status}: {$status->count}\n";
}

echo "\n==============================================\n";
echo "IMPORT COMPLETE!\n";
echo "==============================================\n";
