<?php

namespace App\Console\Commands;

use App\Models\IdentityVerification;
use App\Services\IdentityDocumentSanitizer;
use App\Services\IdentityVerificationService;
use Illuminate\Console\Command;

class IdentityReport extends Command
{
    protected $signature = 'identity:report {--json : Output the report as JSON}';

    protected $description = 'Report identity-verification health and counts for Phase 3 dry run.';

    public function handle(IdentityVerificationService $service, IdentityDocumentSanitizer $sanitizer): int
    {
        $report = [
            'verification_required' => (bool) config('identity.verification_required'),
            'sanitizer_available' => $sanitizer->isAvailable(),
            'counts' => [
                'pending' => IdentityVerification::where('status', IdentityVerification::PENDING)->count(),
                'approved' => IdentityVerification::where('status', IdentityVerification::APPROVED)->count(),
                'rejected' => IdentityVerification::where('status', IdentityVerification::REJECTED)->count(),
                'revoked' => IdentityVerification::where('status', IdentityVerification::REVOKED)->count(),
                'expired' => IdentityVerification::where('status', IdentityVerification::EXPIRED)->count(),
                'cancelled' => IdentityVerification::where('status', IdentityVerification::CANCELLED)->count(),
                'possible_duplicates' => IdentityVerification::whereNotNull('possible_duplicate_user_id')->count(),
                'missing_id_front_path' => IdentityVerification::where('status', IdentityVerification::APPROVED)->whereNull('id_front_path')->count(),
                'missing_selfie_path' => IdentityVerification::where('status', IdentityVerification::APPROVED)->whereNull('selfie_path')->count(),
            ],
            'generated_orphan_count' => $service->purgeOrphanedDocuments(24),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('Identity verification report');
            $this->table(['Metric', 'Value'], array_map(
                fn (array $row) => [$row[0], is_array($row[1]) ? json_encode($row[1]) : $row[1]],
                array_merge([
                    ['Verification required', $report['verification_required'] ? 'yes' : 'no'],
                    ['Sanitizer available', $report['sanitizer_available'] ? 'yes' : 'no'],
                ], array_map(
                    fn ($key, $value) => [ucfirst(str_replace('_', ' ', $key)), $value],
                    array_keys($report['counts']),
                    $report['counts'],
                ))
            ));
        }

        return self::SUCCESS;
    }
}
