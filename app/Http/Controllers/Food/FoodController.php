<?php

namespace App\Http\Controllers\Food;

use App\Http\Controllers\Controller;
use App\Models\HotelOwner;
use App\Models\FoodItem;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function index()
    {
        // Get all active and verified hotel owners
        $restaurants = HotelOwner::where('is_active', true)
            ->where('is_verified', true)
            ->where('status', 'approved')
            ->with(['foodItems' => function($query) {
                $query->where('is_available', true);
            }])
            ->get();

        return view('food.index', compact('restaurants'));
    }

    public function restaurants()
    {
        $restaurants = HotelOwner::where('is_active', true)
            ->where('is_verified', true)
            ->where('status', 'approved')
            ->paginate(12);

        return view('food.restaurants', compact('restaurants'));
    }

    public function restaurant(HotelOwner $hotelOwner)
    {
        if (!$hotelOwner->is_active || !$hotelOwner->is_verified || $hotelOwner->status !== 'approved') {
            abort(404, 'Restaurant not found');
        }

        $foodItems = $hotelOwner->foodItems()
            ->where('is_available', true)
            ->orderBy('is_popular', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $categories = $foodItems->keys();

        return view('food.restaurant', compact('hotelOwner', 'foodItems', 'categories'));
    }

    public function category($category)
    {
        $foodItems = FoodItem::where('category', $category)
            ->where('is_available', true)
            ->with('hotelOwner')
            ->whereHas('hotelOwner', function($query) {
                $query->where('is_active', true)
                    ->where('is_verified', true)
                    ->where('status', 'approved');
            })
            ->paginate(20);

        return view('food.category', compact('foodItems', 'category'));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'food_item_id' => 'required|exists:food_items,id',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $foodItem = FoodItem::with('hotelOwner')->findOrFail($request->food_item_id);

        if (!$foodItem->is_available) {
            return response()->json(['error' => 'This item is currently unavailable'], 400);
        }

        if (!$foodItem->hotelOwner->isOpen()) {
            return response()->json(['error' => 'This restaurant is currently closed'], 400);
        }

        // Add to cart logic (similar to existing cart functionality)
        $cart = session()->get('food_cart', []);
        $itemId = $foodItem->id;

        if (isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] += $request->quantity;
        } else {
            $cart[$itemId] = [
                'id' => $foodItem->id,
                'name' => $foodItem->name,
                'price' => $foodItem->getFinalPrice(),
                'quantity' => $request->quantity,
                'image' => $foodItem->images[0] ?? null,
                'restaurant_name' => $foodItem->hotelOwner->restaurant_name,
                'restaurant_id' => $foodItem->hotelOwner->id,
                'type' => 'food',
            ];
        }

        session()->put('food_cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'cart_count' => count($cart),
        ]);
    }
}