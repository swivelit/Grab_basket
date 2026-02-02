<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page Editor - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #FF6B00;
            --secondary-color: #FFD700;
            --dark-bg: #1a1a1a;
            --card-bg: #2d2d2d;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-container {
            padding: 40px 20px;
        }

        .admin-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border: none;
        }

        .card-header-custom h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }

        .card-body-custom {
            padding: 40px;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--secondary-color);
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, 0.25);
        }

        .form-check-input {
            width: 50px;
            height: 25px;
            border-radius: 25px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), #ff8c42);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.4);
            color: white;
        }

        .btn-preview {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-preview:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            color: var(--secondary-color);
            transform: translateX(-5px);
        }

        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .color-preview {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            border: 3px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .color-preview:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .section-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
        }

        .switch-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="container">
            <div class="admin-card">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-house-gear-fill me-3"></i>Index Page Editor</h1>
                            <p class="mb-0 mt-2">Customize your homepage appearance and content</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="btn-back">
                            <i class="bi bi-arrow-left-circle-fill fs-2"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body-custom">
                    @if(session('success'))
                        <div class="alert alert-success alert-custom">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-custom">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.index-editor.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- Hero Section -->
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-badge-tm me-2"></i>Hero Section</h3>
                            
                            <div class="mb-4">
                                <label class="form-label">Hero Title</label>
                                <input type="text" name="hero_title" class="form-control" 
                                       value="{{ old('hero_title', $settings['hero_title'] ?? '') }}"
                                       placeholder="Welcome to GrabBaskets">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Hero Subtitle</label>
                                <textarea name="hero_subtitle" class="form-control" rows="2"
                                          placeholder="Your one-stop shop for all your needs">{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
                            </div>
                        </div>

                        <!-- Section Visibility -->
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-eye me-2"></i>Section Visibility</h3>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="switch-container">
                                        <input type="checkbox" name="show_categories" class="form-check-input" 
                                               id="show_categories" value="1"
                                               {{ old('show_categories', $settings['show_categories'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-label mb-0" for="show_categories">
                                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Show Categories
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="switch-container">
                                        <input type="checkbox" name="show_banners" class="form-check-input" 
                                               id="show_banners" value="1"
                                               {{ old('show_banners', $settings['show_banners'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-label mb-0" for="show_banners">
                                            <i class="bi bi-image-fill me-2"></i>Show Banners
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="switch-container">
                                        <input type="checkbox" name="show_featured_products" class="form-check-input" 
                                               id="show_featured_products" value="1"
                                               {{ old('show_featured_products', $settings['show_featured_products'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-label mb-0" for="show_featured_products">
                                            <i class="bi bi-star-fill me-2"></i>Show Featured Products
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="switch-container">
                                        <input type="checkbox" name="show_trending" class="form-check-input" 
                                               id="show_trending" value="1"
                                               {{ old('show_trending', $settings['show_trending'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-label mb-0" for="show_trending">
                                            <i class="bi bi-fire me-2"></i>Show Trending Products
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="switch-container">
                                        <input type="checkbox" name="show_newsletter" class="form-check-input" 
                                               id="show_newsletter" value="1"
                                               {{ old('show_newsletter', $settings['show_newsletter'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-label mb-0" for="show_newsletter">
                                            <i class="bi bi-envelope-fill me-2"></i>Show Newsletter
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Titles -->
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-fonts me-2"></i>Section Titles</h3>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Featured Products Title</label>
                                    <input type="text" name="featured_section_title" class="form-control" 
                                           value="{{ old('featured_section_title', $settings['featured_section_title'] ?? 'Featured Products') }}">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Trending Section Title</label>
                                    <input type="text" name="trending_section_title" class="form-control" 
                                           value="{{ old('trending_section_title', $settings['trending_section_title'] ?? 'Trending Now') }}">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Newsletter Title</label>
                                    <input type="text" name="newsletter_title" class="form-control" 
                                           value="{{ old('newsletter_title', $settings['newsletter_title'] ?? 'Subscribe to Our Newsletter') }}">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Newsletter Subtitle</label>
                                    <input type="text" name="newsletter_subtitle" class="form-control" 
                                           value="{{ old('newsletter_subtitle', $settings['newsletter_subtitle'] ?? 'Get updates on new products') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Layout Settings -->
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-layout-three-columns me-2"></i>Layout Settings</h3>
                            
                            <div class="mb-4">
                                <label class="form-label">Products Per Row</label>
                                <select name="products_per_row" class="form-select">
                                    <option value="2" {{ old('products_per_row', $settings['products_per_row'] ?? 4) == 2 ? 'selected' : '' }}>2 Products</option>
                                    <option value="3" {{ old('products_per_row', $settings['products_per_row'] ?? 4) == 3 ? 'selected' : '' }}>3 Products</option>
                                    <option value="4" {{ old('products_per_row', $settings['products_per_row'] ?? 4) == 4 ? 'selected' : '' }}>4 Products (Default)</option>
                                    <option value="5" {{ old('products_per_row', $settings['products_per_row'] ?? 4) == 5 ? 'selected' : '' }}>5 Products</option>
                                    <option value="6" {{ old('products_per_row', $settings['products_per_row'] ?? 4) == 6 ? 'selected' : '' }}>6 Products</option>
                                </select>
                            </div>
                        </div>

                        <!-- Theme Colors -->
                        <div class="section-card">
                            <h3 class="section-title"><i class="bi bi-palette-fill me-2"></i>Theme Colors</h3>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Primary Color</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="color" name="theme_color" class="color-preview" 
                                               value="{{ old('theme_color', $settings['theme_color'] ?? '#FF6B00') }}">
                                        <input type="text" class="form-control" 
                                               value="{{ old('theme_color', $settings['theme_color'] ?? '#FF6B00') }}" 
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Secondary Color</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="color" name="secondary_color" class="color-preview" 
                                               value="{{ old('secondary_color', $settings['secondary_color'] ?? '#FFD700') }}">
                                        <input type="text" class="form-control" 
                                               value="{{ old('secondary_color', $settings['secondary_color'] ?? '#FFD700') }}" 
                                               readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-end mt-4">
                            <a href="/" target="_blank" class="btn btn-preview">
                                <i class="bi bi-eye-fill me-2"></i>Preview Homepage
                            </a>
                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-save-fill me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sync color picker with text input
        document.querySelectorAll('.color-preview').forEach(picker => {
            picker.addEventListener('input', function() {
                this.nextElementSibling.value = this.value;
            });
        });
    </script>
</body>
</html>
