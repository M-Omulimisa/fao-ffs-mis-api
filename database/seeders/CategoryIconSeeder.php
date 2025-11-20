<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class CategoryIconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define icon mapping based on category names
        $iconMappings = [
            'Phones' => 'bi-phone',
            'Main Category' => 'bi-grid-3x3-gap',
            'Ear Buds & Headsets' => 'bi-headphones',
            'Smart Watches' => 'bi-smartwatch',
            'Security & Surveillance' => 'bi-shield-check',
            'Games & Consoles' => 'bi-controller',
            'Clean Energy' => 'bi-lightning-charge',
            'Electronics & Gadgets' => 'bi-cpu',
            'Speakers & Soundbars' => 'bi-speaker',
            'Gas' => 'bi-fire',
            'Solar' => 'bi-sun',
        ];

        // Update categories with their respective icons
        foreach ($iconMappings as $categoryName => $iconClass) {
            ProductCategory::where('category', $categoryName)
                ->update(['icon' => $iconClass]);
        }

        // Log the results
        $this->command->info('Category icons have been updated successfully!');
        
        // Show the updated categories
        $categories = ProductCategory::whereNotNull('icon')->get(['id', 'category', 'icon', 'is_parent']);
        
        $this->command->table(
            ['ID', 'Category', 'Icon', 'Is Parent'],
            $categories->map(function ($cat) {
                return [$cat->id, $cat->category, $cat->icon, $cat->is_parent];
            })->toArray()
        );
    }
}
