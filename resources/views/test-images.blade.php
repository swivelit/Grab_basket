<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Display Test - Grabbaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">🖼️ Product Image Display Test</h1>
        
        <div class="row">
            @php
                $products = \App\Models\Product::whereNotNull('image')
                    ->where('image', '!=', '')
                    ->take(12)
                    ->get();
            @endphp
            
            @foreach($products as $product)
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="position-relative" style="height: 200px; overflow: hidden;">
                            <img src="{{ $product->image_url }}" 
                                 alt="{{ $product->name }}" 
                                 class="card-img-top w-100 h-100" 
                                 style="object-fit: cover;"
                                 onload="console.log('✅ Image loaded:', this.src)"
                                 onerror="console.error('❌ Image failed:', this.src); this.src='https://via.placeholder.com/200?text=Error'; this.style.background='#f8f9fa';">
                        </div>
                        <div class="card-body p-2">
                            <h6 class="card-title small">{{ Str::limit($product->name, 40) }}</h6>
                            <small class="text-muted d-block">ID: {{ $product->id }}</small>
                            <small class="text-muted d-block">Image Path: {{ $product->image }}</small>
                            <small class="text-primary d-block">URL: {{ $product->image_url }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-5 p-4 bg-light rounded">
            <h3>🔧 Debug Information</h3>
            <ul class="list-unstyled">
                <li><strong>Total products with images:</strong> {{ \App\Models\Product::whereNotNull('image')->where('image', '!=', '')->count() }}</li>
                <li><strong>App URL:</strong> {{ config('app.url') }}</li>
                <li><strong>Environment:</strong> {{ app()->environment() }}</li>
                <li><strong>Storage disk:</strong> {{ config('filesystems.default') }}</li>
            </ul>
        </div>
    </div>

    <script>
        // Log image loading status
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img[src*="products/"]');
            console.log(`Found ${images.length} product images to test`);
            
            images.forEach((img, index) => {
                console.log(`Image ${index + 1}: ${img.src}`);
            });
        });
    </script>
</body>
</html>