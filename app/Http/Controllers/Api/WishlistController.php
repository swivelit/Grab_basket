<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index()
    {
        $user = Auth::user();
        $wishlistItems = $user->wishlists()->with('product')->get();

        return response()->json([
            'wishlist' => $wishlistItems
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Check if already in wishlist
        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Product already in wishlist',
                'wishlist_item' => $existing
            ]);
        }

        $wishlistItem = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'message' => 'Product added to wishlist',
            'wishlist_item' => $wishlistItem->load('product')
        ]);
    }

    /**
     * Remove product from wishlist
     */
    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$wishlistItem) {
            return response()->json(['message' => 'Product not found in wishlist'], 404);
        }

        $wishlistItem->delete();

        return response()->json([
            'message' => 'Product removed from wishlist'
        ]);
    }

    /**
     * Toggle product in wishlist (add if not exists, remove if exists)
     */
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'message' => 'Product removed from wishlist',
                'action' => 'removed'
            ]);
        } else {
            $wishlistItem = Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
            ]);

            return response()->json([
                'message' => 'Product added to wishlist',
                'action' => 'added',
                'wishlist_item' => $wishlistItem->load('product')
            ]);
        }
    }

    /**
     * Move wishlist item to cart
     */
    public function moveToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Remove from wishlist
        Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->delete();

        // Add to cart (assuming CartController has add method)
        $cartController = new CartController();
        return $cartController->add($request);
    }
}