<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Gallery - {{ $product->name }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .image-card {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .image-card.primary {
            border-color: #198754;
            background: linear-gradient(135deg, #d1e7dd 0%, #f8f9fa 100%);
        }
        .primary-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #198754;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .image-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .image-card:hover .image-actions {
            opacity: 1;
        }
        .upload-area {
            border: 2px dashed #6c757d;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9ff;
        }
        .upload-area.dragover {
            border-color: #198754;
            background-color: #d1e7dd;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-images text-primary me-2"></i>
                            Product Gallery
                        </h2>
                        <p class="text-muted mb-0">{{ $product->name }}</p>
                    </div>
                    <a href="{{ route('seller.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-cloud-upload me-2"></i>
                            Upload New Images
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('seller.uploadProductImages', $product) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                            @csrf
                            <div class="upload-area" id="uploadArea">
                                <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                                <h5 class="text-muted">Drag & Drop Images Here</h5>
                                <p class="text-muted mb-3">or click to browse files</p>
                                <input type="file" class="form-control d-none" id="images" name="images[]" multiple accept="image/*" required>
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('images').click()">
                                    <i class="bi bi-folder2-open me-1"></i>Browse Files
                                </button>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Supports: JPEG, PNG, GIF, WebP | Max: 5MB per image | Up to 10 images
                                    </small>
                                </div>
                            </div>
                            
                            <div id="selectedFiles" class="mt-3 d-none">
                                <h6>Selected Files:</h6>
                                <div id="fileList"></div>
                                <button type="submit" class="btn btn-success mt-3">
                                    <i class="bi bi-upload me-1"></i>Upload Images
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Images -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-collection me-2"></i>
                            Current Images ({{ count($images) }})
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(count($images) > 0)
                            <div class="row g-4">
                                @foreach($images as $image)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="image-card {{ $image->is_primary ? 'primary' : '' }}">
                                            @if($image->is_primary)
                                                <div class="primary-badge">
                                                    <i class="bi bi-star-fill me-1"></i>Primary
                                                </div>
                                            @endif
                                            
                                            <div class="image-actions">
                                                @if(!$image->is_primary)
                                                    <form action="{{ route('seller.setPrimaryImage', $image) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-success" title="Set as Primary">
                                                            <i class="bi bi-star"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                <form action="{{ route('seller.deleteProductImage', $image) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this image?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Image">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <img src="{{ $image->image_url }}" 
                                                 alt="{{ $image->original_name }}" 
                                                 class="card-img-top" 
                                                 style="height: 200px; object-fit: cover;">
                                            
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1" title="{{ $image->original_name }}">
                                                    {{ Str::limit($image->original_name, 20) }}
                                                </h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        {{ $image->formatted_file_size }}<br>
                                                        {{ $image->created_at->format('M d, Y') }}
                                                    </small>
                                                </p>
                                                <a href="{{ $image->original_url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-box-arrow-up-right"></i> View original
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-images display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No images uploaded yet</h5>
                                <p class="text-muted">Upload some images to create a product gallery</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('images');
            const selectedFiles = document.getElementById('selectedFiles');
            const fileList = document.getElementById('fileList');

            // Click to upload
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });

            // Drag and drop functionality
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                fileInput.files = files;
                displaySelectedFiles(files);
            });

            // File input change
            fileInput.addEventListener('change', function() {
                displaySelectedFiles(this.files);
            });

            function displaySelectedFiles(files) {
                if (files.length > 0) {
                    selectedFiles.classList.remove('d-none');
                    fileList.innerHTML = '';
                    
                    Array.from(files).forEach(function(file, index) {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'alert alert-info d-flex justify-content-between align-items-center mb-2';
                        fileItem.innerHTML = `
                            <div>
                                <i class="bi bi-file-earmark-image me-2"></i>
                                <strong>${file.name}</strong>
                                <small class="text-muted ms-2">(${formatFileSize(file.size)})</small>
                            </div>
                        `;
                        fileList.appendChild(fileItem);
                    });
                } else {
                    selectedFiles.classList.add('d-none');
                }
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        });
    </script>
</body>
</html>