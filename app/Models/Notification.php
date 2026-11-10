<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One cross-complaint "look at this" for a single recipient.
 *
 * Not a copy of complaint_logs: those record what happened to one complaint and
 * are permanent. These are transient, per-person, and unreadable.
 */
class Notification extends Model
{
    use HasFactory;

    /** Verification is waiting on whoever holds the capability. */
    public const COMPLAINT_NEEDS_VERIFICATION = 'complaint.needs_verification';

    /** A verifier handed this complaint to you specifically. */
    public const COMPLAINT_VERIFICATION_DELEGATED = 'complaint.verification_delegated';

    /** A resident answered a request for more evidence. */
    public const COMPLAINT_INFORMATION_PROVIDED = 'complaint.information_provided';

    /** A verifier asked the resident for better evidence. */
    public const COMPLAINT_INFORMATION_REQUESTED = 'complaint.information_requested';

    /** A resident has not answered for long enough to be a problem. */
    public const COMPLAINT_INFORMATION_STALE = 'complaint.information_stale';

    /** The resident's own complaint changed state. */
    public const COMPLAINT_STATUS_CHANGED = 'complaint.status_changed';

    /** A verifier or administrator settled the review decision. */
    public const COMPLAINT_REVIEW_UPDATED = 'complaint.review_updated';

    /** A complaint was handed to a staff member to work. */
    public const COMPLAINT_ASSIGNED = 'complaint.assigned';

    /** The office verified a report and has not started the work in time. */
    public const COMPLAINT_WORK_OVERDUE = 'complaint.work_overdue';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'subject_type',
        'subject_id',
        'context',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ────────────────────────────────────────
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    // ── Helpers ───────────────────────────────────────
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /**
     * Splits the headline into the part worth emphasising and the rest.
     *
     * The ticket id is the entity a reader scans for, so it is bolded and the
     * sentence that follows it stays plain — the way a social feed sets the
     * subject of a sentence in bold and leaves the verb phrase alone.
     *
     * @return array{0: string, 1: string} [emphasised entity, remainder]
     */
    public function headlineParts(): array
    {
        $title = (string) $this->title;
        $ticket = $this->context['ticket_id'] ?? null;

        if (is_string($ticket) && $ticket !== '' && str_starts_with($title, $ticket)) {
            return [$ticket, ltrim(substr($title, strlen($ticket)))];
        }

        return ['', $title];
    }

    /**
     * Icon key for this event. The glyph itself is authored SVG in a view
     * partial, never a unicode character standing in for an icon set.
     */
    public function iconKey(): string
    {
        return match ($this->type) {
            self::COMPLAINT_NEEDS_VERIFICATION => 'verify',
            self::COMPLAINT_VERIFICATION_DELEGATED => 'handover',
            self::COMPLAINT_INFORMATION_REQUESTED => 'question',
            self::COMPLAINT_INFORMATION_PROVIDED => 'reply',
            self::COMPLAINT_INFORMATION_STALE => 'waiting',
            self::COMPLAINT_STATUS_CHANGED => 'status',
            self::COMPLAINT_REVIEW_UPDATED => 'review',
            self::COMPLAINT_ASSIGNED => 'handover',
            default => 'bell',
        };
    }

    /**
     * Where opening this notification should take the reader.
     *
     * Staff and admin land in the workspace complaint view; a resident lands on
     * their own complaint. Resolved from the recipient's role rather than from
     * the event, because the same event means different places to different people.
     */
    public function destination(): ?string
    {
        $complaint = $this->subject;

        if (! $complaint instanceof Complaint || $complaint->id === null) {
            return null;
        }

        $recipient = $this->user;

        if ($recipient?->role === 'citizen') {
            return route('complaints.show', $complaint);
        }

        if ($recipient?->role === 'admin') {
            return route('admin.complaints.show', $complaint);
        }

        return route('staff.complaints.show', $complaint);
    }

    /**
     * A short human label for the event, used by the feed and the full page.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            self::COMPLAINT_NEEDS_VERIFICATION => 'Needs verification',
            self::COMPLAINT_VERIFICATION_DELEGATED => 'Verification handed to you',
            self::COMPLAINT_INFORMATION_REQUESTED => 'More information needed',
            self::COMPLAINT_INFORMATION_PROVIDED => 'Resident replied',
            self::COMPLAINT_INFORMATION_STALE => 'Waiting on resident',
            self::COMPLAINT_STATUS_CHANGED => 'Status changed',
            self::COMPLAINT_REVIEW_UPDATED => 'Review updated',
            self::COMPLAINT_ASSIGNED => 'Assigned to you',
            default => 'Update',
        };
    }

    /**
     * Complaint category label for the list pill. Falls back to the
     * event label when the notification has no complaint subject (or the
     * subject was deleted), so the pill is never empty.
     */
    public function categoryLabel(): string
    {
        $complaint = $this->subject instanceof Complaint ? $this->subject : null;

        if ($complaint && is_string($complaint->category) && $complaint->category !== '') {
            $labels = \App\Services\DepartmentRouter::getCategoryOptions();
            $flat = collect($labels)->flatten(1)->pluck('label', 'value');

            return $flat->get($complaint->category, $this->typeLabel());
        }

        return $this->typeLabel();
    }

    /**
     * Group key for the list: today / earlier-this-week / older.
     * Earlier this week means the past 7 days excluding today.
     */
    public function groupKey(): string
    {
        $created = $this->created_at;

        if ($created?->isToday()) {
            return 'today';
        }

        return $created && $created->greaterThanOrEqualTo(now()->subDays(7))
            ? 'earlier-this-week'
            : 'older';
    }

    public function groupLabel(): string
    {
        return match ($this->groupKey()) {
            'today' => 'Today',
            'earlier-this-week' => 'Earlier this week',
            default => 'Older',
        };
    }

    /**
     * The bell badge caps at 9+ so a busy queue cannot stretch the navbar.
     */
    public static function unreadBadgeFor(int $userId): string
    {
        $count = static::query()
            ->where('user_id', $userId)
            ->unread()
            ->count();

        if ($count === 0) {
            return '';
        }

        return $count > 9 ? '9+' : (string) $count;
    }
}
