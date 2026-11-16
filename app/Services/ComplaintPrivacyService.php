<?php

namespace App\Services;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\PublicationStatus;
use App\Models\Complaint;
use App\Models\User;

/**
 * The privacy gate for complaints that name somebody.
 *
 * Two complaint categories name a party: how you were treated at the LGU
 * names a member of personnel, and the plastic policy names a business. For
 * those, the name of the party is captured in a field that never leaves the
 * office, and the complaint is filed private.
 *
 * It reaches the public transparency register only when every one of these
 * holds:
 *
 *   1. the owning office has settled it — resolved or closed,
 *   2. the information has been verified as true,
 *   3. the Mayor's office has redacted the public copy,
 *   4. the Mayor's office has approved the publication.
 *
 * The handoff is automatic: once the owning office resolves a verified report,
 * it goes to the Mayor's office without a separate request. Redaction belongs
 * to the Mayor's office too — the handling office never writes the public copy.
 *
 * A complaint that is genuinely a privacy concern is declined, and it is never
 * published. The resident is told what happened either way.
 *
 * The rules live in config/complaint_privacy_policy.php so that a category
 * can be gated, or ungated, without touching code.
 */
class ComplaintPrivacyService
{
    /**
     * Whether the category names somebody.
     */
    public static function isGated(?string $category): bool
    {
        if ($category === null || $category === '') {
            return false;
        }

        return array_key_exists($category, self::categories());
    }

    /**
     * What the named party is: a person or a business. Null when the category
     * is not gated.
     */
    public static function namedPartyKind(?string $category): ?string
    {
        return self::policy($category)['named_party'] ?? null;
    }

    /**
     * Whether the category also asks for the party's position or office.
     */
    public static function asksForPosition(?string $category): bool
    {
        return (bool) (self::policy($category)['named_party_position'] ?? false);
    }

    /**
     * The office whose staff approve publication. The Mayor's office.
     */
    public static function approvalOfficeCode(): string
    {
        return (string) config('complaint_privacy_policy.approval_office_code', 'OM');
    }

    /**
     * Whether this user may approve a publication: a member of the approval
     * office, or an administrator.
     *
     * Staff may approve only when their active department's code matches the
     * configured approval office code (OM = Office of the Mayor).  Admins are
     * always allowed as the fallback that stops a department from blocking the
     * feature when its sole verifier is away.
     */
    public static function canApprove(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        // Staff must have an active department whose code matches the
        // approval office. Case-insensitive, trimmed comparison, so a
        // code stored as 'om' or 'OM ' still resolves correctly.
        $userDeptCode = $user->department?->code;

        return ! is_null($userDeptCode)
            && strtolower(trim((string) $userDeptCode)) ===
               strtolower(trim(self::approvalOfficeCode()));
    }

    /**
     * Whether this complaint has been settled by the office.
     */
    public static function isSettled(Complaint $complaint): bool
    {
        return in_array($complaint->status, [
            ComplaintStatus::Resolved->value,
            ComplaintStatus::Closed->value,
        ], true);
    }

    /**
     * Whether the department has verified the information as true.
     */
    public static function isVerified(Complaint $complaint): bool
    {
        if ($complaint->verified_at !== null) {
            return true;
        }

        return $complaint->review_status === ComplaintReviewStatus::Verified->value;
    }

    /**
     * Whether the sensitive parts have been redacted for the public view.
     */
    public static function isRedacted(Complaint $complaint): bool
    {
        return $complaint->redacted_title !== null || $complaint->redacted_description !== null;
    }

    /**
     * What still has to happen before a settled report can be sent to the
     * Mayor's office. Redaction is deliberately absent: the Mayor's office
     * writes the public copy after the handoff.
     *
     * @return list<string>
     */
    public static function handoffBlockers(Complaint $complaint): array
    {
        $reasons = [];

        if (! self::isSettled($complaint)) {
            $reasons[] = 'The office has not resolved this report yet.';
        }

        if (! self::isVerified($complaint)) {
            $reasons[] = 'The information has not been verified as true.';
        }

        return $reasons;
    }

    /**
     * What still has to happen before the Mayor's office can approve the
     * publication: the report must be settled, verified and redacted.
     *
     * @return list<string>
     */
    public static function approvalBlockers(Complaint $complaint): array
    {
        $reasons = self::handoffBlockers($complaint);

        if (! self::isRedacted($complaint)) {
            $reasons[] = 'The Mayor\'s office has not saved the redacted public copy.';
        }

        return $reasons;
    }

    /**
     * Whether the complaint is published right now.
     */
    public static function isPublished(Complaint $complaint): bool
    {
        return $complaint->publicationStatus() === PublicationStatus::Approved;
    }

    /**
     * @return array<string, mixed>
     */
    private static function policy(?string $category): array
    {
        $categories = self::categories();

        return $categories[$category] ?? config('complaint_privacy_policy.default', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function categories(): array
    {
        return (array) config('complaint_privacy_policy.categories', []);
    }
}
