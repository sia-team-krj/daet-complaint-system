<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'sender_type',
        'body',
        'image_paths',
    ];

    protected function casts(): array
    {
        return [
            'image_paths' => 'array',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<int, string> */
    public function getImagesAttribute(): array
    {
        $paths = $this->image_paths;
        if (! is_array($paths)) {
            return [];
        }

        return array_values(array_filter($paths, fn ($p) => is_string($p) && trim($p) !== ''));
    }
}
