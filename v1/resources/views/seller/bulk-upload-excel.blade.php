<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bulk Upload Excel - Seller Dashboard</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
            padding-top: 60px;
            transition: all 0.3s;
            z-index: 1000;
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

        .upload-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }

        .upload-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .file-upload-area {
            border: 3px dashed #28a745;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: rgba(40, 167, 69, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #20c997;
            background: rgba(32, 201, 151, 0.1);
            transform: translateY(-2px);
        }

        .file-upload-area.dragover {
            border-color: #20c997;
            background: rgba(32, 201, 151, 0.15);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }

        .sample-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .table thead {
            background: #343a40;
            color: white;
        }

        .instructions-card {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .step-number {
            background: white;
            color: #007bff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
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

        /* Toggle button */
        .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 1.8rem;
            cursor: pointer;
            color: #212529;
            z-index: 1101;
        }

        .nav-pills {
            position: relative;
            bottom: 50px;
        }

        .progress {
            height: 25px;
            border-radius: 15px;
        }

        .progress-bar {
            border-radius: 15px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .error-alert {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            border-radius: 12px;
        }

        .success-alert {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            border-radius: 12px;
        }

        .warning-alert {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            border: none;
            color: #212529;
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <!-- Toggle Button (mobile) -->
    <div class="menu-toggle d-md-none">
        <i class="bi bi-list"></i>
    </div>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-3" id="sidebarMenu">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <img src="{{ asset('asset/images/grablogo.jpg') }}" alt="Logo" class="logoimg" width="150px">
            <x-notification-bell />
        </div>
        <ul class="nav nav-pills flex-column" style="margin-top:65px;">
            <li>
                <a class="nav-link" href="{{ route('seller.createProduct') }}">
                    <i class="bi bi-plus-circle"></i> Add Product
                </a>
            </li>
            <li>
                <a class="nav-link active" href="{{ route('seller.bulkUploadForm') }}">
                    <i class="bi bi-cloud-upload"></i> Bulk Upload Excel
                </a>
            </li>
            <li>
                <a class="nav-link" href="{{ route('seller.createCategorySubcategory') }}">
                    <i class="bi bi-plus-square"></i> Add Category
                </a>
            </li>
            <li>
                <a class="nav-link" href="{{ route('seller.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a class="nav-link" href="{{ route('seller.transactions') }}">
                    <i class="bi bi-cart-check"></i> Orders
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

    <!-- Content -->
    <div class="content">
        <!-- Header -->
        <div class="upload-header">
            <h1><i class="bi bi-cloud-upload me-3"></i>Bulk Product Upload</h1>
            <p class="mb-0">Upload multiple products at once using Excel sheet with images</p>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert success-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert error-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert warning-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Instructions Card -->
        <div class="instructions-card">
            <h4><i class="bi bi-info-circle me-2"></i>Instructions</h4>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <span class="step-number">1</span>
                        <strong>Download Sample Excel</strong>
                        <p class="mb-0 mt-2">Download the sample Excel template to see the required format and column headers.</p>
                    </div>
                    <div class="mb-3">
                        <span class="step-number">2</span>
                        <strong>Prepare Your Data</strong>
                        <p class="mb-0 mt-2">Fill in your product information following the sample format. Use exact column names.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <span class="step-number">3</span>
                        <strong>Prepare Images (Optional)</strong>
                        <p class="mb-0 mt-2">Create a ZIP file with product images. Image names should match the filename in Excel.</p>
                    </div>
                    <div class="mb-3">
                        <span class="step-number">4</span>
                        <strong>Upload Files</strong>
                        <p class="mb-0 mt-2">Upload both Excel file and ZIP file (if you have images) using the form below.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sample Download -->
        <div class="upload-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="bi bi-download me-2 text-success"></i>Download Sample Template</h4>
                <a href="{{ route('seller.downloadSampleExcel') }}" class="btn btn-success btn-lg">
                    <i class="bi bi-file-earmark-excel me-2"></i>Download Sample Excel
                </a>
            </div>
            <p class="text-muted">Download the sample Excel file to understand the required format and column structure.</p>
        </div>

        <!-- Upload Form -->
        <div class="upload-card">
            <h4 class="mb-4"><i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Your Files</h4>
            
            <form action="{{ route('seller.processBulkUpload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf
                
                <!-- Excel File Upload -->
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="bi bi-file-earmark-excel text-success me-2"></i>Excel File (Required)
                    </label>
                    <div class="file-upload-area" onclick="document.getElementById('excel_file').click()">
                        <div class="upload-icon">
                            <i class="bi bi-file-earmark-excel"></i>
                        </div>
                        <h5>Click to select Excel file</h5>
                        <p class="text-muted mb-0">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</p>
                        <input type="file" id="excel_file" name="excel_file[]" accept=".xlsx,.xls,.csv" class="d-none" required multiple>
                    </div>
                    <div id="excel_file_info" class="mt-2"></div>
                </div>

                <!-- Images ZIP Upload -->
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="bi bi-file-zip text-warning me-2"></i>Images ZIP File (Optional)
                    </label>
                    <div class="file-upload-area" onclick="document.getElementById('images_zip').click()">
                        <div class="upload-icon">
                            <i class="bi bi-file-zip"></i>
                        </div>
                        <h5>Click to select ZIP file with images</h5>
                        <p class="text-muted mb-0">ZIP file containing product images (Max: 50MB)</p>
                        <input type="file" id="images_zip" name="images_zip" accept=".zip" class="d-none">
                    </div>
                    <div id="images_zip_info" class="mt-2"></div>
                </div>

                <!-- Upload Progress -->
                <div id="upload_progress" class="mb-4" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <p class="text-center mt-2 mb-0">Uploading and processing files...</p>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                        <i class="bi bi-cloud-upload me-2"></i>Upload Products
                    </button>
                </div>
            </form>
        </div>

        <!-- Required Columns Information -->
        <div class="upload-card">
            <h4 class="mb-4"><i class="bi bi-table me-2 text-info"></i>Required Excel Columns</h4>
            <div class="table-responsive sample-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Column Name</th>
                            <th>Required</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>NAME</code></td>
                            <td><span class="badge bg-danger">Required</span></td>
                            <td>Text</td>
                            <td>Product name</td>
                            <td>iPhone 15 Pro Max</td>
                        </tr>
                        <tr>
                            <td><code>UNIQUE-ID</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Text</td>
                            <td>Unique product identifier</td>
                            <td>PROD-001</td>
                        </tr>
                        <tr>
                            <td><code>CATEGORY ID</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Number</td>
                            <td>Existing category ID</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td><code>CATEGORY NAME</code></td>
                            <td><span class="badge bg-danger">Required</span></td>
                            <td>Text</td>
                            <td>Category name (will create if not exists)</td>
                            <td>Electronics</td>
                        </tr>
                        <tr>
                            <td><code>SUBCATEGORY ID</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Number</td>
                            <td>Existing subcategory ID</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td><code>SUBCATEGORY-NAME</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Text</td>
                            <td>Subcategory name (will create if not exists)</td>
                            <td>Mobile Phones</td>
                        </tr>
                        <tr>
                            <td><code>IMAGE</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Text</td>
                            <td>Image filename in ZIP</td>
                            <td>iphone15.jpg</td>
                        </tr>
                        <tr>
                            <td><code>DESCRIPTION</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Text</td>
                            <td>Product description</td>
                            <td>Latest iPhone with A17 Pro chip</td>
                        </tr>
                        <tr>
                            <td><code>PRICE</code></td>
                            <td><span class="badge bg-danger">Required</span></td>
                            <td>Number</td>
                            <td>Product price</td>
                            <td>99999.99</td>
                        </tr>
                        <tr>
                            <td><code>DISCOUNT</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Number</td>
                            <td>Discount percentage (0-100)</td>
                            <td>10</td>
                        </tr>
                        <tr>
                            <td><code>DELIVERY-CHARGE</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Number</td>
                            <td>Delivery charge (0 for free)</td>
                            <td>50</td>
                        </tr>
                        <tr>
                            <td><code>GIFT-OPTION</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Boolean</td>
                            <td>Gift option available (true/false)</td>
                            <td>true</td>
                        </tr>
                        <tr>
                            <td><code>STOCK</code></td>
                            <td><span class="badge bg-warning">Optional</span></td>
                            <td>Number</td>
                            <td>Available stock quantity</td>
                            <td>100</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Categories & Subcategories Reference -->
        <div class="row">
            <div class="col-md-6">
                <div class="upload-card">
                    <h5 class="mb-3"><i class="bi bi-tags me-2 text-primary"></i>Available Categories</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $category->id }}</span></td>
                                    <td>{{ $category->name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="upload-card">
                    <h5 class="mb-3"><i class="bi bi-bookmark me-2 text-success"></i>Available Subcategories</h5>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subcategories as $subcategory)
                                <tr>
                                    <td><span class="badge bg-success">{{ $subcategory->id }}</span></td>
                                    <td>{{ $subcategory->name }}</td>
                                    <td><small class="text-muted">{{ $subcategory->category->name ?? 'N/A' }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Details Modal -->
    @if(session('errors') && count(session('errors')) > 0)
    <div class="modal fade" id="errorsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-circle me-2"></i>Upload Errors</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>The following errors occurred during the upload process:
                    </div>
                    <ul class="list-group">
                        @foreach(session('errors') as $error)
                        <li class="list-group-item list-group-item-danger">
                            <i class="bi bi-x-circle me-2"></i>{{ $error }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar toggle
        const toggleBtn = document.querySelector('.menu-toggle');
        const sidebar = document.getElementById('sidebarMenu');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // File upload handlers
        document.getElementById('excel_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const info = document.getElementById('excel_file_info');
            
            if (file) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                info.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>${file.name}</strong> (${size} MB) selected
                    </div>
                `;
            } else {
                info.innerHTML = '';
            }
        });

        document.getElementById('images_zip').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const info = document.getElementById('images_zip_info');
            
            if (file) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                info.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>${file.name}</strong> (${size} MB) selected
                    </div>
                `;
            } else {
                info.innerHTML = '';
            }
        });

        // Form submission with progress
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const progressDiv = document.getElementById('upload_progress');
            const submitBtn = document.getElementById('submitBtn');
            
            progressDiv.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            
            // Simulate progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                document.querySelector('.progress-bar').style.width = progress + '%';
                
                if (progress >= 90) {
                    clearInterval(interval);
                }
            }, 500);
        });

        // Drag and drop functionality
        function setupDragDrop(uploadArea, fileInput) {
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
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        // Setup drag and drop for both upload areas
        const uploadAreas = document.querySelectorAll('.file-upload-area');
        uploadAreas.forEach((area, index) => {
            const input = index === 0 ? document.getElementById('excel_file') : document.getElementById('images_zip');
            setupDragDrop(area, input);
        });

        // Show errors modal if there are errors
        @if(session('errors') && count(session('errors')) > 0)
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('errorsModal')).show();
        });
        @endif
    </script>
</body>

</html>