<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmergencySearchController extends Controller
{
    /**
     * Emergency search fix for guests - basic but working search
     */
    public function search(Request $request)
    {
        try {
            $searchQuery = trim($request->input('q', ''));
            $matchedStores = collect();
            
            // Start with basic product query - no complex joins initially
            $query = Product::query()
                ->whereNotNull('image')
                ->where('image', '!=', '');

            // Apply search if provided
            if (!empty($searchQuery) && strlen($searchQuery) >= 2) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('description', 'LIKE', "%{$searchQuery}%");
                });
                
                // Get matching stores (simplified)
                $matchedStores = Seller::where('name', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('store_name', 'LIKE', "%{$searchQuery}%")
                    ->limit(5)
                    ->get();
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
            if ($request->boolean('free_delivery')) {
                $query->where(function($q) {
                    $q->whereNull('delivery_charge')->orWhere('delivery_charge', 0);
                });
            }

            // Apply sorting
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
                default:
                    $query->orderBy('created_at', 'desc');
            }

            // Get paginated results
            $products = $query->paginate(24)->appends($request->query());
            
            // Get total results
            $totalResults = $products->total();
            
            // Prepare response data
            $filters = $request->only(['price_min', 'price_max', 'discount_min', 'free_delivery', 'sort']);
            
            return view('buyer.products', [
                'products' => $products,
                'searchQuery' => $searchQuery,
                'totalResults' => $totalResults,
                'matchedStores' => $matchedStores,
                'filters' => $filters,
                'isEmergencyMode' => true
            ]);
            
        } catch (\Exception $e) {
            // Log error for debugging
            \Illuminate\Support\Facades\Log::error('Emergency Search Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty result with error message
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
                'error' => 'Search is temporarily unavailable. Please try again later.',
                'isEmergencyMode' => true
            ]);
        }
    }
}