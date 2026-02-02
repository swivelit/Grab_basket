<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Restaurant - grabbaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF6B35;
            --primary-hover: #E55A2B;
            --text-dark: #1C1C1C;
            --bg-light: #F7F7F7;
        }
        
        body {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }
        
        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 2rem auto;
        }
        
        .auth-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 16px 16px 0 0;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px;
            border: 2px solid #E5E5E5;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2><i class="bi bi-shop"></i> Register Your Restaurant</h2>
                        <p class="mb-0">Join grabbaskets food delivery network</p>
                    </div>
                    
                    <div class="p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('hotel-owner.register') }}">
                            @csrf

                            <!-- Personal Information -->
                            <h5 class="section-title">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Full Name</label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                           name="email" value="{{ old('email') }}" required autocomplete="email">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           name="phone" value="{{ old('phone') }}">
                                    @error('phone')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                           name="password" required autocomplete="new-password">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input id="password_confirmation" type="password" class="form-control" 
                                       name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <!-- Restaurant Information -->
                            <h5 class="section-title mt-4">Restaurant Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="restaurant_name" class="form-label">Restaurant Name</label>
                                    <input id="restaurant_name" type="text" class="form-control @error('restaurant_name') is-invalid @enderror" 
                                           name="restaurant_name" value="{{ old('restaurant_name') }}" required>
                                    @error('restaurant_name')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="restaurant_phone" class="form-label">Restaurant Phone</label>
                                    <input id="restaurant_phone" type="text" class="form-control @error('restaurant_phone') is-invalid @enderror" 
                                           name="restaurant_phone" value="{{ old('restaurant_phone') }}" required>
                                    @error('restaurant_phone')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="restaurant_address" class="form-label">Restaurant Address</label>
                                <textarea id="restaurant_address" class="form-control @error('restaurant_address') is-invalid @enderror" 
                                          name="restaurant_address" rows="3" required>{{ old('restaurant_address') }}</textarea>
                                @error('restaurant_address')
                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cuisine_type" class="form-label">Cuisine Type</label>
                                    <select id="cuisine_type" class="form-select @error('cuisine_type') is-invalid @enderror" name="cuisine_type">
                                        <option value="">Select Cuisine Type</option>
                                        <option value="Indian" {{ old('cuisine_type') == 'Indian' ? 'selected' : '' }}>Indian</option>
                                        <option value="Chinese" {{ old('cuisine_type') == 'Chinese' ? 'selected' : '' }}>Chinese</option>
                                        <option value="Italian" {{ old('cuisine_type') == 'Italian' ? 'selected' : '' }}>Italian</option>
                                        <option value="Mexican" {{ old('cuisine_type') == 'Mexican' ? 'selected' : '' }}>Mexican</option>
                                        <option value="Fast Food" {{ old('cuisine_type') == 'Fast Food' ? 'selected' : '' }}>Fast Food</option>
                                        <option value="South Indian" {{ old('cuisine_type') == 'South Indian' ? 'selected' : '' }}>South Indian</option>
                                        <option value="North Indian" {{ old('cuisine_type') == 'North Indian' ? 'selected' : '' }}>North Indian</option>
                                        <option value="Continental" {{ old('cuisine_type') == 'Continental' ? 'selected' : '' }}>Continental</option>
                                        <option value="Other" {{ old('cuisine_type') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('cuisine_type')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="delivery_time" class="form-label">Delivery Time (minutes)</label>
                                    <input id="delivery_time" type="number" class="form-control @error('delivery_time') is-invalid @enderror" 
                                           name="delivery_time" value="{{ old('delivery_time', 30) }}" min="10" max="120">
                                    @error('delivery_time')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Restaurant Description</label>
                                <textarea id="description" class="form-control @error('description') is-invalid @enderror" 
                                          name="description" rows="3" placeholder="Tell customers about your restaurant...">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Business Settings -->
                            <h5 class="section-title mt-4">Business Settings</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="delivery_fee" class="form-label">Delivery Fee (₹)</label>
                                    <input id="delivery_fee" type="number" step="0.01" class="form-control @error('delivery_fee') is-invalid @enderror" 
                                           name="delivery_fee" value="{{ old('delivery_fee', 0) }}" min="0">
                                    @error('delivery_fee')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="min_order_amount" class="form-label">Min Order Amount (₹)</label>
                                    <input id="min_order_amount" type="number" class="form-control @error('min_order_amount') is-invalid @enderror" 
                                           name="min_order_amount" value="{{ old('min_order_amount', 0) }}" min="0">
                                    @error('min_order_amount')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="opening_time" class="form-label">Opening Time</label>
                                    <input id="opening_time" type="time" class="form-control @error('opening_time') is-invalid @enderror" 
                                           name="opening_time" value="{{ old('opening_time') }}">
                                    @error('opening_time')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="closing_time" class="form-label">Closing Time</label>
                                    <input id="closing_time" type="time" class="form-control @error('closing_time') is-invalid @enderror" 
                                           name="closing_time" value="{{ old('closing_time') }}">
                                    @error('closing_time')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Your restaurant will be reviewed by our team before going live. You'll receive an email once approved.
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-shop me-2"></i>Register My Restaurant
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-2">Already have an account?</p>
                            <a href="{{ route('hotel-owner.login') }}" class="btn btn-outline-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login Here
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('home') }}" class="text-primary">
                                <i class="bi bi-arrow-left me-1"></i>Back to Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>