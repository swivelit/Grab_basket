 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Grabbaskets</title>
   
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
 
    <style>
    /* ------------------------------
   Global Body – Royal Theme
-------------------------------*/
body {
    background: white;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    margin: 0;
}
 
/* ------------------------------
   Main Card
-------------------------------*/
.container-card {
    display: flex;
    flex-wrap: wrap;
    max-width: 1000px;
    width: 100%;
    border-radius: 1.2rem;
    overflow: hidden;
    background: #fffdf7;
    box-shadow: 0 15px 45px rgba(80, 47, 22, 0.25);
    border: 1px solid rgba(219, 186, 150, 0.6);
}
 
/* ------------------------------
   Left Branding Section
-------------------------------*/
.left-box {
    flex: 1 1 35%;
    background: linear-gradient(160deg, #7b4c2e, #b57a51, #e6c7a2);
    color: #fffdf6;
    padding: 50px 25px;
    text-align: center;
}
 
.left-box img {
    width: 180px;
    border-radius: 1rem;
    margin-bottom: 25px;
    border: 3px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 6px 20px rgba(48, 27, 11, 0.4);
}
 
.left-box p {
    font-size: 1.15rem;
    line-height: 1.6;
    font-weight: 500;
}
 
/* ------------------------------
   Right Form Section
-------------------------------*/
.right-box {
    flex: 1 1 65%;
    background: #faf4ec;
    padding: 50px 30px;
}
 
/* ------------------------------
   Input Fields
-------------------------------*/
input.form-control,
select.form-select,
textarea.form-control {
    border-radius: 0.8rem;
    border: 1px solid #c9a57c;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    margin-bottom: 15px;
    background: #fffdf9;
    transition: 0.3s ease;
}
 
input.form-control:focus,
select.form-select:focus,
textarea.form-control:focus {
    border-color: #8a5a3a;
    box-shadow: 0 0 0 0.15rem rgba(138, 90, 58, 0.25);
    background: #fff;
}
 
/* Hover */
input.form-control:hover,
select.form-select:hover,
textarea.form-control:hover {
    border-color: #b17850;
    box-shadow: 0 0 0 0.12rem rgba(177,120,80,0.18);
}
 
/* Labels */
label {
    font-weight: 700;
    color: #6b4224;
    margin-bottom: 5px;
}
 
/* ------------------------------
   Buttons – Royal Gold Gradient
-------------------------------*/
.btn-gradient {
    background: linear-gradient(90deg, #8a5a3a, #b2815c, #d6ab82);
    border: 2px solid #e3c9a6;
    border-radius: 1rem;
    font-weight: 700;
    padding: 12px;
    width: 100%;
    color: #fff;
    transition: 0.35s ease;
}
 
.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 18px rgba(138, 90, 58, 0.35);
}
 
/* Outline Button */
.btn-outline-pro {
    border-radius: 1rem;
    border: 2px solid #8a5a3a;
    padding: 12px;
    font-weight: 600;
    width: 100%;
    color: #8a5a3a;
    background: #fffdf8;
    transition: 0.3s ease;
}
 
.btn-outline-pro:hover {
    background: #8a5a3a;
    color: #fff;
    transform: translateY(-2px);
}
 
/* Button Group */
.btn-horizontal-group {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}
 
.btn-horizontal-group .btn {
    flex: 1;
}
 
/* ------------------------------
   Error Message
-------------------------------*/
.text-danger {
    font-size: 0.85rem;
    color: #ba1c1c;
    margin-top: 3px;
}
 
/* ------------------------------
   Responsive
-------------------------------*/
@media(max-width: 768px) {
    body { align-items: flex-start; padding: 10px; }
    .container-card { flex-direction: column; }
    .left-box, .right-box { padding: 30px 20px; }
    .btn-horizontal-group { flex-direction: column; }
}
 
/* Row Spacing */
.row.g-4 > [class^='col-'] { margin-bottom: 18px; }
 
    </style>
</head>
 
<body>
<div class="container-card">
    <div style="width:100%;background:linear-gradient(90deg, #8a5a3a, #b2815c, #d6ab82);;color:#fff;padding:18px 0 12px 0;text-align:center;font-size:1.6rem;font-weight:700;letter-spacing:1px;box-shadow:0 2px 8px rgba(59,130,246,0.08);margin-bottom:0.5rem;">
        <i class="fas fa-pen-to-square me-2"></i> Edit Product
    </div>
    <!-- Left Box -->
    <div class="left-box">
        <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Grabbasket Logo">
        <p>Welcome to <strong>Grabbasket</strong>!<br>
           Edit your product details and keep your inventory up to date.</p>
        <div class="mt-4" style="width:100%;display:flex;flex-direction:column;align-items:center;">
            @if($product->image_url)
                <div style="background:#fff;border-radius:1rem;padding:18px 12px 10px 12px;box-shadow:0 2px 12px rgba(59,130,246,0.10);width:210px;max-width:100%;margin-bottom:10px;">
                    <img src="{{ $product->image_url }}"
                         alt="{{ $product->name }}"
                         style="width:180px;max-height:180px;border-radius:0.7rem;border:2px solid #e0e7ef;box-shadow:0 2px 8px rgba(59,130,246,0.08);background:#fafafa;object-fit:contain;"
                         onerror="this.onerror=null;
                                  if(this.src.includes('githubusercontent.com')) {
                                      const path = this.src.split('/storage/app/public/')[1];
                                      this.src = '{{ url('/serve-image/') }}/' + path;
                                  }">
                </div>
                <div class="text-white small mt-2">Current Product Image</div>
                @if($product->image)
                    <div class="text-white small mt-1">Path: <code style="font-size:0.75rem;background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;">{{ $product->image }}</code></div>
                @endif
            @else
                <div style="padding: 20px; background: rgba(255,255,255,0.1); border-radius: 1rem;">
                    <i class="fas fa-upload" style="font-size: 2rem; opacity: 0.5;"></i>
                    <div class="text-white small mt-2">Upload an image for this product</div>
                    @if($product->image)
                        <div class="text-warning small mt-2">Image path set: <code>{{ $product->image }}</code> but not found in storage.<br>Check if file exists in R2/public disk.</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
 
    <!-- Right Box -->
    <div class="right-box">
    <h2 class="mb-4" style="font-weight:700;color:#3b82f6;letter-spacing:1px;">Edit Product Details</h2>
       
        @if(session('success'))
            <div class="alert alert-success text-dark">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger text-dark">{{ session('error') }}</div>
        @endif
 
        <form method="POST" action="{{ route('seller.updateProduct', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-4">
 
                <div class="col-md-6">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" class="form-control" required value="{{ old('name', $product->name) }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-md-6">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-md-6">
                    <label for="subcategory_id">Subcategory</label>
                    <select id="subcategory_id" name="subcategory_id" class="form-select" required>
                        <option value="">Select Subcategory</option>
                        @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" data-category-id="{{ $subcategory->category_id }}"
                                {{ old('subcategory_id', $product->subcategory_id) == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subcategory_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-md-6">
                    <label for="price">Price</label>
                    <input type="number" step="0.01" id="price" name="price" class="form-control" required value="{{ old('price', $product->price) }}">
                    @error('price') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-md-6">
                    <label for="discount">Discount (%)</label>
                    <input type="number" step="0.01" id="discount" name="discount" class="form-control" value="{{ old('discount', $product->discount) }}">
                    @error('discount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-md-6">
                    <label for="delivery_charge">Delivery Charge</label>
                    <input type="number" step="0.01" id="delivery_charge" name="delivery_charge" class="form-control" value="{{ old('delivery_charge', $product->delivery_charge) }}">
                    @error('delivery_charge') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
        <div class="col-md-6">          
<label>10 Minutes Delivery?</label>
<select name="ten_min_delivery" class="form-control" required>
    <option value="no" {{ old('ten_min_delivery', isset($isTenMin) && $isTenMin ? 'yes' : 'no') == 'no' ? 'selected' : '' }}>No</option>
    <option value="yes" {{ old('ten_min_delivery', isset($isTenMin) && $isTenMin ? 'yes' : 'no') == 'yes' ? 'selected' : '' }}>Yes</option>
</select>
</div>
                <div class="col-md-6">
                    <label for="gift_option">Gift Option Available?</label>
                    <select id="gift_option" name="gift_option" class="form-select" required>
                        <option value="">Select</option>
                        <option value="yes" {{ old('gift_option', $product->gift_option) == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('gift_option', $product->gift_option) == 'no' ? 'selected' : '' }}>No</option>
                    </select>
                    @error('gift_option') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-md-6">
                    <label for="stock">Number of Stock Held</label>
                    <input type="number" id="stock" name="stock" class="form-control" min="0" required value="{{ old('stock', $product->stock) }}">
                    @error('stock') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-12">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required>{{ old('description', $product->description) }}</textarea>
                    @error('description') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
 
                <div class="col-12">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <input type="hidden" id="libraryImageUrl" name="library_image_url">
                    @error('image') <div class="text-danger" style="font-weight:600;">{{ $message }}</div> @enderror
                    <small class="text-muted">Choose a new image to replace the current one. Max size: 2MB</small>
                   
                    <!-- OR Select from Library -->
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imageLibraryModal">
                            <i class="fas fa-images me-1"></i> Or Select from Library
                        </button>
                    </div>
 
                    <!-- Image Preview -->
                    <div id="imagePreview" style="display: none; margin-top: 10px; text-align:center;">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 170px; max-height: 170px; border-radius: 10px; border: 2px solid #3b82f6; box-shadow:0 2px 8px rgba(59,130,246,0.10);">
                        <div><small class="text-muted">New image preview</small></div>
                    </div>
                </div>
 
            </div>
 
            <!-- Horizontal Buttons -->
            <div class="btn-horizontal-group">
                <button type="submit" class="btn btn-gradient">Update Product</button>
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline-pro">Back to Dashboard</a>
            </div>
 
        </form>
    </div>
</div>
 
{{-- Include Image Library Modal --}}
@include('seller.partials.image-library-modal')
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 
<script>
// Handle library image selection
function handleLibraryImageSelection(url) {
    // Set the URL in hidden input
    document.getElementById('libraryImageUrl').value = url;
   
    // Clear file input
    document.getElementById('image').value = '';
   
    // Show preview
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    previewImg.src = url;
    preview.style.display = 'block';
}
 
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
   
    function filterSubcategories(catId) {
        let hasAny = false;
        for (let i = 0; i < subcategorySelect.options.length; i++) {
            const opt = subcategorySelect.options[i];
            const match = String(opt.dataset.categoryId) === String(catId);
            opt.hidden = !match;
            opt.disabled = !match;
            if (match) hasAny = true;
        }
        subcategorySelect.disabled = !catId || !hasAny;
        if (!catId || !subcategorySelect.selectedOptions[0] || subcategorySelect.selectedOptions[0].disabled) {
            subcategorySelect.selectedIndex = 0;
        }
    }
   
    categorySelect.addEventListener('change', function () {
        filterSubcategories(this.value);
    });
   
    filterSubcategories(categorySelect.value);
});
 
// Image preview function
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
   
    if (input.files && input.files[0]) {
        const reader = new FileReader();
       
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
       
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>
 
</body>
</html>
 
 