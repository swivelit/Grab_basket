<?php

// namespace App\Http\Controllers\Customer;

// use App\Http\Controllers\Controller;
// use App\Models\FoodItem;
// use App\Models\FoodOrder;
// use App\Models\FoodOrderItem;
// use App\Models\FoodCart;
// use App\Models\FoodCartItem;
// use App\Models\HotelOwner;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Log; // 👈 ADD THIS

// class CustomerFoodController extends Controller
// {
//     public function index(Request $request)
//     {
//         $now = now();
//         $currentTime = $now->format('H:i:s');
//         $today = strtolower($now->format('l'));

//         $foodCategories = FoodItem::where('is_available', 1)
//             ->select('category')
//             ->distinct()
//             ->orderBy('category')
//             ->get();

//         $query = FoodItem::with('hotelOwner')
//             ->where('is_available', 1)
//             ->whereHas('hotelOwner', function ($q) use ($currentTime, $today) {
//                 $q->where('is_active', true)
//                     ->whereNotNull('opening_time')
//                     ->whereNotNull('closing_time')
//                     ->whereRaw("JSON_CONTAINS(operating_days, '" . json_encode($today) . "')")
//                     ->where('opening_time', '<=', $currentTime)
//                     ->where('closing_time', '>=', $currentTime);
//             });

//         $search = $request->input('search');
//         $category = $request->input('category');
//         $vegFilter = $request->input('veg');

//         if ($search) {
//             $query->where('name', 'LIKE', "%{$search}%");
//         } else {
//             if ($category) {
//                 $query->where('category', $category);
//             }
//             if ($vegFilter === '1') {
//                 $query->where('food_type', 'veg');
//             } elseif ($vegFilter === '0') {
//                 $query->where('food_type', 'non-veg');
//             }
//         }

//         $sort = $request->input('sort');
//         if ($sort === 'costLow') {
//             $query->orderBy('price', 'asc');
//         } elseif ($sort === 'costHigh') {
//             $query->orderBy('price', 'desc');
//         } elseif ($sort === 'ratingHigh') {
//             $query->orderBy('rating', 'desc');
//         } else {
//             $query->latest();
//         }

//         $foods = $query->get();
//         return view('customer.food.index', compact('foodCategories', 'foods'));
//     }

//     public function ajaxIndex(Request $request)
//     {
//         $query = FoodItem::with('hotelOwner')->where('is_available', 1);

//         $search = $request->input('search');
//         $category = $request->input('category');
//         $vegFilter = $request->input('veg');
//         $sort = $request->input('sort');

//         if ($search) {
//             $query->where('name', 'LIKE', "%{$search}%");
//         } else {
//             if ($category) $query->where('category', $category);
//             if ($vegFilter === '1') $query->where('food_type', 'veg');
//             elseif ($vegFilter === '0') $query->where('food_type', 'non-veg');
//         }

//         if ($sort === 'costLow') $query->orderBy('price', 'asc');
//         elseif ($sort === 'costHigh') $query->orderBy('price', 'desc');
//         elseif ($sort === 'ratingHigh') $query->orderBy('rating', 'desc');
//         else $query->latest();

//         $foods = $query->get();

//         return response()->json([
//             'html' => view('customer.food.partials.food-cards', compact('foods'))->render(),
//             'count' => $foods->count()
//         ]);
//     }

//     public function category($categoryName)
//     {
//         $foods = FoodItem::with('hotelOwner')
//             ->where('category', $categoryName)
//             ->where('is_available', 1)
//             ->latest()
//             ->paginate(20);

//         return view('customer.food.category', compact('foods', 'categoryName'));
//     }

//     public function details($id)
//     {
//         $food = FoodItem::with('hotelOwner')->findOrFail($id);
//         return view('customer.food.details', compact('food'));
//     }

//     public function cartAdd(Request $request)
//     {
//         $request->validate([
//             'food_id' => 'required|exists:food_items,id'
//         ]);

//         $user = Auth::user();

//         // ✅ Load food with hotel_owner_id (and only needed fields)
//         $food = FoodItem::select(
//             'id',
//             'name',
//             'price',
//             'discounted_price',
//             'food_type',
//             'category',
//             'hotel_owner_id',
//             'images'
//         )->findOrFail($request->food_id);

//         // ✅ Critical: Validate hotel_owner_id is valid
//         if (!$food->hotel_owner_id || !\App\Models\HotelOwner::where('id', $food->hotel_owner_id)->exists()) {
//             return back()->with('error', 'This food item is not available from a valid restaurant.');
//         }

//         $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);

//         // Enforce single restaurant
//         $existingItem = $cart->items()->first();
//         if ($existingItem && $existingItem->hotel_owner_id !== $food->hotel_owner_id) {
//             $cart->items()->delete();
//         }

//         $cartItem = $cart->items()->where('food_item_id', $food->id)->first();

//         if ($cartItem) {
//             $cartItem->increment('quantity');
//         } else {
//             // ✅ SAVE ALL SNAPSHOT FIELDS — INCLUDING hotel_owner_id
//             FoodCartItem::create([
//                 'food_cart_id' => $cart->id,
//                 'food_item_id' => $food->id,
//                 'quantity' => 1,
//                 'price' => $food->getFinalPrice(),
//                 'name' => $food->name,
//                 'image_url' => $food->first_image_url, // uses your accessor
//                 'food_type' => $food->food_type,
//                 'category' => $food->category,
//                 'hotel_owner_id' => $food->hotel_owner_id, // ← THIS FIXES THE ERROR
//             ]);
//         }

//         return redirect()->route('customer.food.cart')->with('success', 'Added to cart!');
//     }
//     public function cartIndex()
//     {
//         $user = Auth::user();
//         // ✅ NO NEED to load foodItem relationship!
//         $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
//         $cartItems = $cart->items; // already has all data

//         // For JS product lookup (optional, but your JS uses it)
//         $foodsForJs = $cartItems->map(function ($item) {
//             return [
//                 'id' => $item->food_item_id,
//                 'name' => $item->name,
//                 'price' => (float) $item->price,
//                 'img' => $item->image_url ?? 'https://via.placeholder.com/150?text=No+Image',
//                 'desc' => \Illuminate\Support\Str::limit($item->category ?? 'Food', 40),
//                 'prep' => rand(10, 30),
//             ];
//         })->values();

//         // For cart data (matches old session structure)
//         $cartData = $cartItems->map(function ($item) {
//             return [
//                 'id' => $item->food_item_id,
//                 'name' => $item->name,
//                 'price' => (float) $item->price,
//                 'image' => $item->image_url,
//                 'quantity' => $item->quantity,
//                 'food_type' => $item->food_type,
//                 'category' => $item->category,
//                 'hotel_owner_id' => $item->hotel_owner_id,
//             ];
//         })->values()->all();

//         return view('customer.food.cart', compact('cartData', 'foodsForJs'));
//     }

//     public function cartUpdate(Request $request, $foodId)
//     {
//         $quantity = $request->integer('quantity') ?? $request->json('quantity');
//         if ($quantity === null || $quantity < 1) {
//             return response()->json(['error' => 'Invalid quantity'], 422);
//         }

//         $user = Auth::user();
//         $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
//         $cartItem = $cart->items()->where('food_item_id', $foodId)->first();

//         if ($cartItem) {
//             $cartItem->update(['quantity' => $quantity]);
//             return response()->json(['success' => true]);
//         }

//         return response()->json(['error' => 'Item not in cart'], 404);
//     }

//     public function cartRemove($foodId)
//     {
//         $user = Auth::user();
//         $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
//         $cart->items()->where('food_item_id', $foodId)->delete();

//         return request()->ajax()
//             ? response()->json(['success' => true])
//             : redirect()->route('customer.food.cart')->with('success', 'Removed!');
//     }

//     public function checkout()
//     {
//         $user = Auth::user();
//         $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
//         $cartItems = $cart->items;

//         if ($cartItems->isEmpty()) {
//             return redirect()->route('customer.food.cart')->with('error', 'Cart is empty.');
//         }

//         // Validate all same hotel (using snapshot)
//         $hotelOwnerId = $cartItems->first()->hotel_owner_id;
//         $allSame = $cartItems->every(fn($item) => $item->hotel_owner_id === $hotelOwnerId);
//         if (!$allSame) {
//             return back()->with('error', 'Multiple restaurants not allowed.');
//         }

//         $foodTotal = $cartItems->sum(fn($item) => $item->price * $item->quantity);
//         $deliveryFee = 50.00;
//         $total = $foodTotal + $deliveryFee;

//         $order = FoodOrder::create([
//             'hotel_owner_id' => $hotelOwnerId,
//             'customer_name' => $user->name ?? 'Customer',
//             'customer_phone' => '0123456789',
//             'delivery_address' => '123 Test Street',
//             'food_total' => $foodTotal,
//             'delivery_fee' => $deliveryFee,
//             'total_amount' => $total,
//             'status' => 'pending',
//             'estimated_delivery_time' => now()->addMinutes(10),
//         ]);

//         foreach ($cartItems as $item) {
//             FoodOrderItem::create([
//                 'food_order_id' => $order->id,
//                 'food_item_id' => $item->food_item_id,
//                 'food_name' => $item->name,        // ← from snapshot!
//                 'price' => $item->price,
//                 'quantity' => $item->quantity,
//                 'food_type' => $item->food_type,
//             ]);
//         }

//         $cart->items()->delete(); // clear cart
//         return redirect()->route('customer.food.order.success', $order->id);
//     }

//     public function showCheckout()
//     {
//         $user = Auth::user();
//         $cart = FoodCart::with('items')->firstOrCreate(['user_id' => $user->id]);
//         $cartItems = $cart->items;

//         if ($cartItems->isEmpty()) {
//             return redirect()->route('customer.food.cart')->with('error', 'Cart is empty.');
//         }

//         $hotelOwnerId = $cartItems->first()->hotel_owner_id;
//         if (!$cartItems->every(fn($item) => $item->hotel_owner_id === $hotelOwnerId)) {
//             return back()->with('error', 'Multiple restaurants not allowed.');
//         }

//         // ✅ CORRECTED: Use restaurant_name and restaurant_address
//         $hotel = HotelOwner::find($hotelOwnerId);
//         $hotelName = $hotel ? $hotel->restaurant_name : 'Unknown Restaurant';

//         $customerName = $user->name ?? 'Buyer';
//         $customerPhone = $user->phone ?? '0123456789';
//         $customerEmail = $user->email ?? 'user@example.com';
//         $deliveryAddress = $user->address ?? '123 Test Street';

//         $foodTotal = $cartItems->sum(fn($item) => $item->price * $item->quantity);
//         $deliveryFee = 50.00;
//         $total = $foodTotal + $deliveryFee;

//         return view('customer.food.checkout', compact(
//             'cartItems',
//             'foodTotal',
//             'deliveryFee',
//             'total',
//             'customerName',
//             'customerPhone',
//             'customerEmail',
//             'deliveryAddress',
//             'hotelName'
//         ));
//     }
//     // ✅ ONLY ONE placeOrder()
//     public function placeOrder(Request $request)
//     {
//         $user = Auth::user();
//         $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
//         $cartItems = $cart->items;

//         if ($cartItems->isEmpty()) {
//             return redirect()->route('customer.food.cart')->with('error', 'Cart is empty.');
//         }

//         $hotelOwnerId = $cartItems->first()->hotel_owner_id;
//         if (!$cartItems->every(fn($item) => $item->hotel_owner_id === $hotelOwnerId)) {
//             return back()->with('error', 'Multiple restaurants not allowed.');
//         }

//         $foodTotal = $cartItems->sum(fn($item) => $item->price * $item->quantity);
//         $deliveryFee = 50.00;
//         $total = $foodTotal + $deliveryFee;

//         // ✅ CORRECTED: Use restaurant_name and restaurant_address
//         $hotel = HotelOwner::find($hotelOwnerId);
//         $shopName = $hotel ? $hotel->restaurant_name : 'Unknown Shop';
//         $shopAddress = $hotel ? $hotel->restaurant_address : 'Address not available';

//         $deliveryAddress = $request->input('delivery_address', $user->address ?? '123 Test Street');
//         $customerPhone = $request->input('phone', $user->phone ?? '0123456789');
//         $customerEmail = $request->input('email', $user->email);
//         $paymentMethod = $request->input('payment_method', 'cod');

//         $order = FoodOrder::create([
//             'hotel_owner_id' => $hotelOwnerId,
//             'shop_name' => $shopName,
//             'shop_address' => $shopAddress,
//             'customer_name' => $user->name ?? 'Customer',
//             'customer_phone' => $customerPhone,
//             'customer_email' => $customerEmail,
//             'delivery_address' => $deliveryAddress,
//             'food_total' => $foodTotal,
//             'delivery_fee' => $deliveryFee,
//             'total_amount' => $total,
//             'payment_method' => $paymentMethod,
//             'status' => 'pending',
//             'estimated_delivery_time' => now()->addMinutes(10),
//         ]);

//         foreach ($cartItems as $item) {
//             FoodOrderItem::create([
//                 'food_order_id' => $order->id,
//                 'food_item_id' => $item->food_item_id,
//                 'food_name' => $item->name,
//                 'price' => $item->price,
//                 'quantity' => $item->quantity,
//                 'food_type' => $item->food_type,
//             ]);
//         }

//         $cart->items()->delete();
//         return redirect()->route('customer.food.order.success', $order->id);
//     }
//     // ✅ ONLY ONE orderSuccess()
//     public function orderSuccess($orderId)
//     {
//         $order = FoodOrder::with('items')->findOrFail($orderId);
//         return view('customer.food.order-success', compact('order'));
//     }


//  <?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\FoodCart;
use App\Models\FoodCartItem;
use App\Models\HotelOwner;
use App\Models\UserWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class CustomerFoodController extends Controller
{
    private $razorpayKeyId = 'rzp_live_RZLX30zmmnhHum';
    private $razorpayKeySecret = 'XKmsdH5PbR49EiT74CgehYYi';


    public function index(Request $request)
    {
        $now = now();

        $currentTime = $now->format('H:i:s');
        $today = strtolower($now->format('l'));

        $categoryMap = [
            'appetizer' => ['name' => 'Appetizer', 'icon' => 'appetizer.png'],
            'main_course' => ['name' => 'Main Course', 'icon' => 'main-course.png'],
            'dessert' => ['name' => 'Dessert', 'icon' => 'dessert.png'],
            'beverages' => ['name' => 'Beverage', 'icon' => 'beverage.png'],
            'snack' => ['name' => 'Snack', 'icon' => 'snack.png'],
            'salad' => ['name' => 'Salad', 'icon' => 'salad.png'],
            'soup' => ['name' => 'Soup', 'icon' => 'soup.png'],
            'staters' => ['name' => 'Starters', 'icon' => 'starters.png'],
            'rice' => ['name' => 'Rice', 'icon' => 'rice.png'],
            'chicken' => ['name' => 'Chicken', 'icon' => 'chicken.png'],
            'seefood' => ['name' => 'Seafood', 'icon' => 'seafood.png'],
            'burger' => ['name' => 'Burger', 'icon' => 'burger.png'],
            'pizza' => ['name' => 'Pizza', 'icon' => 'pizza.png'],
            'mutton' => ['name' => 'Mutton', 'icon' => 'mutton.png'],
            'briyani' => ['name' => 'Briyani', 'icon' => 'biryani.png'],
        ];

        $availableCategoryKeys = FoodItem::where('is_available', 1)
            ->distinct()
            ->pluck('category')
            ->filter(fn($key) => isset($categoryMap[$key]))
            ->values();

        $foodCategories = $availableCategoryKeys->map(fn($key) => [
            'id' => $key,
            'name' => $categoryMap[$key]['name'],
            'icon_image' => $categoryMap[$key]['icon'],
        ])->values();

        $query = FoodItem::with('hotelOwner')
            ->where('is_available', 1);
        // ->whereHas('hotelOwner', function ($q) {
        //     $q->where('is_active', true);
        // });

        $search = $request->search;
        $category = $request->category;
        $veg = $request->veg;
        $sort = $request->sort;

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        } else {
            if ($category)
                $query->where('category', $category);
            if ($veg === '1')
                $query->where('food_type', 'veg');
            if ($veg === '0')
                $query->where('food_type', 'non-veg');
        }

        match ($sort) {
            'costLow' => $query->orderBy('price', 'asc'),
            'costHigh' => $query->orderBy('price', 'desc'),
            'ratingHigh' => $query->orderBy('rating', 'desc'),
            default => $query->latest()
        };

        $foods = $query->get();

        $activeCategoryKey = $category ?: ($foodCategories->first()['id'] ?? null);

        // Use empty string placeholder if null
        $safeName = 'All';
        if ($activeCategoryKey && isset($categoryMap[$activeCategoryKey])) {
            $safeName = $categoryMap[$activeCategoryKey]['name'];
        }

        $categoryFoods = $foods; // Default to all if not specifically targeted
        if ($category) {
            $categoryFoods = $foods->where('category', $category);
        }

        $activeCategory = (object) [
            'id' => $activeCategoryKey,
            'name' => $safeName,
            'tenMinProducts' => $categoryFoods,
            'filteredSubcategories' => $categoryFoods
                ->pluck('subcategory')
                ->filter()
                ->unique()
                ->values()
                ->map(fn($name) => (object) ['id' => $name, 'name' => $name]),
        ];

        return view('customer.food.index', compact(
            'foodCategories',
            'foods',
            'activeCategory'
        ));
    }

    public function ajaxIndex(Request $request)
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $today = strtolower($now->format('l'));

        $query = FoodItem::with('hotelOwner')
            ->where('is_available', 1);
        // ->whereHas('hotelOwner', function ($q) {
        //     $q->where('is_active', true);
        // });

        $search = $request->input('search');
        $category = $request->input('category');
        $vegFilter = $request->input('veg');
        $sort = $request->input('sort');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        } else {
            if ($category)
                $query->where('category', $category);
            if ($vegFilter === '1')
                $query->where('food_type', 'veg');
            elseif ($vegFilter === '0')
                $query->where('food_type', 'non-veg');
        }

        if ($sort === 'costLow')
            $query->orderBy('price', 'asc');
        elseif ($sort === 'costHigh')
            $query->orderBy('price', 'desc');
        elseif ($sort === 'ratingHigh')
            $query->orderBy('rating', 'desc');
        else
            $query->latest();

        $foods = $query->get();

        return response()->json([
            'html' => view('customer.food.partials.food-cards', compact('foods'))->render(),
            'count' => $foods->count()
        ]);
    }

    public function category($categoryName)
    {
        $foods = FoodItem::with('hotelOwner')
            ->where('category', $categoryName)
            ->where('is_available', 1)
            ->latest()
            ->paginate(20);

        return view('customer.food.category', compact('foods', 'categoryName'));
    }

    public function details($id)
    {
        $food = FoodItem::with('hotelOwner')->findOrFail($id);
        return view('customer.food.details', compact('food'));
    }

    public function cartAdd(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:food_items,id'
        ]);

        $user = Auth::user();

        $food = FoodItem::select(
            'id',
            'name',
            'price',
            'discounted_price',
            'food_type',
            'category',
            'hotel_owner_id',
            'images'
        )->findOrFail($request->food_id);

        if (!$food->hotel_owner_id || !HotelOwner::where('id', $food->hotel_owner_id)->exists()) {
            return back()->with('error', 'This food item is not available from a valid restaurant.');
        }

        $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);

        // $existingItem = $cart->items()->first();
        // if ($existingItem && $existingItem->hotel_owner_id !== $food->hotel_owner_id) {
        //     $cart->items()->delete();
        // }

        $cartItem = $cart->items()->where('food_item_id', $food->id)->first();

        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            FoodCartItem::create([
                'food_cart_id' => $cart->id,
                'food_item_id' => $food->id,
                'quantity' => 1,
                'price' => $food->getFinalPrice(),
                'name' => $food->name,
                'image_url' => $food->first_image_url,
                'food_type' => $food->food_type,
                'category' => $food->category,
                'hotel_owner_id' => $food->hotel_owner_id,
            ]);
        }

        return redirect()->route('customer.food.cart')->with('success', 'Added to cart!');
    }

    public function cartIndex()
    {
        $user = Auth::user();
        $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
        $cartItems = $cart->items;

        $foodsForJs = $cartItems->map(function ($item) {
            return [
                'id' => $item->food_item_id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'img' => $item->image_url ?? 'https://via.placeholder.com/150?text=No+Image',
                'desc' => \Illuminate\Support\Str::limit($item->category ?? 'Food', 40),
                'prep' => rand(10, 30),
            ];
        })->values();

        $cartData = $cartItems->map(function ($item) {
            return [
                'id' => $item->food_item_id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'image' => $item->image_url,
                'quantity' => $item->quantity,
                'food_type' => $item->food_type,
                'category' => $item->category,
                'hotel_owner_id' => $item->hotel_owner_id,
            ];
        })->values()->all();

        $walletPoint = $user->wallet_point ?? 0;
        return view('customer.food.cart', compact('cartData', 'foodsForJs', 'walletPoint'));
    }

    public function cartUpdate(Request $request, $foodId)
    {
        $quantity = $request->integer('quantity') ?? $request->json('quantity');
        if ($quantity === null || $quantity < 1) {
            return response()->json(['error' => 'Invalid quantity'], 422);
        }

        $user = Auth::user();
        $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
        $cartItem = $cart->items()->where('food_item_id', $foodId)->first();

        if ($cartItem) {
            $cartItem->update(['quantity' => $quantity]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Item not in cart'], 404);
    }

    public function cartRemove($foodId)
    {
        $user = Auth::user();
        $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
        $cart->items()->where('food_item_id', $foodId)->delete();

        return request()->ajax()
            ? response()->json(['success' => true])
            : redirect()->route('customer.food.cart')->with('success', 'Removed!');
    }

    public function checkout()
    {
        $user = Auth::user();
        $cart = FoodCart::firstOrCreate(['user_id' => $user->id]);
        $cartItems = $cart->items;

        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.food.cart')->with('error', 'Cart is empty.');
        }

        $hotelOwnerId = $cartItems->first()->hotel_owner_id;
        $allSame = $cartItems->every(fn($item) => $item->hotel_owner_id === $hotelOwnerId);
        if (!$allSame) {
            return back()->with('error', 'Multiple restaurants not allowed.');
        }

        $foodTotal = $cartItems->sum(fn($item) => $item->price * $item->quantity);
        $deliveryFee = 50.00;
        $total = $foodTotal + $deliveryFee;

        $order = FoodOrder::create([
            'hotel_owner_id' => $hotelOwnerId,
            'customer_name' => $user->name ?? 'Customer',
            'customer_phone' => '0123456789',
            'delivery_address' => '123 Test Street',
            'food_total' => $foodTotal,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $total,
            'status' => 'pending',
            'estimated_delivery_time' => now()->addMinutes(10),
        ]);

        foreach ($cartItems as $item) {
            FoodOrderItem::create([
                'food_order_id' => $order->id,
                'food_item_id' => $item->food_item_id,
                'food_name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'food_type' => $item->food_type,
            ]);
        }

        $cart->items()->delete();
        return redirect()->route('customer.food.order.success', $order->id);
    }

    public function showCheckout()
    {
        $user = Auth::user();
        $cart = FoodCart::with('items')->firstOrCreate(['user_id' => $user->id]);
        $cartItems = $cart->items;

        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.food.cart')->with('error', 'Cart is empty.');
        }

        // Group items by seller/hotel owner
        $groupedItems = $cartItems->groupBy('hotel_owner_id');

        // Prepare seller groups with their items and totals
        $sellerGroups = [];
        foreach ($groupedItems as $hotelOwnerId => $items) {
            $hotel = HotelOwner::find($hotelOwnerId);
            // Use the correct field name from your model
            $restaurantName = $hotel ? $hotel->restaurant_name : 'Unknown Restaurant';

            $foodTotal = $items->sum(fn($item) => $item->price * $item->quantity);
            $deliveryFee = 50.00; // Fixed delivery fee per seller
            $tax = round($foodTotal * 0.05); // Integer rounding
            $total = $foodTotal + $deliveryFee + $tax;

            $sellerGroups[] = [
                'hotel_owner_id' => $hotelOwnerId,
                'restaurant_name' => $restaurantName,  // Use consistent field name
                'items' => $items,
                'food_total' => $foodTotal,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'total' => $total,
                'estimated_delivery_time' => now()->addMinutes(10),
            ];
        }

        $customerName = $user->name ?? 'Buyer';
        $customerPhone = $user->phone ?? '0123456789';
        $customerEmail = $user->email ?? 'user@example.com';
        $deliveryAddress = $user->address ?? '123 Test Street';

        $walletPoint = $user->wallet_point ?? 0;

        return view('customer.food.checkout', compact(
            'sellerGroups',
            'customerName',
            'customerPhone',
            'customerEmail',
            'deliveryAddress',
            'walletPoint'
        ));
    }

    public function placeOrder(Request $request)
    {
        try {
            $user = Auth::user();
            $cart = FoodCart::with('items')->firstOrCreate(['user_id' => $user->id]);
            $cartItems = $cart->items;

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty.'
                ], 400);
            }

            $customerPhone = $request->input('phone', $user->phone ?? '0123456789');
            $customerEmail = $request->input('email', $user->email);
            $deliveryAddress = $request->input('delivery_address', $user->address ?? '123 Test Street');
            $paymentMethod = $request->input('payment_method', 'cod');

            // Group items by seller to calculate totals
            $groupedItems = $cartItems->groupBy('hotel_owner_id');
            $grandTotal = 0;

            foreach ($groupedItems as $hotelOwnerId => $items) {
                $foodTotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $deliveryFee = 50.00;
                $tax = round($foodTotal * 0.05);
                $grandTotal += ($foodTotal + $deliveryFee + $tax);
            }

            $useWallet = $request->boolean('use_wallet');
            $totalWalletDiscount = 0;
            if ($useWallet && $user->wallet_point > 0) {
                // Round to match frontend .toFixed(0)
                $totalWalletDiscount = round(min(0.15 * $grandTotal, $user->wallet_point));
            }
            $finalGrandTotal = $grandTotal - $totalWalletDiscount;

            if ($paymentMethod !== 'cod') {
                // Razorpay Order Creation
                $api = new Api($this->razorpayKeyId, $this->razorpayKeySecret);

                $razorpayOrder = $api->order->create([
                    'receipt' => 'food_order_' . time() . '_' . $user->id,
                    'amount' => (int) ($finalGrandTotal * 100), // convert to paise
                    'currency' => 'INR',
                ]);

                // Store checkout data in session
                session([
                    'food_checkout_data' => [
                        'customer_phone' => $customerPhone,
                        'customer_email' => $customerEmail,
                        'delivery_address' => $deliveryAddress,
                        'payment_method' => $paymentMethod,
                        'razorpay_order_id' => $razorpayOrder['id'],
                        'use_wallet' => $useWallet,
                        'wallet_discount' => $totalWalletDiscount,
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'payment_required' => true,
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'amount' => (int) ($finalGrandTotal * 100),
                    'key' => $this->razorpayKeyId,
                    'customer' => [
                        'name' => $user->name,
                        'email' => $customerEmail,
                        'contact' => $customerPhone,
                    ]
                ]);
            }

            // COD Flow
            $orderIds = [];
            foreach ($groupedItems as $hotelOwnerId => $items) {
                $hotel = HotelOwner::find($hotelOwnerId);
                $shopName = $hotel ? $hotel->restaurant_name : 'Unknown Shop';
                $shopAddress = $hotel ? $hotel->restaurant_address : 'Address not available';

                $foodTotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $deliveryFee = 50.00;
                $tax = round($foodTotal * 0.05);
                $sellerSubtotal = $foodTotal + $deliveryFee + $tax;

                // Proportional discount distribution
                $sellerDiscount = ($grandTotal > 0) ? ($sellerSubtotal / $grandTotal) * $totalWalletDiscount : 0;
                $sellerFinalTotal = $sellerSubtotal - $sellerDiscount;

                $order = FoodOrder::create([
                    'hotel_owner_id' => $hotelOwnerId,
                    'shop_name' => $shopName,
                    'shop_address' => $shopAddress,
                    'customer_name' => $user->name ?? 'Customer',
                    'customer_phone' => $customerPhone,
                    'customer_email' => $customerEmail,
                    'delivery_address' => $deliveryAddress,
                    'food_total' => $foodTotal,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $sellerFinalTotal,
                    'wallet_discount' => $sellerDiscount,
                    'payment_method' => 'cod',
                    'status' => 'pending',
                    'estimated_delivery_time' => now()->addMinutes(10),
                ]);

                foreach ($items as $item) {
                    FoodOrderItem::create([
                        'food_order_id' => $order->id,
                        'food_item_id' => $item->food_item_id,
                        'food_name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'food_type' => $item->food_type,
                    ]);
                }

                $orderIds[] = $order->id;
            }

            // Deduct from wallet if COD
            if ($totalWalletDiscount > 0) {
                UserWalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => -$totalWalletDiscount, // Negative for debit
                    'type' => 'debit',
                    'description' => 'Wallet points used for food order ' . implode(',', $orderIds),
                ]);
            }

            // Clear the cart
            $cart->items()->delete();

            return response()->json([
                'success' => true,
                'redirect_url' => route('customer.food.order.success', ['orderIds' => implode(',', $orderIds)])
            ]);

        } catch (\Exception $e) {
            Log::error('Order placement failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        try {
            $user = Auth::user();
            $checkoutData = session('food_checkout_data');

            if (!$checkoutData || $checkoutData['razorpay_order_id'] !== $request->razorpay_order_id) {
                return response()->json(['success' => false, 'message' => 'Invalid session or order.'], 400);
            }

            // Verify Signature
            $api = new Api($this->razorpayKeyId, $this->razorpayKeySecret);
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Payment Verified - Create Orders
            $cart = FoodCart::with('items')->where('user_id', $user->id)->first();
            $cartItems = $cart->items;
            $groupedItems = $cartItems->groupBy('hotel_owner_id');

            // Recalculate original grand total to distribute discount proportionally
            $originalGrandTotal = 0;
            foreach ($groupedItems as $hotelOwnerId => $items) {
                $foodTotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $deliveryFee = 50.00;
                $tax = round($foodTotal * 0.05);
                $originalGrandTotal += ($foodTotal + $deliveryFee + $tax);
            }

            $totalWalletDiscount = $checkoutData['wallet_discount'] ?? 0;

            $orderIds = [];
            foreach ($groupedItems as $hotelOwnerId => $items) {
                $hotel = HotelOwner::find($hotelOwnerId);
                $shopName = $hotel ? $hotel->restaurant_name : 'Unknown Shop';
                $shopAddress = $hotel ? $hotel->restaurant_address : 'Address not available';

                $foodTotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $deliveryFee = 50.00;
                $tax = round($foodTotal * 0.05);
                $sellerSubtotal = $foodTotal + $deliveryFee + $tax;

                // Proportional discount distribution
                $sellerDiscount = ($originalGrandTotal > 0) ? ($sellerSubtotal / $originalGrandTotal) * $totalWalletDiscount : 0;
                $sellerFinalTotal = $sellerSubtotal - $sellerDiscount;

                $order = FoodOrder::create([
                    'hotel_owner_id' => $hotelOwnerId,
                    'shop_name' => $shopName,
                    'shop_address' => $shopAddress,
                    'customer_name' => $user->name ?? 'Customer',
                    'customer_phone' => $checkoutData['customer_phone'],
                    'customer_email' => $checkoutData['customer_email'],
                    'delivery_address' => $checkoutData['delivery_address'],
                    'food_total' => $foodTotal,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $sellerFinalTotal,
                    'wallet_discount' => $sellerDiscount,
                    'payment_method' => $checkoutData['payment_method'],
                    'status' => 'paid', // Mark as paid for online payments
                    'payment_reference' => $request->razorpay_payment_id,
                    'estimated_delivery_time' => now()->addMinutes(10),
                ]);

                foreach ($items as $item) {
                    FoodOrderItem::create([
                        'food_order_id' => $order->id,
                        'food_item_id' => $item->food_item_id,
                        'food_name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'food_type' => $item->food_type,
                    ]);
                }
                $orderIds[] = $order->id;
            }

            // Deduct from wallet if used
            if ($totalWalletDiscount > 0) {
                UserWalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => -$totalWalletDiscount, // Negative for debit
                    'type' => 'debit',
                    'description' => 'Wallet points used for food order ' . implode(',', $orderIds),
                ]);
            }

            // Clear Cart
            $cart->items()->delete();
            session()->forget('food_checkout_data');

            return response()->json([
                'success' => true,
                'redirect_url' => route('customer.food.order.success', ['orderIds' => implode(',', $orderIds)])
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay verification failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 500);
        }
    }
    public function orderSuccess(Request $request)
    {
        $orderIds = $request->query('orderIds', '');
        if (!$orderIds) {
            return redirect()->route('customer.food.index')->with('error', 'No order found.');
        }

        $orderIds = explode(',', $orderIds);
        $orders = FoodOrder::with('items')->whereIn('id', $orderIds)->get();

        if ($orders->isEmpty()) {
            return redirect()->route('customer.food.index')->with('error', 'Orders not found.');
        }

        return view('customer.food.order-success', compact('orders')); // plural
    }
    public function myOrders(Request $request)
    {
        $user = Auth::user();

        // Fetch orders where customer_email OR customer_phone matches (adjust as per your data consistency)
        // Preferably, if you store user_id or customer_id, use that instead.
        $query = FoodOrder::with(['items', 'deliveryPartner'])
            ->where(function ($q) use ($user) {
                $q->where('customer_email', $user->email)
                    ->orWhere('customer_phone', $user->phone);
            });

        // Optional: Filter by status (e.g., ?status=Delivered)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Optional: Search by order ID or food name (basic)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhereHas('items', function ($q2) use ($search) {
                        $q2->where('food_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return view('customer.food.my-orders', compact('orders'));
    }

    public function orderDetails($id)
    {
        $order = FoodOrder::with(['items', 'deliveryPartner'])->findOrFail($id);
        $user = Auth::user();

        // 🔐 Security: Ensure the order belongs to the logged-in user
        if ($order->customer_email !== $user->email && $order->customer_phone !== $user->phone) {
            abort(403, 'Unauthorized access to this order.');
        }

        return view('customer.food.order-details', compact('order'));
    }
}
