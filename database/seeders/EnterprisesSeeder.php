<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EnterprisesSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        DB::table('production_protocols')->delete();
        DB::table('enterprises')->delete();

        $enterprises = [
            // LIVESTOCK ENTERPRISES
            [
                'name' => 'Poultry Farming (Chickens)',
                'description' => 'Commercial chicken rearing for eggs and meat production. Indigenous and improved breeds for small to medium scale farmers.',
                'type' => 'livestock',
                'duration' => 5, // ~19+ weeks = 5 months
                'photo' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Apiary (Beekeeping)',
                'description' => 'Commercial honey and beeswax production. Beekeeping from colony establishment to productive hive management.',
                'type' => 'livestock',
                'duration' => 15, // 15+ months
                'photo' => 'https://images.unsplash.com/photo-1558642891-54be180ea339?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Cattle Farming',
                'description' => 'Dairy and beef cattle production. From calf rearing to lactating cow management for milk and meat.',
                'type' => 'livestock',
                'duration' => 36, // 36+ months for full cycle
                'photo' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Goat Farming',
                'description' => 'Meat and dairy goat production. Complete management from kid to nursing doe for income generation.',
                'type' => 'livestock',
                'duration' => 15, // 15+ months
                'photo' => 'https://images.unsplash.com/photo-1533318087102-b3ad366ed041?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pig Farming',
                'description' => 'Commercial pig rearing for pork production. Management from piglet to productive sow/boar.',
                'type' => 'livestock',
                'duration' => 15, // 15+ months
                'photo' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Turkey Farming',
                'description' => 'Commercial turkey production for meat and breeding. From poult to mature breeder management.',
                'type' => 'livestock',
                'duration' => 15, // 15+ months
                'photo' => 'https://images.unsplash.com/photo-1574484284002-952d92456975?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Rangeland Management',
                'description' => 'Sustainable pasture and grazing land management. From dormant phase to post-maturity recovery for livestock feeding.',
                'type' => 'livestock',
                'duration' => 15, // 15+ months for full cycle
                'photo' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            // CROP ENTERPRISES
            [
                'name' => 'Bean Cultivation',
                'description' => 'Growing beans for food and commercial purposes. From emergence to maturity with pod development.',
                'type' => 'crop',
                'duration' => 3, // 9-12 weeks = 3 months
                'photo' => 'https://images.unsplash.com/photo-1594806251616-504816d13989?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Maize Cultivation',
                'description' => 'Growing maize for food and commercial purposes. Complete cycle from emergence to grain maturity.',
                'type' => 'crop',
                'duration' => 4, // 13-14 weeks = ~4 months
                'photo' => 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Cabbage Growing',
                'description' => 'Growing cabbage for food and commercial purposes. From seedling to harvest with proper head formation.',
                'type' => 'crop',
                'duration' => 7, // ~28 weeks = 7 months
                'photo' => 'https://images.unsplash.com/photo-1594282539557-4ca4f0e0b6ee?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Greengram Cultivation',
                'description' => 'Growing greengram (mung beans) for food and income. Complete management from planting to harvest.',
                'type' => 'crop',
                'duration' => 6, // ~23 weeks = 6 months
                'photo' => 'https://images.unsplash.com/photo-1566385101042-1a0aa0c1268c?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Groundnut Farming',
                'description' => 'Growing groundnuts (peanuts) for food and oil production. From planting to pod maturity and harvest.',
                'type' => 'crop',
                'duration' => 3, // ~12 weeks = 3 months
                'photo' => 'https://images.unsplash.com/photo-1589927986089-35812378f1e9?w=400',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('enterprises')->insert($enterprises);
    }
}
