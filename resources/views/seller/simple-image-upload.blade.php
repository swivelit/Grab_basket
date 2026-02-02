@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="text-center mb-4">
                <h2>📁 Simple Image Upload</h2>
                <p>Upload images for your SRM products</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    ❌ {{ session('error') }}
                </div>
            @endif

            <!-- Ultra Simple Form -->
            <div class="card">
                <div class="card-body p-4">
                    <form action="{{ route('seller.processSimpleUpload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fs-5">Select ZIP File with Images:</label>
                            <input type="file" name="zip_file" class="form-control form-control-lg" 
                                   accept=".zip" required>
                            <small class="text-muted">Name your images: SRM721.jpg, SRM722.png, etc.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                🚀 Upload Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Status -->
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>📊 Upload Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h4 class="text-primary">{{ $totalProducts }}</h4>
                                    <small>Total Products</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h4 class="text-success">{{ $productsWithImages }}</h4>
                                    <small>Have Images</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h4 class="text-danger">{{ $productsNeedingImages }}</h4>
                                    <small>Need Images</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recently Uploaded -->
            @if($recentlyUploaded->count() > 0)
            <div class="mt-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>✅ Recently Uploaded Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($recentlyUploaded as $product)
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $product->image_url }}" class="rounded me-2" 
                                         style="width: 50px; height: 50px; object-fit: cover;" 
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjZjhmOWZhIi8+CjxwYXRoIGQ9Ik0yNSAxNWMtNS41IDAtMTAgNC41LTEwIDEwczQuNSAxMCAxMCAxMCAxMC00LjUgMTAtMTAtNC41LTEwLTEwLTEwem0wIDE2Yy0zLjMgMC02LTIuNy02LTZzMi43LTYgNi02IDYgMi43IDYgNi0yLjcgNi02IDZ6IiBmaWxsPSIjZGVlMmU2Ii8+Cjwvc3ZnPgo='">
                                    <div>
                                        <strong class="text-success">{{ $product->unique_id }}</strong><br>
                                        <small class="text-muted">{{ Str::limit($product->name, 30) }}</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Products Still Needing Images -->
            @if($productsStillNeedingImages->count() > 0)
            <div class="mt-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5>⚠️ Still Need Images ({{ $productsStillNeedingImages->count() }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($productsStillNeedingImages->take(20) as $product)
                            <div class="col-md-3 mb-2">
                                <div class="border rounded p-2 text-center">
                                    <strong class="text-primary">{{ $product->unique_id }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($product->name, 20) }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @if($productsStillNeedingImages->count() > 20)
                            <p class="text-muted mt-2">... and {{ $productsStillNeedingImages->count() - 20 }} more</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</div>
@endsection