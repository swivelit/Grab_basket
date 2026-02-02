<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Image Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .product-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9; }
        .product-image { max-width: 100%; height: 200px; object-fit: cover; border-radius: 5px; border: 2px solid #ddd; }
        .image-placeholder { width: 100%; height: 200px; background: #eee; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666; }
        .status-ok { color: green; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .status-warning { color: orange; font-weight: bold; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .warning-box { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .fix-box { background: #e8f5e8; border-left: 4px solid #4caf50; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .small { font-size: 0.85em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Product Image Diagnostic Tool</h1>
        
        <div class="info-box">
            <h3>Purpose</h3>
            <p>This tool helps diagnose and fix product image visibility issues in the seller edit product form.</p>
        </div>

        <?php
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Initialize Laravel application
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        
        try {
            // Get products with images
            $products = \App\Models\Product::whereNotNull('image')
                                          ->where('image', '!=', '')
                                          ->with(['seller', 'category', 'subcategory'])
                                          ->limit(20)
                                          ->get();
            
            echo "<h2>📊 Image Analysis Results</h2>";
            echo "<p>Found " . $products->count() . " products with image data:</p>";
            
            $stats = [
                'total' => $products->count(),
                'visible' => 0,
                'missing' => 0,
                'different_paths' => []
            ];
            
            foreach ($products as $product) {
                $imagePath = $product->image;
                $fullPath = public_path('storage/' . $imagePath);
                $exists = file_exists($fullPath);
                
                if ($exists) {
                    $stats['visible']++;
                } else {
                    $stats['missing']++;
                }
                
                // Track different path patterns
                if (strpos($imagePath, 'seller/') === 0) {
                    $stats['different_paths']['seller_structure'] = ($stats['different_paths']['seller_structure'] ?? 0) + 1;
                } elseif (strpos($imagePath, 'products/') === 0) {
                    $stats['different_paths']['products_structure'] = ($stats['different_paths']['products_structure'] ?? 0) + 1;
                } else {
                    $stats['different_paths']['other'] = ($stats['different_paths']['other'] ?? 0) + 1;
                }
            }
            
            echo "<div class='info-box'>";
            echo "<h3>📈 Summary Statistics</h3>";
            echo "<ul>";
            echo "<li><span class='status-ok'>✅ Visible Images:</span> {$stats['visible']}</li>";
            echo "<li><span class='status-error'>❌ Missing Images:</span> {$stats['missing']}</li>";
            echo "<li><strong>Path Structures:</strong></li>";
            foreach ($stats['different_paths'] as $type => $count) {
                echo "<li style='margin-left: 20px;'>• " . str_replace('_', ' ', ucfirst($type)) . ": $count</li>";
            }
            echo "</ul>";
            echo "</div>";
            
            if ($stats['missing'] > 0) {
                echo "<div class='warning-box'>";
                echo "<h3>⚠️ Issues Found</h3>";
                echo "<p>{$stats['missing']} products have image references but the files are missing from storage.</p>";
                echo "</div>";
            }
            
            ?>
            
            <h3>🔍 Detailed Product Analysis</h3>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $imagePath = $product->image;
                    $fullPath = public_path('storage/' . $imagePath);
                    $exists = file_exists($fullPath);
                    $fileSize = $exists ? filesize($fullPath) : 0;
                    ?>
                    <div class="product-card">
                        <h4><?= htmlspecialchars($product->name) ?></h4>
                        
                        <?php if ($exists): ?>
                            <img src="<?= asset('storage/' . $imagePath) ?>" alt="<?= htmlspecialchars($product->name) ?>" class="product-image">
                            <div class="status-ok">✅ Image Visible</div>
                        <?php else: ?>
                            <div class="image-placeholder">
                                <div style="text-align: center;">
                                    <div style="font-size: 2em;">📷</div>
                                    <div>Image Not Found</div>
                                </div>
                            </div>
                            <div class="status-error">❌ Image Missing</div>
                        <?php endif; ?>
                        
                        <div class="small">
                            <strong>Image Path:</strong> <?= htmlspecialchars($imagePath) ?><br>
                            <strong>Full Path:</strong> <?= htmlspecialchars($fullPath) ?><br>
                            <strong>File Size:</strong> <?= $exists ? number_format($fileSize / 1024, 1) . ' KB' : 'N/A' ?><br>
                            <strong>Seller:</strong> <?= $product->seller ? htmlspecialchars($product->seller->store_name ?? $product->seller->name ?? 'Unknown') : 'Unknown' ?><br>
                            <strong>Category:</strong> <?= $product->category ? htmlspecialchars($product->category->name) : 'Unknown' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h3>🛠️ Path Structure Analysis</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Image Path</th>
                        <th>Path Type</th>
                        <th>Status</th>
                        <th>File Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $imagePath = $product->image;
                        $fullPath = public_path('storage/' . $imagePath);
                        $exists = file_exists($fullPath);
                        $fileSize = $exists ? filesize($fullPath) : 0;
                        
                        // Determine path type
                        if (strpos($imagePath, 'seller/') === 0) {
                            $pathType = 'Seller Structure';
                        } elseif (strpos($imagePath, 'products/') === 0) {
                            $pathType = 'Products Structure';
                        } else {
                            $pathType = 'Other/Legacy';
                        }
                        ?>
                        <tr>
                            <td><?= $product->id ?></td>
                            <td><?= htmlspecialchars(substr($product->name, 0, 30)) ?><?= strlen($product->name) > 30 ? '...' : '' ?></td>
                            <td style="font-family: monospace; font-size: 0.85em;"><?= htmlspecialchars($imagePath) ?></td>
                            <td><?= $pathType ?></td>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="status-ok">✅ Found</span>
                                <?php else: ?>
                                    <span class="status-error">❌ Missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $exists ? number_format($fileSize / 1024, 1) . ' KB' : 'N/A' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
        } catch (Exception $e) {
            echo "<div class='warning-box'>";
            echo "<h3>⚠️ Error</h3>";
            echo "<p>Could not connect to database or analyze products: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <div class="fix-box">
            <h3>✅ Fixes Applied</h3>
            <ul>
                <li><strong>Enhanced Image Detection:</strong> Edit form now checks multiple possible image paths</li>
                <li><strong>Fallback Handling:</strong> Shows placeholder when image is missing</li>
                <li><strong>Path Debugging:</strong> Displays current image path for troubleshooting</li>
                <li><strong>Image Preview:</strong> Shows preview when uploading new images</li>
                <li><strong>Old Image Cleanup:</strong> Deletes old image when uploading new one</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>🔧 How Image Storage Works</h3>
            <div class="code">
New Products: seller/{seller_id}/{category_id}/{subcategory_id}/image.jpg
Bulk Upload: products/image.jpg
Legacy: Various paths (handled by fallback logic)
            </div>
            <p><strong>Storage Location:</strong> <code>storage/app/public/</code></p>
            <p><strong>Web Access:</strong> <code>public/storage/</code> (via symlink)</p>
        </div>
        
        <div class="warning-box">
            <h3>💡 Troubleshooting Tips</h3>
            <ul>
                <li>Ensure storage symlink exists: <code>php artisan storage:link</code></li>
                <li>Check file permissions on storage directories</li>
                <li>For missing images, re-upload them through the edit form</li>
                <li>Use bulk upload for batch image updates</li>
            </ul>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 5px; text-align: center;">
            <p><strong>🖼️ Product image visibility issues should now be resolved!</strong></p>
            <p>The edit form will now properly display existing images and handle missing ones gracefully.</p>
        </div>
    </div>
</body>
</html>