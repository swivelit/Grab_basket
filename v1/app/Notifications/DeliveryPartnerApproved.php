<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryPartnerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Delivery Partner Account has been Approved!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Good news! Your delivery partner account has been approved.')
            ->line('You can now log in and start accepting delivery requests.')
            ->action('Log In Now', route('delivery-partner.login'))
            ->line('Thank you for joining our delivery network!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account Approved',
            'body' => 'Your delivery partner account has been approved. You can now start accepting deliveries.',
            'type' => 'account_approval'
        ];
    }
}