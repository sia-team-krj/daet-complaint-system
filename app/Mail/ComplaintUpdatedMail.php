<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComplaintUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Complaint $complaint;
    public string $newStatus;
    public ?string $note;

    /**
     * Create a new message instance.
     */
    public function __construct(Complaint $complaint, string $newStatus, ?string $note = null)
    {
        $this->complaint = $complaint;
        $this->newStatus = $newStatus;
        $this->note = $note;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: "Your Complaint {$this->complaint->ticket_id} Has Been Updated — Daet Listens",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            markdown: 'emails.complaint-updated',
            with: [
                'ticketId' => $this->complaint->ticket_id,
                'newStatus' => $this->newStatus,
                'note' => $this->note,
                'complaintUrl' => route('complaints.show', $this->complaint),
                'appName' => config('app.name', 'Daet Listens'),
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
