<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintFeedback extends Model
{
    protected $table = 'complaint_feedbacks';

    protected $fillable = [
        'complaint_id',
        'user_id',
        'rating',
        'comment',
        'status',
        'published_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusEnum(): ?FeedbackStatus
    {
        return FeedbackStatus::tryFrom((string) $this->status);
    }

    public function isPublished(): bool
    {
        return $this->statusEnum() === FeedbackStatus::Published;
    }

    public function isPending(): bool
    {
        return $this->statusEnum() === FeedbackStatus::Pending;
    }

    public function isWithheld(): bool
    {
        return $this->statusEnum() === FeedbackStatus::Withheld;
    }

    public function hasComment(): bool
    {
        return trim((string) $this->comment) !== '';
    }

    public function scopePublished($query)
    {
        return $query->where('status', FeedbackStatus::Published->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', FeedbackStatus::Pending->value);
    }
}
