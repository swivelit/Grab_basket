<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SimpleProlotionalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $title;
    public $message;
    public $promotionData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $title, $message, $promotionData = [])
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
        $this->promotionData = $promotionData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->title)
                    ->view('emails.working-promotional');
    }
}
