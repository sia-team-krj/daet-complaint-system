<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class ActivityLog extends Model
{
    /**
     * Activity records are append-only. There is deliberately no updated_at.
     */
    public $timestamps = false;

    public $incrementing = true;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'department_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Activity log records are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Activity log records are immutable.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForDepartment($query, ?int $departmentId)
    {
        return $departmentId
            ? $query->where('department_id', $departmentId)
            : $query;
    }
}
