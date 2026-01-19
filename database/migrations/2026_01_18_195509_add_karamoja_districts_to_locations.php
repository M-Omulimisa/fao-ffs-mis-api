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
        // First, create Karamoja Region if it doesn't exist
        $karamojaRegion = DB::table('locations')->where('name', 'Karamoja Region')->first();
        
        if (!$karamojaRegion) {
            DB::table('locations')->insert([
                'name' => 'Karamoja Region',
                'type' => 'Region',
                'parent' => 0,
                'code' => 'KAR',
                'locked_down' => 0,
                'order' => 0,
                'processed' => 'No',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $karamojaRegionId = DB::getPdo()->lastInsertId();
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
                DB::table('locations')->insert([
                    'name' => $district['name'],
                    'type' => 'District',
                    'parent' => $karamojaRegionId,
                    'code' => $district['code'],
                    'locked_down' => 0,
                    'order' => 0,
                    'processed' => 'No',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
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
        if ($region) {
            $hasChildren = DB::table('locations')->where('parent', $region->id)->exists();
            if (!$hasChildren) {
                DB::table('locations')->where('id', $region->id)->delete();
            }
        }
    }
}
