<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'category',
        'title',
        'description',
        'image_path',
        'latitude',
        'longitude',
        'address_text',
        'urgency',
        'status',
        'is_public',
    ];

    // ticket_id is generated in booted() — never mass-assigned
    protected $guarded = ['ticket_id'];

    protected $casts = [
        'status' => ComplaintStatus::class,
    ];

    /**
     * Use booted() — NOT boot(). This is the correct Laravel 9+ pattern.
     * NEVER use Str::random() for ticket_id — causes duplicates under concurrent load.
     */
    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint) {
            $year = date('Y');
            // withTrashed() ensures soft-deleted complaints are counted — no gaps in numbering
            $next = static::withTrashed()
                          ->whereYear('created_at', $year)
                          ->count() + 1;

            $complaint->ticket_id = 'COMP-' . $year . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
        });
    }

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Latest logs first — used for status timeline display.
     * NEVER update or delete ComplaintLog rows. They are immutable audit records.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ComplaintLog::class)->latest('created_at');
    }

    /**
     * Staff member assigned to handle this complaint (if any)
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /**
     * Returns the ComplaintStatus enum instance for this complaint.
     * Always use this — never compare raw status strings in PHP logic.
     */
    public function statusEnum(): ComplaintStatus
    {
        return ComplaintStatus::from($this->status instanceof ComplaintStatus
            ? $this->status->value
            : $this->status);
    }

    // ── Accessors ────────────────────────────────────────────────────

    /**
     * Accessor for formatted latitude (6 decimal places).
     */
    public function getLatitudeAttribute(?float $value): ?float
    {
        return $value !== null ? round($value, 6) : null;
    }

    /**
     * Accessor for formatted longitude (6 decimal places).
     */
    public function getLongitudeAttribute(?float $value): ?float
    {
        return $value !== null ? round($value, 6) : null;
    }
}