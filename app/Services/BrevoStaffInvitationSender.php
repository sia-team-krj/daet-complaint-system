<?php

namespace App\Services;

use App\Models\InvitationCode;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoStaffInvitationSender
{
    public function send(InvitationCode $invitation, string $url): void
    {
        $apiKey = trim((string) config('services.brevo.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $invitation->loadMissing('department');
        $appName = (string) config('mail.from.name', 'Daet Listens');
        $expiryHours = (int) config('invitations.expires_in_hours', 24);

        $htmlContent = view('mail.staff-invitation', [
            'url' => $url,
            'inviteeName' => $invitation->invitee_name,
            'inviteeEmail' => $invitation->invitee_email,
            'departmentName' => $invitation->department?->name ?? 'the assigned department',
            'expiresAt' => $invitation->expires_at,
        ])->render();

        $greeting = $invitation->invitee_name ? "Hello {$invitation->invitee_name}," : 'Hello,';

        $textContent = $greeting."\n\n"
            ."You have been invited to join the Daet Listens staff portal as a member of "
            .($invitation->department?->name ?? 'the assigned department').".\n\n"
            ."Open this secure link to create your account and choose your password:\n{$url}\n\n"
            ."This link expires on {$invitation->expires_at?->format('M j, Y g:i A')} and can only be used once.\n\n"
            ."This invitation is reserved for {$invitation->invitee_email}.";

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
                        'name' => $invitation->invitee_name ?: $invitation->invitee_email,
                        'email' => (string) $invitation->invitee_email,
                    ],
                ],
                'subject' => 'Your Daet Listens staff invitation',
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
                'tags' => ['staff-invitation'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Brevo staff invitation delivery failed with HTTP status {$response->status()}.");
        }
    }
}
