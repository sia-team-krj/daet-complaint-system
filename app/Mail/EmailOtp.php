<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public User $user,
        public string $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your Daet Listens email address',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-otp',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'expiryMinutes' => (int) config('email-verification.expires_in_minutes', 10),
                'appName' => config('app.name', 'Daet Listens'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
