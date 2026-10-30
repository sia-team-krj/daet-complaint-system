<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoPasswordResetSender
{
    public function send(User $user, string $url): void
    {
        $apiKey = trim((string) config('services.brevo.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $appName = (string) config('mail.from.name', 'Daet Listens');
        $htmlContent = view('mail.password-reset', [
            'url' => $url,
            'user' => $user,
        ])->render();

        $textContent = "We received a request to reset your Daet Listens password.\n\n"
            ."Open this secure link to choose a new password:\n{$url}\n\n"
            .'This link expires in 60 minutes. If you did not request it, you can ignore this email.';

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
                        'name' => $user->full_name,
                        'email' => $user->email,
                    ],
                ],
                'subject' => 'Reset your Daet Listens password',
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
                'tags' => ['password-reset'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Brevo password reset delivery failed with HTTP status {$response->status()}.");
        }
    }
}
