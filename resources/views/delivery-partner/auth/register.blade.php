@extends('delivery-partner.layouts.app')

@section('title', 'Join as Delivery Partner')

@push('head-scripts')
<script src="{{ asset('js/delivery-partner-form-handler.js') }}"></script>
@endpush

@section('content')
<div class="delivery-card">
    <div class="delivery-header">
        <div class="delivery-logo floating">
            <i class="fas fa-motorcycle"></i>
        </div>
        <h1 class="delivery-title">Join Our Team</h1>
        <p class="delivery-subtitle">Become a delivery partner and start earning today</p>
    </div>

    <div class="delivery-body">
        @if (session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step-1">1</div>
            <div class="step pending" id="step-2">2</div>
            <div class="step pending" id="step-3">3</div>
            <div class="step pending" id="step-4">4</div>
        </div>

        <form method="POST" action="{{ route('delivery-partner.register.post') }}" class="needs-validation" novalidate enctype="multipart/form-data" id="registration-form">
            @csrf

            <!-- Step 1: Personal Information -->
            <div class="form-step active" id="form-step-1">
                <h5 class="mb-3"><i class="fas fa-user me-2"></i>Personal Information</h5>
                
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Enter your full name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" 
                                   placeholder="your.email@example.com" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}" 
                                   placeholder="10-digit mobile number" required maxlength="10" 
                                   oninput="formatPhoneNumber(this)">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alternate_phone" class="form-label">Alternate Phone</label>
                            <input type="tel" class="form-control @error('alternate_phone') is-invalid @enderror" 
                                   id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone') }}" 
                                   placeholder="Optional alternate number" maxlength="10" 
                                   oninput="formatPhoneNumber(this)">
                            @error('alternate_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date_of_birth" class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                   id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" 
                                   required max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="male" value="male" 
                                       {{ old('gender') == 'male' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="male">
                                    <i class="fas fa-mars me-1"></i>Male
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="female" value="female" 
                                       {{ old('gender') == 'female' ? 'checked' : '' }}>
                                <label class="form-check-label" for="female">
                                    <i class="fas fa-venus me-1"></i>Female
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="other" value="other" 
                                       {{ old('gender') == 'other' ? 'checked' : '' }}>
                                <label class="form-check-label" for="other">
                                    <i class="fas fa-genderless me-1"></i>Other
                                </label>
                            </div>
                        </div>
                    </div>
                    @error('gender')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Create Password *</label>
                    <div class="input-group">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Create a strong password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                           id="password_confirmation" name="password_confirmation" 
                           placeholder="Confirm your password" required>
                    @error('password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" onclick="nextStep(1)">
                        Next <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Address Information -->
            <div class="form-step" id="form-step-2">
                <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h5>
                
                <div class="form-group">
                    <label for="address" class="form-label">Complete Address *</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" 
                              id="address" name="address" rows="3" 
                              placeholder="Enter your complete address including landmark" required>{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                   id="city" name="city" value="{{ old('city') }}" 
                                   placeholder="Enter your city" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="state" class="form-label">State *</label>
                            <select class="form-select @error('state') is-invalid @enderror" id="state" name="state" required>
                                <option value="">Select State</option>
                                <option value="Andhra Pradesh" {{ old('state') == 'Andhra Pradesh' ? 'selected' : '' }}>Andhra Pradesh</option>
                                <option value="Arunachal Pradesh" {{ old('state') == 'Arunachal Pradesh' ? 'selected' : '' }}>Arunachal Pradesh</option>
                                <option value="Assam" {{ old('state') == 'Assam' ? 'selected' : '' }}>Assam</option>
                                <option value="Bihar" {{ old('state') == 'Bihar' ? 'selected' : '' }}>Bihar</option>
                                <option value="Chhattisgarh" {{ old('state') == 'Chhattisgarh' ? 'selected' : '' }}>Chhattisgarh</option>
                                <option value="Goa" {{ old('state') == 'Goa' ? 'selected' : '' }}>Goa</option>
                                <option value="Gujarat" {{ old('state') == 'Gujarat' ? 'selected' : '' }}>Gujarat</option>
                                <option value="Haryana" {{ old('state') == 'Haryana' ? 'selected' : '' }}>Haryana</option>
                                <option value="Himachal Pradesh" {{ old('state') == 'Himachal Pradesh' ? 'selected' : '' }}>Himachal Pradesh</option>
                                <option value="Jharkhand" {{ old('state') == 'Jharkhand' ? 'selected' : '' }}>Jharkhand</option>
                                <option value="Karnataka" {{ old('state') == 'Karnataka' ? 'selected' : '' }}>Karnataka</option>
                                <option value="Kerala" {{ old('state') == 'Kerala' ? 'selected' : '' }}>Kerala</option>
                                <option value="Madhya Pradesh" {{ old('state') == 'Madhya Pradesh' ? 'selected' : '' }}>Madhya Pradesh</option>
                                <option value="Maharashtra" {{ old('state') == 'Maharashtra' ? 'selected' : '' }}>Maharashtra</option>
                                <option value="Manipur" {{ old('state') == 'Manipur' ? 'selected' : '' }}>Manipur</option>
                                <option value="Meghalaya" {{ old('state') == 'Meghalaya' ? 'selected' : '' }}>Meghalaya</option>
                                <option value="Mizoram" {{ old('state') == 'Mizoram' ? 'selected' : '' }}>Mizoram</option>
                                <option value="Nagaland" {{ old('state') == 'Nagaland' ? 'selected' : '' }}>Nagaland</option>
                                <option value="Odisha" {{ old('state') == 'Odisha' ? 'selected' : '' }}>Odisha</option>
                                <option value="Punjab" {{ old('state') == 'Punjab' ? 'selected' : '' }}>Punjab</option>
                                <option value="Rajasthan" {{ old('state') == 'Rajasthan' ? 'selected' : '' }}>Rajasthan</option>
                                <option value="Sikkim" {{ old('state') == 'Sikkim' ? 'selected' : '' }}>Sikkim</option>
                                <option value="Tamil Nadu" {{ old('state') == 'Tamil Nadu' ? 'selected' : '' }}>Tamil Nadu</option>
                                <option value="Telangana" {{ old('state') == 'Telangana' ? 'selected' : '' }}>Telangana</option>
                                <option value="Tripura" {{ old('state') == 'Tripura' ? 'selected' : '' }}>Tripura</option>
                                <option value="Uttar Pradesh" {{ old('state') == 'Uttar Pradesh' ? 'selected' : '' }}>Uttar Pradesh</option>
                                <option value="Uttarakhand" {{ old('state') == 'Uttarakhand' ? 'selected' : '' }}>Uttarakhand</option>
                                <option value="West Bengal" {{ old('state') == 'West Bengal' ? 'selected' : '' }}>West Bengal</option>
                                <option value="Delhi" {{ old('state') == 'Delhi' ? 'selected' : '' }}>Delhi</option>
                            </select>
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pincode" class="form-label">PIN Code *</label>
                    <input type="text" class="form-control @error('pincode') is-invalid @enderror" 
                           id="pincode" name="pincode" value="{{ old('pincode') }}" 
                           placeholder="6-digit PIN code" required maxlength="6" 
                           oninput="formatPincode(this)">
                    @error('pincode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-primary" onclick="previousStep(2)">
                        <i class="fas fa-arrow-left me-2"></i>Previous
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                        Next <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Vehicle & License Information -->
            <div class="form-step" id="form-step-3">
                <h5 class="mb-3"><i class="fas fa-motorcycle me-2"></i>Vehicle & License Information</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                            <select class="form-select @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type" required>
                                <option value="">Select Vehicle Type</option>
                                <option value="bike" {{ old('vehicle_type') == 'bike' ? 'selected' : '' }}>
                                    <i class="fas fa-motorcycle"></i> Bike
                                </option>
                                <option value="scooter" {{ old('vehicle_type') == 'scooter' ? 'selected' : '' }}>
                                    <i class="fas fa-motorcycle"></i> Scooter
                                </option>
                                <option value="bicycle" {{ old('vehicle_type') == 'bicycle' ? 'selected' : '' }}>
                                    <i class="fas fa-bicycle"></i> Bicycle
                                </option>
                                <option value="car" {{ old('vehicle_type') == 'car' ? 'selected' : '' }}>
                                    <i class="fas fa-car"></i> Car
                                </option>
                                <option value="auto" {{ old('vehicle_type') == 'auto' ? 'selected' : '' }}>
                                    <i class="fas fa-taxi"></i> Auto Rickshaw
                                </option>
                            </select>
                            @error('vehicle_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vehicle_number" class="form-label">Vehicle Number *</label>
                            <input type="text" class="form-control @error('vehicle_number') is-invalid @enderror" 
                                   id="vehicle_number" name="vehicle_number" value="{{ old('vehicle_number') }}" 
                                   placeholder="e.g., MH 01 AB 1234" required style="text-transform: uppercase;">
                            @error('vehicle_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="license_number" class="form-label">License Number *</label>
                            <input type="text" class="form-control @error('license_number') is-invalid @enderror" 
                                   id="license_number" name="license_number" value="{{ old('license_number') }}" 
                                   placeholder="Driving license number" required style="text-transform: uppercase;">
                            @error('license_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="license_expiry" class="form-label">License Expiry *</label>
                            <input type="date" class="form-control @error('license_expiry') is-invalid @enderror" 
                                   id="license_expiry" name="license_expiry" value="{{ old('license_expiry') }}" 
                                   required min="{{ date('Y-m-d') }}">
                            @error('license_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vehicle_rc_number" class="form-label">Vehicle RC Number</label>
                            <input type="text" class="form-control @error('vehicle_rc_number') is-invalid @enderror" 
                                   id="vehicle_rc_number" name="vehicle_rc_number" value="{{ old('vehicle_rc_number') }}" 
                                   placeholder="Registration certificate number" style="text-transform: uppercase;">
                            @error('vehicle_rc_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_expiry" class="form-label">Insurance Expiry</label>
                            <input type="date" class="form-control @error('insurance_expiry') is-invalid @enderror" 
                                   id="insurance_expiry" name="insurance_expiry" value="{{ old('insurance_expiry') }}" 
                                   min="{{ date('Y-m-d') }}">
                            @error('insurance_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="aadhar_number" class="form-label">Aadhaar Number *</label>
                    <input type="text" class="form-control @error('aadhar_number') is-invalid @enderror" 
                           id="aadhar_number" name="aadhar_number" value="{{ old('aadhar_number') }}" 
                           placeholder="12-digit Aadhaar number" required maxlength="12" 
                           oninput="formatAadhar(this)">
                    @error('aadhar_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control @error('pan_number') is-invalid @enderror" 
                           id="pan_number" name="pan_number" value="{{ old('pan_number') }}" 
                           placeholder="e.g., ABCDE1234F" maxlength="10" style="text-transform: uppercase;">
                    @error('pan_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-primary" onclick="previousStep(3)">
                        <i class="fas fa-arrow-left me-2"></i>Previous
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                        Next <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Documents & Final Details -->
            <div class="form-step" id="form-step-4">
                <h5 class="mb-3"><i class="fas fa-file-upload me-2"></i>Documents & Final Details</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <div class="file-upload">
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                                <label for="profile_photo" class="file-upload-label">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div>Upload Profile Photo</div>
                                    <small class="text-muted">JPG, PNG (Max: 2MB)</small>
                                </label>
                            </div>
                            <div id="profile_photo_preview" class="mt-2"></div>
                            @error('profile_photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="license_photo" class="form-label">License Photo *</label>
                            <div class="file-upload">
                                <input type="file" id="license_photo" name="license_photo" accept="image/*" required>
                                <label for="license_photo" class="file-upload-label">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div>Upload License Photo</div>
                                    <small class="text-muted">JPG, PNG (Max: 2MB)</small>
                                </label>
                            </div>
                            <div id="license_photo_preview" class="mt-2"></div>
                            @error('license_photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vehicle_photo" class="form-label">Vehicle Photo *</label>
                            <div class="file-upload">
                                <input type="file" id="vehicle_photo" name="vehicle_photo" accept="image/*" required>
                                <label for="vehicle_photo" class="file-upload-label">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-motorcycle"></i>
                                    </div>
                                    <div>Upload Vehicle Photo</div>
                                    <small class="text-muted">JPG, PNG (Max: 2MB)</small>
                                </label>
                            </div>
                            <div id="vehicle_photo_preview" class="mt-2"></div>
                            @error('vehicle_photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="aadhar_photo" class="form-label">Aadhaar Photo *</label>
                            <div class="file-upload">
                                <input type="file" id="aadhar_photo" name="aadhar_photo" accept="image/*" required>
                                <label for="aadhar_photo" class="file-upload-label">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-id-badge"></i>
                                    </div>
                                    <div>Upload Aadhaar Photo</div>
                                    <small class="text-muted">JPG, PNG (Max: 2MB)</small>
                                </label>
                            </div>
                            <div id="aadhar_photo_preview" class="mt-2"></div>
                            @error('aadhar_photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Bank Details (Optional) -->
                <div class="card mt-4" style="border-radius: 12px; border: 1px solid var(--border-color);">
                    <div class="card-header bg-light" style="border-radius: 12px 12px 0 0;">
                        <h6 class="mb-0"><i class="fas fa-university me-2"></i>Bank Details (Optional)</h6>
                        <small class="text-muted">For receiving payments</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_account_holder" class="form-label">Account Holder Name</label>
                                    <input type="text" class="form-control @error('bank_account_holder') is-invalid @enderror" 
                                           id="bank_account_holder" name="bank_account_holder" value="{{ old('bank_account_holder') }}" 
                                           placeholder="As per bank records">
                                    @error('bank_account_holder')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_account_number" class="form-label">Account Number</label>
                                    <input type="text" class="form-control @error('bank_account_number') is-invalid @enderror" 
                                           id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}" 
                                           placeholder="Bank account number">
                                    @error('bank_account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_ifsc_code" class="form-label">IFSC Code</label>
                                    <input type="text" class="form-control @error('bank_ifsc_code') is-invalid @enderror" 
                                           id="bank_ifsc_code" name="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}" 
                                           placeholder="e.g., HDFC0001234" style="text-transform: uppercase;">
                                    @error('bank_ifsc_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror" 
                                           id="bank_name" name="bank_name" value="{{ old('bank_name') }}" 
                                           placeholder="Bank name">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="card mt-3" style="border-radius: 12px; border: 1px solid var(--border-color);">
                    <div class="card-header bg-light" style="border-radius: 12px 12px 0 0;">
                        <h6 class="mb-0"><i class="fas fa-phone-alt me-2"></i>Emergency Contact (Optional)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emergency_contact_name" class="form-label">Contact Name</label>
                                    <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" 
                                           id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" 
                                           placeholder="Emergency contact name">
                                    @error('emergency_contact_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                                    <input type="tel" class="form-control @error('emergency_contact_phone') is-invalid @enderror" 
                                           id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" 
                                           placeholder="10-digit phone number" maxlength="10" 
                                           oninput="formatPhoneNumber(this)">
                                    @error('emergency_contact_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emergency_contact_relation" class="form-label">Relationship</label>
                                    <select class="form-select @error('emergency_contact_relation') is-invalid @enderror" 
                                            id="emergency_contact_relation" name="emergency_contact_relation">
                                        <option value="">Select Relationship</option>
                                        <option value="father" {{ old('emergency_contact_relation') == 'father' ? 'selected' : '' }}>Father</option>
                                        <option value="mother" {{ old('emergency_contact_relation') == 'mother' ? 'selected' : '' }}>Mother</option>
                                        <option value="spouse" {{ old('emergency_contact_relation') == 'spouse' ? 'selected' : '' }}>Spouse</option>
                                        <option value="sibling" {{ old('emergency_contact_relation') == 'sibling' ? 'selected' : '' }}>Brother/Sister</option>
                                        <option value="friend" {{ old('emergency_contact_relation') == 'friend' ? 'selected' : '' }}>Friend</option>
                                        <option value="other" {{ old('emergency_contact_relation') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('emergency_contact_relation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-group mt-4">
                    <div class="form-check">
                        <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                               type="checkbox" id="terms_accepted" name="terms_accepted" required>
                        <label class="form-check-label" for="terms_accepted">
                            I agree to the <a href="#" target="_blank">Terms and Conditions</a> and 
                            <a href="#" target="_blank">Privacy Policy</a> *
                        </label>
                        @error('terms_accepted')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-primary" onclick="previousStep(4)">
                        <i class="fas fa-arrow-left me-2"></i>Previous
                    </button>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <span class="submit-text">
                            <i class="fas fa-paper-plane me-2"></i>Submit Application
                        </span>
                        <span class="loading-text d-none">
                            <i class="fas fa-spinner fa-spin me-2"></i>Processing...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="delivery-footer">
        <p class="mb-2">Already have an account?</p>
        <a href="{{ route('delivery-partner.login') }}" class="btn btn-outline-primary">
            <i class="fas fa-sign-in-alt me-2"></i>Sign In
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;
    const totalSteps = 4;

    // Step navigation functions
    function nextStep(step) {
        if (validateStep(step)) {
            if (step < totalSteps) {
                moveToStep(step + 1);
            }
        }
    }

    function previousStep(step) {
        if (step > 1) {
            moveToStep(step - 1);
        }
    }

    function moveToStep(stepNumber) {
        // Hide current step
        document.getElementById(`form-step-${currentStep}`).classList.remove('active');
        document.getElementById(`step-${currentStep}`).classList.remove('active');
        document.getElementById(`step-${currentStep}`).classList.add('completed');

        // Show new step
        currentStep = stepNumber;
        document.getElementById(`form-step-${currentStep}`).classList.add('active');
        document.getElementById(`step-${currentStep}`).classList.remove('pending');
        document.getElementById(`step-${currentStep}`).classList.add('active');

        // Update step indicators
        for (let i = 1; i <= totalSteps; i++) {
            const stepEl = document.getElementById(`step-${i}`);
            if (i < currentStep) {
                stepEl.classList.remove('active', 'pending');
                stepEl.classList.add('completed');
            } else if (i === currentStep) {
                stepEl.classList.remove('completed', 'pending');
                stepEl.classList.add('active');
            } else {
                stepEl.classList.remove('completed', 'active');
                stepEl.classList.add('pending');
            }
        }

        // Scroll to top
        document.querySelector('.delivery-card').scrollIntoView({ behavior: 'smooth' });
    }

    function validateStep(step) {
        const formStep = document.getElementById(`form-step-${step}`);
        const requiredFields = formStep.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.checkValidity()) {
                field.reportValidity();
                isValid = false;
                return false;
            }
        });

        return isValid;
    }

    // Input formatting functions
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 10) {
            input.value = value;
        } else {
            input.value = value.substring(0, 10);
        }
    }

    function formatPincode(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 6) {
            input.value = value;
        } else {
            input.value = value.substring(0, 6);
        }
    }

    function formatAadhar(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 12) {
            input.value = value;
        } else {
            input.value = value.substring(0, 12);
        }
    }

    // Password toggle
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-icon');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // File upload previews
    document.addEventListener('DOMContentLoaded', function() {
        setupFileUpload('profile_photo', 'profile_photo_preview');
        setupFileUpload('license_photo', 'license_photo_preview');
        setupFileUpload('vehicle_photo', 'vehicle_photo_preview');
        setupFileUpload('aadhar_photo', 'aadhar_photo_preview');
    });

    // Form submission with loading state
document.getElementById('submit-btn').addEventListener('click', function(e) {
    const form = document.getElementById('registration-form');

    if (validateStep(4) && form.checkValidity()) {
        this.innerHTML = '<span class="spinner"></span> Submitting Application...';
        this.disabled = true;

        form.submit();   // 🔥 REQUIRED
    }
});




    // Auto-uppercase for certain fields
    document.getElementById('vehicle_number').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });

    document.getElementById('license_number').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });

    document.getElementById('pan_number').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function(e) {
        const password = e.target.value;
        let strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        let strengthText = '';
        let strengthClass = '';
        
        switch (strength) {
            case 0:
            case 1:
                strengthText = 'Very Weak';
                strengthClass = 'text-danger';
                break;
            case 2:
                strengthText = 'Weak';
                strengthClass = 'text-warning';
                break;
            case 3:
                strengthText = 'Medium';
                strengthClass = 'text-info';
                break;
            case 4:
                strengthText = 'Strong';
                strengthClass = 'text-success';
                break;
            case 5:
                strengthText = 'Very Strong';
                strengthClass = 'text-success';
                break;
        }
        
        let indicator = document.getElementById('password-strength');
        if (!indicator) {
            indicator = document.createElement('small');
            indicator.id = 'password-strength';
            e.target.parentNode.appendChild(indicator);
        }
        
        indicator.className = strengthClass;
        indicator.textContent = password.length > 0 ? `Password strength: ${strengthText}` : '';
    });

    // Confirm password validation
    document.getElementById('password_confirmation').addEventListener('input', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = e.target.value;
        
        if (confirmPassword && password !== confirmPassword) {
            e.target.setCustomValidity('Passwords do not match');
        } else {
            e.target.setCustomValidity('');
        }
    });

    // Initialize form handler to prevent multiple submissions
     document.addEventListener('DOMContentLoaded', function() {
         new DeliveryPartnerFormHandler('registration-form', 'submit-btn');
     });

    // Compress images to reduce upload time
    function compressFormImages() {
        const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
        imageInputs.forEach(input => {
            if (input.files.length > 0) {
                const file = input.files[0];
                if (file.size > 500000) { // If larger than 500KB
                    // Create a simple compression by reducing quality
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const img = new Image();
                    
                    img.onload = function() {
                        // Reduce dimensions if too large
                        let { width, height } = img;
                        const maxDimension = 800;
                        
                        if (width > maxDimension || height > maxDimension) {
                            if (width > height) {
                                height = (height * maxDimension) / width;
                                width = maxDimension;
                            } else {
                                width = (width * maxDimension) / height;
                                height = maxDimension;
                            }
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);
                    };
                    
                    img.src = URL.createObjectURL(file);
                }
            }
        });
    }

    // Toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }

    // Auto-save form data to localStorage for recovery
    const form = document.getElementById('registration-form');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        // Load saved data on page load
        const savedValue = localStorage.getItem(`delivery_partner_${input.name}`);
        if (savedValue && input.type !== 'file' && input.type !== 'password') {
            input.value = savedValue;
        }
        
        // Save data on input
        input.addEventListener('input', function() {
            if (this.type !== 'file' && this.type !== 'password') {
                localStorage.setItem(`delivery_partner_${this.name}`, this.value);
            }
        });
    });

    // Clear saved data on successful submission
    form.addEventListener('submit', function() {
        // Add submitting class for visual feedback
        this.classList.add('form-submitting');
        
        setTimeout(() => {
            inputs.forEach(input => {
                localStorage.removeItem(`delivery_partner_${input.name}`);
            });
        }, 1000);
    });
</script>
@endpush

@push('styles')
<style>
    /* Prevent multiple submissions */
    .form-submitting {
        pointer-events: none;
        opacity: 0.7;
        position: relative;
    }

    .form-submitting::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .form-submitting .btn {
        cursor: not-allowed !important;
    }

    .form-submitting input,
    .form-submitting select,
    .form-submitting textarea {
        background-color: #f8f9fa !important;
        cursor: not-allowed !important;
    }

    /* Loading animation */
    .loading-text .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Progress indicator */
    .submission-progress {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #007bff;
        z-index: 9999;
        transform: scaleX(0);
        transform-origin: left;
        animation: progressBar 3s ease-in-out forwards;
    }

    @keyframes progressBar {
        0% { transform: scaleX(0); }
        50% { transform: scaleX(0.7); }
        100% { transform: scaleX(1); }
    }
</style>
@endpush