<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\User;
use App\Imports\ProductsImport;
use App\Services\GitHubImageService;
use App\Models\TenMinDeliveryProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\TenMinGroceryCartItem;

use Exception;
#use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Razorpay\Api\Api;
use App\Models\UserWalletTransaction;

class SellerController extends Controller
{
    // Razorpay Live Keys
    private $razorpayKeyId = 'rzp_live_RZLX30zmmnhHum';
    private $razorpayKeySecret = 'XKmsdH5PbR49EiT74CgehYYi';

    // ...existing code...

    // Bulk product upload: CSV + images
    public function bulkProductUpload(Request $request)
    {
        $request->validate([
            'products_file' => 'required|file|mimes:csv,txt',
            'images' => 'required',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);
        $sellerId = Auth::id();
        $imageMap = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $imageMap[strtolower($filename)] = $image;
            }
        }
        $file = $request->file('products_file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_map('trim', array_map('strtolower', $rows[0]));
        unset($rows[0]);
        $count = 0;
        $updatedImages = 0;
        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            if (!$data)
                continue;
            $data['seller_id'] = $sellerId;
            // Check if product exists by unique_id for this seller
            $product = null;
            if (isset($data['unique_id'])) {
                $product = Product::where('seller_id', $sellerId)
                    ->where('unique_id', $data['unique_id'])
                    ->first();
            }
            if ($product) {
                $product->fill($data);
            } else {
                $product = new Product($data);
            }
            // Attach image if available
            $uid = isset($data['unique_id']) ? strtolower($data['unique_id']) : null;
            if ($uid && isset($imageMap[$uid])) {
                $img = $imageMap[$uid];
                // Store under products/ to keep URL generation consistent
                $folder = "products/seller/{$sellerId}/{$data['category_id']}/{$data['subcategory_id']}";

                // DUAL STORAGE: Save to both AWS R2 and Git storage for redundancy
                $r2Path = null;
                $publicPath = null;
                $r2Success = false;
                $publicSuccess = false;

                // Try AWS R2 first
                try {
                    $r2Path = $img->store($folder, 'r2');
                    $r2Success = !empty($r2Path);
                } catch (\Throwable $r2Ex) {
                    Log::warning('AWS R2 upload failed during bulk product upload', [
                        'error' => $r2Ex->getMessage(),
                        'unique_id' => $uid
                    ]);
                }

                // Then save to Git storage (public disk)
                try {
                    $publicPath = $img->store($folder, 'public');
                    $publicSuccess = !empty($publicPath);
                } catch (\Throwable $publicEx) {
                    Log::warning('Git storage upload failed during bulk product upload', [
                        'error' => $publicEx->getMessage(),
                        'unique_id' => $uid
                    ]);
                }

                // Use whichever path was successful (prefer R2)
                $finalPath = $r2Success ? $r2Path : $publicPath;

                if ($finalPath) {
                    $product->image = $finalPath;
                    $updatedImages++;

                    Log::info('Bulk product image stored with dual storage redundancy', [
                        'unique_id' => $uid,
                        'path' => $finalPath,
                        'r2_success' => $r2Success,
                        'public_success' => $publicSuccess
                    ]);
                } else {
                    Log::error('Both storages failed for bulk product upload image', [
                        'unique_id' => $uid
                    ]);
                }
            }
            $product->save();
            $count++;
        }
        $msg = "$count products uploaded/updated. $updatedImages images assigned.";
        return redirect()->route('seller.dashboard')->with('bulk_upload_success', $msg);
    }
    // Display product images by seller/category/subcategory
    public function productImages(Request $request)
    {
        $query = Product::query();
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }
        $products = $query->latest()->get();
        return view('seller.product-images', compact('products'));
    }
    // Delete a product and its image
    public function destroyProduct(Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            abort(403);
        }
        if ($product->image) {
            // Try delete from both disks, ignore errors
            try {
                Storage::disk('r2')->delete($product->image);
            } catch (\Throwable $e) {
            }
            try {
                Storage::disk('public')->delete($product->image);
            } catch (\Throwable $e) {
            }
        }

        // Delete all product images
        foreach ($product->productImages as $productImage) {
            try {
                Storage::disk('r2')->delete($productImage->image_path);
            } catch (\Throwable $e) {
            }
            try {
                Storage::disk('public')->delete($productImage->image_path);
            } catch (\Throwable $e) {
            }
            $productImage->delete();
        }

        $product->delete();
        return redirect()->route('seller.dashboard')->with('success', 'Product deleted!');
    }

    // Upload multiple images for a product
    public function uploadProductImages(Request $request, Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max per image
        ]);

        $uploadedCount = 0;
        $errors = [];

        foreach ($request->file('images') as $index => $image) {
            try {
                $sellerId = Auth::id();
                $folder = 'products/seller-' . $sellerId;
                $originalName = $image->getClientOriginalName();
                $originalNameSlug = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
                $ext = $image->getClientOriginalExtension();
                $filename = $originalNameSlug . '-' . time() . '-' . Str::random(4) . '.' . $ext;
                $mimeType = $image->getMimeType();
                $fileSize = $image->getSize();

                // DUAL STORAGE: Save to both AWS R2 and Git storage for redundancy
                $r2Path = null;
                $publicPath = null;
                $r2Success = false;
                $publicSuccess = false;
                $finalPath = null;

                // Try AWS R2 first
                try {
                    $r2Path = $image->storeAs($folder, $filename, 'r2');
                    $r2Success = !empty($r2Path);
                } catch (\Throwable $r2Ex) {
                    Log::warning('AWS R2 upload failed for product gallery image', [
                        'error' => $r2Ex->getMessage(),
                        'product_id' => $product->id,
                        'original_name' => $originalName
                    ]);
                }

                // Then save to Git storage (public disk)
                try {
                    $publicPath = $image->storeAs($folder, $filename, 'public');
                    $publicSuccess = !empty($publicPath);
                } catch (\Throwable $publicEx) {
                    Log::warning('Git storage (public) upload failed for product gallery image', [
                        'error' => $publicEx->getMessage(),
                        'product_id' => $product->id,
                        'original_name' => $originalName
                    ]);
                }

                // Use whichever path was successful (prefer R2)
                $finalPath = $r2Success ? $r2Path : $publicPath;

                if (!$finalPath) {
                    throw new \Exception('Both AWS R2 and Git storage failed');
                }

                Log::info('Product gallery image stored with dual storage redundancy', [
                    'product_id' => $product->id,
                    'path' => $finalPath,
                    'r2_success' => $r2Success,
                    'public_success' => $publicSuccess,
                    'original_name' => $originalName
                ]);

                // Get the next sort order
                $nextSortOrder = ProductImage::where('product_id', $product->id)
                    ->max('sort_order') + 1;

                // Create ProductImage record
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $finalPath,
                    'original_name' => $originalName,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'sort_order' => $nextSortOrder,
                    'is_primary' => $index === 0 && $product->productImages()->count() === 0, // First image is primary if no images exist
                ]);

                $uploadedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to upload {$originalName}: " . $e->getMessage();
            }
        }

        $message = "{$uploadedCount} images uploaded successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()->back()->with('success', $message);
    }

    // Delete a specific product image
    public function deleteProductImage(ProductImage $productImage)
    {
        if ($productImage->product->seller_id !== Auth::id()) {
            abort(403);
        }

        // Delete from storage
        try {
            Storage::disk('r2')->delete($productImage->image_path);
        } catch (\Throwable $e) {
        }
        try {
            Storage::disk('public')->delete($productImage->image_path);
        } catch (\Throwable $e) {
        }

        $productImage->delete();

        return redirect()->back()->with('success', 'Image deleted successfully.');
    }

    // Set primary image
    public function setPrimaryImage(ProductImage $productImage)
    {
        if ($productImage->product->seller_id !== Auth::id()) {
            abort(403);
        }

        // Remove primary flag from all images of this product
        ProductImage::where('product_id', $productImage->product_id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $productImage->update(['is_primary' => true]);

        return redirect()->back()->with('success', 'Primary image updated.');
    }

    // Show product gallery management
    public function productGallery(Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            abort(403);
        }

        $images = $product->productImages()->ordered()->get();
        return view('seller.product-gallery', compact('product', 'images'));
    }
    public function storeProducts(\App\Models\Seller $seller)
    {
        // Get the User ID from the seller's email (products.seller_id references users.id, not sellers.id)
        $user = User::where('email', $seller->email)->first();

        if (!$user) {
            // If no matching user found, return empty products
            $products = Product::with(['category', 'subcategory'])
                ->whereNull('id') // Force empty result
                ->paginate(12);
            $productsByCategory = collect();
            return view('seller.store-products', compact('seller', 'products', 'productsByCategory'));
        }

        // Get all products by this seller grouped by category
        $productsByCategory = Product::with(['category', 'subcategory'])
            ->where('seller_id', $user->id)
            ->whereNotNull('image') // Only show products with images
            ->orderBy('category_id')
            ->get()
            ->groupBy('category_id');

        // Also get paginated products for the main display
        $products = Product::with(['category', 'subcategory'])
            ->where('seller_id', $user->id)
            ->whereNotNull('image') // Only show products with images
            ->latest()
            ->paginate(12);

        return view('seller.store-products', compact('seller', 'products', 'productsByCategory'));
    }
    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'store_name' => 'nullable|string|max:255',
                'gst_number' => 'nullable|string|max:255',
                'store_address' => 'nullable|string|max:500',
                'store_contact' => 'nullable|string|max:255',
                'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048', // 2MB max
                'avatar_url' => 'nullable|string|url|max:500', // For avatar/emoji URLs
            ]);

            $user = Auth::user();

            if (!$user) {
                Log::error('updateProfile: User not authenticated');

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please log in to update your profile.'
                    ], 401);
                }

                return redirect()->route('login')->with('error', 'Please log in to update your profile.');
            }

            $seller = \App\Models\Seller::where('email', $user->email)->first();

            if (!$seller) {
                Log::error('updateProfile: Seller not found', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Seller profile not found.'
                    ], 404);
                }

                return redirect()->back()->with('error', 'Seller profile not found.');
            }

            // Handle avatar/emoji URL (simpler than file upload)
            if ($request->has('avatar_url')) {
                $avatarUrl = $request->input('avatar_url');

                // Update user's profile picture with avatar URL
                \App\Models\User::where('id', $user->id)->update(['profile_picture' => $avatarUrl]);

                Log::info('Profile avatar updated successfully', [
                    'user_id' => $user->id,
                    'avatar_url' => $avatarUrl
                ]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Avatar updated successfully!',
                        'photo_url' => $avatarUrl
                    ]);
                }

                return redirect()->route('seller.profile')->with('success', 'Avatar updated successfully!');
            }

            // Update seller information
            $seller->update($request->only([
                'store_name',
                'gst_number',
                'store_address',
                'store_contact'
            ]));

            // Handle profile photo upload with dual storage (R2 + public)
            if ($request->hasFile('profile_photo')) {
                try {
                    $photo = $request->file('profile_photo');

                    // Generate unique filename
                    $originalName = $photo->getClientOriginalName();
                    $originalNameSlug = \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
                    $ext = $photo->getClientOriginalExtension();
                    $filename = $originalNameSlug . '-' . time() . '-' . \Illuminate\Support\Str::random(4) . '.' . $ext;
                    $folder = 'profile_photos/' . $user->id;

                    $r2Path = null;
                    $publicPath = null;
                    $r2Success = false;
                    $publicSuccess = false;
                    $finalPath = null;

                    // Try AWS R2 first
                    try {
                        $r2Path = $photo->storeAs($folder, $filename, 'r2');
                        $r2Success = !empty($r2Path);
                    } catch (\Throwable $r2Ex) {
                        \Log::warning('AWS R2 profile photo upload failed', [
                            'error' => $r2Ex->getMessage(),
                            'user_id' => $user->id,
                            'original_name' => $originalName
                        ]);
                    }

                    // Then try public disk (local/Git storage)
                    try {
                        $publicPath = $photo->storeAs($folder, $filename, 'public');
                        $publicSuccess = !empty($publicPath);
                    } catch (\Throwable $publicEx) {
                        \Log::warning('Public disk profile photo upload failed', [
                            'error' => $publicEx->getMessage(),
                            'user_id' => $user->id,
                            'original_name' => $originalName
                        ]);
                    }

                    // Prefer R2 if available, otherwise fall back to public
                    $finalPath = $r2Success ? $r2Path : $publicPath;

                    if (!$finalPath) {
                        throw new \Exception('Both AWS R2 and public storage failed to save the profile photo.');
                    }

                    // Build public URL — FIXED: removed trailing space!
                    $r2PublicUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud'; // ← NO TRAILING SPACE
                    $photoUrl = $r2Success
                        ? $r2PublicUrl . '/' . $finalPath
                        // : Storage::disk('public')->url($finalPath);
                        : asset('storage/' . $finalPath);

                    // Delete old profile photo if it exists
                    if ($user->profile_picture) {
                        try {
                            // Only delete if it's a managed profile photo (not an external avatar URL)
                            if (str_starts_with($user->profile_picture, $r2PublicUrl) || str_contains($user->profile_picture, '/storage/')) {
                                // Extract path for R2
                                if (str_starts_with($user->profile_picture, $r2PublicUrl)) {
                                    $oldPath = str_replace($r2PublicUrl . '/', '', $user->profile_picture);
                                    if (str_starts_with($oldPath, 'profile_photos/')) {
                                        Storage::disk('r2')->delete($oldPath);
                                        \Log::info('Deleted old R2 profile photo', ['path' => $oldPath]);
                                    }
                                }
                                // Extract path for public disk
                                elseif (str_contains($user->profile_picture, '/storage/')) {
                                    $oldPath = str_replace('/storage/', '', $user->profile_picture);
                                    if (str_starts_with($oldPath, 'profile_photos/')) {
                                        Storage::disk('public')->delete($oldPath);
                                        \Log::info('Deleted old public profile photo', ['path' => $oldPath]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to delete old profile photo', [
                                'error' => $e->getMessage(),
                                'old_url' => $user->profile_picture
                            ]);
                        }
                    }

                    // Update user's profile picture
                    \App\Models\User::where('id', $user->id)->update(['profile_picture' => $photoUrl]);

                    \Log::info('Profile photo updated with dual storage', [
                        'user_id' => $user->id,
                        'photo_url' => $photoUrl,
                        'r2_success' => $r2Success,
                        'public_success' => $publicSuccess
                    ]);

                    // Return response based on request type
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Profile photo updated successfully!',
                            'photo_url' => $photoUrl
                        ]);
                    }

                    return redirect()->route('seller.profile')->with('success', 'Profile photo and store info updated successfully!');
                } catch (\Exception $e) {
                    \Log::error('Profile photo upload failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Upload failed: ' . $e->getMessage()
                        ], 500);
                    }

                    return redirect()->back()->with('error', 'Profile photo upload failed: ' . $e->getMessage());
                }
            }

            // Final return when only seller info was updated (no avatar, no file)
            return redirect()->route('seller.profile')->with('success', 'Store info updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please fix the validation errors.');
        } catch (\Exception $e) {
            Log::error('updateProfile error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to update profile. Please try again.');
        }
    }

    // The following block is your original commented-out code (preserved as requested):
    /*
//         // Handle profile photo upload
//         if ($request->hasFile('profile_photo')) {
//             try {
//                 $photo = $request->file('profile_photo');

//                 // Generate unique filename
//                 $filename = 'profile_photos/' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();

//                 // Upload to R2 storage
//                 Storage::disk('r2')->put($filename, file_get_contents($photo->getPathname()));

//                 // Construct the public URL (Laravel Cloud R2 public URL)
//                 $r2PublicUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud  ';
//                 $photoUrl = $r2PublicUrl . '/' . $filename;

//                 // Delete old profile photo if exists
//                 if ($user->profile_picture) {
//                     try {
//                         // Extract filename from full URL
//                         $oldFilename = str_replace($r2PublicUrl . '/', '', $user->profile_picture);

//                         // Only delete if it's a profile photo (starts with profile_photos/)
//                         if (str_starts_with($oldFilename, 'profile_photos/')) {
//                             Storage::disk('r2')->delete($oldFilename);
//                             Log::info('Deleted old profile photo', ['filename' => $oldFilename]);
//                         }
//                     } catch (\Exception $e) {
//                         Log::warning('Failed to delete old profile photo', [
//                             'error' => $e->getMessage(),
//                             'old_url' => $user->profile_picture
//                         ]);
//                     }
//                 }

//                 // Update user's profile picture in database
//                 \App\Models\User::where('id', $user->id)->update(['profile_picture' => $photoUrl]);

//                 // Reload user from database to get fresh data
//                 $user = \App\Models\User::find($user->id);

//                 Log::info('Profile photo updated successfully', [
//                     'user_id' => $user->id,
//                     'filename' => $filename,
//                     'photo_url' => $photoUrl
//                 ]);

//                 // Check if AJAX request (for WhatsApp-style upload)
//                 if ($request->ajax() || $request->wantsJson()) {
//                     return response()->json([
//                         'success' => true,
//                         'message' => 'Profile photo updated successfully!',
//                         'photo_url' => $photoUrl
//                     ]);
//                 }

//                 return redirect()->route('seller.profile')->with('success', 'Profile photo and store info updated successfully!');
//             } catch (\Exception $e) {
//                 Log::error('Profile photo upload failed', [
//                     'error' => $e->getMessage(),
//                     'trace' => $e->getTraceAsString()
//                 ]);

//                 // Check if AJAX request
//                 if ($request->ajax() || $request->wantsJson()) {
//                     return response()->json([
//                         'success' => false,
//                         'message' => 'Upload failed: ' . $e->getMessage()
//                     ], 500);
//                 }

//                 return redirect()->back()->with('error', 'Profile photo upload failed: ' . $e->getMessage());
//             }
//         }

//         return redirect()->route('seller.profile')->with('success', 'Store info updated successfully!');
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return redirect()->back()
//             ->withErrors($e->validator)
//             ->withInput()
//             ->with('error', 'Please fix the validation errors.');
//     } catch (\Exception $e) {
//         Log::error('updateProfile error', [
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ]);
//         return redirect()->back()->with('error', 'Failed to update profile. Please try again.');
//     }
// */
    public function dashboard(Request $request)
    {
        $search = $request->input('search');

        // Start with seller’s products
        $productsQuery = Product::with(['category', 'subcategory'])
            ->where('seller_id', Auth::id());

        // ✅ Apply search filter if keyword entered
        if ($search) {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subcategory', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // ✅ Get results
        $products = $productsQuery->latest()->get();

        return view('seller.dashboard', compact('products', 'search'));
    }
    /**
     * Update product images by uploading a ZIP file where each image filename is the product unique_id
     */
    public function updateImagesByZip(Request $request)
    {
        // Increase limits to prevent 502 errors
        set_time_limit(0); // No time limit
        ini_set('memory_limit', '1G');
        ignore_user_abort(true); // Continue processing even if user closes browser

        // Log the start of upload attempt
        Log::info('Bulk image upload started', [
            'user_id' => Auth::id(),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ]);

        try {
            $request->validate([
                'images_zip' => 'required|file|mimes:zip|max:102400', // 100MB max
            ]);

            $zipFile = $request->file('images_zip');

            if (!$zipFile || !$zipFile->isValid()) {
                throw new \Exception('Invalid ZIP file uploaded');
            }

            $fileSize = $zipFile->getSize();
            Log::info('Processing ZIP file', [
                'filename' => $zipFile->getClientOriginalName(),
                'size_mb' => round($fileSize / 1024 / 1024, 2),
            ]);

            $zipPath = $zipFile->store('temp', 'local');
            $fullZipPath = storage_path('app/' . $zipPath);

            if (!file_exists($fullZipPath)) {
                throw new \Exception('Failed to save ZIP file');
            }

            $zip = new \ZipArchive();
            $updated = 0;
            $errors = [];
            $processed = 0;

            if ($zip->open($fullZipPath) === TRUE) {
                $totalFiles = $zip->numFiles;
                Log::info('ZIP opened successfully', ['total_files' => $totalFiles]);

                // Process all files but with better error handling
                for ($i = 0; $i < $totalFiles; $i++) {
                    $processed++;

                    // Log progress every 10 files
                    if ($processed % 10 == 0) {
                        Log::info("Processing file $processed of $totalFiles");
                        // Force garbage collection every 10 files
                        gc_collect_cycles();
                    }

                    $filename = $zip->getNameIndex($i);
                    if (empty($filename) || strpos($filename, '__MACOSX') !== false || substr($filename, -1) === '/') {
                        continue; // Skip system files and directories
                    }

                    $basename = pathinfo($filename, PATHINFO_BASENAME);
                    $uniqueId = pathinfo($basename, PATHINFO_FILENAME);

                    try {
                        $imageContent = $zip->getFromIndex($i);

                        if ($imageContent === false || empty($imageContent)) {
                            $errors[] = "Could not extract: $basename";
                            continue;
                        }

                        // Check individual image size (10MB max)
                        if (strlen($imageContent) > 10 * 1024 * 1024) {
                            $errors[] = "Image too large (>10MB): $basename";
                            continue;
                        }

                        // Validate image content
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($imageContent);

                        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                            $errors[] = "Invalid image type for $uniqueId: $mimeType";
                            continue;
                        }

                        // Try to find product by unique_id - improve matching
                        $product = Product::where('seller_id', Auth::id())
                            ->where(function ($query) use ($uniqueId) {
                                $query->where('unique_id', $uniqueId)
                                    ->orWhere('unique_id', 'LIKE', "%{$uniqueId}%")
                                    ->orWhere('name', 'LIKE', "%{$uniqueId}%");
                            })
                            ->first();

                        if ($product) {
                            $extension = pathinfo($basename, PATHINFO_EXTENSION) ?: 'jpg';
                            $uniqueName = Str::random(40) . '.' . $extension;
                            $storagePath = 'products/' . $product->id . '/' . $uniqueName;

                            // Always try to store to both R2 and public
                            $savedR2 = false;
                            $savedPublic = false;
                            try {
                                $savedR2 = Storage::disk('r2')->put($storagePath, $imageContent);
                                if ($savedR2) {
                                    Log::info('Bulk image stored in AWS (r2)', [
                                        'product_id' => $product->id,
                                        'unique_id' => $uniqueId,
                                        'path' => $storagePath
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('AWS upload failed for bulk image', [
                                    'product_id' => $product->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            try {
                                $savedPublic = Storage::disk('public')->put($storagePath, $imageContent);
                                if ($savedPublic) {
                                    Log::info('Bulk image stored in public disk', [
                                        'product_id' => $product->id,
                                        'unique_id' => $uniqueId,
                                        'path' => $storagePath
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('Public disk upload failed for bulk image', [
                                    'product_id' => $product->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            if ($savedR2 || $savedPublic) {
                                // Delete old legacy image if exists
                                if ($product->image) {
                                    try {
                                        Storage::disk('r2')->delete($product->image);
                                    } catch (\Throwable $e) {
                                    }
                                    try {
                                        Storage::disk('public')->delete($product->image);
                                    } catch (\Throwable $e) {
                                    }
                                }

                                // Update legacy image field
                                $product->image = $storagePath;
                                $product->save();

                                // Also create/update ProductImage record for gallery system
                                try {
                                    \App\Models\ProductImage::updateOrCreate(
                                        [
                                            'product_id' => $product->id,
                                            'is_primary' => true
                                        ],
                                        [
                                            'image_path' => $storagePath,
                                            'original_name' => $basename,
                                            'mime_type' => $mimeType,
                                            'file_size' => strlen($imageContent),
                                            'sort_order' => 1,
                                        ]
                                    );
                                } catch (\Throwable $e) {
                                    Log::warning('Failed to create ProductImage record', [
                                        'product_id' => $product->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                $updated++;
                                if (!$savedR2) {
                                    $errors[] = "Image for product $uniqueId saved to public but failed to save to R2.";
                                }
                                if (!$savedPublic) {
                                    $errors[] = "Image for product $uniqueId saved to R2 but failed to save to public.";
                                }
                            } else {
                                $errors[] = "Failed to save image for product $uniqueId to either R2 or public.";
                            }
                        } else {
                            $errors[] = "No product found for unique_id: $uniqueId";
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error processing $basename: " . $e->getMessage();
                        Log::error("Error processing bulk image file", [
                            'filename' => $basename,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                $zip->close();
            } else {
                throw new \Exception('Could not open ZIP file. Please ensure it is a valid ZIP file.');
            }

            // Clean up temp file
            if (file_exists($fullZipPath)) {
                Storage::disk('local')->delete($zipPath);
            }

            // Log completion
            Log::info('Bulk image upload completed', [
                'updated' => $updated,
                'processed' => $processed,
                'errors' => count($errors),
                'final_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ]);

            $msg = "$updated product images updated successfully.";
            if ($errors) {
                $errorMsg = implode(' | ', array_slice($errors, 0, 5)); // Show first 5 errors
                if (count($errors) > 5) {
                    $errorMsg .= ' | And ' . (count($errors) - 5) . ' more errors...';
                }
                $msg .= ' Issues: ' . $errorMsg;
            }

            return redirect()->route('seller.dashboard')->with('bulk_upload_success', $msg);
        } catch (\Throwable $e) {
            // Clean up temp file on error
            if (isset($zipPath) && Storage::disk('local')->exists($zipPath)) {
                Storage::disk('local')->delete($zipPath);
            }

            Log::error('Bulk image upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ]);

            $errorMessage = 'Upload failed: ';
            if (strpos($e->getMessage(), 'memory') !== false) {
                $errorMessage .= 'Not enough memory. Try uploading smaller ZIP files.';
            } elseif (strpos($e->getMessage(), 'time') !== false || strpos($e->getMessage(), 'timeout') !== false) {
                $errorMessage .= 'Processing took too long. Try smaller batches.';
            } elseif (strpos($e->getMessage(), 'zip') !== false) {
                $errorMessage .= 'Invalid ZIP file. Please ensure it\'s a valid ZIP archive.';
            } else {
                $errorMessage .= $e->getMessage();
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }
    public function addMultipleSubcategories()
    {
        $categories = Category::all();
        return view('seller.add-multiple-subcategories', compact('categories'));
    }

    public function storeMultipleSubcategories(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_names' => 'required|array|min:1',
            'subcategory_names.*' => 'required|string|max:255',
        ]);
        foreach ($request->subcategory_names as $name) {
            $unique_id = Str::upper(Str::random(2)) . rand(0, 9);
            // Subcategory creation logic removed
        }
        return redirect()->route('seller.dashboard')->with('success', 'Subcategories added!');
    }
    // Merged Category & Subcategory Form
    public function createCategorySubcategory()
    {
        $categories = Category::all();
        return view('seller.create-category-subcategory', compact('categories'));
    }

    public function storeCategorySubcategory(Request $request)
    {
        // If new category is provided
        if ($request->filled('category_name') && $request->filled('category_unique_id')) {
            $request->validate([
                'category_name' => 'required|string|max:255',
                'category_unique_id' => 'required|string|max:3|unique:categories,unique_id',
                'subcategory_name' => 'required|string|max:255',
            ]);

            // Create new category
            $category = Category::create([
                'name' => strtoupper($request->category_name),
                'unique_id' => strtoupper($request->category_unique_id),
            ]);
        }
        // If existing category selected
        elseif ($request->filled('existing_category')) {
            $request->validate([
                'existing_category' => 'required|exists:categories,id',
                'subcategory_name' => 'required|string|max:255',
            ]);

            $category = Category::findOrFail($request->existing_category);
        } else {
            return back()->withErrors(['error' => 'Please select or create a category.']);
        }

        // Convert subcategory name to uppercase
        $subcategoryName = strtoupper($request->subcategory_name);

        // Check if subcategory already exists for this category
        $existingSubcategory = Subcategory::where('category_id', $category->id)
            ->where('name', $subcategoryName)
            ->first();

        if ($existingSubcategory) {
            return back()->with('error', 'This subcategory already exists for the selected category!');
        }

        // Add subcategory if not exists
        Subcategory::create([
            'name' => $subcategoryName,
            'category_id' => $category->id,
            'unique_id' => strtoupper(Str::random(3)), // Example: random 3-letter code
        ]);

        return redirect('seller/dashboard')->with('success', 'Subcategory added successfully!');
    }


    // Category Form
    public function createCategory()
    {
        return view('seller.create-category');
    }

    // Product Form
    public function createProduct()
    {
        $categories = Category::all();
        $subcategories = Subcategory::all();
        return view('seller.create-product', compact('categories', 'subcategories'));
    }

    public function storeProduct(Request $request)
    {
        Log::info('storeProduct called', [
            'has_image_file' => $request->hasFile('image'),
            'all_files' => $request->allFiles(),
            'input_keys' => array_keys($request->all())
        ]);
        // ---------------- VALIDATION ----------------
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'subcategory_id' => 'required|integer|exists:subcategories,id',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'delivery_charge' => 'nullable|numeric',
            'gift_option' => 'nullable|string|in:yes,no',
            'stock' => 'required|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'ten_min_delivery' => 'required|in:yes,no',
        ]);

        // ---------------- UNIQUE ID ----------------
        $uniqueId = Str::upper(Str::random(3));

        $seller = Auth::user();
        return $this->storeProductWithDatabaseImage($request);
    } // New method for cloud-compatible image storage
    private function storeProductWithDatabaseImage(Request $request)
    {
        try {
            $seller = Auth::user();
            if (!$seller) {
                return redirect()->back()->withInput()->with('error', 'Authentication error.');
            }

            $unique_id = Str::upper(Str::random(2)) . rand(0, 9);

            // ---------------- IMAGE UPLOAD ----------------
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $folder = 'products/seller-' . $seller->id;
                $filename = Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
                    . '.' . $image->getClientOriginalExtension();
                $disk = $this->isLaravelCloud() ? 'r2' : 'public';
                $imagePath = $image->storeAs($folder, $filename, $disk);
            }

            // ---------------- CREATE PRODUCT ----------------
            $product = Product::create([
                'name' => $request->name,
                'unique_id' => $unique_id,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'seller_id' => $seller->id,
                'description' => $request->description,
                'price' => $request->price,
                'discount' => $request->discount ?? 0,
                'delivery_charge' => $request->delivery_charge ?? 0,
                'gift_option' => $request->gift_option ?? 'no',
                'stock' => $request->stock,
                'status' => 'active',
                'is_active' => true,
                'image' => $imagePath, // ✅ Save uploaded image path
            ]);

            if ($imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'file_size' => $image->getSize(),
                    'sort_order' => 1,
                    'is_primary' => true,
                ]);
            }

            // ---------------- TEN MIN DELIVERY ----------------
            if ($request->ten_min_delivery === 'yes') {
                TenMinDeliveryProduct::create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'unique_id' => $product->unique_id,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'seller_id' => $product->seller_id,
                    'description' => $product->description,
                    'price' => $product->price,
                    'discount' => $product->discount,
                    'delivery_charge' => $product->delivery_charge,
                    'gift_option' => $product->gift_option,
                    'stock' => $product->stock,
                    'image' => $imagePath, // ✅ Use uploaded image path
                ]);
            }

            return redirect()->route('seller.dashboard')
                ->with('success', "Product '{$product->name}' added successfully!");
        } catch (\Exception $e) {
            Log::error('Product creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }


    public function editProduct(Product $product)
    {
        try {
            Log::info('editProduct called', [
                'product_id' => $product->id ?? null,
                'seller_id' => $product->seller_id ?? null,
                'auth_id' => Auth::id(),
                'product_exists' => $product ? true : false
            ]);

            // Ensure product exists and belongs to the authenticated seller
            if (!$product || !isset($product->seller_id)) {
                Log::error('editProduct: Product not found or missing seller_id', ['product_id' => $product->id ?? null]);
                return redirect()->route('seller.dashboard')->with('error', 'Product not found.');
            }

            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }

            if ((int) $product->seller_id !== (int) Auth::id()) {
                Log::warning('editProduct: Unauthorized access', [
                    'product_seller_id' => $product->seller_id,
                    'auth_id' => Auth::id()
                ]);
                return redirect()->route('seller.dashboard')->with('error', 'Unauthorized access to product.');
            }

            $categories = Category::all();
            $subcategories = Subcategory::all();

            // Check if product is in 10-min delivery
            $isTenMin = \App\Models\TenMinDeliveryProduct::where('product_id', $product->id)->exists();

            Log::info('editProduct: categories/subcategories loaded', [
                'categories_count' => $categories->count(),
                'subcategories_count' => $subcategories->count(),
                'is_ten_min' => $isTenMin
            ]);
            return view('seller.edit-product', compact('product', 'categories', 'subcategories', 'isTenMin'));
        } catch (\Throwable $e) {
            Log::error('editProduct: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('seller.dashboard')->with('error', 'An unexpected error occurred while opening the edit page.');
        }
    }

    //     public function showTenMinProducts(Request $request)
//     {
//         $selectedCategoryId = $request->query('category');

    //         // Fetch only categories that have 10-min products
//         $categories = Category::whereHas('tenMinProducts')
//             ->with(['tenMinProducts.subcategory'])
//             ->get()
//             ->map(function ($cat) {
//                 // Only subcategories that are actually used by 10-min products
//                 $subcategories = $cat->tenMinProducts
//                     ->pluck('subcategory')
//                     ->filter() // remove nulls
//                     ->unique('id')
//                     ->values();

    //                 $cat->filteredSubcategories = $subcategories;

    //                 return $cat;
//             });

    //         // Determine active category safely
//         $activeCategory = $categories->firstWhere('id', $selectedCategoryId) ?? $categories->first();

    //         // Prepare JS-ready data
//         $jsCategories = $categories->map(function ($cat) {
//             return [
//                 'id' => $cat->id,
//                 'name' => $cat->name,
//                 'icon' => $cat->icon ?? '🛒',
//                 'subcategories' => $cat->filteredSubcategories->map(fn($s) => [
//                     'id' => $s->id,
//                     'name' => $s->name,
//                 ])->toArray(),
//                 'products' => $cat->tenMinProducts->map(fn($p) => [
//                     'id' => $p->id,
//                     'name' => $p->name,
//                     'subcategory' => $p->subcategory ? $p->subcategory->name : 'Other',
//                     'img' => ($p->image && !empty($p->image)) ? url('/serve-image/public/' . $p->image) : asset('images/placeholder.png'),
//                     'price' => $p->price,
//                     'discount' => $p->discount ?? 0,
//                 ])->toArray(),
//             ];
//         })->toArray();

    //         return view('ten-min-products/index', compact('categories', 'jsCategories', 'activeCategory'));
//     }

    // // ✅ ADD THESE METHODS BELOW showTenMinProducts

    // public function tenMinCartAdd(Request $request)
// {
//     $request->validate([
//         'product_id' => 'required|exists:ten_min_products,id'
//     ]);

    //     $product = \App\Models\TenMinProduct::findOrFail($request->product_id);
//     $cart = session()->get('cart_tenmin', []);

    //     if (isset($cart[$product->id])) {
//         $cart[$product->id]['quantity']++;
//     } else {
//         $cart[$product->id] = [
//             'id' => $product->id,
//             'name' => $product->name,
//             'price' => $product->price,
//             'image' => $product->image,
//             'quantity' => 1,
//         ];
//     }

    //     session()->put('cart_tenmin', $cart);

    //     return redirect()->route('tenmin.cart')->with('success', 'Added to quick basket!');
// }

    // public function tenMinCartIndex()
// {
//     $cart = session()->get('cart_tenmin', []);
//     $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
//     return view('ten_min_cart', compact('cart', 'total'));
// }

    // public function tenMinCartUpdate(Request $request, $productId)
// {
//     $request->validate(['quantity' => 'required|integer|min:1']);
//     $cart = session()->get('cart_tenmin', []);

    //     if (isset($cart[$productId])) {
//         if ($request->quantity <= 0) {
//             unset($cart[$productId]);
//         } else {
//             $cart[$productId]['quantity'] = $request->quantity;
//         }
//         session()->put('cart_tenmin', $cart);
//     }

    //     return redirect()->route('tenmin.cart');
// }

    // public function tenMinCartRemove($productId)
// {
//     $cart = session()->get('cart_tenmin', []);
//     unset($cart[$productId]);
//     session()->put('cart_tenmin', $cart);
//     return redirect()->route('tenmin.cart')->with('success', 'Removed from basket.');
// }

    // public function tenMinCheckout()
// {
//     $cart = session()->get('cart_tenmin', []);
//     if (empty($cart)) {
//         return redirect()->route('tenmin.cart')->with('error', 'Your quick basket is empty.');
//     }

    //     $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

    //     // Example: ₹100 minimum for 10-min basket (adjust as needed)
//     if ($total < 100) {
//         return back()->with('error', 'Minimum order ₹100 for 10-minute delivery.');
//     }

    //     // 🚨 IMPORTANT: You need to decide how to store this order.
//     // Since you're reusing the food system, we'll create a special "grab basket" vendor
//     // or use a placeholder hotel_owner_id (e.g., ID 1 as admin/store)

    //     $grabBasketVendorId = 1; // ← Set this to a valid hotel_owner_id that handles 10-min orders

    //     $deliveryFee = 30; // or 0, or 50 — your choice
//     $totalAmount = $total + $deliveryFee;

    //     $order = \App\Models\FoodOrder::create([
//         'hotel_owner_id' => $grabBasketVendorId,
//         'customer_name' => 'Quick Customer',
//         'customer_phone' => '0123456789', // replace with real data if logged in
//         'delivery_address' => '123 Test Street', // replace with real address
//         'food_total' => $total,
//         'delivery_fee' => $deliveryFee,
//         'total_amount' => $totalAmount,
//         'status' => 'pending',
//         'estimated_delivery_time' => now()->addMinutes(10),
//         // Optional: add a note or flag if you extend the table later
//     ]);

    //     foreach ($cart as $item) {
//         \App\Models\FoodOrderItem::create([
//             'food_order_id' => $order->id,
//             'food_item_id' => null, // not a food item
//             'food_name' => $item['name'],
//             'price' => $item['price'],
//             'quantity' => $item['quantity'],
//             'food_type' => 'fast', // or leave null
//         ]);
//     }

    //     session()->forget('cart_tenmin');
//     return redirect()->route('tenmin.order.success', $order->id);
// }

    // public function tenMinOrderSuccess($orderId)
// {
//     $order = \App\Models\FoodOrder::with('items')->findOrFail($orderId);
//     return view('ten_min_order_success', compact('order'));
// }

    public function showTenMinProducts(Request $request)
    {
        // Fetch categories that have at least one in-stock 10-minute delivery product
        $categories = Category::whereHas('tenMinProducts', function ($query) {
            $query->where('stock', '>', 0);
        })
            ->with([
                'tenMinProducts' => function ($query) {
                    $query->where('stock', '>', 0)
                        ->with('subcategory'); // Ensure subcategory relation is loaded
                }
            ])
            ->get()
            ->filter(fn($cat) => $cat->tenMinProducts->isNotEmpty())
            ->values();

        // Determine active category: from query param
        $selectedCategoryId = $request->query('category');
        $activeCategory = null;
        $commonProducts = collect();

        if ($selectedCategoryId) {
            $activeCategory = $categories->firstWhere('id', $selectedCategoryId);
        }

        // If no categories or no selected category, prepare "Top Picks" (2 from each category)
        if (!$activeCategory && $categories->isNotEmpty()) {
            foreach ($categories as $cat) {
                $commonProducts = $commonProducts->merge($cat->tenMinProducts->take(2));
            }
        }

        // Safely attach filteredSubcategories to every category (including active one)
        $categories = $categories->map(function ($cat) {
            $cat->filteredSubcategories = $cat->tenMinProducts
                ->pluck('subcategory')     // Extract subcategory relation
                ->filter()                 // Remove nulls (if subcategory_id was null)
                ->unique('id')             // Avoid duplicates
                ->values();                // Re-index as [0, 1, 2...]
            return $cat;
        });

        // Reassign activeCategory from updated $categories to ensure it has filteredSubcategories
        if ($activeCategory) {
            $activeCategory = $categories->firstWhere('id', $activeCategory->id);
        }

        // Prepare JS-friendly data structure
        $jsCategories = $categories->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'icon' => $cat->emoji ?? '🛒',
                'subcategories' => $cat->filteredSubcategories->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->toArray(),
                'products' => $cat->tenMinProducts->map(function ($p) use ($cat) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'subcategory' => $p->subcategory?->name ?? 'Other',
                        'categoryName' => $cat->name,
                        'img' => $p->image_url,
                        'price' => $p->price,
                        'discount' => $p->discount ?? 0,
                    ];
                })->toArray(),
            ];
        })->toArray();

        return view('ten-min-products.index', compact('categories', 'jsCategories', 'activeCategory', 'commonProducts'));
    }

    public function show($id)
    {
        $product = TenMinDeliveryProduct::with(['category', 'subcategory', 'seller'])
            ->where('stock', '>', 0)
            ->findOrFail($id);

        return view('ten-min-products.show', compact('product'));
    }

    // Real-time API
    public function getProductDetails($id)
    {
        $product = TenMinDeliveryProduct::where('id', $id)
            ->where('stock', '>', 0)
            ->select('id', 'price', 'stock')
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Unavailable'], 404);
        }

        return response()->json($product);
    }

    public function tenMinCartAdd(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Please login to add items to cart'], 401);
        }

        $request->validate([
            'product_id' => 'required|exists:ten_min_delivery_products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $userId = auth()->id();
        $product = TenMinDeliveryProduct::findOrFail($request->product_id);

        // ✅ ALLOW MULTIPLE SELLERS — NO SELLER CHECK HERE

        // Check stock
        if ($product->stock < $request->quantity) {
            return response()->json(['error' => 'Not enough stock'], 422);
        }

        $cartItem = TenMinGroceryCartItem::firstOrNew([
            'user_id' => $userId,
            'product_id' => $product->id,
        ]);

        if ($cartItem->exists) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            if ($newQuantity > $product->stock) {
                return response()->json(['error' => 'Not enough stock'], 422);
            }
            $cartItem->quantity = $newQuantity;
        } else {
            $cartItem->fill([
                'seller_id' => $product->seller_id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => $request->quantity,
            ]);
        }

        $cartItem->save();

        $cartCount = TenMinGroceryCartItem::where('user_id', $userId)->sum('quantity');

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'seller_id' => $product->seller_id,
            'message' => 'Item added successfully',
        ]);
    }
    // View cart
// SellerController.php
    public function tenMinCartView()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $cartItems = TenMinGroceryCartItem::where('user_id', $user->id)->get();
        $cartCount = $cartItems->sum('quantity');
        $walletPoint = $user->wallet_point ?? 0;
        return view('ten-min-products.cart', compact('cartItems', 'cartCount', 'walletPoint'));
    }
    // Update item quantity
    public function tenMinCartUpdate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:ten_min_delivery_products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = TenMinGroceryCartItem::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if (!$cartItem) {
            return response()->json(['error' => 'Item not in cart'], 404);
        }

        $product = TenMinDeliveryProduct::find($cartItem->product_id);
        if (!$product || $product->stock < $request->quantity) {
            return response()->json(['error' => 'Not enough stock'], 422);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json(['success' => true]);
    }

    // Remove item
    public function tenMinCartRemove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:ten_min_delivery_products,id'
        ]);

        TenMinGroceryCartItem::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->delete();

        return response()->json(['success' => true]);
    }
    // In your controller


    public function tenMinCheckout()
    {
        $user = auth()->user();
        $cartItems = TenMinGroceryCartItem::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('tenmin.cart.view')
                ->with('error', 'Your quick basket is empty.');
        }

        // Group cart items by seller
        $grouped = $cartItems->groupBy('seller_id');
        $orders = [];

        foreach ($grouped as $sellerId => $items) {
            $subtotal = $items->sum(fn($item) => $item->price * $item->quantity);

            // Enforce ₹200 minimum per seller
            if ($subtotal < 200) {
                $storeName = $items->first()->seller?->name ?? 'this store';
                return back()->with('error', "Minimum ₹200 required for {$storeName} (currently ₹" . number_format($subtotal, 2) . ").");
            }

            $deliveryFee = 50;
            $tax = round($subtotal * 0.05); // 5% Tax rounded to integer
            $total = $subtotal + $deliveryFee + $tax;

            $orders[] = [
                'seller_id' => $sellerId,
                'store_name' => $items->first()->seller?->name ?? 'Store',
                'items' => $items,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total' => $total,
            ];
        }

        $walletPoint = $user->wallet_point ?? 0;

        // Pass shared customer info + grouped orders + walletPoint
        return view('ten-min-products.checkout', [
            'orders' => $orders,
            'customerName' => $user->name,
            'deliveryAddress' => $user->address ?? '123 Test Street',
            'customerEmail' => $user->email ?? '',
            'customerPhone' => $user->phone ?? '0123456789',
            'walletPoint' => $walletPoint,
        ]);
    }
    public function tenMinOrderSuccess($orderId)
    {
        $order = \App\Models\TenMinOrder::with('items')->findOrFail($orderId);
        return view('ten-min-products.order-success', compact('order'));
    }
    public function placeTenMinGroceryOrder(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'delivery_address' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email',
            'payment_method' => 'required|in:cod,upi,card',
        ]);

        $cartItems = TenMinGroceryCartItem::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 400);
        }

        // Group by seller for calculation
        $groupedItems = $cartItems->groupBy('seller_id');
        $grandTotal = 0;

        foreach ($groupedItems as $sellerId => $items) {
            $subtotal = $items->sum(fn($item) => $item->price * $item->quantity);
            $deliveryFee = 50.00;
            $tax = round($subtotal * 0.05);
            $grandTotal += ($subtotal + $deliveryFee + $tax);
        }

        $useWallet = $request->boolean('use_wallet');
        $totalWalletDiscount = 0;
        if ($useWallet && $user->wallet_point > 0) {
            $totalWalletDiscount = round(min(0.15 * $grandTotal, $user->wallet_point));
        }
        $finalGrandTotal = $grandTotal - $totalWalletDiscount;

        if ($request->payment_method !== 'cod') {
            // Razorpay Order Creation
            $api = new Api($this->razorpayKeyId, $this->razorpayKeySecret);
            $razorpayOrder = $api->order->create([
                'receipt' => 'tenmin_' . time() . '_' . $user->id,
                'amount' => (int) ($finalGrandTotal * 100),
                'currency' => 'INR',
            ]);

            session([
                'tenmin_checkout_data' => [
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'delivery_address' => $request->delivery_address,
                    'payment_method' => $request->payment_method,
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'use_wallet' => $useWallet,
                    'wallet_discount' => $totalWalletDiscount,
                ]
            ]);

            return response()->json([
                'success' => true,
                'payment_required' => true,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => (int) ($finalGrandTotal * 100),
                'key' => $this->razorpayKeyId,
                'customer' => [
                    'name' => $user->name,
                    'email' => $request->email,
                    'contact' => $request->phone,
                ]
            ]);
        }

        // COD Flow
        $orderIds = [];
        foreach ($groupedItems as $sellerId => $items) {
            $subtotal = $items->sum(fn($item) => $item->price * $item->quantity);
            $deliveryFee = 50.00;
            $tax = round($subtotal * 0.05);
            $sellerSubtotal = $subtotal + $deliveryFee + $tax;

            $sellerDiscount = ($grandTotal > 0) ? ($sellerSubtotal / $grandTotal) * $totalWalletDiscount : 0;
            $sellerFinalTotal = $sellerSubtotal - $sellerDiscount;

            $order = \App\Models\TenMinOrder::create([
                'user_id' => $user->id,
                'seller_id' => $sellerId,
                'customer_name' => $user->name,
                'customer_phone' => $request->phone,
                'customer_email' => $request->email,
                'delivery_address' => $request->delivery_address,
                'order_total' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total_amount' => $sellerFinalTotal,
                'wallet_discount' => $sellerDiscount,
                'payment_method' => 'cod',
                'status' => 'pending',
                'estimated_delivery_time' => now()->addMinutes(10),
            ]);

            foreach ($items as $item) {
                \App\Models\TenMinOrderItem::create([
                    'ten_min_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'seller_id' => $item->seller_id,
                ]);
            }
            $orderIds[] = $order->id;
        }

        // Handle Wallet Deduction for COD
        if ($totalWalletDiscount > 0) {
            UserWalletTransaction::create([
                'user_id' => $user->id,
                'amount' => -$totalWalletDiscount,
                'description' => 'Wallet points used for 10-Min Order',
                'transaction_type' => 'debit',
                'status' => 'completed',
            ]);
        }

        TenMinGroceryCartItem::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'order_id' => $orderIds[0], // Redirect to first order or success page
            'redirect_url' => route('tenmin.order.success', $orderIds[0])
        ]);
    }

    public function verifyTenMinPayment(Request $request)
    {
        $user = auth()->user();
        $checkoutData = session('tenmin_checkout_data');

        if (!$checkoutData || $checkoutData['razorpay_order_id'] !== $request->razorpay_order_id) {
            return response()->json(['success' => false, 'message' => 'Invalid session or order ID.'], 400);
        }

        try {
            $api = new Api($this->razorpayKeyId, $this->razorpayKeySecret);
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $cartItems = TenMinGroceryCartItem::where('user_id', $user->id)->get();
            $groupedItems = $cartItems->groupBy('seller_id');
            $originalGrandTotal = 0;

            foreach ($groupedItems as $sellerId => $items) {
                $subtotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $deliveryFee = 50.00;
                $tax = round($subtotal * 0.05);
                $originalGrandTotal += ($subtotal + $deliveryFee + $tax);
            }

            $totalWalletDiscount = $checkoutData['wallet_discount'] ?? 0;
            $orderIds = [];

            foreach ($groupedItems as $sellerId => $items) {
                $subtotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $deliveryFee = 50.00;
                $tax = round($subtotal * 0.05);
                $sellerSubtotal = $subtotal + $deliveryFee + $tax;

                $sellerDiscount = ($originalGrandTotal > 0) ? ($sellerSubtotal / $originalGrandTotal) * $totalWalletDiscount : 0;
                $sellerFinalTotal = $sellerSubtotal - $sellerDiscount;

                $order = \App\Models\TenMinOrder::create([
                    'user_id' => $user->id,
                    'seller_id' => $sellerId,
                    'customer_name' => $user->name,
                    'customer_phone' => $checkoutData['phone'],
                    'customer_email' => $checkoutData['email'],
                    'delivery_address' => $checkoutData['delivery_address'],
                    'order_total' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'tax' => $tax,
                    'total_amount' => $sellerFinalTotal,
                    'wallet_discount' => $sellerDiscount,
                    'payment_method' => $checkoutData['payment_method'],
                    'payment_reference' => $request->razorpay_payment_id,
                    'status' => 'paid',
                    'estimated_delivery_time' => now()->addMinutes(10),
                ]);

                foreach ($items as $item) {
                    \App\Models\TenMinOrderItem::create([
                        'ten_min_order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'seller_id' => $item->seller_id,
                    ]);
                }
                $orderIds[] = $order->id;
            }

            if ($totalWalletDiscount > 0) {
                UserWalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => -$totalWalletDiscount,
                    'description' => 'Wallet points used for 10-Min Order',
                    'transaction_type' => 'debit',
                    'status' => 'completed',
                ]);
            }

            TenMinGroceryCartItem::where('user_id', $user->id)->delete();
            session()->forget('tenmin_checkout_data');

            return response()->json([
                'success' => true,
                'redirect_url' => route('tenmin.order.success', $orderIds[0])
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay Verification Error (TenMin): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 400);
        }
    }

    public function updateProduct(Request $request, Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            return redirect()->route('seller.dashboard')->with('error', 'Unauthorized access to product.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',

            // ADD THIS FOR 10-MIN FIELD
            'ten_min_delivery' => 'required|in:yes,no',
        ]);

        $data = $request->only([
            'name',
            'category_id',
            'subcategory_id',
            'description',
            'price',
            'discount',
            'delivery_charge'
        ]);

        // Handle Image Upload
        if ($request->hasFile('image')) {
            try {
                $image = $request->file('image');
                $sellerId = Auth::id();
                $folder = "products/seller-{$sellerId}";
                $originalName = $image->getClientOriginalName();
                $originalNameSlug = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
                $ext = $image->getClientOriginalExtension();
                $filename = $originalNameSlug . '-' . time() . '-' . Str::random(4) . '.' . $ext;

                // DUAL STORAGE: Save to both AWS R2 and Git storage for redundancy
                $r2Path = null;
                $publicPath = null;
                $r2Success = false;
                $publicSuccess = false;
                $finalPath = null;

                // Try AWS R2 first
                try {
                    $r2Path = $image->storeAs($folder, $filename, 'r2');
                    $r2Success = !empty($r2Path);
                } catch (\Throwable $r2Ex) {
                    Log::warning('AWS R2 upload failed during product update', [
                        'error' => $r2Ex->getMessage(),
                        'product_id' => $product->id
                    ]);
                }

                // Then save to Git storage (public disk)
                try {
                    $publicPath = $image->storeAs($folder, $filename, 'public');
                    $publicSuccess = !empty($publicPath);
                } catch (\Throwable $publicEx) {
                    Log::warning('Git storage upload failed during product update', [
                        'error' => $publicEx->getMessage(),
                        'product_id' => $product->id
                    ]);
                }

                // Use whichever path was successful (prefer R2)
                $finalPath = $r2Success ? $r2Path : $publicPath;

                if ($finalPath) {
                    // Delete old image if exists
                    if ($product->image) {
                        try {
                            Storage::disk('r2')->delete($product->image);
                        } catch (\Throwable $e) {
                        }
                        try {
                            Storage::disk('public')->delete($product->image);
                        } catch (\Throwable $e) {
                        }
                    }

                    $data['image'] = $finalPath;

                    Log::info('Product image updated with dual storage', [
                        'product_id' => $product->id,
                        'path' => $finalPath,
                        'r2_success' => $r2Success,
                        'public_success' => $publicSuccess
                    ]);

                    // ✅ SYNC WITH PRODUCT GALLERY (ProductImage)
                    // Find the primary or first image to update
                    $galleryImage = \App\Models\ProductImage::where('product_id', $product->id)
                        ->where('is_primary', true)
                        ->first();

                    if (!$galleryImage) {
                        $galleryImage = \App\Models\ProductImage::where('product_id', $product->id)->first();
                    }

                    if ($galleryImage) {
                        // Update existing gallery record
                        $galleryImage->update([
                            'image_path' => $finalPath,
                            'original_name' => $originalName,
                            'mime_type' => $image->getMimeType(),
                            'file_size' => $image->getSize(),
                        ]);
                    } else {
                        // Create new gallery record if none existed
                        \App\Models\ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $finalPath,
                            'original_name' => $originalName,
                            'mime_type' => $image->getMimeType(),
                            'file_size' => $image->getSize(),
                            'sort_order' => 1,
                            'is_primary' => true,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Product image update failed', ['error' => $e->getMessage()]);
                // Continue with update even if image fails, or flash error? 
                // Better to just log for now to avoid breaking the text update.
            }
        } elseif ($request->filled('library_image_url')) {
            // Handle Library Image Selection
            $url = $request->input('library_image_url');
            // Try to extract relative path if it matches our storage domain
            // This is a basic implementation - assuming the URL structure matches our storage
            $r2Domain = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
            if (str_starts_with($url, $r2Domain)) {
                $data['image'] = str_replace($r2Domain . '/', '', $url);
            } elseif (str_contains($url, '/storage/')) {
                // If it's a local public URL
                $parts = explode('/storage/', $url);
                if (count($parts) > 1) {
                    $data['image'] = $parts[1];
                }
            } else {
                // Determine if we should store the full URL or handle it differently
                // For now, if it's external or unknown, we might not update it to avoid breaking 'image' path expectation
                // or just store it if the model supports full URLs.
            }
        }

        // After image logic ends → IMPORTANT
        $product->update($data);

        /*
    ==========================================================
    🔥 ADD THIS SECTION: HANDLE 10-MIN DELIVERY TABLE UPDATE
    ==========================================================
    */

        if ($request->ten_min_delivery === 'yes') {

            // Check if already exists
            $exists = TenMinDeliveryProduct::where('product_id', $product->id)->first();

            if ($exists) {
                // UPDATE existing record
                $exists->update([
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'description' => $product->description,
                    'price' => $product->price,
                    'discount' => $product->discount,
                    'delivery_charge' => $product->delivery_charge,
                    'image' => $product->image,
                    'gift_option' => $product->gift_option,
                    'stock' => $product->stock,
                ]);
            } else {
                // INSERT new record
                TenMinDeliveryProduct::create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'unique_id' => $product->unique_id,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'seller_id' => $product->seller_id,
                    'description' => $product->description,
                    'price' => $product->price,
                    'discount' => $product->discount,
                    'delivery_charge' => $product->delivery_charge,
                    'gift_option' => $product->gift_option,
                    'stock' => $product->stock,
                    'image' => $product->image, // ✅ Use uploaded image path
                ]);
            }
        } else {
            // If changed to NO → remove from 10-min delivery
            TenMinDeliveryProduct::where('product_id', $product->id)->delete();
        }

        /*
    ==========================================================
    END 10-MIN DELIVERY SECTION
    ==========================================================
    */

        return redirect()
            ->route('seller.editProduct', $product)
            ->with('success', 'Product updated successfully!');
    }


    // Seller profile pages
    public function myProfile()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::error('myProfile: User not authenticated');
                return redirect()->route('login')->with('error', 'Please log in to view your profile.');
            }

            // Resolve Seller model by email or create a bridge if needed
            $seller = \App\Models\Seller::where('email', $user->email)->first();

            if (!$seller) {
                Log::error('myProfile: Seller not found', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                abort(404, 'Seller profile not found');
            }

            $products = Product::with(['category', 'subcategory'])
                ->where('seller_id', $user->id)
                ->latest()->get();

            return view('seller.profile', compact('seller', 'products'));
        } catch (\Exception $e) {
            Log::error('myProfile error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('seller.dashboard')
                ->with('error', 'Unable to load profile page. Please try again.');
        }
    }

    public function publicProfileBySeller(\App\Models\Seller $seller)
    {
        // We assume products.seller_id references users.id and that the seller's email ties to user.
        $user = \App\Models\User::where('email', $seller->email)->first();
        $products = $user
            ? Product::with(['category', 'subcategory'])->where('seller_id', $user->id)->latest()->get()
            : collect();
        return view('seller.profile', compact('seller', 'products'));
    }

    // Transactions page for seller
    public function transactions()
    {
        $sellerId = Auth::id();
        $orders = Order::with(['product'])
            ->where('seller_id', $sellerId)
            ->latest()
            ->paginate(15);
        return view('seller.transactions', compact('orders'));
    }

    /**
     * Show the bulk upload Excel form
     */
    public function showBulkUploadForm()
    {
        $categories = Category::all();
        $subcategories = Subcategory::with('category')->get();
        return view('seller.bulk-upload-excel', compact('categories', 'subcategories'));
    }

    /**
     * Process bulk upload from Excel with images
     */
    public function processBulkUpload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|array',
            'excel_file.*' => 'mimes:xlsx,xls,csv|max:10240', // 10MB per file
            'images_zip' => 'nullable|mimes:zip|max:51200', // 50MB max for zip
        ]);

        try {
            $zipPath = null;
            // Handle images zip file
            if ($request->hasFile('images_zip')) {
                $zipFile = $request->file('images_zip');
                $zipPath = $zipFile->store('temp/bulk-uploads', 'local');
            }

            $totalSuccess = 0; // optional counter; may be unknown if importer doesn't expose
            $allErrors = [];
            $files = $request->file('excel_file');
            foreach ($files as $excelFile) {
                // Pass zip path and current seller id to ensure updates are scoped to the seller
                $import = new \App\Imports\ProductsImport($zipPath, Auth::id());
                \Maatwebsite\Excel\Facades\Excel::import($import, $excelFile);
                // Best-effort accumulate; suppress if methods unavailable
                try {
                    $totalSuccess += (int) $import->getSuccessCount();
                } catch (\Throwable $e) {
                }
                try {
                    $allErrors = array_merge($allErrors, (array) $import->getErrors());
                } catch (\Throwable $e) {
                }
            }

            // Clean up temporary zip file
            if ($zipPath && Storage::disk('local')->exists($zipPath)) {
                Storage::disk('local')->delete($zipPath);
            }

            $message = $totalSuccess > 0
                ? "Successfully imported {$totalSuccess} products from all Excel files."
                : "Bulk import completed.";
            // Suppress all errors and always show success
            return redirect()->route('seller.dashboard')->with('success', $message);
        } catch (\Exception $e) {
            if (isset($zipPath) && $zipPath && Storage::disk('local')->exists($zipPath)) {
                Storage::disk('local')->delete($zipPath);
            }
            return redirect()->route('seller.bulkUploadForm')
                ->with('error', 'Error processing upload: ' . $e->getMessage());
        }
    }

    /**
     * Download sample Excel template
     */
    public function downloadSampleExcel()
    {
        // Create sample data with proper column headers
        $sampleData = [
            [
                'name' => 'Sample Product 1',
                'unique_id' => 'PROD-001',
                'category_id' => 1,
                'category_name' => 'Electronics',
                'subcategory_id' => 1,
                'subcategory_name' => 'Mobile Phones',
                'image' => 'sample-product-1.jpg',
                'description' => 'This is a sample product description. Describe your product features here.',
                'price' => 999.99,
                'discount' => 10,
                'delivery_charge' => 50,
                'gift_option' => true,
                'stock' => 100
            ],
            [
                'name' => 'Sample Product 2',
                'unique_id' => 'PROD-002',
                'category_id' => 2,
                'category_name' => 'Fashion',
                'subcategory_id' => 5,
                'subcategory_name' => 'Men Clothing',
                'image' => 'sample-product-2.jpg',
                'description' => 'Another sample product with different category.',
                'price' => 499.99,
                'discount' => 15,
                'delivery_charge' => 0,
                'gift_option' => false,
                'stock' => 50
            ]
        ];

        // Create the export class
        $export = new class ($sampleData) implements FromArray, WithHeadings {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'NAME',
                    'UNIQUE-ID',
                    'CATEGORY ID',
                    'CATEGORY NAME',
                    'SUBCATEGORY ID',
                    'SUBCATEGORY-NAME',
                    'IMAGE',
                    'DESCRIPTION',
                    'PRICE',
                    'DISCOUNT',
                    'DELIVERY-CHARGE',
                    'GIFT-OPTION',
                    'STOCK'
                ];
            }
        };

        return Excel::download($export, 'bulk-products-sample.xlsx');
    }

    // Bulk Image Re-upload Methods
    public function showBulkImageReupload()
    {
        try {
            $categories = Category::all();

            // Simplified query to avoid potential issues
            $productsNeedingImages = Product::where('seller_id', Auth::id())
                ->where(function ($query) {
                    $query->whereNull('image')
                        ->orWhere('image', '');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return view('seller.bulk-image-reupload-simple', compact('categories', 'productsNeedingImages'));
        } catch (\Exception $e) {
            Log::error('Bulk image reupload page error: ' . $e->getMessage());
            return redirect()->route('seller.dashboard')->with('error', 'Unable to load bulk upload page: ' . $e->getMessage());
        }
    }

    public function processBulkImageReupload(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:102400', // 100MB
            'category_id' => 'nullable|exists:categories,id',
            'matching_method' => 'required|in:name,unique_id,both'
        ]);

        try {
            $zipFile = $request->file('zip_file');
            $matchingMethod = $request->matching_method;
            $categoryId = $request->category_id;

            // Create temporary directory for extraction
            $tempDir = storage_path('app/temp/bulk_images_' . time());
            mkdir($tempDir, 0755, true);

            // Extract zip file
            $zip = new \ZipArchive;
            if ($zip->open($zipFile->getPathname()) !== TRUE) {
                throw new \Exception('Unable to open zip file');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // Get seller's products that need images
            $query = Product::where('seller_id', Auth::id())
                ->where(function ($q) {
                    $q->whereNull('image')
                        ->orWhere('image', '')
                        ->orWhere('description', 'LIKE', '%⚠️ Image needs to be re-uploaded%');
                })
                ->whereNull('image_data');

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            $productsNeedingImages = $query->get();

            // Find image files in extracted directory
            $imageFiles = $this->findImageFiles($tempDir);

            // Match images to products
            $matches = $this->matchImagesToProducts($imageFiles, $productsNeedingImages, $matchingMethod);

            // Process matches and upload to cloud storage
            $uploadedCount = 0;
            $errors = [];

            foreach ($matches['matched'] as $productId => $imagePath) {
                try {
                    $product = Product::find($productId);
                    if ($product && $product->seller_id === Auth::id()) {

                        // Generate unique filename for cloud storage
                        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
                        $cloudFileName = $product->unique_id . '_' . time() . '.' . $extension;
                        $cloudPath = 'products/' . $cloudFileName;

                        // Upload to cloud storage
                        $imageContent = file_get_contents($imagePath);
                        // Try cloud first, fallback to local/public
                        $uploaded = false;
                        try {
                            $uploaded = Storage::disk('r2')->put($cloudPath, $imageContent);
                        } catch (\Throwable $e) {
                            $uploaded = false;
                        }
                        if (!$uploaded) {
                            $uploaded = Storage::disk('public')->put($cloudPath, $imageContent);
                        }

                        if ($uploaded) {
                            // Update product
                            $product->update([
                                'image' => $cloudPath,
                                'description' => str_replace("\n\n⚠️ Image needs to be re-uploaded by seller.", '', $product->description)
                            ]);
                            $uploadedCount++;

                            Log::info('Bulk image uploaded', [
                                'product_id' => $product->id,
                                'cloud_path' => $cloudPath,
                                'original_file' => basename($imagePath)
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to upload image for product {$productId}: " . $e->getMessage();
                }
            }

            // Clean up temporary directory
            $this->deleteDirectory($tempDir);

            // Prepare response message
            $message = "Successfully uploaded {$uploadedCount} images.";

            if (count($matches['unmatched']) > 0) {
                $message .= " " . count($matches['unmatched']) . " images could not be matched to products.";
            }

            if (!empty($errors)) {
                $message .= " " . count($errors) . " errors occurred during upload.";
                Log::warning('Bulk image upload errors', $errors);
            }

            return redirect()->route('seller.bulkImageReupload')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Bulk image upload failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    private function findImageFiles($directory)
    {
        $imageFiles = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (in_array($extension, $allowedExtensions)) {
                    $imageFiles[] = $file->getPathname();
                }
            }
        }

        return $imageFiles;
    }

    private function matchImagesToProducts($imageFiles, $products, $matchingMethod)
    {
        $matched = [];
        $unmatched = [];

        foreach ($imageFiles as $imagePath) {
            $fileName = pathinfo($imagePath, PATHINFO_FILENAME);
            $bestMatch = null;
            $bestScore = 0;

            foreach ($products as $product) {
                $score = 0;

                if ($matchingMethod === 'name' || $matchingMethod === 'both') {
                    // Match by product name
                    $nameScore = $this->calculateSimilarity($fileName, $product->name);
                    $score = max($score, $nameScore);
                }

                if ($matchingMethod === 'unique_id' || $matchingMethod === 'both') {
                    // Match by unique ID
                    if (stripos($fileName, $product->unique_id) !== false) {
                        $score = max($score, 0.9); // High score for ID match
                    }
                }

                if ($score > $bestScore && $score > 0.6) { // Minimum 60% similarity
                    $bestScore = $score;
                    $bestMatch = $product;
                }
            }

            if ($bestMatch) {
                $matched[$bestMatch->id] = $imagePath;
            } else {
                $unmatched[] = [
                    'filename' => basename($imagePath),
                    'path' => $imagePath
                ];
            }
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched
        ];
    }

    private function calculateSimilarity($str1, $str2)
    {
        // Normalize strings
        $str1 = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $str1));
        $str2 = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $str2));

        // Calculate similarity
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir))
            return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    // ====== IMAGE LIBRARY MANAGEMENT ======

    /**
     * Show seller's image library
     */
    public function imageLibrary()
    {
        $sellerId = Auth::id();
        $libraryFolder = 'library/seller-' . $sellerId;
        $images = [];

        try {
            // Get images from R2
            $files = Storage::disk('r2')->files($libraryFolder);

            foreach ($files as $filePath) {
                $filename = basename($filePath);

                // Skip non-image files
                if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
                    continue;
                }

                $images[] = [
                    'name' => $filename,
                    'path' => $filePath,
                    'url' => $this->getImageUrl($filePath),
                    'size' => $this->formatFileSize(Storage::disk('r2')->size($filePath))
                ];
            }

            // Sort by name
            usort($images, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to load image library', [
                'error' => $e->getMessage(),
                'seller_id' => $sellerId
            ]);
        }

        return view('seller.image-library', compact('images'));
    }

    /**
     * Upload images to seller's library
     */
    public function uploadToLibrary(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $sellerId = Auth::id();
        $libraryFolder = 'library/seller-' . $sellerId;
        $uploadedCount = 0;
        $errors = [];

        foreach ($request->file('images') as $image) {
            try {
                $ext = $image->getClientOriginalExtension();
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $filename = Str::slug($originalName) . '-' . uniqid() . '.' . $ext;

                // Upload to R2
                $path = $image->storeAs($libraryFolder, $filename, 'r2');

                if ($path) {
                    $uploadedCount++;
                    Log::info('Image uploaded to library', [
                        'seller_id' => $sellerId,
                        'filename' => $filename
                    ]);
                }
            } catch (\Throwable $e) {
                $errors[] = $image->getClientOriginalName();
                Log::error('Failed to upload image to library', [
                    'error' => $e->getMessage(),
                    'filename' => $image->getClientOriginalName()
                ]);
            }
        }

        if ($uploadedCount > 0) {
            $message = "Successfully uploaded {$uploadedCount} image(s)";
            if (count($errors) > 0) {
                $message .= " (" . count($errors) . " failed)";
            }
            return redirect()->route('seller.imageLibrary')->with('success', $message);
        }

        return redirect()->route('seller.imageLibrary')->with('error', 'Failed to upload images');
    }

    /**
     * Get list of library images (for AJAX)
     */
    public function getLibraryImages()
    {
        $sellerId = Auth::id();
        $libraryFolder = 'library/seller-' . $sellerId;
        $images = [];

        try {
            $files = Storage::disk('r2')->files($libraryFolder);

            foreach ($files as $filePath) {
                $filename = basename($filePath);

                if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
                    continue;
                }

                $images[] = [
                    'name' => $filename,
                    'path' => $filePath,
                    'url' => $this->getImageUrl($filePath),
                    'size' => Storage::disk('r2')->size($filePath)
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Failed to get library images', ['error' => $e->getMessage()]);
        }

        return response()->json(['images' => $images]);
    }

    /**
     * Delete image from library
     */
    public function deleteLibraryImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $sellerId = Auth::id();
        $path = $request->path;

        // Verify the path belongs to this seller
        if (!str_starts_with($path, 'library/seller-' . $sellerId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            Storage::disk('r2')->delete($path);

            Log::info('Image deleted from library', [
                'seller_id' => $sellerId,
                'path' => $path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete library image', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    /**
     * Helper: Get image URL (R2 public URL or serve-image route)
     */
    private function getImageUrl($path)
    {
        if (app()->environment('production')) {
            $r2BaseUrl = config('filesystems.disks.r2.url');
            if (!empty($r2BaseUrl)) {
                return rtrim($r2BaseUrl, '/') . '/' . ltrim($path, '/');
            }
        }

        return url('serve-image/' . str_replace('library/', 'library/', $path));
    }

    /**
     * Helper: Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Helper: Detect if running on Laravel Cloud
     * Uses multiple signals to avoid false positives when testing locally with APP_ENV=production
     */
    private function isLaravelCloud()
    {
        // Priority 1: Explicit Laravel Cloud deployment flag
        if (env('LARAVEL_CLOUD_DEPLOYMENT') === true) {
            return true;
        }

        // Priority 2: Check if actually running on Laravel Cloud infrastructure
        // (not just having APP_URL set to laravel.cloud)
        if (
            app()->environment('production') &&
            isset($_SERVER['SERVER_NAME']) &&
            str_contains($_SERVER['SERVER_NAME'], '.laravel.cloud')
        ) {
            return true;
        }

        // Priority 3: Vapor environment (Laravel Cloud uses Vapor)
        if (env('VAPOR_ENVIRONMENT') !== null) {
            return true;
        }

        return false;
    }

    public function tenmins()
    {
        return view('tenmins');
    }

    public function joinus()
    {
        return view('joinus');
    }
}
