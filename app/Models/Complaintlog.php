<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintLog extends Model
{
    /**
     * IMMUTABILITY RULE:
     * This model has NO updated_at column and must NEVER be updated.
     * Log entries are permanent records of what happened and when.
     * Never call update() or save() (after creation) on this model.
     */

    // Tell Laravel this model only has created_at, not updated_at
    public $timestamps = false;
    const CREATED_AT = "created_at";

    protected $fillable = [
        "complaint_id",
        "actor_id",
        "previous_status",
        "new_status",
        "comment",
    ];

    protected function casts(): array
    {
        return [
            "created_at" => "datetime",
        ];
    }

    // ─────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────

    /**
     * The complaint this log entry belongs to.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * The staff member or admin who made the change.
     * May be NULL if their account was soft-deleted.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, "actor_id");
    }

    // ─────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────

    /**
     * Human-readable description of this log entry.
     * e.g., "Status changed from Submitted to In Progress"
     */
    public function getSummaryAttribute(): string
    {
        if ($this->previous_status) {
            return "Status changed from {$this->previous_status} to {$this->new_status}";
        }

        return "Complaint submitted with status {$this->new_status}";
    }

    /**
     * Display name of who made the change.
     * Returns "System" if actor is unknown.
     */
    public function getActorNameAttribute(): string
    {
        return $this->actor?->full_name ?? "System";
    }
}
