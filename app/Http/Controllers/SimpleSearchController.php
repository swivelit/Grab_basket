<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Category;

class SimpleSearchController extends Controller
{
    /**
     * Zepto/Blinkit style instant search with autocomplete
     */
    public function search(Request $request)
    {
        try {
            $searchQuery = trim($request->input('q', ''));
            $matchedStores = collect();
            
            // Basic product query with image filtering
            $query = Product::whereNotNull('image')
                ->where('image', '!=', '');

            // Apply search if provided
            if (!empty($searchQuery) && strlen($searchQuery) >= 2) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('description', 'LIKE', "%{$searchQuery}%");
                });
            }

            // Apply basic filters
            if ($request->filled('price_min')) {
                $query->where('price', '>=', (float)$request->input('price_min'));
            }
            if ($request->filled('price_max')) {
                $query->where('price', '<=', (float)$request->input('price_max'));
            }
            if ($request->filled('discount_min')) {
                $query->where('discount', '>=', (float)$request->input('discount_min'));
            }

            // Apply sorting
            $sort = $request->input('sort', 'newest');
            switch ($sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'discount':
                    $query->orderBy('discount', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }

            // Support filtering by category/subcategory (for Zepto/Blinkit style UX)
            $categoryId = $request->input('category_id');
            $subcategoryId = $request->input('subcategory_id');

            if ($subcategoryId) {
                $query->where('subcategory_id', $subcategoryId);
            } elseif ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            // Get paginated results
            $perPage = (int) $request->input('per_page', 24);
            $products = $query->paginate($perPage)->appends($request->query());

            // Prepare response data
            $totalResults = $products->total();
            $filters = $request->only(['price_min', 'price_max', 'discount_min', 'sort']);
            $isAuthenticated = Auth::check();

            // Load categories for sidebar (used by both desktop and mobile)
            $categories = Category::with('subcategories')->orderBy('name')->get();

            return view('products.index', compact(
                'products', 'categories', 'searchQuery', 'totalResults', 'filters', 'isAuthenticated', 'sort'
            ));
            
        } catch (\Exception $e) {
            // Log error
            Log::error('Simple Search Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return empty result
            $emptyProducts = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                24,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Search Error - GrabBaskets</title>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
            </head>
            <body>
                <div class='container mt-4'>
                    <div class='alert alert-warning'>
                        <h4>⚠️ Search Temporarily Unavailable</h4>
                        <p>We're experiencing technical difficulties. Please try again later.</p>
                        <p><small>Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
                    </div>
                </div>
            </body>
            </html>";
            
            return response($html, 503);
        }
    }
    
    /**
     * Instant search API for real-time suggestions (Zepto/Blinkit style)
     */
    public function instantSearch(Request $request)
    {
        try {
            $query = trim($request->input('q', ''));
            
            if (strlen($query) < 2) {
                return response()->json([
                    'suggestions' => [],
                    'products' => [],
                    'categories' => []
                ]);
            }
            
            // Cache key for suggestions
            $cacheKey = 'instant_search_' . md5($query);
            
            return Cache::remember($cacheKey, 300, function() use ($query) { // 5 minute cache
                
                // Get top matching products (limit to 6 for instant display)
                $products = Product::whereNotNull('image')
                    ->where('image', '!=', '')
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get(['id', 'name', 'price', 'discount', 'image', 'stock_quantity'])
                    ->map(function($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->price,
                            'discount' => $product->discount,
                            'image' => $product->image_url ?? $product->image ?? '/images/placeholder.png',
                            'in_stock' => $product->stock_quantity === null || $product->stock_quantity > 0,
                            'url' => "/product/{$product->id}"
                        ];
                    });
                
                // Get matching categories
                $categories = Category::where('name', 'LIKE', "%{$query}%")
                    ->limit(4)
                    ->get(['id', 'name', 'emoji'])
                    ->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'emoji' => $category->emoji,
                            'url' => "/products?category_id={$category->id}"
                        ];
                    });
                
                // Popular search suggestions
                $suggestions = [
                    $query . ' products',
                    $query . ' deals',
                    $query . ' offers'
                ];
                
                return [
                    'products' => $products,
                    'categories' => $categories,
                    'suggestions' => $suggestions,
                    'query' => $query
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Instant Search Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'products' => [],
                'categories' => [],
                'suggestions' => [],
                'error' => 'Search temporarily unavailable'
            ], 500);
        }
    }
    
    /**
     * Auto-complete suggestions API
     */
    public function suggestions(Request $request)
    {
        try {
            $query = trim($request->input('q', ''));
            
            if (strlen($query) < 2) {
                return response()->json(['suggestions' => []]);
            }
            
            $cacheKey = 'suggestions_' . md5($query);
            
            $suggestions = Cache::remember($cacheKey, 3600, function() use ($query) { // 1 hour cache
                
                // Get product name suggestions
                $productSuggestions = Product::where('name', 'LIKE', "%{$query}%")
                    ->limit(8)
                    ->pluck('name')
                    ->map(function($name) {
                        return ['text' => $name, 'type' => 'product'];
                    });
                
                // Get category suggestions
                $categorySuggestions = Category::where('name', 'LIKE', "%{$query}%")
                    ->limit(4)
                    ->get(['name', 'emoji'])
                    ->map(function($category) {
                        return [
                            'text' => $category->name,
                            'type' => 'category',
                            'emoji' => $category->emoji
                        ];
                    });
                
                return $productSuggestions->merge($categorySuggestions)->take(10);
            });
            
            return response()->json(['suggestions' => $suggestions]);
            
        } catch (\Exception $e) {
            Log::error('Suggestions Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['suggestions' => []], 500);
        }
    }

    /**
     * Food delivery products - filtered by food categories
     */
    public function foodDelivery(Request $request)
    {
        try {
            // Get food-related categories
            $foodCategories = Category::where(function($query) {
                $query->where('name', 'LIKE', '%food%')
                      ->orWhere('name', 'LIKE', '%restaurant%')
                      ->orWhere('name', 'LIKE', '%meal%')
                      ->orWhere('name', 'LIKE', '%snack%')
                      ->orWhere('name', 'LIKE', '%beverage%')
                      ->orWhere('name', 'LIKE', '%drink%')
                      ->orWhere('name', 'LIKE', '%grocery%')
                      ->orWhere('name', 'LIKE', '%kitchen%');
            })->pluck('id');

            // Query food products
            $query = Product::whereNotNull('image')
                ->where('image', '!=', '');

            // Filter by food categories if found
            if ($foodCategories->isNotEmpty()) {
                $query->whereIn('category_id', $foodCategories);
            }

            // Search functionality
            $searchQuery = trim($request->input('q', ''));
            if (!empty($searchQuery) && strlen($searchQuery) >= 2) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('description', 'LIKE', "%{$searchQuery}%");
                });
            }

            // Apply filters
            if ($request->filled('price_min')) {
                $query->where('price', '>=', (float)$request->input('price_min'));
            }
            if ($request->filled('price_max')) {
                $query->where('price', '<=', (float)$request->input('price_max'));
            }

            // Sorting
            $sort = $request->input('sort', 'newest');
            switch ($sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'discount':
                    $query->orderBy('discount', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $products = $query->paginate($perPage);

            // Get all categories for sidebar
            $categories = Category::with('subcategories')->get();

            // Prepare data similar to main search method
            $totalResults = $products->total();
            $filters = $request->only(['price_min', 'price_max', 'discount_min', 'sort']);
            $isAuthenticated = Auth::check();

            return view('products.index', compact(
                'products', 'categories', 'searchQuery', 'totalResults', 'filters', 'isAuthenticated', 'sort'
            ));

        } catch (\Exception $e) {
            Log::error('Food Delivery Search Error', [
                'error' => $e->getMessage(),
                'query' => $request->input('q'),
            ]);

            return back()->with('error', 'Search temporarily unavailable. Please try again.');
        }
    }
}