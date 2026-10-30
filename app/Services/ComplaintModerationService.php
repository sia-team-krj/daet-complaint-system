<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moderation of a complaint's public presentation.
 *
 * This is deliberately NOT the same thing as visibility, and the split matters:
 *
 *   - Visibility (`is_public`) is the resident's consent. It is written when
 *     they file, changed only by them, and no service here touches it.
 *   - Moderation is a staff/admin decision to withhold or clean up content that
 *     is foul, fraudulent, or sensitive. It can withhold a complaint somebody
 *     consented to publish, and it can never publish one they declined.
 *
 * Because an official can now act against a complaint a resident made public,
 * every action is reason-bearing, attributed, and written to the audit trail.
 * An unexplained disappearance from the public record is exactly the thing this
 * system exists to prevent.
 */
class ComplaintModerationService
{
    /**
     * @param  array<int, string>  $withheldImagePaths
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function apply(Complaint $complaint, User $actor, array $input): array
    {
        $hiddenFromPublic = (bool) ($input['hidden_from_public'] ?? false);
        $redactedTitle = $this->clean($input['redacted_title'] ?? null);
        $redactedDescription = $this->clean($input['redacted_description'] ?? null);
        $reason = $this->clean($input['moderation_reason'] ?? null);

        $existing = $complaint->withheldImagePaths();
        $stillWithheld = array_values(array_unique(array_merge(
            $existing,
            $this->ownedPaths($complaint, (array) ($input['withheld_image_paths'] ?? [])),
        )));

        // A withheld path is a decision about a specific photo, so it can be
        // revoked. Removing it restores the photo to the public view.
        if (array_key_exists('restore_image_paths', $input)) {
            $restore = array_map('strval', (array) $input['restore_image_paths']);
            $stillWithheld = array_values(array_diff($stillWithheld, $restore));
        }

        $acting = $hiddenFromPublic
            || $redactedTitle !== null
            || $redactedDescription !== null
            || $stillWithheld !== $existing;

        if (! $acting) {
            return [];
        }

        // Without a reason this is just an unexplained edit to the public
        // record, which is the failure mode this feature could create.
        if ($reason === null) {
            throw ValidationException::withMessages([
                'moderation_reason' => 'Give a reason. Anything removed from the transparency page must be explainable.',
            ]);
        }

        $updates = [
            'hidden_from_public' => $hiddenFromPublic,
            'redacted_title' => $redactedTitle,
            'redacted_description' => $redactedDescription,
            'withheld_image_paths' => $stillWithheld,
            'moderation_reason' => $reason,
            'moderated_by' => $actor->id,
            'moderated_at' => now(),
        ];

        DB::transaction(function () use ($complaint, $updates) {
            // `is_public` is intentionally not in $updates. Moderation must not
            // be able to publish a private complaint, and this is the assertion
            // that it cannot.
            $complaint->update($updates);
            $complaint->refresh();
        });

        return $updates;
    }

    /** A short sentence describing what was withheld, for the audit trail. */
    public function summary(Complaint $complaint, array $updates): string
    {
        $parts = [];

        if ($updates['hidden_from_public'] ?? false) {
            $parts[] = 'hidden from the public register';
        }

        if ($updates['redacted_title'] ?? null) {
            $parts[] = 'title redacted';
        }

        if ($updates['redacted_description'] ?? null) {
            $parts[] = 'description redacted';
        }

        $withheldCount = count($updates['withheld_image_paths'] ?? []);

        if ($withheldCount > 0) {
            $parts[] = $withheldCount === 1
                ? 'one photo withheld'
                : "{$withheldCount} photos withheld";
        }

        return $parts === []
            ? 'content moderated'
            : 'public listing '.implode(', ', $parts);
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Keep only paths this complaint actually owns, so a crafted request cannot
     * withhold evidence belonging to some other report.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function ownedPaths(Complaint $complaint, array $paths): array
    {
        $owned = $complaint->allImagePaths();

        return array_values(array_intersect(
            array_map('strval', $paths),
            $owned,
        ));
    }
}
