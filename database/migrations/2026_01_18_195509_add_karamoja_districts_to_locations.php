<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddKaramojaDistrictsToLocations extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds Karamoja region districts from Northern Uganda
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('locations')) {
            return;
        }

        $columns = Schema::getColumnListing('locations');
        if (!in_array('name', $columns, true) || !in_array('type', $columns, true)) {
            return;
        }

        $parentColumn = in_array('parent', $columns, true)
            ? 'parent'
            : (in_array('parent_id', $columns, true) ? 'parent_id' : null);

        // First, create Karamoja Region if it doesn't exist
        $karamojaRegion = DB::table('locations')->where('name', 'Karamoja Region')->first();
        
        if (!$karamojaRegion) {
            $regionData = [
                'name' => 'Karamoja Region',
                'type' => 'Region',
            ];
            if ($parentColumn !== null) {
                $regionData[$parentColumn] = 0;
            }
            if (in_array('code', $columns, true)) {
                $regionData['code'] = 'KAR';
            }
            if (in_array('locked_down', $columns, true)) {
                $regionData['locked_down'] = 0;
            }
            if (in_array('order', $columns, true)) {
                $regionData['order'] = 0;
            }
            if (in_array('processed', $columns, true)) {
                $regionData['processed'] = 'No';
            }
            if (in_array('created_at', $columns, true)) {
                $regionData['created_at'] = now();
            }
            if (in_array('updated_at', $columns, true)) {
                $regionData['updated_at'] = now();
            }

            try {
                DB::table('locations')->insert($regionData);
                $karamojaRegionId = DB::getPdo()->lastInsertId();
            } catch (\Throwable $e) {
                // Some environments have stricter enum values for locations.type.
                // Skip this optional seed migration rather than breaking test bootstrap.
                return;
            }
        } else {
            $karamojaRegionId = $karamojaRegion->id;
        }
        
        // Karamoja Region Districts
        $karamojaDistricts = [
            ['name' => 'Kaabong District', 'code' => 'KAB'],
            ['name' => 'Abim District', 'code' => 'ABI'],
            ['name' => 'Kotido District', 'code' => 'KOT'],
            ['name' => 'Moroto District', 'code' => 'MOR'],
            ['name' => 'Napak District', 'code' => 'NAP'],
            ['name' => 'Nakapiripirit District', 'code' => 'NAK'],
            ['name' => 'Amudat District', 'code' => 'AMU'],
            ['name' => 'Nabilatuk District', 'code' => 'NAB'],
            ['name' => 'Karenga District', 'code' => 'KAR'],
        ];
        
        foreach ($karamojaDistricts as $district) {
            // Check if district already exists
            $exists = DB::table('locations')
                ->where('name', $district['name'])
                ->exists();
            
            if (!$exists) {
                $districtData = [
                    'name' => $district['name'],
                    'type' => 'District',
                ];
                if ($parentColumn !== null) {
                    $districtData[$parentColumn] = $karamojaRegionId;
                }
                if (in_array('code', $columns, true)) {
                    $districtData['code'] = $district['code'];
                }
                if (in_array('locked_down', $columns, true)) {
                    $districtData['locked_down'] = 0;
                }
                if (in_array('order', $columns, true)) {
                    $districtData['order'] = 0;
                }
                if (in_array('processed', $columns, true)) {
                    $districtData['processed'] = 'No';
                }
                if (in_array('created_at', $columns, true)) {
                    $districtData['created_at'] = now();
                }
                if (in_array('updated_at', $columns, true)) {
                    $districtData['updated_at'] = now();
                }

                try {
                    DB::table('locations')->insert($districtData);
                } catch (\Throwable $e) {
                    // Optional seed data only; continue to keep migrations resilient.
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('locations')) {
            return;
        }

        $columns = Schema::getColumnListing('locations');
        $parentColumn = in_array('parent', $columns, true)
            ? 'parent'
            : (in_array('parent_id', $columns, true) ? 'parent_id' : null);

        // Remove Karamoja districts
        $karamojaDistricts = [
            'Kaabong District',
            'Abim District',
            'Kotido District',
            'Moroto District',
            'Napak District',
            'Nakapiripirit District',
            'Amudat District',
            'Nabilatuk District',
            'Karenga District',
        ];
        
        DB::table('locations')
            ->whereIn('name', $karamojaDistricts)
            ->delete();
        
        // Optionally remove Karamoja Region if no other children
        $region = DB::table('locations')->where('name', 'Karamoja Region')->first();
        if ($region && $parentColumn !== null) {
            $hasChildren = DB::table('locations')->where($parentColumn, $region->id)->exists();
            if (!$hasChildren) {
                DB::table('locations')->where('id', $region->id)->delete();
            }
        }
    }
}
