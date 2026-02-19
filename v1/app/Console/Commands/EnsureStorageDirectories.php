<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnsureStorageDirectories extends Command
{
    protected $signature = 'storage:ensure-directories';
    protected $description = 'Ensure all required storage directories exist';

    public function handle()
    {
        $directories = [
            'storage/framework/views',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/logs',
            'bootstrap/cache'
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0775, true);
                $this->info("Created directory: {$directory}");
            } else {
                $this->info("Directory already exists: {$directory}");
            }
        }

        $this->info('All storage directories are ready!');
        return 0;
    }
}