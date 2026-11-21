<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetIdentityStatusAll extends Command
{
    protected $signature = 'identity:reset-all {--user= : Reset only this user ID} {--apply : Actually reset; otherwise dry-run}';

    protected $description = 'Reset resident identity-verification status to unverified (operation/testing escape hatch).';

    public function handle(): int
    {
        $query = User::query()->where('role', 'citizen');

        if ($this->option('user')) {
            $query->where('id', (int) $this->option('user'));
        }

        $users = $query->where('identity_status', 'verified')->get();

        if (! $this->option('apply')) {
            $this->info("Dry run: {$users->count()} resident(s) currently verified.");
            $users->each(fn (User $user) => $this->line("  #{$user->id} {$user->full_name} ({$user->email})"));
            $this->info('Pass --apply to reset identity_status and identity_verified_at for these users.');

            return self::SUCCESS;
        }

        $users->each(fn (User $user) => $user->forceFill([
            'identity_status' => 'unverified',
            'identity_verified_at' => null,
        ])->save());

        $this->info("Reset {$users->count()} resident(s) to unverified.");

        return self::SUCCESS;
    }
}
