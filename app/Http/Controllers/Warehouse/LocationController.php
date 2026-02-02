<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Display warehouse locations
     */
    public function index(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_locations')) {
            abort(403, 'Access denied. Location management permission required.');
        }

        try {
            // Get location statistics
            $locationStats = $this->getLocationStatistics();
            
            // Get locations with product counts
            $locations = $this->getLocationsWithCounts($request);
            
            // Get unassigned products
            $unassignedCount = WarehouseProduct::whereNull('location')
                ->orWhere('location', '')
                ->count();
            
            return view('warehouse.locations.index', compact(
                'locationStats',
                'locations',
                'unassignedCount'
            ));

        } catch (\Exception $e) {
            Log::error('Location index error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Unable to load location data. Please try again.');
        }
    }

    /**
     * Store a new location
     */
    public function store(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_locations')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'aisle' => 'required|string|max:10',
            'rack' => 'nullable|string|max:10',
            'shelf' => 'nullable|string|max:10',
            'bin' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
            'is_quick_delivery' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            // Build location string
            $locationParts = array_filter([
                $request->aisle,
                $request->rack,
                $request->shelf,
                $request->bin
            ]);
            $location = implode('-', $locationParts);

            // Check if location already exists
            $existingCount = WarehouseProduct::where('location', $location)->count();
            if ($existingCount > 0) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Location already exists with products assigned.'
                ]);
            }

            // For this demo, we'll create a virtual location record
            // In a real system, you might have a separate locations table
            
            Log::info('Location created', [
                'location' => $location,
                'created_by' => $user->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location created successfully!',
                'location' => [
                    'location' => $location,
                    'aisle' => $request->aisle,
                    'rack' => $request->rack,
                    'shelf' => $request->shelf,
                    'bin' => $request->bin,
                    'description' => $request->description,
                    'is_quick_delivery' => $request->boolean('is_quick_delivery'),
                    'is_active' => $request->boolean('is_active', true)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Location creation error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to create location. Please try again.'
            ]);
        }
    }

    /**
     * Update location
     */
    public function update(Request $request, $locationId)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_locations')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:255',
            'is_quick_delivery' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            // Update products in this location
            $updatedCount = WarehouseProduct::where('location', $locationId)
                ->update([
                    'is_quick_delivery' => $request->boolean('is_quick_delivery'),
                    'updated_by' => $user->name
                ]);

            Log::info('Location updated', [
                'location' => $locationId,
                'updated_by' => $user->id,
                'products_affected' => $updatedCount,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Location updated successfully! {$updatedCount} products affected."
            ]);

        } catch (\Exception $e) {
            Log::error('Location update error', [
                'user_id' => $user->id,
                'location' => $locationId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update location. Please try again.'
            ]);
        }
    }

    /**
     * Move products between locations
     */
    public function moveProducts(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_locations')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'from_location' => 'nullable|string|max:50',
            'to_location' => 'required|string|max:50',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:warehouse_products,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $productIds = $request->product_ids;
            $fromLocation = $request->from_location;
            $toLocation = $request->to_location;
            $reason = $request->reason ?? 'Location change';

            // Update product locations
            $updatedCount = WarehouseProduct::whereIn('id', $productIds)
                ->when($fromLocation, function($query) use ($fromLocation) {
                    return $query->where('location', $fromLocation);
                })
                ->update([
                    'location' => $toLocation,
                    'updated_by' => $user->name
                ]);

            // Create stock movement records for location changes
            foreach ($productIds as $productId) {
                $product = WarehouseProduct::find($productId);
                if ($product) {
                    \App\Models\WarehouseStockMovement::create([
                        'warehouse_product_id' => $productId,
                        'movement_type' => 'transfer',
                        'quantity_before' => $product->current_stock,
                        'quantity_changed' => 0,
                        'quantity_after' => $product->current_stock,
                        'reason' => "Location moved from '{$fromLocation}' to '{$toLocation}': {$reason}",
                        'performed_by' => $user->name,
                        'notes' => "Location change operation"
                    ]);
                }
            }

            DB::commit();

            Log::info('Products moved between locations', [
                'moved_by' => $user->id,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'products_count' => $updatedCount,
                'product_ids' => $productIds
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} products moved to {$toLocation} successfully!"
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Product move error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to move products. Please try again.'
            ]);
        }
    }

    /**
     * Get products in a specific location
     */
    public function getLocationProducts(Request $request, $location)
    {
        $user = Auth::guard('warehouse')->user();

        try {
            $query = WarehouseProduct::with('product')
                ->where('location', $location);

            // Filter by user's assigned areas if not manager
            if (!$user->hasPermission('manage_users')) {
                $assignedAreas = $user->assigned_areas ?? [];
                if (!empty($assignedAreas)) {
                    $query->whereIn('aisle', $assignedAreas);
                }
            }

            $products = $query->orderBy('product_id')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Location products fetch error', [
                'user_id' => $user->id,
                'location' => $location,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch products for this location.'
            ]);
        }
    }

    /**
     * Optimize locations for quick delivery
     */
    public function optimizeQuickDelivery()
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_quick_delivery')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            DB::beginTransaction();

            // Get products that should be in quick delivery locations
            $quickProducts = WarehouseProduct::with('product')
                ->where('is_quick_delivery', true)
                ->where('current_stock', '>', 0)
                ->whereNotIn('aisle', ['A', 'B']) // Assume A and B are quick delivery aisles
                ->get();

            $moved = 0;
            foreach ($quickProducts as $product) {
                // Find available quick delivery location
                $newLocation = $this->findAvailableQuickLocation();
                
                if ($newLocation) {
                    $product->update([
                        'location' => $newLocation,
                        'aisle' => substr($newLocation, 0, 1),
                        'updated_by' => $user->name
                    ]);

                    // Create movement record
                    \App\Models\WarehouseStockMovement::create([
                        'warehouse_product_id' => $product->id,
                        'movement_type' => 'transfer',
                        'quantity_before' => $product->current_stock,
                        'quantity_changed' => 0,
                        'quantity_after' => $product->current_stock,
                        'reason' => 'Quick delivery optimization',
                        'performed_by' => $user->name,
                        'notes' => "Moved to quick delivery location: {$newLocation}"
                    ]);

                    $moved++;
                }
            }

            DB::commit();

            Log::info('Quick delivery locations optimized', [
                'optimized_by' => $user->id,
                'products_moved' => $moved
            ]);

            return response()->json([
                'success' => true,
                'message' => "Optimization complete! {$moved} products moved to quick delivery locations."
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Quick delivery optimization error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Optimization failed. Please try again.'
            ]);
        }
    }

    /**
     * Get location statistics
     */
    private function getLocationStatistics(): array
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
        $assignedProducts = $query->whereNotNull('location')
            ->where('location', '!=', '')
            ->count();
        
        $quickDeliveryLocations = $query->where('is_quick_delivery', true)
            ->distinct('location')
            ->count('location');
        
        $activeLocations = $query->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct('location')
            ->count('location');

        return [
            'total_products' => $totalProducts,
            'assigned_products' => $assignedProducts,
            'unassigned_products' => $totalProducts - $assignedProducts,
            'active_locations' => $activeLocations,
            'quick_delivery_locations' => $quickDeliveryLocations,
            'assignment_percentage' => $totalProducts > 0 ? round(($assignedProducts / $totalProducts) * 100, 1) : 0,
        ];
    }

    /**
     * Get locations with product counts
     */
    private function getLocationsWithCounts(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user = Auth::guard('warehouse')->user();
        
        $query = WarehouseProduct::select(
                'location',
                'aisle',
                DB::raw('COUNT(*) as product_count'),
                DB::raw('SUM(current_stock) as total_stock'),
                DB::raw('AVG(CASE WHEN is_quick_delivery THEN 1 ELSE 0 END) as quick_delivery_ratio'),
                DB::raw('COUNT(CASE WHEN current_stock > 0 THEN 1 END) as in_stock_count'),
                DB::raw('COUNT(CASE WHEN current_stock <= 0 THEN 1 END) as out_stock_count')
            )
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location', 'aisle');

        // Filter by user's assigned areas if not manager
        if (!$user->hasPermission('manage_users')) {
            $assignedAreas = $user->assigned_areas ?? [];
            if (!empty($assignedAreas)) {
                $query->whereIn('aisle', $assignedAreas);
            }
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('location', 'like', "%{$search}%");
        }

        // Aisle filter
        if ($request->filled('aisle')) {
            $query->where('aisle', $request->aisle);
        }

        return $query->orderBy('location')
            ->paginate(20);
    }

    /**
     * Find available quick delivery location
     */
    private function findAvailableQuickLocation(): ?string
    {
        $quickAisles = ['A', 'B'];
        $maxRack = 10;
        $maxShelf = 5;

        foreach ($quickAisles as $aisle) {
            for ($rack = 1; $rack <= $maxRack; $rack++) {
                for ($shelf = 1; $shelf <= $maxShelf; $shelf++) {
                    $location = "{$aisle}-{$rack}-{$shelf}";
                    
                    // Check if location is available (has space)
                    $productCount = WarehouseProduct::where('location', $location)->count();
                    
                    if ($productCount < 10) { // Assume max 10 products per location
                        return $location;
                    }
                }
            }
        }

        return null;
    }
}