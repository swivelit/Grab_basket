<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotelOwner;
use App\Models\FoodItem;
use App\Models\FoodCart;
use App\Models\FoodCartItem;
use Illuminate\Support\Facades\Auth;

class FoodShopController extends Controller
{
    /**
     * Display a listing of the shops (restaurants).
     */
    /**
     * Display a listing of the shops (restaurants).
     */
    public function index()
    {
        // Fetch ALL shops as requested (Removed status filters)
        $shops = HotelOwner::paginate(12);

        return view('food.shops.index', compact('shops'));
    }

    /**
     * Display the specified shop and its food items.
     */
    /**
     * Display the specified shop and its food items.
     */
    public function show($id)
    {
        // Fetch shop with ALL food items
        $shop = HotelOwner::with('foodItems')->findOrFail($id);

        // Group items by category for Swiggy-like layout
        $groupedItems = $shop->foodItems->groupBy('category');

        return view('food.shops.show', compact('shop', 'groupedItems'));
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to add items to cart.');
        }

        $foodItem = FoodItem::findOrFail($id);
        $user = Auth::user();

        // Get or create cart for user
        $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);

        // Check if item already exists in cart
        $cartItem = FoodCartItem::where('food_cart_id', $cart->id)
            ->where('food_item_id', $foodItem->id)
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            FoodCartItem::create([
                'food_cart_id' => $cart->id,
                'food_item_id' => $foodItem->id,
                'quantity' => 1,
                'price' => $foodItem->getFinalPrice(),
                'name' => $foodItem->name,
                'image_url' => $foodItem->first_image_url,
                'food_type' => $foodItem->food_type,
                'category' => $foodItem->category,
                'hotel_owner_id' => $foodItem->hotel_owner_id,
            ]);
        }

        return redirect()->back()->with('success', 'Item added to cart successfully!');
    }
}
