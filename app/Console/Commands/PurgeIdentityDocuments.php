<?php

namespace App\Console\Commands;

use App\Services\IdentityVerificationService;
use Illuminate\Console\Command;

class PurgeIdentityDocuments extends Command
{
    protected $signature = 'identity:purge {--expired : Only expire pending submissions}';

    protected $description = 'Expire pending identity submissions and sweep orphan identity files.';

    public function handle(IdentityVerificationService $service): int
    {
        $expired = $service->expireOldestPending(30);
        $orphans = $service->purgeOrphanedDocuments(24);

        $this->info("Expired {$expired} pending submission(s); purged {$orphans} orphan file(s).");

        return self::SUCCESS;
    }
}
