<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        "code",
        "department_id",
        "created_by",
        "expires_at",
        "max_uses",
        "used_count",
        "redeemed_by",
    ];

    protected function casts(): array
    {
        return [
            "expires_at"  => "datetime",
            "created_at"  => "datetime",
            "redeemed_at" => "datetime",
            "max_uses"    => "integer",
            "used_count"  => "integer",
        ];
    }

    // ── Relationships ─────────────────────────────────
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    // ── Helpers ───────────────────────────────────────
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_count >= $this->max_uses;
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->isUsed() && $this->is_active;
    }
}
