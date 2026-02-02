<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - GrabBasket Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            max-width: 500px;
            width: 90%;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }
        .icon-large {
            font-size: 4rem;
            color: #dc3545;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="card-body text-center p-5">
            <img src="{{ asset('asset/images/grabbasket.png') }}" alt="GrabBasket Logo" class="logo">
            <div class="icon-large mb-4">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3 class="mb-4">{{ $message ?? 'Oops! Something went wrong' }}</h3>
            <p class="text-muted mb-4">
                @if(session('is_admin'))
                    Please try refreshing the page or contact the system administrator if the issue persists.
                @else
                    Please ensure you are logged in with admin credentials.
                @endif
            </p>
            <div class="d-grid gap-2">
                @if(session('is_admin'))
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-clockwise"></i> Retry Dashboard
                    </a>
                @else
                    <a href="{{ route('admin.login') }}" class="btn btn-primary">
                        <i class="bi bi-shield-lock"></i> Admin Login
                    </a>
                @endif
                <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-house"></i> Go to Main Site
                </a>
            </div>
        </div>
    </div>

    <script>
        // Automatically retry loading the dashboard after 5 seconds if user is admin
        @if(session('is_admin'))
        setTimeout(() => {
            window.location.href = '{{ route("admin.dashboard") }}';
        }, 5000);
        @endif
    </script>
</body>
</html>
