<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $categoryName }} - GrabBasket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f4f7;
            font-family: 'Poppins', sans-serif;
        }
        .food-card {
            transition: transform 0.2s;
        }
        .food-card:hover {
            transform: translateY(-4px);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary">Category: {{ ucwords(str_replace('_', ' ', $categoryName)) }}</h2>
            <a href="{{ route('customer.food.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to All
            </a>
        </div>

        @if($foods->isEmpty())
            <div class="text-center py-5">
                <div class="text-muted">No food items available in this category.</div>
            </div>
        @else
            <div class="row g-4">
                @foreach($foods as $food)
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card food-card h-100 shadow-sm">
                            @if(!empty($food->images) && is_array($food->images) && !empty($food->images[0]))
                                <img src="{{ $food->images[0] }}" class="card-img-top" alt="{{ $food->name }}" style="height: 180px; object-fit: cover;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <i class="fas fa-utensils text-muted" style="font-size: 48px;"></i>
                                </div>
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $food->name }}</h5>
                                @if($food->rating)
                                    <div class="text-warning mb-2">
                                        <i class="fas fa-star"></i> {{ number_format($food->rating, 1) }}
                                    </div>
                                @endif
                                <p class="card-text flex-grow-1">
                                    @if($food->description)
                                        {{ Str::limit($food->description, 60) }}
                                    @else
                                        <span class="text-muted">No description</span>
                                    @endif
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-success">₹{{ $food->getFinalPrice() }}</strong>
                                        @if($food->discounted_price)
                                            <small class="text-muted text-decoration-line-through">
                                                ₹{{ $food->price }}
                                            </small>
                                        @endif
                                    </div>
                                    <a href="{{ route('customer.food.details', $food->id) }}" class="btn btn-primary w-100 mt-2">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $foods->links() }}
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>