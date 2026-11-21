<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Resident submissions and admin decisions.
 *
 * All decisions run in a transaction so a double click cannot approve/reject
 * twice. File purging is best effort; failures are retried by the scheduled
 * command.
 */
class IdentityVerificationService
{
    public function submit(User $user, array $attributes): IdentityVerification
    {
        $this->checkSubmissionRateLimit($user);

        $idNumberHash = app(IdentityHashService::class)->hash(
            (string) $attributes['id_type'],
            (string) $attributes['id_number'],
        );

        $possibleDuplicate = $this->duplicateFor($idNumberHash, (int) $user->id);

        $verification = DB::transaction(function () use ($user, $attributes, $idNumberHash, $possibleDuplicate): IdentityVerification {
            $pending = IdentityVerification::query()
                ->where('user_id', $user->id)
                ->where('status', IdentityVerification::PENDING)
                ->first();

            if ($pending !== null) {
                // Delete files from the previous submission; we will not keep
                // two sets of ID proof for one resident.
                app(IdentityStorageService::class)->deleteVerificationFiles(
                    (string) $pending->id_front_path,
                    (string) $pending->selfie_path,
                );
                $pending->forceFill(['status' => IdentityVerification::CANCELLED])->save();
            }

            $verification = IdentityVerification::query()->create([
                'user_id' => $user->id,
                'status' => IdentityVerification::PENDING,
                'id_type' => (string) $attributes['id_type'],
                'id_number_hash' => $idNumberHash,
                'id_front_path' => (string) ($attributes['id_front_path'] ?? ''),
                'selfie_path' => (string) ($attributes['selfie_path'] ?? ''),
                'possible_duplicate_user_id' => $possibleDuplicate?->id,
                'submitted_at' => now(),
            ]);

            $user->forceFill(['identity_status' => 'unverified', 'identity_verified_at' => null])->save();

            return $verification;
        });

        app(ActivityLogger::class)->log(
            'identity.submitted',
            "{$user->full_name} submitted a {$verification->id_type} verification.",
            $verification,
            metadata: [
                'verification_id' => $verification->id,
                'id_type' => $verification->id_type,
                'possible_duplicate' => $possibleDuplicate?->id,
            ],
        );

        app(NotificationService::class)->identitySubmitted($verification);

        return $verification;
    }

    public function cancel(User $user, IdentityVerification $verification): void
    {
        if ($verification->user_id !== $user->id || ! $verification->isPending()) {
            abort(403);
        }

        DB::transaction(function () use ($user, $verification): void {
            app(IdentityStorageService::class)->deleteVerificationFiles($verification->id_front_path, $verification->selfie_path);
            $verification->forceFill([
                'status' => IdentityVerification::CANCELLED,
                'files_purged_at' => now(),
            ])->save();

            $user->forceFill(['identity_status' => 'unverified', 'identity_verified_at' => null])->save();
        });

        app(ActivityLogger::class)->log(
            'identity.cancelled',
            "{$user->full_name} cancelled a verification.",
            $verification,
            metadata: ['verification_id' => $verification->id],
        );
    }

    public function approve(User $admin, IdentityVerification $verification): IdentityVerification
    {
        abort_unless($verification->isPending(), 403);

        $verification = $this->decision(
            $admin,
            $verification,
            IdentityVerification::APPROVED,
            action: 'identity.approved',
            description: 'approved',
            purge: true,
        );

        $verification->user->forceFill([
            'identity_status' => 'verified',
            'identity_verified_at' => $verification->reviewed_at,
        ])->save();

        app(NotificationService::class)->identityApproved($verification);

        return $verification;
    }

    public function reject(User $admin, IdentityVerification $verification, string $reason): IdentityVerification
    {
        abort_unless($verification->isPending(), 403);

        if ($reason === '') {
            throw new RuntimeException('A rejection reason is required.');
        }

        $verification = $this->decision(
            $admin,
            $verification,
            IdentityVerification::REJECTED,
            action: 'identity.rejected',
            description: 'rejected',
            purge: true,
            extra: ['rejection_reason' => $reason],
        );

        // Rejected residents may resubmit; make sure the cache no longer says
        // they were previously verified while their latest status is rejected.
        $verification->user->forceFill(['identity_status' => 'unverified', 'identity_verified_at' => null])->save();

        app(NotificationService::class)->identityRejected($verification);

        return $verification;
    }

    public function revoke(User $admin, User $resident, IdentityVerification $verification, string $reason): IdentityVerification
    {
        abort_unless($verification->isApproved(), 403);

        if ($reason === '') {
            throw new RuntimeException('A revocation reason is required.');
        }

        $verification = $this->decision(
            $admin,
            $verification,
            IdentityVerification::REVOKED,
            action: 'identity.revoked',
            description: 'revoked',
            purge: true,
            extra: ['revocation_reason' => $reason],
        );

        $resident->forceFill(['identity_status' => 'unverified', 'identity_verified_at' => null])->save();

        app(NotificationService::class)->identityRevoked($verification);

        return $verification;
    }

    private function decision(
        User $admin,
        IdentityVerification $verification,
        string $status,
        string $action,
        string $description,
        bool $purge,
        array $extra = [],
    ): IdentityVerification {
        if (! in_array($verification->status, [IdentityVerification::PENDING, IdentityVerification::APPROVED], true)) {
            abort(403);
        }

        $result = DB::transaction(function () use ($admin, $verification, $status, $extra): IdentityVerification {
            $verification = IdentityVerification::query()->whereKey($verification->id)->lockForUpdate()->firstOrFail();
            $verification->forceFill([
                'status' => $status,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                ...$extra,
            ])->save();

            return $verification;
        });

        if ($purge) {
            $this->purgeFiles($result);
        }

        app(ActivityLogger::class)->log(
            $action,
            "{$admin->full_name} {$description} a verification ({$result->user->full_name}).",
            $result,
            metadata: [
                'verification_id' => $result->id,
                'id_type' => $result->id_type,
                ...($result->rejection_reason !== null ? ['rejection_reason' => $result->rejection_reason] : []),
                ...($result->revocation_reason !== null ? ['revocation_reason' => $result->revocation_reason] : []),
            ],
        );

        return $result;
    }

    public function purgeFiles(IdentityVerification $verification): bool
    {
        if ($verification->files_purged_at !== null) {
            return true;
        }

        $deleted = app(IdentityStorageService::class)->deleteVerificationFiles(
            $verification->id_front_path,
            $verification->selfie_path,
        );

        $verification->forceFill(['files_purged_at' => now()])->save();

        if ($deleted === 0) {
            app(ActivityLogger::class)->log(
                'identity.purged',
                "{$verification->id} proof files were already absent.",
                $verification,
            );
        }

        return true;
    }

    public function expireOldestPending(int $days = 30): int
    {
        $cutoff = now()->subDays($days)->startOfSecond();
        $verifications = IdentityVerification::query()
            ->where('status', IdentityVerification::PENDING)
            ->where('submitted_at', '<', $cutoff)
            ->get();

        $expired = 0;

        foreach ($verifications as $verification) {
            DB::transaction(function () use ($verification, &$expired): void {
                $updated = IdentityVerification::query()
                    ->whereKey($verification->id)
                    ->where('status', IdentityVerification::PENDING)
                    ->lockForUpdate()
                    ->first();

                if (! $updated) {
                    return;
                }

                $updated->forceFill([
                    'status' => IdentityVerification::EXPIRED,
                    'reviewed_at' => now(),
                ])->save();

                $updated->user->forceFill(['identity_status' => 'unverified', 'identity_verified_at' => null])->save();

                $this->purgeFiles($updated);
                $expired++;
            });

            app(ActivityLogger::class)->log(
                'identity.expired',
                "{$verification->user->full_name}'s pending verification expired.",
                $verification,
            );
        }

        return $expired;
    }

    public function purgeOrphanedDocuments(int $hours = 24): int
    {
        $orphans = 0;
        $storage = app(IdentityStorageService::class);

        foreach (Storage::disk('minio')->directories('identity') as $userDir) {
            foreach (Storage::disk('minio')->allFiles($userDir) as $path) {
                if (! str_ends_with($path, '.enc')) {
                    continue;
                }

                $known = IdentityVerification::query()
                    ->where('id_front_path', $path)
                    ->orWhere('selfie_path', $path)
                    ->exists();

                if ($known) {
                    continue;
                }

                $mtime = Storage::disk('minio')->lastModified($path) ?? null;
                if ($mtime !== null && now()->getTimestamp() - $mtime > ($hours * 3600)) {
                    $storage->deleteDocument($path);
                    $orphans++;
                }
            }
        }

        return $orphans;
    }

    public function duplicateFor(string $idNumberHash, int $userId): ?User
    {
        return User::query()
            ->where('id', '!=', $userId)
            ->where(function ($query) use ($idNumberHash): void {
                $query->whereHas('identityVerifications', function ($q) use ($idNumberHash): void {
                    $q->where('id_number_hash', $idNumberHash);
                });
            })
            ->whereIn('identity_status', ['pending', 'verified'])
            ->first();
    }

    private function checkSubmissionRateLimit(User $user): void
    {
        $key = "identity-submit:{$user->id}:".now()->toDateString();
        $max = (int) config('identity.max_submissions_per_day', 3);

        if ($max <= 0) {
            return;
        }

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'id_type' => __('identity.limit_reached', ['hours' => intdiv($seconds, 3600) + 1]),
            ]);
        }

        RateLimiter::hit($key, 86400);
    }
}
