<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarehouseProduct;
use App\Models\WarehouseStockMovement;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Services\QuickDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WarehouseController extends Controller
{
    /**
     * Warehouse Dashboard - Overview and Analytics
     */
    public function dashboard()
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            // Key metrics
            $totalProducts = WarehouseProduct::count();
            $totalStock = WarehouseProduct::sum('stock_quantity');
            $availableStock = WarehouseProduct::sum(DB::raw('stock_quantity - reserved_quantity'));
            $reservedStock = WarehouseProduct::sum('reserved_quantity');
            
            // Quick delivery metrics
            $quickDeliveryProducts = WarehouseProduct::availableForQuickDelivery()->count();
            $quickDeliveryStock = WarehouseProduct::availableForQuickDelivery()->sum('stock_quantity');
            
            // Stock alerts
            $lowStockCount = WarehouseProduct::lowStock()->count();
            $expiredCount = WarehouseProduct::expired()->count();
            $expiringCount = WarehouseProduct::expiringSoon()->count();
            $reorderNeeded = WarehouseProduct::needsReorder()->count();
            
            // Recent movements (last 7 days)
            $recentMovements = WarehouseStockMovement::with(['product', 'warehouseProduct'])
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Movement statistics (last 30 days)
            $movementStats = WarehouseStockMovement::getMovementsByType(
                Carbon::now()->subDays(30)->toDateString(),
                Carbon::now()->toDateString()
            );
            
            // Daily movement trend (last 14 days)
            $dailyTrend = WarehouseStockMovement::getDailyMovementTrend(14);
            
            // Top products by movement
            $topMovedProducts = WarehouseStockMovement::with('product')
                ->select('product_id', DB::raw('SUM(ABS(quantity_changed)) as total_movement'))
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->groupBy('product_id')
                ->orderBy('total_movement', 'desc')
                ->limit(10)
                ->get();
            
            // Low stock alerts
            $lowStockProducts = WarehouseProduct::with('product')
                ->lowStock()
                ->orderBy('stock_quantity')
                ->limit(10)
                ->get();
            
            // Expiring products
            $expiringProducts = WarehouseProduct::with('product')
                ->expiringSoon(7)
                ->orderBy('expiry_date')
                ->limit(10)
                ->get();

            return view('admin.warehouse.dashboard', compact(
                'totalProducts', 'totalStock', 'availableStock', 'reservedStock',
                'quickDeliveryProducts', 'quickDeliveryStock',
                'lowStockCount', 'expiredCount', 'expiringCount', 'reorderNeeded',
                'recentMovements', 'movementStats', 'dailyTrend',
                'topMovedProducts', 'lowStockProducts', 'expiringProducts'
            ));

        } catch (\Exception $e) {
            Log::error('Warehouse dashboard error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load warehouse dashboard. Please try again.');
        }
    }

    /**
     * Inventory Management - List all warehouse products
     */
    public function inventory(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            $query = WarehouseProduct::with(['product']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                })->orWhere('location_code', 'like', "%{$search}%");
            }

            // Status filter
            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->where('stock_quantity', '>', 0)
                              ->where('is_expired', false);
                        break;
                    case 'low_stock':
                        $query->whereRaw('stock_quantity <= minimum_stock_level');
                        break;
                    case 'out_of_stock':
                        $query->whereRaw('(stock_quantity - reserved_quantity) <= 0');
                        break;
                    case 'expired':
                        $query->where('is_expired', true);
                        break;
                    case 'expiring_soon':
                        $query->expiringSoon(7);
                        break;
                    case 'needs_reorder':
                        $query->needsReorder();
                        break;
                }
            }

            // Quick delivery filter
            if ($request->filled('quick_delivery')) {
                if ($request->quick_delivery === '1') {
                    $query->availableForQuickDelivery();
                } else {
                    $query->where('is_available_for_quick_delivery', false);
                }
            }

            // Location filter
            if ($request->filled('location')) {
                $query->where('aisle', $request->location)
                      ->orWhere('location_code', 'like', $request->location . '%');
            }

            // Sort
            $sortBy = $request->get('sort_by', 'updated_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'product_name') {
                $query->join('products', 'warehouse_products.product_id', '=', 'products.id')
                      ->orderBy('products.name', $sortOrder)
                      ->select('warehouse_products.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            $products = $query->paginate(20);

            return view('admin.warehouse.inventory', compact('products'));

        } catch (\Exception $e) {
            Log::error('Warehouse inventory error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load inventory. Please try again.');
        }
    }

    /**
     * Show/Edit specific warehouse product
     */
    public function show(Request $request, $id)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            $warehouseProduct = WarehouseProduct::with(['product', 'stockMovements' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            }])->findOrFail($id);

            // Recent movements for this product
            $recentMovements = $warehouseProduct->stockMovements()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Movement statistics for last 30 days
            $movementStats = WarehouseStockMovement::forProduct($warehouseProduct->product_id)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->selectRaw('movement_type, COUNT(*) as count, SUM(ABS(quantity_changed)) as total_quantity')
                ->groupBy('movement_type')
                ->get()
                ->keyBy('movement_type');

            return view('admin.warehouse.product-details', compact(
                'warehouseProduct', 'recentMovements', 'movementStats'
            ));

        } catch (\Exception $e) {
            Log::error('Warehouse product show error: ' . $e->getMessage());
            return back()->with('error', 'Product not found or error occurred.');
        }
    }

    /**
     * Update warehouse product details
     */
    public function update(Request $request, $id)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'stock_quantity' => 'required|integer|min:0',
            'minimum_stock_level' => 'required|integer|min:0',
            'maximum_stock_level' => 'required|integer|min:0',
            'aisle' => 'nullable|string|max:10',
            'rack' => 'nullable|string|max:10',
            'shelf' => 'nullable|string|max:10',
            'location_code' => 'nullable|string|max:20',
            'expiry_date' => 'nullable|date|after:today',
            'condition' => 'required|in:excellent,good,fair,damaged',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:100',
            'batch_number' => 'nullable|string|max:50',
            'weight_grams' => 'nullable|integer|min:0',
            'fragility' => 'required|in:low,medium,high',
            'requires_cold_storage' => 'boolean',
            'handling_notes' => 'nullable|string|max:500',
            'is_available_for_quick_delivery' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $warehouseProduct = WarehouseProduct::findOrFail($id);
            $oldQuantity = $warehouseProduct->stock_quantity;
            
            // Update warehouse product
            $warehouseProduct->update($request->all());

            // If stock quantity changed, log the adjustment
            if ($oldQuantity != $request->stock_quantity) {
                $warehouseProduct->stockMovements()->create([
                    'product_id' => $warehouseProduct->product_id,
                    'movement_type' => 'adjustment',
                    'quantity_before' => $oldQuantity,
                    'quantity_changed' => $request->stock_quantity - $oldQuantity,
                    'quantity_after' => $request->stock_quantity,
                    'reason' => 'Manual adjustment via admin panel',
                    'performed_by' => 'Admin',
                ]);
            }

            // Update stock flags
            $warehouseProduct->updateStockFlags();

            return back()->with('success', 'Warehouse product updated successfully!');

        } catch (\Exception $e) {
            Log::error('Warehouse product update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update product. Please try again.');
        }
    }

    /**
     * Stock Movements History
     */
    public function stockMovements(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            $query = WarehouseStockMovement::with(['product', 'warehouseProduct', 'order']);

            // Date range filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->inDateRange($request->start_date, $request->end_date);
            } else {
                // Default to last 30 days
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            }

            // Movement type filter
            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            // Product search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Performer filter
            if ($request->filled('performed_by')) {
                $query->where('performed_by', 'like', "%{$request->performed_by}%");
            }

            $movements = $query->orderBy('created_at', 'desc')->paginate(25);

            // Movement type options for filter
            $movementTypes = [
                'stock_in' => 'Stock In',
                'stock_out' => 'Stock Out',
                'reserved' => 'Reserved',
                'released' => 'Released',
                'expired' => 'Expired',
                'damaged' => 'Damaged',
                'adjustment' => 'Adjustment',
                'transfer_out' => 'Transfer Out',
                'transfer_in' => 'Transfer In',
                'returned' => 'Returned'
            ];

            return view('admin.warehouse.stock-movements', compact('movements', 'movementTypes'));

        } catch (\Exception $e) {
            Log::error('Warehouse stock movements error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load stock movements. Please try again.');
        }
    }

    /**
     * Add new stock (Stock In)
     */
    public function addStock(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'warehouse_product_id' => 'required|exists:warehouse_products,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:50',
            'reason' => 'required|string|max:200',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $warehouseProduct = WarehouseProduct::findOrFail($request->warehouse_product_id);
            
            $details = [
                'reason' => $request->reason,
                'notes' => $request->notes,
                'reference' => $request->reference_number,
                'supplier' => $request->supplier_name,
                'unit_cost' => $request->unit_cost,
            ];

            $warehouseProduct->addStock($request->quantity, $details);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully added {$request->quantity} units to stock!"
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Add stock error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add stock. Please try again.']);
        }
    }

    /**
     * Quick Delivery Optimization
     */
    public function quickDeliveryOptimization(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            // Products available for quick delivery
            $quickDeliveryProducts = WarehouseProduct::with('product')
                ->availableForQuickDelivery()
                ->orderBy('stock_quantity', 'desc')
                ->paginate(20);

            // Products not available but could be enabled
            $potentialProducts = WarehouseProduct::with('product')
                ->where('stock_quantity', '>', 0)
                ->where('is_available_for_quick_delivery', false)
                ->where('condition', '!=', 'damaged')
                ->where(function($query) {
                    $query->where('is_expired', false)
                          ->orWhereNull('expiry_date');
                })
                ->orderBy('stock_quantity', 'desc')
                ->limit(10)
                ->get();

            // Quick delivery statistics
            $stats = [
                'total_products' => WarehouseProduct::availableForQuickDelivery()->count(),
                'total_stock' => WarehouseProduct::availableForQuickDelivery()->sum('stock_quantity'),
                'low_stock_products' => WarehouseProduct::availableForQuickDelivery()->lowStock()->count(),
                'high_demand_products' => WarehouseStockMovement::whereIn('movement_type', ['reserved', 'stock_out'])
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->selectRaw('product_id, SUM(ABS(quantity_changed)) as demand')
                    ->groupBy('product_id')
                    ->orderBy('demand', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return view('admin.warehouse.quick-delivery', compact(
                'quickDeliveryProducts', 'potentialProducts', 'stats'
            ));

        } catch (\Exception $e) {
            Log::error('Quick delivery optimization error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load quick delivery optimization. Please try again.');
        }
    }

    /**
     * Toggle quick delivery availability for a product
     */
    public function toggleQuickDelivery(Request $request, $id)
    {
        if (!session('is_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        try {
            $warehouseProduct = WarehouseProduct::findOrFail($id);
            $warehouseProduct->is_available_for_quick_delivery = !$warehouseProduct->is_available_for_quick_delivery;
            $warehouseProduct->save();

            $status = $warehouseProduct->is_available_for_quick_delivery ? 'enabled' : 'disabled';
            
            return response()->json([
                'success' => true,
                'message' => "Quick delivery {$status} for this product!",
                'status' => $warehouseProduct->is_available_for_quick_delivery
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle quick delivery error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update product status.']);
        }
    }

    /**
     * Bulk operations for warehouse products
     */
    public function bulkOperation(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'operation' => 'required|in:enable_quick_delivery,disable_quick_delivery,update_location,mark_reorder',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:warehouse_products,id',
            'location_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $count = 0;
            
            switch ($request->operation) {
                case 'enable_quick_delivery':
                    $count = WarehouseProduct::whereIn('id', $request->product_ids)
                        ->update(['is_available_for_quick_delivery' => true]);
                    break;
                    
                case 'disable_quick_delivery':
                    $count = WarehouseProduct::whereIn('id', $request->product_ids)
                        ->update(['is_available_for_quick_delivery' => false]);
                    break;
                    
                case 'mark_reorder':
                    $count = WarehouseProduct::whereIn('id', $request->product_ids)
                        ->update(['needs_reorder' => true]);
                    break;
                    
                case 'update_location':
                    if ($request->filled('location_data')) {
                        foreach ($request->product_ids as $id) {
                            if (isset($request->location_data[$id])) {
                                WarehouseProduct::where('id', $id)->update([
                                    'aisle' => $request->location_data[$id]['aisle'] ?? null,
                                    'rack' => $request->location_data[$id]['rack'] ?? null,
                                    'shelf' => $request->location_data[$id]['shelf'] ?? null,
                                    'location_code' => $request->location_data[$id]['location_code'] ?? null,
                                ]);
                                $count++;
                            }
                        }
                    }
                    break;
            }

            DB::commit();

            return back()->with('success', "Bulk operation completed successfully on {$count} products!");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bulk operation error: ' . $e->getMessage());
            return back()->with('error', 'Bulk operation failed. Please try again.');
        }
    }

    /**
     * Export inventory data
     */
    public function exportInventory(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            $products = WarehouseProduct::with('product')->get();
            
            $csvData = [];
            $csvData[] = [
                'Product ID', 'Product Name', 'SKU', 'Stock Quantity', 'Reserved Quantity', 
                'Available Quantity', 'Location', 'Expiry Date', 'Condition', 'Cost Price', 
                'Selling Price', 'Quick Delivery Enabled', 'Status'
            ];

            foreach ($products as $wp) {
                $csvData[] = [
                    $wp->product_id,
                    $wp->product->name ?? 'N/A',
                    $wp->product->sku ?? 'N/A',
                    $wp->stock_quantity,
                    $wp->reserved_quantity,
                    $wp->available_quantity,
                    $wp->location_full,
                    $wp->expiry_date ? Carbon::parse($wp->expiry_date)->format('Y-m-d') : 'N/A',
                    $wp->condition,
                    $wp->cost_price,
                    $wp->selling_price,
                    $wp->is_available_for_quick_delivery ? 'Yes' : 'No',
                    $wp->stock_status
                ];
            }

            $filename = 'warehouse_inventory_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}",
            ]);

        } catch (\Exception $e) {
            Log::error('Export inventory error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export inventory. Please try again.');
        }
    }
}