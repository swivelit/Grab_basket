@extends('warehouse.layouts.app')

@section('title', 'My Profile')

@section('breadcrumb')
<li class="breadcrumb-item active">My Profile</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Profile Information Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i>
                        Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('warehouse.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $user->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           value="{{ $user->email }}" 
                                           readonly>
                                    <div class="form-text">Email cannot be changed. Contact your manager if needed.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Employee ID</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="employee_id" 
                                           value="{{ $user->employee_id }}" 
                                           readonly>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Change Password Section -->
                        <h6 class="mb-3">Change Password (Optional)</h6>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" 
                                           class="form-control @error('current_password') is-invalid @enderror" 
                                           id="current_password" 
                                           name="current_password">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" 
                                           class="form-control @error('new_password') is-invalid @enderror" 
                                           id="new_password" 
                                           name="new_password">
                                    @error('new_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password_confirmation" 
                                           name="new_password_confirmation">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Update Profile
                            </button>
                            
                            <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Summary Cards -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-0">
                                <i class="bi bi-shield-check me-2"></i>
                                Role & Permissions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-{{ $user->role_badge_color }} fs-6">{{ $user->role_display }}</span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Department:</strong> {{ $user->department }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Assigned Areas:</strong>
                                @if($user->assigned_areas && count($user->assigned_areas) > 0)
                                    @foreach($user->assigned_areas as $area)
                                        <span class="badge bg-light text-dark me-1">{{ $area }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No areas assigned</span>
                                @endif
                            </div>
                            
                            <div>
                                <strong>Permissions:</strong>
                                @if(count($user->permission_list) > 0)
                                    <ul class="mb-0 mt-1">
                                        @foreach($user->permission_list as $permission)
                                            <li><small>{{ $permission }}</small></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">No special permissions</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-0">
                                <i class="bi bi-activity me-2"></i>
                                Activity Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h4 mb-0 text-primary">{{ $activitySummary['total_movements'] }}</div>
                                    <small class="text-muted">Total Movements</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 mb-0 text-success">{{ number_format($activitySummary['stock_added']) }}</div>
                                    <small class="text-muted">Items Added</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h5 mb-0 text-warning">{{ $activitySummary['adjustments_made'] }}</div>
                                    <small class="text-muted">Adjustments</small>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">
                                        <strong>Last Activity:</strong><br>
                                        {{ $activitySummary['last_activity'] ? $activitySummary['last_activity']->diffForHumans() : 'Never' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Account Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Account Status:</strong> 
                                <span class="badge bg-{{ $user->status_badge_color }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                            <p><strong>Member Since:</strong> {{ $user->created_at->format('F j, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Last Login:</strong> {{ $user->last_login_display }}</p>
                            @if($user->last_login_ip)
                                <p><strong>Last Login IP:</strong> {{ $user->last_login_ip }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection