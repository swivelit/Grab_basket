@extends('seller.layouts.app')

@section('title', 'Seller Dashboard')

@section('content')
<!-- Dashboard Header -->
<div class="dashboard-header">
    @php
    $user = Auth::user();
    $dashboardPhoto = $user && $user->profile_picture
    ? $user->profile_picture
    : asset('asset/images/grabbasket.png');
    @endphp
    <img src="{{ $dashboardPhoto }}" alt="Seller Profile">
    <h2>Welcome, {{ Auth::user()->name ?? 'Seller' }}!</h2>
    <p class="mb-0">Here's an overview of your store performance.</p>
</div>

<!-- Stats -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card stat-card text-center p-3 bg-light">
            <div class="text-primary fs-2"><i class="bi bi-currency-dollar"></i></div>
            <h6>Revenue</h6>
            <p class="display-6 fw-bold">
                ₹{{ number_format(\App\Models\Order::where('seller_id', Auth::id())->sum('amount'), 2) }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3 bg-light">
            <div class="text-success fs-2"><i class="bi bi-box-seam"></i></div>
            <h6>Products</h6>
            <p class="display-6 fw-bold">{{ $products->count() }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3 bg-light">
            <div class="text-warning fs-2"><i class="bi bi-cart-check"></i></div>
            <h6>Orders</h6>
            <p class="display-6 fw-bold">{{ \App\Models\Order::where('seller_id', Auth::id())->count() }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3 bg-light">
            <div class="text-warning fs-2"><i class="bi bi-star-fill"></i></div>
            <h6>Reviews</h6>
            <p class="display-6 fw-bold">
                {{ \App\Models\Review::whereIn('product_id', $products->pluck('id'))->count() }}
            </p>
        </div>
    </div>
</div>
<div class="search-bar mb-4 col-md-8 mx-auto">
    <form action="{{ route('seller.dashboard') }}" method="GET">
        <input type="text" name="search" placeholder="Search products, orders, or reviews..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
    </form>
</div>
<!-- Products Table -->
<div class="orders-table p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-clock-history"></i> Your Products</h4>
        <div class="btn-group" role="group">
            <a href="{{ route('seller.importExport') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-down-up"></i> Import/Export
            </a>
            <form action="{{ route('seller.products.export.excel') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Quick Export
                </button>
            </form>
        </div>
    </div>
    @if(isset($products) && $products->count())
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Unique ID</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Price</th>
                    <th>Discount</th>
                    <th>Delivery</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                <tr>
                    <td>
                        @if($p->image_url)
                        <a href="{{ route('product.details', $p->id) }}" class="d-inline-block">
                            <img src="{{ $p->image_url }}"
                                alt="{{ $p->name }}"
                                style="height:48px; width:48px; object-fit:cover; border-radius:8px; border:1px solid #eee; cursor:pointer; transition: transform 0.2s;"
                                onmouseover="this.style.transform='scale(1.1)'"
                                onmouseout="this.style.transform='scale(1)'"
                                onerror="this.onerror=null; if(this.src.includes('githubusercontent.com')) { const path = this.src.split('/storage/app/public/')[1]; this.src = '{{ url('/serve-image/') }}/' + path.split('/')[0] + '/' + path.split('/').slice(1).join('/'); }">
                        </a>
                        @endif
                        @if($p->image)
                        <div class="mt-1 small text-secondary">Legacy: <span style="word-break:break-all">{{ $p->image }}</span></div>
                        @endif
                    </td>
                    <td><a href="{{ route('product.details', $p->id) }}" class="text-decoration-none text-dark">{{ $p->name }}</a></td>
                    <td><span class="badge bg-secondary">{{ $p->unique_id ?? '-' }}</span></td>
                    <td>{{ optional($p->category)->name ?? '-' }}</td>
                    <td>{{ optional($p->subcategory)->name ?? '-' }}</td>
                    <td>₹{{ number_format($p->price, 2) }}</td>
                    <td>{{ $p->discount ? $p->discount . '%' : '-' }}</td>
                    <td>{{ $p->delivery_charge ? '₹' . number_format($p->delivery_charge, 2) : 'Free' }}</td>
                    <td>{{ $p->created_at?->format('d M Y') }}</td>
                    <td class="d-flex gap-2">
                        <a href="{{ route('seller.editProduct', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="{{ route('seller.productGallery', $p) }}" class="btn btn-sm btn-outline-info">
                            Gallery
                            @if($p->productImages->count() > 0)
                            <span class="badge bg-info">{{ $p->productImages->count() }}</span>
                            @endif
                        </a>
                        <form action="{{ route('seller.destroyProduct', $p) }}" method="POST" onsubmit="return confirm('Delete this product? This will remove its images too.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="mb-0">You haven't added any products yet.</p>
    @endif
</div>

<!-- Update Product Images by ZIP -->
<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-images me-2"></i>Update Product Images by ZIP
        </div>
        <div class="card-body">
            <form action="{{ route('seller.updateImagesByZip') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="images_zip" class="form-label fw-bold">Upload ZIP File (image filenames must match
                        product unique IDs)</label>
                    <input type="file" class="form-control" id="images_zip" name="images_zip" accept=".zip"
                        required>
                    <div class="form-text">Each image filename (without extension) must match a product's unique
                        ID. Example: <code>PROD-123.jpg</code> will update the image for product with unique ID
                        <code>PROD-123</code>.
                    </div>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload me-1"></i>Upload & Update Images
                </button>
            </form>
        </div>
    </div>
</div>
@endsection