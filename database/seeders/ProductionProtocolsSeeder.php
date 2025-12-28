<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionProtocolsSeeder extends Seeder
{
    public function run()
    {
        // Get enterprise IDs
        $poultryId = DB::table('enterprises')->where('name', 'Poultry Farming')->value('id');
        $maizeId = DB::table('enterprises')->where('name', 'Maize Cultivation')->value('id');
        $fishId = DB::table('enterprises')->where('name', 'Fish Farming')->value('id');

        $protocols = [
            // Poultry Protocols (8 weeks)
            [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Prepare Brooding House',
                'activity_description' => 'Clean, disinfect and prepare brooding area for chicks',
                'start_time' => '0',
                'end_time' => '1',
                'is_compulsory' => true,
                'order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Receive Day-Old Chicks',
                'activity_description' => 'Receive chicks from hatchery and ensure proper temperature',
                'start_time' => '1',
                'end_time' => '2',
                'is_compulsory' => true,
                'order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $poultryId,
                'activity_name' => 'First Vaccination',
                'activity_description' => 'Administer Newcastle disease vaccine',
                'start_time' => '2',
                'end_time' => '3',
                'is_compulsory' => true,
                'order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Monitor Growth',
                'activity_description' => 'Weigh birds and check for abnormal growth',
                'start_time' => '4',
                'end_time' => '5',
                'is_compulsory' => false,
                'order' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Second Vaccination',
                'activity_description' => 'Administer Gumboro vaccine',
                'start_time' => '5',
                'end_time' => '6',
                'is_compulsory' => true,
                'order' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $poultryId,
                'activity_name' => 'Harvest/Market',
                'activity_description' => 'Sell birds at optimal weight',
                'start_time' => '8',
                'end_time' => '8',
                'is_compulsory' => true,
                'order' => 6,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Maize Protocols (16 weeks)
            [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Land Preparation',
                'activity_description' => 'Plow and harrow the field',
                'start_time' => '0',
                'end_time' => '1',
                'is_compulsory' => true,
                'order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Planting',
                'activity_description' => 'Plant maize seeds at proper spacing',
                'start_time' => '1',
                'end_time' => '2',
                'is_compulsory' => true,
                'order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $maizeId,
                'activity_name' => 'First Weeding',
                'activity_description' => 'Remove weeds from the field',
                'start_time' => '3',
                'end_time' => '4',
                'is_compulsory' => true,
                'order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Apply Fertilizer',
                'activity_description' => 'Top dress with NPK fertilizer',
                'start_time' => '4',
                'end_time' => '5',
                'is_compulsory' => true,
                'order' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Second Weeding',
                'activity_description' => 'Final weeding before maturity',
                'start_time' => '6',
                'end_time' => '7',
                'is_compulsory' => false,
                'order' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $maizeId,
                'activity_name' => 'Harvest',
                'activity_description' => 'Harvest mature maize cobs',
                'start_time' => '16',
                'end_time' => '16',
                'is_compulsory' => true,
                'order' => 6,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Fish Farming Protocols (24 weeks)
            [
                'enterprise_id' => $fishId,
                'activity_name' => 'Pond Preparation',
                'activity_description' => 'Clean and fill pond with water',
                'start_time' => '0',
                'end_time' => '1',
                'is_compulsory' => true,
                'order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $fishId,
                'activity_name' => 'Stock Fingerlings',
                'activity_description' => 'Introduce fish fingerlings to pond',
                'start_time' => '1',
                'end_time' => '2',
                'is_compulsory' => true,
                'order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $fishId,
                'activity_name' => 'Daily Feeding',
                'activity_description' => 'Feed fish twice daily',
                'start_time' => '2',
                'end_time' => '23',
                'is_compulsory' => true,
                'order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $fishId,
                'activity_name' => 'Water Quality Check',
                'activity_description' => 'Test pH and oxygen levels',
                'start_time' => '4',
                'end_time' => '5',
                'is_compulsory' => false,
                'order' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $fishId,
                'activity_name' => 'Harvest',
                'activity_description' => 'Harvest mature fish',
                'start_time' => '24',
                'end_time' => '24',
                'is_compulsory' => true,
                'order' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('production_protocols')->insert($protocols);
    }
}
