<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Banner Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --diwali-gold: #FFD700;
            --diwali-orange: #FF6B00;
            --diwali-red: #FF4444;
        }
        
        body {
            background: linear-gradient(135deg, #FFF8E7 0%, #FFEBCD 50%, #FFE4E1 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF6B00 100%);
            box-shadow: 0 4px 20px rgba(255, 107, 0, 0.3);
        }
        
        .banner-card {
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 15px;
            background: white;
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .banner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(255, 107, 0, 0.25);
        }
        
        .banner-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .badge-festive {
            background: linear-gradient(45deg, #FF6B00, #FFD700);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
        }
        
        .badge-modern {
            background: linear-gradient(45deg, #4A5568, #2D3748);
            color: white;
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
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .status-active {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        
        .status-inactive {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('admin.dashboard') }}">
                🪔 GrabBasket Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('admin.banners.index') }}">
                            <i class="bi bi-images"></i> Banners
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.logout') }}">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold" style="background: linear-gradient(45deg, #FF4444, #FF6B00, #FFD700); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    🎨 Banner Management
                </h1>
                <p class="text-muted">Manage homepage banners and promotional content</p>
            </div>
            <a href="{{ route('admin.banners.create') }}" class="btn btn-festive">
                <i class="bi bi-plus-circle"></i> Create New Banner
            </a>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Banners Grid -->
        @if($banners->count() > 0)
        <div class="row g-4">
            @foreach($banners as $banner)
            <div class="col-md-6 col-lg-4">
                <div class="banner-card p-3">
                    <!-- Banner Preview -->
                    @if($banner->image_url)
                    <img src="{{ asset($banner->image_url) }}" alt="{{ $banner->title }}" class="banner-preview mb-3">
                    @else
                    <div class="banner-preview mb-3 d-flex align-items-center justify-content-center" 
                         style="background: {{ $banner->background_color }}; color: {{ $banner->text_color }};">
                        <div class="text-center">
                            <h4>{{ $banner->title }}</h4>
                            <p>{{ $banner->description }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Banner Info -->
                    <div class="mb-3">
                        <h5 class="fw-bold mb-2">{{ $banner->title }}</h5>
                        <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($banner->description, 80) }}</p>
                        
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <span class="badge badge-{{ $banner->theme }}">
                                @if($banner->theme == 'festive') 🪔 @endif
                                {{ ucfirst($banner->theme) }}
                            </span>
                            <span class="badge bg-secondary">
                                <i class="bi bi-pin-angle"></i> {{ ucfirst($banner->position) }}
                            </span>
                            <span class="badge bg-info">
                                <i class="bi bi-sort-numeric-down"></i> Order: {{ $banner->display_order }}
                            </span>
                        </div>
                        
                        <div class="mb-2">
                            <span class="status-badge status-{{ $banner->is_active ? 'active' : 'inactive' }}">
                                <i class="bi bi-{{ $banner->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                {{ $banner->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        @if($banner->start_date || $banner->end_date)
                        <div class="small text-muted">
                            @if($banner->start_date)
                            <div><i class="bi bi-calendar-check"></i> Start: {{ $banner->start_date->format('M d, Y') }}</div>
                            @endif
                            @if($banner->end_date)
                            <div><i class="bi bi-calendar-x"></i> End: {{ $banner->end_date->format('M d, Y') }}</div>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-sm btn-primary flex-fill">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-{{ $banner->is_active ? 'warning' : 'success' }}" 
                                onclick="toggleStatus({{ $banner->id }})">
                            <i class="bi bi-toggle-{{ $banner->is_active ? 'on' : 'off' }}"></i>
                        </button>
                        <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Are you sure you want to delete this banner?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-images" style="font-size: 4rem; color: var(--diwali-orange);"></i>
            <h3 class="mt-3">No Banners Yet</h3>
            <p class="text-muted">Create your first banner to get started!</p>
            <a href="{{ route('admin.banners.create') }}" class="btn btn-festive mt-3">
                <i class="bi bi-plus-circle"></i> Create First Banner
            </a>
        </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleStatus(bannerId) {
            fetch(`/admin/banners/${bannerId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update banner status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    </script>
</body>
</html>
