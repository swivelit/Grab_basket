<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\DeliveryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class DeliveryJobAssigned extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected ?DeliveryRequest $deliveryRequest = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Delivery Job Assigned',
            'message' => "You have been assigned a new delivery job. Order #{$this->order->order_number}",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'delivery_request_id' => $this->deliveryRequest?->id,
            'customer_name' => $this->order->user->name ?? 'Customer',
            'delivery_address' => $this->order->delivery_address,
            'total_amount' => $this->order->total_amount,
            'type' => 'job_assigned',
            'action_url' => route('delivery-partner.requests.show', $this->deliveryRequest?->id ?? '#'),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
