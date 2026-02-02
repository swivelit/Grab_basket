<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller
{
    /**
     * Get user's cart
     */
    public function index(Request $request)
    {
        $cartItems = CartItem::with(['product.productImages', 'product.category'])
            ->where('user_id', $request->user()->id)
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        $itemCount = $cartItems->sum('quantity');
        
        // Check if quick delivery is available
        $quickDeliveryAvailable = $cartItems->every(function ($item) {
            return $item->product->quick_delivery_available ?? false;
        });

        $deliveryCharges = $cartItems->some(function ($item) {
            return $item->delivery_type === 'quick';
        }) ? 25 : 0;

        return response()->json([
            'success' => true,
            'items' => $cartItems,
            'total' => $total,
            'item_count' => $itemCount,
            'delivery_charges' => $deliveryCharges,
            'quick_delivery_available' => $quickDeliveryAvailable,
            'grand_total' => $total + $deliveryCharges,
        ]);
    }

    /**
     * Add item to cart
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'delivery_type' => 'in:standard,quick',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($request->product_id);
        
        if (!$product || !$product->seller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Product not available',
            ], 404);
        }

        // Check stock availability
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
            ], 400);
        }

        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Update existing cart item
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock',
                ], 400);
            }

            $cartItem->update([
                'quantity' => $newQuantity,
                'delivery_type' => $request->get('delivery_type', 'standard'),
            ]);
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'delivery_type' => $request->get('delivery_type', 'standard'),
            ]);
        }

        $cartItem->load(['product.productImages']);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'cart_item' => $cartItem,
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Check stock availability
        if ($cartItem->product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
            ], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->load(['product.productImages']);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated',
            'cart_item' => $cartItem,
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request, $id)
    {
        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
        ]);
    }

    /**
     * Clear entire cart
     */
    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
        ]);
    }

    /**
     * Switch delivery type for cart item
     */
    public function switchDeliveryType(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delivery_type' => 'required|in:standard,quick',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cartItem = CartItem::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Check if quick delivery is available for this product
        if ($request->delivery_type === 'quick' && !($cartItem->product->quick_delivery_available ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Quick delivery not available for this product',
            ], 400);
        }

        $cartItem->update(['delivery_type' => $request->delivery_type]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery type updated',
            'cart_item' => $cartItem,
        ]);
    }
}