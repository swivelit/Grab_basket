<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Delivery Partner') - {{ config('app.name') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #16a34a;
            --warning-color: #ca8a04;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-gray: #f8fafc;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        .delivery-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .delivery-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            border: 1px solid var(--border-color);
        }

        .delivery-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .delivery-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: white;
            border-radius: 20px 20px 0 0;
        }

        .delivery-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            backdrop-filter: blur(10px);
        }

        .delivery-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .delivery-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            font-weight: 400;
        }

        .delivery-body {
            padding: 2rem 1.5rem 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .input-group-text {
            border: 2px solid var(--border-color);
            border-radius: 12px 0 0 12px;
            background: var(--light-gray);
            border-right: none;
            color: var(--dark-color);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .delivery-footer {
            text-align: center;
            padding: 1rem 1.5rem 2rem;
            color: #6b7280;
        }

        .delivery-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .delivery-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 1rem;
        }

        .alert-success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-warning {
            background: rgba(202, 138, 4, 0.1);
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }

        .invalid-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .is-invalid {
            border-color: var(--danger-color) !important;
        }

        /* Multi-step form */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            position: relative;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: var(--success-color);
            color: white;
        }

        .step.pending {
            background: var(--border-color);
            color: #6b7280;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 30px;
            height: 2px;
            background: var(--border-color);
            transform: translateY(-50%);
        }

        .step.completed:not(:last-child)::after {
            background: var(--success-color);
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        /* File upload styling */
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: block;
            padding: 1rem;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-gray);
        }

        .file-upload-label:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .file-upload-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        /* Mobile optimizations */
        @media (max-width: 576px) {
            .delivery-container {
                padding: 0.5rem;
            }

            .delivery-card {
                max-width: 100%;
                border-radius: 16px;
            }

            .delivery-header {
                padding: 1.5rem 1rem 1rem;
            }

            .delivery-logo {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }

            .delivery-title {
                font-size: 1.5rem;
            }

            .delivery-body {
                padding: 1.5rem 1rem 1rem;
            }

            .form-group {
                margin-bottom: 1.25rem;
            }

            .step {
                width: 35px;
                height: 35px;
                font-size: 0.8rem;
            }

            .step:not(:last-child)::after {
                width: 25px;
            }
            
            /* Enhanced mobile touch styles */
            .form-control {
                min-height: 48px;
                font-size: 16px; /* Prevents zoom on iOS */
                touch-action: manipulation;
            }
            
            .btn {
                min-height: 48px;
                touch-action: manipulation;
                user-select: none;
                -webkit-tap-highlight-color: transparent;
            }
            
            .btn:active {
                transform: scale(0.98);
                transition: transform 0.1s ease;
            }
            
            .form-check-input {
                width: 1.5rem;
                height: 1.5rem;
                touch-action: manipulation;
            }
            
            .form-check-label {
                touch-action: manipulation;
                padding-left: 0.5rem;
            }
            
            .input-group-text, 
            .input-group .btn {
                min-height: 48px;
                touch-action: manipulation;
            }
            
            a, button {
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
            }
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Custom checkbox and radio styles */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(202, 138, 4, 0.1);
            color: var(--warning-color);
        }

        .status-approved {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        /* Floating elements */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="delivery-container">
        @yield('content')
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <script>
        // CSRF Token setup for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Form validation enhancements
        (function() {
            'use strict';
            
            // Fetch all forms with validation
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // File upload preview
        function setupFileUpload(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            if (input && preview) {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }

        // Phone number formatting
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 10) {
                input.value = value;
            } else {
                input.value = value.substring(0, 10);
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

    @stack('scripts')
</body>
</html>