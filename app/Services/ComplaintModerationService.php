<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintPrivacyService;
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
        // The public copy for a report that names somebody belongs to the
        // Mayor's office's publication review, not to the handling office's
        // moderation form. Refuse the fields outright so a direct POST cannot
        // do what the ticket page does not offer.
        if ($complaint->isPrivacyGated()
            && (array_key_exists('redacted_title', $input) || array_key_exists('redacted_description', $input))) {
            throw ValidationException::withMessages([
                'redacted_title' => 'The public copy for this report is written by the Mayor\'s office on the publication review page.',
            ]);
        }

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

    /**
     * A redacted copy for a report that names somebody, built from what the
     * office ticked must not appear.
     *
     * The public register carries barangay-level detail, so the barangay
     * stays. What the office ticked is removed from the office's own text
     * (when it appears in it). If the party's name was ticked and could not
     * be proven gone, the whole copy is replaced with a generic one that
     * carries no identifying detail at all — an office must never publish
     * text it has not checked.
     *
     * @return array{title: string, description: string, replaced: list<string>, generated: bool}
     */
    public function redactionDraft(Complaint $complaint, bool $stripParty, bool $stripPosition): array
    {
        $neutral = ComplaintPrivacyService::namedPartyKind($complaint->category) === 'person'
            ? 'a member of personnel'
            : 'an establishment';

        $title = (string) $complaint->title;
        $description = (string) $complaint->description;
        $replaced = [];

        if ($stripParty) {
            $this->strikeOut($complaint->named_party, 'the name of the party', $neutral, $title, $description, $replaced);
        }

        if ($stripPosition) {
            $this->strikeOut($complaint->named_party_position, 'their position', $neutral, $title, $description, $replaced);
        }

        // The whole point of redaction is that the name is gone. If it was
        // ticked and could not be proven gone, do not ship prose that may
        // still identify them — ship a safe generic copy instead, and say so.
        if ($stripParty && ! in_array('the name of the party', $replaced, true)) {
            $barangay = $this->clean($complaint->barangay) ?? 'the municipality';
            $person = ComplaintPrivacyService::namedPartyKind($complaint->category) === 'person';

            $title = $person
                ? "A member of personnel in {$barangay} was reported to the office"
                : "An establishment in {$barangay} was reported to the office";
            $description = $person
                ? "A member of personnel in {$barangay} was reported to the office. The identity of that member and the details that would identify them are withheld from the public record."
                : "An establishment in {$barangay} was reported to the office. The identity of the establishment and the details that would identify it are withheld from the public record.";
            $replaced[] = 'the copy — replaced with a safe generic one, because the name could not be removed automatically';

            return ['title' => $title, 'description' => $description, 'replaced' => $replaced, 'generated' => true];
        }

        return [
            'title' => mb_strimwidth($title, 0, 255, ''),
            'description' => mb_strimwidth($description, 0, 5000, ''),
            'replaced' => $replaced,
            'generated' => $replaced === [],
        ];
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
     * Remove every occurrence of a string from the copy, case-insensitively,
     * and record the label when it removed anything.
     */
    private function strikeOut(mixed $needle, string $label, string $neutral, string &$title, string &$description, array &$replaced): void
    {
        $needle = $this->clean(is_string($needle) ? $needle : null);

        if ($needle === null || mb_strlen($needle) < 2) {
            return;
        }

        $before = $title."\n".$description;
        $pattern = '/'.preg_quote($needle, '/').'/iu';
        $title = preg_replace($pattern, $neutral, $title) ?? $title;
        $description = preg_replace($pattern, $neutral, $description) ?? $description;

        if (($title."\n".$description) !== $before) {
            $replaced[] = $label;
        }
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
