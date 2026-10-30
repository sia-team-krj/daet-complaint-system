<?php

namespace App\Services;

use App\Mail\EmailOtp;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public const PURPOSE_REGISTRATION = 'registration';

    public const PURPOSE_EMAIL_CHANGE = 'email_change';

    public function __construct(
        private readonly BrevoOtpSender $brevoOtpSender,
    ) {}

    public function issue(User $user, string $email, string $purpose): EmailVerificationCode
    {
        $email = strtolower(trim($email));
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = DB::transaction(function () use ($user, $email, $purpose, $code): EmailVerificationCode {
            EmailVerificationCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return EmailVerificationCode::create([
                'user_id' => $user->id,
                'email' => $email,
                'purpose' => $purpose,
                'code_hash' => $this->hashCode($code),
                'expires_at' => now()->addMinutes((int) config('email-verification.expires_in_minutes', 10)),
            ]);
        });

        if ($this->shouldUseBrevo()) {
            $this->brevoOtpSender->send($code, $user, $email, $purpose);
        } else {
            Mail::to($email)->send(new EmailOtp($code, $user, $purpose));
        }

        return $verification;
    }

    public function verify(User $user, string $email, string $purpose, string $code): bool
    {
        $normalizedCode = preg_replace('/\D+/', '', $code) ?? '';
        $email = strtolower(trim($email));

        return DB::transaction(function () use ($user, $email, $purpose, $normalizedCode): bool {
            $verification = EmailVerificationCode::query()
                ->where('user_id', $user->id)
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $verification
                || $verification->isExpired()
                || $verification->attempts >= max(1, (int) config('email-verification.max_attempts', 5))) {
                return false;
            }

            if (! hash_equals($verification->code_hash, $this->hashCode($normalizedCode))) {
                $verification->attempts++;
                $verification->save();

                return false;
            }

            $verification->update(['consumed_at' => now()]);

            return true;
        });
    }

    public function pendingFor(User $user, string $purpose): ?EmailVerificationCode
    {
        return EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
    }

    private function shouldUseBrevo(): bool
    {
        return trim((string) config('services.brevo.api_key')) !== ''
            || app()->environment('production');
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
