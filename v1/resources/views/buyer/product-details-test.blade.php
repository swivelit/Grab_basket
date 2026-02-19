<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Product Details</title>
</head>
<body>
    <h1>Product Details Test</h1>
    
    <div>
        <h2>{{ $product->name }}</h2>
        <p><strong>ID:</strong> {{ $product->id }}</p>
        <p><strong>Image Path:</strong> {{ $product->image }}</p>
        <p><strong>Image URL:</strong> {{ $product->image_url }}</p>
        
        @if($product->image)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="max-width: 300px;">
        @else
            <p>No image available</p>
        @endif
    </div>
    
    <p><strong>Test successful!</strong> The controller and model are working.</p>
</body>
</html>