@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar bg-light">
            <div class="sidebar-sticky">
                <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo" class="logoimg" width="150px">
                <ul class="nav flex-column mt-4">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('seller.dashboard') }}">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('seller.bulkImageReupload') }}">
                            <i class="bi bi-upload"></i> Bulk Image Re-upload
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-upload text-primary"></i> Bulk Image Re-upload
                </h1>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Instructions Card -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="bi bi-info-circle"></i> Instructions for Bulk Image Re-upload</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-folder-zip text-warning"></i> Zip File Format:</h6>
                            <ul>
                                <li>Create a ZIP file containing all product images</li>
                                <li>Image files should be named with product names or IDs</li>
                                <li>Supported formats: JPG, JPEG, PNG, GIF</li>
                                <li>Maximum zip size: 100MB</li>
                                <li>Individual image max size: 2MB</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-diagram-3 text-success"></i> Matching Process:</h6>
                            <ul>
                                <li>Images matched by product name similarity</li>
                                <li>Or by product unique ID in filename</li>
                                <li>Case-insensitive matching</li>
                                <li>Partial name matches supported</li>
                                <li>Unmatched images will be listed for manual assignment</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Note:</strong> This will re-upload images for products that currently show placeholder images due to missing cloud storage files.
                    </div>
                </div>
            </div>

            <!-- Your Products Needing Images -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-image text-danger"></i> Your Products Needing Images ({{ $productsNeedingImages->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($productsNeedingImages->count() > 0)
                        <div class="row">
                            @foreach($productsNeedingImages->take(12) as $product)
                                <div class="col-md-3 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-body text-center p-2">
                                            <div class="placeholder-image bg-light border" style="height: 80px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                            </div>
                                            <small class="text-muted mt-1 d-block">{{ Str::limit($product->name, 30) }}</small>
                                            <small class="badge bg-warning">ID: {{ $product->unique_id }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($productsNeedingImages->count() > 12)
                            <p class="text-muted">... and {{ $productsNeedingImages->count() - 12 }} more products</p>
                        @endif
                    @else
                        <div class="text-center text-success">
                            <i class="bi bi-check-circle" style="font-size: 3rem;"></i>
                            <p>All your products have images! No re-upload needed.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Upload Form -->
            @if($productsNeedingImages->count() > 0)
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="bi bi-upload"></i> Upload Zip File with Images</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('seller.processBulkImageReupload') }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        @csrf
                        
                        <!-- Category Selection -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">
                                    <i class="bi bi-tags"></i> Target Category (Optional)
                                </label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select a category to limit matching to products in that category only</small>
                            </div>
                            <div class="col-md-6">
                                <label for="matching_method" class="form-label">
                                    <i class="bi bi-gear"></i> Matching Method
                                </label>
                                <select class="form-control" id="matching_method" name="matching_method">
                                    <option value="name">Match by Product Name</option>
                                    <option value="unique_id">Match by Unique ID</option>
                                    <option value="both">Match by Both Name and ID</option>
                                </select>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label for="zip_file" class="form-label">
                                <i class="bi bi-file-zip"></i> Select Zip File
                            </label>
                            <input type="file" class="form-control @error('zip_file') is-invalid @enderror" 
                                   id="zip_file" name="zip_file" accept=".zip" required>
                            @error('zip_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> 
                                Select a ZIP file containing your product images. Max size: 100MB
                            </div>
                        </div>

                        <!-- Upload Progress -->
                        <div id="uploadProgress" class="mb-3" style="display: none;">
                            <label class="form-label">Upload Progress</label>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="progressText" class="text-muted">Preparing upload...</small>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="bi bi-upload"></i> Upload and Process Images
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg d-none" id="cancelBtn">
                                <i class="bi bi-x-circle"></i> Cancel Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 20px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    overflow-y: auto;
}

.main-content {
    margin-left: 16.66667%;
    padding: 0 20px;
}

.logoimg {
    display: block;
    margin: 0 auto 20px;
}

.nav-link {
    color: #333;
    font-weight: 500;
    padding: 10px 20px;
    border-radius: 5px;
    margin: 2px 10px;
    transition: all 0.3s;
}

.nav-link:hover, .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
    text-decoration: none;
}

.placeholder-image {
    border-radius: 8px;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

#bulkUploadForm {
    max-width: none;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    
    // Show progress
    progressDiv.style.display = 'block';
    submitBtn.classList.add('d-none');
    cancelBtn.classList.remove('d-none');
    
    // Simulate progress (since we can't track actual upload progress easily)
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress > 90) progress = 90; // Stop at 90% until actual completion
        
        progressBar.style.width = progress + '%';
        
        if (progress < 30) {
            progressText.textContent = 'Uploading zip file...';
        } else if (progress < 60) {
            progressText.textContent = 'Extracting images...';
        } else if (progress < 90) {
            progressText.textContent = 'Processing and matching images...';
        }
    }, 500);
    
    // Store interval reference to clear it if needed
    this.progressInterval = interval;
});

// File size validation
document.getElementById('zip_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const maxSize = 100 * 1024 * 1024; // 100MB
        if (file.size > maxSize) {
            alert('File size too large! Maximum allowed size is 100MB.');
            e.target.value = '';
        }
    }
});
</script>
@endsection