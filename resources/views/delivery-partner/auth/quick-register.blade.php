@extends('delivery-partner.layouts.app')

@section('title', 'Quick Registration - Delivery Partner')

@push('head-scripts')
<script src="{{ asset('js/delivery-partner-form-handler.js') }}"></script>
@endpush

@section('content')
<div class="auth-container">
    <div class="auth-header">
        <div class="logo">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <h2 class="title">Quick Registration</h2>
        <p class="subtitle">Get started in 2 minutes - Complete details later!</p>
    </div>

    <div class="auth-form">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('delivery-partner.quick-register') }}" id="quick-registration-form">
            @csrf
            
            <!-- Essential Information Only -->
            <div class="form-group">
                <label for="name" class="form-label">Full Name *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" 
                       placeholder="Enter your full name" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Mobile Number *</label>
                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                       id="phone" name="phone" value="{{ old('phone') }}" 
                       placeholder="10-digit mobile number" required maxlength="10" 
                       oninput="formatPhone(this)">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       id="email" name="email" value="{{ old('email') }}" 
                       placeholder="your.email@example.com" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                <select class="form-select @error('vehicle_type') is-invalid @enderror" 
                        id="vehicle_type" name="vehicle_type" required>
                    <option value="">Select Your Vehicle</option>
                    <option value="bike" {{ old('vehicle_type') == 'bike' ? 'selected' : '' }}>🏍️ Bike</option>
                    <option value="scooter" {{ old('vehicle_type') == 'scooter' ? 'selected' : '' }}>🛵 Scooter</option>
                    <option value="bicycle" {{ old('vehicle_type') == 'bicycle' ? 'selected' : '' }}>🚲 Bicycle</option>
                    <option value="car" {{ old('vehicle_type') == 'car' ? 'selected' : '' }}>🚗 Car</option>
                    <option value="auto" {{ old('vehicle_type') == 'auto' ? 'selected' : '' }}>🛺 Auto Rickshaw</option>
                </select>
                @error('vehicle_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="city" class="form-label">City *</label>
                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                       id="city" name="city" value="{{ old('city') }}" 
                       placeholder="Which city will you deliver in?" required>
                @error('city')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password *</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                       id="password" name="password" 
                       placeholder="Create a strong password (min 6 characters)" required minlength="6">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm Password *</label>
                <input type="password" class="form-control" 
                       id="password_confirmation" name="password_confirmation" 
                       placeholder="Re-enter your password" required minlength="6">
            </div>

            <!-- Terms and Conditions -->
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                           type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required>
                    <label class="form-check-label" for="terms_accepted">
                        I agree to the <a href="#" target="_blank">Terms & Conditions</a> and 
                        <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                    @error('terms_accepted')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 btn-lg" id="submit-btn">
                <span class="submit-text">
                    <i class="fas fa-rocket me-2"></i>Quick Start - Join Now!
                </span>
                <span class="loading-text d-none">
                    <i class="fas fa-spinner fa-spin me-2"></i>Setting up your account...
                </span>
            </button>

            <!-- Additional Info Notice -->
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Quick Setup!</strong> You can complete your documents and additional details after registration.
            </div>
        </form>
    </div>

    <div class="delivery-footer">
        <p class="mb-2">Need full registration? <a href="{{ route('delivery-partner.register') }}">Complete Registration</a></p>
        <p class="mb-2">Already have an account?</p>
        <a href="{{ route('delivery-partner.login') }}" class="btn btn-outline-primary">
            <i class="fas fa-sign-in-alt me-2"></i>Sign In
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Phone number formatting
    function formatPhone(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 10) {
            value = value.slice(0, 10);
        }
        input.value = value;
    }

    // Real-time validation
    document.getElementById('phone').addEventListener('input', function(e) {
        const phone = e.target.value;
        if (phone.length === 10) {
            // Quick check if phone exists
            fetch('/delivery-partner/check-phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ phone: phone })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    e.target.classList.add('is-invalid');
                    showFieldError(e.target, 'Phone number already registered');
                } else {
                    e.target.classList.remove('is-invalid');
                    clearFieldError(e.target);
                }
            })
            .catch(() => {
                // Ignore validation errors during quick check
            });
        }
    });

    // Email validation
    document.getElementById('email').addEventListener('blur', function(e) {
        const email = e.target.value;
        if (email) {
            fetch('/delivery-partner/check-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    e.target.classList.add('is-invalid');
                    showFieldError(e.target, 'Email already registered');
                } else {
                    e.target.classList.remove('is-invalid');
                    clearFieldError(e.target);
                }
            })
            .catch(() => {
                // Ignore validation errors during quick check
            });
        }
    });

    // Password confirmation
    document.getElementById('password_confirmation').addEventListener('input', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = e.target.value;
        
        if (confirmPassword && password !== confirmPassword) {
            e.target.classList.add('is-invalid');
            showFieldError(e.target, 'Passwords do not match');
        } else {
            e.target.classList.remove('is-invalid');
            clearFieldError(e.target);
        }
    });

    // Initialize form handler to prevent multiple submissions
    document.addEventListener('DOMContentLoaded', function() {
        new DeliveryPartnerFormHandler('quick-registration-form', 'submit-btn');
    });

    // Helper functions
    function showFieldError(field, message) {
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    function clearFieldError(field) {
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 3000);
    }

    // Auto-save for recovery
    const inputs = document.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.type !== 'password') {
            const savedValue = localStorage.getItem(`quick_reg_${input.name}`);
            if (savedValue) {
                input.value = savedValue;
            }
            
            input.addEventListener('input', function() {
                localStorage.setItem(`quick_reg_${this.name}`, this.value);
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
    .auth-container {
        max-width: 400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Prevent multiple submissions */
    .form-submitting {
        pointer-events: none;
        opacity: 0.7;
    }

    .form-submitting .btn {
        cursor: not-allowed;
    }

    /* Loading animation */
    .loading-text .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .auth-header .logo {
        font-size: 3rem;
        color: #007bff;
        text-align: center;
        margin-bottom: 1rem;
    }

    .auth-header .title {
        font-size: 1.8rem;
        font-weight: 600;
        color: #333;
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .auth-header .subtitle {
        color: #666;
        text-align: center;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-label {
        font-weight: 500;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: none;
        border-radius: 8px;
        padding: 14px 24px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .delivery-footer {
        text-align: center;
        margin-top: 2rem;
        color: #666;
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    .alert-info {
        background-color: #e7f3ff;
        color: #0066cc;
        border-left: 4px solid #007bff;
    }

    @media (max-width: 576px) {
        .auth-container {
            padding: 15px;
        }
        
        .form-control, .form-select {
            font-size: 16px; /* Prevent zoom on iOS */
        }
    }
</style>
@endpush