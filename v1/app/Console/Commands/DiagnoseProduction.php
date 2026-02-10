<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DiagnoseProduction extends Command
{
    protected $signature = 'app:diagnose-production';
    protected $description = 'Diagnose production issues and provide detailed error information';

    public function handle()
    {
        $this->info('🔍 Starting Production Diagnosis...');
        $this->newLine();

        // Test 1: Environment Configuration
        $this->info('1️⃣ Environment Configuration:');
        $this->table(['Setting', 'Value'], [
            ['APP_ENV', config('app.env')],
            ['APP_DEBUG', config('app.debug') ? 'true' : 'false'],
            ['APP_URL', config('app.url')],
            ['DB_CONNECTION', config('database.default')],
            ['DB_HOST', config('database.connections.mysql.host')],
            ['DB_DATABASE', config('database.connections.mysql.database')],
        ]);

        // Test 2: Database Connection
        $this->info('2️⃣ Database Connection Test:');
        try {
            $pdo = DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
            
            $databaseName = DB::connection()->getDatabaseName();
            $this->info("✅ Connected to database: {$databaseName}");
            
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            $this->info('💡 This is likely the cause of 500 errors');
            
            // Check if it's a Laravel Cloud specific issue
            if (str_contains($e->getMessage(), 'ProxySQL')) {
                $this->warn('🚨 Laravel Cloud ProxySQL error detected');
                $this->info('💡 This suggests the Laravel Cloud database is not properly configured or accessible');
            }
        }

        // Test 3: Database Tables
        $this->info('3️⃣ Database Tables Check:');
        try {
            if (Schema::hasTable('categories')) {
                $categoryCount = DB::table('categories')->count();
                $this->info("✅ Categories table exists with {$categoryCount} records");
            } else {
                $this->warn('⚠️ Categories table does not exist - migrations needed');
            }

            if (Schema::hasTable('products')) {
                $productCount = DB::table('products')->count();
                $this->info("✅ Products table exists with {$productCount} records");
            } else {
                $this->warn('⚠️ Products table does not exist - migrations needed');
            }

            if (Schema::hasTable('users')) {
                $userCount = DB::table('users')->count();
                $this->info("✅ Users table exists with {$userCount} records");
            } else {
                $this->warn('⚠️ Users table does not exist - migrations needed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Cannot check tables: ' . $e->getMessage());
        }

        // Test 4: Storage Directories
        $this->info('4️⃣ Storage Directories:');
        $requiredDirs = [
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache'
        ];

        foreach ($requiredDirs as $dir) {
            if (is_dir(base_path($dir))) {
                $this->info("✅ {$dir} exists");
            } else {
                $this->warn("⚠️ {$dir} missing");
            }
        }

        // Test 5: Route Testing
        $this->info('5️⃣ Route Access Simulation:');
        try {
            // Simulate what happens when index route is called
            $categories = \App\Models\Category::with('subcategories')->get();
            $this->info("✅ Categories loaded: " . $categories->count() . " found");
            
            $products = \App\Models\Product::latest()->take(5)->get();
            $this->info("✅ Products loaded: " . $products->count() . " found");
            
        } catch (\Exception $e) {
            $this->error('❌ Route simulation failed: ' . $e->getMessage());
            $this->info('💡 This error would cause 500 status on the homepage');
        }

        // Test 6: Configuration Cache
        $this->info('6️⃣ Configuration Cache:');
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->info('✅ Configuration is cached');
        } else {
            $this->warn('⚠️ Configuration not cached - run php artisan config:cache');
        }

        $this->newLine();
        $this->info('🎯 Diagnosis Complete!');
        
        // Recommendations
        $this->info('📋 Recommendations:');
        $this->info('1. If database connection fails, check Laravel Cloud database settings');
        $this->info('2. If tables are missing, run: php artisan migrate --force');
        $this->info('3. If storage directories are missing, run: php artisan app:ensure-storage-directories');
        $this->info('4. If routes fail, the error handling in web.php should prevent 500 errors');

        return 0;
    }
}