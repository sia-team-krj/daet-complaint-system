<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerification extends Model
{
    use HasFactory;

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const REVOKED = 'revoked';
    const EXPIRED = 'expired';
    const CANCELLED = 'cancelled';

    const ID_TYPES = [
        'PhilID',
        'PHTIN',
        'PRC Card (Professional/Special)',
        'Authentication',
        'National ID',
        'Other Government ID',
        'Barangay Certificate',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'id_type',
        'id_number_hash',
        'id_front_path',
        'selfie_path',
        'files_purged_at',
        'possible_duplicate_user_id',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'reviewer_note',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'files_purged_at' => 'datetime',
            'revoked_at' => 'datetime',
            'possible_duplicate_user_id' => 'integer',
        ];
    }

    protected $attributes = [
        'status' => self::PENDING,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function possibleDuplicate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'possible_duplicate_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECTED;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::REVOKED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }
}
