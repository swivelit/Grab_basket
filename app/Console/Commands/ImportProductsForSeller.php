<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportProductsForSeller extends Command
{
    protected $signature = 'products:import {seller_email} {file} {--images_zip=}';
    protected $description = 'Import products for a seller from Excel/CSV with optional images ZIP';

    public function handle()
    {
        $email = $this->argument('seller_email');
        $filePath = $this->argument('file');
        $imagesZip = $this->option('images_zip');

        $seller = User::where('email', $email)->where('role', 'seller')->first();
        if (!$seller) {
            $this->error("Seller not found: {$email}");
            return Command::FAILURE;
        }

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $zipPath = null;
        if ($imagesZip) {
            if (!file_exists($imagesZip)) {
                $this->error("Images ZIP not found: {$imagesZip}");
                return Command::FAILURE;
            }
            // Store locally so ProductsImport can access via Storage
            $zipPath = Storage::disk('local')->putFile('temp/bulk-uploads', $imagesZip);
        }

        $count = 0;
        $errors = [];

        try {
            if (in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
                $import = new ProductsImport($zipPath, $seller->id);
                Excel::import($import, $filePath);
                $count = $import->getSuccessCount();
                $errors = $import->getErrors();
            } else {
                $this->error('Unsupported file type. Use CSV, XLSX or XLS.');
                return Command::FAILURE;
            }
        } finally {
            if ($zipPath && Storage::disk('local')->exists($zipPath)) {
                Storage::disk('local')->delete($zipPath);
            }
        }

        $this->info("Imported {$count} products for {$seller->email}.");
        if (!empty($errors)) {
            $this->warn('Some rows had issues:');
            foreach ($errors as $err) {
                $this->line(" - {$err}");
            }
        }

        return Command::SUCCESS;
    }
}
