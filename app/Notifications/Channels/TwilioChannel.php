<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!config('services.sms.enabled', false)) {
            Log::info('SMS notifications disabled - skipping Twilio send');
            return;
        }

        $message = $notification->toTwilio($notifiable);

        if (!$message) {
            return;
        }

        $to = $this->getRecipientPhoneNumber($notifiable);
        
        if (!$to) {
            Log::warning('No phone number for Twilio notification', [
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_type' => get_class($notifiable)
            ]);
            return;
        }

        $this->sendSms($to, $message);
    }

    /**
     * Get the recipient's phone number.
     */
    protected function getRecipientPhoneNumber(object $notifiable): ?string
    {
        if (isset($notifiable->phone)) {
            return $this->formatPhoneNumber($notifiable->phone);
        }

        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('twilio');
        }

        return null;
    }

    /**
     * Format phone number for Twilio (E.164 format).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it's a 10-digit Indian number, add country code
        if (strlen($phone) === 10) {
            return '+91' . $phone;
        }
        
        // If it already starts with country code
        if (strlen($phone) > 10 && !str_starts_with($phone, '+')) {
            return '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send SMS via Twilio API.
     * Returns array with success status and details.
     */
    public function sendSms(string $to, string $message): array
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.from');
            $senderName = config('services.twilio.sender_name', 'grabbaskets-TN');

            if (!$sid || !$token || !$from) {
                Log::error('Twilio credentials not configured');
                return ['success' => false, 'error' => 'Twilio credentials not configured'];
            }

            $payload = [
                'To' => $to,
                'From' => $from,
                'Body' => $message,
            ];

            // Note: Twilio sender name is configured at account level, not per-message
            // The 'From' number will show as the sender
            // For alphanumeric sender ID, you need to configure it in Twilio console

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);

            if ($response->successful()) {
                Log::info('Twilio SMS sent successfully', [
                    'to' => $to,
                    'sender' => $senderName,
                    'message_sid' => $response->json('sid')
                ]);
                return [
                    'success' => true,
                    'message_sid' => $response->json('sid'),
                    'to' => $to
                ];
            } else {
                Log::error('Twilio SMS failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $response->json('message') ?? $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => $response->json('message') ?? $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Twilio SMS exception', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
