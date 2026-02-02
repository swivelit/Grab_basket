<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class FixMissingImages extends Command
{
    protected $signature = 'fix:missing-images';
    protected $description = 'Automatically fix missing product images by assigning available images from storage.';

    public function handle()
    {
        $products = Product::all();
        $fixedCount = 0;
        foreach ($products as $product) {
            if ($product->productImages()->count() === 0) {
                $imagePath = $this->findImageForProduct($product->id);
                if ($imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $imagePath,
                    ]);
                    $fixedCount++;
                    $this->info("Fixed image for product ID: {$product->id}");
                }
            }
        }
        $this->info("Total products fixed: $fixedCount");
    }

    private function findImageForProduct($productId)
    {
        $storagePath = storage_path('app/public/products');
        $files = glob($storagePath . "/{$productId}_*.*");
        if (!empty($files)) {
            $file = basename($files[0]);
            return 'products/' . $file;
        }
        return null;
    }
}
