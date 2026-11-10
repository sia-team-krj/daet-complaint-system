<?php

namespace App\Services;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Mail\ComplaintUpdatedMail;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers the resident-facing "your ticket moved" email for every update a
 * staff member or administrator makes to a complaint. The in-app bell
 * notification stays the primary channel; this is the same news, arriving
 * where the resident actually reads it.
 *
 * Brevo is used when it is configured (or in production) and falls back to
 * the configured Laravel mailer otherwise, which keeps local Mailpit/SMTP
 * testing on the same code path. Callers are expected to treat delivery as
 * best-effort (try/catch + report), exactly like the in-app notification.
 */
class ComplaintUpdateDelivery
{
    public function __construct(
        private readonly BrevoComplaintUpdateSender $brevoSender,
    ) {}

    /**
     * The status moved — staff, admin, or the automatic closer.
     */
    public function statusChanged(Complaint $complaint, ComplaintStatus $status, ?string $note = null): void
    {
        $this->deliver($complaint, fn (Complaint $complaint): array => [
            'subject' => __('mail.ticket_status_subject', ['ticket' => $complaint->ticket_id, 'status' => $status->label()]),
            'headline' => __('mail.ticket_status_headline'),
            'summary' => __('mail.ticket_status_summary', ['status' => $status->label()]),
            'note' => $note,
            'noteLabel' => __('mail.ticket_note_label'),
        ]);
    }

    /**
     * A ticket the system closed after the resident went quiet on a
     * Resolved complaint.
     */
    public function autoClosed(Complaint $complaint, int $days): void
    {
        $this->deliver($complaint, fn (Complaint $complaint): array => [
            'subject' => __('mail.ticket_autoclose_subject', ['ticket' => $complaint->ticket_id]),
            'headline' => __('mail.ticket_autoclose_headline'),
            'summary' => __('mail.ticket_autoclose_summary', ['days' => $days]),
            'note' => null,
            'noteLabel' => null,
        ]);
    }

    /**
     * The department settled the review — verified, rejected, needs more
     * information, duplicate, escalated.
     */
    public function reviewUpdated(Complaint $complaint, ComplaintReviewStatus $reviewStatus): void
    {
        $this->deliver($complaint, fn (Complaint $complaint): array => [
            'subject' => __('mail.ticket_review_subject', ['ticket' => $complaint->ticket_id, 'decision' => $reviewStatus->label()]),
            'headline' => __('mail.ticket_review_headline'),
            'summary' => __('mail.ticket_review_summary', ['decision' => $reviewStatus->label()]),
            'note' => null,
            'noteLabel' => null,
        ]);
    }

    /**
     * The department asked the resident for better evidence; the resident
     * must act, so the question is the body of the email.
     */
    public function informationRequested(Complaint $complaint, ComplaintInformationRequest $request): void
    {
        $this->deliver($complaint, fn (Complaint $complaint): array => [
            'subject' => __('mail.ticket_information_subject', ['ticket' => $complaint->ticket_id]),
            'headline' => __('mail.ticket_information_headline'),
            'summary' => __('mail.ticket_information_summary', ['ticket' => $complaint->ticket_id]),
            'note' => $request->question,
            'noteLabel' => __('mail.ticket_information_note_label'),
        ]);
    }

    /**
     * @param  callable(Complaint): array{subject: string, headline: string, summary: string, note: string|null, noteLabel: string|null}  $build
     */
    private function deliver(Complaint $complaint, callable $build): void
    {
        $recipient = $complaint->user;

        // Nobody to email, or an account that no longer wants the mail.
        if ($recipient === null || $recipient->email === null || ! $recipient->is_active) {
            return;
        }

        // The resident's saved language wins; otherwise the system default.
        $locale = in_array($recipient->locale, ['en', 'fil'], true)
            ? $recipient->locale
            : (string) SystemSetting::getValue('default_locale', 'en');

        $previousLocale = App::getLocale();
        App::setLocale($locale);
        Lang::setLocale($locale);

        try {
            ['subject' => $subject, 'headline' => $headline, 'summary' => $summary, 'note' => $note, 'noteLabel' => $noteLabel] = $build($complaint);

            $mail = new ComplaintUpdatedMail($complaint, $subject, [
                'firstName' => $recipient->first_name ?: 'there',
                'ticketId' => $complaint->ticket_id,
                'headline' => $headline,
                'summary' => $summary,
                'note' => $note,
                'noteLabel' => $noteLabel,
                'url' => route('complaints.show', $complaint),
            ]);

            if ($this->shouldUseBrevo()) {
                $this->brevoSender->send($complaint, $mail);

                return;
            }

            Mail::to($recipient->email)->send($mail);
        } finally {
            App::setLocale($previousLocale);
            Lang::setLocale($previousLocale);
        }
    }

    private function shouldUseBrevo(): bool
    {
        return trim((string) config('services.brevo.api_key')) !== ''
            || app()->environment('production');
    }
}
