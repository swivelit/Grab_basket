<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\Blog;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BuyerController extends Controller
{
public function index()
{
    $categories = Category::with(['subcategories' => function($query) {
        $query->withCount('products');
    }])->withCount('products')->get();

    // Carousel products with higher discounts for banner
    $carouselProducts = Product::with('category')
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->where('discount', '>=', 20) // Only show products with 20% or higher discount in carousel
        ->orderBy('discount', 'desc') // Order by highest discount first
        ->take(10)
        ->get();
        
    // Get shuffled products from MASALA/COOKING, PERFUME/BEAUTY & DENTAL CARE - ONLY RELEVANT IMAGES
    $cookingCategory = Category::where('name', 'COOKING')->first();
    $beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
    $dentalCategory = Category::where('name', 'DENTAL CARE')->first();
    
    $mixedProducts = collect();
    
    // Get products from each category
    if ($cookingCategory) {
        $cookingProducts = Product::where('category_id', $cookingCategory->id)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(8)
            ->get();
        $mixedProducts = $mixedProducts->merge($cookingProducts);
    }
    
    if ($beautyCategory) {
        $beautyProducts = Product::where('category_id', $beautyCategory->id)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(2)
            ->get();
        $mixedProducts = $mixedProducts->merge($beautyProducts);
    }
    
    if ($dentalCategory) {
        $dentalProducts = Product::where('category_id', $dentalCategory->id)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(2)
            ->get();
        $mixedProducts = $mixedProducts->merge($dentalProducts);
    }
    
    // Shuffle the mixed products and paginate
    $shuffledProducts = $mixedProducts->shuffle();
    $products = new \Illuminate\Pagination\LengthAwarePaginator(
        $shuffledProducts->forPage(1, 12),
        $shuffledProducts->count(),
        12,
        1,
        ['path' => request()->url()]
    );
    // 🔥 Trending items (fetch 5 random products)
    $trending = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->inRandomOrder()
        ->take(5)
        ->get();
       $lookbookProduct = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->inRandomOrder()
        ->first();
    $blogProducts = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->inRandomOrder()
        ->take(3)
        ->get();
    // ✅ Deals of the day - products with discounts
    $deals = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->where('discount', '>', 0)
        ->inRandomOrder()
        ->take(12)
        ->get();
    
    // 🔥 Flash Sale - products with high discounts (>20%)
    $flashSale = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->where('discount', '>', 20)
        ->inRandomOrder()
        ->take(12)
        ->get();
    
    // 🚚 Free Delivery - products with no delivery charge
    $freeDelivery = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->where('delivery_charge', 0)
        ->inRandomOrder()
        ->take(12)
        ->get();

    return view('buyer.index', compact('categories', 'products', 'carouselProducts','trending','lookbookProduct','blogProducts','deals','flashSale','freeDelivery'));
}

public function dashboard()
{
    try {
        // Simplified approach with fallbacks
        Log::info('Buyer Dashboard: Starting dashboard load');
        
        // Try to load categories with minimal complexity first
        $categories = collect();
        
        try {
            $categories = Category::select('id', 'name', 'emoji')->limit(50)->get();
            Log::info('Buyer Dashboard: Basic categories loaded', ['count' => $categories->count()]);
        } catch (\Exception $categoryError) {
            Log::warning('Buyer Dashboard: Category loading failed, using empty collection', [
                'error' => $categoryError->getMessage()
            ]);
            $categories = collect();
        }
        
        // Return view with minimal data
        return view('buyer.dashboard', ['categories' => $categories]);
        
    } catch (\Exception $e) {
        Log::error('Buyer Dashboard Critical Error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        // Ultimate fallback - simple response
        return response('<h1>Dashboard Loading...</h1><p>Please refresh the page. If the problem persists, contact support.</p><script>setTimeout(() => window.location.reload(), 3000);</script>', 500);
    }
}



    public function search(Request $request)
    {
        try {
            $searchQuery = $request->input('q', '');
            $matchedStores = collect();
            
            // Query only from 'products' table - exclude ten_min_delivery_products
            // Product model defaults to 'products' table, so we just use Product:: directly
            $query = Product::with(['category', 'subcategory'])
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%');

        if ($request->filled('q')) {
            $search = trim($searchQuery);
            
            // Search for matching stores - CASE INSENSITIVE and works with 2+ characters
            // Using DB::raw with LOWER() for case-insensitive search in MySQL
            $matchedStores = Seller::where(function($query) use ($search) {
                    // Case-insensitive search on name
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                          // Case-insensitive search on store_name
                          ->orWhereRaw('LOWER(store_name) LIKE ?', ['%' . strtolower($search) . '%'])
                          // Also search by email (case-insensitive)
                          ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($search) . '%']);
                })
                ->with(['user' => function($query) {
                    $query->select('id', 'email');
                }])
                ->get()
                ->map(function($seller) {
                    // Get user ID for this seller
                    $user = User::where('email', $seller->email)->first();
                    if ($user) {
                        $seller->user_id = $user->id;
                        // Count products for this seller
                        $seller->product_count = Product::where('seller_id', $user->id)->count();
                    }
                    return $seller;
                });
            
            $query->where(function ($q) use ($search) {
                // Search in product fields - CASE INSENSITIVE
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(unique_id) LIKE ?', ['%' . strtolower($search) . '%'])
                  // Search in category (case-insensitive)
                  ->orWhereHas('category', function($query) use ($search) {
                      $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
                  })
                  // Search in subcategory (case-insensitive)
                  ->orWhereHas('subcategory', function($query) use ($search) {
                      $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
                  });
                  
                // Search in sellers table (match seller emails to user emails, then to product seller_id)
                // Case-insensitive search
                $sellerEmails = Seller::where(function($query) use ($search) {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                              ->orWhereRaw('LOWER(store_name) LIKE ?', ['%' . strtolower($search) . '%']);
                    })
                    ->pluck('email');
                    
                if ($sellerEmails->isNotEmpty()) {
                    // Get user IDs that match these seller emails
                    $userIds = User::whereIn('email', $sellerEmails)->pluck('id');
                    if ($userIds->isNotEmpty()) {
                        $q->orWhereIn('seller_id', $userIds);
                    }
                }
            });
        }

        // Apply category filter if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Apply subcategory filter if provided
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->input('subcategory_id'));
        }

        // Add sorting
        $sort = $request->input('sort', 'relevance');
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
                if ($request->filled('q')) {
                    // When searching, prioritize exact matches - CASE INSENSITIVE
                    $query->orderByRaw("CASE 
                        WHEN LOWER(name) LIKE ? THEN 1
                        WHEN LOWER(description) LIKE ? THEN 2
                        ELSE 3
                    END", ['%' . strtolower($search) . '%', '%' . strtolower($search) . '%'])
                    ->orderBy('created_at', 'desc');
                } else {
                    $query->latest();
                }
        }

        $products = $query->paginate(24)->appends($request->query());
        
        // Get search statistics
        $totalResults = $products->total();
        
        // Prepare filters array for the view
        $filters = [
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
            'discount_min' => $request->input('discount_min'),
            'free_delivery' => $request->boolean('free_delivery'),
            'sort' => $request->input('sort', 'relevance')
        ];
        
        // Get related products if search returns no results
        $relatedProducts = collect();
        if ($request->filled('q') && $totalResults == 0) {
            // Try to find products from similar categories or popular products
            // Query only from 'products' table - exclude ten_min_delivery_products
            $relatedProducts = Product::with(['category', 'subcategory'])
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%')
                ->inRandomOrder()
                ->take(6)
                ->get();
        }

        // Log search query for analytics
        if ($request->filled('q')) {
            \Illuminate\Support\Facades\Log::info('Search Query', [
                'query' => $searchQuery,
                'results' => $totalResults,
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);
        }

        // Get all categories for sidebar
        $allCategories = Category::with(['subcategories' => function($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        // Ensure we're passing 'categories' variable (not 'allCategories')
        return view('products.index', [
            'products' => $products,
            'searchQuery' => $searchQuery,
            'totalResults' => $totalResults,
            'matchedStores' => $matchedStores,
            'filters' => $filters,
            'relatedProducts' => $relatedProducts,
            'categories' => $allCategories
        ]);
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Search Error', [
            'query' => $request->input('q'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Return empty paginated result instead of collection
        $emptyProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            collect([]),
            0,
            24,
            1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        // Get all categories for sidebar even on error
        $allCategories = Category::with(['subcategories' => function($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        return view('products.index', [
            'products' => $emptyProducts,
            'searchQuery' => $request->input('q', ''),
            'totalResults' => 0,
            'matchedStores' => collect([]),
            'filters' => [],
            'relatedProducts' => collect([]),
            'categories' => $allCategories,
            'error' => 'An error occurred while searching. Please try again.'
        ]);
    }
}


    public function storeCatalog(Request $request, $seller_id)
    {
        // Get seller information
        $user = User::findOrFail($seller_id);
        $seller = Seller::where('email', $user->email)->first();
        
        if (!$seller) {
            abort(404, 'Store not found');
        }
        
        // Get all products from this seller
        $query = Product::where('seller_id', $seller_id)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%');
        
        // Add sorting
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
            default: // newest
                $query->orderBy('created_at', 'desc');
        }
        
        $products = $query->paginate(24)->appends($request->query());
        $totalProducts = $query->count();
        
        return view('buyer.store-catalog', compact('seller', 'products', 'totalProducts'));
    }

    public function productsByCategory(Request $request, $category_id)
    {
        try {
            $category = Category::findOrFail($category_id);
            // Query only from 'products' table - exclude ten_min_delivery_products
            $query = Product::with(['category', 'subcategory'])
                ->where('category_id', $category_id)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%');

            // Filters
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
                $query->where(function($q){ $q->whereNull('delivery_charge')->orWhere('delivery_charge', 0); });
            }

            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sort = $request->input('sort', 'latest');
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
                default:
                    $query->latest();
            }

            $products = $query->paginate(12)->appends($request->query());
            $allCategories = Category::orderBy('name')->get();
            $subsByCategory = Subcategory::orderBy('name')->get()->groupBy('category_id');
            
            // Make sure all required variables are set for the view
            return view('buyer.products', [
                'category' => $category,
                'products' => $products,
                'categories' => $allCategories,
                'subsByCategory' => $subsByCategory,
                'activeCategoryId' => (int)$category_id,
                'activeSubcategoryId' => null,
                'filters' => $request->only(['price_min','price_max','discount_min','free_delivery','sort']),
                'searchQuery' => $request->input('q', ''),
                'totalResults' => $products->total(),
                'matchedStores' => collect([]),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Category products error: ' . $e->getMessage(), [
                'category_id' => $category_id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return a fallback response
            return response()->view('errors.500', [
                'message' => 'Unable to load category products. Please try again later.'
            ], 500);
        }
    }

    public function productsBySubcategory(Request $request, $subcategory_id)
    {
        try {
            $subcategory = Subcategory::with('category')->findOrFail($subcategory_id);
            // Query only from 'products' table - exclude ten_min_delivery_products
            $query = Product::with(['category', 'subcategory'])
                ->where('subcategory_id', $subcategory_id)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%');

            // Filters
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
                $query->where(function($q){ $q->whereNull('delivery_charge')->orWhere('delivery_charge', 0); });
            }

            // Sorting
            $sort = $request->input('sort', 'latest');
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
                default:
                    $query->latest();
            }

            $products = $query->paginate(12)->appends($request->query());
            $allCategories = Category::orderBy('name')->get();
            $subsByCategory = Subcategory::orderBy('name')->get()->groupBy('category_id');
            
            return view('buyer.products', [
                'subcategory' => $subcategory,
                'products' => $products,
                'categories' => $allCategories,
                'subsByCategory' => $subsByCategory,
                'activeCategoryId' => (int)$subcategory->category_id,
                'activeSubcategoryId' => (int)$subcategory_id,
                'filters' => $request->only(['price_min','price_max','discount_min','free_delivery','sort']),
                'searchQuery' => $request->input('q', ''),
                'totalResults' => $products->total(),
                'matchedStores' => collect([]),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Subcategory products error: ' . $e->getMessage(), [
                'subcategory_id' => $subcategory_id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return a fallback response
            return response()->view('errors.500', [
                'message' => 'Unable to load subcategory products. Please try again later.'
            ], 500);
        }
    }}
    

