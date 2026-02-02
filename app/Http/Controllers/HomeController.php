<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Banner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        try {
            // Test database connection first
            DB::connection()->getPdo();
            
            // Load basic data with error handling and fallbacks
            $categories = collect([]);
            $products = collect([]);
            $trending = collect([]);
            $banners = collect([]);
            
            try {
                $categories = Category::with('subcategories')->limit(20)->get();
            } catch (\Exception $e) {
                Log::warning('Categories load failed: ' . $e->getMessage());
            }
            
            try {
                $products = Product::with('category')->limit(12)->get();
            } catch (\Exception $e) {
                Log::warning('Products load failed: ' . $e->getMessage());
            }
            
            try {
                $trending = Product::orderBy('created_at', 'desc')->limit(8)->get();
            } catch (\Exception $e) {
                Log::warning('Trending products load failed: ' . $e->getMessage());
            }
            
            try {
                $banners = Banner::where('is_active', true)
                    ->where('position', 'hero')
                    ->orderBy('display_order')
                    ->get();
            } catch (\Exception $e) {
                Log::warning('Banners load failed: ' . $e->getMessage());
            }
            
            // Default settings
            $settings = [
                'hero_title' => 'Welcome to GrabBaskets',
                'hero_subtitle' => 'Your one-stop shop for all your needs',
                'show_categories' => true,
                'show_featured_products' => true,
                'show_trending' => true,
                'theme_color' => '#FF6B00',
                'secondary_color' => '#FFD700',
            ];

            // Load 10-minute delivery products for mobile view
            $ten_min_products = Product::limit(12)->get();
            $ten_min_categories = Category::limit(8)->get();
            
            // Load all products for desktop normal delivery view
            $all_products = Product::limit(24)->get();

            return view('index', [
                'categories' => $categories,
                'products' => $products,
                'trending' => $trending,
                'lookbookProduct' => $products->first(),
                'blogProducts' => $products->take(6),
                'categoryProducts' => [],
                'banners' => $banners,
                'settings' => $settings,
                'ten_min_products' => $ten_min_products,
                'ten_min_categories' => $ten_min_categories,
                'all_products' => $all_products
            ]);
            
        } catch (\PDOException $e) {
            // Database connection error
            Log::error('Database connection error on homepage: ' . $e->getMessage());
            
            if (config('app.debug')) {
                return response()->json([
                    'error' => 'Database connection failed',
                    'message' => $e->getMessage(),
                    'hint' => 'Check database credentials in .env file'
                ], 500);
            }
            
            return response()->view('errors.500', [], 500);
            
        } catch (\Throwable $e) {
            // Any other error
            Log::error('Homepage error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return error response for debugging
            if (config('app.debug')) {
                return response()->json([
                    'error' => 'Homepage error',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ], 500);
            }
            
            // Try to return minimal fallback page
            try {
                return view('index', [
                    'categories' => collect([]),
                    'products' => collect([]),
                    'trending' => collect([]),
                    'lookbookProduct' => null,
                    'blogProducts' => collect([]),
                    'categoryProducts' => [],
                    'banners' => collect([]),
                    'settings' => [
                        'hero_title' => 'Welcome to GrabBaskets',
                        'hero_subtitle' => 'Temporarily unavailable',
                        'theme_color' => '#FF6B00',
                    ],
                    'database_error' => 'Service temporarily unavailable'
                ]);
            } catch (\Exception $viewException) {
                // If even the view fails, return a simple HTML response
                return response('<html><body><h1>GrabBaskets</h1><p>We are experiencing technical difficulties. Please try again shortly.</p></body></html>', 500);
            }
        }
    }
}
