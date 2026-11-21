<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TwoFactorAuthentication;
use Illuminate\Console\Command;

/**
 * Last-resort recovery for an administrator who has lost both the authenticator
 * and the printed recovery codes. Requires server access, which is the point.
 */
class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa
                            {email : The administrator email address}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Remove an administrator\'s TOTP secret and recovery codes so they can enroll again.';

    public function handle(TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("No account found for {$email}.");

            return self::FAILURE;
        }

        if (! $user->isAdmin()) {
            $this->error("{$user->email} is not an administrator account.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Reset two-factor authentication for {$user->email}?")) {
            $this->line('Cancelled.');

            return self::SUCCESS;
        }

        $twoFactor->reset($user);

        $activityLogger->log(
            'auth.2fa.reset',
            "Two-factor authentication was reset for {$user->full_name} from the console.",
            $user,
            actor: null,
        );

        $this->info("Two-factor authentication reset for {$user->email}. They must enroll again at the next sign-in.");

        return self::SUCCESS;
    }
}
