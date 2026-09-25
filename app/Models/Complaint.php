<?php

namespace App\Models;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
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
        "urgency",
        "status",
        "is_public",
        "assigned_staff_id",
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
     * Ticket ID format: COMP-2026-00001
     *
     * WHY sequential and not Str::random()?
     * Str::random(6) under concurrent submissions can produce duplicates
     * (birthday problem). Sequential IDs are collision-free.
     *
     * withTrashed() includes soft-deleted records in the count so
     * ticket IDs are never reused after a soft delete.
     */
    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint) {
            $year = date("Y");
            $count = static::withTrashed()
                ->whereYear("created_at", $year)
                ->count();

            $complaint->ticket_id =
                "COMP-" .
                $year .
                "-" .
                str_pad($count + 1, 5, "0", STR_PAD_LEFT);
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

    public function isPubliclyVisible(): bool
    {
        return $this->is_public && $this->isVerified();
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

    public function scopePublic($query)
    {
        return $query
            ->where('is_public', true)
            ->where('review_status', ComplaintReviewStatus::Verified->value);
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
