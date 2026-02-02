<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Seller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeliveryModeController extends Controller
{
    /**
     * 10-Minute Express Delivery Index (Zepto Style)
     * Shows only shops within 5km radius with limited categories
     */
    public function tenMinuteDelivery(Request $request)
    {
        try {
            // Get user location from session or request
            $userLat = session('user_lat') ?? $request->input('lat');
            $userLng = session('user_lng') ?? $request->input('lng');

            // Get stores within 5km radius
            $stores = $this->getNearbyStores($userLat, $userLng, 5);

            // Get store IDs for filtering
            $storeIds = $stores->pluck('id')->toArray();

            // Get categories available for 10-minute delivery (quick categories only)
            $categories = $this->getTenMinuteDeliveryCategories();

            // Get featured products from nearby stores within 2km range (priority products)
            $nearbyProducts = $this->getProductsByLocationRange($userLat, $userLng, $storeIds, 2);

            // Get trending/featured products from all nearby stores (5km)
            $products = Product::whereIn('seller_id', $storeIds)
                ->with(['category', 'seller'])
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(12)
                ->get();

            // Merge nearby products first for better relevance
            if ($nearbyProducts->count() > 0) {
                $products = $nearbyProducts->merge($products)->unique('id')->take(12);
            }

            // Get trending/featured products
            $trending = Product::whereIn('seller_id', $storeIds)
                ->with(['category', 'seller'])
                ->where('is_active', true)
                ->orderBy('sales', 'desc')
                ->limit(8)
                ->get();

            // Get banners
            $banners = Banner::where('is_active', true)
                ->where('position', 'hero')
                ->orderBy('display_order')
                ->get();

            $settings = [
                'delivery_mode' => '10-minute',
                'delivery_radius_km' => 5,
                'priority_distance_km' => 2,
                'nearby_stores_count' => count($storeIds),
                'hero_title' => '10-Minute Express Delivery',
                'hero_subtitle' => 'Get groceries in 10 minutes!',
                'show_categories' => true,
                'show_featured_products' => true,
                'show_trending' => true,
                'theme_color' => '#0C831F',
                'secondary_color' => '#F8CB46'
            ];

            return view('delivery.ten-minute-index', [
                'categories' => $categories,
                'products' => $products,
                'nearbyProducts' => $nearbyProducts,
                'trending' => $trending,
                'stores' => $stores,
                'banners' => $banners,
                'settings' => $settings,
                'user_lat' => $userLat,
                'user_lng' => $userLng,
            ]);

        } catch (\Exception $e) {
            Log::error('10-Minute delivery error: ' . $e->getMessage());

            if (config('app.debug')) {
                return response()->json([
                    'error' => '10-Minute delivery error',
                    'message' => $e->getMessage(),
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * Normal Delivery Index (Including Food)
     */
    public function normalDelivery(Request $request)
    {
        try {
            // Get all categories for normal delivery
            $categories = Category::with('subcategories')
                ->limit(20)
                ->get();

            // Get all products
            $products = Product::with(['category', 'seller'])
                ->where('is_active', true)
                ->limit(12)
                ->get();

            // Get trending products
            $trending = Product::with(['category', 'seller'])
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();

            // Get food category products separately
            $foodCategory = Category::where('slug', 'food')
                ->orWhere('name', 'like', '%food%')
                ->first();

            $foodProducts = $foodCategory
                ? Product::where('category_id', $foodCategory->id)
                    ->with(['category', 'seller'])
                    ->where('is_active', true)
                    ->limit(6)
                    ->get()
                : collect([]);

            // Get banners
            $banners = Banner::where('is_active', true)
                ->where('position', 'hero')
                ->orderBy('display_order')
                ->get();

            $settings = [
                'delivery_mode' => 'normal',
                'hero_title' => 'Shop Everything You Love',
                'hero_subtitle' => 'Groceries, Fresh Food & More',
                'show_categories' => true,
                'show_featured_products' => true,
                'show_trending' => true,
                'show_food_section' => true,
                'theme_color' => '#FF6B00',
                'secondary_color' => '#FFD700',
            ];

            return view('delivery.normal-index', [
                'categories' => $categories,
                'products' => $products,
                'trending' => $trending,
                'foodProducts' => $foodProducts,
                'banners' => $banners,
                'settings' => $settings,
            ]);

        } catch (\Exception $e) {
            Log::error('Normal delivery error: ' . $e->getMessage());

            if (config('app.debug')) {
                return response()->json([
                    'error' => 'Normal delivery error',
                    'message' => $e->getMessage(),
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * Get nearby stores within specified radius (in km)
     * Uses Haversine formula for distance calculation
     */
    private function getNearbyStores($userLat = null, $userLng = null, $radiusKm = 5)
    {
        if (!$userLat || !$userLng) {
            // Return all stores if location not available
            return Seller::where('available_for_10_min_delivery', true)
                ->limit(10)
                ->get();
        }

        try {
            // Haversine formula to calculate distance
            $stores = Seller::selectRaw(
                '*, 
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * 
                sin( radians( latitude ) ) ) ) AS distance',
                [$userLat, $userLng, $userLat]
            )
                ->where('available_for_10_min_delivery', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->havingRaw('distance < ?', [$radiusKm])
                ->orderBy('distance')
                ->limit(15)
                ->get();
        } catch (\Exception $e) {
            // Fallback if Haversine fails - return all available stores
            Log::warning('Haversine calculation failed: ' . $e->getMessage());
            $stores = Seller::where('available_for_10_min_delivery', true)
                ->limit(10)
                ->get();
        }

        return $stores;
    }

    /**
     * Get products from sellers within specified range (in km)
     * Fetches products from stores closest to user location
     */
    private function getProductsByLocationRange($userLat = null, $userLng = null, $storeIds = [], $radiusKm = 2)
    {
        if (!$userLat || !$userLng || empty($storeIds)) {
            return collect([]);
        }

        try {
            // Get stores within specified radius using Haversine formula
            $nearbyStores = Seller::selectRaw(
                '*, 
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * 
                sin( radians( latitude ) ) ) ) AS distance',
                [$userLat, $userLng, $userLat]
            )
                ->whereIn('id', $storeIds)
                ->where('available_for_10_min_delivery', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->havingRaw('distance < ?', [$radiusKm])
                ->orderBy('distance')
                ->get();

            $nearbyStoreIds = $nearbyStores->pluck('id')->toArray();

            if (empty($nearbyStoreIds)) {
                return collect([]);
            }

            // Get products from nearby stores with distance prioritization
            $products = Product::whereIn('seller_id', $nearbyStoreIds)
                ->with(['category', 'seller'])
                ->where('is_active', true)
                ->where('in_stock', true)
                ->orderBy('created_at', 'desc')
                ->limit(12)
                ->get();

            return $products;

        } catch (\Exception $e) {
            Log::warning('Get products by location range failed: ' . $e->getMessage());
            return collect([]);
        }
    }
    /**
     * These are quick pickup categories (not food)
     */
    private function getTenMinuteDeliveryCategories()
    {
        // Categories suitable for 10-minute delivery
        $tenMinuteCategories = [
            'Groceries',
            'Vegetables',
            'Fruits',
            'Dairy & Eggs',
            'Bread & Bakery',
            'Beverages',
            'Snacks',
            'Household Items',
            'Personal Care',
            'Health & Wellness'
        ];

        return Category::whereIn('name', $tenMinuteCategories)
            ->with('subcategories')
            ->get();
    }

    /**
     * API: Get products within location range (AJAX endpoint)
     */
    public function getLocationBasedProducts(Request $request)
    {
        try {
            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius_km' => 'nullable|numeric|min:1|max:10',
                'category_id' => 'nullable|integer',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);

            $userLat = $validated['latitude'];
            $userLng = $validated['longitude'];
            $radiusKm = $validated['radius_km'] ?? 2;
            $categoryId = $validated['category_id'] ?? null;
            $limit = $validated['limit'] ?? 12;

            // Get stores in radius
            $stores = $this->getNearbyStores($userLat, $userLng, 5);
            $storeIds = $stores->pluck('id')->toArray();

            // Get products by location range
            $query = Seller::selectRaw(
                'sellers.*, 
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * 
                sin( radians( latitude ) ) ) ) AS distance',
                [$userLat, $userLng, $userLat]
            )
                ->whereIn('sellers.id', $storeIds)
                ->where('available_for_10_min_delivery', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->havingRaw('distance < ?', [$radiusKm])
                ->orderBy('distance');

            $nearbyStores = $query->get();
            $nearbyStoreIds = $nearbyStores->pluck('id')->toArray();

            if (empty($nearbyStoreIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No stores found within ' . $radiusKm . 'km radius',
                ]);
            }

            // Get products from nearby stores
            $productsQuery = Product::whereIn('seller_id', $nearbyStoreIds)
                ->with(['category', 'seller'])
                ->where('is_active', true)
                ->where('in_stock', true);

            if ($categoryId) {
                $productsQuery->where('category_id', $categoryId);
            }

            $products = $productsQuery
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products->map(function ($product) use ($nearbyStores) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'image_url' => $product->image_url,
                        'category' => $product->category->name ?? 'N/A',
                        'seller' => $product->seller->shop_name ?? 'N/A',
                        'in_stock' => $product->in_stock,
                        'distance_km' => round($nearbyStores->firstWhere('id', $product->seller_id)?->distance ?? 0, 2),
                    ];
                }),
                'nearby_stores_count' => count($nearbyStoreIds),
                'radius_km' => $radiusKm,
            ]);

        } catch (\Exception $e) {
            Log::error('Get location-based products error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store user location in session
     */
    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string',
        ]);

        session([
            'user_lat' => $validated['latitude'],
            'user_lng' => $validated['longitude'],
            'user_address' => $validated['address'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get products by category for 10-minute delivery
     */
    public function getCategoryProducts(Request $request, $categoryId)
    {
        try {
            $userLat = session('user_lat');
            $userLng = session('user_lng');

            $stores = $this->getNearbyStores($userLat, $userLng, 5);
            $storeIds = $stores->pluck('id')->toArray();

            // Get products from nearby stores, prioritizing 2km range
            $nearbyProducts = $this->getProductsByLocationRange($userLat, $userLng, $storeIds, 2);
            $nearbyProductIds = $nearbyProducts->pluck('id')->toArray();

            // Get remaining products from all nearby stores
            $allProducts = Product::where('category_id', $categoryId)
                ->whereIn('seller_id', $storeIds)
                ->with(['category', 'seller'])
                ->where('is_active', true)
                ->paginate(12);

            // Reorganize to put nearby products first
            if (count($nearbyProductIds) > 0) {
                $sorted = $allProducts->getCollection()->sortByDesc(function ($product) use ($nearbyProductIds) {
                    return in_array($product->id, $nearbyProductIds) ? 1 : 0;
                });
                $allProducts->setCollection($sorted);
            }

            return view('delivery.category-products', [
                'products' => $allProducts,
                'nearbyProducts' => $nearbyProducts,
                'categoryId' => $categoryId,
                'delivery_mode' => '10-minute',
            ]);

        } catch (\Exception $e) {
            Log::error('Get category products error: ' . $e->getMessage());
            return response()->view('errors.500', [], 500);
        }
    }
}
