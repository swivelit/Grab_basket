<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseProduct;
use App\Models\WarehouseStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index()
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('view_reports')) {
            abort(403, 'Access denied. Reports permission required.');
        }

        try {
            // Get report summary data
            $reportData = [
                'inventory_summary' => $this->getInventorySummary(),
                'movement_summary' => $this->getMovementSummary(),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'recent_alerts' => $this->getRecentAlerts(),
            ];

            return view('warehouse.reports.index', $reportData);

        } catch (\Exception $e) {
            return back()->with('error', 'Unable to load reports. Please try again.');
        }
    }

    /**
     * Stock summary report
     */
    public function stockSummary(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('view_reports')) {
            abort(403, 'Access denied. Reports permission required.');
        }

        try {
            $query = WarehouseProduct::with('product');

            // Filter by user's assigned areas if not manager
            if (!$user->hasPermission('manage_users')) {
                $assignedAreas = $user->assigned_areas ?? [];
                if (!empty($assignedAreas)) {
                    $query->whereIn('aisle', $assignedAreas);
                }
            }

            // Apply filters
            if ($request->filled('aisle')) {
                $query->where('aisle', $request->aisle);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->where('current_stock', '>', 0);
                        break;
                    case 'low_stock':
                        $query->where('current_stock', '>', 0)
                              ->whereRaw('current_stock <= reorder_level');
                        break;
                    case 'out_of_stock':
                        $query->where('current_stock', '<=', 0);
                        break;
                }
            }

            if ($request->filled('quick_delivery')) {
                $query->where('is_quick_delivery', $request->boolean('quick_delivery'));
            }

            // Get summary statistics
            $summary = [
                'total_products' => $query->count(),
                'total_value' => $query->sum(DB::raw('current_stock * cost_price')),
                'in_stock_count' => $query->where('current_stock', '>', 0)->count(),
                'low_stock_count' => $query->where('current_stock', '>', 0)
                                          ->whereRaw('current_stock <= reorder_level')->count(),
                'out_of_stock_count' => $query->where('current_stock', '<=', 0)->count(),
            ];

            // Get detailed product data
            $products = $query->orderBy('product_id')
                             ->paginate(50);

            return view('warehouse.reports.stock-summary', compact('summary', 'products'));

        } catch (\Exception $e) {
            return back()->with('error', 'Unable to generate stock summary report.');
        }
    }

    /**
     * Movement analysis report
     */
    public function movements(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('view_reports')) {
            abort(403, 'Access denied. Reports permission required.');
        }

        try {
            $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            $query = WarehouseStockMovement::with(['warehouseProduct.product'])
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

            // Filter by user if not manager
            if (!$user->hasPermission('manage_users')) {
                $query->where('performed_by', $user->name);
            }

            // Apply filters
            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            if ($request->filled('performed_by')) {
                $query->where('performed_by', 'like', '%' . $request->performed_by . '%');
            }

            // Get movement statistics
            $movementStats = [
                'total_movements' => $query->count(),
                'stock_in_total' => $query->where('movement_type', 'stock_in')->sum('quantity_changed'),
                'stock_out_total' => abs($query->where('movement_type', 'stock_out')->sum('quantity_changed')),
                'adjustments_total' => $query->where('movement_type', 'adjustment')->sum('quantity_changed'),
                'transfers_total' => $query->where('movement_type', 'transfer')->count(),
            ];

            // Get daily movement trend
            $dailyMovements = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(ABS(quantity_changed)) as total_quantity')
                                   ->groupBy('date')
                                   ->orderBy('date')
                                   ->get();

            // Get movements by type
            $movementsByType = $query->selectRaw('movement_type, COUNT(*) as count, SUM(ABS(quantity_changed)) as total_quantity')
                                    ->groupBy('movement_type')
                                    ->get();

            // Get top performing users
            $topUsers = WarehouseStockMovement::selectRaw('performed_by, COUNT(*) as movement_count, SUM(ABS(quantity_changed)) as total_quantity')
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->groupBy('performed_by')
                ->orderByDesc('movement_count')
                ->limit(10)
                ->get();

            // Get recent movements
            $recentMovements = $query->orderBy('created_at', 'desc')
                                    ->limit(20)
                                    ->get();

            return view('warehouse.reports.movements', compact(
                'movementStats',
                'dailyMovements',
                'movementsByType',
                'topUsers',
                'recentMovements',
                'startDate',
                'endDate'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Unable to generate movement report.');
        }
    }

    /**
     * Export reports
     */
    public function export(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('view_reports')) {
            abort(403, 'Access denied. Reports permission required.');
        }

        $reportType = $request->get('type', 'stock_summary');
        $format = $request->get('format', 'csv');

        try {
            switch ($reportType) {
                case 'stock_summary':
                    return $this->exportStockSummary($format, $request);
                case 'movements':
                    return $this->exportMovements($format, $request);
                case 'low_stock':
                    return $this->exportLowStock($format, $request);
                default:
                    return back()->with('error', 'Invalid report type.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Export failed. Please try again.');
        }
    }

    /**
     * Get inventory summary
     */
    private function getInventorySummary(): array
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

        return [
            'total_products' => $query->count(),
            'total_stock_value' => $query->sum(DB::raw('current_stock * cost_price')),
            'in_stock_count' => $query->where('current_stock', '>', 0)->count(),
            'low_stock_count' => $query->where('current_stock', '>', 0)
                                      ->whereRaw('current_stock <= reorder_level')->count(),
            'out_of_stock_count' => $query->where('current_stock', '<=', 0)->count(),
            'quick_delivery_count' => $query->where('is_quick_delivery', true)->count(),
            'average_stock_level' => round($query->avg('current_stock'), 2),
        ];
    }

    /**
     * Get movement summary
     */
    private function getMovementSummary(): array
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseStockMovement::query();
        
        // Filter by user if not manager
        if (!$user->hasPermission('manage_users')) {
            $query->where('performed_by', $user->name);
        }

        $last30Days = now()->subDays(30);

        return [
            'total_movements_30d' => $query->where('created_at', '>=', $last30Days)->count(),
            'stock_in_30d' => $query->where('created_at', '>=', $last30Days)
                                   ->where('movement_type', 'stock_in')->sum('quantity_changed'),
            'stock_out_30d' => abs($query->where('created_at', '>=', $last30Days)
                                        ->where('movement_type', 'stock_out')->sum('quantity_changed')),
            'adjustments_30d' => $query->where('created_at', '>=', $last30Days)
                                      ->where('movement_type', 'adjustment')->count(),
            'today_movements' => $query->whereDate('created_at', now())->count(),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        $user = Auth::guard('warehouse')->user();
        
        // User's personal metrics
        $userMovements = WarehouseStockMovement::where('performed_by', $user->name)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Location efficiency (for managers)
        $locationMetrics = [];
        if ($user->hasPermission('manage_users')) {
            $locationMetrics = WarehouseProduct::select('aisle')
                ->selectRaw('COUNT(*) as product_count')
                ->selectRaw('AVG(current_stock) as avg_stock')
                ->selectRaw('COUNT(CASE WHEN current_stock <= 0 THEN 1 END) as out_of_stock')
                ->groupBy('aisle')
                ->get()
                ->map(function ($item) {
                    $item->efficiency = $item->product_count > 0 
                        ? round((($item->product_count - $item->out_of_stock) / $item->product_count) * 100, 1)
                        : 0;
                    return $item;
                });
        }

        return [
            'user_productivity' => $userMovements,
            'location_efficiency' => $locationMetrics,
            'quick_delivery_ratio' => WarehouseProduct::where('is_quick_delivery', true)->count() / 
                                     max(WarehouseProduct::count(), 1) * 100,
        ];
    }

    /**
     * Get recent alerts
     */
    private function getRecentAlerts(): array
    {
        $alerts = [];

        // Low stock alerts
        $lowStock = WarehouseProduct::with('product')
            ->where('current_stock', '>', 0)
            ->whereRaw('current_stock <= reorder_level')
            ->limit(5)
            ->get();

        foreach ($lowStock as $product) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => "{$product->product->name} is running low (Stock: {$product->current_stock})",
                'created_at' => $product->updated_at,
            ];
        }

        // Out of stock alerts
        $outOfStock = WarehouseProduct::with('product')
            ->where('current_stock', '<=', 0)
            ->limit(3)
            ->get();

        foreach ($outOfStock as $product) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Out of Stock',
                'message' => "{$product->product->name} is out of stock",
                'created_at' => $product->updated_at,
            ];
        }

        // Sort by date
        usort($alerts, function ($a, $b) {
            return $b['created_at']->timestamp - $a['created_at']->timestamp;
        });

        return array_slice($alerts, 0, 8);
    }

    /**
     * Export stock summary
     */
    private function exportStockSummary(string $format, Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseProduct::with('product');

        // Filter by user's assigned areas if not manager
        if (!$user->hasPermission('manage_users')) {
            $assignedAreas = $user->assigned_areas ?? [];
            if (!empty($assignedAreas)) {
                $query->whereIn('aisle', $assignedAreas);
            }
        }

        $products = $query->get();

        $filename = 'warehouse_stock_summary_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($products) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Product Name', 'SKU', 'Barcode', 'Location', 'Aisle', 
                    'Current Stock', 'Reorder Level', 'Cost Price', 'Stock Value',
                    'Quick Delivery', 'Condition', 'Last Updated'
                ]);

                // CSV data
                foreach ($products as $product) {
                    fputcsv($file, [
                        $product->product->name ?? 'Unknown',
                        $product->product->sku ?? '',
                        $product->barcode ?? '',
                        $product->location ?? '',
                        $product->aisle ?? '',
                        $product->current_stock,
                        $product->reorder_level,
                        number_format($product->cost_price, 2),
                        number_format($product->current_stock * $product->cost_price, 2),
                        $product->is_quick_delivery ? 'Yes' : 'No',
                        $product->condition,
                        $product->updated_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        return back()->with('error', 'Export format not supported.');
    }

    /**
     * Export movements
     */
    private function exportMovements(string $format, Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $query = WarehouseStockMovement::with(['warehouseProduct.product'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        // Filter by user if not manager
        if (!$user->hasPermission('manage_users')) {
            $query->where('performed_by', $user->name);
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        $filename = 'warehouse_movements_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($movements) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Date', 'Product Name', 'SKU', 'Movement Type', 
                    'Quantity Before', 'Quantity Changed', 'Quantity After',
                    'Reason', 'Performed By', 'Notes'
                ]);

                // CSV data
                foreach ($movements as $movement) {
                    fputcsv($file, [
                        $movement->created_at->format('Y-m-d H:i:s'),
                        $movement->warehouseProduct->product->name ?? 'Unknown',
                        $movement->warehouseProduct->product->sku ?? '',
                        ucfirst(str_replace('_', ' ', $movement->movement_type)),
                        $movement->quantity_before,
                        $movement->quantity_changed,
                        $movement->quantity_after,
                        $movement->reason,
                        $movement->performed_by,
                        $movement->notes
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        return back()->with('error', 'Export format not supported.');
    }

    /**
     * Export low stock report
     */
    private function exportLowStock(string $format, Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseProduct::with('product')
            ->where('current_stock', '>', 0)
            ->whereRaw('current_stock <= reorder_level');

        // Filter by user's assigned areas if not manager
        if (!$user->hasPermission('manage_users')) {
            $assignedAreas = $user->assigned_areas ?? [];
            if (!empty($assignedAreas)) {
                $query->whereIn('aisle', $assignedAreas);
            }
        }

        $products = $query->orderBy('current_stock')->get();

        $filename = 'warehouse_low_stock_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($products) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Product Name', 'SKU', 'Location', 'Current Stock', 
                    'Reorder Level', 'Shortage', 'Suggested Order Quantity',
                    'Quick Delivery', 'Last Updated'
                ]);

                // CSV data
                foreach ($products as $product) {
                    $shortage = $product->reorder_level - $product->current_stock;
                    $suggestedOrder = max($shortage, $product->reorder_level * 2);
                    
                    fputcsv($file, [
                        $product->product->name ?? 'Unknown',
                        $product->product->sku ?? '',
                        $product->location ?? '',
                        $product->current_stock,
                        $product->reorder_level,
                        $shortage,
                        $suggestedOrder,
                        $product->is_quick_delivery ? 'Yes' : 'No',
                        $product->updated_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        return back()->with('error', 'Export format not supported.');
    }
}