<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductHasSpecification;

class SystemHealthCheckCommand extends Command
{
    protected $signature = 'system:health-check {--fix : Attempt to fix issues}';
    protected $description = 'Check system health and API endpoints';

    public function handle()
    {
        $this->info('🏥 Starting System Health Check...');
        $this->newLine();

        $fix = $this->option('fix');
        $issues = [];

        // 1. Database Connectivity
        $this->info('1. Testing Database Connectivity...');
        try {
            DB::select('SELECT 1');
            $this->line('   ✅ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('   ❌ Database connection failed: ' . $e->getMessage());
            $issues[] = 'Database connectivity';
        }

        // 2. Models Test
        $this->info('2. Testing Models...');
        try {
            $productCount = Product::count();
            $categoryCount = ProductCategory::count();
            $attributeCount = ProductHasSpecification::count();
            
            $this->line("   ✅ Products: {$productCount}");
            $this->line("   ✅ Categories: {$categoryCount}");
            $this->line("   ✅ Specifications: {$attributeCount}");
        } catch (\Exception $e) {
            $this->error('   ❌ Model test failed: ' . $e->getMessage());
            $issues[] = 'Model functionality';
        }

        // 3. Product Model Accessors
        $this->info('3. Testing Product Model Accessors...');
        try {
            $product = Product::first();
            if ($product) {
                $tags = $product->tags_array ?? [];
                $attributes = $product->attributes_array ?? [];
                $this->line('   ✅ Product accessors: OK');
                $this->line("   📊 Sample product: {$product->name}");
                $this->line("   📊 Tags count: " . count($tags));
                $this->line("   📊 Attributes count: " . count($attributes));
            } else {
                $this->line('   ⚠️  No products found to test');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Product accessors failed: ' . $e->getMessage());
            $issues[] = 'Product model accessors';
        }

        // 4. Test Laravel Routes
        $this->info('4. Testing Laravel Application...');
        try {
            $response = $this->call('route:list', ['--path' => 'api']);
            $this->line('   ✅ Laravel routes: OK');
        } catch (\Exception $e) {
            $this->error('   ❌ Laravel routes failed: ' . $e->getMessage());
            $issues[] = 'Laravel application';
        }

        // 5. Test Database Tables
        $this->info('5. Testing Database Tables...');
        $tables = ['products', 'product_categories', 'product_has_specifications', 'product_category_specifications'];
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $this->line("   ✅ {$table}: {$count} records");
            } catch (\Exception $e) {
                $this->error("   ❌ {$table}: " . $e->getMessage());
                $issues[] = "Database table: {$table}";
            }
        }

        // 6. File Permissions
        $this->info('6. Testing File Permissions...');
        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views')
        ];

        foreach ($paths as $path) {
            if (is_writable($path)) {
                $this->line("   ✅ " . basename($path) . ": Writable");
            } else {
                $this->error("   ❌ " . basename($path) . ": Not writable");
                $issues[] = "File permissions: " . basename($path);
            }
        }

        // Summary
        $this->newLine();
        if (empty($issues)) {
            $this->info('🎉 All health checks passed! System is healthy.');
        } else {
            $this->error('⚠️  Issues found:');
            foreach ($issues as $issue) {
                $this->line("   - {$issue}");
            }

            if ($fix) {
                $this->info('🔧 Attempting to fix issues...');
                $this->fixIssues($issues);
            } else {
                $this->info('Run with --fix to attempt automatic fixes.');
            }
        }

        return empty($issues) ? 0 : 1;
    }

    private function fixIssues($issues)
    {
        foreach ($issues as $issue) {
            if (str_contains($issue, 'File permissions')) {
                $this->call('cache:clear');
                $this->call('view:clear');
                $this->call('config:clear');
                $this->line("   🔧 Cleared caches");
            }

            if (str_contains($issue, 'Product model accessors')) {
                $this->call('optimize:clear');
                $this->line("   🔧 Cleared optimizations");
            }
        }
    }
}
