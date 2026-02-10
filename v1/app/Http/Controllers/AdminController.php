<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\Order;
use App\Models\FoodOrder;
use App\Models\TenMinOrder;
use App\Models\DeliveryPartner;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\InfobipSmsService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // Show bulk product upload form
    public function showBulkProductUpload()
    {
        $sellers = User::where('role', 'seller')->pluck('email');
        return view('admin.bulk-product-upload', compact('sellers'));
    }

    // Handle bulk product upload
    public function handleBulkProductUpload(Request $request)
    {
        $request->validate([
            'seller_email' => 'required|email',
            'products_file' => 'required|file|mimes:csv,txt,xlsx,xls',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:4096',
            'images_zip' => 'sometimes|file|mimes:zip|max:102400', // optional ZIP up to 100MB
        ]);

        $seller = User::where('email', $request->seller_email)->where('role', 'seller')->first();
        if (!$seller) {
            return back()->withErrors(['seller_email' => 'Command: seller not exist']);
        }

        $file = $request->file('products_file');
        $ext = strtolower($file->getClientOriginalExtension());

        $errors = [];
        $count = 0;
        $updatedImages = 0;

        // Optional: store ZIP temporarily for ProductsImport
        $zipPath = null;
        if ($request->hasFile('images_zip')) {
            $zipPath = $request->file('images_zip')->store('temp/bulk-uploads', 'local');
        }

        if (in_array($ext, ['xlsx', 'xls'])) {
            // Use ProductsImport for Excel with optional ZIP and force seller_id
            $import = new \App\Imports\ProductsImport($zipPath, $seller->id);
            Excel::import($import, $file);
            $count = $import->getSuccessCount();
            $errors = $import->getErrors();
        } elseif (in_array($ext, ['csv', 'txt'])) {
            // Backward-compatible CSV handling with optional individual images[]
            $rows = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_map('trim', array_map('strtolower', $rows[0]));
            unset($rows[0]);

            $imageMap = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $imageMap[strtolower($filename)] = $image;
                }
            }

            foreach ($rows as $row) {
                $data = array_combine($header, $row);
                if (!$data)
                    continue;

                $data['seller_id'] = $seller->id;

                if (isset($data['unique_id']) && Product::where('unique_id', $data['unique_id'])->exists()) {
                    continue;
                }

                $uid = isset($data['unique_id']) ? strtolower($data['unique_id']) : null;
                if ($uid && isset($imageMap[$uid])) {
                    $img = $imageMap[$uid];
                    $folder = "admin/{$seller->id}/" . ($data['category_id'] ?? 'uncategorized') . "/" . ($data['subcategory_id'] ?? 'general');
                    $path = $img->store($folder, 'public');
                    $data['image'] = $path;
                    $updatedImages++;
                }

                try {
                    Product::create($data);
                    $count++;
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } else {
            return back()->withErrors(['products_file' => 'Unsupported file type. Use CSV, XLSX or XLS.']);
        }

        // Clean up temp ZIP
        if ($zipPath && Storage::disk('local')->exists($zipPath)) {
            Storage::disk('local')->delete($zipPath);
        }

        $msg = "Imported {$count} products for {$seller->email}.";
        if ($updatedImages > 0) {
            $msg .= " {$updatedImages} images assigned.";
        }
        if (!empty($errors)) {
            return back()->with('success', $msg)->with('import_errors', $errors);
        }
        return back()->with('success', $msg);
    }

    public function transactions()
    {
        try {
            // Count of products safely
            $productsCount = Product::count();

            // Get latest orders with eager loading - handle null relationships gracefully
            $ordersCount = Order::with([
                'buyerUser' => function ($query) {
                    $query->select('id', 'name', 'email');
                },
                'product' => function ($query) {
                    $query->select('id', 'name', 'price');
                }
            ])->latest()->take(10)->get();

            $sellersCount = User::where('role', 'seller')->count();
            $buyersCount = User::where('role', 'buyer')->count();

            // Add delivery partners count
            $deliveryPartnersCount = \App\Models\DeliveryPartner::count();
            $activeDeliveryPartnersCount = \App\Models\DeliveryPartner::where('status', 'approved')->count();

            // Create a collection with count method available
            $products = collect(['count' => $productsCount]);

            return view('admin.dashboard', compact(
                'products',
                'ordersCount',
                'sellersCount',
                'buyersCount',
                'deliveryPartnersCount',
                'activeDeliveryPartnersCount'
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin dashboard error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->view('errors.custom', [
                'message' => 'There was an error loading the dashboard. Please try again.'
            ], 500);
        }
    }

    public function orders(Request $request, $type = 'all')
    {
        $statuses = ['Pending', 'Preparing', 'Shipped', 'Delivered', 'Cancelled'];
        $deliveryTypes = ['standard', 'food', 'express_10min'];

        // Define the active type based on route parameter or query string
        $activeType = $request->get('type', $type);

        if ($activeType === 'food') {
            $query = FoodOrder::with('items.foodItem');
        } elseif ($activeType === 'express' || $activeType === 'tenmins') {
            $query = TenMinOrder::with('items.product');
            $activeType = 'express'; // Normalize
        } elseif ($activeType === 'standard') {
            $query = Order::with(['buyerUser', 'product'])->where('delivery_type', 'standard');
        } else {
            // Unified view (all)
            $standardOrders = Order::with(['buyerUser', 'product'])
                ->select('id', 'buyer_id', 'amount', 'status', 'created_at', 'payment_method', 'delivery_type', 'tracking_number', 'courier_name')
                ->selectRaw("'standard' as type");

            $foodOrders = FoodOrder::with(['items'])
                ->select('id', 'hotel_owner_id as buyer_id', 'total_amount as amount', 'status', 'created_at', 'payment_method')
                ->selectRaw("'food' as delivery_type, NULL as tracking_number, NULL as courier_name, 'food' as type");

            $expressOrders = TenMinOrder::with(['user', 'items'])
                ->select('id', 'user_id as buyer_id', 'total_amount as amount', 'status', 'created_at', 'payment_method')
                ->selectRaw("'express_10min' as delivery_type, NULL as tracking_number, NULL as courier_name, 'express' as type");

            $query = $standardOrders->union($foodOrders)->union($expressOrders);
        }

        // Apply global filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            // Search logic here...
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->all());

        // Hydrate only for unified view because specialized queries already have relations
        if ($activeType === 'all') {
            $orders->getCollection()->transform(function ($order) {
                if ($order->type === 'food') {
                    $item = FoodOrder::with('items.foodItem')->find($order->id);
                    if ($item)
                        $item->type = 'food';
                    return $item ?? $order;
                } elseif ($order->type === 'express') {
                    $item = TenMinOrder::with('items.product')->find($order->id);
                    if ($item)
                        $item->type = 'express';
                    return $item ?? $order;
                } else {
                    $item = Order::with(['buyerUser', 'product'])->find($order->id);
                    if ($item)
                        $item->type = 'standard';
                    return $item ?? $order;
                }
            });
        } else {
            // Ensure type is set on individual models
            $orders->getCollection()->each(function ($item) use ($activeType) {
                $item->type = $activeType;
            });
        }

        $partners = DeliveryPartner::where('status', 'approved')->get();

        return view('admin.orders', compact('orders', 'statuses', 'deliveryTypes', 'partners', 'activeType'));
    }

    public function products(Request $request)
    {
        try {
            $query = Product::with(['seller', 'category', 'subcategory']);

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('category') && $request->category !== 'all') {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('name', $request->category);
                });
            }

            $categories = Category::pluck('name')->toArray();
            $products = $query->paginate(10)->appends($request->only(['search', 'category']));
            $sellers = User::where('role', 'seller')->pluck('name', 'id');

            return view('admin.products', compact('products', 'categories', 'sellers'));
        } catch (\Throwable $e) {
            Log::error('Admin products page failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Admin products error');
        }
    }

    // Show products grouped by sellers
    public function productsBySeller(Request $request)
    {
        try {
            $search = $request->input('search');
            $selectedSeller = $request->input('seller_id');

            // Get all sellers with product counts using the correct relationship
            $sellersQuery = User::where('role', 'seller')
                ->withCount([
                    'products' => function ($query) {
                        $query->whereNotNull('image'); // Only count products with images
                    }
                ]);

            // Apply search filter
            if ($search) {
                $sellersQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $sellers = $sellersQuery->orderBy('products_count', 'desc')->get();

            // Get products for selected seller
            $products = null;
            $selectedSellerInfo = null;

            if ($selectedSeller) {
                $selectedSellerInfo = User::find($selectedSeller);
                if ($selectedSellerInfo) {
                    $products = Product::with(['category', 'subcategory'])
                        ->where('seller_id', $selectedSeller)
                        ->whereNotNull('image')
                        ->latest()
                        ->paginate(12)
                        ->appends(['seller_id' => $selectedSeller, 'search' => $search]);
                }
            }

            return view('admin.products-by-seller', compact('sellers', 'products', 'selectedSellerInfo', 'search', 'selectedSeller'));
        } catch (\Throwable $e) {
            Log::error('Admin products by seller page failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Admin products by seller error');
        }
    }

    // ✅ Enhanced users() with search, role, and status filters
    public function users(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by status (active = is_suspended = false)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_suspended', false);
            } elseif ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            }
        }

        $users = $query->paginate(10)->appends($request->only(['search', 'role', 'status']));

        return view('admin.manageuser', compact('users'));
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        if ($user->role === 'admin') {
            return redirect()->back()->with('error', 'You cannot delete an admin.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function suspendUser(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot suspend an admin.');
        }

        $user->is_suspended = !$user->is_suspended;
        $user->save();

        $action = $user->is_suspended ? 'suspended' : 'restored';
        return back()->with('success', "User {$action} successfully.");
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'order_type' => 'required|in:standard,food,express'
        ]);

        $orderType = $request->order_type;

        if ($orderType !== 'standard') {
            return back()->with('error', 'Status for ' . ucfirst($orderType) . ' orders must be updated by the respective seller or hotel owner.');
        }

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        // Release delivery partner if order completed or cancelled
        if (in_array($request->status, ['delivered', 'cancelled'])) {
            if ($order->delivery_partner_id) {
                // Eager load if needed, but it should be fine
                $order->deliveryPartner->clearOrder();
            }
        }

        // Notify buyer
        $buyer = $order->buyerUser;
        if ($buyer) {
            Notification::create([
                'user_id' => $buyer->id,
                'title' => "Order Status Updated",
                'message' => "Your order #{$id} status has been updated to {$request->status}.",
                'type' => 'order_status_update',
                'data' => json_encode(['order_id' => $id, 'type' => 'standard'])
            ]);
        }

        return back()->with('success', 'Order status updated successfully.');
    }

    public function updateTracking(Request $request, Order $order)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:255',
            'courier_name' => 'nullable|string|max:255',
        ]);

        $order->tracking_number = $request->tracking_number;
        $order->courier_name = $request->courier_name ?? 'Unknown Courier';
        $order->save();

        // Create Amazon-style tracking notification for buyer
        $buyer = $order->buyerUser;
        if ($buyer) {
            // Create in-app notification
            Notification::create([
                'user_id' => $buyer->id,
                'title' => 'Package Shipped! 📦',
                'message' => "Great news! Your order #{$order->id} has been shipped via {$order->courier_name}. Track it with number: {$order->tracking_number}",
                'type' => 'order_shipped',
                'data' => json_encode([
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'courier_name' => $order->courier_name,
                    'tracking_url' => route('tracking.form') . '?tracking_number=' . $order->tracking_number
                ])
            ]);

            // Send email notification to buyer
            if ($buyer->email) {
                $subject = 'Your Order Has Been Shipped! 🚚';
                $trackingUrl = route('tracking.form') . '?tracking_number=' . $order->tracking_number;
                $message = "
Dear {$buyer->name},

Exciting news! Your order #{$order->id} has been shipped and is on its way to you.

📦 Tracking Details:
• Courier: {$order->courier_name}
• Tracking Number: {$order->tracking_number}
• Track Your Package: {$trackingUrl}

You can track your package in real-time using our tracking system. Just click the link above or enter your tracking number on our website.

Thank you for shopping with us!

Best regards,
Grabbasket Team
                ";

                Mail::raw($message, function ($mail) use ($buyer, $subject) {
                    $mail->to($buyer->email)
                        ->subject($subject);
                });
            }
        }

        // Update order status to shipped if it's not already
        if ($order->status !== 'shipped' && $order->status !== 'delivered') {
            $order->status = 'shipped';
            $order->save();
        }

        // 📱 Send SMS shipping notification to buyer
        if ($buyer && $buyer->phone) {
            $smsService = new InfobipSmsService();
            $smsResult = $smsService->sendShippingNotificationToBuyer($buyer, $order);
            if ($smsResult['success']) {
                \Illuminate\Support\Facades\Log::info('Admin - Shipping SMS sent to buyer', ['buyer_id' => $buyer->id, 'order_id' => $order->id]);
            } else {
                \Illuminate\Support\Facades\Log::warning('Admin - Failed to send shipping SMS to buyer', ['buyer_id' => $buyer->id, 'error' => $smsResult['error']]);
            }
        }

        // Notify admin (optional)
        $admin = User::where('role', 'admin')->first();
        if ($admin && $admin->email) {
            Mail::raw(
                "Order #{$order->id} tracking number updated to: {$order->tracking_number} via {$order->courier_name}. Buyer has been notified via email and SMS.",
                function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject('Order Tracking Number Updated');
                }
            );
        }

        return back()->with('success', 'Tracking information updated and buyer notified with tracking details via email and SMS!');
    }

    public function destroyProduct(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    /**
     * Show promotional notifications form
     */
    public function showPromotionalForm()
    {
        return view('admin.promotional-notifications');
    }

    /**
     * Send promotional notifications
     */
    public function sendPromotionalNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'user_type' => 'required|in:all,buyers,sellers',
            'send_email' => 'boolean'
        ]);

        // Get users based on type
        $users = collect();
        if ($request->user_type === 'all') {
            $users = User::all();
        } elseif ($request->user_type === 'buyers') {
            $users = User::where('role', 'buyer')->get();
        } elseif ($request->user_type === 'sellers') {
            $users = User::where('role', 'seller')->get();
        }

        $sendEmail = $request->has('send_email') && $request->send_email;

        if ($sendEmail) {
            // Send both notifications and emails
            if ($request->user_type === 'buyers') {
                $sentCount = NotificationService::sendPromotionalEmailToBuyers(
                    $request->title,
                    $request->message,
                    ['type' => 'custom', 'user_type' => $request->user_type]
                );
                return back()->with('success', "Promotional notification and email sent to {$sentCount} buyers.");
            } else {
                // For sellers or all users, use bulk notification with email
                NotificationService::sendBulkNotification(
                    $users->pluck('id')->toArray(),
                    'promotion',
                    $request->title,
                    $request->message,
                    ['type' => 'custom'],
                    true // Send email
                );
                return back()->with('success', "Promotional notification and email sent to {$users->count()} users.");
            }
        } else {
            // Send only notifications
            NotificationService::sendBulkNotification(
                $users->pluck('id')->toArray(),
                'promotion',
                $request->title,
                $request->message
            );
            return back()->with('success', "Promotional notification sent to {$users->count()} users.");
        }
    }

    /**
     * Send automated notifications
     */
    public function sendAutomatedNotifications(Request $request)
    {
        $request->validate([
            'notification_type' => 'required|in:daily_deals,weekly_newsletter,flash_sale,wishlist_sale,back_in_stock,weekend_special',
            'send_email' => 'boolean'
        ]);

        $type = $request->notification_type;
        $sendEmail = $request->has('send_email') && $request->send_email;
        $sentCount = 0;

        if ($sendEmail && in_array($type, ['daily_deals', 'weekly_newsletter', 'flash_sale', 'weekend_special'])) {
            // Send promotional emails
            $sentCount = NotificationService::sendAutomatedPromotionalEmail($type);
        } else {
            // Original logic for non-email notifications
            switch ($type) {
                case 'daily_deals':
                    $users = User::where('role', 'buyer')->get();
                    $title = "🔥 Daily Deals - Up to 50% Off!";
                    $message = "Don't miss today's amazing deals! Limited time offers on top products.";
                    NotificationService::sendBulkNotification($users->pluck('id')->toArray(), 'promotion', $title, $message);
                    $sentCount = $users->count();
                    break;

                case 'weekly_newsletter':
                    $users = User::where('role', 'buyer')->get();
                    $newProducts = Product::where('created_at', '>=', now()->subWeek())->count();
                    $title = "📰 Weekly Update - {$newProducts} New Products Added!";
                    $message = "Check out this week's new arrivals and trending products.";
                    NotificationService::sendBulkNotification($users->pluck('id')->toArray(), 'promotion', $title, $message);
                    $sentCount = $users->count();
                    break;

                case 'flash_sale':
                    $users = User::where('role', 'buyer')->get();
                    $title = "⚡ FLASH SALE - 2 Hours Only!";
                    $message = "Hurry! Flash sale ends in 2 hours. Extra 20% off on selected items.";
                    NotificationService::sendBulkNotification($users->pluck('id')->toArray(), 'promotion', $title, $message);
                    $sentCount = $users->count();
                    break;

                case 'weekend_special':
                    $users = User::where('role', 'buyer')->get();
                    $title = "🎉 Weekend Special - Extra Savings!";
                    $message = "Make your weekend special with exclusive deals and free delivery!";
                    NotificationService::sendBulkNotification($users->pluck('id')->toArray(), 'promotion', $title, $message);
                    $sentCount = $users->count();
                    break;

                case 'wishlist_sale':
                    $wishlistItems = \App\Models\Wishlist::with(['user', 'product'])
                        ->whereHas('product', function ($query) {
                            $query->where('discount', '>', 0);
                        })
                        ->get();
                    foreach ($wishlistItems as $item) {
                        NotificationService::sendWishlistItemOnSale($item->user, $item->product);
                    }
                    $sentCount = $wishlistItems->count();
                    break;

                case 'back_in_stock':
                    $wishlistItems = \App\Models\Wishlist::with(['user', 'product'])
                        ->whereHas('product', function ($query) {
                            $query->where('stock', '>', 0);
                        })
                        ->get();
                    foreach ($wishlistItems as $item) {
                        NotificationService::sendProductBackInStock($item->user, $item->product);
                    }
                    $sentCount = $wishlistItems->count();
                    break;
            }
        }

        $emailText = $sendEmail ? " and email" : "";
        return back()->with('success', "Sent {$type} notifications{$emailText} to {$sentCount} users.");
    }

    /**
     * Send bulk promotional emails to all buyers
     */
    public function sendBulkPromotionalEmail(Request $request)
    {
        $request->validate([
            'email_type' => 'required|in:daily_deals,flash_sale,weekly_newsletter,weekend_special'
        ]);

        $type = $request->email_type;
        $sentCount = NotificationService::sendAutomatedPromotionalEmail($type);

        return back()->with('success', "Promotional emails sent to {$sentCount} buyers successfully!");
    }
    /**
     * Update the seller for a product (admin action)
     */
    public function updateProductSeller(Request $request, Product $product)
    {
        $request->validate([
            'seller_id' => 'required|exists:users,id',
        ]);
        $seller = User::where('id', $request->seller_id)->where('role', 'seller')->first();
        if (!$seller) {
            return back()->with('error', 'Selected user is not a valid seller.');
        }
        $product->seller_id = $seller->id;
        $product->save();
        return back()->with('success', 'Seller updated successfully.');
    }

    public function assignDeliveryPartner(Request $request, $id)
    {
        $request->validate([
            'delivery_partner_id' => 'required|exists:delivery_partners,id',
            'order_type' => 'required|in:standard,food,express'
        ]);

        $partner = DeliveryPartner::findOrFail($request->delivery_partner_id);

        // Validation: Must be online and available
        if (!$partner->is_online) {
            return back()->with('error', 'Cannot assign order: Partner is offline.');
        }

        if (!$partner->is_available || $partner->current_order_id) {
            return back()->with('error', 'Cannot assign order: Partner is currently busy with another order.');
        }

        $orderType = $request->order_type;
        $order = null;

        if ($orderType === 'standard') {
            $order = Order::findOrFail($id);
        } elseif ($orderType === 'food') {
            $order = FoodOrder::findOrFail($id);
        } else {
            $order = TenMinOrder::findOrFail($id);
        }

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        // Assign the order and update partner status
        $partner->assignOrder($order);

        // Update order status based on type
        if ($orderType === 'standard') {
            $order->update(['delivery_status' => 'assigned']);
        } else {
            $order->update(['status' => 'assigned']);
        }

        return back()->with('success', 'Delivery partner assigned successfully.');
    }
}