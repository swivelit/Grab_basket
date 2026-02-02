<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockMovementController extends Controller
{
    /**
     * Display stock movements listing
     */
    public function index(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        try {
            $query = WarehouseStockMovement::with(['warehouseProduct.product']);

            // Filter by user if not manager
            if (!$user->hasPermission('manage_users')) {
                $query->where('performed_by', $user->name);
            }

            // Apply filters
            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('performed_by', 'like', "%{$search}%")
                      ->orWhere('reason', 'like', "%{$search}%")
                      ->orWhereHas('warehouseProduct.product', function($productQuery) use ($search) {
                          $productQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $movements = $query->orderBy('created_at', 'desc')->paginate(25);

            // Get movement statistics
            $stats = [
                'total_movements' => $query->count(),
                'stock_in' => WarehouseStockMovement::where('movement_type', 'stock_in')->count(),
                'stock_out' => WarehouseStockMovement::where('movement_type', 'stock_out')->count(),
                'adjustments' => WarehouseStockMovement::where('movement_type', 'adjustment')->count(),
            ];

            return view('warehouse.stock-movements.index', compact('movements', 'stats'));

        } catch (\Exception $e) {
            return back()->with('error', 'Unable to load stock movements. Please try again.');
        }
    }

    /**
     * Show individual stock movement details
     */
    public function show($id)
    {
        $user = Auth::guard('warehouse')->user();
        
        try {
            $movement = WarehouseStockMovement::with(['warehouseProduct.product'])->findOrFail($id);

            // Check access permissions
            if (!$user->hasPermission('manage_users') && $movement->performed_by !== $user->name) {
                abort(403, 'Access denied to this stock movement record.');
            }

            return view('warehouse.stock-movements.show', compact('movement'));

        } catch (\Exception $e) {
            return back()->with('error', 'Stock movement record not found or access denied.');
        }
    }
}
