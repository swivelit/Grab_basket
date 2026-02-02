<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\TwilioChannel;

class SellerNewOrder extends Notification
{
    use Queueable;

    protected $order;
    protected $product;

    /**
     * Create a new notification instance.
     */
    public function __construct($order, $product)
    {
        $this->order = $order;
        $this->product = $product;
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
            ->subject('New Order Received - ' . $this->product->name)
            ->view('emails.seller-order-notification', [
                'user' => $notifiable,
                'order' => $this->order,
                'product' => $this->product
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
        $buyerName = $this->order->buyerUser->name ?? 'Customer';
        
        return "🎉 New Order! {$notifiable->name}, you received an order #{$orderId} "
            . "for {$productName} (₹{$amount}) from {$buyerName}. "
            . "Login to your seller dashboard to view details and ship the order!";
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $orderId = $this->order->id;
        $amount = number_format($this->order->amount, 2);
        $productName = $this->product->name;
        $buyerName = $this->order->buyerUser->name ?? 'Customer';
        
        return [
            'title' => '🎉 New Order Received!',
            'message' => "You received order #{$orderId} for {$productName} (₹{$amount}) from {$buyerName}.",
            'type' => 'new_order',
            'order_id' => $orderId,
            'product_name' => $productName,
            'amount' => $this->order->amount,
            'buyer_name' => $buyerName,
            'action_url' => route('seller.orders.show', $orderId),
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
