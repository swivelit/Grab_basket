<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DatabaseChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        // Get data from toDatabase if available, otherwise from toArray
        $data = method_exists($notification, 'toDatabase') 
            ? $notification->toDatabase($notifiable)
            : $notification->toArray($notifiable);
        
        // Generate UUID if not set
        if (!isset($notification->id)) {
            $notification->id = Str::uuid()->toString();
        }
        
        $notifiable->notifications()->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
