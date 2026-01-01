<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarketPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates realistic market price data for Uganda's agricultural sector:
     * - 5 product categories
     * - 10 agricultural products
     * - 100 price records across major Ugandan markets
     */
    public function run()
    {
        // Get admin user ID (or create default)
        $adminUserId = DB::table('users')->where('user_type', 'admin')->first()->id ?? 1;

        // Clear existing data
        DB::table('market_prices')->delete();
        DB::table('market_price_products')->delete();
        DB::table('market_price_categories')->delete();

        // ========================================
        // STEP 1: CREATE CATEGORIES (5 records)
        // ========================================
        echo "Creating market price categories...\n";
        
        $categories = [
            [
                'name' => 'Vegetables',
                'description' => 'Fresh vegetables including leafy greens, tomatoes, onions, and other garden produce',
                'icon' => '🥬',
                'order' => 1,
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fruits',
                'description' => 'Fresh fruits including bananas (matooke), pineapples, mangoes, and other tropical fruits',
                'icon' => '🍌',
                'order' => 2,
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Grains & Cereals',
                'description' => 'Staple grains including maize, rice, millet, and sorghum',
                'icon' => '🌾',
                'order' => 3,
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Root Crops & Tubers',
                'description' => 'Root vegetables including cassava, sweet potatoes, and Irish potatoes',
                'icon' => '🥔',
                'order' => 4,
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Legumes & Pulses',
                'description' => 'Protein-rich crops including beans, groundnuts, cowpeas, and green grams',
                'icon' => '🫘',
                'order' => 5,
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('market_price_categories')->insert($category);
        }

        $categoryIds = DB::table('market_price_categories')->pluck('id', 'name');
        echo "Created " . count($categories) . " categories\n";

        // ========================================
        // STEP 2: CREATE PRODUCTS (10 records)
        // ========================================
        echo "Creating market price products...\n";

        $products = [
            // Vegetables (3 products)
            [
                'category_id' => $categoryIds['Vegetables'],
                'name' => 'Tomatoes',
                'description' => 'Fresh red tomatoes, commonly used in cooking throughout Uganda',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $categoryIds['Vegetables'],
                'name' => 'Onions',
                'description' => 'Red and white onions, essential cooking ingredient',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $categoryIds['Vegetables'],
                'name' => 'Cabbage',
                'description' => 'Fresh green cabbage heads',
                'unit' => 'piece',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Fruits (2 products)
            [
                'category_id' => $categoryIds['Fruits'],
                'name' => 'Matooke (Cooking Bananas)',
                'description' => 'Green cooking bananas, staple food in Uganda',
                'unit' => 'bunch',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $categoryIds['Fruits'],
                'name' => 'Pineapples',
                'description' => 'Fresh sweet pineapples',
                'unit' => 'piece',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Grains (2 products)
            [
                'category_id' => $categoryIds['Grains & Cereals'],
                'name' => 'Maize (Corn)',
                'description' => 'White and yellow maize grains, milled for posho',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $categoryIds['Grains & Cereals'],
                'name' => 'Rice',
                'description' => 'Local and imported rice varieties',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Root Crops (2 products)
            [
                'category_id' => $categoryIds['Root Crops & Tubers'],
                'name' => 'Irish Potatoes',
                'description' => 'Fresh Irish potatoes from highland areas',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $categoryIds['Root Crops & Tubers'],
                'name' => 'Cassava',
                'description' => 'Fresh cassava roots, staple food crop',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Legumes (1 product)
            [
                'category_id' => $categoryIds['Legumes & Pulses'],
                'name' => 'Beans',
                'description' => 'Various bean varieties including red, white, and mixed beans',
                'unit' => 'kg',
                'status' => 'Active',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($products as $product) {
            DB::table('market_price_products')->insert($product);
        }

        $productRecords = DB::table('market_price_products')->get();
        echo "Created " . count($products) . " products\n";

        // ========================================
        // STEP 3: DEFINE UGANDA DISTRICTS & LOCATIONS
        // Using simplified approach - just use integer IDs
        // ========================================
        
        // Since districts table may be empty, we'll just use integer IDs
        // These will be valid foreign keys but might not link to actual district records
        $districtIds = range(1, 6); // IDs 1-6 for 6 major districts
        $subCountyIds = range(1, 20); // IDs 1-20 for sub-counties (or null if not available)

        // ========================================
        // STEP 4: DEFINE UGANDA MARKETS
        // ========================================
        
        $markets = [
            // Kampala Markets
            ['name' => 'Nakasero Market', 'district' => 'Kampala', 'type' => 'urban'],
            ['name' => 'Owino Market (St. Balikuddembe)', 'district' => 'Kampala', 'type' => 'urban'],
            ['name' => 'Kalerwe Market', 'district' => 'Kampala', 'type' => 'urban'],
            ['name' => 'Nakawa Market', 'district' => 'Kampala', 'type' => 'urban'],
            ['name' => 'Busega Market', 'district' => 'Kampala', 'type' => 'suburban'],
            ['name' => 'Nateete Market', 'district' => 'Kampala', 'type' => 'suburban'],
            
            // Wakiso Markets
            ['name' => 'Nansana Market', 'district' => 'Wakiso', 'type' => 'suburban'],
            ['name' => 'Entebbe Town Market', 'district' => 'Wakiso', 'type' => 'urban'],
            
            // Other Districts
            ['name' => 'Mukono Town Market', 'district' => 'Mukono', 'type' => 'urban'],
            ['name' => 'Jinja Main Market', 'district' => 'Jinja', 'type' => 'urban'],
        ];

        // ========================================
        // STEP 5: DEFINE REALISTIC PRICE RANGES (in UGX)
        // ========================================
        
        $priceRanges = [
            'Tomatoes' => ['base' => 3000, 'min' => 2500, 'max' => 4000, 'volatility' => 0.15],
            'Onions' => ['base' => 2500, 'min' => 2000, 'max' => 3500, 'volatility' => 0.12],
            'Cabbage' => ['base' => 1500, 'min' => 1000, 'max' => 2000, 'volatility' => 0.10],
            'Matooke (Cooking Bananas)' => ['base' => 25000, 'min' => 20000, 'max' => 35000, 'volatility' => 0.20],
            'Pineapples' => ['base' => 2000, 'min' => 1500, 'max' => 3000, 'volatility' => 0.15],
            'Maize (Corn)' => ['base' => 2000, 'min' => 1800, 'max' => 2500, 'volatility' => 0.08],
            'Rice' => ['base' => 4000, 'min' => 3500, 'max' => 5000, 'volatility' => 0.10],
            'Irish Potatoes' => ['base' => 2500, 'min' => 2000, 'max' => 3500, 'volatility' => 0.12],
            'Cassava' => ['base' => 1500, 'min' => 1000, 'max' => 2000, 'volatility' => 0.10],
            'Beans' => ['base' => 3500, 'min' => 3000, 'max' => 4500, 'volatility' => 0.12],
        ];

        // ========================================
        // STEP 6: CREATE MARKET PRICE RECORDS (100 records)
        // ========================================
        echo "Creating market price records...\n";

        $prices = [];
        $recordsPerProduct = 10; // 10 products × 10 records = 100 records
        
        foreach ($productRecords as $product) {
            $productName = $product->name;
            $priceRange = $priceRanges[$productName];
            
            // Get category info for this product
            $category = DB::table('market_price_categories')->find($product->category_id);
            
            for ($i = 0; $i < $recordsPerProduct; $i++) {
                // Randomly select a market
                $market = $markets[array_rand($markets)];
                
                // Use random district ID from our range
                $districtId = $districtIds[array_rand($districtIds)];
                
                // Use random sub-county ID or null (50% chance)
                $subCountyId = (rand(0, 1) === 1) ? $subCountyIds[array_rand($subCountyIds)] : null;
                
                // Generate date (last 30 days, more recent dates weighted higher)
                $daysAgo = min(30, pow(rand(0, 100) / 100, 2) * 30); // Quadratic distribution
                $date = Carbon::now()->subDays((int)$daysAgo)->format('Y-m-d');
                
                // Calculate price with realistic variation
                $basePrice = $priceRange['base'];
                $volatility = $priceRange['volatility'];
                
                // Add seasonal/market variation
                $variation = 1 + (rand(-100, 100) / 100) * $volatility;
                $avgPrice = round($basePrice * $variation / 100) * 100; // Round to nearest 100
                
                // Calculate min/max based on average
                $priceMin = round($avgPrice * 0.85 / 100) * 100;
                $priceMax = round($avgPrice * 1.15 / 100) * 100;
                
                // Ensure within absolute bounds
                $avgPrice = max($priceRange['min'], min($priceRange['max'], $avgPrice));
                $priceMin = max($priceRange['min'], $priceMin);
                $priceMax = min($priceRange['max'], $priceMax);
                
                // Data sources
                $sources = [
                    'Field Survey',
                    'Market Monitor',
                    'Trader Report',
                    'Extension Officer',
                    'Market Committee',
                    'MAAIF Market Information',
                ];
                
                $prices[] = [
                    'product_id' => $product->id,
                    'district_id' => $districtId,
                    'sub_county_id' => $subCountyId,
                    'market_name' => $market['name'],
                    'price' => $avgPrice,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                    'currency' => 'UGX',
                    'unit' => $product->unit,
                    'quantity' => null,
                    'date' => $date,
                    'source' => $sources[array_rand($sources)],
                    'notes' => $this->generateNotes($productName, $avgPrice, $basePrice, $market['type']),
                    'status' => 'Active',
                    'created_by' => $adminUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert in batches for better performance
        foreach (array_chunk($prices, 50) as $chunk) {
            DB::table('market_prices')->insert($chunk);
        }

        echo "Created " . count($prices) . " market price records\n";
        
        // ========================================
        // SUMMARY
        // ========================================
        echo "\n========================================\n";
        echo "Market Price Data Seeding Complete!\n";
        echo "========================================\n";
        echo "Categories: 5\n";
        echo "Products: 10\n";
        echo "Price Records: " . count($prices) . "\n";
        echo "Date Range: Last 30 days\n";
        echo "Markets: " . count($markets) . " Uganda markets\n";
        echo "\nYou can now view this data in:\n";
        echo "- Admin Panel: /admin/market-price-categories\n";
        echo "- Mobile App: More → Market Prices\n";
        echo "========================================\n";
    }

    /**
     * Generate contextual notes for price records
     */
    private function generateNotes($productName, $currentPrice, $basePrice, $marketType)
    {
        $notes = [];
        
        // Price trend notes
        $priceDiff = (($currentPrice - $basePrice) / $basePrice) * 100;
        
        if ($priceDiff > 10) {
            $notes[] = "Prices higher than average due to increased demand";
        } elseif ($priceDiff < -10) {
            $notes[] = "Lower prices due to abundant supply";
        } else {
            $notes[] = "Stable prices observed";
        }
        
        // Market-specific notes
        if ($marketType === 'urban') {
            $notes[] = "Urban market with high volume trading";
        } else {
            $notes[] = "Suburban market serving local communities";
        }
        
        // Quality notes
        $qualityNotes = [
            "Good quality produce available",
            "Mixed quality - sorting recommended",
            "Premium quality stock",
            "Fresh arrivals from farms",
            "Standard market quality",
        ];
        $notes[] = $qualityNotes[array_rand($qualityNotes)];
        
        return implode('. ', $notes) . '.';
    }
}
