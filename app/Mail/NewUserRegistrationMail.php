<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a new user registers (if registration is enabled).
 * Contains the auto-generated password.
 */
class NewUserRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[MeemMark] Your New Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-user-registration',
        );
    }
}
