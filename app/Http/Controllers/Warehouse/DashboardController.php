<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseProduct;
use App\Models\WarehouseStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Middleware will be applied in routes file
    }

    /**
     * Show the warehouse dashboard
     */
    public function index()
    {
        try {
            // Get basic statistics
            $stats = $this->getDashboardStats();
            
            // Get recent stock movements
            $recentMovements = $this->getRecentMovements();
            
            // Get low stock alerts
            $lowStockProducts = $this->getLowStockProducts();
            
            // Get out of stock products
            $outOfStockProducts = $this->getOutOfStockProducts();

            return view('warehouse.dashboard', compact(
                'stats',
                'recentMovements',
                'lowStockProducts',
                'outOfStockProducts'
            ));

        } catch (\Exception $e) {
            return view('warehouse.dashboard')->with('error', 'Unable to load dashboard data. Please try again.');
        }
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseProduct::query();
        
        // Filter by user's assigned areas if not manager
        if (!$user->hasPermission('manage_users')) {
            $assignedAreas = $user->assigned_areas ?? [];
            if (!empty($assignedAreas)) {
                $query->whereIn('aisle', $assignedAreas);
            }
        }

        $totalProducts = $query->count();
        $inStock = $query->where('current_stock', '>', 0)->count();
        $lowStock = $query->where('current_stock', '>', 0)
                          ->where('current_stock', '<=', DB::raw('reorder_level'))
                          ->count();
        $outOfStock = $query->where('current_stock', '<=', 0)->count();

        return [
            'total_products' => $totalProducts,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'stock_value' => $query->sum(DB::raw('current_stock * cost_price')),
        ];
    }

    /**
     * Get recent stock movements
     */
    private function getRecentMovements(int $limit = 10)
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseStockMovement::with(['warehouseProduct.product'])
            ->orderBy('created_at', 'desc');

        // Filter by user's movements if not manager
        if (!$user->hasPermission('manage_users')) {
            $query->where('performed_by', $user->name);
        }

        return $query->take($limit)->get();
    }

    /**
     * Get products with low stock
     */
    private function getLowStockProducts(int $limit = 20)
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseProduct::with('product')
            ->where('current_stock', '>', 0)
            ->where('current_stock', '<=', DB::raw('reorder_level'))
            ->orderBy('current_stock', 'asc');

        // Filter by user's assigned areas if not manager
        if (!$user->hasPermission('manage_users')) {
            $assignedAreas = $user->assigned_areas ?? [];
            if (!empty($assignedAreas)) {
                $query->whereIn('aisle', $assignedAreas);
            }
        }

        return $query->take($limit)->get();
    }

    /**
     * Get products that are out of stock
     */
    private function getOutOfStockProducts(int $limit = 20)
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseProduct::with('product')
            ->where('current_stock', '<=', 0)
            ->orderBy('updated_at', 'desc');

        // Filter by user's assigned areas if not manager
        if (!$user->hasPermission('manage_users')) {
            $assignedAreas = $user->assigned_areas ?? [];
            if (!empty($assignedAreas)) {
                $query->whereIn('aisle', $assignedAreas);
            }
        }

        return $query->take($limit)->get();
    }

    /**
     * Get quick stats for AJAX requests
     */
    public function quickStats()
    {
        try {
            $stats = $this->getDashboardStats();
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch statistics.',
            ], 500);
        }
    }

    /**
     * Get notifications for the user
     */
    public function notifications()
    {
        try {
            $notifications = [];
            
            // Low stock notifications
            $lowStockCount = $this->getLowStockProducts()->count();
            if ($lowStockCount > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'icon' => 'exclamation-triangle',
                    'title' => 'Low Stock Alert',
                    'message' => "{$lowStockCount} products are running low on stock",
                    'url' => route('warehouse.inventory', ['filter' => 'low_stock']),
                    'priority' => 'medium',
                ];
            }

            // Out of stock notifications
            $outOfStockCount = $this->getOutOfStockProducts()->count();
            if ($outOfStockCount > 0) {
                $notifications[] = [
                    'type' => 'danger',
                    'icon' => 'x-circle',
                    'title' => 'Out of Stock',
                    'message' => "{$outOfStockCount} products are completely out of stock",
                    'url' => route('warehouse.inventory', ['filter' => 'out_of_stock']),
                    'priority' => 'high',
                ];
            }

            // System notifications
            $notifications[] = [
                'type' => 'info',
                'icon' => 'info-circle',
                'title' => 'System Update',
                'message' => 'New quick delivery features are now available',
                'url' => route('warehouse.quick-delivery'),
                'priority' => 'low',
            ];

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => count(array_filter($notifications, fn($n) => $n['priority'] !== 'low')),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch notifications.',
            ], 500);
        }
    }

    /**
     * Search products for quick access
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 2 characters.',
                ]);
            }

            $user = Auth::guard('warehouse')->user();
            
            $products = WarehouseProduct::with('product')
                ->whereHas('product', function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%");
                })
                ->orWhere('barcode', 'like', "%{$query}%")
                ->orWhere('location', 'like', "%{$query}%");

            // Filter by user's assigned areas if not manager
            if (!$user->hasPermission('manage_users')) {
                $assignedAreas = $user->assigned_areas ?? [];
                if (!empty($assignedAreas)) {
                    $products->whereIn('aisle', $assignedAreas);
                }
            }

            $results = $products->take(10)->get()->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->product->name ?? 'Unknown Product',
                    'sku' => $product->product->sku ?? 'No SKU',
                    'barcode' => $product->barcode,
                    'location' => $product->location,
                    'current_stock' => $product->current_stock,
                    'status' => $product->current_stock > 0 ? 'in_stock' : 'out_of_stock',
                    'url' => route('warehouse.inventory.show', $product->id),
                ];
            });

            return response()->json([
                'success' => true,
                'results' => $results,
                'count' => $results->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed. Please try again.',
            ], 500);
        }
    }
}