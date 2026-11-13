<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A resident's feedback.
 *
 * A rating counts from the moment it is given; a suggestion is read by the
 * administration before anybody else sees it. Both live here because both are
 * a resident's words about the portal, and the difference between them is
 * their status, not their shape.
 */
class Feedback extends Model
{
    use HasFactory;

    /** The portal itself, or one office. */
    public const KIND_RATING = 'rating';

    /** A suggestion or an improvement the resident is proposing. */
    public const KIND_SUGGESTION = 'suggestion';

    protected $fillable = [
        'user_id',
        'kind',
        'department_id',
        'rating',
        'body',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The office rated, when the rating is of an office. */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isRating(): bool
    {
        return $this->kind === self::KIND_RATING;
    }

    public function isSuggestion(): bool
    {
        return $this->kind === self::KIND_SUGGESTION;
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

    /** Published feedback: on the register, and counted in the averages. */
    public function scopePublished($query)
    {
        return $query->where('status', FeedbackStatus::Published->value);
    }

    public function scopeRatings($query)
    {
        return $query->where('kind', self::KIND_RATING);
    }

    public function scopeSuggestions($query)
    {
        return $query->where('kind', self::KIND_SUGGESTION);
    }

    /** Suggestions waiting for the administration to read them. */
    public function scopePending($query)
    {
        return $query->where('status', FeedbackStatus::Pending->value);
    }
}
