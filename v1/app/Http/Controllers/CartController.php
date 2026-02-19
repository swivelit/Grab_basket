<?php
namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $items = CartItem::with('product')
            ->where('user_id', Auth::id())
            ->get();

        $totals = $this->calculateTotals($items);

        return view('cart.index', compact('items', 'totals'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:10',
            'delivery_type' => 'nullable|in:express_10min,standard',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check stock availability
        if ($product->stock_quantity !== null && $product->stock_quantity <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is out of stock'
                ], 400);
            }
            return back()->with('error', 'Product is out of stock');
        }

        $qty = max(1, (int) $request->input('quantity', 1));
        $deliveryType = $request->input('delivery_type', 'standard');

        // Check if user already has this product in cart
        $item = CartItem::where([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ])->first();

        if ($item) {
            // Check if total quantity would exceed stock
            $newQuantity = $item->quantity + $qty;
            if ($product->stock_quantity !== null && $newQuantity > $product->stock_quantity) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Not enough stock available. Available: ' . $product->stock_quantity . ', In cart: ' . $item->quantity
                    ], 400);
                }
                return back()->with('error', 'Not enough stock available');
            }
            $item->quantity = $newQuantity;
        } else {
            // Create new cart item
            $item = new CartItem();
            $item->user_id = Auth::id();
            $item->product_id = $product->id;
            $item->seller_id = $product->seller_id;
            $item->price = $product->price;
            $item->discount = $product->discount ?? 0;
            $item->delivery_charge = $product->delivery_charge ?? 0;
            $item->quantity = $qty;
            $item->delivery_type = $deliveryType;
        }
        
        $item->save();

        $successMessage = 'Item added to ' . ($deliveryType === 'express_10min' ? '10-min' : 'standard') . ' cart!';

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'cart_item' => $item->load('product'),
                'cart_count' => CartItem::where('user_id', Auth::id())->sum('quantity')
            ]);
        }

        return redirect()->route('cart.index')->with('success', $successMessage);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
        ]);
        $cartItem->update(['quantity' => $request->quantity]);
        return back()->with('success', 'Cart updated');
    }

    public function remove(CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }
        $cartItem->delete();
        return back()->with('success', 'Item removed');
    }

    public function clear()
    {
        CartItem::where('user_id', Auth::id())->delete();
        return back()->with('success', 'Cart cleared');
    }

    public function count()
    {
        $count = CartItem::where('user_id', Auth::id())->sum('quantity') ?? 0;
        return response()->json(['count' => $count]);
    }
    

    public function checkout(Request $request)
    {
        $request->validate([
            'address' => 'nullable|string|max:255',
            'new_address' => 'nullable|string|max:255',
            'address_type' => 'nullable|in:home,office,other',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => ['required', 'string', 'max:10', 'regex:/^[0-9]{5,10}$/'],
            'payment_method' => 'required|in:razorpay,stripe,cod',
        ]);

        $items = CartItem::with('product')
            ->where('user_id', Auth::id())
            ->get();

        if ($items->isEmpty()) {
            return back()->with('success', 'Your cart is empty');
        }

        $address = $request->new_address ?: $request->address;
        $city = $request->city;
        $state = $request->state;
        $pincode = $request->pincode;
        $address_type = $request->address_type;
        $payment_method = $request->payment_method;

        // Save new address if provided
        if ($request->new_address) {
            \App\Models\Address::create([
                'user_id' => Auth::id(),
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode,
                'type' => $address_type,
            ]);
        }

        // Simulate payment gateway
        $payment_status = ($payment_method === 'cod') ? 'pending' : 'paid';
        $payment_ref = strtoupper($payment_method) . '-' . uniqid();

        foreach ($items as $item) {
            $amount = $this->lineAmount($item);
            Order::create([
                'product_id' => $item->product_id,
                'seller_id' => $item->seller_id,
                'buyer_id' => Auth::id(),
                'amount' => $amount,
                'status' => $payment_status,
                'paid_at' => $payment_status === 'paid' ? now() : null,
                'payment_reference' => $payment_ref,
                'delivery_address' => $address,
                'delivery_city' => $city,
                'delivery_state' => $state,
                'delivery_pincode' => $pincode,
                'payment_method' => $payment_method,
            ]);
        }

        // Clear cart after successful order creation
        CartItem::where('user_id', Auth::id())->delete();

        return redirect()->route('cart.index')->with('success', 'Order placed! Payment ' . ($payment_status === 'paid' ? 'successful' : 'pending for COD'));
    }

    /**
     * Show new checkout page with separate carts for express and standard delivery
     */
    public function showCheckoutNew()
    {
        $expressCartItems = CartItem::with('product')
            ->where('user_id', Auth::id())
            ->where('delivery_type', 'express_10min')
            ->get();

        $standardCartItems = CartItem::with('product')
            ->where('user_id', Auth::id())
            ->where('delivery_type', 'standard')
            ->get();

        if ($expressCartItems->isEmpty() && $standardCartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('info', 'Your cart is empty');
        }

        // Calculate totals
        $expressTotal = $expressCartItems->sum(function ($item) {
            return $this->lineAmount($item);
        });

        $standardTotal = $standardCartItems->sum(function ($item) {
            return $this->lineAmount($item);
        });

        return view('cart.checkout-new', compact(
            'expressCartItems',
            'standardCartItems',
            'expressTotal',
            'standardTotal'
        ));
    }

    /**
     * Switch item delivery type
     */
    public function switchDeliveryType(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'delivery_type' => 'required|in:express_10min,standard',
        ]);

        $cartItem->update(['delivery_type' => $request->delivery_type]);

        return back()->with('success', 'Delivery type updated!');
    }

    private function calculateTotals($items)
    {
        $subtotal = 0;
        $discountTotal = 0;
        $deliveryTotal = 0;
        foreach ($items as $item) {
            $price = (float) $item->price;
            $discPct = (float) $item->discount;
            $qty = (int) $item->quantity;
            $delivery = (float) $item->delivery_charge;

            $lineBase = $price * $qty;
            $lineDisc = $discPct > 0 ? ($lineBase * ($discPct / 100)) : 0;
            $subtotal += $lineBase;
            $discountTotal += $lineDisc;
            $deliveryTotal += $delivery; // per item shipment; adjust if per qty
        }
        $total = $subtotal - $discountTotal + $deliveryTotal;
        return compact('subtotal', 'discountTotal', 'deliveryTotal', 'total');
    }

    private function lineAmount(CartItem $item): float
    {
        $price = (float) $item->price;
        $discPct = (float) $item->discount;
        $qty = (int) $item->quantity;
        $delivery = (float) $item->delivery_charge;
        $lineBase = $price * $qty;
        $lineDisc = $discPct > 0 ? ($lineBase * ($discPct / 100)) : 0;
        return $lineBase - $lineDisc + $delivery;
    }

    public function moveToWishlist(CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        // Add to wishlist
        \App\Models\Wishlist::firstOrCreate([
            'user_id' => Auth::id(),
            'product_id' => $cartItem->product_id,
        ]);

        // Remove from cart
        $cartItem->delete();

        return back()->with('success', 'Item moved to wishlist');
    }

    // Checkout page for address/payment
    public function showCheckout(Request $request)
    {
        $user = Auth::user();
        $items = CartItem::with('product')->where('user_id', $user->id)->get();
        
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }
        
        $totals = $this->calculateTotals($items);
        $addresses = $user->addresses ? $user->addresses->pluck('address')->toArray() : [];
        
        if ($user->billing_address) {
            array_unshift($addresses, $user->billing_address);
        }
        
        return view('cart.checkout', compact('items', 'totals', 'addresses', 'user'));
    }

    public function location(){
        return view ('location');
    }
}
