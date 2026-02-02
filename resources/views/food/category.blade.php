<!DOCTYPE html>
<html>
<head>
    <title>{{ $category }} - Food Category</title>
</head>
<body>

<h1>Category: {{ $category }}</h1>

@if($foodItems->count())
    <ul>
        @foreach($foodItems as $item)
            <li>{{ $item->name }} - ₹{{ $item->price }}</li>
        @endforeach
    </ul>

    {{ $foodItems->links() }}
@else
    <p>No food items found in this category.</p>
@endif

</body>
</html>
