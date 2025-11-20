<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductCategorySpecification;
use App\Models\ProductHasSpecification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DtehmEcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds for DTEHM ecommerce system.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting DTEHM E-commerce Seeder...');
        
        // Clear existing data (optional - comment out if you want to keep existing data)
        $this->command->info('Clearing existing ecommerce data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductHasSpecification::truncate();
        Product::truncate();
        ProductCategorySpecification::truncate();
        ProductCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Create categories
        $categories = $this->createCategories();
        $this->command->info('Created ' . count($categories) . ' categories');
        
        // Create products
        $products = $this->createProducts($categories);
        $this->command->info('Created ' . count($products) . ' products');
        
        $this->command->info('DTEHM E-commerce data seeded successfully!');
    }
    
    /**
     * Create DTEHM-relevant product categories
     */
    private function createCategories()
    {
        $categories = [];
        
        // 1. Disability Aids & Equipment
        $categories[] = ProductCategory::create([
            'category' => 'Disability Aids & Equipment',
            'image' => 'images/categories/disability-aids.jpg',
            'banner_image' => 'images/banners/disability-aids-banner.jpg',
            'show_in_banner' => 'Yes',
            'show_in_categories' => 'Yes',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'icon' => 'fa fa-wheelchair',
            'is_first_banner' => 'No',
        ]);
        
        // Add specifications for Disability Aids
        $this->addSpecifications($categories[0]->id, [
            ['name' => 'Brand', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Material', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Weight Capacity', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Warranty Period', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Color', 'is_required' => 'No', 'attribute_type' => 'text'],
        ]);
        
        // 2. Health & Wellness Products
        $categories[] = ProductCategory::create([
            'category' => 'Health & Wellness Products',
            'image' => 'images/categories/health-wellness.jpg',
            'banner_image' => 'images/banners/health-wellness-banner.jpg',
            'show_in_banner' => 'Yes',
            'show_in_categories' => 'Yes',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'icon' => 'fa fa-heartbeat',
            'is_first_banner' => 'Yes',
            'first_banner_image' => 'images/banners/health-wellness-first.jpg',
        ]);
        
        // Add specifications for Health & Wellness
        $this->addSpecifications($categories[1]->id, [
            ['name' => 'Brand', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Type', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Expiry Date', 'is_required' => 'No', 'attribute_type' => 'date'],
            ['name' => 'Dosage/Size', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Origin', 'is_required' => 'No', 'attribute_type' => 'text'],
        ]);
        
        // 3. Mobility Solutions
        $categories[] = ProductCategory::create([
            'category' => 'Mobility Solutions',
            'image' => 'images/categories/mobility.jpg',
            'banner_image' => 'images/banners/mobility-banner.jpg',
            'show_in_banner' => 'Yes',
            'show_in_categories' => 'Yes',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'icon' => 'fa fa-ambulance',
            'is_first_banner' => 'No',
        ]);
        
        // Add specifications for Mobility
        $this->addSpecifications($categories[2]->id, [
            ['name' => 'Brand', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Max Load', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Adjustable', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Folding', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Material', 'is_required' => 'No', 'attribute_type' => 'text'],
        ]);
        
        // 4. Assistive Technology
        $categories[] = ProductCategory::create([
            'category' => 'Assistive Technology',
            'image' => 'images/categories/assistive-tech.jpg',
            'banner_image' => 'images/banners/assistive-tech-banner.jpg',
            'show_in_banner' => 'Yes',
            'show_in_categories' => 'Yes',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'icon' => 'fa fa-microphone',
            'is_first_banner' => 'No',
        ]);
        
        // Add specifications for Assistive Technology
        $this->addSpecifications($categories[3]->id, [
            ['name' => 'Brand', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Battery Life', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Connectivity', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Warranty', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Model', 'is_required' => 'Yes', 'attribute_type' => 'text'],
        ]);
        
        // 5. Personal Care Items
        $categories[] = ProductCategory::create([
            'category' => 'Personal Care Items',
            'image' => 'images/categories/personal-care.jpg',
            'banner_image' => 'images/banners/personal-care-banner.jpg',
            'show_in_banner' => 'No',
            'show_in_categories' => 'Yes',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'icon' => 'fa fa-heart',
            'is_first_banner' => 'No',
        ]);
        
        // Add specifications for Personal Care
        $this->addSpecifications($categories[4]->id, [
            ['name' => 'Brand', 'is_required' => 'Yes', 'attribute_type' => 'text'],
            ['name' => 'Size', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Material', 'is_required' => 'No', 'attribute_type' => 'text'],
            ['name' => 'Hypoallergenic', 'is_required' => 'No', 'attribute_type' => 'text'],
        ]);
        
        return $categories;
    }
    
    /**
     * Add specifications to a category
     */
    private function addSpecifications($categoryId, $specifications)
    {
        foreach ($specifications as $spec) {
            ProductCategorySpecification::create([
                'product_category_id' => $categoryId,
                'name' => $spec['name'],
                'is_required' => $spec['is_required'],
                'attribute_type' => $spec['attribute_type'] ?? 'text',
            ]);
        }
    }
    
    /**
     * Create DTEHM-relevant products
     */
    private function createProducts($categories)
    {
        $products = [];
        
        // Category 1: Disability Aids & Equipment (5 products)
        $products[] = $this->createProduct($categories[0]->id, [
            'name' => 'Premium Manual Wheelchair',
            'price' => 850000,
            'description' => 'Lightweight aluminum wheelchair with comfortable padded seat and adjustable footrests. Perfect for daily mobility needs.',
            'details' => json_encode(['features' => ['Lightweight aluminum frame', 'Padded armrests', 'Adjustable footrests', 'Easy to fold', 'Anti-tip wheels']]),
            'quantity' => 15,
            'specs' => [
                'Brand' => 'MobilityPro',
                'Material' => 'Aluminum Alloy',
                'Weight Capacity' => '120kg',
                'Warranty Period' => '2 years',
                'Color' => 'Black/Silver'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[0]->id, [
            'name' => 'Adjustable Walking Crutches (Pair)',
            'price' => 45000,
            'description' => 'Height-adjustable aluminum crutches with comfortable grip handles and non-slip rubber tips.',
            'details' => json_encode(['features' => ['Height adjustable', 'Comfortable grip', 'Non-slip tips', 'Lightweight', 'Durable']]),
            'quantity' => 50,
            'specs' => [
                'Brand' => 'WalkEasy',
                'Material' => 'Aluminum',
                'Weight Capacity' => '100kg',
                'Warranty Period' => '1 year',
                'Color' => 'Gray'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[0]->id, [
            'name' => 'Quad Walking Stick with Base',
            'price' => 35000,
            'description' => 'Stable quad-base walking stick providing maximum support and balance. Height adjustable.',
            'details' => json_encode(['features' => ['Four-point base', 'Height adjustable', 'Ergonomic handle', 'Lightweight', 'Maximum stability']]),
            'quantity' => 30,
            'specs' => [
                'Brand' => 'StableWalk',
                'Material' => 'Aluminum/Plastic',
                'Weight Capacity' => '130kg',
                'Warranty Period' => '1 year',
                'Color' => 'Black'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[0]->id, [
            'name' => 'Hospital Bed with Side Rails',
            'price' => 1200000,
            'description' => 'Adjustable hospital bed with manual crank system and safety side rails. Ideal for home care.',
            'details' => json_encode(['features' => ['Manual adjustment', 'Side safety rails', 'Sturdy steel frame', 'Mattress included', 'Easy assembly']]),
            'quantity' => 8,
            'specs' => [
                'Brand' => 'MedCare',
                'Material' => 'Steel Frame',
                'Weight Capacity' => '150kg',
                'Warranty Period' => '3 years',
                'Color' => 'White'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[0]->id, [
            'name' => 'Commode Chair with Wheels',
            'price' => 180000,
            'description' => 'Mobile commode chair with removable bucket and wheels. Ideal for bedroom or bathroom use.',
            'details' => json_encode(['features' => ['Wheeled', 'Removable bucket', 'Padded seat', 'Armrests', 'Lockable wheels']]),
            'quantity' => 20,
            'specs' => [
                'Brand' => 'ComfortCare',
                'Material' => 'Steel/Plastic',
                'Weight Capacity' => '120kg',
                'Warranty Period' => '1 year',
                'Color' => 'White/Blue'
            ]
        ]);
        
        // Category 2: Health & Wellness Products (4 products)
        $products[] = $this->createProduct($categories[1]->id, [
            'name' => 'Digital Blood Pressure Monitor',
            'price' => 65000,
            'description' => 'Automatic digital blood pressure monitor with large LCD display and memory function.',
            'details' => json_encode(['features' => ['Automatic measurement', 'Large LCD display', '90 memory records', 'Irregular heartbeat detection', 'Portable']]),
            'quantity' => 40,
            'specs' => [
                'Brand' => 'HealthTrack',
                'Type' => 'Digital BP Monitor',
                'Expiry Date' => null,
                'Dosage/Size' => 'Standard Cuff',
                'Origin' => 'Japan'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[1]->id, [
            'name' => 'Glucose Monitoring Kit',
            'price' => 85000,
            'description' => 'Complete glucose monitoring system with meter, 50 test strips, and lancets.',
            'details' => json_encode(['features' => ['Fast results', '50 test strips included', 'Memory storage', 'Easy to use', 'Carrying case']]),
            'quantity' => 35,
            'specs' => [
                'Brand' => 'GlucoCheck',
                'Type' => 'Blood Glucose Meter',
                'Expiry Date' => '2026-12-31',
                'Dosage/Size' => 'Starter Kit',
                'Origin' => 'USA'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[1]->id, [
            'name' => 'Vitamin D3 Supplements (60 Capsules)',
            'price' => 25000,
            'description' => 'High-quality Vitamin D3 supplements for bone health and immunity. 60 capsules.',
            'details' => json_encode(['features' => ['1000 IU per capsule', '60 day supply', 'Supports bone health', 'Boosts immunity', 'Easy to swallow']]),
            'quantity' => 100,
            'specs' => [
                'Brand' => 'VitaHealth',
                'Type' => 'Dietary Supplement',
                'Expiry Date' => '2026-06-30',
                'Dosage/Size' => '1000 IU',
                'Origin' => 'Germany'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[1]->id, [
            'name' => 'First Aid Kit - Complete',
            'price' => 45000,
            'description' => 'Comprehensive first aid kit with bandages, antiseptics, and emergency supplies.',
            'details' => json_encode(['features' => ['100+ pieces', 'Bandages & gauze', 'Antiseptic wipes', 'Scissors & tweezers', 'Portable case']]),
            'quantity' => 60,
            'specs' => [
                'Brand' => 'MediReady',
                'Type' => 'First Aid Kit',
                'Expiry Date' => null,
                'Dosage/Size' => 'Large (100+ items)',
                'Origin' => 'UK'
            ]
        ]);
        
        // Category 3: Mobility Solutions (4 products)
        $products[] = $this->createProduct($categories[2]->id, [
            'name' => 'Electric Mobility Scooter',
            'price' => 2500000,
            'description' => '4-wheel electric mobility scooter with long battery life and comfortable seat.',
            'details' => json_encode(['features' => ['4-wheel stability', '30km range', 'Adjustable seat', 'LED headlights', 'Storage basket']]),
            'quantity' => 5,
            'specs' => [
                'Brand' => 'MobiScoot',
                'Max Load' => '150kg',
                'Adjustable' => 'Yes',
                'Folding' => 'No',
                'Material' => 'Steel/Plastic'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[2]->id, [
            'name' => 'Rollator Walker with Seat',
            'price' => 220000,
            'description' => 'Four-wheel rollator walker with built-in seat, hand brakes, and storage basket.',
            'details' => json_encode(['features' => ['Built-in seat', 'Hand brakes', 'Storage basket', 'Foldable', 'Height adjustable']]),
            'quantity' => 25,
            'specs' => [
                'Brand' => 'WalkSafe',
                'Max Load' => '120kg',
                'Adjustable' => 'Yes',
                'Folding' => 'Yes',
                'Material' => 'Aluminum'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[2]->id, [
            'name' => 'Transfer Board for Wheelchair',
            'price' => 55000,
            'description' => 'Smooth transfer board for safe wheelchair transfers. Non-slip surface.',
            'details' => json_encode(['features' => ['Non-slip surface', 'Smooth transfers', 'Durable hardwood', 'Ergonomic design', 'Easy to clean']]),
            'quantity' => 30,
            'specs' => [
                'Brand' => 'SafeTransfer',
                'Max Load' => '180kg',
                'Adjustable' => 'No',
                'Folding' => 'No',
                'Material' => 'Hardwood'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[2]->id, [
            'name' => 'Portable Ramp - Wheelchair Access',
            'price' => 350000,
            'description' => 'Lightweight aluminum ramp for wheelchair access. Foldable and portable.',
            'details' => json_encode(['features' => ['Foldable design', 'Non-slip surface', 'Lightweight', '6 feet length', 'Carrying handle']]),
            'quantity' => 12,
            'specs' => [
                'Brand' => 'AccessEasy',
                'Max Load' => '300kg',
                'Adjustable' => 'No',
                'Folding' => 'Yes',
                'Material' => 'Aluminum'
            ]
        ]);
        
        // Category 4: Assistive Technology (4 products)
        $products[] = $this->createProduct($categories[3]->id, [
            'name' => 'Digital Hearing Aid (Pair)',
            'price' => 450000,
            'description' => 'Advanced digital hearing aids with noise reduction and rechargeable battery.',
            'details' => json_encode(['features' => ['Digital sound processing', 'Noise reduction', 'Rechargeable', 'Bluetooth compatible', 'Discreet design']]),
            'quantity' => 20,
            'specs' => [
                'Brand' => 'HearClear',
                'Battery Life' => '24 hours',
                'Connectivity' => 'Bluetooth',
                'Warranty' => '2 years',
                'Model' => 'HC-2024'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[3]->id, [
            'name' => 'Braille Display Device',
            'price' => 1800000,
            'description' => 'Electronic braille display device with 40 cells. USB and Bluetooth connectivity.',
            'details' => json_encode(['features' => ['40 braille cells', 'USB & Bluetooth', 'Compatible with screen readers', 'Portable', 'Rechargeable']]),
            'quantity' => 8,
            'specs' => [
                'Brand' => 'BrailleTech',
                'Battery Life' => '20 hours',
                'Connectivity' => 'USB/Bluetooth',
                'Warranty' => '3 years',
                'Model' => 'BT-40'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[3]->id, [
            'name' => 'Voice Amplifier for Speech Aid',
            'price' => 95000,
            'description' => 'Portable voice amplifier with headset microphone for clear communication.',
            'details' => json_encode(['features' => ['Clear amplification', 'Headset microphone', 'Rechargeable battery', 'Adjustable volume', 'Portable']]),
            'quantity' => 30,
            'specs' => [
                'Brand' => 'VoicePro',
                'Battery Life' => '12 hours',
                'Connectivity' => 'Wired',
                'Warranty' => '1 year',
                'Model' => 'VP-100'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[3]->id, [
            'name' => 'Large Button Phone for Seniors',
            'price' => 75000,
            'description' => 'Easy-to-use phone with large buttons, loud speaker, and emergency button.',
            'details' => json_encode(['features' => ['Large buttons', 'Loud speaker', 'Emergency SOS button', 'Photo memory dial', 'Hearing aid compatible']]),
            'quantity' => 35,
            'specs' => [
                'Brand' => 'EasyCall',
                'Battery Life' => '7 days standby',
                'Connectivity' => '2G/3G',
                'Warranty' => '1 year',
                'Model' => 'EC-Senior'
            ]
        ]);
        
        // Category 5: Personal Care Items (3 products)
        $products[] = $this->createProduct($categories[4]->id, [
            'name' => 'Adult Diapers (Pack of 10)',
            'price' => 35000,
            'description' => 'Premium adult diapers with high absorbency and odor control. Pack of 10.',
            'details' => json_encode(['features' => ['High absorbency', 'Odor control', 'Comfortable fit', 'Leak-proof', 'Skin-friendly']]),
            'quantity' => 80,
            'specs' => [
                'Brand' => 'ComfortCare',
                'Size' => 'Large',
                'Material' => 'Cotton blend',
                'Hypoallergenic' => 'Yes'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[4]->id, [
            'name' => 'Shower Chair with Back Support',
            'price' => 95000,
            'description' => 'Sturdy shower chair with comfortable back support and non-slip feet.',
            'details' => json_encode(['features' => ['Back support', 'Non-slip feet', 'Drainage holes', 'Height adjustable', 'Rust-resistant']]),
            'quantity' => 25,
            'specs' => [
                'Brand' => 'BathSafe',
                'Size' => 'Standard',
                'Material' => 'Aluminum/Plastic',
                'Hypoallergenic' => 'N/A'
            ]
        ]);
        
        $products[] = $this->createProduct($categories[4]->id, [
            'name' => 'Pressure Relief Cushion',
            'price' => 65000,
            'description' => 'Medical-grade pressure relief cushion for wheelchair users. Prevents pressure sores.',
            'details' => json_encode(['features' => ['Pressure relief', 'Gel & foam', 'Breathable cover', 'Washable', 'Anti-slip bottom']]),
            'quantity' => 40,
            'specs' => [
                'Brand' => 'ComfortSeat',
                'Size' => '18" x 16"',
                'Material' => 'Gel & Memory Foam',
                'Hypoallergenic' => 'Yes'
            ]
        ]);
        
        return $products;
    }
    
    /**
     * Create a single product with specifications
     */
    private function createProduct($categoryId, $data)
    {
        // Build detailed description from features if provided
        $description = '<p>' . $data['description'] . '</p>';
        if (isset($data['details'])) {
            $detailsArray = json_decode($data['details'], true);
            if (isset($detailsArray['features'])) {
                $description .= "<p><strong>Key Features:</strong></p><ul>";
                foreach ($detailsArray['features'] as $feature) {
                    $description .= "<li>{$feature}</li>";
                }
                $description .= "</ul>";
            }
        }
        
        $product = Product::create([
            'name' => $data['name'],
            'price_1' => $data['price'], // Selling price
            'price_2' => round($data['price'] * 1.15), // Original price (15% higher)
            'category' => $categoryId,
            'description' => $description,
            'feature_photo' => 'images/products/' . Str::slug($data['name']) . '.jpg',
            'local_id' => 'DTEHM-' . strtoupper(Str::random(8)),
            'has_colors' => 'No',
            'has_sizes' => 'No',
            'currency' => 'UGX',
            'tags' => 'disability,wellness,dtehm,medical,healthcare',
            'home_section_1' => rand(0, 1) ? 'Yes' : 'No',
            'home_section_2' => 'No',
            'home_section_3' => rand(0, 1) ? 'Yes' : 'No',
        ]);
        
        // Add specifications if provided
        if (isset($data['specs'])) {
            foreach ($data['specs'] as $name => $value) {
                if ($value !== null) {
                    ProductHasSpecification::create([
                        'product_id' => $product->id,
                        'name' => $name,
                        'value' => $value,
                    ]);
                }
            }
        }
        
        return $product;
    }
}
