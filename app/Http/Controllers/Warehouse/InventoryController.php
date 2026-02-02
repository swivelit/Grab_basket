<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseProduct;
use App\Models\WarehouseStockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Display inventory listing
     */
    public function index(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        try {
            $query = WarehouseProduct::with('product');

            // Filter by user's assigned areas if not manager
            if (!$user->hasPermission('manage_users')) {
                $assignedAreas = $user->assigned_areas ?? [];
                if (!empty($assignedAreas)) {
                    $query->whereIn('aisle', $assignedAreas);
                }
            }

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('barcode', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhereHas('product', function($productQuery) use ($search) {
                          $productQuery->where('name', 'like', "%{$search}%")
                                      ->orWhere('sku', 'like', "%{$search}%");
                      });
                });
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

            // Sort options
            $sortBy = $request->get('sort', 'updated_at');
            $sortDirection = $request->get('direction', 'desc');
            
            if (in_array($sortBy, ['current_stock', 'location', 'updated_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('updated_at', 'desc');
            }

            $products = $query->paginate(20);

            // Get summary statistics
            $stats = [
                'total' => WarehouseProduct::count(),
                'in_stock' => WarehouseProduct::where('current_stock', '>', 0)->count(),
                'low_stock' => WarehouseProduct::where('current_stock', '>', 0)
                                              ->whereRaw('current_stock <= reorder_level')->count(),
                'out_of_stock' => WarehouseProduct::where('current_stock', '<=', 0)->count(),
            ];

            return view('warehouse.inventory.index', compact('products', 'stats'));

        } catch (\Exception $e) {
            Log::error('Inventory index error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Unable to load inventory. Please try again.');
        }
    }

    /**
     * Show individual product
     */
    public function show($id)
    {
        $user = Auth::guard('warehouse')->user();
        
        try {
            $product = WarehouseProduct::with(['product', 'stockMovements' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            }])->findOrFail($id);

            // Check if user can access this product's location
            if (!$user->canManageProduct($product)) {
                abort(403, 'Access denied for this product location.');
            }

            return view('warehouse.inventory.show', compact('product'));

        } catch (\Exception $e) {
            Log::error('Inventory show error', [
                'user_id' => $user->id,
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Product not found or access denied.');
        }
    }

    /**
     * Show add stock form
     */
    public function showAddStock()
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('add_stock')) {
            abort(403, 'Access denied. Add stock permission required.');
        }

        return view('warehouse.inventory.add-stock');
    }

    /**
     * Add stock to products
     */
    public function addStock(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('add_stock')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:warehouse_products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'reason' => 'required|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $product = WarehouseProduct::findOrFail($request->product_id);
            
            // Check if user can manage this product
            if (!$user->canManageProduct($product)) {
                return response()->json(['success' => false, 'message' => 'Access denied for this product location']);
            }

            $quantityBefore = $product->current_stock;
            $quantityAdded = $request->quantity;
            $quantityAfter = $quantityBefore + $quantityAdded;

            // Update product stock
            $product->update([
                'current_stock' => $quantityAfter,
                'cost_price' => $request->cost_price ?? $product->cost_price,
                'expiry_date' => $request->expiry_date ?? $product->expiry_date,
                'updated_by' => $user->name,
            ]);

            // Create stock movement record
            WarehouseStockMovement::create([
                'warehouse_product_id' => $product->id,
                'movement_type' => 'stock_in',
                'quantity_before' => $quantityBefore,
                'quantity_changed' => $quantityAdded,
                'quantity_after' => $quantityAfter,
                'reason' => $request->reason,
                'performed_by' => $user->name,
                'notes' => 'Stock added via inventory management',
                'cost_per_unit' => $request->cost_price ?? $product->cost_price,
            ]);

            DB::commit();

            Log::info('Stock added successfully', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity_added' => $quantityAdded,
                'new_stock' => $quantityAfter
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully added {$quantityAdded} units. New stock: {$quantityAfter}",
                'new_stock' => $quantityAfter
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Add stock error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to add stock. Please try again.'
            ]);
        }
    }

    /**
     * Show adjust stock form
     */
    public function showAdjustStock()
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('adjust_stock')) {
            abort(403, 'Access denied. Adjust stock permission required.');
        }

        return view('warehouse.inventory.adjust-stock');
    }

    /**
     * Adjust stock quantities
     */
    public function adjustStock(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('adjust_stock')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:warehouse_products,id',
            'new_quantity' => 'required|integer|min:0|max:50000',
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $product = WarehouseProduct::findOrFail($request->product_id);
            
            // Check if user can manage this product
            if (!$user->canManageProduct($product)) {
                return response()->json(['success' => false, 'message' => 'Access denied for this product location']);
            }

            $quantityBefore = $product->current_stock;
            $newQuantity = $request->new_quantity;
            $quantityChanged = $newQuantity - $quantityBefore;

            // Update product stock
            $product->update([
                'current_stock' => $newQuantity,
                'updated_by' => $user->name,
            ]);

            // Create stock movement record
            WarehouseStockMovement::create([
                'warehouse_product_id' => $product->id,
                'movement_type' => 'adjustment',
                'quantity_before' => $quantityBefore,
                'quantity_changed' => $quantityChanged,
                'quantity_after' => $newQuantity,
                'reason' => $request->reason,
                'performed_by' => $user->name,
                'notes' => 'Stock adjusted via inventory management',
            ]);

            DB::commit();

            Log::info('Stock adjusted successfully', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $newQuantity,
                'adjustment' => $quantityChanged
            ]);

            $changeType = $quantityChanged >= 0 ? 'increased' : 'decreased';
            $changeAmount = abs($quantityChanged);

            return response()->json([
                'success' => true,
                'message' => "Stock {$changeType} by {$changeAmount} units. New stock: {$newQuantity}",
                'new_stock' => $newQuantity
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Adjust stock error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to adjust stock. Please try again.'
            ]);
        }
    }

    /**
     * Update product details
     */
    public function update(Request $request, $id)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_locations')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string|max:50',
            'aisle' => 'nullable|string|max:10',
            'condition' => 'required|in:new,good,fair,damaged',
            'reorder_level' => 'required|integer|min:0',
            'is_quick_delivery' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            $product = WarehouseProduct::findOrFail($id);
            
            // Check if user can manage this product
            if (!$user->canManageProduct($product)) {
                return response()->json(['success' => false, 'message' => 'Access denied for this product location']);
            }

            $product->update([
                'location' => $request->location,
                'aisle' => $request->aisle,
                'condition' => $request->condition,
                'reorder_level' => $request->reorder_level,
                'is_quick_delivery' => $request->boolean('is_quick_delivery'),
                'updated_by' => $user->name,
            ]);

            Log::info('Product updated successfully', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'changes' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Product update error', [
                'user_id' => $user->id,
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update product. Please try again.'
            ]);
        }
    }

    /**
     * Search products for AJAX requests
     */
    public function search(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 2 characters.',
                ]);
            }

            $products = WarehouseProduct::with('product')
                ->where(function($q) use ($query) {
                    $q->where('barcode', 'like', "%{$query}%")
                      ->orWhere('location', 'like', "%{$query}%")
                      ->orWhereHas('product', function($productQuery) use ($query) {
                          $productQuery->where('name', 'like', "%{$query}%")
                                      ->orWhere('sku', 'like', "%{$query}%");
                      });
                });

            // Filter by user's assigned areas if not manager
            if (!$user->hasPermission('manage_users')) {
                $assignedAreas = $user->assigned_areas ?? [];
                if (!empty($assignedAreas)) {
                    $products->whereIn('aisle', $assignedAreas);
                }
            }

            $results = $products->take(20)->get()->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->product->name ?? 'Unknown Product',
                    'sku' => $product->product->sku ?? 'No SKU',
                    'barcode' => $product->barcode,
                    'location' => $product->location,
                    'current_stock' => $product->current_stock,
                    'status' => $product->current_stock > 0 ? 'in_stock' : 'out_of_stock',
                    'is_quick_delivery' => $product->is_quick_delivery,
                ];
            });

            return response()->json([
                'success' => true,
                'results' => $results,
                'count' => $results->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Product search error', [
                'user_id' => $user->id,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Search failed. Please try again.',
            ]);
        }
    }
}
