<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizedBuyerController extends Controller
{
    /**
     * Ultra-fast search optimized for guest users
     * Uses database indexes, full-text search, and caching for maximum performance
     */
    public function guestSearch(Request $request)
    {
        try {
            $searchQuery = trim($request->input('q', ''));
            $sort = $request->input('sort', 'relevance');
            $page = (int) $request->input('page', 1);
            
            // Generate cache key for this search
            $cacheKey = 'guest_search_' . md5($searchQuery . $sort . $page . serialize($request->only(['price_min', 'price_max', 'discount_min', 'free_delivery'])));
            
            // Try to get cached results first (cache for 10 minutes)
            if ($request->filled('q') && strlen($searchQuery) >= 2) {
                $cachedResults = Cache::get($cacheKey);
                if ($cachedResults) {
                    return view('buyer.products', $cachedResults);
                }
            }
            
            $matchedStores = collect();
            
            // Start with base query using optimized image filtering
            // This uses the new composite index for faster performance
            $query = Product::select('products.*')
                ->with(['category:id,name', 'subcategory:id,name'])
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%');

            // Apply search filters if query provided
            if ($request->filled('q') && strlen($searchQuery) >= 2) {
                $search = strtolower($searchQuery);
                
                // Use full-text search for better performance instead of LIKE queries
                $query->where(function ($q) use ($search, $searchQuery) {
                    // Full-text search on name and description (uses new index)
                    $q->whereRaw('MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery])
                      // Fallback to optimized LIKE searches
                      ->orWhere('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('unique_id', 'LIKE', "%{$searchQuery}%")
                      // Category searches (uses existing indexes)
                      ->orWhereHas('category', function($query) use ($searchQuery) {
                          $query->where('name', 'LIKE', "%{$searchQuery}%");
                      })
                      ->orWhereHas('subcategory', function($query) use ($searchQuery) {
                          $query->where('name', 'LIKE', "%{$searchQuery}%");
                      });
                });
                
                // Optimized seller search - get seller IDs first, then filter products
                $sellerUserIds = $this->getSellerUserIds($searchQuery);
                if ($sellerUserIds->isNotEmpty()) {
                    $query->orWhereIn('seller_id', $sellerUserIds);
                }
                
                // Get matching stores for display (optimized query)
                $matchedStores = $this->getMatchingStores($searchQuery);
            }

            // Apply additional filters
            $this->applyFilters($query, $request);

            // Apply sorting with optimized queries
            $this->applySorting($query, $sort, $searchQuery);

            // Execute paginated query
            $products = $query->paginate(24)->appends($request->query());
            
            // Get search statistics
            $totalResults = $products->total();
            
            // Prepare response data
            $responseData = [
                'products' => $products,
                'searchQuery' => $searchQuery,
                'totalResults' => $totalResults,
                'matchedStores' => $matchedStores,
                'filters' => $request->only(['price_min', 'price_max', 'discount_min', 'free_delivery', 'sort']),
                'isOptimized' => true,
                'executionTime' => null
            ];
            
            // Cache successful searches for 10 minutes
            if ($request->filled('q') && strlen($searchQuery) >= 2 && $totalResults > 0) {
                Cache::put($cacheKey, $responseData, 600); // 10 minutes
            }
            
            // Log search for analytics (non-blocking)
            if ($request->filled('q')) {
                $this->logSearch($searchQuery, $totalResults, $request);
            }

            return view('buyer.products', $responseData);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Optimized Guest Search Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty result with error handling
            return $this->getEmptySearchResult($request);
        }
    }
    
    /**
     * Get seller user IDs efficiently using optimized query
     */
    private function getSellerUserIds($searchQuery)
    {
        return DB::table('sellers')
            ->join('users', 'sellers.email', '=', 'users.email')
            ->where(function($query) use ($searchQuery) {
                $query->where('sellers.name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('sellers.store_name', 'LIKE', "%{$searchQuery}%");
            })
            ->pluck('users.id');
    }
    
    /**
     * Get matching stores with product counts
     */
    private function getMatchingStores($searchQuery)
    {
        return DB::table('sellers')
            ->select('sellers.*', 'users.id as user_id', 
                DB::raw('(SELECT COUNT(*) FROM products WHERE products.seller_id = users.id AND products.image IS NOT NULL AND products.image != "" AND products.image NOT LIKE "%unsplash%" AND products.image NOT LIKE "%placeholder%") as product_count'))
            ->join('users', 'sellers.email', '=', 'users.email')
            ->where(function($query) use ($searchQuery) {
                $query->where('sellers.name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('sellers.store_name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('sellers.email', 'LIKE', "%{$searchQuery}%");
            })
            ->having('product_count', '>', 0)
            ->get();
    }
    
    /**
     * Apply filters to the query
     */
    private function applyFilters($query, $request)
    {
        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float)$request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float)$request->input('price_max'));
        }
        if ($request->filled('discount_min')) {
            $query->where('discount', '>=', (float)$request->input('discount_min'));
        }
        if ($request->boolean('free_delivery')) {
            $query->where(function($q) {
                $q->whereNull('delivery_charge')->orWhere('delivery_charge', 0);
            });
        }
    }
    
    /**
     * Apply sorting with performance optimization
     */
    private function applySorting($query, $sort, $searchQuery = '')
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('view_count', 'desc');
                break;
            case 'discount':
                $query->orderBy('discount', 'desc');
                break;
            default: // relevance
                if (!empty($searchQuery)) {
                    // Relevance scoring with full-text search boost
                    $query->orderByRaw("
                        CASE 
                            WHEN MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE) THEN 1
                            WHEN name LIKE ? THEN 2
                            WHEN description LIKE ? THEN 3
                            ELSE 4
                        END, created_at DESC
                    ", [$searchQuery, "%{$searchQuery}%", "%{$searchQuery}%"]);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
        }
    }
    
    /**
     * Log search queries for analytics (non-blocking)
     */
    private function logSearch($searchQuery, $totalResults, $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Guest Search Query', [
                'query' => $searchQuery,
                'results' => $totalResults,
                'user_type' => 'guest',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            // Silently handle logging errors
        }
    }
    
    /**
     * Return empty search result with proper structure
     */
    private function getEmptySearchResult($request)
    {
        $emptyProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            collect([]),
            0,
            24,
            1,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('buyer.products', [
            'products' => $emptyProducts,
            'searchQuery' => $request->input('q', ''),
            'totalResults' => 0,
            'matchedStores' => collect([]),
            'filters' => [],
            'error' => 'Search temporarily unavailable. Please try again.',
            'isOptimized' => true
        ]);
    }
    
    /**
     * Get popular search suggestions for autocomplete
     */
    public function getSearchSuggestions(Request $request)
    {
        $query = trim($request->input('q', ''));
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        // Cache suggestions for 1 hour
        $suggestions = Cache::remember("search_suggestions_{$query}", 3600, function() use ($query) {
            $suggestions = [];
            
            // Get product name suggestions
            $productSuggestions = Product::select('name')
                ->where('name', 'LIKE', "{$query}%")
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->distinct()
                ->limit(5)
                ->pluck('name')
                ->toArray();
            
            // Get category suggestions
            $categorySuggestions = Category::select('name')
                ->where('name', 'LIKE', "{$query}%")
                ->limit(3)
                ->pluck('name')
                ->toArray();
            
            // Get store suggestions
            $storeSuggestions = Seller::select('store_name')
                ->where('store_name', 'LIKE', "{$query}%")
                ->whereNotNull('store_name')
                ->where('store_name', '!=', '')
                ->limit(2)
                ->pluck('store_name')
                ->toArray();
            
            return array_merge($productSuggestions, $categorySuggestions, $storeSuggestions);
        });
        
        return response()->json(array_slice($suggestions, 0, 10));
    }
}