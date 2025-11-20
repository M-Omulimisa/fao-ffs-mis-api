<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionSystemTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates a complete test hierarchy with 50 users and test orders
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('🚀 Starting DTEHM Commission System Test Data Generation...');
        
        DB::beginTransaction();
        
        try {
            // Step 1: Create users with proper hierarchy
            $this->command->info('📝 Step 1: Creating test users with 10-level hierarchy...');
            $users = $this->createUserHierarchy();
            $this->command->info("✅ Created {$users->count()} users with proper parent hierarchy");
            
            // Step 2: Get test products
            $this->command->info('📝 Step 2: Getting test products...');
            $products = $this->getTestProducts();
            $this->command->info("✅ Found {$products->count()} products for testing");
            
            // Step 3: Create test orders with DTEHM sellers
            $this->command->info('📝 Step 3: Creating test orders...');
            $orders = $this->createTestOrders($users, $products);
            $this->command->info("✅ Created {$orders->count()} test orders");
            
            // Step 4: Create test ordered items
            $this->command->info('📝 Step 4: Creating test ordered items...');
            $items = $this->createTestOrderedItems($orders, $products, $users);
            $this->command->info("✅ Created {$items->count()} test ordered items");
            
            // Step 5: Mark some items as paid (will trigger commission processing)
            $this->command->info('📝 Step 5: Processing payments and commissions...');
            $processedCount = $this->processPayments($items);
            $this->command->info("✅ Processed {$processedCount} paid items with commissions");
            
            DB::commit();
            
            $this->command->info('');
            $this->command->info('🎉 Commission System Test Data Generation Complete!');
            $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->displaySummary($users, $orders, $items);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error generating test data: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
    
    /**
     * Create users with proper 10-level hierarchy
     */
    private function createUserHierarchy()
    {
        $users = collect();
        
        // Create root user (has no parents)
        $rootUser = User::create([
            'name' => 'Root Seller - Level 0',
            'first_name' => 'Root',
            'last_name' => 'Seller',
            'email' => 'root.seller@dtehm.test',
            'username' => 'root_seller',
            'password' => Hash::make('password'),
            'phone_number' => '+256700000000',
            'address' => 'Kampala, Uganda',
            'sex' => 'Male',
            'dob' => '1990-01-01',
            'status' => 1,
            'is_dtehm_member' => 'Yes',
            'dtehm_member_id' => 'DTEHM20250001',
            'dtehm_member_membership_date' => now(),
            'dtehm_membership_is_paid' => 'Yes',
            'dtehm_membership_paid_date' => now(),
            'dtehm_membership_paid_amount' => 50000,
        ]);
        
        $users->push($rootUser);
        $this->command->info("  ✓ Created root user: {$rootUser->name} (ID: {$rootUser->id})");
        
        // Create 10 generations under root user
        $previousUser = $rootUser;
        
        for ($generation = 1; $generation <= 10; $generation++) {
            // Create 2-3 users at each generation level
            $usersPerLevel = rand(2, 3);
            
            for ($i = 1; $i <= $usersPerLevel; $i++) {
                $userId = str_pad(($generation * 100) + $i, 4, '0', STR_PAD_LEFT);
                
                // Build parent hierarchy from previousUser
                $parentHierarchy = ['parent_1' => $previousUser->id];
                for ($p = 2; $p <= 10; $p++) {
                    $parentField = "parent_" . ($p - 1);
                    if (!empty($previousUser->$parentField)) {
                        $parentHierarchy["parent_{$p}"] = $previousUser->$parentField;
                    }
                }
                
                $user = User::create(array_merge([
                    'name' => "Seller Gen{$generation}-{$i}",
                    'first_name' => "Gen{$generation}",
                    'last_name' => "Seller{$i}",
                    'email' => "seller.gen{$generation}.{$i}@dtehm.test",
                    'username' => "seller_gen{$generation}_{$i}",
                    'password' => Hash::make('password'),
                    'phone_number' => '+25670' . str_pad($generation * 1000 + $i, 7, '0', STR_PAD_LEFT),
                    'address' => 'Kampala, Uganda',
                    'sex' => $i % 2 == 0 ? 'Female' : 'Male',
                    'dob' => '199' . rand(0, 9) . '-0' . rand(1, 9) . '-' . rand(10, 28),
                    'status' => 1,
                    'is_dtehm_member' => 'Yes',
                    'dtehm_member_id' => "DTEHM2025{$userId}",
                    'dtehm_member_membership_date' => now()->subDays(rand(1, 365)),
                    'dtehm_membership_is_paid' => 'Yes',
                    'dtehm_membership_paid_date' => now()->subDays(rand(1, 365)),
                    'dtehm_membership_paid_amount' => 50000,
                ], $parentHierarchy));
                
                $users->push($user);
                
                if ($i == 1) {
                    $previousUser = $user; // Use first user of this level for next generation
                }
            }
            
            $this->command->info("  ✓ Created generation {$generation}: {$usersPerLevel} users");
        }
        
        // Create some additional users with varying hierarchy depths
        for ($extra = 1; $extra <= 10; $extra++) {
            $randomParent = $users->random();
            
            // Build parent hierarchy from randomParent
            $parentHierarchy = ['parent_1' => $randomParent->id];
            for ($p = 2; $p <= 10; $p++) {
                $parentField = "parent_" . ($p - 1);
                if (!empty($randomParent->$parentField)) {
                    $parentHierarchy["parent_{$p}"] = $randomParent->$parentField;
                }
            }
            
            $user = User::create(array_merge([
                'name' => "Extra Seller {$extra}",
                'first_name' => "Extra",
                'last_name' => "Seller{$extra}",
                'email' => "extra.seller{$extra}@dtehm.test",
                'username' => "extra_seller_{$extra}",
                'password' => Hash::make('password'),
                'phone_number' => '+25679' . str_pad($extra, 7, '0', STR_PAD_LEFT),
                'address' => 'Kampala, Uganda',
                'sex' => $extra % 2 == 0 ? 'Female' : 'Male',
                'dob' => '199' . rand(0, 9) . '-0' . rand(1, 9) . '-' . rand(10, 28),
                'status' => 1,
                'is_dtehm_member' => 'Yes',
                'dtehm_member_id' => "DTEHM20259" . str_pad($extra, 3, '0', STR_PAD_LEFT),
                'dtehm_member_membership_date' => now()->subDays(rand(1, 365)),
                'dtehm_membership_is_paid' => 'Yes',
                'dtehm_membership_paid_date' => now()->subDays(rand(1, 365)),
                'dtehm_membership_paid_amount' => 50000,
            ], $parentHierarchy));
            $users->push($user);
        }
        
        $this->command->info("  ✓ Created 10 additional users with random hierarchy");
        
        return $users;
    }
    
    /**
     * Get test products
     */
    private function getTestProducts()
    {
        $products = Product::limit(20)->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('  ⚠ No products found. Creating sample products...');
            
            // Create some basic products for testing
            for ($i = 1; $i <= 10; $i++) {
                Product::create([
                    'name' => "Test Product {$i}",
                    'local_id' => "TEST" . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'price_1' => rand(10000, 500000),
                    'description' => "Test product for commission system testing",
                    'quantity' => rand(10, 100),
                    'category' => 1,
                ]);
            }
            
            $products = Product::limit(20)->get();
        }
        
        return $products;
    }
    
    /**
     * Create test orders
     */
    private function createTestOrders($users, $products)
    {
        $orders = collect();
        
        // Create 20 orders with different sellers
        for ($i = 1; $i <= 20; $i++) {
            $seller = $users->where('is_dtehm_member', 'Yes')->random();
            $receiptNumber = 'TEST-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            $order = Order::create([
                'receipt_number' => $receiptNumber,
                'invoice_number' => 'INV-' . $receiptNumber,
                'order_date' => now()->subDays(rand(0, 30)),
                'order_state' => rand(0, 2), // Pending, Processing, or Completed
                'customer_name' => 'Test Customer ' . $i,
                'customer_phone_number_1' => '+25670' . rand(1000000, 9999999),
                'customer_address' => 'Kampala, Uganda',
                'payment_gateway' => ['cash_on_delivery', 'pesapal', 'manual'][rand(0, 2)],
                'order_total' => 0, // Will be calculated
                'sub_total' => 0,
                'tax' => 0,
                'discount' => 0,
                'delivery_amount' => rand(0, 10000),
                'payable_amount' => 0,
                // Commission fields
                'has_detehm_seller' => 'Yes',
                'dtehm_seller_id' => $seller->dtehm_member_id,
                'dtehm_user_id' => $seller->id,
                'order_is_paid' => rand(0, 1) ? 'Yes' : 'No',
                'order_paid_date' => rand(0, 1) ? now()->subDays(rand(0, 15)) : null,
            ]);
            
            $orders->push($order);
        }
        
        return $orders;
    }
    
    /**
     * Create test ordered items
     */
    private function createTestOrderedItems($orders, $products, $users)
    {
        $items = collect();
        
        foreach ($orders as $order) {
            // Create 1-5 items per order
            $itemCount = rand(1, 5);
            $orderTotal = 0;
            
            for ($i = 1; $i <= $itemCount; $i++) {
                $product = $products->random();
                $qty = rand(1, 5);
                $unitPrice = $product->price_1;
                $subtotal = $unitPrice * $qty;
                $orderTotal += $subtotal;
                
                $item = OrderedItem::create([
                    'order' => $order->id,
                    'product' => $product->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'amount' => $unitPrice,
                    'color' => ['Red', 'Blue', 'Black', 'White', null][rand(0, 4)],
                    'size' => ['S', 'M', 'L', 'XL', null][rand(0, 4)],
                    // Commission fields - copy from order
                    'has_detehm_seller' => $order->has_detehm_seller,
                    'dtehm_seller_id' => $order->dtehm_seller_id,
                    'dtehm_user_id' => $order->dtehm_user_id,
                    'item_is_paid' => $order->order_is_paid,
                    'item_paid_date' => $order->order_paid_date,
                    'item_paid_amount' => $order->order_is_paid === 'Yes' ? $subtotal : null,
                ]);
                
                $items->push($item);
            }
            
            // Update order totals
            $order->update([
                'sub_total' => $orderTotal,
                'order_total' => $orderTotal + $order->delivery_amount,
                'payable_amount' => $orderTotal + $order->delivery_amount,
                'order_paid_amount' => $order->order_is_paid === 'Yes' ? ($orderTotal + $order->delivery_amount) : null,
            ]);
        }
        
        return $items;
    }
    
    /**
     * Process payments and trigger commissions
     */
    private function processPayments($items)
    {
        $processedCount = 0;
        
        foreach ($items as $item) {
            // Process 70% of items as paid (30% remain unpaid for testing)
            if ($item->item_is_paid === 'Yes') {
                // Item is already marked as paid, commission should auto-process
                // Let's verify it processed
                $item->refresh();
                
                if ($item->commission_is_processed === 'Yes') {
                    $processedCount++;
                    $this->command->info("  ✓ Item #{$item->id}: Commission auto-processed (Total: UGX " . number_format($item->total_commission_amount, 0) . ")");
                } else {
                    $this->command->warn("  ⚠ Item #{$item->id}: Paid but commission not processed");
                }
            }
        }
        
        // Manually mark some additional items as paid to trigger more commissions
        $unpaidItems = $items->where('item_is_paid', 'No')->take(5);
        
        foreach ($unpaidItems as $item) {
            $this->command->info("  → Marking item #{$item->id} as paid...");
            
            $item->item_is_paid = 'Yes';
            $item->item_paid_date = now();
            $item->item_paid_amount = $item->subtotal;
            $item->save(); // This should trigger commission processing
            
            $item->refresh();
            
            if ($item->commission_is_processed === 'Yes') {
                $processedCount++;
                $this->command->info("  ✓ Item #{$item->id}: Commission processed! (Total: UGX " . number_format($item->total_commission_amount, 0) . ")");
            } else {
                $this->command->warn("  ⚠ Item #{$item->id}: Commission processing may have failed");
            }
        }
        
        return $processedCount;
    }
    
    /**
     * Display summary of test data
     */
    private function displaySummary($users, $orders, $items)
    {
        $this->command->info('');
        $this->command->info('📊 TEST DATA SUMMARY');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("Total Users Created: {$users->count()}");
        $this->command->info("  - DTEHM Members: " . $users->where('is_dtehm_member', 'Yes')->count());
        $this->command->info("  - With Full Hierarchy (10 levels): " . $users->whereNotNull('parent_10')->count());
        $this->command->info('');
        $this->command->info("Total Orders Created: {$orders->count()}");
        $this->command->info("  - With DTEHM Seller: " . $orders->where('has_detehm_seller', 'Yes')->count());
        $this->command->info("  - Paid Orders: " . $orders->where('order_is_paid', 'Yes')->count());
        $this->command->info('');
        $this->command->info("Total Order Items: {$items->count()}");
        $this->command->info("  - Paid Items: " . $items->where('item_is_paid', 'Yes')->count());
        $this->command->info("  - Commission Processed: " . $items->where('commission_is_processed', 'Yes')->count());
        $this->command->info('');
        
        // Commission stats
        $totalCommissions = $items->where('commission_is_processed', 'Yes')->sum('total_commission_amount');
        $this->command->info("💰 Total Commissions Distributed: UGX " . number_format($totalCommissions, 0));
        
        // Get a sample user with commissions
        $sampleSeller = $users->where('is_dtehm_member', 'Yes')->first();
        if ($sampleSeller) {
            $service = new \App\Services\CommissionService();
            $summary = $service->getUserCommissionSummary($sampleSeller->id);
            
            if ($summary['success'] && $summary['total_earned'] > 0) {
                $this->command->info('');
                $this->command->info("📈 Sample User Commission Earnings:");
                $this->command->info("  User: {$sampleSeller->name} (ID: {$sampleSeller->id})");
                $this->command->info("  Total Earned: UGX " . number_format($summary['total_earned'], 0));
                $this->command->info("  Transactions: {$summary['transaction_count']}");
            }
        }
        
        $this->command->info('');
        $this->command->info('🎯 TESTING CREDENTIALS');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Email: root.seller@dtehm.test');
        $this->command->info('Password: password');
        $this->command->info('');
        $this->command->info('All test users have password: password');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
