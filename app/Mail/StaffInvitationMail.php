<?php

namespace App\Mail;

use App\Models\InvitationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InvitationCode $invitation,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daet Listens staff invitation',
        );
    }

    public function content(): Content
    {
        $invitation = $this->invitation->loadMissing('department');

        return new Content(
            html: 'mail.staff-invitation',
            with: [
                'url' => $this->url,
                'inviteeName' => $invitation->invitee_name,
                'inviteeEmail' => $invitation->invitee_email,
                'departmentName' => $invitation->department?->name ?? 'the assigned department',
                'expiresAt' => $invitation->expires_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
