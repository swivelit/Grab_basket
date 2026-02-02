<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>GrabBasket — Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --accent: #FF6B00; }
        body { background: #f8f9fa; }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <h2>🛒 Your Cart</h2>
    <a href="{{ route('customer.food.index') }}" class="btn btn-outline-secondary mb-3">← Continue Shopping</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($cart))
        <div class="text-center py-5">
            <p>Your cart is empty.</p>
            <a href="{{ route('customer.food.index') }}" class="btn btn-primary">Browse Menu</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $item['image'] ?? 'https://via.placeholder.com/80' }}" class="me-3">
                                <div>
                                    <div>{{ $item['name'] }}</div>
                                    <small class="text-muted">{{ ucfirst($item['food_type']) }}</small>
                                </div>
                            </div>
                        </td>
                        <td>₹{{ number_format($item['price'], 0) }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>₹{{ number_format($item['price'] * $item['quantity'], 0) }}</td>
                        <td>
                            <a href="{{ route('customer.food.cart.remove', $item['id']) }}" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Remove this item?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th>₹{{ number_format($total, 0) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-3">
            <a href="{{ route('customer.food.index') }}" class="btn btn-secondary">Add More Items</a>
            <button class="btn btn-success">Proceed to Checkout</button>
        </div>
    @endif
</div>

</body>
</html>