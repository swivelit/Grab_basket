@extends('layouts.minimal')

@section('title', 'Edit Food Item')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-edit me-2"></i>Edit Food Item: {{ $foodItem->name }}</h4>
                    <div>
                        <a href="{{ route('hotel-owner.food-items.show', $foodItem) }}" class="btn btn-info btn-sm me-2">
                            <i class="fas fa-eye me-1"></i>View
                        </a>
                        <a href="{{ route('hotel-owner.food-items.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('hotel-owner.food-items.update', $foodItem) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label required">Food Item Name</label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $foodItem->name) }}"
                                    placeholder="e.g., Butter Chicken"
                                    required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label required">Category</label>
                                <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="appetizer" {{ old('category', $foodItem->category) == 'appetizer' ? 'selected' : '' }}>Appetizer</option>
                                    <option value="main_course" {{ old('category', $foodItem->category) == 'main_course' ? 'selected' : '' }}>Main Course</option>
                                    <option value="dessert" {{ old('category', $foodItem->category) == 'dessert' ? 'selected' : '' }}>Dessert</option>
                                    <option value="beverage" {{ old('category', $foodItem->category) == 'beverage' ? 'selected' : '' }}>Beverage</option>
                                    <option value="snack" {{ old('category', $foodItem->category) == 'snack' ? 'selected' : '' }}>Snack</option>
                                    <option value="salad" {{ old('category', $foodItem->category) == 'salad' ? 'selected' : '' }}>Salad</option>
                                    <option value="soup" {{ old('category', $foodItem->category) == 'soup' ? 'selected' : '' }}>Soup</option>
                                    <option value="bread" {{ old('category', $foodItem->category) == 'bread' ? 'selected' : '' }}>Bread</option>
                                    <option value="rice" {{ old('category', $foodItem->category) == 'rice' ? 'selected' : '' }}>Rice</option>
                                    <option value="bread" {{ old('category', $foodItem->category) == 'Briyani' ? 'selected' : '' }}>Briyani</option>
                                    <option value="burger" {{ old('category', $foodItem->category) == 'burger' ? 'selected' : '' }}>Burger</option>
                                    <option value="Pizza" {{ old('category', $foodItem->category) == 'Pizza' ? 'selected' : '' }}>Pizza</option>
                                    <option value="Seefood" {{ old('category', $foodItem->category) == 'Seefood' ? 'selected' : '' }}>Seefood</option>
                                    <option value="chicken" {{ old('category', $foodItem->category) == 'chicken' ? 'selected' : '' }}>Chicken</option>
                                    <option value="staters" {{  old('category', $foodItem->category) == 'staters' ? 'selected' : '' }}>Staters</option>
                                    <option value="chicken" {{  old('category', $foodItem->category) == 'chicken' ? 'selected' : '' }}>Chicken</option>
                                    <option value="burger" {{  old('category', $foodItem->category) == 'burger' ? 'selected' : '' }}>Burger</option>
                                    <option value="pizza" {{  old('category', $foodItem->category) == 'pizza' ? 'selected' : '' }}>pizza</option>
                                    <option value="seefoods" {{  old('category', $foodItem->category) == 'seefoods' ? 'selected' : '' }}>See foods</option>
                                    <option value="briyani" {{  old('category', $foodItem->category) == 'briyani' ? 'selected' : '' }}>Briyani</option>
                                    <option value="mutton" {{  old('category', $foodItem->category) == 'mutton' ? 'selected' : '' }}>Mutton</option>

                                </select>
                                @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Describe your food item...">{{ old('description', $foodItem->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label required">Price (₹)</label>
                                <input type="number"
                                    class="form-control @error('price') is-invalid @enderror"
                                    id="price"
                                    name="price"
                                    value="{{ old('price', $foodItem->price) }}"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    required>
                                @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="discounted_price" class="form-label">Discounted Price (₹)</label>
                                <input type="number"
                                    class="form-control @error('discounted_price') is-invalid @enderror"
                                    id="discounted_price"
                                    name="discounted_price"
                                    value="{{ old('discounted_price', $foodItem->discounted_price) }}"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00">
                                @error('discounted_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="preparation_time" class="form-label">Prep Time (minutes)</label>
                                <input type="number"
                                    class="form-control @error('preparation_time') is-invalid @enderror"
                                    id="preparation_time"
                                    name="preparation_time"
                                    value="{{ old('preparation_time', $foodItem->preparation_time) }}"
                                    min="1"
                                    placeholder="15">
                                @error('preparation_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="food_type" class="form-label required">Food Type</label>
                                <select class="form-select @error('food_type') is-invalid @enderror" id="food_type" name="food_type" required>
                                    <option value="">Select Type</option>
                                    <option value="veg" {{ old('food_type', $foodItem->food_type) == 'veg' ? 'selected' : '' }}>Vegetarian</option>
                                    <option value="non-veg" {{ old('food_type', $foodItem->food_type) == 'non-veg' ? 'selected' : '' }}>Non-Vegetarian</option>
                                </select>
                                @error('food_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="spice_level" class="form-label">Spice Level</label>
                                <select class="form-select @error('spice_level') is-invalid @enderror" id="spice_level" name="spice_level">
                                    <option value="">Select Spice Level</option>
                                    <option value="mild" {{ old('spice_level', $foodItem->spice_level) == 'mild' ? 'selected' : '' }}>Mild</option>
                                    <option value="medium" {{ old('spice_level', $foodItem->spice_level) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="hot" {{ old('spice_level', $foodItem->spice_level) == 'hot' ? 'selected' : '' }}>Hot</option>
                                    <option value="very_hot" {{ old('spice_level', $foodItem->spice_level) == 'very_hot' ? 'selected' : '' }}>Very Hot</option>
                                </select>
                                @error('spice_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="serves" class="form-label">Serves (people)</label>
                                <input type="number"
                                    class="form-control @error('serves') is-invalid @enderror"
                                    id="serves"
                                    name="serves"
                                    value="{{ old('serves', $foodItem->serves) }}"
                                    min="1"
                                    placeholder="1">
                                @error('serves')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ingredients" class="form-label">Ingredients</label>
                            <textarea class="form-control @error('ingredients') is-invalid @enderror"
                                id="ingredients"
                                name="ingredients"
                                rows="3"
                                placeholder="List main ingredients (comma separated)">{{ old('ingredients', $foodItem->ingredients) }}</textarea>
                            @error('ingredients')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="allergens" class="form-label">Allergens</label>
                            <input type="text"
                                class="form-control @error('allergens') is-invalid @enderror"
                                id="allergens"
                                name="allergens"
                                value="{{ old('allergens', $foodItem->allergens) }}"
                                placeholder="e.g., Nuts, Dairy, Gluten">
                            @error('allergens')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Food Image</label>
                            @if($foodItem->first_image_url)
                            <div class="mb-2">
                                <img src="{{ $foodItem->first_image_url }}"
                                     alt="{{ $foodItem->name }}"
                                     class="img-thumbnail"
                                     style="max-height: 150px;"
                                     onerror="this.onerror=null;this.src='https://via.placeholder.com/150?text=Error+Loading';">
                                <p class="small text-muted mt-1">Current image</p>
                            </div>
                            @endif
                            <input type="file"
                                class="form-control @error('image') is-invalid @enderror"
                                id="image"
                                name="image"
                                accept="image/*">
                            <div class="form-text">Upload a new image to replace the current one (JPG, PNG, max 2MB)</div>
                            @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        id="is_available"
                                        name="is_available"
                                        value="1"
                                        {{ old('is_available', $foodItem->is_available) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_available">
                                        Available for ordering
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        id="is_featured"
                                        name="is_featured"
                                        value="1"
                                        {{ old('is_featured', $foodItem->is_featured) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_featured">
                                        Featured item
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('hotel-owner.food-items.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <div>
                                <a href="{{ route('hotel-owner.food-items.show', $foodItem) }}" class="btn btn-info me-2">
                                    <i class="fas fa-eye me-1"></i>View Item
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Food Item
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .required::after {
        content: ' *';
        color: red;
    }
</style>
@endsection