<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\TwilioChannel;

class BuyerPurchaseConfirmation extends Notification
{
    use Queueable;

    protected $order;
    protected $product;
    protected $allOrders;

    /**
     * Create a new notification instance.
     */
    public function __construct($order, $product, $allOrders = null)
    {
        $this->order = $order;
        $this->product = $product;
        $this->allOrders = $allOrders;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', \App\Notifications\Channels\DatabaseChannel::class];
        
        // Add SMS channel if phone number is available
        if ($notifiable->phone) {
            $channels[] = TwilioChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Confirmation - ' . config('app.name'))
            ->view('emails.buyer-order-confirmation', [
                'user' => $notifiable,
                'order' => $this->order,
                'product' => $this->product,
                'orders' => $this->allOrders
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): string
    {
        $orderId = $this->order->id;
        $amount = number_format($this->order->amount, 2);
        $productName = $this->product->name;
        
        if ($this->allOrders && count($this->allOrders) > 1) {
            $totalAmount = number_format(collect($this->allOrders)->sum('amount'), 2);
            return "✅ Order Confirmed! {$notifiable->name}, your " . count($this->allOrders) 
                . " orders (Total: ₹{$totalAmount}) have been placed successfully. "
                . "Track your orders at " . config('app.url') . "/orders/track";
        }
        
        return "✅ Order #{$orderId} Confirmed! {$notifiable->name}, your order for {$productName} "
            . "(₹{$amount}) has been placed successfully. Track at " 
            . config('app.url') . "/orders/track";
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $orderId = $this->order->id;
        $amount = number_format($this->order->amount, 2);
        $productName = $this->product->name;
        
        if ($this->allOrders && count($this->allOrders) > 1) {
            $totalAmount = number_format(collect($this->allOrders)->sum('amount'), 2);
            return [
                'title' => '✅ Orders Confirmed!',
                'message' => "Your " . count($this->allOrders) . " orders (Total: ₹{$totalAmount}) have been placed successfully.",
                'type' => 'order_confirmation',
                'order_ids' => collect($this->allOrders)->pluck('id')->toArray(),
                'total_amount' => collect($this->allOrders)->sum('amount'),
                'action_url' => route('orders.index'),
                'action_text' => 'Track Orders'
            ];
        }
        
        return [
            'title' => '✅ Order Confirmed!',
            'message' => "Your order #{$orderId} for {$productName} (₹{$amount}) has been placed successfully.",
            'type' => 'order_confirmation',
            'order_id' => $orderId,
            'product_name' => $productName,
            'amount' => $this->order->amount,
            'action_url' => route('orders.show', $orderId),
            'action_text' => 'View Order'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
