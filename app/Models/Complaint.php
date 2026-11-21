<?php

namespace App\Models;

use App\Enums\ComplaintMarking;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\HoldReason;
use App\Enums\PublicationStatus;
use App\Enums\VerificationMethod;
use App\Services\ComplaintPrivacyService;
use App\Services\DepartmentRouter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'department_id',
        'category',
        'title',
        'description',
        'image_path',
        'image_paths',
        'latitude',
        'longitude',
        'address_text',
        'barangay',
        'intake_mode',
        'work_mode',
        'urgency',
        'status',
        'resolved_at',
        'closed_at',
        'closed_by',
        'is_public',
        'hidden_from_public',
        'redacted_title',
        'redacted_description',
        'withheld_image_paths',
        'moderation_reason',
        'moderated_by',
        'moderated_at',
        'assigned_staff_id',
        'verifying_staff_id',
        'verification_method',
        'content_fingerprint',
        'spam_status',
        'spam_score',
        'spam_reasons',
        'similarity_score',
        // The complaint this one follows as a sub-ticket (a follow-up). Null on
        // the originating complaint; set on every sub-ticket.
        'parent_id',
        'duplicate_of_id',
        'suggested_priority',
        'confirmed_priority',
        'review_status',
        'suggestion_reasons',
        'reviewed_by',
        'reviewed_at',
        'verified_at',
        'review_notes',
        // Office marking of the report — misleading or spam. The marking is
        // the office's rejection of the report; see ComplaintMarkingService.
        'marking',
        'mark_description',
        'mark_comment',
        'marked_by',
        'marked_at',
        'marking_confirmed_at',
        'marking_confirmed_by',
        'marking_dismissed_at',
        'marking_dismissed_by',
        'named_party',
        'named_party_position',
        'publication_status',
        'publication_requested_at',
        'publication_requested_by',
        'publication_decided_at',
        'publication_decided_by',
        'publication_note',
        'hold_reason',
        'hold_note',
        'hold_internal_note',
        'hold_expected_resume_at',
        'hold_started_at',
        'hold_started_by',
        'hold_total_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'hidden_from_public' => 'boolean',
            'withheld_image_paths' => 'array',
            'moderated_at' => 'datetime',
            'publication_requested_at' => 'datetime',
            'publication_decided_at' => 'datetime',
            'hold_expected_resume_at' => 'date',
            'hold_started_at' => 'datetime',
            'hold_total_minutes' => 'integer',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'image_paths' => 'array',
            'spam_status' => 'string',
            'spam_score' => 'integer',
            'spam_reasons' => 'array',
            'spam_confirmed_at' => 'datetime',
            'similarity_score' => 'float',
            'suggestion_reasons' => 'array',
            'reviewed_at' => 'datetime',
            'verified_at' => 'datetime',
            'suggested_priority' => 'string',
            'confirmed_priority' => 'string',
            'review_status' => 'string',
            'marking' => ComplaintMarking::class,
            'marked_at' => 'datetime',
            'marking_confirmed_at' => 'datetime',
            'marking_dismissed_at' => 'datetime',
            'verification_method' => VerificationMethod::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // ─────────────────────────────────────────────
    // TICKET ID GENERATION
    // ─────────────────────────────────────────────

    /**
     * booted() is the correct Laravel 9+ pattern.
     * Do NOT use the old boot() method — it requires parent::boot()
     * and can conflict with trait-level boot methods.
     *
     * Ticket ID format: COMP-2026-A7K9P (random, 5 unambiguous chars)
     *
     * WHY random and not sequential?
     * Sequential ids (COMP-2026-00001, …) are enumerable: a resident can
     * guess another resident's ticket by incrementing. Random ids resist that
     * while staying short enough to read over the phone and type into
     * /complaints/track.
     *
     * Collisions are handled by a retry loop that checks ticket_id uniqueness
     * (including soft-deleted rows, so ids are never reused after a soft
     * delete) up to TICKET_MAX_ATTEMPTS times. The DB unique constraint is
     * the final backstop.
     *
     * A manually supplied ticket_id (seeders, tests, admin tooling) is
     * preserved via the filled() guard.
     */
    public const TICKET_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const TICKET_RANDOM_LENGTH = 5;

    public const TICKET_MAX_ATTEMPTS = 10;

    /**
     * Find a complaint by ticket number, whatever way the holder typed it.
     *
     * The ticket is the only secret on the public tracking page, so the lookup
     * should not fail because somebody left out a hyphen or used a lower-case
     * letter. Punctuation and case are normalised on both sides; the stored
     * value is compared with PostgreSQL's REGEXP_REPLACE.
     *
     * Soft-deleted rows are excluded by the default scope, and a complaint the
     * administrator confirmed as spam is treated as if it does not exist.
     */
    public static function findByTicketId(?string $input): ?self
    {
        $normalized = self::normalizeTicketId($input);

        if ($normalized === '') {
            return null;
        }

        return static::query()
            ->whereRaw(
                "UPPER(REGEXP_REPLACE(ticket_id, '[^A-Za-z0-9]', '', 'g')) = ?",
                [$normalized]
            )
            ->whereNull('spam_confirmed_at')
            ->with('department')
            ->first();
    }

    /**
     * Uppercase letters and digits only, capped at a sane length.
     *
     * Shared with the controller so the input limit and the stored-side
     * comparison cannot drift apart.
     */
    public static function normalizeTicketId(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        $normalized = preg_replace('/[^A-Za-z0-9]/', '', trim($input)) ?? '';

        return substr(strtoupper($normalized), 0, 30);
    }

    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint) {
            if (filled($complaint->ticket_id)) {
                return; // preserve explicitly supplied ids
            }

            $year = now()->format('Y');
            $alphabet = self::TICKET_ALPHABET;
            $maxIndex = strlen($alphabet) - 1;

            for ($attempt = 1; $attempt <= self::TICKET_MAX_ATTEMPTS; $attempt++) {
                $suffix = '';
                for ($i = 0; $i < self::TICKET_RANDOM_LENGTH; $i++) {
                    $suffix .= $alphabet[random_int(0, $maxIndex)];
                }

                $candidate = "COMP-{$year}-{$suffix}";

                if (! static::withTrashed()->where('ticket_id', $candidate)->exists()) {
                    $complaint->ticket_id = $candidate;

                    return;
                }
            }

            throw new \RuntimeException('Unable to generate a unique complaint ticket id.');
        });
    }

    // ─────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────

    /**
     * The citizen who filed the complaint.
     * May be NULL if the user's account was soft-deleted.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The department responsible for resolving this complaint.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The full audit trail for this complaint.
     * Ordered newest-first so the citizen sees the latest update at the top.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ComplaintLog::class)->latest();
    }

    /**
     * The staff member assigned to this complaint.
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /**
     * The staff member checking whether this report is genuine. Kept separate
     * from assignedStaff() on purpose — see the migration note.
     */
    public function verifyingStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifying_staff_id');
    }

    /**
     * The separation-of-duties control. A verifier may not be the person who
     * does the work, and admin never counts as a conflict. This holds even in a
     * department small enough that one person wears both hats, which a role
     * split would not survive.
     */
    public function hasSelfVerificationConflict(): bool
    {
        return $this->verifying_staff_id !== null
            && $this->assigned_staff_id !== null
            && $this->verifying_staff_id === $this->assigned_staff_id;
    }

    public function informationRequests(): HasMany
    {
        return $this->hasMany(ComplaintInformationRequest::class);
    }

    /**
     * The ask a verifier still has outstanding, if any.
     */
    public function outstandingInformationRequest(): ?ComplaintInformationRequest
    {
        return $this->informationRequests()->outstanding()->latest('id')->first();
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(ComplaintFeedback::class);
    }

    public function canReceiveFeedback(): bool
    {
        return in_array($this->statusEnum, [
            ComplaintStatus::Resolved,
            ComplaintStatus::Closed,
        ], true);
    }

    public function spamConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spam_confirmed_by');
    }

    public function isSpamConfirmed(): bool
    {
        return $this->spam_confirmed_at !== null;
    }

    public function verificationMethodEnum(): ?VerificationMethod
    {
        return $this->verification_method instanceof VerificationMethod
            ? $this->verification_method
            : ($this->verification_method !== null
                ? VerificationMethod::tryFrom($this->verification_method)
                : null);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicate_of_id');
    }

    /**
     * The sub-ticket this complaint follows — a follow-up filed against a
     * resolved or closed complaint. Null on the originating complaint.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * The follow-ups filed against this complaint, newest-first.
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest('id');
    }

    /**
     * The open follow-up for this complaint, if one is waiting.
     *
     * A finished complaint may accept one follow-up at a time; once that
     * follow-up resolves or closes the parent can accept another later.
     */
    public function openFollowUp(): ?Complaint
    {
        return $this->followUps()
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ])
            ->latest('id')
            ->first();
    }

    /**
     * The most recent log entry — useful for showing "last updated by X".
     */
    public function latestLog(): HasMany
    {
        return $this->hasMany(ComplaintLog::class)->latest()->limit(1);
    }

    // ─────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────

    /**
     * All evidence photo paths, with a fallback for complaints created before
     * the multi-photo field existed.
     *
     * @return array<int, string>
     */
    public function getEvidenceImagesAttribute(): array
    {
        $paths = $this->image_paths;

        if (is_array($paths) && $paths !== []) {
            return array_values(array_filter(
                $paths,
                fn (mixed $path): bool => is_string($path) && trim($path) !== '',
            ));
        }

        return filled($this->image_path) ? [$this->image_path] : [];
    }

    /**
     * Number of evidence photos attached to the complaint.
     */
    public function getEvidenceImageCountAttribute(): int
    {
        return count($this->evidence_images);
    }

    /**
     * Returns the ComplaintStatus enum instance for the current status.
     * Usage: $complaint->statusEnum->label()
     *        $complaint->statusEnum->badgeColor()
     */
    public function getStatusEnumAttribute(): ComplaintStatus
    {
        return ComplaintStatus::from($this->status);
    }

    public function getReviewStatusEnumAttribute(): ComplaintReviewStatus
    {
        return ComplaintReviewStatus::tryFrom((string) $this->review_status)
            ?? ComplaintReviewStatus::Pending;
    }

    public function getSuggestedPriorityEnumAttribute(): ComplaintPriority
    {
        return ComplaintPriority::tryFrom((string) $this->suggested_priority)
            ?? ComplaintPriority::Routine;
    }

    public function getConfirmedPriorityEnumAttribute(): ?ComplaintPriority
    {
        return $this->confirmed_priority
            ? ComplaintPriority::tryFrom((string) $this->confirmed_priority)
            : null;
    }

    public function isVerified(): bool
    {
        return $this->review_status === ComplaintReviewStatus::Verified->value;
    }

    // ── Office marking — misleading or spam ────────────────────────────

    /**
     * The marking the office applied — misleading or spam, or null when the
     * report was never marked.
     */
    public function markingEnum(): ?ComplaintMarking
    {
        $marking = $this->marking;

        // The attribute is cast to the enum for normal reads. This fallback
        // also covers raw values (seeder rows, legacy data) without ever
        // casting an enum object to a string.
        if ($marking instanceof ComplaintMarking) {
            return $marking;
        }

        if ($marking === null || $marking === '') {
            return null;
        }

        return ComplaintMarking::tryFrom((string) $marking);
    }

    public function isMarked(): bool
    {
        return $this->markingEnum() !== null;
    }

    public function isMarkingPendingReview(): bool
    {
        return $this->isMarked()
            && $this->marking_confirmed_at === null
            && $this->marking_dismissed_at === null;
    }

    public function isMarkingConfirmed(): bool
    {
        return $this->marking_confirmed_at !== null;
    }

    public function isMarkingDismissed(): bool
    {
        return $this->marking_dismissed_at !== null;
    }

    /**
     * The staff member who marked the report. Used on the ticket page and
     * in management notifications.
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function markingConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marking_confirmed_by');
    }

    public function markingDismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marking_dismissed_by');
    }

    /**
     * Resolve intake mode/policy for a category (extensible, config-driven).
     *
     * @return array{mode:string, photos_required:bool, photos_allowed:bool, location_required:bool, barangay_required:bool, address_text_required:bool, show_photos_step:bool, show_location_steps:bool}
     */
    public static function intakePolicyFor(?string $category): array
    {
        $config = config('complaint_intake_policy', []);
        $modes = $config['modes'] ?? [];
        $defaultMode = $config['default'] ?? 'physical_required';
        // The category's own setting is authoritative. The config map stays
        // as the fallback for a category that predates the taxonomy or an
        // intake mode that was removed from the table.
        $mode = DepartmentRouter::intakeModeFor($category)
            ?? ($config['categories'] ?? [])[$category] ?? $defaultMode;
        $policy = $modes[$mode] ?? $modes[$defaultMode] ?? [
            'photos_required' => true,
            'photos_allowed' => true,
            'location_required' => true,
            'barangay_required' => true,
            'address_text_required' => false,
            'show_photos_step' => true,
            'show_location_steps' => true,
        ];

        return ['mode' => $mode] + $policy;
    }

    public function intakePolicy(): array
    {
        $policy = self::intakePolicyFor($this->category);
        if (is_string($this->intake_mode) && $this->intake_mode !== '') {
            $config = config('complaint_intake_policy', []);
            $modes = $config['modes'] ?? [];
            if (isset($modes[$this->intake_mode])) {
                $policy = ['mode' => $this->intake_mode] + $modes[$this->intake_mode];
            }
        }

        return $policy;
    }

    public function intakeLocationApplicable(): bool
    {
        $policy = $this->intakePolicy();

        return (bool) ($policy['location_required'] || $policy['barangay_required'] || $policy['address_text_required'] || $policy['show_location_steps']);
    }

    /**
     * Whether this complaint may appear on the public transparency page.
     *
     * Three independent conditions, owned by three different decisions:
     *
     *  1. `is_public` — the resident's own consent. Nobody else may set it.
     *  2. `hidden_from_public` — staff or admin moderation, for content that
     *     is foul, fraudulent or sensitive. This withholds a complaint someone
     *     consented to publish; it never publishes one they declined.
     *  3. moderation flags — spam, scam and duplicates, caught by the detector
     *     before anything is broadcast.
     *
     * Department verification is deliberately NOT a condition. Verification
     * answers "is this report accurate", which is a different question from
     * "did this resident agree to be public", and the resident's answer must not
     * depend on an office choosing to sign off.
     *
     * A complaint in a privacy-gated category is the one exception, and it is
     * not the resident's consent that carries it: those complaints name
     * somebody, so they are filed private and reach the register only when the
     * office has settled them, the information has been verified, the Mayor's
     * office has redacted the public copy and the Mayor's office has approved
     * the publication. See ComplaintPrivacyService.
     */
    /**
     * Whether the report has been verified (or has already been settled).
     *
     * An ordinary consented report is not shown on the public register until
     * at least this much is known to be true — a resident's consent is an
     * instruction to publish, not a promise that the report is accurate.
     * Settling a report implies the office verified it first, so Resolved or
     * Closed passes too (covers legacy rows filed before verification existed).
     * `verified_at` survives a later needs-information round — an open or
     * answered ask must not un-publish a report that is already verified.
     */
    public function isVerifiedForPublic(): bool
    {
        return $this->isVerified()
            || $this->verified_at !== null
            || in_array($this->statusEnum, [ComplaintStatus::Resolved, ComplaintStatus::Closed], true);
    }

    public function isPubliclyVisible(): bool
    {
        if ($this->hidden_from_public || $this->isModerationFlagged()) {
            return false;
        }

        // Consent to publish is the resident's call; when it is published is
        // not. An unverified report stays off the public record until the
        // office has verified it (or settled it, which implies the same).
        if (! $this->isVerifiedForPublic()) {
            return false;
        }

        if ($this->isPrivacyGated()) {
            return $this->publicationStatus() === PublicationStatus::Approved;
        }

        return $this->is_public;
    }

    /**
     * Whether this complaint names somebody, and is therefore governed by the
     * privacy policy rather than by the resident's publication choice.
     */
    public function isPrivacyGated(): bool
    {
        return ComplaintPrivacyService::isGated($this->category);
    }

    /**
     * The publication state of a privacy-gated complaint.
     */
    public function publicationStatus(): ?PublicationStatus
    {
        if ($this->publication_status === null || $this->publication_status === '') {
            return null;
        }

        return PublicationStatus::tryFrom($this->publication_status);
    }

    /**
     * The named party — a person or a business — as it was written by the
     * resident. This never leaves the office: it is not rendered on the public
     * register and is not part of the public search text.
     */
    public function namedParty(): ?string
    {
        $value = $this->named_party;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function namedPartyPosition(): ?string
    {
        $value = $this->named_party_position;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    // ── On hold ─────────────────────────────────────────────────────────
    //
    // A complaint can be verified and still not be something the office can
    // finish today. The office says why, the resident is told, and the
    // deadline pauses — because a budget gap is not a failure to work.

    /**
     * Whether the office has paused the work on this complaint.
     */
    public function isOnHold(): bool
    {
        return $this->hold_reason !== null && $this->hold_reason !== '';
    }

    public function holdReasonEnum(): ?HoldReason
    {
        if (! $this->isOnHold()) {
            return null;
        }

        return HoldReason::tryFrom($this->hold_reason);
    }

    /**
     * How long the complaint has been held, in total.
     *
     * The wait that is still running counts too, so the number the admin
     * sees is the whole story and not just the finished parts of it.
     */
    public function heldMinutes(): int
    {
        $minutes = (int) ($this->hold_total_minutes ?? 0);

        if ($this->isOnHold() && $this->hold_started_at !== null) {
            $minutes += max(0, (int) $this->hold_started_at->diffInMinutes(now()));
        }

        return $minutes;
    }

    /**
     * The whole days the complaint has been held.
     */
    public function heldDays(): int
    {
        return intdiv($this->heldMinutes(), 1440);
    }

    /**
     * The public explanation of the hold — what the resident is told.
     */
    public function holdNote(): ?string
    {
        $value = $this->hold_note;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Only the resident who filed a complaint may change whether it is public.
     *
     * Staff and admins moderate content but never consent on someone's behalf.
     */
    public function visibilityIsOwnedBy(?User $user): bool
    {
        return $user !== null && $user->id === $this->user_id;
    }

    public function isRedacted(): bool
    {
        return $this->redacted_title !== null
            || $this->redacted_description !== null
            || $this->withheldImagePaths() !== [];
    }

    public function isHiddenFromPublic(): bool
    {
        return (bool) $this->hidden_from_public;
    }

    /** @return array<int, string> */
    public function withheldImagePaths(): array
    {
        return array_values(array_filter((array) ($this->withheld_image_paths ?? [])));
    }

    // ── The public view ────────────────────────────────────────────────
    // A redacted field falls back to the original when no replacement was
    // supplied, so redacting a title never blanks it entirely by accident.

    public function publicTitle(): string
    {
        return $this->redacted_title ?? $this->title;
    }

    public function publicDescription(): string
    {
        return $this->redacted_description ?? $this->description;
    }

    /** Evidence photos the public is allowed to see. @return array<int, string> */
    public function publicImagePaths(): array
    {
        $withheld = $this->withheldImagePaths();

        return array_values(array_filter(
            $this->allImagePaths(),
            fn (string $path): bool => ! in_array($path, $withheld, true),
        ));
    }

    /** @return array<int, string> */
    public function allImagePaths(): array
    {
        $paths = (array) ($this->image_paths ?? []);

        if ($this->image_path && ! in_array($this->image_path, $paths, true)) {
            $paths[] = $this->image_path;
        }

        return array_values(array_filter($paths));
    }

    public function isModerationFlagged(): bool
    {
        return in_array($this->spam_status, ['review', 'spam'], true) || $this->duplicate_of_id !== null;
    }

    public function moderationLabel(): string
    {
        if ($this->duplicate_of_id) {
            return 'Possible duplicate';
        }

        return match ($this->spam_status) {
            'spam' => 'Likely spam',
            'review' => 'Needs review',
            default => 'Clear',
        };
    }

    // ─────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            ComplaintStatus::Submitted->value,
            ComplaintStatus::UnderReview->value,
            ComplaintStatus::InProgress->value,
        ]);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', ComplaintStatus::Resolved->value);
    }

    /**
     * Complaints a member of the public is allowed to see.
     *
     * Must stay in step with isPubliclyVisible() — a mismatch here would let the
     * list page and a direct link disagree about what is public.
     */
    public function scopePublic($query)
    {
        return $query
            ->where('hidden_from_public', false)
            ->whereNotIn('spam_status', ['review', 'spam'])
            ->whereNull('duplicate_of_id')
            // A rejected complaint is not a municipal matter — the office has
            // said so — so it does not belong on the public register, which
            // exists to show what residents are reporting and what is being
            // done about it. The resident still sees it on their own ticket,
            // with the office's reason. Resolved and closed complaints stay:
            // they are the record of a problem that was fixed, which is the
            // whole value of the register.
            ->where('status', '!=', ComplaintStatus::Rejected->value)
            ->where(function ($query) {
                // Consent to publish is not a promise the report is true:
                // an ordinary report goes on the register only once it is
                // verified (or already settled, which implies the same).
                $query->where('review_status', ComplaintReviewStatus::Verified->value)
                    ->orWhereNotNull('verified_at')
                    ->orWhereIn('status', [ComplaintStatus::Resolved->value, ComplaintStatus::Closed->value]);
            })
            ->where(function ($query) {
                // A complaint the resident agreed to publish, or one the
                // Mayor's office approved for publication after the office
                // redacted it.
                $query->where('is_public', true)
                    ->orWhere('publication_status', PublicationStatus::Approved->value);
            });
    }

    public function scopePendingReview($query)
    {
        return $query->where('review_status', ComplaintReviewStatus::Pending->value);
    }

    public function scopeFlagged($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('spam_status', ['review', 'spam'])
                ->orWhereNotNull('duplicate_of_id')
                ->orWhere(function ($marking) {
                    $marking->whereNotNull('marking')
                        ->whereNull('marking_confirmed_at')
                        ->whereNull('marking_dismissed_at');
                });
        });
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * The work this staff member is allowed to see.
     *
     * A verifier — the Department Administrator, or staff with verification
     * rights — carries the office's whole queue. An ordinary staff member
     * works the cases the office handed them, so they see only those:
     * assigned to them now, or assigned to them at some point. A case they
     * worked and then passed on stays readable to them — their own history
     * is theirs — but the rest of the office's work is not.
     *
     * The assignment trail is the audit log: every hand-over writes
     * `complaint.assignment_updated` with the new assignee in its metadata.
     */
    public function scopeVisibleToStaff($query, User $user)
    {
        if ($user->canVerifyComplaints()) {
            return $query;
        }

        $complaintMorph = (new self)->getMorphClass();

        return $query->where(function ($inner) use ($user, $complaintMorph) {
            $inner->where('complaints.assigned_staff_id', $user->id)
                ->orWhereIn('complaints.id', function ($assigned) use ($user, $complaintMorph) {
                    $assigned->select('subject_id')
                        ->from('activity_logs')
                        ->where('action', 'complaint.assignment_updated')
                        ->where('subject_type', $complaintMorph)
                        ->whereJsonContains('metadata->assigned_staff_id', $user->id);
                });
        });
    }

    /**
     * May this staff member open this complaint at all?
     *
     * The single check the staff workspace uses, so the queue, the ticket and
     * the live-ticket poll all agree on who may read what.
     */
    public static function isVisibleToStaff(self $complaint, User $user): bool
    {
        if (! $user->isDepartmentStaff()) {
            return false;
        }

        if ($user->canVerifyComplaints()) {
            return (int) $complaint->department_id === (int) $user->department_id;
        }

        return self::query()
            ->visibleToStaff($user)
            ->whereKey($complaint->getKey())
            ->exists();
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    /**
     * Log a status change.
     * ALWAYS call this when updating status — never update status without logging.
     *
     * Usage:
     *   $complaint->logStatus('In Progress', auth()->id(), 'Contractor dispatched');
     */
    public function logStatus(
        string $newStatus,
        ?int $actorId = null,
        ?string $comment = null,
    ): void {
        $previous = $this->status;

        $this->update(['status' => $newStatus]);

        $this->logs()->create([
            'actor_id' => $actorId ?? auth()->id(),
            'previous_status' => $previous,
            'new_status' => $newStatus,
            'comment' => $comment,
        ]);
    }
}
