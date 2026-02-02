<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Banner - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #FFF8E7 0%, #FFEBCD 50%, #FFE4E1 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF6B00 100%);
            box-shadow: 0 4px 20px rgba(255, 107, 0, 0.3);
        }
        
        .form-card {
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 15px;
            background: white;
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.15);
            padding: 30px;
        }
        
        .btn-festive {
            background: linear-gradient(45deg, #FF4444, #FF6B00, #FFD700);
            color: white;
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
        }
        
        .btn-festive:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.4);
            color: white;
        }
        
        .color-preview {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            border: 2px solid #ddd;
            cursor: pointer;
        }
        
        .theme-preview {
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        
        .theme-preview:hover {
            transform: scale(1.05);
        }
        
        .theme-preview.selected {
            border-color: var(--diwali-orange);
            box-shadow: 0 0 20px rgba(255, 107, 0, 0.4);
        }
        
        .preview-area {
            min-height: 200px;
            border-radius: 15px;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('admin.dashboard') }}">🪔 GrabBasket Admin</a>
            <div class="ms-auto">
                <a href="{{ route('admin.banners.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Banners
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-card">
            <h2 class="fw-bold mb-4" style="background: linear-gradient(45deg, #FF4444, #FF6B00, #FFD700); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                🎨 Create New Banner
            </h2>

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Banner Title *</label>
                            <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="e.g., Diwali Sale 2025">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Short description about the banner">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Button Text *</label>
                            <input type="text" name="button_text" class="form-control" required value="{{ old('button_text', 'Shop Now') }}" placeholder="e.g., Shop Now, Learn More">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Link URL</label>
                            <input type="url" name="link_url" class="form-control" value="{{ old('link_url') }}" placeholder="https://example.com/sale">
                            <small class="text-muted">Where should the button redirect?</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Banner Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(event)">
                            <small class="text-muted">Recommended: 1920x600px, Max 2MB</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Theme *</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="theme-preview selected" data-theme="festive" onclick="selectTheme('festive')" style="background: linear-gradient(45deg, #FF6B00, #FFD700);">
                                        <div class="text-white text-center">
                                            <i class="bi bi-brightness-high fs-3"></i>
                                            <div class="fw-bold">Festive</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="theme-preview" data-theme="modern" onclick="selectTheme('modern')" style="background: linear-gradient(45deg, #4A5568, #2D3748);">
                                        <div class="text-white text-center">
                                            <i class="bi bi-grid fs-3"></i>
                                            <div class="fw-bold">Modern</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="theme-preview" data-theme="minimal" onclick="selectTheme('minimal')" style="background: linear-gradient(45deg, #F7FAFC, #EDF2F7);">
                                        <div class="text-dark text-center">
                                            <i class="bi bi-circle fs-3"></i>
                                            <div class="fw-bold">Minimal</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="theme-preview" data-theme="gradient" onclick="selectTheme('gradient')" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                        <div class="text-white text-center">
                                            <i class="bi bi-rainbow fs-3"></i>
                                            <div class="fw-bold">Gradient</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="theme" id="theme" value="festive" required>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Position *</label>
                                <select name="position" class="form-select" required>
                                    <option value="hero" selected>Hero (Main)</option>
                                    <option value="top">Top</option>
                                    <option value="middle">Middle</option>
                                    <option value="bottom">Bottom</option>
                                </select>
                            </div>

                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="{{ old('display_order', 0) }}" min="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Background Color *</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="background_color" class="form-control form-control-color" value="{{ old('background_color', '#FFD700') }}" id="bgColor" onchange="updatePreview()">
                                    <input type="text" class="form-control" value="#FFD700" id="bgColorText" onchange="document.getElementById('bgColor').value = this.value; updatePreview();">
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Text Color *</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="text_color" class="form-control form-control-color" value="{{ old('text_color', '#FFFFFF') }}" id="textColor" onchange="updatePreview()">
                                    <input type="text" class="form-control" value="#FFFFFF" id="textColorText" onchange="document.getElementById('textColor').value = this.value; updatePreview();">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                            </div>

                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-check-label fw-bold" for="is_active">
                                    Active (Show on website)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Live Preview</label>
                    <div id="livePreview" class="preview-area" style="background: #FFD700; color: #FFFFFF;">
                        <div>
                            <h3 id="previewTitle">Banner Title</h3>
                            <p id="previewDesc">Banner description goes here...</p>
                            <button type="button" class="btn btn-light" id="previewButton">Shop Now</button>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-festive flex-fill">
                        <i class="bi bi-check-circle"></i> Create Banner
                    </button>
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectTheme(theme) {
            document.querySelectorAll('.theme-preview').forEach(el => el.classList.remove('selected'));
            document.querySelector(`[data-theme="${theme}"]`).classList.add('selected');
            document.getElementById('theme').value = theme;
        }

        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = 
                        `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
                };
                reader.readAsDataURL(file);
            }
        }

        function updatePreview() {
            const bgColor = document.getElementById('bgColor').value;
            const textColor = document.getElementById('textColor').value;
            document.getElementById('livePreview').style.background = bgColor;
            document.getElementById('livePreview').style.color = textColor;
            document.getElementById('bgColorText').value = bgColor;
            document.getElementById('textColorText').value = textColor;
        }

        // Real-time preview updates
        document.querySelector('input[name="title"]').addEventListener('input', function(e) {
            document.getElementById('previewTitle').textContent = e.target.value || 'Banner Title';
        });

        document.querySelector('textarea[name="description"]').addEventListener('input', function(e) {
            document.getElementById('previewDesc').textContent = e.target.value || 'Banner description goes here...';
        });

        document.querySelector('input[name="button_text"]').addEventListener('input', function(e) {
            document.getElementById('previewButton').textContent = e.target.value || 'Shop Now';
        });
    </script>
</body>
</html>
