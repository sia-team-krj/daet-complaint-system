<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintLog extends Model
{
    // Only created_at — this model has NO updated_at column.
    // NEVER call update() or delete() on ComplaintLog. Logs are immutable.
    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'actor_id',
        'previous_status',
        'new_status',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'is_internal' => 'boolean',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Public notes visible to citizens.
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Internal notes visible only to staff.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }
}