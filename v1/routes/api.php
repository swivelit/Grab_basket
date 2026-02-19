<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\UserController;
// use App\Http\Controllers\Api\DeliveryPartnerController; // TODO: Create this controller
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes for Mobile Apps
|--------------------------------------------------------------------------
| 
| Customer App: GrabBaskets
| Delivery Partner App: GrabBaskets Delivery Partner
|
*/

// Legacy routes (keep for backward compatibility)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Live Tracking API Routes (can be called without auth for testing)
Route::get('/order/{order}/track', [OrderController::class, 'apiTrackOrder'])->name('api.order.track');
Route::post('/order/{order}/update-location', [OrderController::class, 'apiUpdateLocation'])->name('api.order.updateLocation');

// Mobile App API Routes
// Public routes (no authentication required)
Route::prefix('v1')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [AuthController::class, 'sendOTP']);
        Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
        Route::post('refresh', [AuthController::class, 'refreshToken']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Public product data
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'getSubcategories']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/trending', [ProductController::class, 'trending']);
    Route::get('search', [ProductController::class, 'search']);

    // Delivery partner public routes - TODO: Create Api\DeliveryPartnerController
    // Route::prefix('delivery-partner')->group(function () {
    //     Route::post('register', [DeliveryPartnerController::class, 'register']);
    //     Route::post('login', [DeliveryPartnerController::class, 'login']);
    //     Route::post('send-otp', [DeliveryPartnerController::class, 'sendOTP']);
    //     Route::post('verify-otp', [DeliveryPartnerController::class, 'verifyOTP']);
    // });
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // User management
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('avatar', [UserController::class, 'updateAvatar']);
        Route::get('addresses', [UserController::class, 'getAddresses']);
        Route::post('addresses', [UserController::class, 'addAddress']);
        Route::put('addresses/{id}', [UserController::class, 'updateAddress']);
        Route::delete('addresses/{id}', [UserController::class, 'deleteAddress']);
    });

    // Cart management
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('add', [CartController::class, 'add']);
        Route::patch('{id}', [CartController::class, 'update']);
        Route::delete('{id}', [CartController::class, 'remove']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::patch('{id}/delivery-type', [CartController::class, 'switchDeliveryType']);
    });

    // Wishlist management
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('add', [WishlistController::class, 'add']);
        Route::post('remove', [WishlistController::class, 'remove']);
        Route::post('toggle', [WishlistController::class, 'toggle']);
        Route::post('move-to-cart', [WishlistController::class, 'moveToCart']);
    });

    // Order management - Temporarily disabled due to missing controller
    // Route::prefix('orders')->group(function () {
    //     Route::get('/', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    //     Route::post('/', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    //     Route::get('{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    //     Route::get('{id}/track', [\App\Http\Controllers\Api\OrderController::class, 'track']);
    //     Route::get('{id}/location', [\App\Http\Controllers\Api\OrderController::class, 'getLocation']);
    //     Route::post('{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    // });

    // Payment
    Route::prefix('payment')->group(function () {
        Route::post('create-order', [PaymentController::class, 'createOrder']);
        Route::post('verify', [PaymentController::class, 'verify']);
        Route::get('methods', [PaymentController::class, 'getMethods']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('device-token', [NotificationController::class, 'updateDeviceToken']);
    });
});

// Delivery Partner protected routes - TODO: Create Api\DeliveryPartnerController
// Route::prefix('v1/delivery')->middleware('auth:delivery_partner')->group(function () {
//     
//     // Authentication
//     Route::post('logout', [DeliveryPartnerController::class, 'logout']);
//     Route::get('me', [DeliveryPartnerController::class, 'me']);
//
//     // Profile management
//     Route::get('profile', [DeliveryPartnerController::class, 'profile']);
//     Route::put('profile', [DeliveryPartnerController::class, 'updateProfile']);
//     Route::post('documents', [DeliveryPartnerController::class, 'uploadDocuments']);
//
//     // Status management
//     Route::post('status/online', [DeliveryPartnerController::class, 'toggleOnlineStatus']);
//     Route::post('status/available', [DeliveryPartnerController::class, 'toggleAvailability']);
//     Route::post('location', [DeliveryPartnerController::class, 'updateLocation']);
//
//     // Order management
//     Route::prefix('orders')->group(function () {
//         Route::get('available', [DeliveryPartnerController::class, 'getAvailableOrders']);
//         Route::get('assigned', [DeliveryPartnerController::class, 'getAssignedOrders']);
//         Route::get('{id}', [DeliveryPartnerController::class, 'getOrderDetails']);
//         Route::post('{id}/accept', [DeliveryPartnerController::class, 'acceptOrder']);
//         Route::post('{id}/pickup', [DeliveryPartnerController::class, 'pickupOrder']);
//         Route::post('{id}/complete', [DeliveryPartnerController::class, 'completeDelivery']);
//         Route::post('{id}/cancel', [DeliveryPartnerController::class, 'cancelDelivery']);
//     });
//
//     // Earnings and wallet
//     Route::prefix('earnings')->group(function () {
//         Route::get('/', [DeliveryPartnerController::class, 'getEarnings']);
//         Route::get('daily', [DeliveryPartnerController::class, 'getDailyEarnings']);
//         Route::get('weekly', [DeliveryPartnerController::class, 'getWeeklyEarnings']);
//         Route::get('monthly', [DeliveryPartnerController::class, 'getMonthlyEarnings']);
//         Route::post('withdraw', [DeliveryPartnerController::class, 'requestWithdrawal']);
//     });
//
//     // Performance stats
//     Route::get('stats', [DeliveryPartnerController::class, 'getStats']);
//     Route::get('delivery-history', [DeliveryPartnerController::class, 'getDeliveryHistory']);
// });

// Webhook routes (for payment gateways, etc.)
Route::prefix('webhooks')->group(function () {
    Route::post('razorpay', [PaymentController::class, 'razorpayWebhook']);
    // Route::post('delivery-status', [\App\Http\Controllers\Api\OrderController::class, 'deliveryStatusWebhook']);
});
