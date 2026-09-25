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
        'documentation_paths',
        'is_internal',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'documentation_paths' => 'array',
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
     * Documentation photos attached to this immutable status update.
     *
     * @return array<int, string>
     */
    public function getDocumentationImagesAttribute(): array
    {
        return is_array($this->documentation_paths)
            ? array_values(array_filter(
                $this->documentation_paths,
                fn (mixed $path): bool => is_string($path) && trim($path) !== '',
            ))
            : [];
    }

    /**
     * Number of documentation photos attached to the update.
     */
    public function getDocumentationImageCountAttribute(): int
    {
        return count($this->documentation_images);
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
