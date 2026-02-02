<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Get paginated products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'productImages'])
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%');

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by availability
        if ($request->has('in_stock') && $request->in_stock == '1') {
            $query->where('stock_quantity', '>', 0);
        }

        // Sort products
        $sortBy = $request->get('sort_by', 'newest');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'popularity':
                $query->orderByDesc('view_count');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Get product details
     */
    public function show($id)
    {
        $product = Product::with(['category', 'seller', 'productImages', 'reviews.user'])
            ->where('id', $id)
            ->whereNotNull('seller_id')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Increment view count
        $product->increment('view_count');

        // Get related products
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'product' => $product,
            'related_products' => $relatedProducts,
        ]);
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 422);
        }

        $products = Product::with(['category', 'productImages'])
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                  ->orWhere('description', 'LIKE', '%' . $query . '%')
                  ->orWhereHas('category', function ($categoryQuery) use ($query) {
                      $categoryQuery->where('name', 'LIKE', '%' . $query . '%');
                  });
            })
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'query' => $query,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Cache::remember('featured_products', 3600, function () {
            return Product::with(['category', 'productImages'])
                ->whereNotNull('seller_id')
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('is_featured', true)
                ->inRandomOrder()
                ->limit(12)
                ->get();
        });

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    /**
     * Get trending products
     */
    public function trending()
    {
        $products = Cache::remember('trending_products', 1800, function () {
            return Product::with(['category', 'productImages'])
                ->whereNotNull('seller_id')
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->orderByDesc('view_count')
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();
        });

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }
}