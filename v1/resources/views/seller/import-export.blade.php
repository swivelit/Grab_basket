<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Import / Export Products</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* === SIDEBAR === */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 260px;
            background: #1a1a1a;
            color: #fff;
            transition: all 0.3s ease;
            z-index: 1000;
            height: 100vh;
            overflow-y: auto;
            /* ✅ Scroll inside sidebar */
            overflow-x: hidden;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
        }

        /* === SIDEBAR LOGO BOX === */
        /* === SIDEBAR LOGO BOX === */
        .sidebar-logo-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            background: #003366;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        .sidebar-logo-img {
            width: 150px;
            height: 200px;
            object-fit: cover;
            margin-top: -3px;
            /* Pull up slightly to counter height increase */
            margin-bottom: -3px;
        }

        .sidebar-logo-text {
            color: #fff;
            font-size: 0.85rem;
            line-height: 1.1;
            text-align: left;
            margin: 0;
            padding: 0;
        }

        .sidebar-logo-text strong {
            font-size: 0.95rem;
            display: block;
            font-weight: 600;
        }

        .sidebar-logo-text small {
            opacity: 0.8;
            font-size: 0.7rem;
            font-weight: 400;
        }

        /* Fixed Header */
        .sidebar-header {
            position: sticky;
            top: 0;
            padding: 12px 20px;
            z-index: 1001;
            /* Must be higher than other sidebar content */
            background: #1a1a1a;
            /* Match sidebar background to avoid "ghosting" */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100px;
            /* Adjusted height for better fit */
        }

        .sidebar-header .logoimg {
            width: 130px;
            height: auto;
            filter: brightness(0.9);
        }

        .sidebar-header .notification-bell {
            font-size: 1.2rem;
            color: #adb5bd;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 32px;
            width: 32px;
        }

        .sidebar-header .notification-bell:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* Scrollable Content */
        .sidebar-content {
            padding: 0;
            padding-bottom: 60px;
            background-color: #1a1a1a;
            /* Prevent logout from sticking to bottom */
        }

        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: #2d2d2d;
            border-radius: 10px;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 10px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        /* Nav Links */
        .sidebar .nav-link {
            color: #adb5bd;
            margin: 6px 15px;
            border-radius: 6px;
            padding: 10px 15px;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* Logout Highlight */
        .sidebar .nav-link[href="#"] {
            color: #dc3545;
        }

        .sidebar .nav-link[href="#"]:hover {
            background: #dc3545;
            color: white;
        }

        /* === CONTENT AREA === */
        .content {
            margin-left: 240px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            /* Ensure full height */
            background: #f8f9fa;
            position: relative;
            z-index: 999;
            /* Ensure content stays above other elements */
        }

        /* === MOBILE TOGGLE === */
        .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 1.8rem;
            cursor: pointer;
            color: #fff;
            z-index: 1101;
            background: #212529;
            padding: 8px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .menu-toggle:hover {
            background: #343a40;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -240px;
                height: 100vh;
                overflow-y: auto;
                z-index: 1001;
                /* Higher than content */
            }

            .sidebar.show {
                left: 0;
            }


            .menu-toggle {
                color: #fff;
                background: #212529;
            }
        }

        /* === NOTIFICATION BELL DROPDOWN FIX === */
        .sidebar-header .notification-bell {
            position: relative;
            /* Ensure it's a positioning context for its children */
        }

        /* Target the notification dropdown (assuming it's a direct child or descendant of the bell) */
        .sidebar-header .notification-bell~.dropdown-menu,
        .sidebar-header .notification-bell+.dropdown-menu,
        .sidebar-header .notification-bell .dropdown-menu {
            position: absolute;
            top: 100%;
            /* Position below the bell */
            left: 50%;
            /* Start from the center of the bell */
            transform: translateX(-50%);
            /* Center it horizontally */
            z-index: 1002;
            /* Higher than the sidebar (z-index: 1000) */
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.15);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            min-width: 280px;
            max-width: 320px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        /* Optional: If the dropdown needs to appear on the right side specifically */
        .sidebar-header .notification-bell .dropdown-menu {
            left: auto;
            /* Override the centering */
            right: -10px;
            /* Position slightly to the right of the bell */
            transform: none;
            /* Remove centering transform */
        }

        /* Ensure the dropdown doesn't get clipped by the sidebar */
        .sidebar-header .notification-bell .dropdown-menu {
            /* This is the key: use 'fixed' positioning to escape the sidebar's bounds */
            position: fixed;
            top: calc(100% + 0px);
            /* Position below the header with a small gap */
            left: calc(100vw - 350px);
            /* Position near the right edge of the viewport */
            width: 320px;
            z-index: 1002;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.15);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 1rem;
        }

        /* === NOTIFICATION BELL DROPDOWN FIX === */
        /* === NOTIFICATION BELL DROPDOWN FIX === */
        .dropdown-menu {
            position: fixed !important;
            top: 0 !important;
            /* Fixed at the very top of the screen */
            right: 20px !important;
            /* Position near the right edge */
            z-index: 1002 !important;
            /* Ensure it's above the sidebar */
            width: 320px !important;
            background: #fff !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            border-radius: 8px !important;
            padding: 1rem !important;
        }

        /* Optional: Adjust the arrow if needed */
        .dropdown-menu::before {
            display: none !important;
        }
    </style>
</head>

<body>
    <!-- Toggle Button (mobile) -->
    <div class="menu-toggle d-md-none">
        <i class="bi bi-list"></i>
    </div>

    <!-- Sidebar -->

    <div class="sidebar d-flex flex-column p-0" id="sidebarMenu">
        <div class="sidebar-header">
            <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo" class="sidebar-logo-img">
            <x-notification-bell />
        </div>

        <div class="sidebar-content">
            <ul class="nav nav-pills flex-column" style="margin-top: 20px;">
                <li>
                    <a class="nav-link" href="{{ route('seller.createProduct') }}">
                        <i class="bi bi-plus-circle"></i> Add Product
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('seller.imageLibrary') }}">
                        <i class="bi bi-images"></i> Image Library
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('seller.bulkUploadForm') }}">
                        <i class="bi bi-cloud-upload"></i> Bulk Upload Excel
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('seller.bulkImageReupload') }}">
                        <i class="bi bi-images"></i> Bulk Image Re-upload
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('seller.createCategorySubcategory') }}">
                        <i class="bi bi-plus-square"></i> Add Category
                    </a>
                </li>
                <li>
                    <a class="nav-link " href="{{ route('seller.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="nav-link " href="{{ route('seller.transactions') }}">
                        <i class="bi bi-cart-check"></i> Orders
                    </a>
                </li>
                <li>
                    <a class="nav-link active" href="{{ route('seller.importExport') }}">
                        <i class="bi bi-arrow-down-up"></i> Import / Export
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('tracking.form') }}">
                        <i class="bi bi-truck"></i> Track Package
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('notifications.index') }}">
                        <i class="bi bi-bell"></i> Notifications
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('seller.profile') }}">
                        <i class="bi bi-person-circle"></i> Profile
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="#"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid px-4 py-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-arrow-down-up me-2"></i>Import / Export Products
                    </h1>
                    <p class="text-muted mb-0">Manage your product listings in bulk</p>
                </div>
                <a href="{{ route('seller.dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if (isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="row g-4">
                <!-- Export Section -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-download me-2"></i>Export Products
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">
                                Download your product listings in your preferred format. All your products will be included.
                            </p>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Total Products:</strong> {{ $productsCount }} products
                            </div>

                            <div class="d-grid gap-3">
                                <!-- Export to Excel -->
                                <form action="{{ route('seller.products.export.excel') }}" method="POST" class="export-form">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-file-excel me-2"></i>
                                        Export to Excel (.xlsx)
                                        <small class="d-block mt-1 opacity-75">Best for editing and calculations</small>
                                    </button>
                                </form>

                                <!-- Export to CSV -->
                                <form action="{{ route('seller.products.export.csv') }}" method="POST" class="export-form">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-lg w-100">
                                        <i class="fas fa-file-csv me-2"></i>
                                        Export to CSV
                                        <small class="d-block mt-1 opacity-75">Universal format, compatible everywhere</small>
                                    </button>
                                </form>

                                <!-- Export to PDF -->
                                <form action="{{ route('seller.products.export.pdf') }}" method="POST" class="export-form">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-lg w-100">
                                        <i class="fas fa-file-pdf me-2"></i>
                                        Export to PDF (Simple)
                                        <small class="d-block mt-1 opacity-75">Table format, no images</small>
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4">
                                <h6 class="fw-bold mb-2">Exported Data Includes:</h6>
                                <ul class="small text-muted">
                                    <li>Product ID, Name, Description</li>
                                    <li>Category & Subcategory</li>
                                    <li>Pricing (Price, Original Price, Discount)</li>
                                    <li>Stock, SKU, Barcode</li>
                                    <li>Product Details (Brand, Model, Color, Size, etc.)</li>
                                    <li>SEO Information (Meta Title, Description, Tags)</li>
                                    <li>Image URLs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Section -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-upload me-2"></i>Import Products
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">
                                Upload your product listing file. We support Excel (.xlsx, .xls) and CSV formats.
                            </p>

                            <div class="alert alert-warning">
                                <i class="fas fa-magic me-2"></i>
                                <strong>Smart Header Detection:</strong> Our system automatically detects your column headers, even if they're different from our template!
                            </div>

                            <!-- Download Template -->
                            <div class="mb-4">
                                <a href="{{ route('seller.products.template') }}" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-download me-2"></i>
                                    Download Sample Template
                                    <small class="d-block mt-1 opacity-75">Start with our pre-formatted template</small>
                                </a>
                            </div>

                            <!-- Import Form -->
                            <form action="{{ route('seller.products.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                @csrf

                                <div class="mb-4">
                                    <label for="importFile" class="form-label fw-bold">
                                        <i class="fas fa-file-upload me-2"></i>Choose File to Import
                                    </label>
                                    <input type="file"
                                        class="form-control form-control-lg @error('file') is-invalid @enderror"
                                        id="importFile"
                                        name="file"
                                        accept=".xlsx,.xls,.csv"
                                        required>
                                    @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Accepted formats: .xlsx, .xls, .csv (Max: 10MB)
                                    </small>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" id="importBtn">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>
                                        Import Products
                                    </button>
                                </div>
                            </form>

                            <!-- Import Instructions -->
                            <div class="mt-4">
                                <h6 class="fw-bold mb-2">✨ Super Flexible Import:</h6>
                                <ol class="small text-muted">
                                    <li><strong>Use ANY column names:</strong> Works with any header format automatically</li>
                                    <li><strong>Import ONLY what you have:</strong> Missing columns? No problem! System uses what's available</li>
                                    <li><strong>Updates existing products:</strong> Matches by Product ID or Name</li>
                                    <li><strong>Creates new products:</strong> Automatically adds products that don't exist</li>
                                    <li><strong>Auto-creates categories:</strong> Categories/subcategories created if missing</li>
                                    <li><strong>Default category:</strong> Products without category → "Uncategorized" (edit later)</li>
                                    <li><strong>Leaves blank fields alone:</strong> Empty cells won't overwrite existing data</li>
                                    <li><strong>📸 Image Import:</strong> Supports image URLs (comma-separated for multiple)</li>
                                </ol>
                            </div>

                            <div class="mt-3">
                                <div class="alert alert-success">
                                    <i class="fas fa-magic me-2"></i>
                                    <strong>✅ Maximum Flexibility!</strong>
                                    <ul class="mb-0 mt-2 small">
                                        <li><strong>Minimum Required:</strong> Just "Name" and "Price" are enough!</li>
                                        <li><strong>Add Whatever You Have:</strong> Category, Stock, SKU, Brand, etc. - all optional</li>
                                        <li><strong>No Category? No Problem:</strong> Products without category go to "Uncategorized"</li>
                                        <li><strong>Any Format Works:</strong> "Product Name" or "Name" or "Title" - all recognized</li>
                                        <li><strong>Blank = Skip:</strong> Empty cells won't update existing product data</li>
                                        <li><strong>Example:</strong> Import just names and prices, add categories/images later!</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-camera me-2"></i>
                                    <strong>Image Import Options:</strong>
                                    <ul class="mb-0 mt-2 small">
                                        <li>Add "Image URL" column with direct image links</li>
                                        <li>Supports http:// and https:// URLs</li>
                                        <li>Multiple images: separate with commas</li>
                                        <li>Example: https://example.com/image1.jpg, https://example.com/image2.jpg</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6 class="fw-bold mb-2">Supported Header Variations:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm small text-muted">
                                        <tbody>
                                            <tr>
                                                <td><strong>Name:</strong></td>
                                                <td>"Product Name", "Name", "Title", "Item Name"</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Price:</strong></td>
                                                <td>"Price", "Selling Price", "Sale Price", "MRP"</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Stock:</strong></td>
                                                <td>"Stock", "Quantity", "Qty", "Inventory"</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="text-center">...and many more! Try any format.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-star me-2 text-warning"></i>Key Features
                            </h5>
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="fas fa-brain fa-3x text-primary"></i>
                                        </div>
                                        <h6>Smart Detection</h6>
                                        <p class="small text-muted">Automatically recognizes different header formats and column names</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="fas fa-sync-alt fa-3x text-success"></i>
                                        </div>
                                        <h6>Update or Create</h6>
                                        <p class="small text-muted">Updates existing products or creates new ones automatically</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="fas fa-file-alt fa-3x text-info"></i>
                                        </div>
                                        <h6>Multiple Formats</h6>
                                        <p class="small text-muted">Supports Excel, CSV, and PDF for exports</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="feature-icon mb-3">
                                            <i class="fas fa-shield-alt fa-3x text-warning"></i>
                                        </div>
                                        <h6>Safe & Secure</h6>
                                        <p class="small text-muted">Only your products are affected, fully validated data</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            body {
                background-color: #f8f9fa;
            }

            /* Sidebar */
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                width: 240px;
                background: #212529;
                color: #fff;
                padding-top: 20px;
                transition: all 0.3s;
                z-index: 1000;
                overflow-y: auto;
            }

            .sidebar .nav-link {
                color: #adb5bd;
                margin: 6px 0;
                border-radius: 6px;
            }

            .sidebar .nav-link.active,
            .sidebar .nav-link:hover {
                background: #0d6efd;
                color: #fff;
            }

            .sidebar .nav-link i {
                margin-right: 8px;
            }

            /* Content area */
            .content {
                margin-left: 240px;
                padding: 20px;
            }

            .export-form button {
                transition: all 0.3s ease;
            }

            .export-form button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .feature-icon i {
                opacity: 0.8;
            }

            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.08);
            }

            .card-header {
                border-radius: 10px 10px 0 0 !important;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .sidebar {
                    left: -240px;
                }

                .sidebar.show {
                    left: 0;
                }

                .content {
                    margin-left: 0;
                }
            }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Import form handler
            document.getElementById('importForm')?.addEventListener('submit', function(e) {
                const btn = document.getElementById('importBtn');
                const fileInput = document.getElementById('importFile');

                if (!fileInput.files.length) {
                    e.preventDefault();
                    alert('Please select a file to import');
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing... Please wait';
            });

            // Show file name when selected
            document.getElementById('importFile')?.addEventListener('change', function(e) {
                if (this.files.length) {
                    const fileName = this.files[0].name;
                    const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                    console.log(`Selected: ${fileName} (${fileSize} MB)`);
                }
            });

            // PDF Export form handlers with loading indicators
            document.querySelectorAll('.export-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const btn = this.querySelector('button[type="submit"]');
                    const originalHtml = btn.innerHTML;
                    const isPdfWithImages = this.action.includes('pdfWithImages');

                    // Disable button and show loading
                    btn.disabled = true;

                    if (isPdfWithImages) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating catalog with images...<small class="d-block mt-1">This may take 30-60 seconds</small>';
                    } else {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDF...';
                    }

                    // Log for debugging
                    console.log('Submitting export form:', this.action);
                    console.log('Form method:', this.method);
                    console.log('CSRF token:', this.querySelector('[name="_token"]')?.value);

                    // Re-enable button after delay (in case download completes)
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }, isPdfWithImages ? 60000 : 10000); // 60s for images, 10s for simple
                });
            });

            // Log any errors
            window.addEventListener('error', function(e) {
                console.error('Page error:', e.message, e.filename, e.lineno);
            });
        </script>
</body>

</html>