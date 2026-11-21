<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP enrollment, verification, and recovery codes for administrator accounts.
 *
 * The secret is stored through the User model's encrypted cast. Recovery codes
 * are hashed individually; the plaintext exists only in the response that
 * displays them once.
 */
class TwoFactorAuthentication
{
    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            (string) config('admin.two_factor.issuer', config('app.name', 'Daet Listens')),
            $user->email,
            $secret,
        );
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd(),
        );

        $svg = (new Writer($renderer))->writeString($this->otpauthUrl($user, $secret));

        // The XML prolog is not needed inside an HTML document and some
        // browsers render it as text when it is inlined.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg) ?? $svg;
    }

    /**
     * Verify a login code. A code may be used once: the package is given the
     * last accepted time-step and only returns a newer one.
     */
    public function verify(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $code = $this->normalizeCode($code);

        if (strlen($code) !== 6) {
            return false;
        }

        $step = $this->google2fa->verifyKeyNewer(
            $secret,
            $code,
            (int) ($user->two_factor_last_used_step ?? 0),
            (int) config('admin.two_factor.window', 1),
        );

        if (! is_int($step)) {
            return false;
        }

        $user->forceFill(['two_factor_last_used_step' => $step])->save();

        return true;
    }

    /**
     * Verify a code before the secret is confirmed. No replay marker is
     * written because enrollment is not a login.
     */
    public function verifyFresh(string $secret, string $code): bool
    {
        $code = $this->normalizeCode($code);

        if (strlen($code) !== 6) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey(
            $secret,
            $code,
            (int) config('admin.two_factor.window', 1),
        );
    }

    /**
     * Confirm a pending secret and return the plaintext recovery codes once.
     *
     * @return array<int, string>|null
     */
    public function enable(User $user, string $secret, string $code): ?array
    {
        if (! $this->verifyFresh($secret, $code)) {
            return null;
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
            // The code just used for enrollment must not be accepted again.
            'two_factor_last_used_step' => $this->google2fa->getTimestamp(),
        ])->save();

        return $recoveryCodes;
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $count = max(1, (int) config('admin.two_factor.recovery_codes', 8));
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $plain = '';

            for ($j = 0; $j < 10; $j++) {
                $plain .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $codes[] = substr($plain, 0, 5).'-'.substr($plain, 5);
        }

        return $codes;
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(
            fn (string $code): string => Hash::make($code),
            $codes,
        );
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = $this->normalizeRecoveryCode($code);

        foreach ($codes as $index => $hash) {
            if (! is_string($hash) || ! Hash::check($normalized, $hash)) {
                continue;
            }

            unset($codes[$index]);

            $user->forceFill([
                'two_factor_recovery_codes' => array_values($codes),
            ])->save();

            return true;
        }

        return false;
    }

    public function recoveryCodesRemaining(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }

    public function reset(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_used_step' => null,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function dialogData(User $user): array
    {
        $confirmed = $user->hasTwoFactorEnabled();
        $authorized = ! $confirmed && (bool) session('2fa.setup_authorized', false);
        $secret = $user->two_factor_secret;

        if (! $confirmed && $authorized && (! is_string($secret) || $secret === '')) {
            $secret = $this->generateSecret();
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        return [
            'user' => $user,
            'confirmed' => $confirmed,
            'needsPassword' => ! $confirmed && ! $authorized,
            'secret' => is_string($secret) ? $secret : null,
            'qrSvg' => ($confirmed || is_string($secret) && $secret !== '') ? $this->qrCodeSvg($user, (string) $secret) : null,
            'recoveryCodes' => session('two_factor_recovery_codes', []),
            'remainingRecoveryCodes' => $this->recoveryCodesRemaining($user),
        ];
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/\D+/', '', $code) ?? '';
    }

    private function normalizeRecoveryCode(string $code): string
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');

        if (strlen($normalized) === 10) {
            $normalized = substr($normalized, 0, 5).'-'.substr($normalized, 5);
        }

        return $normalized;
    }
}
