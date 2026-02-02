@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="text-center mb-4">
                <h2>📷 Single Image Upload Test</h2>
                <p>Upload one image file for testing</p>
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

            <div class="card">
                <div class="card-body p-4">
                    <form action="{{ route('seller.processSingleImage') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Select Image File:</label>
                            <input type="file" name="image_file" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif,.webp" required>
                            <small class="text-muted">Supported: JPG, PNG, GIF, WebP</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Unique ID:</label>
                            <input type="text" name="unique_id" class="form-control" 
                                   placeholder="e.g., SRM721" required>
                            <small class="text-muted">Enter the SRM product ID for this image</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                🚀 Upload Image
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('seller.simpleUpload') }}" class="btn btn-outline-secondary">
                    ← Back to Bulk Upload
                </a>
            </div>

        </div>
    </div>
</div>
@endsection