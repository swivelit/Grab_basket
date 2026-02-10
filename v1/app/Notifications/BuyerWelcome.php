<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\TwilioChannel;

class BuyerWelcome extends Notification
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
            ->subject('Welcome to ' . config('app.name') . '! 🎉')
            ->view('emails.buyer-welcome', ['user' => $notifiable]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): string
    {
        return "🛒 Welcome to " . config('app.name') . ", {$notifiable->name}! "
            . "Start shopping from local sellers across India. "
            . "Track orders, secure payments & fast delivery. Happy shopping! 🎁";
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => '🎉 Welcome to ' . config('app.name') . '!',
            'message' => "Hi {$notifiable->name}! Start shopping from local sellers across India. Track orders, secure payments & fast delivery.",
            'type' => 'welcome',
            'action_url' => route('home'),
            'action_text' => 'Start Shopping'
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
