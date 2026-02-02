@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <!-- Header -->
            <div class="text-center mb-4">
                <h2><i class="bi bi-cloud-upload text-primary"></i> Bulk Image Re-upload</h2>
                <p class="text-muted">Upload ZIP file containing product images for SRM721, SRM722, etc.</p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Upload Form -->
            <div class="card shadow">
                <div class="card-body p-4">
                    
                    <!-- Quick Guide -->
                    <div class="alert alert-info mb-4">
                        <h6><i class="bi bi-info-circle"></i> Quick Guide:</h6>
                        <ul class="mb-0">
                            <li><strong>Name your images:</strong> SRM721.jpg, SRM722.png, SRM723.jpg, etc.</li>
                            <li><strong>Create ZIP file:</strong> Select all images and compress to .zip</li>
                            <li><strong>Upload:</strong> Choose your ZIP file and click "Upload Images"</li>
                            <li><strong>Supported formats:</strong> JPG, PNG, GIF, WebP</li>
                        </ul>
                    </div>

                    <form action="{{ route('seller.processBulkImageReupload') }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        @csrf
                        
                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="zip_file" class="form-label">
                                <i class="bi bi-file-zip"></i> Select ZIP File
                            </label>
                            <input type="file" class="form-control form-control-lg @error('zip_file') is-invalid @enderror" 
                                   id="zip_file" name="zip_file" accept=".zip" required>
                            @error('zip_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum file size: 100MB</div>
                        </div>

                        <!-- Matching Method -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-search"></i> Image Matching Method
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="matching_method" 
                                               id="unique_id" value="unique_id" checked>
                                        <label class="form-check-label" for="unique_id">
                                            <strong>Unique ID Matching</strong><br>
                                            <small class="text-muted">For files named: SRM721.jpg, SRM722.png, etc.</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="matching_method" 
                                               id="name" value="name">
                                        <label class="form-check-label" for="name">
                                            <strong>Product Name Matching</strong><br>
                                            <small class="text-muted">For files named like product names</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Category Filter (Optional) -->
                        <div class="mb-4">
                            <label for="category_id" class="form-label">
                                <i class="bi bi-funnel"></i> Category Filter (Optional)
                            </label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Leave empty to search all products</div>
                        </div>

                        <!-- Upload Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="uploadBtn">
                                <i class="bi bi-cloud-upload"></i> Upload Images
                            </button>
                        </div>
                    </form>

                    <!-- Progress Bar (Hidden by default) -->
                    <div id="uploadProgress" class="mt-4" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="text-center mt-2">
                            <small id="progressText">Uploading...</small>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Products Preview -->
            @if($productsNeedingImages->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="bi bi-list-ul"></i> Products Needing Images ({{ $productsNeedingImages->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($productsNeedingImages->take(12) as $product)
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2">
                                <small class="text-primary fw-bold">{{ $product->unique_id }}</small><br>
                                <small class="text-muted">{{ Str::limit($product->name, 25) }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($productsNeedingImages->count() > 12)
                        <small class="text-muted">... and {{ $productsNeedingImages->count() - 12 }} more products</small>
                    @endif
                </div>
            </div>
            @endif

            <!-- Back Button -->
            <div class="text-center mt-4">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 10px;
}

.form-control-lg {
    padding: 12px 16px;
}

.btn-lg {
    padding: 12px 24px;
    font-size: 1.1rem;
}

.progress {
    height: 8px;
}

.alert {
    border-radius: 8px;
}
</style>

<script>
document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
    const uploadBtn = document.getElementById('uploadBtn');
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    
    // Show progress
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    progressDiv.style.display = 'block';
    
    // Simulate progress (since we can't track real upload progress)
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 90) progress = 90;
        
        progressBar.style.width = progress + '%';
        
        if (progress < 30) {
            progressText.textContent = 'Extracting ZIP file...';
        } else if (progress < 60) {
            progressText.textContent = 'Matching images to products...';
        } else {
            progressText.textContent = 'Uploading to cloud storage...';
        }
    }, 500);
    
    // Clean up interval after 30 seconds (fallback)
    setTimeout(() => {
        clearInterval(interval);
        progressBar.style.width = '100%';
        progressText.textContent = 'Almost done...';
    }, 30000);
});
</script>
@endsection