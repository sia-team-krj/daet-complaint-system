<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The branded "your ticket moved" email, shared by both delivery paths:
 * the Brevo API (production) and the configured Laravel mailer (local
 * Mailpit/SMTP testing). The caller prepares the localized strings; this
 * mailable only carries them.
 */
class ComplaintUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     firstName: string,
     *     ticketId: string,
     *     headline: string,
     *     summary: string,
     *     note: string|null,
     *     noteLabel: string|null,
     *     url: string,
     * }  $payload
     */
    public function __construct(
        public Complaint $complaint,
        public string $subjectLine,
        public array $payload,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'mail.complaint-update',
            with: $this->viewData(),
        );
    }

    /**
     * The template variables both transports render from.
     *
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        return $this->payload + [
            'subject' => $this->subjectLine,
        ];
    }

    /**
     * Plain-text fallback body for clients that prefer text over HTML.
     */
    public function textContent(): string
    {
        $lines = [
            __('mail.ticket_greeting', ['name' => $this->payload['firstName']]),
            '',
            __('mail.ticket_reference', ['ticket' => $this->payload['ticketId']]),
            $this->payload['headline'],
            $this->payload['summary'],
        ];

        if ($this->payload['note'] !== null && $this->payload['note'] !== '') {
            $lines[] = '';
            $lines[] = (string) ($this->payload['noteLabel'] ?? __('mail.ticket_note_label'));
            $lines[] = $this->payload['note'];
        }

        $lines[] = '';
        $lines[] = $this->payload['url'];
        $lines[] = '';
        $lines[] = __('mail.ticket_ignore_note');

        return implode("\n", $lines);
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
