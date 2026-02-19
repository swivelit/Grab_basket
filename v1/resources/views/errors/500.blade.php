<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - GrabBaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 600px;
        }
        .error-icon {
            font-size: 6rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .error-code {
            font-size: 3rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }
        .btn-home:hover {
            transform: translateY(-3px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="bi bi-exclamation-triangle-fill error-icon"></i>
        <div class="error-code">500</div>
        <h2 class="mb-3">Oops! Something went wrong</h2>
        <p class="error-message">
            {{ $message ?? 'We encountered an unexpected error while processing your request. Our team has been notified and is working to fix it.' }}
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ url('/') }}" class="btn-home">
                <i class="bi bi-house-fill me-2"></i>Back to Home
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary px-4">
                <i class="bi bi-arrow-left me-2"></i>Go Back
            </a>
        </div>
        <div class="mt-4">
            <small class="text-muted">
                Error Code: 500 | Time: {{ now()->format('Y-m-d H:i:s') }}
            </small>
        </div>
    </div>
</body>
</html>
