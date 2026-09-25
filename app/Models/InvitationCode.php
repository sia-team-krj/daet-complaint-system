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
        "redeemed_at",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "expires_at"  => "datetime",
            "created_at"  => "datetime",
            "redeemed_at" => "datetime",
            "max_uses"    => "integer",
            "used_count"  => "integer",
            "is_active"   => "boolean",
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
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return (int) $this->used_count >= (int) $this->max_uses;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active && ! $this->isExpired() && ! $this->isUsed();
    }

    public function statusLabel(): string
    {
        if ($this->isUsed()) {
            return 'Redeemed';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->is_active ? 'Active' : 'Revoked';
    }
}
