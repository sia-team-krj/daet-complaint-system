<?php

namespace App\Models;

use App\Enums\InformationRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A verifier's ask to a resident for better evidence, and the resident's reply.
 *
 * One open request per complaint at a time is the rule the controller enforces;
 * `round` counts how many times the conversation has gone back to the resident
 * so a single complaint cannot loop a verifier indefinitely.
 */
class ComplaintInformationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'requested_by',
        'question',
        'status',
        'round',
        'response_text',
        'response_image_paths',
        'responded_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InformationRequestStatus::class,
            'round' => 'integer',
            'response_image_paths' => 'array',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Used to raise a stale-request alert exactly once, so a daily sweep cannot
     * train verifiers to ignore the badge.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'subject');
    }

    // ── Scopes ────────────────────────────────────────
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InformationRequestStatus::Open->value,
            InformationRequestStatus::Answered->value,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', InformationRequestStatus::Open->value);
    }

    public function scopeAnswered(Builder $query): Builder
    {
        return $query->where('status', InformationRequestStatus::Answered->value);
    }

    // ── Helpers ───────────────────────────────────────
    public function isOpen(): bool
    {
        return $this->status === InformationRequestStatus::Open;
    }

    public function isAnswered(): bool
    {
        return $this->status === InformationRequestStatus::Answered;
    }

    public function isResolved(): bool
    {
        return $this->status === InformationRequestStatus::Resolved;
    }

    /**
     * A request nobody has closed is the failure mode that quietly rots an LGU
     * workflow, so age is a first-class thing to be able to see and sort by.
     */
    public function isStale(int $days = 3): bool
    {
        if ($this->isResolved()) {
            return false;
        }

        return $this->created_at->diffInDays(now()) >= $days;
    }
}
