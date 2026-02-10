<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner Login - GrabBaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0C831F;
            --primary-normal: #FF6B00;
            --text-dark: #1a1a1a;
            --text-light: #666;
            --border-light: #f0f0f0;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #10a832 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .form-control {
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(12, 131, 31, 0.1);
            outline: none;
        }
        
        .btn-login {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: #086b25;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(12, 131, 31, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .footer-text a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer-text a:hover {
            text-decoration: underline;
        }
        
        .feature-list {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
        }
        
        .feature-list h6 {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .feature-item i {
            color: var(--primary-color);
            margin-right: 8px;
            font-size: 16px;
        }
        
        .demo-credentials {
            background: #f8f8f8;
            border-left: 3px solid var(--primary-color);
            padding: 12px;
            border-radius: 4px;
            margin-top: 16px;
            font-size: 12px;
        }
        
        .demo-credentials strong {
            display: block;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .demo-credentials code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="bi bi-bicycle"></i> Delivery Partner</h1>
            <p>Login to manage deliveries</p>
        </div>

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="error-message" style="background: #f8d7da; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    {{ $error }}
                </div>
            @endforeach
        @endif

        <form action="{{ route('delivery-partner.login.submit') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Login to Dashboard
            </button>
        </form>

        <div class="feature-list">
            <h6>Partner Benefits</h6>
            <div class="feature-item">
                <i class="bi bi-compass"></i>
                <span>Real-time delivery tracking</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-wallet"></i>
                <span>Earn up to ₹2,000/day</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-shield-check"></i>
                <span>Complete insurance coverage</span>
            </div>
            <div class="feature-item">
                <i class="bi bi-calendar-event"></i>
                <span>Flexible working hours</span>
            </div>
        </div>

        <div class="footer-text">
            Not a delivery partner yet? <a href="{{ route('home') }}">Go to home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
