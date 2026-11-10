<?php

namespace App\Services;

use App\Mail\ComplaintUpdatedMail;
use App\Models\Complaint;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoComplaintUpdateSender
{
    public function send(Complaint $complaint, ComplaintUpdatedMail $mail): void
    {
        $apiKey = trim((string) config('services.brevo.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $recipient = $complaint->user;

        if ($recipient === null) {
            throw new RuntimeException('The complaint has no recipient to email.');
        }

        $appName = (string) config('mail.from.name', 'Daet Listens');
        $htmlContent = view('mail.complaint-update', $mail->viewData())->render();
        $textContent = $mail->textContent();

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])
            ->timeout(15)
            ->connectTimeout(5)
            ->post((string) config('services.brevo.endpoint', 'https://api.brevo.com/v3/smtp/email'), [
                'sender' => [
                    'name' => (string) config('services.brevo.from_name', $appName),
                    'email' => (string) config('services.brevo.from_email', config('mail.from.address')),
                ],
                'to' => [
                    [
                        'name' => $recipient->full_name,
                        'email' => $recipient->email,
                    ],
                ],
                'subject' => $mail->subjectLine,
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
                'tags' => ['ticket-update'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Brevo ticket update delivery failed with HTTP status {$response->status()}.");
        }
    }
}
