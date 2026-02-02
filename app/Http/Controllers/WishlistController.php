<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with('product.category', 'product.subcategory')
            ->where('user_id', Auth::id())
            ->get();

        return view('wishlist.index', compact('wishlists'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
        ]);

        if ($wishlist->wasRecentlyCreated) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist',
                'action' => 'added'
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Product already in wishlist',
                'action' => 'exists'
            ]);
        }
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist',
                'action' => 'removed'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found in wishlist'
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist',
                'action' => 'removed',
                'in_wishlist' => false
            ]);
        } else {
            Wishlist::create([
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist',
                'action' => 'added',
                'in_wishlist' => true
            ]);
        }
    }

    public function moveToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in wishlist'
            ]);
        }

        $product = Product::findOrFail($request->product_id);

        // Add to cart
        $cartController = new CartController();
        $cartRequest = new Request(['product_id' => $product->id, 'quantity' => 1]);
        $cartController->add($cartRequest);

        // Remove from wishlist
        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product moved to cart'
        ]);
    }

    public function checkStatus(Product $product)
    {
        $inWishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->exists();

        return response()->json([
            'in_wishlist' => $inWishlist
        ]);
    }

    public function count()
    {
        $count = Wishlist::where('user_id', Auth::id())->count();
        
        return response()->json([
            'count' => $count
        ]);
    }
}