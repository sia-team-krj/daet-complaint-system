<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoOtpSender
{
    public function send(string $code, User $user, string $email, string $purpose): void
    {
        $apiKey = trim((string) config('services.brevo.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $appName = (string) config('mail.from.name', 'Daet Listens');
        $expiryMinutes = (int) config('email-verification.expires_in_minutes', 10);
        $htmlContent = view('emails.email-otp-html', [
            'code' => $code,
            'purpose' => $purpose,
            'expiryMinutes' => $expiryMinutes,
            'appName' => $appName,
        ])->render();

        $textContent = $purpose === 'email_change'
            ? "We received a request to change the email address on your account.\n\n"
                ."Your verification code is: {$code}\n\n"
                ."This code expires in {$expiryMinutes} minutes."
            : "Use this code to verify your {$appName} email address.\n\n"
                ."Your verification code is: {$code}\n\n"
                ."This code expires in {$expiryMinutes} minutes.";

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
                        'email' => $email,
                    ],
                ],
                'subject' => 'Verify your Daet Listens email address',
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
                'tags' => ['email-verification', 'otp'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Brevo OTP delivery failed with HTTP status {$response->status()}.");
        }
    }
}
