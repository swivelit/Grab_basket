<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Image Library - Grabbaskets</title>
    
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header-card h1 {
            color: #667eea;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .upload-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .dropzone {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 60px 20px;
            text-align: center;
            background: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .dropzone:hover {
            background: #eff1ff;
            border-color: #764ba2;
        }
        .dropzone.dragover {
            background: #e8eaff;
            border-color: #764ba2;
            transform: scale(1.02);
        }
        .dropzone i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        .gallery-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            background: #f8f9fa;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .image-card .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .image-card:hover .overlay {
            opacity: 1;
        }
        .image-card .actions {
            display: flex;
            gap: 10px;
        }
        .image-card .btn {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .upload-progress {
            display: none;
            margin-top: 20px;
        }
        .image-info {
            padding: 10px;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
        }
        .image-info strong {
            display: block;
            color: #333;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #667eea;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <h1>
                    <i class="fas fa-images"></i>
                    My Image Library
                </h1>
                <a href="{{ route('seller.dashboard') }}" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Upload Section -->
        <div class="upload-section">
            <h3 class="mb-4"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Images</h3>
            <form id="uploadForm" action="{{ route('seller.uploadToLibrary') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="dropzone" id="dropzone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drag & Drop Images Here</h4>
                    <p class="text-muted">or click to browse</p>
                    <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display: none;">
                    <button type="button" class="btn btn-gradient mt-3" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-folder-open me-2"></i>Choose Files
                    </button>
                </div>
                
                <div id="selectedFiles" class="mt-3"></div>
                
                <div class="upload-progress">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <p class="text-center mt-2" id="uploadStatus">Uploading...</p>
                </div>

                <button type="submit" id="uploadBtn" class="btn btn-gradient mt-3" style="display: none;">
                    <i class="fas fa-upload me-2"></i>Upload Selected Images
                </button>
            </form>
        </div>

        <!-- Gallery Section -->
        <div class="gallery-section">
            <h3 class="mb-4"><i class="fas fa-th me-2"></i>My Uploaded Images</h3>
            
            <div id="imageGallery" class="image-grid">
                @forelse($images as $image)
                    <div class="image-card" data-image-path="{{ $image['path'] }}">
                        <img src="{{ $image['url'] }}" alt="{{ $image['name'] }}" loading="lazy">
                        <div class="overlay">
                            <div class="actions">
                                <button class="btn btn-light btn-sm" onclick="copyUrl('{{ $image['url'] }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteImage('{{ $image['path'] }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="image-info">
                            <strong>{{ $image['name'] }}</strong>
                            <small>{{ $image['size'] }}</small>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <i class="fas fa-images"></i>
                        <h4>No Images Yet</h4>
                        <p>Upload images above to build your library</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const selectedFiles = document.getElementById('selectedFiles');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const uploadProgress = document.querySelector('.upload-progress');

        // Drag and drop
        dropzone.addEventListener('click', () => fileInput.click());
        
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            displaySelectedFiles();
        });

        fileInput.addEventListener('change', displaySelectedFiles);

        function displaySelectedFiles() {
            const files = fileInput.files;
            if (files.length === 0) {
                selectedFiles.innerHTML = '';
                uploadBtn.style.display = 'none';
                return;
            }

            let html = '<div class="alert alert-info"><strong>Selected Files:</strong><ul class="mb-0 mt-2">';
            for (let file of files) {
                const size = (file.size / 1024).toFixed(2);
                html += `<li>${file.name} (${size} KB)</li>`;
            }
            html += '</ul></div>';
            selectedFiles.innerHTML = html;
            uploadBtn.style.display = 'block';
        }

        // Form submission with progress
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (fileInput.files.length === 0) {
                alert('Please select images to upload');
                return;
            }

            const formData = new FormData(uploadForm);
            uploadProgress.style.display = 'block';
            uploadBtn.disabled = true;

            try {
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Upload failed. Please try again.');
                    uploadProgress.style.display = 'none';
                    uploadBtn.disabled = false;
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
                uploadProgress.style.display = 'none';
                uploadBtn.disabled = false;
            }
        });

        // Copy URL to clipboard
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Image URL copied to clipboard!');
            });
        }

        // Delete image
        function deleteImage(path) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            fetch('{{ route("seller.deleteLibraryImage") }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ path: path })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete image');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('Failed to delete image');
            });
        }
    </script>
</body>
</html>
