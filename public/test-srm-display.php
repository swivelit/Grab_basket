<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRM Product Image Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2>🖼️ SRM Product Image Display Test</h2>
    
    <?php
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    use App\Models\Product;
    
    // Get SRM701, SRM702, SRM703 products that should have working images
    $srmProducts = Product::whereIn('unique_id', ['SRM701', 'SRM702', 'SRM703'])
        ->whereNotNull('image')
        ->get();
    ?>
    
    <div class="row">
        <?php foreach($srmProducts as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <strong><?php echo htmlspecialchars($product->name); ?></strong>
                    <br><small>ID: <?php echo $product->unique_id; ?></small>
                </div>
                <div class="card-body text-center">
                    <?php
                    $imagePath = $product->image;
                    $imageUrl = asset('storage/' . $imagePath);
                    ?>
                    
                    <img src="<?php echo $imageUrl; ?>" 
                         alt="<?php echo htmlspecialchars($product->name); ?>"
                         class="img-fluid rounded"
                         style="max-height: 200px; border: 2px solid #dee2e6;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    
                    <div style="display: none;" class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        Image not found
                    </div>
                    
                    <div class="mt-2">
                        <small class="text-muted">
                            Path: <?php echo $imagePath; ?><br>
                            <a href="<?php echo $imageUrl; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                Open Image
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="mt-4">
        <h4>Test Results:</h4>
        <ul class="list-group">
            <li class="list-group-item">
                <strong>Storage Symlink:</strong> 
                <?php 
                $storageLink = public_path('storage');
                if (file_exists($storageLink)) {
                    echo '<span class="text-success">✅ EXISTS</span>';
                } else {
                    echo '<span class="text-danger">❌ MISSING</span>';
                }
                ?>
            </li>
            <li class="list-group-item">
                <strong>Products Found:</strong> <?php echo $srmProducts->count(); ?>
            </li>
            <li class="list-group-item">
                <strong>Image Storage Path:</strong> <?php echo storage_path('app/public/products/'); ?>
            </li>
        </ul>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>