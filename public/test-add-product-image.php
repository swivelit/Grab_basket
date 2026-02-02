<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Add Product - Image Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e0f7fa, #80deea); padding: 20px; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .preview-img { max-width: 200px; border-radius: 10px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3><i class="fas fa-plus-circle"></i> Test Add Product - Image Upload</h3>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This is a test page to verify image upload functionality.
                        Upload an image to see if it works correctly.
                    </div>

                    <form method="POST" action="/test-image-upload" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" value="Test Product" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" value="100" step="0.01" required>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" id="imageInput" onchange="previewImage()">
                                <div id="imagePreview" style="display: none;">
                                    <img id="preview" class="preview-img" alt="Preview">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-upload"></i> Test Upload
                                </button>
                                <a href="/seller/create-product" class="btn btn-primary btn-lg ms-2">
                                    <i class="fas fa-arrow-left"></i> Back to Add Product
                                </a>
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">
                    
                    <h5><i class="fas fa-info-circle"></i> Recent Products with Images</h5>
                    
                    <?php
                    require_once 'vendor/autoload.php';
                    $app = require_once 'bootstrap/app.php';
                    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                    
                    use App\Models\Product;
                    
                    $recentProducts = Product::whereNotNull('image')
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    ?>
                    
                    <div class="row">
                        <?php foreach($recentProducts as $product): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6><?php echo htmlspecialchars($product->name); ?></h6>
                                    <p class="text-muted">ID: <?php echo $product->unique_id; ?></p>
                                    <p class="text-muted">Price: Rs.<?php echo $product->price; ?></p>
                                    
                                    <?php 
                                    $imagePath = $product->image;
                                    $imageUrl = null;
                                    
                                    // Check different possible image locations
                                    if ($imagePath) {
                                        $storagePath = public_path('storage/' . $imagePath);
                                        $directPath = public_path($imagePath);
                                        $imagesPath = public_path('images/' . basename($imagePath));
                                        
                                        if (file_exists($storagePath)) {
                                            $imageUrl = asset('storage/' . $imagePath);
                                        } elseif (file_exists($directPath)) {
                                            $imageUrl = asset($imagePath);
                                        } elseif (file_exists($imagesPath)) {
                                            $imageUrl = asset('images/' . basename($imagePath));
                                        }
                                    }
                                    ?>
                                    
                                    <?php if ($imageUrl): ?>
                                        <img src="<?php echo $imageUrl; ?>" alt="Product Image" class="img-fluid" style="max-height: 120px; border-radius: 5px;">
                                        <p class="text-success mt-2"><i class="fas fa-check"></i> Image Found</p>
                                        <small class="text-muted">Path: <?php echo $imagePath; ?></small>
                                    <?php else: ?>
                                        <div class="text-danger">
                                            <i class="fas fa-times"></i> Image Not Found
                                            <br><small>Path: <?php echo $imagePath; ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage() {
    const input = document.getElementById('imageInput');
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>