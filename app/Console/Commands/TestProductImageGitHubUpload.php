<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\GitHubImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class TestProductImageGitHubUpload extends Command
{
    protected $signature = 'test:product-image-github {product_id} {image_path}';
    protected $description = 'Test uploading a product image to GitHub for any product';

    public function handle()
    {
        $productId = $this->argument('product_id');
        $imagePath = $this->argument('image_path');
        $product = Product::find($productId);
        if (!$product) {
            $this->error('Product not found.');
            return 1;
        }
        if (!file_exists($imagePath)) {
            $this->error('Image file not found: ' . $imagePath);
            return 1;
        }
        $service = new GitHubImageService();
        $result = $service->uploadImage(new File($imagePath));
        if ($result['success']) {
            $product->image = $result['url'];
            $product->save();
            $this->info('Image uploaded to GitHub and product updated!');
            $this->info('GitHub URL: ' . $result['url']);
        } else {
            $this->error('GitHub upload failed: ' . $result['error']);
        }
        return 0;
    }
}
