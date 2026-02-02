@extends('delivery-partner.layouts.dashboard')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="page-title">
                    <i class="fas fa-user-circle me-2"></i>My Profile
                </h1>
                <p class="text-muted">Manage your profile information and settings</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('delivery-partner.profile.update') }}">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                            id="name" name="name" value="{{ old('name', $partner->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                            id="email" name="email" value="{{ old('email', $partner->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                            id="phone" name="phone" value="{{ old('phone', $partner->phone) }}" 
                                            pattern="[0-9]{10}" maxlength="10" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="alternate_phone" class="form-label">Alternate Phone</label>
                                        <input type="tel" class="form-control @error('alternate_phone') is-invalid @enderror" 
                                            id="alternate_phone" name="alternate_phone" 
                                            value="{{ old('alternate_phone', $partner->alternate_phone) }}" 
                                            pattern="[0-9]{10}" maxlength="10">
                                        @error('alternate_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label for="address" class="form-label">Address *</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                            id="address" name="address" rows="2" required>{{ old('address', $partner->address) }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="city" class="form-label">City *</label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                            id="city" name="city" value="{{ old('city', $partner->city) }}" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="state" class="form-label">State *</label>
                                        <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                            id="state" name="state" value="{{ old('state', $partner->state) }}" required>
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="pincode" class="form-label">Pincode *</label>
                                        <input type="text" class="form-control @error('pincode') is-invalid @enderror" 
                                            id="pincode" name="pincode" value="{{ old('pincode', $partner->pincode) }}" 
                                            pattern="[0-9]{6}" maxlength="6" required>
                                        @error('pincode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="vehicle_number" class="form-label">Vehicle Number *</label>
                                        <input type="text" class="form-control @error('vehicle_number') is-invalid @enderror" 
                                            id="vehicle_number" name="vehicle_number" 
                                            value="{{ old('vehicle_number', $partner->vehicle_number) }}" required>
                                        @error('vehicle_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="license_expiry" class="form-label">License Expiry *</label>
                                        <input type="date" class="form-control @error('license_expiry') is-invalid @enderror" 
                                            id="license_expiry" name="license_expiry" 
                                            value="{{ old('license_expiry', $partner->license_expiry ? $partner->license_expiry->format('Y-m-d') : '') }}" required>
                                        @error('license_expiry')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('delivery-partner.change-password') }}">
                                @csrf

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                            id="current_password" name="current_password" required>
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                            id="new_password" name="new_password" required minlength="8">
                                        @error('new_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="new_password_confirmation" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" 
                                            id="new_password_confirmation" name="new_password_confirmation" required minlength="8">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-warning text-dark">
                                        <i class="fas fa-key me-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Status & Info -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="text-muted small">Account Status</label>
                                <div class="mt-1">
                                    @if ($partner->status === 'approved')
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-check-circle me-1"></i>Approved
                                        </span>
                                    @elseif ($partner->status === 'pending')
                                        <span class="badge bg-warning fs-6">
                                            <i class="fas fa-clock me-1"></i>Pending Approval
                                        </span>
                                    @elseif ($partner->status === 'suspended')
                                        <span class="badge bg-danger fs-6">
                                            <i class="fas fa-ban me-1"></i>Suspended
                                        </span>
                                    @else
                                        <span class="badge bg-secondary fs-6">{{ ucfirst($partner->status) }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small">Member Since</label>
                                <div class="fw-bold">{{ $partner->created_at->format('M d, Y') }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small">Total Deliveries</label>
                                <div class="fw-bold fs-4">{{ $partner->completed_deliveries ?? 0 }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small">Current Rating</label>
                                <div>
                                    @php
                                        $rating = $partner->rating ?? 0;
                                    @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $rating)
                                            <i class="fas fa-star text-warning"></i>
                                        @else
                                            <i class="far fa-star text-muted"></i>
                                        @endif
                                    @endfor
                                    <span class="ms-2 fw-bold">{{ number_format($rating, 1) }}</span>
                                </div>
                            </div>

                            @if ($partner->status === 'pending')
                                <div class="alert alert-warning mb-0">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        Your account is under review. You'll be notified once approved.
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-phone-alt me-2"></i>Emergency Contact</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('delivery-partner.profile.update') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="emergency_contact_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" 
                                        id="emergency_contact_name" name="emergency_contact_name" 
                                        value="{{ old('emergency_contact_name', $partner->emergency_contact_name) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="emergency_contact_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" 
                                        id="emergency_contact_phone" name="emergency_contact_phone" 
                                        value="{{ old('emergency_contact_phone', $partner->emergency_contact_phone) }}" 
                                        pattern="[0-9]{10}" maxlength="10">
                                </div>

                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
