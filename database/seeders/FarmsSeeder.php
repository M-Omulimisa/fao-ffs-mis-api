<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FarmsSeeder extends Seeder
{
    public function run()
    {
        // Get first user
        $user = User::first();
        if (!$user) {
            $this->command->warn('No users found. Skipping farms seeding.');
            return;
        }

        // Get enterprises
        $poultryId = DB::table('enterprises')->where('name', 'Poultry Farming')->value('id');
        $maizeId = DB::table('enterprises')->where('name', 'Maize Cultivation')->value('id');
        $fishId = DB::table('enterprises')->where('name', 'Fish Farming')->value('id');

        // Create farms
        $farms = [
            [
                'enterprise_id' => $poultryId,
                'user_id' => $user->id,
                'name' => 'Kampala Broiler Farm',
                'description' => 'Commercial broiler chicken farm with 500 birds capacity',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(30),
                'expected_end_date' => Carbon::now()->addDays(26),
                'gps_latitude' => 0.347596,
                'gps_longitude' => 32.582520,
                'location_text' => 'Kampala, Uganda',
                'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800',
                'overall_score' => 0,
                'completed_activities_count' => 0,
                'total_activities_count' => 0,
                'is_active' => true,
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $maizeId,
                'user_id' => $user->id,
                'name' => 'Masaka Maize Field',
                'description' => '2-acre maize plantation for commercial production',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(45),
                'expected_end_date' => Carbon::now()->addDays(67),
                'gps_latitude' => -0.334700,
                'gps_longitude' => 31.733800,
                'location_text' => 'Masaka, Uganda',
                'photo' => 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=800',
                'overall_score' => 0,
                'completed_activities_count' => 0,
                'total_activities_count' => 0,
                'is_active' => true,
                'created_at' => Carbon::now()->subDays(45),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $fishId,
                'user_id' => $user->id,
                'name' => 'Jinja Fish Pond',
                'description' => 'Tilapia fish farming in 1000sqm pond',
                'status' => 'planning',
                'start_date' => Carbon::now()->addDays(7),
                'expected_end_date' => Carbon::now()->addDays(175),
                'gps_latitude' => 0.449200,
                'gps_longitude' => 33.204800,
                'location_text' => 'Jinja, Uganda',
                'photo' => 'https://images.unsplash.com/photo-1524704654690-b56c05c78a00?w=800',
                'overall_score' => 0,
                'completed_activities_count' => 0,
                'total_activities_count' => 0,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'enterprise_id' => $poultryId,
                'user_id' => $user->id,
                'name' => 'Entebbe Layer Farm',
                'description' => 'Egg production with 300 layer hens',
                'status' => 'completed',
                'start_date' => Carbon::now()->subDays(120),
                'expected_end_date' => Carbon::now()->subDays(64),
                'actual_end_date' => Carbon::now()->subDays(60),
                'gps_latitude' => 0.042800,
                'gps_longitude' => 32.463500,
                'location_text' => 'Entebbe, Uganda',
                'photo' => 'https://images.unsplash.com/photo-1612170153139-6f881ff067e0?w=800',
                'overall_score' => 87.5,
                'completed_activities_count' => 6,
                'total_activities_count' => 6,
                'is_active' => false,
                'created_at' => Carbon::now()->subDays(120),
                'updated_at' => Carbon::now()->subDays(60),
            ],
        ];

        foreach ($farms as $farmData) {
            Farm::create($farmData);
        }

        $this->command->info('Farms seeded successfully with auto-generated activities!');
    }
}
