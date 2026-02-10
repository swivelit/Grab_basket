<?php

namespace App\Mail;

use App\Models\DeliveryPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryPartnerBlocked extends Mailable
{
    use Queueable, SerializesModels;

    public DeliveryPartner $partner;
    public string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(DeliveryPartner $partner, string $reason = '')
    {
        $this->partner = $partner;
        $this->reason = $reason ?: 'Policy violation or misbehavior';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Delivery Partner Account Has Been Suspended',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-partner.blocked',
            with: [
                'partner' => $this->partner,
                'reason' => $this->reason,
                'supportEmail' => config('mail.support_email', 'support@grabbaskets.com'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
