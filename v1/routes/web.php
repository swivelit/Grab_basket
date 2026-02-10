<?php
// Debug logging for edit product route


use App\Http\Controllers\BuyerController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CourierTrackingController;
use App\Http\Controllers\HomeController;
use \App\Models\DeliveryPartner;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
#use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InternshipController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TenMinsOrderController;
use App\Http\Controllers\DeliveryModeController;


// Universal image serving route for public and R2 disks
use App\Http\Controllers\ImageServeController;

// Test routes - Only available in local/development environment
if (app()->environment(['local', 'development'])) {
    // Test image upload route
    Route::match(['get', 'post'], '/test-upload', function (Request $request) {
        if ($request->isMethod('post')) {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->storeAs('products', $file->getClientOriginalName(), 'public');
                return back()->with('success', 'Image uploaded: ' . $path);
            } else {
                return back()->with('error', 'No file uploaded.');
            }
        }
        return view('test-upload');
    })->name('test.upload');

    // Test direct upload to R2
    Route::match(['get', 'post'], '/test-upload-r2', function (Request $request) {
        if ($request->isMethod('post')) {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->storeAs('products', $file->getClientOriginalName(), 'r2');
                return back()->with('success', 'Image uploaded to R2: ' . $path);
            } else {
                return back()->with('error', 'No file uploaded.');
            }
        }
        return view('test-upload');
    })->name('test.upload.r2');
}

// Admin: Update product seller
Route::post('/admin/products/{product}/update-seller', function (Request $request, $product) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(\App\Http\Controllers\AdminController::class)->updateProductSeller($request, \App\Models\Product::findOrFail($product));
})->name('admin.products.updateSeller');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// DIAGNOSTIC ROUTES - For production troubleshooting
Route::get('/health-check', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toDateTimeString(),
        'app' => 'GrabBaskets',
        'env' => config('app.env'),
        'debug' => config('app.debug')
    ], 200);
});

Route::get('/test-index-debug', function () {
    try {
        $diagnostics = [];

        // Test 1: Banner model
        try {
            $banners = \App\Models\Banner::active()->byPosition('hero')->get();
            $diagnostics['banners'] = 'OK - ' . $banners->count() . ' banners';
        } catch (\Exception $e) {
            $diagnostics['banners'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 2: Categories
        try {
            $categories = \App\Models\Category::with('subcategories')->get();
            $diagnostics['categories'] = 'OK - ' . $categories->count() . ' categories';
        } catch (\Exception $e) {
            $diagnostics['categories'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 3: Products
        try {
            $products = \App\Models\Product::whereNotNull('seller_id')->take(5)->get();
            $diagnostics['products'] = 'OK - ' . $products->count() . ' products';
        } catch (\Exception $e) {
            $diagnostics['products'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 4: View exists
        $diagnostics['view_exists'] = view()->exists('index') ? 'YES' : 'NO';

        // Test 5: Database connection
        try {
            DB::connection()->getPdo();
            $diagnostics['database'] = 'OK - Connected';
        } catch (\Exception $e) {
            $diagnostics['database'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 6: Storage permissions
        $diagnostics['storage_writable'] = is_writable(storage_path('logs')) ? 'YES' : 'NO';
        $diagnostics['cache_writable'] = is_writable(storage_path('framework/cache')) ? 'YES' : 'NO';
        $diagnostics['views_writable'] = is_writable(storage_path('framework/views')) ? 'YES' : 'NO';

        // Test 7: Try to load the actual index route logic
        try {
            $banners = \App\Models\Banner::active()->byPosition('hero')->get();
            $categories = \App\Models\Category::with('subcategories')->get();
            $diagnostics['index_route_logic'] = 'OK - Can execute index route code';
        } catch (\Exception $e) {
            $diagnostics['index_route_logic'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 8: Check config cache
        $diagnostics['config_cached'] = file_exists(base_path('bootstrap/cache/config.php')) ? 'YES' : 'NO';
        $diagnostics['routes_cached'] = file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'YES' : 'NO';

        return response()->json([
            'status' => 'Index Page Diagnostics',
            'timestamp' => now()->toDateTimeString(),
            'tests' => $diagnostics,
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'message' => 'All tests completed. Check results above.',
            'next_step' => 'If all tests pass, the issue might be in the view rendering. Clear caches or check permissions.'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Diagnostic failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});

// Removed conflicting closure route - using HomeController instead

// New simplified home route using controller
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Delivery Mode Routes (10-minute vs Normal)
Route::get('/10-minute-delivery', [DeliveryModeController::class, 'tenMinuteDelivery'])->name('delivery.10-minute');
Route::get('/normal-delivery', [DeliveryModeController::class, 'normalDelivery'])->name('delivery.normal');
Route::post('/store-location', [DeliveryModeController::class, 'storeLocation'])->name('delivery.store-location');
Route::get('/delivery/category/{categoryId}', [DeliveryModeController::class, 'getCategoryProducts'])->name('delivery.category-products');
Route::post('/api/location-based-products', [DeliveryModeController::class, 'getLocationBasedProducts'])->name('api.location-based-products');

Route::get('/otp/verify-page', function (Request $request) {
    $user_id = $request->query('user_id');
    $type = $request->query('type', 'email');
    return view('auth.verify-otp', ['user_id' => $user_id, 'type' => $type]);
})->name('otp.verify.page');

// Authenticated user routes
Route::middleware(['auth', 'prevent.back'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/wallet', [ProfileController::class, 'wallet'])->name('wallet.show');
    Route::post('/apply-referral', [App\Http\Controllers\ReferralController::class, 'apply'])->name('referral.apply');

});

// Test route without middleware
Route::get('/test-seller-dashboard', function () {
    $controller = new App\Http\Controllers\SellerController();
    return $controller->dashboard(request());
});

// Verified user routes (buyer + seller)
Route::middleware(['auth', 'verified', 'prevent.back'])->group(function () {
    // Seller: Category & Subcategory
    Route::get('/seller/category-subcategory/create', [SellerController::class, 'createCategorySubcategory'])->name('seller.createCategorySubcategory');
    Route::post('/seller/category-subcategory/store', [SellerController::class, 'storeCategorySubcategory'])->name('seller.storeCategorySubcategory');
    Route::get('/seller/subcategory/add-multiple', [SellerController::class, 'addMultipleSubcategories'])->name('seller.addMultipleSubcategories');
    Route::post('/seller/subcategory/store-multiple', [SellerController::class, 'storeMultipleSubcategories'])->name('seller.storeMultipleSubcategories');

    // Seller: Product Management
    Route::get('/seller/product/create', [SellerController::class, 'createProduct'])->name('seller.createProduct');
    Route::post('/seller/product/store', [SellerController::class, 'storeProduct'])->name('seller.storeProduct');
    Route::get('/seller/product/{product}/edit', [SellerController::class, 'editProduct'])->name('seller.editProduct');
    Route::put('/seller/product/{product}', [SellerController::class, 'updateProduct'])->name('seller.updateProduct');
    Route::delete('/seller/product/{product}', [SellerController::class, 'destroyProduct'])->name('seller.destroyProduct');

    // Product Gallery Management
    Route::get('/seller/product/{product}/gallery', [SellerController::class, 'productGallery'])->name('seller.productGallery');
    Route::post('/seller/product/{product}/images', [SellerController::class, 'uploadProductImages'])->name('seller.uploadProductImages');
    Route::delete('/seller/product-image/{productImage}', [SellerController::class, 'deleteProductImage'])->name('seller.deleteProductImage');
    Route::patch('/seller/product-image/{productImage}/primary', [SellerController::class, 'setPrimaryImage'])->name('seller.setPrimaryImage');

    // Bulk Image Reupload
    Route::get('/seller/bulk-image-reupload', [SellerController::class, 'showBulkImageReupload'])->name('seller.bulkImageReupload');
    Route::post('/seller/bulk-image-reupload', [SellerController::class, 'processBulkImageReupload'])->name('seller.processBulkImageReupload');

    // Legacy bulk uploads (keep for compatibility)
    Route::post('/seller/bulk-image-upload-legacy', [SellerController::class, 'bulkImageUpload'])->name('seller.bulkImageUpload');
    Route::post('/seller/bulk-product-upload', [SellerController::class, 'bulkProductUpload'])->name('seller.bulkProductUpload');

    // Excel Bulk Upload Routes
    Route::get('/seller/bulk-upload-excel', [SellerController::class, 'showBulkUploadForm'])->name('seller.bulkUploadForm');
    Route::post('/seller/bulk-upload-excel', [SellerController::class, 'processBulkUpload'])->name('seller.processBulkUpload');
    Route::get('/seller/download-sample-excel', [SellerController::class, 'downloadSampleExcel'])->name('seller.downloadSampleExcel');

    // Import/Export Products Routes
    Route::get('/seller/import-export', [\App\Http\Controllers\ProductImportExportController::class, 'index'])->name('seller.importExport');
    Route::post('/seller/products/export/excel', [\App\Http\Controllers\ProductImportExportController::class, 'exportExcel'])->name('seller.products.export.excel');
    Route::post('/seller/products/export/csv', [\App\Http\Controllers\ProductImportExportController::class, 'exportCsv'])->name('seller.products.export.csv');
    Route::post('/seller/products/export/pdf', [\App\Http\Controllers\ProductImportExportController::class, 'exportPdf'])->name('seller.products.export.pdf');
    Route::post('/seller/products/export/pdf-with-images', [\App\Http\Controllers\ProductImportExportController::class, 'exportPdfWithImages'])->name('seller.products.export.pdfWithImages');
    Route::post('/seller/products/import', [\App\Http\Controllers\ProductImportExportController::class, 'import'])->name('seller.products.import');
    Route::get('/seller/products/template', [\App\Http\Controllers\ProductImportExportController::class, 'downloadTemplate'])->name('seller.products.template');

    // Seller: Dashboard & Profile
    Route::get('/seller/dashboard', [SellerController::class, 'dashboard'])->name('seller.dashboard');
    Route::get('/seller/my-profile', [SellerController::class, 'myProfile'])->name('seller.profile');
    Route::post('/seller/update-profile', [SellerController::class, 'updateProfile'])->name('seller.updateProfile');
    Route::get('/seller/profile/{seller}', [SellerController::class, 'publicProfileBySeller'])->name('seller.publicProfile');
    Route::get('/seller/transactions', [SellerController::class, 'transactions'])->name('seller.transactions');

    // Seller: Image Library
    Route::get('/seller/image-library', [SellerController::class, 'imageLibrary'])->name('seller.imageLibrary');
    Route::post('/seller/upload-to-library', [SellerController::class, 'uploadToLibrary'])->name('seller.uploadToLibrary');
    Route::get('/seller/get-library-images', [SellerController::class, 'getLibraryImages'])->name('seller.getLibraryImages');
    Route::delete('/seller/delete-library-image', [SellerController::class, 'deleteLibraryImage'])->name('seller.deleteLibraryImage');

    // Orders (user & seller) - Unified for Food and Express orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/track', [OrderController::class, 'track'])->name('orders.track');
    Route::get('/orders/live-track', [OrderController::class, 'liveTrack'])->name('orders.liveTrack');

    // New unified order routes with type parameter
    Route::get('/orders/{type}/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{type}/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/seller/orders', [OrderController::class, 'sellerOrders'])->name('seller.orders');
    Route::post('/orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/update-tracking', [OrderController::class, 'updateTracking'])->name('orders.updateTracking');

    // Quick Delivery & Live Tracking (Blinkit/Zepto Style)
    Route::get('/orders/{order}/live-tracking', [OrderController::class, 'liveTracking'])->name('orders.liveTracking');
    Route::post('/orders/check-quick-delivery', [OrderController::class, 'checkQuickDelivery'])->name('orders.checkQuickDelivery');
    Route::post('/orders/{order}/assign-delivery', [OrderController::class, 'assignDelivery'])->name('orders.assignDelivery');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/{cartItem}/move-to-wishlist', [CartController::class, 'moveToWishlist'])->name('cart.moveToWishlist');
    Route::post('/cart/{cartItem}/switch-delivery', [CartController::class, 'switchDeliveryType'])->name('cart.switchDelivery');
    Route::get('/checkout', [CartController::class, 'showCheckout'])->name('cart.checkout.page');
    Route::get('/checkout-new', [CartController::class, 'showCheckoutNew'])->name('cart.checkout.new');
    Route::post('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    // Payment routes
    Route::post('/payment/create-order', [PaymentController::class, 'createOrder'])->name('payment.createOrder');
    Route::post('/payment/verify', [PaymentController::class, 'verifyPayment'])->name('payment.verify');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/add', [WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/wishlist/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.moveToCart');
    Route::get('/wishlist/check/{product}', [WishlistController::class, 'checkStatus'])->name('wishlist.check');
    Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('wishlist.count');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');

    // Chatbot
    Route::post('/chatbot/support', [SupportController::class, 'chatbotSupport'])->name('chatbot.support');

    // Courier Tracking (Authenticated users)
    Route::get('/tracking/order/{order}', [CourierTrackingController::class, 'trackOrder'])->name('tracking.order');
    Route::post('/tracking/track-multiple', [CourierTrackingController::class, 'trackMultiple'])->name('tracking.multiple');
});

// OTP Auth
Route::post('/otp/send', [OtpController::class, 'send'])->name('otp.send');
Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');

// ===== PUBLIC BUYER ROUTES (Guest + Authenticated users can access) =====
// Buyer dashboard & browsing - Anyone can view products
Route::get('/buyer/dashboard', [BuyerController::class, 'dashboard'])->name('buyer.dashboard');

// Buyer referral page - View and share referral code
Route::get('/buyer/referral', function () {
    if (!auth()->check() || auth()->user()->role !== 'buyer') {
        return redirect()->route('login')->with('error', 'Please login as a buyer to access referrals');
    }
    return view('buyer.referral');
})->name('buyer.referral');

// Test route to bypass controller and render view directly
Route::get('/buyer/dashboard/test', function () {
    try {
        $categories = \App\Models\Category::with([
            'subcategories' => function ($query) {
                $query->withCount('products');
            }
        ])->withCount('products')->get();

        return view('buyer.dashboard', compact('categories'));
    } catch (\Exception $e) {
        return response('DIRECT TEST ERROR: ' . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
    }
})->name('buyer.dashboard.test');

// Simple text debug route to test buyer dashboard functionality
Route::get('/buyer/dashboard/debug', function () {
    $output = "=== BUYER DASHBOARD DEBUG ===\n\n";

    try {
        // Test 1: Database connection
        $output .= "1. Database Connection: ";
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $output .= "SUCCESS\n";

        // Test 2: Category model basic query
        $output .= "2. Category Model: ";
        $categoryCount = \App\Models\Category::count();
        $output .= "SUCCESS - {$categoryCount} categories found\n";

        // Test 3: Category with relationships
        $output .= "3. Category Relationships: ";
        $categories = \App\Models\Category::with([
            'subcategories' => function ($query) {
                $query->withCount('products');
            }
        ])->withCount('products')->take(1)->get();
        $output .= "SUCCESS - Relationships loaded\n";

        // Test 4: View existence
        $output .= "4. Dashboard View: ";
        $viewExists = view()->exists('buyer.dashboard');
        $output .= $viewExists ? "EXISTS\n" : "NOT FOUND\n";

        // Test 5: Try to call dashboard method directly
        $output .= "5. Dashboard Controller: ";
        $controller = new \App\Http\Controllers\BuyerController();
        $output .= "INSTANTIATED SUCCESSFULLY\n";

        $output .= "\n=== ALL TESTS PASSED ===\n";
        $output .= "The buyer dashboard should be working. If still getting 500 error, check server logs for runtime issues.\n";
    } catch (\Exception $e) {
        $output .= "FAILED\n";
        $output .= "ERROR: " . $e->getMessage() . "\n";
        $output .= "FILE: " . $e->getFile() . "\n";
        $output .= "LINE: " . $e->getLine() . "\n";
    }

    return response($output, 200)->header('Content-Type', 'text/plain');
})->name('buyer.dashboard.debug');
// Debug route for category testing
Route::get('/debug-category/{id}', function ($id) {
    try {
        $category = \App\Models\Category::find($id);
        if (!$category) {
            return "Category {$id} not found";
        }

        $products = \App\Models\Product::where('category_id', $id)->count();

        return response()->json([
            'category_id' => $id,
            'category_name' => $category->name,
            'products_count' => $products,
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Debug route for controller testing
Route::get('/debug-controller-category/{id}', function ($id) {
    try {
        $request = new \Illuminate\Http\Request();
        $controller = new \App\Http\Controllers\BuyerController();

        // Test data gathering first
        $category = \App\Models\Category::findOrFail($id);
        $products = \App\Models\Product::with(['category', 'subcategory'])
            ->where('category_id', $id)->paginate(1);

        return response()->json([
            'category_id' => $id,
            'category_name' => $category->name,
            'products_found' => $products->count(),
            'controller_accessible' => true,
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Debug route to force cache clearing (for deployment issues)
Route::get('/debug-clear-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');

        return response()->json([
            'message' => 'All caches cleared successfully',
            'timestamp' => now(),
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'status' => 'failed'
        ], 500);
    }
});

// Debug route for delivery partner data
Route::get('/debug-delivery-partners', function () {
    try {
        $partners = \App\Models\DeliveryPartner::select(['id', 'name', 'email', 'phone', 'status'])
            ->limit(5)
            ->get();

        $totalCount = \App\Models\DeliveryPartner::count();

        return response()->json([
            'total_partners' => $totalCount,
            'sample_partners' => $partners,
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'status' => 'failed'
        ], 500);
    }
});

// Debug route to create a test delivery partner
Route::get('/debug-create-delivery-partner', function () {
    try {
        // Check if test partner already exists
        $existing = \App\Models\DeliveryPartner::where('email', 'test@delivery.com')->first();
        if ($existing) {
            return response()->json([
                'message' => 'Test delivery partner already exists',
                'partner' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'email' => $existing->email,
                    'phone' => $existing->phone,
                    'status' => $existing->status
                ],
                'login_credentials' => [
                    'email' => 'test@delivery.com',
                    'phone' => '9999999999',
                    'password' => 'password123'
                ],
                'status' => 'exists'
            ]);
        }

        // Create test partner
        $partner = \App\Models\DeliveryPartner::create([
            'name' => 'Test Delivery Partner',
            'email' => 'test@delivery.com',
            'phone' => '9999999999',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'address' => 'Test Address',
            'city' => 'Test City',
            'state' => 'Test State',
            'pincode' => '123456',
            'vehicle_type' => 'bike',
            'status' => 'approved',
            'is_verified' => true,
            'is_online' => true,
            'is_available' => true
        ]);

        return response()->json([
            'message' => 'Test delivery partner created successfully',
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'phone' => $partner->phone,
                'status' => $partner->status
            ],
            'login_credentials' => [
                'email' => 'test@delivery.com',
                'phone' => '9999999999',
                'password' => 'password123'
            ],
            'status' => 'created'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'status' => 'failed'
        ], 500);
    }
});

Route::get('/buyer/category/{category_id}', [BuyerController::class, 'productsByCategory'])->name('buyer.productsByCategory');
Route::get('/buyer/subcategory/{subcategory_id}', [BuyerController::class, 'productsBySubcategory'])->name('buyer.productsBySubcategory');

// Store/Seller catalog - View all products from a specific store (no auth required for guests)
Route::get('/store/{seller}', [SellerController::class, 'storeProducts'])->name('store.products');

// Product details & reviews - Anyone can view (no auth required)
Route::get('/products/{id}', [ProductController::class, 'show'])->name('product.details');
Route::post('/products/{id}/review', [ProductController::class, 'addReview'])
    ->middleware(['auth', 'verified'])
    ->name('product.addReview');

// Delivery Zone / District-wise filtering routes
Route::get('/api/products/by-delivery-type', [ProductController::class, 'getByDeliveryType'])->name('api.products.byDeliveryType');
Route::get('/api/nearby-stores', [ProductController::class, 'getNearbyStores'])->middleware('auth')->name('api.nearbyStores');
Route::get('/api/product/{id}/check-10min', [ProductController::class, 'check10MinDelivery'])->middleware('auth')->name('api.product.check10min');


// Public product search - Anyone can search (Zepto/Blinkit style..)
Route::get('/products', [App\Http\Controllers\BuyerController::class, 'search'])->name('products.index');

// Food delivery products route
Route::get('/products/food-delivery', [App\Http\Controllers\SimpleSearchController::class, 'foodDelivery'])->name('products.food-delivery');

// Food delivery main page route (alias for convenience)
Route::get('/food', [App\Http\Controllers\SimpleSearchController::class, 'foodDelivery'])->name('food.index');

// Food Shops Routes
Route::get('/food/shops', [App\Http\Controllers\FoodShopController::class, 'index'])->name('food.shops.index');
Route::get('/food/shop/{id}', [App\Http\Controllers\FoodShopController::class, 'show'])->name('food.shops.show');
Route::post('/food/cart/add/{id}', [App\Http\Controllers\FoodShopController::class, 'addToCart'])->name('food.cart.add');

// AJAX search routes disabled per user request
// Route::get('/api/search/instant', [App\Http\Controllers\SimpleSearchController::class, 'instantSearch'])->name('search.instant');
// Route::get('/api/search/suggestions', [App\Http\Controllers\SimpleSearchController::class, 'suggestions'])->name('search.suggestions');

// Optimized search route (for testing)
Route::get('/products/optimized', [App\Http\Controllers\OptimizedBuyerController::class, 'guestSearch'])->name('products.optimized');

// Legacy search suggestions API (fallback)  
Route::get('/api/search/suggestions/legacy', [App\Http\Controllers\OptimizedBuyerController::class, 'getSearchSuggestions'])->name('search.suggestions.legacy');

// Legacy search route
Route::get('/products/legacy', [BuyerController::class, 'search'])->name('products.legacy');

// Store/Seller catalog - View all products from a specific store
Route::get('/store/{seller_id}/catalog', [BuyerController::class, 'storeCatalog'])->name('store.catalog');

// Courier Tracking (Public access)
Route::get('/tracking', [CourierTrackingController::class, 'showForm'])->name('tracking.form');
Route::post('/tracking/track', [CourierTrackingController::class, 'track'])->name('tracking.track');
Route::get('/tracking/detect/{trackingNumber}', [CourierTrackingController::class, 'detectCourier'])->name('tracking.detect');

// API Routes for Courier Tracking
Route::prefix('api/tracking')->group(function () {
    Route::post('/track', [CourierTrackingController::class, 'apiTrack'])->name('api.tracking.track');
    Route::get('/detect/{trackingNumber}', [CourierTrackingController::class, 'apiDetectCourier'])->name('api.tracking.detect');
});

// API route for mobile category menu subcategories
Route::get('/api/categories/{category}/subcategories', function ($categoryId) {
    try {
        $category = \App\Models\Category::with([
            'subcategories' => function ($query) {
                $query->withCount('products');
            }
        ])->findOrFail($categoryId);

        $subcategories = $category->subcategories->map(function ($subcat) {
            return [
                'id' => $subcat->id,
                'name' => $subcat->name,
                'emoji' => $subcat->emoji ?? '📦',
                'products_count' => $subcat->products_count,
                'url' => route('buyer.productsBySubcategory', $subcat->id)
            ];
        });

        return response()->json([
            'success' => true,
            'subcategories' => $subcategories,
            'category_name' => $category->name
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Category not found'
        ], 404);
    }
})->name('api.categories.subcategories');

// API Health Check for diagnostics
Route::get('/api/health-check', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'server_time' => microtime(true)
    ]);
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Session-based, not auth middleware)
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Admin Routes (Session-based)
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Admin Routes (Session-based)
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', function () {
    return view('admin.login');
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($request->email === 'admin@swivel.co.in' && $request->password === 'swivel') {
        session(['is_admin' => true]);
        return redirect('/admin/dashboard');
    }

    return back()->withErrors(['email' => 'Invalid admin credentials']);
})->name('admin.login.submit');

Route::get('/admin/logout', function () {
    session()->forget('is_admin');
    return redirect('/');
})->name('admin.logout');

// Seller Login Routes
Route::get('/seller/login', function () {
    return view('seller.login');
})->name('seller.login');

Route::post('/seller/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $seller = \App\Models\Seller::where('email', $request->email)->first();

    if ($seller && Hash::check($request->password, $seller->password)) {
        session(['seller_id' => $seller->id, 'is_seller' => true]);
        return redirect('/seller/dashboard');
    }

    return back()->withErrors(['email' => 'Invalid seller credentials']);
})->name('seller.login.submit');

Route::get('/seller/logout', function () {
    session()->forget(['seller_id', 'is_seller']);
    return redirect('/');
})->name('seller.logout');

// Delivery Partner Login Routes - DEPRECATED (See line 1504)
/*
Route::prefix('partner')->name('delivery-partner.')->group(function () {

    Route::get('/login', [DeliveryPartner::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [DeliveryPartner::class, 'login'])
        ->name('login.submit');

    Route::post('/logout', [DeliveryPartner::class, 'logout'])
        ->name('logout');

});
*/
// Categories Alias
Route::get('/all-categories', [App\Http\Controllers\HomeController::class, 'index'])->name('categories.index');

// Admin dashboard
Route::get('/admin/dashboard', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->transactions();
})->name('admin.dashboard');

// Admin pages (GET)
Route::get('/admin/manageuser', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->users($request);
})->name('admin.manageuser');

Route::get('/admin/orders', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->orders(request(), 'standard');
})->name('admin.orders');

Route::get('/admin/food', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->orders(request(), 'food');
})->name('admin.orders.food');

Route::get('/admin/tenmins', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->orders(request(), 'express');
})->name('admin.orders.express');

Route::get('/admin/products', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->products(request());
})->name('admin.products');

Route::get('/admin/products-by-seller', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->productsBySeller($request);
})->name('admin.products.bySeller');

Route::get('/admin/bulk-product-upload', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->showBulkProductUpload();
})->name('admin.bulkProductUpload');


// Admin actions (POST/DELETE)
Route::post('/admin/bulk-product-upload', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->handleBulkProductUpload($request);
})->name('admin.bulkProductUpload.post');

Route::delete('/admin/users/{id}', function ($id) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->destroy($id);
})->name('admin.users.delete');

Route::post('/admin/users/{user}/suspend', function (Request $request, $user) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->suspendUser(\App\Models\User::findOrFail($user));
})->name('admin.users.suspend');

Route::post('/admin/orders/{id}/update-status', function (Request $request, $id) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->updateOrderStatus($request, $id);
})->name('admin.updateOrderStatus');

Route::post('/admin/orders/{id}/update-tracking', function (Request $request, $id) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->updateTracking($request, \App\Models\Order::findOrFail($id));
})->name('admin.updateTracking');

Route::post('/admin/orders/{id}/assign-partner', function (Request $request, $id) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->assignDeliveryPartner($request, $id);
})->name('admin.assignDeliveryPartner');

Route::delete('/admin/products/{product}', function ($product) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->destroyProduct(\App\Models\Product::findOrFail($product));
})->name('admin.products.destroy');

// Admin Promotional Notifications
Route::get('/admin/promotional-notifications', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->showPromotionalForm();
})->name('admin.promotional.form');

Route::post('/admin/send-promotional-notification', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->sendPromotionalNotification($request);
})->name('admin.promotional.send');

Route::post('/admin/send-automated-notifications', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->sendAutomatedNotifications($request);
})->name('admin.promotional.automated');

Route::post('/admin/send-bulk-promotional-email', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(AdminController::class)->sendBulkPromotionalEmail($request);
})->name('admin.promotional.bulk.email');

// SMS Management Routes
Route::get('/admin/sms-management', function () {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(\App\Http\Controllers\SmsController::class)->index();
})->name('admin.sms.dashboard');

Route::post('/admin/sms/test', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(\App\Http\Controllers\SmsController::class)->testSms($request);
})->name('admin.sms.test');

Route::post('/admin/sms/test-sellers', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(\App\Http\Controllers\SmsController::class)->testWithCurrentSellers($request);
})->name('admin.sms.test.sellers');

Route::post('/admin/sms/bulk-promotion', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(\App\Http\Controllers\SmsController::class)->sendBulkPromotion($request);
})->name('admin.sms.bulk');

Route::post('/admin/sms/order-reminders', function (Request $request) {
    if (!session('is_admin')) {
        return redirect()->route('admin.login');
    }
    return app(\App\Http\Controllers\SmsController::class)->sendOrderReminders($request);
})->name('admin.sms.reminders');

// Admin Banner Management Routes
Route::prefix('admin/banners')->middleware('web')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\BannerController::class, 'index'])->name('admin.banners.index');
    Route::get('/create', [App\Http\Controllers\Admin\BannerController::class, 'create'])->name('admin.banners.create');
    Route::post('/', [App\Http\Controllers\Admin\BannerController::class, 'store'])->name('admin.banners.store');
    Route::get('/{id}/edit', [App\Http\Controllers\Admin\BannerController::class, 'edit'])->name('admin.banners.edit');
    Route::put('/{id}', [App\Http\Controllers\Admin\BannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('/{id}', [App\Http\Controllers\Admin\BannerController::class, 'destroy'])->name('admin.banners.destroy');
    Route::post('/{id}/toggle', [App\Http\Controllers\Admin\BannerController::class, 'toggleStatus'])->name('admin.banners.toggle');
});

// Admin Category Emoji Management Routes
Route::prefix('admin/category-emojis')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\CategoryEmojiController::class, 'index'])->name('admin.category-emojis.index');
    Route::put('/{category}', [App\Http\Controllers\Admin\CategoryEmojiController::class, 'update'])->name('admin.category-emojis.update');
    Route::post('/bulk-update', [App\Http\Controllers\Admin\CategoryEmojiController::class, 'bulkUpdate'])->name('admin.category-emojis.bulk-update');
    Route::post('/suggestions', [App\Http\Controllers\Admin\CategoryEmojiController::class, 'getSuggestions'])->name('admin.category-emojis.suggestions');
});

// Admin Index Page Editor Routes
Route::prefix('admin/index-editor')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\IndexPageEditorController::class, 'index'])->name('admin.index-editor.index');
    Route::put('/update', [App\Http\Controllers\Admin\IndexPageEditorController::class, 'update'])->name('admin.index-editor.update');
    Route::get('/preview', [App\Http\Controllers\Admin\IndexPageEditorController::class, 'preview'])->name('admin.index-editor.preview');
});

// Admin Warehouse Management Routes
Route::prefix('admin/warehouse')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\WarehouseController::class, 'dashboard'])->name('admin.warehouse.dashboard');
    Route::get('/inventory', [App\Http\Controllers\Admin\WarehouseController::class, 'inventory'])->name('admin.warehouse.inventory');
    Route::get('/product/{id}', [App\Http\Controllers\Admin\WarehouseController::class, 'show'])->name('admin.warehouse.product.show');
    Route::put('/product/{id}', [App\Http\Controllers\Admin\WarehouseController::class, 'update'])->name('admin.warehouse.product.update');
    Route::get('/stock-movements', [App\Http\Controllers\Admin\WarehouseController::class, 'stockMovements'])->name('admin.warehouse.stock-movements');
    Route::post('/add-stock', [App\Http\Controllers\Admin\WarehouseController::class, 'addStock'])->name('admin.warehouse.add-stock');
    Route::get('/quick-delivery', [App\Http\Controllers\Admin\WarehouseController::class, 'quickDeliveryOptimization'])->name('admin.warehouse.quick-delivery');
    Route::post('/product/{id}/toggle-quick-delivery', [App\Http\Controllers\Admin\WarehouseController::class, 'toggleQuickDelivery'])->name('admin.warehouse.toggle-quick-delivery');
    Route::post('/bulk-operation', [App\Http\Controllers\Admin\WarehouseController::class, 'bulkOperation'])->name('admin.warehouse.bulk-operation');
    Route::get('/export-inventory', [App\Http\Controllers\Admin\WarehouseController::class, 'exportInventory'])->name('admin.warehouse.export-inventory');
});

// Admin Delivery Partner Management Routes
Route::prefix('admin/delivery-partners')->name('admin.delivery-partners.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'dashboard'])->name('dashboard');

    // List all partners
    Route::get('/', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'index'])->name('index');

    // Show partner details
    Route::get('/{deliveryPartner}', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'show'])->name('show');

    // Approve/Block/Unblock partners
    Route::post('/{id}/approve', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'approve'])->name('approve');
    Route::post('/{id}/block', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'block'])->name('block');
    Route::post('/{id}/unblock', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'unblock'])->name('unblock');
    Route::post('/{id}/reject', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'reject'])->name('reject');

    // Assign job to partner
    Route::post('/{deliveryPartner}/assign-job', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'assignJob'])->name('assign-job');

    // Bulk assign jobs
    Route::post('/bulk-assign', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'bulkAssignJobs'])->name('bulk-assign');

    // Update partner status
    Route::post('/{deliveryPartner}/update-status', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'updateStatus'])->name('update-status');

    // Track partner location
    Route::get('/{deliveryPartner}/track', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'track'])->name('track');

    // Send notification
    Route::post('/{deliveryPartner}/send-notification', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'sendNotification'])->name('send-notification');

    // API routes
    Route::get('/api/available-partners', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'getAvailablePartners'])->name('api.available');
    Route::get('/api/statistics', [App\Http\Controllers\Admin\AdminDeliveryPartnerController::class, 'getStatistics'])->name('api.statistics');
});


// Separate Warehouse Staff Authentication & Management Routes
Route::prefix('warehouse')->group(function () {
    // Authentication routes
    Route::get('/login', [App\Http\Controllers\Warehouse\AuthController::class, 'showLoginForm'])->name('warehouse.login');
    Route::post('/login', [App\Http\Controllers\Warehouse\AuthController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Warehouse\AuthController::class, 'logout'])->name('warehouse.logout');

    // Protected warehouse routes
    Route::middleware('auth:warehouse')->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Warehouse\DashboardController::class, 'index'])->name('warehouse.dashboard');
        Route::get('/stats', [App\Http\Controllers\Warehouse\DashboardController::class, 'quickStats'])->name('warehouse.stats');
        Route::get('/notifications', [App\Http\Controllers\Warehouse\DashboardController::class, 'notifications'])->name('warehouse.notifications');
        Route::get('/search', [App\Http\Controllers\Warehouse\DashboardController::class, 'search'])->name('warehouse.search');

        // User Profile Management
        Route::get('/profile', [App\Http\Controllers\Warehouse\AuthController::class, 'profile'])->name('warehouse.profile');
        Route::put('/profile', [App\Http\Controllers\Warehouse\AuthController::class, 'updateProfile'])->name('warehouse.profile.update');

        // User Management (Managers only)
        Route::get('/users', [App\Http\Controllers\Warehouse\AuthController::class, 'userManagement'])->name('warehouse.users');
        Route::post('/users', [App\Http\Controllers\Warehouse\AuthController::class, 'createUser'])->name('warehouse.users.create');
        Route::put('/users/{id}', [App\Http\Controllers\Warehouse\AuthController::class, 'updateUser'])->name('warehouse.users.update');
        Route::post('/users/{id}/toggle-status', [App\Http\Controllers\Warehouse\AuthController::class, 'toggleUserStatus'])->name('warehouse.users.toggle-status');

        // Inventory Management
        Route::get('/inventory', [App\Http\Controllers\Warehouse\InventoryController::class, 'index'])->name('warehouse.inventory');
        Route::get('/inventory/add', [App\Http\Controllers\Warehouse\InventoryController::class, 'showAddStock'])->name('warehouse.inventory.add');
        Route::post('/inventory/add', [App\Http\Controllers\Warehouse\InventoryController::class, 'addStock'])->name('warehouse.inventory.store');
        Route::get('/inventory/adjust', [App\Http\Controllers\Warehouse\InventoryController::class, 'showAdjustStock'])->name('warehouse.inventory.adjust');
        Route::post('/inventory/adjust', [App\Http\Controllers\Warehouse\InventoryController::class, 'adjustStock'])->name('warehouse.inventory.adjust.store');
        Route::get('/inventory/{id}', [App\Http\Controllers\Warehouse\InventoryController::class, 'show'])->name('warehouse.inventory.show');
        Route::put('/inventory/{id}', [App\Http\Controllers\Warehouse\InventoryController::class, 'update'])->name('warehouse.inventory.update');

        // Stock Movements
        Route::get('/stock-movements', [App\Http\Controllers\Warehouse\StockMovementController::class, 'index'])->name('warehouse.stock-movements');
        Route::get('/stock-movements/{id}', [App\Http\Controllers\Warehouse\StockMovementController::class, 'show'])->name('warehouse.stock-movements.show');

        // Quick Delivery Management
        Route::get('/quick-delivery', [App\Http\Controllers\Warehouse\QuickDeliveryController::class, 'index'])->name('warehouse.quick-delivery');
        Route::post('/quick-delivery/{id}/toggle', [App\Http\Controllers\Warehouse\QuickDeliveryController::class, 'toggle'])->name('warehouse.quick-delivery.toggle');
        Route::get('/quick-delivery/optimize', [App\Http\Controllers\Warehouse\QuickDeliveryController::class, 'optimize'])->name('warehouse.quick-delivery.optimize');

        // Reports & Analytics
        Route::get('/reports', [App\Http\Controllers\Warehouse\ReportController::class, 'index'])->name('warehouse.reports');
        Route::get('/reports/stock-summary', [App\Http\Controllers\Warehouse\ReportController::class, 'stockSummary'])->name('warehouse.reports.stock-summary');
        Route::get('/reports/movements', [App\Http\Controllers\Warehouse\ReportController::class, 'movements'])->name('warehouse.reports.movements');
        Route::get('/reports/export', [App\Http\Controllers\Warehouse\ReportController::class, 'export'])->name('warehouse.reports.export');

        // Location Management
        Route::get('/locations', [App\Http\Controllers\Warehouse\LocationController::class, 'index'])->name('warehouse.locations');
        Route::post('/locations', [App\Http\Controllers\Warehouse\LocationController::class, 'store'])->name('warehouse.locations.store');
        Route::put('/locations/{id}', [App\Http\Controllers\Warehouse\LocationController::class, 'update'])->name('warehouse.locations.update');
        Route::delete('/locations/{id}', [App\Http\Controllers\Warehouse\LocationController::class, 'destroy'])->name('warehouse.locations.destroy');
    });
});

// Debug route to check emojis
Route::get('/debug/emojis', function () {
    $categories = App\Models\Category::select('id', 'name', 'emoji')->get();
    $output = '<h1>Category Emojis</h1><ul>';
    foreach ($categories as $cat) {
        $output .= '<li>' . $cat->id . ': ' . $cat->name . ' = ' . ($cat->emoji ?: 'NULL') . '</li>';
    }
    $output .= '</ul>';
    return $output;
});

// Test route to update an emoji manually
Route::get('/debug/test-emoji-update/{id}/{emoji}', function ($id, $emoji) {
    $category = App\Models\Category::find($id);
    if ($category) {
        $category->emoji = $emoji;
        $category->save();
        return "Updated category {$category->name} with emoji: {$emoji}";
    }
    return "Category not found";
});

Route::post('seller/update-images-zip', [App\Http\Controllers\SellerController::class, 'updateImagesByZip'])->name('seller.updateImagesByZip');

// Include test routes
require __DIR__ . '/test.php';

// Include debug routes
require __DIR__ . '/debug.php';

require __DIR__ . '/auth.php';

// Public debug routes (no authentication required)
Route::get('/debug-bulk-system', function () {
    try {
        return response()->json([
            'status' => 'OK',
            'ziparchive_available' => class_exists('ZipArchive'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'storage_driver' => config('filesystems.default'),
            'categories_count' => \App\Models\Category::count(),
            'products_count' => \App\Models\Product::count(),
            'auth_middleware' => 'Route requires login to test seller features'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Image serving route as fallback for storage symlink issues
// Log facade is available via global alias; explicit import not required here

Route::get('/serve-image/{type}/{path}', function ($type, $path) {
    error_log("SERVE-IMAGE HIT: type=$type, path=$path");
    \Log::info("DEBUG: /serve-image hit", ['type' => $type, 'path' => $path]);

    try {
        $allowedTypes = ['products', 'public', 'library', 'r2'];
        if (!in_array($type, $allowedTypes, true)) {
            \Log::warning("/serve-image: Invalid type", ['type' => $type, 'path' => $path]);
            return response()->json(['error' => 'Invalid type', 'type' => $type], 404);
        }

        $leafPath = ltrim($path, '/');
        $storagePath = $leafPath;

        if ($type === 'public') {
            $storagePath = preg_replace('/^public\//', '', $leafPath);
        } elseif ($type === 'library') {
            $storagePath = 'library/' . $leafPath;
        } elseif ($type === 'products') {
            $storagePath = 'products/' . $leafPath;
        }

        \Log::info("/serve-image: Resolved storage path", ['storagePath' => $storagePath, 'type' => $type]);

        // Try public disk first
        try {
            $publicExists = Storage::disk('public')->exists($storagePath);
            Log::info("/serve-image: Public disk check", [
                'path' => $storagePath,
                'exists' => $publicExists,
                'disk_root' => Storage::disk('public')->path(''),
                'full_path' => Storage::disk('public')->path($storagePath)
            ]);

            if ($publicExists) {
                Log::info("/serve-image: Found in public disk", ['path' => $storagePath]);
                $file = Storage::disk('public')->get($storagePath);
                $fullPath = Storage::disk('public')->path($storagePath);
                $mimeType = 'image/jpeg';
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($fullPath);
                    if ($detectedType) {
                        $mimeType = $detectedType;
                    }
                }
                return Response::make($file, 200, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            } else {
                Log::info("/serve-image: Not found in public disk", ['path' => $storagePath]);
            }
        } catch (\Throwable $publicEx) {
            Log::warning("/serve-image: Public disk error", [
                'path' => $storagePath,
                'error' => $publicEx->getMessage()
            ]);
        }
        // Try R2 SDK directly 
        try {
            if (Storage::disk('r2')->exists($storagePath)) {
                Log::info("/serve-image: Found in r2 disk via SDK", ['path' => $storagePath]);
                $file = Storage::disk('r2')->get($storagePath);
                $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
                $mimeType = 'image/jpeg';
                if (in_array($ext, ['png', 'gif', 'webp'])) {
                    $mimeType = 'image/' . $ext;
                }
                return Response::make($file, 200, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            } else {
                Log::info("/serve-image: Not found in r2 disk via SDK", ['path' => $storagePath]);
            }
        } catch (\Throwable $sdkEx) {
            Log::warning('R2 SDK error in /serve-image', [
                'path' => $storagePath,
                'message' => $sdkEx->getMessage(),
            ]);
        }

        // For legacy paths, try multiple fallback paths
        if ($type === 'products') {
            $legacyPaths = [
                $leafPath, // Just the filename part without products/ prefix
                'images/' . $leafPath, // Old images/ prefix
                'storage/' . $leafPath, // Old storage/ prefix
                'uploads/' . $leafPath, // Old uploads/ prefix
            ];

            foreach ($legacyPaths as $legacyPath) {
                try {
                    if (Storage::disk('public')->exists($legacyPath)) {
                        Log::info("/serve-image: Found legacy path in public disk", ['path' => $legacyPath]);
                        $file = Storage::disk('public')->get($legacyPath);
                        $fullPath = Storage::disk('public')->path($legacyPath);
                        $mimeType = 'image/jpeg';
                        if (function_exists('mime_content_type')) {
                            $detectedType = mime_content_type($fullPath);
                            if ($detectedType) {
                                $mimeType = $detectedType;
                            }
                        }
                        return Response::make($file, 200, [
                            'Content-Type' => $mimeType,
                            'Cache-Control' => 'public, max-age=86400',
                        ]);
                    }

                    if (Storage::disk('r2')->exists($legacyPath)) {
                        Log::info("/serve-image: Found legacy path in r2 disk", ['path' => $legacyPath]);
                        $file = Storage::disk('r2')->get($legacyPath);
                        $ext = strtolower(pathinfo($legacyPath, PATHINFO_EXTENSION));
                        $mimeType = 'image/jpeg';
                        if (in_array($ext, ['png', 'gif', 'webp'])) {
                            $mimeType = 'image/' . $ext;
                        }
                        return Response::make($file, 200, [
                            'Content-Type' => $mimeType,
                            'Cache-Control' => 'public, max-age=86400',
                        ]);
                    }
                } catch (\Throwable $legacyEx) {
                    // Continue to next legacy path
                    Log::debug('Legacy path not found', ['path' => $legacyPath]);
                }
            }

            Log::warning('All legacy paths failed for /serve-image', [
                'tested_paths' => $legacyPaths,
                'original_path' => $storagePath
            ]);
        }

        // If R2 public URL is configured, try redirect as fallback
        $r2Base = config('filesystems.disks.r2.url');
        if (!empty($r2Base)) {
            $target = rtrim($r2Base, '/') . '/' . ltrim($storagePath, '/');
            Log::info("/serve-image: Redirecting to R2 public URL", ['target' => $target]);
            return redirect()->away($target, 302, [
                'Cache-Control' => 'public, max-age=86400'
            ]);
        }


        Log::warning("/serve-image: File not found in any disk", ['path' => $storagePath]);

        // Return a placeholder image instead of 404 JSON for better UX
        return redirect('https://via.placeholder.com/480x300?text=Image+Not+Found');
    } catch (\Throwable $e) {
        Log::error('Error in /serve-image route', [
            'type' => $type,
            'path' => $path,
            'message' => $e->getMessage()
        ]);
        // Fallback to placeholder on fatal error too
        return redirect('https://via.placeholder.com/480x300?text=Error+Loading+Image');
    }
})->where('path', '.*');

// DEBUG: Visual image test page
Route::get('/debug/image-display-test', function () {
    return response()->json([
        'status' => 'Testing basic response',
        'product_count' => \App\Models\Product::count(),
        'products_with_images' => \App\Models\Product::where('image', '!=', '')->whereNotNull('image')->count(),
        'app_url' => config('app.url'),
        'storage_disks' => config('filesystems.disks'),
    ]);
});

// DEBUG: Test specific image path
Route::get('/debug/test-image-path', function () {
    $testPath = 'products/seller-2/srm340-1760342455.jpg';

    $result = [
        'test_path' => $testPath,
        'r2_configured' => config('filesystems.disks.r2') !== null,
        'r2_bucket' => config('filesystems.disks.r2.bucket', 'NOT SET'),
        'checks' => []
    ];

    // Check public disk
    try {
        $publicExists = Storage::disk('public')->exists($testPath);
        $result['checks']['public'] = [
            'exists' => $publicExists,
            'root' => Storage::disk('public')->path(''),
        ];
    } catch (\Exception $e) {
        $result['checks']['public'] = ['error' => $e->getMessage()];
    }

    // Check R2 disk
    try {
        $r2Exists = Storage::disk('r2')->exists($testPath);
        $result['checks']['r2'] = [
            'exists' => $r2Exists,
        ];

        if ($r2Exists) {
            $result['checks']['r2']['size'] = Storage::disk('r2')->size($testPath);
            $result['checks']['r2']['last_modified'] = Storage::disk('r2')->lastModified($testPath);
        }
    } catch (\Exception $e) {
        $result['checks']['r2'] = ['error' => $e->getMessage()];
    }

    return response()->json($result);
});

// Debug: List files in storage to see what actually exists
Route::get('/debug/storage-files', function (Request $request) {
    $directory = $request->get('dir', 'products');
    $result = [
        'directory' => $directory,
        'public_files' => [],
        'r2_files' => [],
        'errors' => []
    ];

    try {
        $publicFiles = Storage::disk('public')->allFiles($directory);
        $result['public_files'] = array_slice($publicFiles, 0, 20); // Limit to first 20
    } catch (\Throwable $e) {
        $result['errors']['public'] = $e->getMessage();
    }

    try {
        $r2Files = Storage::disk('r2')->allFiles($directory);
        $result['r2_files'] = array_slice($r2Files, 0, 20); // Limit to first 20
    } catch (\Throwable $e) {
        $result['errors']['r2'] = $e->getMessage();
    }

    return response()->json($result);
});

// Debug: Check file system and storage configuration
Route::get('/debug/file-system', function (Request $request) {
    $path = $request->get('path', 'products/1551/teat-1760330018-OtAw4b.jpg');

    $result = [
        'path_tested' => $path,
        'public_disk' => [
            'exists' => false,
            'root_path' => '',
            'full_path' => '',
            'error' => null,
        ],
        'r2_disk' => [
            'exists' => false,
            'config' => [],
            'error' => null,
        ],
        'app_env' => app()->environment(),
        'storage_link_exists' => is_link(public_path('storage')),
    ];

    // Test public disk
    try {
        $result['public_disk']['root_path'] = Storage::disk('public')->path('');
        $result['public_disk']['full_path'] = Storage::disk('public')->path($path);
        $result['public_disk']['exists'] = Storage::disk('public')->exists($path);
    } catch (\Throwable $e) {
        $result['public_disk']['error'] = $e->getMessage();
    }

    // Test R2 disk
    try {
        $result['r2_disk']['config'] = config('filesystems.disks.r2');
        $result['r2_disk']['exists'] = Storage::disk('r2')->exists($path);
    } catch (\Throwable $e) {
        $result['r2_disk']['error'] = $e->getMessage();
    }

    return response()->json($result);
});

// Debug: Inspect a product's image resolution details by id or name
Route::get('/debug/product-image', function (Request $request) {
    $query = \App\Models\Product::with([
        'productImages' => function ($q) {
            $q->orderByDesc('is_primary')->orderBy('id');
        }
    ]);
    if ($request->filled('id')) {
        $query->where('id', $request->id);
    } elseif ($request->filled('name')) {
        $query->where('name', 'LIKE', '%' . $request->name . '%');
    } else {
        return response()->json(['error' => 'Provide id or name query param'], 400);
    }

    $product = $query->first();
    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }

    $details = [
        'id' => $product->id,
        'name' => $product->name,
        'legacy_image_field' => $product->image,
        'computed_image_url' => $product->image_url,
        'has_image_data' => (bool) ($product->image_data && $product->image_mime_type),
        'product_images' => $product->productImages->map(function ($img) {
            return [
                'path' => $img->image_path,
                'is_primary' => (bool) $img->is_primary,
                'computed_url' => $img->image_url,
            ];
        }),
        'exists_in_public_disk' => false,
        'public_disk_path_tested' => null,
        'r2_candidate_url' => null,
    ];

    // Check file existence on public disk for legacy path
    if ($product->image && !str_starts_with($product->image, 'http')) {
        $imagePath = ltrim($product->image, '/');
        $details['public_disk_path_tested'] = $imagePath;
        try {
            $details['exists_in_public_disk'] = Storage::disk('public')->exists($imagePath);
        } catch (\Throwable $e) {
            $details['exists_in_public_disk'] = false;
        }
        $r2Base = config('filesystems.disks.r2.url');
        if (!empty($r2Base)) {
            $details['r2_candidate_url'] = rtrim($r2Base, '/') . '/' . $imagePath;
        }
    }

    return response()->json($details);
});

// Public test route for simple upload (no auth required)
Route::get('/test-simple-upload', function () {
    try {
        // Add deployment verification to existing route
        $deploymentInfo = [
            'serve_route_exists' => false,
            'product_count_with_seller' => 0,
            'sample_image_url' => '',
            'routes_found' => []
        ];

        // Check for serve-image route
        $router = app('router');
        $routes = $router->getRoutes();

        foreach ($routes->getRoutes() as $route) {
            if (str_contains($route->uri(), 'serve-image')) {
                $deploymentInfo['serve_route_exists'] = true;
                $deploymentInfo['routes_found'][] = $route->uri();
            }
        }

        // Check product filtering
        $deploymentInfo['product_count_with_seller'] = \App\Models\Product::whereNotNull('seller_id')->count();

        // Get sample image URL
        $sampleProduct = \App\Models\Product::whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->first();

        if ($sampleProduct) {
            $deploymentInfo['sample_image_url'] = $sampleProduct->image_url;
        }

        return response()->json([
            'status' => 'Simple upload system working',
            'routes_available' => [
                'simple_upload_form' => url('/seller/simple-upload'),
                'login_first' => url('/login'),
                'dashboard' => url('/seller/dashboard')
            ],
            'note' => 'You need to login first to access seller routes',
            'deployment_verification' => $deploymentInfo
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Deployment verification route
Route::get('/test-deployment', function () {
    $response = [
        'status' => 'deployment-test',
        'timestamp' => now()->toDateTimeString(),
        'routes' => [],
        'serve_route_exists' => false,
        'sample_image_url' => '',
        'product_count' => 0,
        'git_commit' => '02681ff' // Latest commit
    ];

    try {
        // Check if routes are loaded
        $router = app('router');
        $routes = $router->getRoutes();

        foreach ($routes->getRoutes() as $route) {
            if (str_contains($route->uri(), 'serve-image')) {
                $response['serve_route_exists'] = true;
                $response['routes'][] = $route->uri();
            }
        }

        // Get product count with seller filter
        $response['product_count'] = \App\Models\Product::whereNotNull('seller_id')->count();

        // Get sample image URL
        $sampleProduct = \App\Models\Product::whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->first();

        if ($sampleProduct) {
            $response['sample_image_url'] = $sampleProduct->image_url;
            $response['sample_product_name'] = $sampleProduct->name;
        }

        $response['status'] = 'success';
    } catch (\Exception $e) {
        $response['status'] = 'error';
        $response['error'] = $e->getMessage();
    }

    return response()->json($response, 200, [], JSON_PRETTY_PRINT);
});

// Test route for image display verification
Route::get('/test-images', function () {
    return view('test-images');
})->name('test.images');

/*
|--------------------------------------------------------------------------
| Delivery Partner Routes
|--------------------------------------------------------------------------
*/

// Delivery Partner Authentication Routes (Guest only)
Route::prefix('delivery-partner')->name('delivery-partner.')->middleware('guest:delivery_partner')->group(function () {
    // Registration
    Route::get('/register', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'showRegisterForm'])
        ->name('register');
    Route::post('/register', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'register'])
        ->name('register.post');

    // Quick Registration - OPTIMIZED FOR SPEED
    Route::get('/quick-register', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'showQuickRegisterForm'])
        ->name('quick-register');
    Route::post('/quick-register', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'quickRegister'])
        ->name('quick-register.post');

    // AJAX validation routes
    Route::post('/check-phone', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'checkPhone'])
        ->name('check-phone');
    Route::post('/check-email', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'checkEmail'])
        ->name('check-email');

    // Login - SUPER FAST VERSION (No caching overhead)
    Route::get('/login', [App\Http\Controllers\DeliveryPartner\SuperFastAuthController::class, 'showLoginForm'])
        ->name('login');
    Route::post('/login', [App\Http\Controllers\DeliveryPartner\SuperFastAuthController::class, 'login'])
        ->name('login.post');

    // Diagnostics page for debugging login issues
    Route::get('/login-diagnostics', function () {
        return view('delivery-partner.auth.diagnostics');
    })->name('login.diagnostics');
});

// Delivery Partner Protected Routes
Route::prefix('delivery-partner')->name('delivery-partner.')->middleware(['auth:delivery_partner', 'delivery.partner.status'])->group(function () {
    // Logout
    Route::post('/logout', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'logout'])
        ->name('logout');

    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\DeliveryPartner\DashboardController::class, 'index'])
        ->name('dashboard');

    // AJAX Refresh
    Route::get('/dashboard/orders/refresh', [App\Http\Controllers\DeliveryPartner\DashboardController::class, 'refreshAvailableOrders'])
        ->name('orders.refresh');

    // Profile Management
    Route::get('/profile', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'profile'])
        ->name('profile');
    Route::post('/profile', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'updateProfile'])
        ->name('profile.update');
    Route::post('/change-password', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'changePassword'])
        ->name('change-password');

    // Status Management
    Route::post('/toggle-online', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'toggleOnlineStatus'])
        ->name('toggle-online');
    Route::post('/toggle-availability', [App\Http\Controllers\DeliveryPartner\AuthController::class, 'toggleAvailability'])
        ->name('toggle-availability');
    // Location update route (moved to DeliveryRequestController for better organization)
    Route::post('/update-location', [App\Http\Controllers\DeliveryRequestController::class, 'updateLocation'])
        ->name('update-location');

    // Delivery Requests Management - NEW WALLET SYSTEM WITH ₹25 REWARDS
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [App\Http\Controllers\DeliveryRequestController::class, 'index'])
            ->name('index');
        Route::get('/{deliveryRequest}', [App\Http\Controllers\DeliveryRequestController::class, 'show'])
            ->name('show');
        Route::post('/{deliveryRequest}/accept', [App\Http\Controllers\DeliveryRequestController::class, 'accept'])
            ->name('accept');
        Route::post('/{deliveryRequest}/pickup', [App\Http\Controllers\DeliveryRequestController::class, 'pickup'])
            ->name('pickup');
        Route::post('/{deliveryRequest}/complete', [App\Http\Controllers\DeliveryRequestController::class, 'complete'])
            ->name('complete');
        Route::post('/{deliveryRequest}/cancel', [App\Http\Controllers\DeliveryRequestController::class, 'cancel'])
            ->name('cancel');
    });

    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'index'])
            ->name('index');
        Route::get('/available', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'available'])
            ->name('available');
        Route::get('/{order}', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'show'])
            ->name('show');
        Route::post('/{order}/accept', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'accept'])
            ->name('accept');
        Route::post('/{order}/pickup', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'pickup'])
            ->name('pickup');
        Route::post('/{order}/deliver', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'deliver'])
            ->name('deliver');
        Route::post('/{order}/cancel', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'cancel'])
            ->name('cancel');
        Route::post('/{order}/update-status', [App\Http\Controllers\DeliveryPartner\OrderController::class, 'updateStatus'])
            ->name('update-status');
    });

    // Earnings and Reports
    Route::prefix('earnings')->name('earnings.')->group(function () {
        Route::get('/', [App\Http\Controllers\DeliveryPartner\EarningsController::class, 'index'])
            ->name('index');
        Route::get('/weekly', [App\Http\Controllers\DeliveryPartner\EarningsController::class, 'weekly'])
            ->name('weekly');
        Route::get('/monthly', [App\Http\Controllers\DeliveryPartner\EarningsController::class, 'monthly'])
            ->name('monthly');
        Route::post('/withdraw', [App\Http\Controllers\DeliveryPartner\EarningsController::class, 'withdraw'])
            ->name('withdraw');
    });

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\DeliveryPartner\NotificationController::class, 'index'])
        ->name('notifications');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\DeliveryPartner\NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    // Support and Help
    Route::get('/support', [App\Http\Controllers\DeliveryPartner\SupportController::class, 'index'])
        ->name('support');
    Route::post('/support', [App\Http\Controllers\DeliveryPartner\SupportController::class, 'submit'])
        ->name('support.submit');
});

// ============================================
// HOTEL OWNER ROUTES (Food Delivery System)
// ============================================

// Hotel Owner Authentication Routes
Route::prefix('hotel-owner')->name('hotel-owner.')->group(function () {
    // Guest routes (not authenticated)
    Route::middleware('guest:hotel_owner')->group(function () {
        Route::get('/login', [App\Http\Controllers\HotelOwner\AuthController::class, 'showLoginForm'])
            ->name('login');
        Route::post('/login', [App\Http\Controllers\HotelOwner\AuthController::class, 'login']);

        Route::get('/register', [App\Http\Controllers\HotelOwner\AuthController::class, 'showRegistrationForm'])
            ->name('register');
        Route::post('/register', [App\Http\Controllers\HotelOwner\AuthController::class, 'register']);
    });

    // Authenticated routes
    Route::middleware('auth:hotel_owner')->group(function () {
        Route::post('/logout', [App\Http\Controllers\HotelOwner\AuthController::class, 'logout'])
            ->name('logout');

        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\HotelOwner\DashboardController::class, 'index'])
            ->name('dashboard');

        // Profile Management
        Route::get('/profile', [App\Http\Controllers\HotelOwner\DashboardController::class, 'profile'])
            ->name('profile');
        Route::put('/profile', [App\Http\Controllers\HotelOwner\DashboardController::class, 'updateProfile'])
            ->name('profile.update');

        // Food Items Management
        Route::resource('food-items', App\Http\Controllers\HotelOwner\FoodItemController::class);

        // Earnings and Reports
        Route::prefix('earnings')->name('earnings.')->group(function () {
            Route::get('/', [App\Http\Controllers\HotelOwner\EarningsController::class, 'index'])
                ->name('index');
            Route::get('/weekly', [App\Http\Controllers\HotelOwner\EarningsController::class, 'weekly'])
                ->name('weekly');
            Route::get('/monthly', [App\Http\Controllers\HotelOwner\EarningsController::class, 'monthly'])
                ->name('monthly');
            Route::get('/fetch', [App\Http\Controllers\HotelOwner\EarningsController::class, 'fetchEarnings'])
                ->name('fetch');

            Route::post('/withdraw', [App\Http\Controllers\HotelOwner\EarningsController::class, 'withdraw'])
                ->name('withdraw');
        });

        // Wallet (withdrawals)
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [App\Http\Controllers\HotelOwner\WalletController::class, 'index'])
                ->name('index');
            Route::post('/withdraw', [App\Http\Controllers\HotelOwner\WalletController::class, 'withdraw'])
                ->name('withdraw');
        });

        // Orders Management
        Route::get('/orders', [App\Http\Controllers\HotelOwner\OrderController::class, 'index'])
            ->name('orders.index');
        Route::get('/orders/{order}', [App\Http\Controllers\HotelOwner\OrderController::class, 'show'])
            ->name('orders.show');
        Route::put('/orders/{order}/status', [App\Http\Controllers\HotelOwner\OrderController::class, 'updateStatus'])
            ->name('orders.update-status');

        // Analytics and Reports - Commented out until AnalyticsController is created  
        // Route::get('/analytics', [App\Http\Controllers\HotelOwner\AnalyticsController::class, 'index'])
        //     ->name('analytics');
    });
});

// Food Delivery Routes (Customer-facing)
Route::prefix('food')->name('food.')->group(function () {
    Route::get('/', [App\Http\Controllers\Food\FoodController::class, 'index'])->name('index');
    Route::get('/restaurants', [App\Http\Controllers\Food\FoodController::class, 'restaurants'])->name('restaurants');
    Route::get('/restaurant/{hotelOwner}', [App\Http\Controllers\Food\FoodController::class, 'restaurant'])->name('restaurant');
    Route::get('/category/{category}', [App\Http\Controllers\Food\FoodController::class, 'category'])->name('category');
    Route::post('/add-to-cart', [App\Http\Controllers\Food\FoodController::class, 'addToCart'])->name('add-to-cart');
});

// Debug password reset route
Route::get('/debug-password-reset', function () {
    try {
        echo '<h2>Password Reset Debug</h2>';

        // Test 1: Get a user with email
        $user = App\Models\User::whereNotNull('email')->first();
        if (!$user) {
            echo '<p>❌ No users with email found</p>';
            return;
        }

        echo "<p>✓ Testing with user: {$user->email} (ID: {$user->id})</p>";

        // Test 2: Check mail configuration
        echo '<h3>Mail Configuration:</h3>';
        echo '<ul>';
        echo '<li>Driver: ' . config('mail.default') . '</li>';
        echo '<li>Host: ' . config('mail.mailers.smtp.host') . '</li>';
        echo '<li>Port: ' . config('mail.mailers.smtp.port') . '</li>';
        echo '<li>Username: ' . config('mail.mailers.smtp.username') . '</li>';
        echo '<li>From: ' . config('mail.from.address') . '</li>';
        echo '<li>Queue: ' . config('queue.default') . '</li>';
        echo '</ul>';

        // Test 3: Send password reset
        echo '<h3>Password Reset Test:</h3>';

        $status = Password::sendResetLink(['email' => $user->email]);

        echo "<p>Reset status: <strong>{$status}</strong></p>";

        if ($status == Password::RESET_LINK_SENT) {
            echo '<p style="color: green;">✓ Password reset link sent successfully!</p>';
        } else {
            echo '<p style="color: red;">❌ Password reset failed</p>';
            echo "<p>Possible reasons:</p>";
            echo "<ul>";
            echo "<li>Email server configuration issue</li>";
            echo "<li>User not found in password_resets table structure</li>";
            echo "<li>Mail template missing</li>";
            echo "<li>SMTP authentication failure</li>";
            echo "</ul>";
        }

        // Test 4: Try sending a basic test email
        echo '<h3>Basic Email Test:</h3>';
        try {
            Mail::raw('Test email from ' . config('app.name'), function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Test Email - ' . date('Y-m-d H:i:s'))
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            echo '<p style="color: green;">✓ Test email sent successfully!</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">❌ Test email failed: ' . $e->getMessage() . '</p>';
        }

        // Test 5: Check password_resets table
        echo '<h3>Password Resets Table:</h3>';
        try {
            $resets = DB::table('password_resets')->where('email', $user->email)->latest()->first();
            if ($resets) {
                echo '<p>✓ Password reset entry created in database</p>';
                echo '<p>Token created at: ' . $resets->created_at . '</p>';
            } else {
                echo '<p>❌ No password reset entry found in database</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">❌ Error checking password_resets table: ' . $e->getMessage() . '</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red;">❌ Debug error: ' . $e->getMessage() . '</p>';
        echo '<p>File: ' . $e->getFile() . ' Line: ' . $e->getLine() . '</p>';
    }
});

// Simple password reset test route
Route::get('/test-password-reset-simple', function () {
    try {
        echo "<h2>Password Reset Test</h2>";

        // Find a user
        $user = App\Models\User::whereNotNull('email')->first();
        if (!$user) {
            return "No user with email found.";
        }

        echo "<p>Testing with: {$user->email}</p>";

        // Test direct password reset
        $status = Password::sendResetLink(['email' => $user->email]);

        echo "<p>Status: <strong>{$status}</strong></p>";

        if ($status === Password::RESET_LINK_SENT) {
            echo '<p style="color: green;">✓ SUCCESS: Reset link sent!</p>';
        } else {
            echo '<p style="color: red;">✗ FAILED: ' . $status . '</p>';

            // Additional debugging
            echo "<h3>Debugging Info:</h3>";
            echo "<ul>";
            echo "<li>Mail Driver: " . config('mail.default') . "</li>";
            echo "<li>SMTP Host: " . config('mail.mailers.smtp.host') . "</li>";
            echo "<li>From Address: " . config('mail.from.address') . "</li>";
            echo "<li>Queue Driver: " . config('queue.default') . "</li>";
            echo "</ul>";

            // Check if token was created in database
            $token = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->latest('created_at')
                ->first();

            if ($token) {
                echo "<p>✓ Token created in database at: {$token->created_at}</p>";
            } else {
                echo "<p>✗ No token found in database</p>";
            }
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    }
});
Route::post('/internship/payment', [InternshipController::class, 'payment']);
Route::post('/internship/payment/success', [InternshipController::class, 'paymentSuccess']);

Route::get('/internship', [InternshipController::class, 'index']);
Route::get('/job/apply', [InternshipController::class, 'job']);
Route::get('/internship/apply', [InternshipController::class, 'form']);
Route::get('/pay', [PaymentController::class, 'showButton']);
Route::post('/create-order', [PaymentController::class, 'createOrder1'])->name('create.order');
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
// Legal and Information Pages
Route::get('/terms', function () {
    return view('terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/about', function () {
    return view('about');
})->name('about');

// SEO Routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/ten-min-products', [SellerController::class, 'showTenMinProducts'])->name('ten.min.products');


Route::get('/product/{id}', [SellerController::class, 'show'])->name('tenmin.product.details'); // ✅ Renamed!
Route::get('/api/product/{id}', [SellerController::class, 'getProductDetails']);
// In routes/web.php

// Add to cart
Route::post('/ten-min/cart/add', [SellerController::class, 'tenMinCartAdd'])->name('tenmin.cart.add');

// View cart
Route::get('/ten-min/cart', [SellerController::class, 'tenMinCartView'])
    ->name('tenmin.cart.view');
// Update cart item
Route::post('/ten-min/cart/update', [SellerController::class, 'tenMinCartUpdate'])->name('tenmin.cart.update');
//TEN MINS ORDER ROUTE
Route::prefix('seller')
    ->name('seller.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/10-mins-orders', [TenMinsOrderController::class, 'index'])->name('tenmins.orders');
        Route::get('/10-mins-orders/{id}', [TenMinsOrderController::class, 'show'])->name('tenmins.orders.show');
        Route::post('/10-mins-orders/{id}/update-status', [TenMinsOrderController::class, 'updateStatus'])->name('tenmins.orders.update');
    });
// Inside your seller group
Route::post('/10-mins-orders/{id}/update-status', [TenMinsOrderController::class, 'updateStatus'])
    ->name('tenmins.orders.update');


// REMOVE THIS LINE → Route::post('/ten-min/cart/remove', [SellerController::class, 'tenMinCartRemove']);
// ADD THIS INSTEAD →
Route::post('/ten-min/cart/remove', [SellerController::class, 'tenMinCartRemove'])->name('tenmin.cart.remove');


// ... other routes ...

// In routes/web.php
Route::get('/ten-min-checkout', [SellerController::class, 'tenMinCheckout'])
    ->name('tenmin.checkout')
    ->middleware('auth');

Route::get('/ten-min-order/success/{orderId}', [SellerController::class, 'tenMinOrderSuccess'])
    ->name('tenmin.order.success');

// ✅ FIXED: Complete, valid route
Route::post('/ten-min/order/place', [SellerController::class, 'placeTenMinGroceryOrder'])
    ->name('tenmin.grocery.order.place')
    ->middleware('auth');

Route::post('/ten-min/payment/verify', [SellerController::class, 'verifyTenMinPayment'])
    ->name('tenmin.payment.verify')
    ->middleware('auth');


use App\Http\Controllers\Customer\CustomerFoodController;

Route::prefix('food')->group(function () {
    // Public routes
    Route::get('/customer', [CustomerFoodController::class, 'index'])->name('customer.food.index');



    Route::get('/customer/details/{id}', [CustomerFoodController::class, 'details'])
        ->name('customer.food.details');
    // Protected cart routes
    Route::middleware('auth')->group(function () {
        Route::get('/cart', [CustomerFoodController::class, 'cartIndex'])->name('customer.food.cart');
        Route::post('/cart/add', [CustomerFoodController::class, 'cartAdd'])->name('customer.food.cart.add'); // <-- ADD THIS
        Route::post('/cart/update/{foodId}', [CustomerFoodController::class, 'cartUpdate'])
            ->name('customer.food.cart.update');
        Route::get('/cart/remove/{foodId}', [CustomerFoodController::class, 'cartRemove'])->name('customer.food.cart.remove');
        Route::get('/checkout', [CustomerFoodController::class, 'showCheckout'])->name('customer.food.checkout');
        Route::post('/checkout/place', [CustomerFoodController::class, 'placeOrder'])->name('customer.food.checkout.place');
        Route::post('/payment/verify', [CustomerFoodController::class, 'verifyPayment'])->name('customer.food.payment.verify');
        // Route::get('/order/success/{orderId}', [CustomerFoodController::class, 'orderSuccess'])->name('customer.food.order.success');
        Route::get('/order/success', [CustomerFoodController::class, 'orderSuccess'])
            ->name('customer.food.order.success');

        Route::get('/my-orders', [CustomerFoodController::class, 'myOrders'])
            ->name('food.my-orders');
        Route::get('/my-orders/{order}', [CustomerFoodController::class, 'orderDetails'])
            ->name('food.order.details');

    });
});

Route::get('/location',[CartController::class,'location']);
Route::get('/tenmins', [SellerController::class, 'tenmins']);
Route::get('/joinus', [SellerController::class, 'joinus']);
Route::get('/internship/details', [InternshipController::class, 'details'])
    ->name('internship.details');
