<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class ProductCategoryReorganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing categories to start fresh
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Define the organized category structure
        $categories = [
            // Main Category 1: Mobile Phones
            [
                'category' => 'Mobile Phones',
                'icon' => 'bi-phone',
                'is_parent' => 'Yes',
                'show_in_categories' => 'Yes',
                'show_in_banner' => 'Yes',
                'image' => 'mobile-phones.jpg',
                'subcategories' => [
                    [
                        'category' => 'Smartphones',
                        'icon' => 'bi-phone-fill',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Feature Phones',
                        'icon' => 'bi-phone-landscape',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Refurbished Phones',
                        'icon' => 'bi-arrow-clockwise',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Gaming Phones',
                        'icon' => 'bi-controller',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Rugged Phones',
                        'icon' => 'bi-shield-fill',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ]
                ]
            ],

            // Main Category 2: Accessories
            [
                'category' => 'Accessories',
                'icon' => 'bi-bag',
                'is_parent' => 'Yes',
                'show_in_categories' => 'Yes',
                'show_in_banner' => 'Yes',
                'image' => 'accessories.jpg',
                'subcategories' => [
                    [
                        'category' => 'Phone Cases & Covers',
                        'icon' => 'bi-phone-flip',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Screen Protectors',
                        'icon' => 'bi-shield-check',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Chargers & Cables',
                        'icon' => 'bi-lightning-charge',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Power Banks',
                        'icon' => 'bi-battery-charging',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Wireless Chargers',
                        'icon' => 'bi-wireless',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ]
                ]
            ],

            // Main Category 3: Electronics & Gadgets
            [
                'category' => 'Electronics & Gadgets',
                'icon' => 'bi-cpu',
                'is_parent' => 'Yes',
                'show_in_categories' => 'Yes',
                'show_in_banner' => 'Yes',
                'image' => 'electronics-gadgets.jpg',
                'subcategories' => [
                    [
                        'category' => 'Headphones & Earbuds',
                        'icon' => 'bi-headphones',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Bluetooth Speakers',
                        'icon' => 'bi-speaker',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Smart Watches',
                        'icon' => 'bi-watch',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Tablets & iPads',
                        'icon' => 'bi-tablet',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ],
                    [
                        'category' => 'Smart Home Devices',
                        'icon' => 'bi-house-gear',
                        'show_in_categories' => 'Yes',
                        'show_in_banner' => 'Yes',
                    ]
                ]
            ]
        ];

        // Create the categories
        foreach ($categories as $categoryData) {
            // Create main category
            $subcategories = $categoryData['subcategories'];
            unset($categoryData['subcategories']);
            
            $mainCategory = ProductCategory::create([
                'category' => $categoryData['category'],
                'icon' => $categoryData['icon'],
                'is_parent' => $categoryData['is_parent'],
                'parent_id' => null,
                'show_in_categories' => $categoryData['show_in_categories'],
                'show_in_banner' => $categoryData['show_in_banner'],
                'image' => $categoryData['image'],
                'banner_image' => null,
                'is_first_banner' => 'No',
                'first_banner_image' => null,
            ]);

            // Create subcategories
            foreach ($subcategories as $subcategoryData) {
                ProductCategory::create([
                    'category' => $subcategoryData['category'],
                    'icon' => $subcategoryData['icon'],
                    'is_parent' => 'No',
                    'parent_id' => $mainCategory->id,
                    'show_in_categories' => $subcategoryData['show_in_categories'],
                    'show_in_banner' => $subcategoryData['show_in_banner'],
                    'image' => 'blank.png',
                    'banner_image' => null,
                    'is_first_banner' => 'No',
                    'first_banner_image' => null,
                ]);
            }
        }

        $this->command->info('Product categories have been successfully reorganized!');
        $this->command->info('Created 3 main categories with 5 subcategories each (15 subcategories total)');
        $this->command->info('All categories are set to show in mega menu (show_in_categories = Yes)');
    }
}
