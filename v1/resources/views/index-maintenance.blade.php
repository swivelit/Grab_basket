<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrabBaskets - Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body text-center p-5">
                        <h1 class="display-4 mb-4">🛠️ We're Fixing Things</h1>
                        <p class="lead mb-4">Our homepage is undergoing maintenance to fix a technical issue.</p>
                        <p class="text-muted">This usually takes just a few minutes.</p>
                        <div class="mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg me-2">Browse Products</a>
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
