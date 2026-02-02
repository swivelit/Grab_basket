<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Review;
use App\Models\Seller;
use App\Services\DeliveryZoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Get products filtered by delivery type and user's zone
     */
    public function getByDeliveryType(Request $request)
    {
        $deliveryType = $request->get('delivery_type', 'standard'); // 'standard' or 'express_10min'
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $products = DeliveryZoneService::getFilteredProducts($deliveryType, $perPage);

        return response()->json([
            'success' => true,
            'delivery_type' => $deliveryType,
            'products' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * Get nearby stores for 10-minute delivery
     */
    public function getNearbyStores(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to see nearby stores'
            ], 401);
        }

        $radiusKm = $request->get('radius', 5);
        $stores = DeliveryZoneService::getNearbyStores(Auth::user(), $radiusKm);

        return response()->json([
            'success' => true,
            'nearby_stores' => $stores,
            'user_location' => DeliveryZoneService::getUserDeliveryZone()
        ]);
    }

    /**
     * Check if product is deliverable in 10 minutes
     */
    public function check10MinDelivery(Request $request, $productId)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to check 10-minute delivery'
            ], 401);
        }

        $product = Product::findOrFail($productId);
        $isDeliverable = DeliveryZoneService::isDeliverableIn10Minutes($product);

        return response()->json([
            'success' => true,
            'product_id' => $productId,
            'is_10min_deliverable' => $isDeliverable,
            'delivery_charge' => $isDeliverable ? ($product->delivery_charge ?? 0) : 'N/A'
        ]);
    }

    public function show($id)
    {
        try {
            // Load product with relationships including seller (User)
            $product = Product::with(['category', 'subcategory', 'seller'])->findOrFail($id);
            
            // Get seller info from sellers table via email match
            $seller = null;
            if ($product->seller && $product->seller->email) {
                $seller = Seller::where('email', $product->seller->email)->first();
            }
            
            // If seller not found, set to null (view will handle the fallback message)
            if (!$seller) {
                $seller = null;
                Log::warning("Product {$id} has no valid seller info", [
                    'seller_id' => $product->seller_id,
                    'user_exists' => $product->seller ? 'yes' : 'no',
                    'user_email' => $product->seller ? $product->seller->email : 'N/A'
                ]);
            }
            
            $reviews = Review::where('product_id', $product->id)->with('user')->latest()->get();
            
            $otherProducts = Product::where('seller_id', $product->seller_id)
                ->where('id', '!=', $product->id)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%')
                ->latest()->take(8)->get();
            
            // Check if 10-min delivery is available for this product
            $is10MinAvailable = false;
            if (Auth::check()) {
                $is10MinAvailable = DeliveryZoneService::isDeliverableIn10Minutes($product);
            }
            
            return view('buyer.product-details', compact('product', 'seller', 'reviews', 'otherProducts', 'is10MinAvailable'));
            
        } catch (\Exception $e) {
            Log::error("Error loading product {$id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return user-friendly error page
            return response()->view('errors.500', [
                'message' => 'Unable to load product details. The product may not exist or there was a server error.'
            ], 500);
        }
    }

    public function addReview(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        Review::create([
            'product_id' => $id,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);
        return back()->with('success', 'Review added!');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('products.edit', compact('product'));
    }
}
