<!DOCTYPE html><!DOCTYPE html>

<html><html lang="en">

<head><head>

    <title>Image Display Test</title>    <meta charset="UTF-8">

    <style>    <meta name="viewport" content="width=device-width, initial-scale=1.0">

        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }    <title>Image Display Test</title>

        .test-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        .image-test { border: 2px solid #ddd; padding: 10px; margin: 10px 0; }    <style>

        img { max-width: 200px; border: 1px solid #ccc; }        body { background: #f8f9fa; padding: 20px; }

        .info { color: #666; font-size: 12px; margin: 5px 0; word-break: break-all; }        .img-test { max-width: 200px; margin: 10px; border: 2px solid #dee2e6; border-radius: 8px; }

        .success { color: green; }        .test-card { margin-bottom: 20px; }

        .error { color: red; }        .alert { margin-bottom: 10px; }

    </style>    </style>

</head></head>

<body><body>

    <h1>🔍 Production Image Display Test</h1><div class="container">

        <h2><i class="fas fa-image"></i> Image Display Test</h2>

    <?php    <p>Testing if images are properly accessible through storage symlink:</p>

    require_once __DIR__ . '/../vendor/autoload.php';

    $app = require_once __DIR__ . '/../bootstrap/app.php';    <?php

    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();    require_once 'vendor/autoload.php';

        $app = require_once 'bootstrap/app.php';

    use App\Models\Product;    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        

    echo "<div class='test-box'>";    use App\Models\Product;

    echo "<h2>Finding Products with Images</h2>";    

        $products = Product::whereNotNull('image')->take(5)->get();

    // Get a product with image    ?>

    $product = Product::where('image', '!=', '')    

        ->whereNotNull('image')    <div class="row">

        ->where('unique_id', '996')        <?php foreach($products as $product): ?>

        ->first();        <div class="col-md-4">

                <div class="card test-card">

    if (!$product) {                <div class="card-header">

        $product = Product::where('image', '!=', '')                    <strong><?php echo htmlspecialchars($product->name); ?></strong>

            ->whereNotNull('image')                </div>

            ->first();                <div class="card-body">

    }                    <p><strong>Image Path:</strong> <?php echo $product->image; ?></p>

                        

    if ($product) {                    <?php

        echo "<p><strong>Product:</strong> {$product->product_name}</p>";                    $imagePath = $product->image;

        echo "<p><strong>Unique ID:</strong> {$product->unique_id}</p>";                    $fullStoragePath = storage_path('app/public/' . $imagePath);

        echo "<p><strong>Raw Image Field:</strong> <code>{$product->image}</code></p>";                    $publicPath = public_path('storage/' . $imagePath);

                            $imageUrl = asset('storage/' . $imagePath);

        echo "<div class='image-test'>";                    ?>

        echo "<h3>Testing Image URL Generation</h3>";                    

                            <div class="alert alert-info">

        // Get the generated URL                        <small>

        $imageUrl = $product->image_url;                            <strong>Storage File Exists:</strong> <?php echo file_exists($fullStoragePath) ? '✅ YES' : '❌ NO'; ?><br>

        echo "<p class='info'><strong>Generated URL:</strong><br><code>{$imageUrl}</code></p>";                            <strong>Public Link Exists:</strong> <?php echo file_exists($publicPath) ? '✅ YES' : '❌ NO'; ?><br>

                                    <strong>Image URL:</strong> <?php echo $imageUrl; ?>

        // Check if it's the correct format                        </small>

        if (strpos($imageUrl, '/serve-image/') !== false) {                    </div>

            echo "<p class='success'>✅ Using /serve-image/ route (NEW CODE)</p>";                    

        } elseif (strpos($imageUrl, 'r2.cloudflarestorage.com') !== false) {                    <!-- Test Image Display -->

            echo "<p class='error'>❌ Using R2 direct URL (OLD CODE - BROKEN)</p>";                    <div class="text-center">

            echo "<p class='error'>This is why images show as text! R2 URLs return 400 error.</p>";                        <img src="<?php echo $imageUrl; ?>" 

        } else {                             alt="<?php echo htmlspecialchars($product->name); ?>" 

            echo "<p>⚠️ Unknown URL format</p>";                             class="img-test img-fluid"

        }                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">

                                <div style="display: none;" class="alert alert-danger">

        echo "<h4>Browser Test:</h4>";                            <i class="fas fa-exclamation-triangle"></i> Image failed to load

        echo "<img src='{$imageUrl}' alt='{$product->product_name}' onerror='this.style.border=\"3px solid red\"; this.alt=\"❌ IMAGE FAILED TO LOAD\";' onload='this.style.border=\"3px solid green\";'>";                        </div>

        echo "<p class='info'>Green border = image loaded ✅<br>Red border = image failed ❌</p>";                    </div>

        echo "</div>";                    

                            <!-- Direct storage test -->

        // Test ProductImage model too                    <div class="mt-2">

        $productImages = $product->productImages()->limit(1)->get();                        <small>

        if ($productImages->count() > 0) {                            <a href="<?php echo $imageUrl; ?>" target="_blank" class="btn btn-sm btn-outline-primary">

            echo "<div class='image-test'>";                                Open Image in New Tab

            echo "<h3>Testing ProductImage Model</h3>";                            </a>

            $img = $productImages->first();                        </small>

            echo "<p class='info'><strong>Image Path:</strong> <code>{$img->image_path}</code></p>";                    </div>

            $imgUrl = $img->image_url;                </div>

            echo "<p class='info'><strong>Generated URL:</strong><br><code>{$imgUrl}</code></p>";            </div>

                    </div>

            if (strpos($imgUrl, '/serve-image/') !== false) {        <?php endforeach; ?>

                echo "<p class='success'>✅ Using /serve-image/ route (NEW CODE)</p>";    </div>

            } else {    

                echo "<p class='error'>❌ Using old URL format</p>";    <hr>

            }    

                <h4>Storage Link Test</h4>

            echo "<img src='{$imgUrl}' alt='ProductImage' onerror='this.style.border=\"3px solid red\";' onload='this.style.border=\"3px solid green\";'>";    <div class="alert alert-info">

            echo "</div>";        <?php

        }        $storageLink = public_path('storage');

                if (is_link($storageLink)) {

    } else {            echo "✅ Storage symlink exists and points to: " . readlink($storageLink);

        echo "<p class='error'>No products found with images</p>";        } elseif (is_dir($storageLink)) {

    }            echo "⚠️ Storage exists as directory (not symlink)";

            } else {

    echo "</div>";            echo "❌ Storage symlink does not exist";

            }

    echo "<div class='test-box'>";        ?>

    echo "<h2>Environment Info</h2>";    </div>

    echo "<p><strong>Environment:</strong> " . app()->environment() . "</p>";    

    echo "<p><strong>APP_URL:</strong> " . config('app.url') . "</p>";    <h4>Direct File Access Test</h4>

    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";    <?php

    echo "</div>";    $testProduct = $products->first();

    ?>    if ($testProduct && $testProduct->image) {

            $testImagePath = $testProduct->image;

    <div class="test-box">        $directUrl = "/storage/" . $testImagePath;

        <h2>What This Test Shows</h2>        echo "<p>Testing direct access: <a href='{$directUrl}' target='_blank'>{$directUrl}</a></p>";

        <ul>    }

            <li>If images have <strong style="color:red;">RED border</strong> = Failed to load (broken URLs)</li>    ?>

            <li>If images have <strong style="color:green;">GREEN border</strong> = Successfully loaded ✅</li></div>

            <li>If using "R2 direct URL" = Old broken code still deployed</li>

            <li>If using "/serve-image/" = New fixed code deployed</li><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        </ul></body>

        <p><a href="/seller/dashboard">← Back to Dashboard</a></p></html>
    </div>
</body>
</html>
