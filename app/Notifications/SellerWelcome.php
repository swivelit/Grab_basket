<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\TwilioChannel;

class SellerWelcome extends Notification
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
            ->subject('Welcome to ' . config('app.name') . ' Seller Hub! 🏪')
            ->view('emails.seller-welcome', ['user' => $notifiable]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): string
    {
        return "🏪 Welcome to " . config('app.name') . " Seller Hub, {$notifiable->name}! "
            . "Start listing products & reach thousands of customers. "
            . "Instant payments, analytics & order notifications. Let's grow your business! 🚀";
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => '🏪 Welcome to ' . config('app.name') . ' Seller Hub!',
            'message' => "Hi {$notifiable->name}! Start listing products & reach thousands of customers. Instant payments, analytics & order notifications.",
            'type' => 'welcome',
            'action_url' => route('seller.dashboard'),
            'action_text' => 'Go to Dashboard'
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
