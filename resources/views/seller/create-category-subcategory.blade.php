<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Category & Subcategory</title>

<!-- Replace Laravel icon with your project logo -->
<link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Body & background */
body {
    background: linear-gradient(135deg, #e0f7fa, #80deea);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

/* Container Card */
.container-card {
    display: flex;
    flex-wrap: wrap;
    max-width: 900px;
    width: 100%;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    background: #ffffff;
}

/* Left Box - Branding */
.left-box {
    flex: 1 1 40%;
    background: linear-gradient(135deg, #3b82f6, #06b6d4);
    color: #fff;
    padding: 40px 25px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}
.left-box img {
    width: 200px;
    height: auto;
    border-radius: 1rem;
    margin-bottom: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}
.left-box p {
    font-size: 1rem;
    line-height: 1.5;
    font-weight: 500;
}

/* Right Box - Form */
.right-box {
    flex: 1 1 60%;
    background: #f3f4f6;
    padding: 40px 30px;
}

/* Inputs */
input.form-control, select.form-select {
    border-radius: 0.6rem;
    border: 1px solid #d1d5db;
    padding: 12px 15px;
    background: #ffffff;
    color: #1f2937;
    font-size: 0.95rem;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}
input.form-control:focus, select.form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.25);
    outline: none;
}

/* Labels */
label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
}

/* Buttons */
.btn-gradient {
    background: linear-gradient(90deg, #3b82f6, #06b6d4);
    border: none;
    border-radius: 0.6rem;
    font-weight: 600;
    color: #fff;
    padding: 12px;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    width: 100%;
    margin-top: 10px;
}
.btn-gradient::after {
    content: '';
    position: absolute;
    top: 0; left: -75%;
    width: 50%; height: 100%;
    background: rgba(255, 255, 255, 0.2);
    transform: skewX(-25deg);
    transition: all 0.5s ease;
}
.btn-gradient:hover::after {
    left: 125%;
}
.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(59,130,246,0.4);
}

.btn-outline-pro {
    border-radius: 0.6rem;
    border: 1px solid #9ca3af;
    color: #374151;
    font-weight: 600;
    padding: 12px;
    width: 100%;
    transition: all 0.3s ease;
}
.btn-outline-pro:hover {
    background: rgba(107,114,128,0.1);
}

/* Error */
.text-danger {
    font-size: 0.85rem;
    margin-top: 3px;
}

/* Responsive */
@media(max-width: 768px) {
    .container-card {
        flex-direction: column;
    }
    .left-box, .right-box {
        flex: 1 1 100%;
        padding: 30px 20px;
    }
}
</style>
</head>
<body>

<div class="container-card">
    <!-- Left Box -->
    <div class="left-box">
        <img src="{{ asset('asset/images/grabbasket.png') }}" alt="GrabBasket Logo">
        <p>Welcome to <strong>GrabBaskets</strong>!<br>
           Your trusted marketplace to manage products, categories, and subcategories with ease.</p>
    </div>

    <!-- Right Box -->
    <div class="right-box">
        <h2 class="mb-4">Add Category & Subcategory</h2>

        @if(session('success'))
            <div class="alert alert-success text-dark">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger text-dark">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('seller.storeCategorySubcategory') }}">
            @csrf

            <label for="category_name">Category Name</label>
            <input type="text" id="category_name" name="category_name" class="form-control text-uppercase" placeholder="Enter category name">
            @error('category_name') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="category_unique_id">Category ID</label>
            <input type="text" id="category_unique_id" name="category_unique_id" class="form-control text-uppercase" maxlength="3" placeholder="ABC">
            @error('category_unique_id') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="existing_category_id">Select existing Category ID</label>
            <select id="existing_category_id" name="existing_category_id" class="form-select">
                <option value="">-- Select Category ID --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->unique_id }}">{{ $cat->unique_id }} ({{ $cat->name }})</option>
                @endforeach
            </select>

            <label for="existing_category">Select existing category</label>
            <select id="existing_category" name="existing_category" class="form-select">
                <option value="">-- Select Category --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }} (ID: {{ $cat->unique_id }})</option>
                @endforeach
            </select>

            <label for="subcategory_name">Subcategory Name</label>
            <input type="text" id="subcategory_name" name="subcategory_name" class="form-control text-uppercase" placeholder="Enter subcategory name" required>
            @error('subcategory_name') <div class="text-danger">{{ $message }}</div> @enderror

            <button type="submit" class="btn btn-gradient">Add Category & Subcategory</button>
            <a href="/seller/dashboard" class="btn btn-outline-pro mt-3">Dashboard</a>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const catNameDiv = document.getElementById('catNameDiv');
    const catIdDiv = document.getElementById('catIdDiv');
    const existingCat = document.getElementById('existing_category');
    const existingCatId = document.getElementById('existing_category_id');

    function toggleFields() {
        if(catNameDiv && catIdDiv) {
            catNameDiv.style.display = existingCat.value ? 'none' : '';
            catIdDiv.style.display = existingCatId.value ? 'none' : '';
        }
    }

    if(existingCat) existingCat.addEventListener('change', toggleFields);
    if(existingCatId) existingCatId.addEventListener('change', toggleFields);
    toggleFields();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>