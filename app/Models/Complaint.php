<?php

namespace App\Models;

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
        "latitude",
        "longitude",
        "address_text",
        "urgency",
        "status",
        "is_public",
    ];

    protected function casts(): array
    {
        return [
            "is_public" => "boolean",
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
     * Returns the ComplaintStatus enum instance for the current status.
     * Usage: $complaint->statusEnum->label()
     *        $complaint->statusEnum->badgeColor()
     */
    public function getStatusEnumAttribute(): ComplaintStatus
    {
        return ComplaintStatus::from($this->status);
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
        return $query->where("is_public", true);
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
