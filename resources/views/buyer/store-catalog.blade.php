<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seller->store_name ?? $seller->name }} - Store Catalog</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0C831F;
            --secondary-color: #F8CB46;
            --bg-light: #f8f9fa;
        }

        body {
            background-color: var(--bg-light);
        }

        .store-header {
            background: linear-gradient(135deg, var(--primary-color), #0A6917);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 24px 24px;
        }

        .store-info-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-card-modern {
            background: white;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(12, 131, 31, 0.15);
        }

        .product-image-modern {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 8px;
            margin-bottom: 12px;
        }

        .product-discount-modern {
            position: absolute;
            top: 16px;
            right: 16px;
            background: linear-gradient(135deg, #ff6b00, #ff9800);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 5;
        }

        .product-title-modern {
            font-size: 0.875rem;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price-modern {
            margin-bottom: 12px;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .original-price {
            font-size: 0.875rem;
            color: #95a5a6;
            text-decoration: line-through;
            margin-left: 8px;
        }

        .add-to-cart-modern {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, var(--primary-color), #0A6917);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .add-to-cart-modern:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(12, 131, 31, 0.3);
        }

        .products-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        @media (max-width: 768px) {
            .products-grid-modern {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <x-back-button />

    <!-- Store Header -->
    <div class="store-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-shop-window"></i> {{ $seller->store_name ?? $seller->name }}
                    </h1>
                    @if($seller->store_name && $seller->name && $seller->store_name !== $seller->name)
                        <p class="mb-0 opacity-75">Owned by {{ $seller->name }}</p>
                    @endif
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="d-inline-block bg-white text-dark px-4 py-2 rounded-pill">
                        <i class="bi bi-box-seam"></i> <strong>{{ $totalProducts }}</strong> Products
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Store Information Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="store-info-card">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <h5 class="mb-3"><i class="bi bi-info-circle-fill text-primary"></i> Store Details</h5>
                            @if($seller->store_address)
                                <p class="mb-2">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> 
                                    <strong>Address:</strong><br>
                                    <span class="ms-4">{{ $seller->store_address }}</span>
                                </p>
                            @endif
                            @if($seller->city || $seller->state || $seller->pincode)
                                <p class="mb-2">
                                    <i class="bi bi-pin-map-fill text-info"></i> 
                                    <strong>Location:</strong><br>
                                    <span class="ms-4">
                                        {{ implode(', ', array_filter([$seller->city, $seller->state, $seller->pincode])) }}
                                    </span>
                                </p>
                            @endif
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <h5 class="mb-3"><i class="bi bi-telephone-fill text-success"></i> Contact</h5>
                            @if($seller->store_contact)
                                <p class="mb-2">
                                    <i class="bi bi-phone"></i> 
                                    <strong>Phone:</strong> {{ $seller->store_contact }}
                                </p>
                            @endif
                            @if($seller->email)
                                <p class="mb-2">
                                    <i class="bi bi-envelope"></i> 
                                    <strong>Email:</strong> {{ $seller->email }}
                                </p>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-3"><i class="bi bi-file-text-fill text-warning"></i> Business Info</h5>
                            @if($seller->gst_number)
                                <p class="mb-2">
                                    <i class="bi bi-receipt"></i> 
                                    <strong>GST:</strong> {{ $seller->gst_number }}
                                </p>
                            @endif
                            @if($seller->gift_option)
                                <p class="mb-2">
                                    <i class="bi bi-gift"></i> 
                                    <span class="badge bg-success">Gift Wrapping Available</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sort Options -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Store Catalog</h4>
            <form method="GET" class="d-flex align-items-center">
                <label class="me-2 mb-0">Sort by:</label>
                <select name="sort" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="discount" {{ request('sort') === 'discount' ? 'selected' : '' }}>Highest Discount</option>
                </select>
            </form>
        </div>

        <!-- Products Grid -->
        @if($products->count() > 0)
            <div class="products-grid-modern">
                @foreach($products as $product)
                    <div class="product-card-modern" onclick="window.location.href='{{ route('product.details', $product->id) }}'">
                        <!-- Discount Badge -->
                        @if($product->discount > 0)
                            <div class="product-discount-modern">
                                {{ (int)$product->discount }}% OFF
                            </div>
                        @endif

                        <!-- Product Image -->
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            class="product-image-modern"
                            data-fallback="{{ asset('images/no-image.png') }}"
                            onerror="this.src=this.dataset.fallback"
                            loading="lazy">

                        <!-- Product Info -->
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            <div class="product-title-modern">{{ $product->name }}</div>
                            
                            <!-- Price Section -->
                            <div class="product-price-modern">
                                @if($product->discount > 0)
                                    <span class="current-price">₹{{ number_format($product->price * (1 - $product->discount / 100), 2) }}</span>
                                    <span class="original-price">₹{{ number_format($product->price, 2) }}</span>
                                @else
                                    <span class="current-price">₹{{ number_format($product->price, 2) }}</span>
                                @endif
                            </div>

                            <!-- Add to Cart Button -->
                            @auth
                                <button class="add-to-cart-modern" onclick="event.stopPropagation(); document.getElementById('quick-add-{{ $product->id }}').submit();">
                                    <i class="bi bi-cart-plus"></i>
                                    Add to Cart
                                </button>
                                <form id="quick-add-{{ $product->id }}" method="POST" action="{{ route('cart.add') }}" style="display: none;">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                </form>
                            @else
                                <button class="add-to-cart-modern" onclick="event.stopPropagation(); window.location.href='{{ route('login') }}';">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Login to Buy
                                </button>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h4 class="mt-3">No Products Available</h4>
                <p class="text-muted">This store doesn't have any products listed yet.</p>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
