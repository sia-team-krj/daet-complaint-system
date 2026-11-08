<?php

namespace App\Models;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "ticket_id",
        "user_id",
        "department_id",
        "category",
        "title",
        "description",
        "image_path",
        "image_paths",
        "latitude",
        "longitude",
        "address_text",
        "barangay",
        "urgency",
        "status",
        "is_public",
        "hidden_from_public",
        "redacted_title",
        "redacted_description",
        "withheld_image_paths",
        "moderation_reason",
        "moderated_by",
        "moderated_at",
        "assigned_staff_id",
        "verifying_staff_id",
        "verification_method",
        "content_fingerprint",
        "spam_status",
        "spam_score",
        "spam_reasons",
        "similarity_score",
        "duplicate_of_id",
        "suggested_priority",
        "confirmed_priority",
        "review_status",
        "suggestion_reasons",
        "reviewed_by",
        "reviewed_at",
        "review_notes",
    ];

    protected function casts(): array
    {
        return [
            "is_public" => "boolean",
            "hidden_from_public" => "boolean",
            "withheld_image_paths" => "array",
            "moderated_at" => "datetime",
            "image_paths" => "array",
            "spam_status" => "string",
            "spam_score" => "integer",
            "spam_reasons" => "array",
            "similarity_score" => "float",
            "suggestion_reasons" => "array",
            "reviewed_at" => "datetime",
            "suggested_priority" => "string",
            "confirmed_priority" => "string",
            "review_status" => "string",
            "verification_method" => VerificationMethod::class,
            "latitude" => "decimal:8",
            "longitude" => "decimal:8",
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
     */
    public function isPubliclyVisible(): bool
    {
        return $this->is_public
            && ! $this->hidden_from_public
            && ! $this->isModerationFlagged();
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
        return $query->whereIn("status", [
            ComplaintStatus::Submitted->value,
            ComplaintStatus::UnderReview->value,
            ComplaintStatus::InProgress->value,
        ]);
    }

    public function scopeResolved($query)
    {
        return $query->where("status", ComplaintStatus::Resolved->value);
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
            ->where('is_public', true)
            ->where('hidden_from_public', false)
            ->whereNotIn('spam_status', ['review', 'spam'])
            ->whereNull('duplicate_of_id');
    }

    public function scopePendingReview($query)
    {
        return $query->where('review_status', ComplaintReviewStatus::Pending->value);
    }

    public function scopeFlagged($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('spam_status', ['review', 'spam'])
                ->orWhereNotNull('duplicate_of_id');
        });
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where("department_id", $departmentId);
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

        $this->update(["status" => $newStatus]);

        $this->logs()->create([
            "actor_id" => $actorId ?? auth()->id(),
            "previous_status" => $previous,
            "new_status" => $newStatus,
            "comment" => $comment,
        ]);
    }
}
