<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SetupProduction extends Command
{
    protected $signature = 'app:setup-production';
    protected $description = 'Setup production environment with database migrations and optimizations';

    public function handle()
    {
        $this->info('Starting production setup...');

        try {
            // Test database connection
            $this->info('Testing database connection...');
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');

            // Run migrations
            $this->info('Running database migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Migrations completed');

            // Clear and cache config
            $this->info('Optimizing configuration...');
            Artisan::call('config:clear');
            Artisan::call('config:cache');
            $this->info('✅ Configuration optimized');

            // Clear and cache routes
            $this->info('Optimizing routes...');
            Artisan::call('route:clear');
            Artisan::call('route:cache');
            $this->info('✅ Routes optimized');

            // Clear and cache views
            $this->info('Optimizing views...');
            Artisan::call('view:clear');
            Artisan::call('view:cache');
            $this->info('✅ Views optimized');

            // Create storage directories
            $this->info('Setting up storage directories...');
            Artisan::call('app:ensure-storage-directories');
            $this->info('✅ Storage directories created');

            $this->info('🎉 Production setup completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Production setup failed: ' . $e->getMessage());
            $this->error('Full error: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}