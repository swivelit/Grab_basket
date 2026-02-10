<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Mail\SimpleProlotionalMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send order confirmation notification (like Amazon)
     */
    public static function sendOrderPlaced($user, $order)
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'order_placed',
            'title' => 'Order Confirmed! 🎉',
            'message' => "Your order #{$order->id} has been placed successfully. We'll keep you updated on its progress.",
            'data' => [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'product_name' => $order->product->name ?? 'Product'
            ]
        ]);
    }

    /**
     * Send order status update notification
     */
    public static function sendOrderStatusUpdate($user, $order, $status)
    {
        $messages = [
            'confirmed' => "Great news! Your order #{$order->id} has been confirmed and is being prepared.",
            'shipped' => "📦 Your order #{$order->id} is on its way! Track your package for delivery updates.",
            'out_for_delivery' => "🚚 Your order #{$order->id} is out for delivery. It should arrive today!",
            'delivered' => "✅ Your order #{$order->id} has been delivered. Hope you love your purchase!",
            'cancelled' => "❌ Your order #{$order->id} has been cancelled. Your refund will be processed within 3-5 business days."
        ];

        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'order_status_update',
            'title' => 'Order Update',
            'message' => $messages[$status] ?? "Your order #{$order->id} status has been updated to: {$status}",
            'data' => [
                'order_id' => $order->id,
                'status' => $status,
                'product_name' => $order->product->name ?? 'Product'
            ]
        ]);
    }

    /**
     * Send new order notification to seller (like Amazon seller notifications)
     */
    public static function sendNewOrderToSeller($seller, $order)
    {
        Notification::create([
            'notifiable_id' => $seller->id,
            'notifiable_type' => get_class($seller),
            'type' => 'order_received',
            'title' => 'New Order Received! 💰',
            'message' => "You have a new order for {$order->product->name}. Order #{$order->id} worth ₹{$order->amount}",
            'data' => [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'product_name' => $order->product->name ?? 'Product',
                'buyer_name' => $order->user->name ?? 'Customer'
            ]
        ]);
    }

    /**
     * Send payment confirmation notification
     */
    public static function sendPaymentConfirmed($user, $order)
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'payment_confirmed',
            'title' => 'Payment Successful! 💳',
            'message' => "Your payment of ₹{$order->amount} for order #{$order->id} has been processed successfully.",
            'data' => [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'payment_method' => $order->payment_method ?? 'razorpay'
            ]
        ]);
    }

    /**
     * Send product back in stock notification
     */
    public static function sendProductBackInStock($user, $product)
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'product_back_in_stock',
            'title' => 'Back in Stock! 📦',
            'message' => "Good news! {$product->name} is back in stock. Order now before it runs out again!",
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price
            ]
        ]);
    }

    /**
     * Send price drop notification
     */
    public static function sendPriceDrop($user, $product, $oldPrice, $newPrice)
    {
        $discount = round((($oldPrice - $newPrice) / $oldPrice) * 100);

        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'price_drop',
            'title' => 'Price Drop Alert! 🏷️',
            'message' => "{$product->name} is now ₹{$newPrice} (was ₹{$oldPrice}). Save {$discount}%!",
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'discount_percent' => $discount
            ]
        ]);
    }

    /**
     * Send wishlist item on sale notification
     */
    public static function sendWishlistItemOnSale($user, $product)
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'wishlist_item_sale',
            'title' => 'Wishlist Item on Sale! ❤️',
            'message' => "Great news! {$product->name} from your wishlist is now on sale with {$product->discount}% off!",
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'discount' => $product->discount,
                'price' => $product->price
            ]
        ]);
    }

    /**
     * Send delivery reminder notification
     */
    public static function sendDeliveryReminder($user, $order)
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'delivery_reminder',
            'title' => 'Delivery Reminder 🚚',
            'message' => "Don't forget! Your order #{$order->id} will be delivered today between 10 AM - 8 PM.",
            'data' => [
                'order_id' => $order->id,
                'product_name' => $order->product->name ?? 'Product'
            ]
        ]);
    }

    /**
     * Send review request notification
     */
    public static function sendReviewRequest($user, $order)
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'review_request',
            'title' => 'How was your experience? ⭐',
            'message' => "We'd love to hear your feedback on {$order->product->name}. Share your review to help other customers!",
            'data' => [
                'order_id' => $order->id,
                'product_id' => $order->product->id,
                'product_name' => $order->product->name ?? 'Product'
            ]
        ]);
    }

    /**
     * Send bulk notification to multiple users with email option
     */
    public static function sendBulkNotification($userIds, $type, $title, $message, $data = [], $sendEmail = false)
    {
        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'notifiable_id' => $userId,
                'notifiable_type' => User::class,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => json_encode($data),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        Notification::insert($notifications);

        // Send emails if requested
        if ($sendEmail) {
            $users = User::whereIn('id', $userIds)->get();
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new SimpleProlotionalMail($user, $title, $message, $data));
                } catch (\Exception $e) {
                    Log::error("Failed to send promotional email to {$user->email}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Send promotional email to all buyers
     */
    public static function sendPromotionalEmailToBuyers($title, $message, $promotionData = [])
    {
        $buyers = User::where('role', 'buyer')->get();
        $sentCount = 0;

        foreach ($buyers as $buyer) {
            try {
                // Send in-app notification
                Notification::create([
                    'notifiable_id' => $buyer->id,
                    'notifiable_type' => get_class($buyer),
                    'type' => 'promotion',
                    'title' => $title,
                    'message' => $message,
                    'data' => $promotionData
                ]);

                // Send email
                Mail::to($buyer->email)->send(new SimpleProlotionalMail($buyer, $title, $message, $promotionData));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send promotional email to {$buyer->email}: " . $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * Send automated promotional emails
     */
    public static function sendAutomatedPromotionalEmail($type, $userType = 'buyers')
    {
        $users = collect();
        if ($userType === 'buyers') {
            $users = User::where('role', 'buyer')->get();
        } elseif ($userType === 'sellers') {
            $users = User::where('role', 'seller')->get();
        } else {
            $users = User::all();
        }

        $title = '';
        $message = '';
        $promotionData = ['type' => $type];

        switch ($type) {
            case 'daily_deals':
                $title = "🔥 Daily Deals - Up to 50% Off Today Only!";
                $message = "Don't miss today's incredible deals! Flash discounts on electronics, fashion, and more. Limited stock available!";
                break;

            case 'weekly_newsletter':
                $newProductsCount = \App\Models\Product::where('created_at', '>=', now()->subWeek())->count();
                $title = "📰 Weekly Update - {$newProductsCount} New Products This Week!";
                $message = "Check out this week's new arrivals, trending products, and exclusive member deals.";
                $promotionData['new_products_count'] = $newProductsCount;
                break;

            case 'flash_sale':
                $title = "⚡ FLASH SALE ALERT - 2 Hours Only!";
                $message = "URGENT: Flash sale ends in 2 hours! Extra 20% off on selected items. This is your last chance!";
                break;

            case 'weekend_special':
                $title = "🎉 Weekend Special - Extra Savings Just for You!";
                $message = "Make your weekend special with our exclusive deals. Free delivery on all orders this weekend!";
                break;

            default:
                $title = "🛒 Special Offer Just for You!";
                $message = "We have some amazing deals waiting for you. Check out our latest offers!";
        }

        $sentCount = 0;
        foreach ($users as $user) {
            try {
                // Send in-app notification
                Notification::create([
                    'notifiable_id' => $user->id,
                    'notifiable_type' => get_class($user),
                    'type' => 'promotion',
                    'title' => $title,
                    'message' => $message,
                    'data' => $promotionData
                ]);

                // Send email
                Mail::to($user->email)->send(new SimpleProlotionalMail($user, $title, $message, $promotionData));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send promotional email to {$user->email}: " . $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * Send promotional notification
     */
    public static function sendPromotion($user, $title, $message, $data = [])
    {
        Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'promotion',
            'title' => $title,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send new order assignment notification to partner
     */
    public static function sendOrderAssignedToPartner($partner, $order)
    {
        Notification::create([
            'notifiable_id' => $partner->id,
            'notifiable_type' => get_class($partner),
            'type' => 'order_assigned',
            'title' => 'New Order Assigned! 🔔',
            'message' => "You have been assigned a new order #{$order->id}. Please pick it up as soon as possible.",
            'data' => [
                'order_id' => $order->id,
                'type' => isset($order->order_number) ? 'standard' : (isset($order->hotel_owner_id) ? 'food' : 'ten_min'),
                'amount' => $order->amount ?? $order->total_amount
            ]
        ]);
    }
}