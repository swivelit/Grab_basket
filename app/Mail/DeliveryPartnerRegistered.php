<?php

namespace App\Mail;

use App\Models\DeliveryPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryPartnerRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public DeliveryPartner $partner;

    /**
     * Create a new message instance.
     */
    public function __construct(DeliveryPartner $partner)
    {
        $this->partner = $partner;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Delivery Partner Registration - ' . $this->partner->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-partner.registered',
            with: [
                'partner' => $this->partner,
                'approveUrl' => route('admin.delivery-partners.show', $this->partner->id),
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
