<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Catalog - {{ $seller->business_name ?? $seller->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .header .subtitle {
            font-size: 12px;
            opacity: 0.95;
            margin-bottom: 5px;
        }
        
        .header .date {
            font-size: 10px;
            opacity: 0.85;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            text-align: center;
        }
        
        .stat-card .label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .category-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .category-header {
            background: #667eea;
            color: white;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .category-count {
            float: right;
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .product-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            page-break-inside: avoid;
        }
        
        .product-image-container {
            width: 100%;
            height: 150px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #999;
            font-size: 12px;
            text-align: center;
        }
        
        .product-details {
            padding: 12px;
        }
        
        .product-name {
            font-size: 12px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 6px;
            min-height: 32px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 9px;
        }
        
        .product-info-label {
            color: #666;
            font-weight: normal;
        }
        
        .product-info-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .price-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #e0e0e0;
        }
        
        .current-price {
            font-size: 14px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .original-price {
            font-size: 10px;
            color: #999;
            text-decoration: line-through;
        }
        
        .discount-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .stock-high {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f39c12;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #667eea;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        
        .footer .brand {
            font-weight: bold;
            color: #667eea;
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Additional product metadata */
        .product-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
            margin-top: 6px;
            font-size: 8px;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        
        .meta-label {
            color: #999;
        }
        
        .meta-value {
            color: #555;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>📦 Product Catalog</h1>
        <div class="subtitle">{{ $seller->business_name ?? $seller->name }}</div>
        <div class="date">📅 Exported on {{ $exportDate->format('F d, Y - h:i A') }}</div>
    </div>

    <!-- Statistics Summary -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Products</div>
            <div class="value">{{ $stats['total_products'] }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Categories</div>
            <div class="value">{{ $stats['total_categories'] }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Total Stock</div>
            <div class="value">{{ number_format($stats['total_stock']) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Active Products</div>
            <div class="value" style="color: #27ae60;">{{ $stats['active_products'] }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Inventory Value</div>
            <div class="value" style="color: #667eea;">₹{{ number_format($stats['total_value'], 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Out of Stock</div>
            <div class="value" style="color: #e74c3c;">{{ $stats['out_of_stock'] }}</div>
        </div>
    </div>

    <!-- Products by Category -->
    @foreach($productsByCategory as $categoryName => $products)
    <div class="category-section">
        <div class="category-header">
            {{ $categoryName }}
            <span class="category-count">{{ $products->count() }} {{ Str::plural('Product', $products->count()) }}</span>
        </div>

        <div class="product-grid">
            @foreach($products as $product)
            <div class="product-card">
                <!-- Product Image -->
                <div class="product-image-container">
                    @php
                        $imageUrl = null;
                        $imageData = null;
                        
                        // Try to get primary image from images relationship
                        if ($product->images && $product->images->count() > 0) {
                            $primaryImage = $product->images->where('is_primary', true)->first() 
                                ?? $product->images->first();
                            if ($primaryImage && $primaryImage->image_path) {
                                $imageUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/' . $primaryImage->image_path;
                            }
                        }
                        
                        // Fallback to legacy image field
                        if (!$imageUrl && $product->image) {
                            if (filter_var($product->image, FILTER_VALIDATE_URL)) {
                                $imageUrl = $product->image;
                            } else {
                                $imageUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/' . $product->image;
                            }
                        }
                        
                        // Try to convert image to base64 for better PDF compatibility
                        if ($imageUrl) {
                            try {
                                // Set context options to handle HTTPS and timeouts
                                $context = stream_context_create([
                                    'http' => [
                                        'timeout' => 10,
                                        'ignore_errors' => true
                                    ],
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                    ]
                                ]);
                                
                                $imageContent = @file_get_contents($imageUrl, false, $context);
                                
                                if ($imageContent !== false) {
                                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                                    $mimeType = $finfo->buffer($imageContent);
                                    $imageData = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                                }
                            } catch (\Exception $e) {
                                // If conversion fails, fall back to direct URL
                                \Log::warning('Image conversion failed for PDF', [
                                    'product_id' => $product->id,
                                    'url' => $imageUrl,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    @endphp

                    @if($imageData)
                        <img src="{{ $imageData }}" alt="{{ $product->name }}" class="product-image">
                    @elseif($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="product-image">
                    @else
                        <div class="no-image">
                            📦<br>No Image<br>Available
                        </div>
                    @endif

                    @if($product->featured)
                    <div class="featured-badge">⭐ FEATURED</div>
                    @endif
                </div>

                <!-- Product Details -->
                <div class="product-details">
                    <div class="product-name">{{ $product->name }}</div>

                    <div class="product-info-row">
                        <span class="product-info-label">Product ID:</span>
                        <span class="product-info-value">{{ $product->unique_id ?? $product->id }}</span>
                    </div>

                    @if($product->sku)
                    <div class="product-info-row">
                        <span class="product-info-label">SKU:</span>
                        <span class="product-info-value">{{ $product->sku }}</span>
                    </div>
                    @endif

                    @if($product->subcategory)
                    <div class="product-info-row">
                        <span class="product-info-label">Subcategory:</span>
                        <span class="product-info-value">{{ $product->subcategory->name }}</span>
                    </div>
                    @endif

                    @if($product->brand)
                    <div class="product-info-row">
                        <span class="product-info-label">Brand:</span>
                        <span class="product-info-value">{{ $product->brand }}</span>
                    </div>
                    @endif

                    <!-- Price Section -->
                    <div class="price-section">
                        <div>
                            <div class="current-price">₹{{ number_format($product->price, 2) }}</div>
                        </div>
                        @if($product->discount && $product->discount > 0)
                        <div class="discount-badge">{{ $product->discount }}% OFF</div>
                        @endif
                    </div>

                    <!-- Stock Badge -->
                    <div style="margin-top: 8px;">
                        @if($product->stock <= 0)
                        <span class="stock-badge stock-out">OUT OF STOCK</span>
                        @elseif($product->stock <= 5)
                        <span class="stock-badge stock-low">Low Stock: {{ $product->stock }}</span>
                        @elseif($product->stock <= 20)
                        <span class="stock-badge stock-medium">Stock: {{ $product->stock }}</span>
                        @else
                        <span class="stock-badge stock-high">In Stock: {{ $product->stock }}</span>
                        @endif
                    </div>

                    <!-- Additional Metadata -->
                    @if($product->weight || $product->dimensions || $product->color || $product->size)
                    <div class="product-meta">
                        @if($product->weight)
                        <div class="meta-item">
                            <span class="meta-label">Weight:</span>
                            <span class="meta-value">{{ $product->weight }} kg</span>
                        </div>
                        @endif
                        
                        @if($product->color)
                        <div class="meta-item">
                            <span class="meta-label">Color:</span>
                            <span class="meta-value">{{ $product->color }}</span>
                        </div>
                        @endif
                        
                        @if($product->size)
                        <div class="meta-item">
                            <span class="meta-label">Size:</span>
                            <span class="meta-value">{{ $product->size }}</span>
                        </div>
                        @endif
                        
                        @if($product->dimensions)
                        <div class="meta-item">
                            <span class="meta-label">Dimensions:</span>
                            <span class="meta-value">{{ $product->dimensions }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif
    @endforeach

    <!-- Footer -->
    <div class="footer">
        <div class="brand">🛒 GrabBaskets E-Commerce Platform</div>
        <div>Generated on {{ now()->format('F d, Y h:i A') }} | © {{ date('Y') }} All Rights Reserved</div>
        <div style="margin-top: 5px;">This catalog contains confidential business information</div>
    </div>
</body>
</html>
