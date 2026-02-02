@extends('layouts.app')

@section('content')
<style>
.shadow-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>

<div class="container py-4">
    <h2 class="mb-4">Product Images by Seller/Category/Subcategory</h2>
    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="number" name="seller_id" class="form-control" placeholder="Seller ID" value="{{ request('seller_id') }}">
            </div>
            <div class="col-md-3">
                <input type="number" name="category_id" class="form-control" placeholder="Category ID" value="{{ request('category_id') }}">
            </div>
            <div class="col-md-3">
                <input type="number" name="subcategory_id" class="form-control" placeholder="Subcategory ID" value="{{ request('subcategory_id') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>
    @if($products->isEmpty())
        <div class="alert alert-info">No products found for the given filter.</div>
    @else
    <div class="row row-cols-1 row-cols-md-4 g-4">
        @foreach($products as $product)
        <div class="col">
            <a href="{{ route('product.details', $product->id) }}" class="text-decoration-none">
                <div class="card h-100 shadow-hover" style="transition: transform 0.2s; cursor: pointer;">
                    @if($product->image || $product->image_data)
                    <img src="{{ $product->image_url }}" class="card-img-top" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                    @else
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                        <span class="text-muted">No Image</span>
                    </div>
                    @endif
                    <div class="card-body">
                        <h5 class="card-title text-dark">{{ $product->name }}</h5>
                        <p class="card-text text-muted small">ID: {{ $product->id }}</p>
                        <p class="card-text text-muted small">Seller: {{ $product->seller_id }}</p>
                        <p class="card-text text-muted small">Category: {{ $product->category_id }}</p>
                        <p class="card-text text-muted small">Subcategory: {{ $product->subcategory_id }}</p>
                        @if($product->discount > 0)
                            <p class="card-text fw-bold text-success">₹{{ number_format($product->price * (1 - $product->discount / 100), 2) }}</p>
                            <small class="text-muted text-decoration-line-through">₹{{ number_format($product->price, 2) }}</small>
                            <small class="text-danger">({{ $product->discount }}% off)</small>
                        @else
                            <p class="card-text fw-bold text-success">₹{{ number_format($product->price, 2) }}</p>
                        @endif
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
