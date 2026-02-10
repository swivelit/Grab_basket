{{-- Image Library Modal Component --}}
<div class="modal fade" id="imageLibraryModal" tabindex="-1" aria-labelledby="imageLibraryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="imageLibraryModalLabel">
                    <i class="fas fa-images me-2"></i>Select Image from Library
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Click on an image to select it for your product. 
                            <a href="{{ route('seller.imageLibrary') }}" target="_blank" class="text-decoration-none">
                                Manage your library <i class="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <input type="text" id="imageSearchInput" class="form-control form-control-sm" placeholder="Search images...">
                    </div>
                </div>

                <div id="libraryImagesContainer" class="row g-3">
                    <!-- Images will be loaded here -->
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading images...</p>
                    </div>
                </div>

                <div id="noImagesMessage" class="text-center py-5" style="display: none;">
                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No images in your library yet.</p>
                    <a href="{{ route('seller.imageLibrary') }}" class="btn btn-primary" target="_blank">
                        <i class="fas fa-upload me-2"></i>Upload Images
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .library-image-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 3px solid transparent;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    .library-image-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .library-image-card.selected {
        border-color: #667eea;
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }
    .library-image-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    .library-image-card .selected-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #667eea;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        display: none;
    }
    .library-image-card.selected .selected-badge {
        display: block;
    }
    .image-info-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        color: white;
        padding: 10px;
        font-size: 0.85rem;
    }
</style>

<script>
    let libraryImages = [];
    let selectedImageUrl = null;

    // Load library images when modal is opened
    document.getElementById('imageLibraryModal').addEventListener('shown.bs.modal', function() {
        loadLibraryImages();
    });

    async function loadLibraryImages() {
        const container = document.getElementById('libraryImagesContainer');
        const noImagesMessage = document.getElementById('noImagesMessage');
        
        try {
            const response = await fetch('{{ route("seller.getLibraryImages") }}');
            const data = await response.json();
            libraryImages = data.images;

            if (libraryImages.length === 0) {
                container.style.display = 'none';
                noImagesMessage.style.display = 'block';
                return;
            }

            container.innerHTML = '';
            container.style.display = 'flex';
            noImagesMessage.style.display = 'none';

            libraryImages.forEach((image, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-sm-4 col-6';
                col.innerHTML = `
                    <div class="library-image-card" onclick="selectLibraryImage('${image.url}', ${index})">
                        <img src="${image.url}" alt="${image.name}" loading="lazy">
                        <div class="selected-badge">
                            <i class="fas fa-check"></i> Selected
                        </div>
                        <div class="image-info-overlay">
                            <strong>${truncateText(image.name, 20)}</strong>
                        </div>
                    </div>
                `;
                container.appendChild(col);
            });

        } catch (error) {
            console.error('Failed to load library images:', error);
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                    <p class="text-danger">Failed to load images. Please try again.</p>
                </div>
            `;
        }
    }

    function selectLibraryImage(url, index) {
        // Remove previous selection
        document.querySelectorAll('.library-image-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Mark as selected
        document.querySelectorAll('.library-image-card')[index].classList.add('selected');
        selectedImageUrl = url;

        // Set the URL in a hidden input or directly use it
        // We'll handle this differently for create vs edit
        if (typeof handleLibraryImageSelection === 'function') {
            handleLibraryImageSelection(url);
        }

        // Close modal after 500ms
        setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('imageLibraryModal'));
            modal.hide();
        }, 500);
    }

    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    // Search functionality
    document.getElementById('imageSearchInput')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.library-image-card');
        
        cards.forEach((card, index) => {
            const image = libraryImages[index];
            if (image && image.name.toLowerCase().includes(searchTerm)) {
                card.parentElement.style.display = 'block';
            } else {
                card.parentElement.style.display = 'none';
            }
        });
    });
</script>
