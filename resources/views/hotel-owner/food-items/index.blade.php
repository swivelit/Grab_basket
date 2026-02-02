@extends('layouts.minimal')

@section('title', 'Food Items')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-hamburger me-2"></i>Food Items
        </h2>

        <div>
            <a href="{{ route('hotel-owner.dashboard') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
            <a href="{{ route('hotel-owner.food-items.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Food Item
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Food Items --}}
    @if($foodItems->count())
        <div class="row">
            @foreach($foodItems as $item)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">

                        {{-- IMAGE --}}
                        @if($item->first_image_url)
                            <img src="{{ $item->first_image_url }}"
                                 class="card-img-top"
                                 style="height:200px; object-fit:contain;"
                                 alt="{{ $item->name }}">
                        @else
                            <div class="card-img-top bg-light d-flex justify-content-center align-items-center"
                                 style="height:200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        @endif

                        {{-- BODY --}}
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">{{ $item->name }}</h5>

                                <span class="badge bg-{{ $item->food_type === 'veg' ? 'success' : 'danger' }}">
                                    {{ strtoupper($item->food_type) }}
                                </span>
                            </div>

                            <p class="text-muted small">
                                {{ Str::limit($item->description, 80) }}
                            </p>

                            <div class="mb-2">
                                <span class="badge bg-secondary">{{ ucfirst($item->category) }}</span>

                                @if($item->spice_level)
                                    <span class="badge bg-warning text-dark">
                                        {{ ucfirst($item->spice_level) }}
                                    </span>
                                @endif
                            </div>

                            {{-- PRICE --}}
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong class="text-primary">
                                            ₹{{ number_format($item->discounted_price ?? $item->price, 2) }}
                                        </strong>

                                        @if($item->discounted_price)
                                            <small class="text-muted ms-1">
                                                <del>₹{{ number_format($item->price, 2) }}</del>
                                            </small>
                                        @endif
                                    </div>

                                    <span class="badge bg-{{ $item->is_available ? 'success' : 'secondary' }}">
                                        {{ $item->is_available ? 'Available' : 'Unavailable' }}
                                    </span>
                                </div>

                                {{-- ACTIONS --}}
                                <div class="btn-group w-100">
                                    <a href="{{ route('hotel-owner.food-items.show', $item->id) }}"
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="{{ route('hotel-owner.food-items.edit', $item->id) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('hotel-owner.food-items.destroy', $item->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Delete this food item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $foodItems->links() }}
        </div>
    @else
        {{-- EMPTY STATE --}}
        <div class="text-center py-5">
            <i class="fas fa-hamburger fa-5x text-muted mb-4"></i>
            <h4 class="text-muted">No Food Items Added</h4>
            <p class="text-muted">Start building your menu by adding food items.</p>

            <a href="{{ route('hotel-owner.food-items.create') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Add Food Item
            </a>
        </div>
    @endif
</div>
@endsection
