<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PromotionalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $title;
    public $message;
    public $promotionData;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $title, $message, $promotionData = [])
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
        $this->promotionData = $promotionData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.promotional',
            with: [
                'user' => $this->user,
                'title' => $this->title,
                'message' => $this->message,
                'promotionData' => $this->promotionData,
            ]
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
