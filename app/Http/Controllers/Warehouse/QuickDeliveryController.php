<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickDeliveryController extends Controller
{
    /**
     * Display quick delivery management
     */
    public function index(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_quick_delivery')) {
            abort(403, 'Access denied. Quick delivery management permission required.');
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

            // Quick delivery filter
            if ($request->filled('status')) {
                if ($request->status === 'enabled') {
                    $query->where('is_quick_delivery', true);
                } else {
                    $query->where('is_quick_delivery', false);
                }
            }

            $products = $query->orderBy('is_quick_delivery', 'desc')
                             ->orderBy('current_stock', 'desc')
                             ->paginate(20);

            // Get quick delivery statistics
            $stats = [
                'total_products' => WarehouseProduct::count(),
                'quick_delivery_enabled' => WarehouseProduct::where('is_quick_delivery', true)->count(),
                'quick_delivery_in_stock' => WarehouseProduct::where('is_quick_delivery', true)
                                                           ->where('current_stock', '>', 0)->count(),
            ];

            return view('warehouse.quick-delivery.index', compact('products', 'stats'));

        } catch (\Exception $e) {
            return back()->with('error', 'Unable to load quick delivery data. Please try again.');
        }
    }

    /**
     * Toggle quick delivery status for a product
     */
    public function toggle(Request $request, $id)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_quick_delivery')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            $product = WarehouseProduct::findOrFail($id);
            
            // Check if user can manage this product
            if (!$user->canManageProduct($product)) {
                return response()->json(['success' => false, 'message' => 'Access denied for this product location']);
            }

            $newStatus = !$product->is_quick_delivery;
            
            $product->update([
                'is_quick_delivery' => $newStatus,
                'updated_by' => $user->name,
            ]);

            $statusText = $newStatus ? 'enabled' : 'disabled';
            
            return response()->json([
                'success' => true,
                'message' => "Quick delivery {$statusText} for {$product->product->name}",
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update quick delivery status.'
            ]);
        }
    }

    /**
     * Optimize quick delivery locations
     */
    public function optimize()
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_quick_delivery')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            // This is a simplified optimization
            // In a real system, this would involve complex algorithms
            
            $optimizedCount = WarehouseProduct::where('is_quick_delivery', true)
                ->where('current_stock', '>', 0)
                ->whereNotIn('aisle', ['A', 'B']) // Assume A and B are quick delivery aisles
                ->update([
                    'aisle' => DB::raw("CASE WHEN id % 2 = 0 THEN 'A' ELSE 'B' END"),
                    'updated_by' => $user->name
                ]);

            return response()->json([
                'success' => true,
                'message' => "Optimized {$optimizedCount} products for quick delivery locations."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Optimization failed. Please try again.'
            ]);
        }
    }
}
