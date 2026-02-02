    @extends('layouts.minimal')

    @section('title', 'Hotel Owner Profile')

    @section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>Profile Settings
                        </h4>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('hotel-owner.profile.update') }}">
                            @csrf
                            @method('PUT')

                            <!-- Personal Information -->
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                        id="name" name="name" value="{{ old('name', $hotelOwner->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                        id="phone" name="phone" value="{{ old('phone', $hotelOwner->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr>

                            <!-- Restaurant Information -->
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-store me-2"></i>Restaurant Information
                            </h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="restaurant_name" class="form-label">Restaurant Name</label>
                                    <input type="text" class="form-control @error('restaurant_name') is-invalid @enderror" 
                                        id="restaurant_name" name="restaurant_name" 
                                        value="{{ old('restaurant_name', $hotelOwner->restaurant_name) }}" required>
                                    @error('restaurant_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurant_phone" class="form-label">Restaurant Phone</label>
                                    <input type="text" class="form-control @error('restaurant_phone') is-invalid @enderror" 
                                        id="restaurant_phone" name="restaurant_phone" 
                                        value="{{ old('restaurant_phone', $hotelOwner->restaurant_phone) }}" required>
                                    @error('restaurant_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="restaurant_address" class="form-label">Restaurant Address</label>
                                <textarea class="form-control @error('restaurant_address') is-invalid @enderror" 
                                        id="restaurant_address" name="restaurant_address" rows="3" required>{{ old('restaurant_address', $hotelOwner->restaurant_address) }}</textarea>
                                @error('restaurant_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="cuisine_type" class="form-label">Cuisine Type</label>
                                    <input type="text" class="form-control @error('cuisine_type') is-invalid @enderror" 
                                        id="cuisine_type" name="cuisine_type" 
                                        value="{{ old('cuisine_type', $hotelOwner->cuisine_type) }}"
                                        placeholder="e.g., Indian, Chinese, Italian">
                                    @error('cuisine_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="delivery_fee" class="form-label">Delivery Fee (₹)</label>
                                    <input type="number" step="0.01" class="form-control @error('delivery_fee') is-invalid @enderror" 
                                        id="delivery_fee" name="delivery_fee" 
                                        value="{{ old('delivery_fee', $hotelOwner->delivery_fee) }}">
                                    @error('delivery_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Restaurant Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                        id="description" name="description" rows="3"
                                        placeholder="Describe your restaurant, specialties, etc.">{{ old('description', $hotelOwner->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr>

                            <!-- Operations -->
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-clock me-2"></i>Operating Hours & Settings
                            </h5>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="opening_time" class="form-label">Opening Time</label>
                                    <input type="time" class="form-control @error('opening_time') is-invalid @enderror" 
                                        id="opening_time" name="opening_time" 
                                        value="{{ old('opening_time', $hotelOwner->opening_time) }}">
                                    @error('opening_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="closing_time" class="form-label">Closing Time</label>
                                    <input type="time" class="form-control @error('closing_time') is-invalid @enderror" 
                                        id="closing_time" name="closing_time" 
                                        value="{{ old('closing_time', $hotelOwner->closing_time) }}">
                                    @error('closing_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="delivery_time" class="form-label">Delivery Time (minutes)</label>
                                    <input type="number" class="form-control @error('delivery_time') is-invalid @enderror" 
                                        id="delivery_time" name="delivery_time" 
                                        value="{{ old('delivery_time', $hotelOwner->delivery_time) }}"
                                        min="10" max="120">
                                    @error('delivery_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="min_order_amount" class="form-label">Minimum Order Amount (₹)</label>
                                    <input type="number" class="form-control @error('min_order_amount') is-invalid @enderror" 
                                        id="min_order_amount" name="min_order_amount" 
                                        value="{{ old('min_order_amount', $hotelOwner->min_order_amount) }}"
                                        min="0">
                                    @error('min_order_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Operating Days</label>
                                <div class="row">
                                    @php
                                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                        $dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        $selectedDays = old('operating_days', $hotelOwner->operating_days ?? []);
                                    @endphp
                                    @foreach($days as $index => $day)
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                name="operating_days[]" value="{{ $day }}" 
                                                id="{{ $day }}"
                                                {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $day }}">
                                                {{ $dayLabels[$index] }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('hotel-owner.dashboard') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection