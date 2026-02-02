@extends('layouts.minimal')

@section('title', $foodItem->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-utensils me-2"></i>{{ $foodItem->name }}</h4>
                    <div>
                        <a href="{{ route('hotel-owner.food-items.edit', $foodItem) }}" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('hotel-owner.food-items.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row">
                        @if($foodItem->image)
                        <div class="col-md-6 mb-4">
                            <img src="{{ $foodItem->first_image_url }}"
                                 alt="{{ $foodItem->name }}"
                                 class="img-fluid rounded shadow"
                                 onerror="this.onerror=null; this.src='{{ asset('images/placeholder.png') }}'">
                        </div>
                        @endif
                        
                        <div class="col-md-{{ $foodItem->image ? '6' : '12' }}">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <h5 class="mb-0 me-3">{{ $foodItem->name }}</h5>
                                    <span class="badge bg-{{ $foodItem->food_type == 'veg' ? 'success' : 'danger' }} me-2">
                                        {{ $foodItem->food_type == 'veg' ? 'VEG' : 'NON-VEG' }}
                                    </span>
                                    <span class="badge bg-{{ $foodItem->is_available ? 'success' : 'secondary' }}">
                                        {{ $foodItem->is_available ? 'Available' : 'Unavailable' }}
                                    </span>
                                    @if($foodItem->is_popular)
                                        <span class="badge bg-warning ms-2">Popular</span>
                                    @endif
                                </div>
                                
                                @if($foodItem->description)
                                    <p class="text-muted">{{ $foodItem->description }}</p>
                                @endif
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Category:</strong><br>
                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $foodItem->category)) }}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Price:</strong><br>
                                    <span class="h5 text-primary">₹{{ number_format($foodItem->getFinalPrice(), 2) }}</span>
                                    @if($foodItem->discounted_price)
                                        <br><small class="text-muted"><del>₹{{ number_format($foodItem->price, 2) }}</del></small>
                                        <span class="badge bg-success">{{ $foodItem->getDiscountPercentage() }}% OFF</span>
                                    @endif
                                </div>
                            </div>

                            @if($foodItem->spice_level || $foodItem->calories || $foodItem->preparation_time)
                            <div class="row mb-3">
                                @if($foodItem->spice_level)
                                <div class="col-4">
                                    <strong>Spice Level:</strong><br>
                                    <span class="badge bg-warning">{{ ucfirst($foodItem->spice_level) }}</span>
                                </div>
                                @endif
                                @if($foodItem->calories)
                                <div class="col-4">
                                    <strong>Calories:</strong><br>
                                    {{ $foodItem->calories }} kcal
                                </div>
                                @endif
                                @if($foodItem->preparation_time)
                                <div class="col-4">
                                    <strong>Prep Time:</strong><br>
                                    {{ $foodItem->preparation_time }} minutes
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($foodItem->ingredients)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6><i class="fas fa-list me-2"></i>Ingredients</h6>
                            <p class="text-muted">{{ $foodItem->ingredients }}</p>
                        </div>
                    </div>
                    @endif

                    @if($foodItem->allergens)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Allergens</h6>
                            <p class="text-danger">{{ $foodItem->allergens }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        Created: {{ $foodItem->created_at->format('M d, Y') }}
                                        @if($foodItem->updated_at != $foodItem->created_at)
                                            | Updated: {{ $foodItem->updated_at->format('M d, Y') }}
                                        @endif
                                    </small>
                                </div>
                                <div>
                                    <form action="{{ route('hotel-owner.food-items.destroy', $foodItem) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this food item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash me-1"></i>Delete Item
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar me-2"></i>Item Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <span class="badge bg-{{ $foodItem->is_available ? 'success' : 'secondary' }}">
                                {{ $foodItem->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Popular:</span>
                            <span class="badge bg-{{ $foodItem->is_popular ? 'warning' : 'light text-dark' }}">
                                {{ $foodItem->is_popular ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Orders:</span>
                            <span class="badge bg-info">0</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Revenue:</span>
                            <span class="badge bg-success">₹0.00</span>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <button class="btn btn-outline-primary btn-sm" disabled>
                            <i class="fas fa-chart-line me-1"></i>View Analytics
                        </button>
                        <p class="small text-muted mt-2">Analytics coming soon</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-cogs me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('hotel-owner.food-items.update', $foodItem) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_available" value="{{ $foodItem->is_available ? '0' : '1' }}">
                            <input type="hidden" name="name" value="{{ $foodItem->name }}">
                            <input type="hidden" name="category" value="{{ $foodItem->category }}">
                            <input type="hidden" name="price" value="{{ $foodItem->price }}">
                            <input type="hidden" name="food_type" value="{{ $foodItem->food_type }}">
                            <button type="submit" class="btn btn-{{ $foodItem->is_available ? 'warning' : 'success' }} btn-sm w-100">
                                <i class="fas fa-{{ $foodItem->is_available ? 'pause' : 'play' }} me-1"></i>
                                {{ $foodItem->is_available ? 'Mark Unavailable' : 'Mark Available' }}
                            </button>
                        </form>
                        
                        <form action="{{ route('hotel-owner.food-items.update', $foodItem) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_popular" value="{{ $foodItem->is_popular ? '0' : '1' }}">
                            <input type="hidden" name="name" value="{{ $foodItem->name }}">
                            <input type="hidden" name="category" value="{{ $foodItem->category }}">
                            <input type="hidden" name="price" value="{{ $foodItem->price }}">
                            <input type="hidden" name="food_type" value="{{ $foodItem->food_type }}">
                            <button type="submit" class="btn btn-{{ $foodItem->is_popular ? 'outline-warning' : 'warning' }} btn-sm w-100">
                                <i class="fas fa-star me-1"></i>
                                {{ $foodItem->is_popular ? 'Remove from Popular' : 'Make Popular' }}
                            </button>
                        </form>
                        
                        <a href="{{ route('hotel-owner.food-items.edit', $foodItem) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit Details
                        </a>
                        
                        <button class="btn btn-outline-info btn-sm" disabled>
                            <i class="fas fa-copy me-1"></i>Duplicate Item
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection