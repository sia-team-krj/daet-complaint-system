<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\IdentityVerification;
use Illuminate\Console\Command;

class IdentityMonitor extends Command
{
    protected $signature = 'identity:monitor';

    protected $description = 'Daily operational monitor for the identity-verification workflow.';

    public function handle(): int
    {
        $pending = IdentityVerification::where('status', IdentityVerification::PENDING)->count();
        $oldestPending = IdentityVerification::where('status', IdentityVerification::PENDING)->min('submitted_at');
        $duplicates = IdentityVerification::whereNotNull('possible_duplicate_user_id')->count();
        $missingPurgeProof = IdentityVerification::query()
            ->whereNotIn('status', [IdentityVerification::PENDING])
            ->whereNull('files_purged_at')
            ->where(fn ($q) => $q->whereNotNull('id_front_path')->orWhereNotNull('selfie_path'))
            ->count();
        $twoFaLockouts = ActivityLog::query()
            ->where('action', 'auth.2fa.challenge_failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $this->table([
            'Metric',
            'Value',
            'Threshold',
        ], [
            ['Pending submissions', $pending, 'Review oldest after 30 days'],
            ['Oldest pending', $oldestPending ? $oldestPending->diffForHumans(null, true) : '—', 'None'],
            ['Possible duplicates', $duplicates, 'Review weekly'],
            ['Decisions missing purge proof', $missingPurgeProof, 'Should be 0 after purge command'],
            ['2FA lockouts (24h)', $twoFaLockouts, 'Review if spikes'],
        ]);

        return self::SUCCESS;
    }
}
