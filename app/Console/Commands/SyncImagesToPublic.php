<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class SyncImagesToPublic extends Command
{
    protected $signature = 'images:sync-to-public';
    protected $description = 'Sync all images from R2 to public disk';

    public function handle()
    {
        $this->info('Syncing images from R2 to public disk...');
        
        $synced = 0;
        $missing = 0;
        
        // Get all product images
        $productImages = ProductImage::all();
        
        $this->info('Found ' . $productImages->count() . ' product images');
        
        foreach ($productImages as $img) {
            $existsR2 = Storage::disk('r2')->exists($img->image_path);
            $existsPublic = Storage::disk('public')->exists($img->image_path);
            
            if ($existsR2 && !$existsPublic) {
                try {
                    $content = Storage::disk('r2')->get($img->image_path);
                    Storage::disk('public')->put($img->image_path, $content);
                    
                    if (Storage::disk('public')->exists($img->image_path)) {
                        $this->line('✅ Synced: ' . $img->image_path);
                        $synced++;
                    } else {
                        $this->error('❌ Failed: ' . $img->image_path);
                        $missing++;
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Error: ' . $img->image_path . ' - ' . $e->getMessage());
                    $missing++;
                }
            } elseif (!$existsR2 && !$existsPublic) {
                $this->error('❌ Missing everywhere: ' . $img->image_path);
                $missing++;
            }
        }
        
        $this->info("\nSummary:");
        $this->info("Files synced: $synced ✅");
        if ($missing > 0) {
            $this->error("Files missing/failed: $missing ❌");
        }
        
        return 0;
    }
}
