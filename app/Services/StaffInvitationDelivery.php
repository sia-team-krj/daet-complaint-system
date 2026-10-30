<?php

namespace App\Services;

use App\Mail\StaffInvitationMail;
use App\Models\InvitationCode;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class StaffInvitationDelivery
{
    public function __construct(
        private readonly BrevoStaffInvitationSender $brevoSender,
    ) {}

    /**
     * Deliver an invitation link to the address it was issued for.
     *
     * Uses the Brevo API when it is configured (or in production) and falls
     * back to the configured Laravel mailer otherwise, which keeps local
     * Mailpit/SMTP testing on the same code path.
     */
    public function send(InvitationCode $invitation, string $url): void
    {
        if (! $invitation->isReservedToEmail()) {
            throw new RuntimeException('This invitation has no invitee email address to deliver to.');
        }

        if ($this->shouldUseBrevo()) {
            $this->brevoSender->send($invitation, $url);

            return;
        }

        Mail::to($invitation->invitee_email)->send(new StaffInvitationMail($invitation, $url));
    }

    private function shouldUseBrevo(): bool
    {
        return trim((string) config('services.brevo.api_key')) !== ''
            || app()->environment('production');
    }
}
