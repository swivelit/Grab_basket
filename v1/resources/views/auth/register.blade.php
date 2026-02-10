<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - GrabBasket</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff6b00;
            --secondary: #232f3e;
            --buyer-color: #16a34a;
            --seller-color: #6d28d9;
        }

        body {
            background: linear-gradient(135deg, #fdfbfb, #ebedee);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }

        .brand {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .brand img {
            width: 45px;
            height: 45px;
            margin-right: 12px;
            object-fit: contain;
        }

        .registration-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Role Selection Cards */
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .role-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .role-card.active {
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.2);
        }

        .role-card.buyer.active {
            border-color: var(--buyer-color);
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.2);
        }

        .role-card.seller.active {
            border-color: var(--seller-color);
            box-shadow: 0 10px 30px rgba(109, 40, 217, 0.2);
        }

        .role-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .role-card.buyer .role-icon {
            color: var(--buyer-color);
        }

        .role-card.seller .role-icon {
            color: var(--seller-color);
        }

        .role-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .role-description {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h4 {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .form-header .role-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }

        .role-badge.buyer {
            background: var(--buyer-color);
        }

        .role-badge.seller {
            background: var(--seller-color);
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, .15);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), #ff8c00);
            border: none;
            border-radius: 12px;
            padding: 0.85rem 2rem;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1.05rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 0, 0.3);
            color: white;
        }

        .login-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .login-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .hidden {
            display: none;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .role-selection {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-card {
                padding: 25px;
            }

            .brand {
                font-size: 1.5rem;
            }

            .brand img {
                width: 35px;
                height: 35px;
            }

            .role-icon {
                font-size: 2.5rem;
            }

            .role-title {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px 0;
            }

            .registration-container {
                padding: 0 15px;
            }

            .form-card {
                padding: 20px;
                border-radius: 15px;
            }

            .brand {
                font-size: 1.3rem;
                margin-bottom: 20px;
            }

            .role-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="registration-container">
        <!-- Brand -->
        <div class="text-center">
            <a href="/" class="brand text-decoration-none">
                <img src="{{ asset('asset/images/grabbasket.png') }}" alt="GrabBasket Logo">
                GrabBasket
            </a>
        </div>

        <!-- Role Selection -->
        <div class="role-selection" id="roleSelection">
            <div class="role-card buyer" data-role="buyer">
                <div class="role-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="role-title">I'm a Buyer</div>
                <div class="role-description">Shop from local sellers and get products delivered</div>
            </div>

            <div class="role-card seller" data-role="seller">
                <div class="role-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="role-title">I'm a Seller</div>
                <div class="role-description">Sell your products and grow your business</div>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="form-card hidden" id="registrationForm">
            <div class="form-header">
                <h4>Create Your Account</h4>
                <span class="role-badge" id="roleBadge"></span>
            </div>

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf

                <!-- Hidden role field -->
                <input type="hidden" name="role" id="roleInput" value="{{ old('role') }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}"
                            required>
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}"
                            required>
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input id="phone" type="text" class="form-control" name="phone" value="{{ old('phone') }}"
                            required>
                        @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="sex" class="form-label">Gender</label>
                        <select id="sex" name="sex" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('sex') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('sex') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('sex') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('sex') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="billing_address" class="form-label">Address</label>
                        <input id="billing_address" type="text" class="form-control" name="billing_address"
                            value="{{ old('billing_address') }}" required>
                        @error('billing_address') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="state" class="form-label">State</label>
                        <input id="state" type="text" class="form-control" name="state" value="{{ old('state') }}"
                            required>
                        @error('state') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="city" class="form-label">City</label>
                        <input id="city" type="text" class="form-control" name="city" value="{{ old('city') }}"
                            required>
                        @error('city') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="pincode" class="form-label">Pincode</label>
                        <input id="pincode" type="text" class="form-control" name="pincode" value="{{ old('pincode') }}"
                            required>
                        @error('pincode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" class="form-control" name="password" required>
                        @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input id="password_confirmation" type="password" class="form-control"
                            name="password_confirmation" required>
                        @error('password_confirmation') <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="referral_code_input" class="form-label">
                            Referral Code <span class="text-muted small">(Optional)</span>
                        </label>
                        <input id="referral_code_input" type="text" class="form-control" name="referral_code_input"
                            value="{{ old('referral_code_input') }}" placeholder="Enter referral code" maxlength="8"
                            style="text-transform: uppercase;">
                        <div class="form-text">
                            <i class="fas fa-gift text-primary"></i>
                            Have a referral code? Enter it here to participate in our referral program!
                        </div>
                        @error('referral_code_input') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-register">Create Account</button>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="login-link">Already have an account? Login</a>
                </div>

                <div class="text-center mt-3">
                    <button type="button" class="btn btn-link text-muted" id="changeRole">
                        <i class="fas fa-arrow-left"></i> Choose Different Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleSelection = document.getElementById('roleSelection');
            const registrationForm = document.getElementById('registrationForm');
            const roleCards = document.querySelectorAll('.role-card');
            const roleInput = document.getElementById('roleInput');
            const roleBadge = document.getElementById('roleBadge');
            const changeRoleBtn = document.getElementById('changeRole');

            // Check if there's an old role value (validation error)
            const oldRole = "{{ old('role') }}";
            if (oldRole) {
                selectRole(oldRole);
            }

            // Role card click handlers
            roleCards.forEach(card => {
                card.addEventListener('click', function () {
                    const role = this.dataset.role;
                    selectRole(role);
                });
            });

            // Change role button
            changeRoleBtn.addEventListener('click', function () {
                roleSelection.classList.remove('hidden');
                registrationForm.classList.add('hidden');
                roleCards.forEach(card => card.classList.remove('active'));
                roleInput.value = '';
            });

            function selectRole(role) {
                // Hide role selection, show form
                roleSelection.classList.add('hidden');
                registrationForm.classList.remove('hidden');

                // Update active state
                roleCards.forEach(card => {
                    card.classList.remove('active');
                    if (card.dataset.role === role) {
                        card.classList.add('active');
                    }
                });

                // Update hidden input
                roleInput.value = role;

                // Update badge
                roleBadge.textContent = role === 'buyer' ? 'Buyer Registration' : 'Seller Registration';
                roleBadge.className = 'role-badge ' + role;

                // Scroll to form
                registrationForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    </script>
</body>

</html>