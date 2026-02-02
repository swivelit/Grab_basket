<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3>Test Edit Product - 500 Error Debug</h3>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        require_once 'vendor/autoload.php';
                        $app = require_once 'bootstrap/app.php';
                        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                        
                        use App\Models\Product;
                        use App\Models\Category;
                        use App\Models\Subcategory;
                        
                        // Get product ID from URL parameter
                        $productId = $_GET['id'] ?? 2; // Default to product ID 2
                        
                        echo "<div class='alert alert-info'>Testing product ID: {$productId}</div>";
                        
                        // Try to fetch the product
                        $product = Product::find($productId);
                        
                        if (!$product) {
                            echo "<div class='alert alert-danger'>Product not found!</div>";
                            exit;
                        }
                        
                        echo "<div class='alert alert-success'>✅ Product found: {$product->name}</div>";
                        
                        // Try to fetch categories and subcategories
                        $categories = Category::all();
                        $subcategories = Subcategory::all();
                        
                        echo "<div class='alert alert-info'>Categories: {$categories->count()}, Subcategories: {$subcategories->count()}</div>";
                        
                        // Test image handling
                        if ($product->image) {
                            $imagePath = $product->image;
                            $imageFound = false;
                            $imageUrl = null;
                            
                            // Test different paths
                            if (file_exists(public_path('storage/' . $imagePath))) {
                                $imageUrl = asset('storage/' . $imagePath);
                                $imageFound = true;
                                $imageLocation = 'storage/' . $imagePath;
                            } elseif (file_exists(public_path($imagePath))) {
                                $imageUrl = asset($imagePath);
                                $imageFound = true;
                                $imageLocation = $imagePath;
                            } elseif (file_exists(public_path('images/' . basename($imagePath)))) {
                                $imageUrl = asset('images/' . basename($imagePath));
                                $imageFound = true;
                                $imageLocation = 'images/' . basename($imagePath);
                            }
                            
                            if ($imageFound) {
                                echo "<div class='alert alert-success'>✅ Image found at: {$imageLocation}</div>";
                                echo "<img src='{$imageUrl}' alt='Product Image' style='max-width: 200px; border-radius: 8px;'>";
                            } else {
                                echo "<div class='alert alert-warning'>⚠️ Image not found. Stored path: {$imagePath}</div>";
                            }
                        } else {
                            echo "<div class='alert alert-info'>ℹ️ No image set for this product</div>";
                        }
                        
                        // Test if we can simulate the edit form
                        echo "<h5 class='mt-4'>Simulated Edit Form Data:</h5>";
                        echo "<table class='table table-sm'>";
                        echo "<tr><td>Name:</td><td>{$product->name}</td></tr>";
                        echo "<tr><td>Price:</td><td>Rs.{$product->price}</td></tr>";
                        echo "<tr><td>Category:</td><td>" . ($product->category ? $product->category->name : 'N/A') . "</td></tr>";
                        echo "<tr><td>Subcategory:</td><td>" . ($product->subcategory ? $product->subcategory->name : 'N/A') . "</td></tr>";
                        echo "<tr><td>Stock:</td><td>{$product->stock}</td></tr>";
                        echo "<tr><td>Seller:</td><td>" . ($product->seller ? $product->seller->name : 'N/A') . "</td></tr>";
                        echo "</table>";
                        
                        echo "<div class='alert alert-success'>✅ All tests passed! Edit functionality should work.</div>";
                        
                        // Provide link to actual edit page
                        $editUrl = "https://grabbaskets.com/seller/product/{$product->id}/edit";
                        echo "<a href='{$editUrl}' class='btn btn-primary'>Test Actual Edit Page</a>";
                        
                    } catch (\Exception $e) {
                        echo "<div class='alert alert-danger'>";
                        echo "<h5>❌ Error Caught:</h5>";
                        echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
                        echo "<strong>File:</strong> " . $e->getFile() . "<br>";
                        echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
                        echo "<details><summary>Stack Trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>