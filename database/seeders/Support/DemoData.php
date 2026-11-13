<?php

namespace Database\Seeders\Support;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared machinery for the presentation seeders.
 *
 * The point of this file is that every complaint seeded for the demo moves
 * through the same doors a real complaint moves through: a filing with a
 * routed department, a log line per status change, an audit-trail entry per
 * meaningful action. Backdating is done by hand because `created_at` is not
 * fillable and the demo data must look like it accumulated over three months,
 * not like a burst at the moment of seeding.
 */
class DemoData
{
    /**
     * Create one demo complaint with a deliberately backdated filing time.
     *
     * @param array{
     *     user: User, department_code: string, category: string, title: string,
     *     description: string, filed_at: Carbon, barangay?: string,
     *     latitude?: float, longitude?: float, urgency?: string, status: string,
     *     is_public?: bool, suggested_priority?: string, confirmed_priority?: string|null,
     *     review_status?: string, reviewed_by?: int|null, reviewed_at?: Carbon|null,
     *     verified_at?: Carbon|null, verifying_staff_id?: int|null, verification_method?: string|null,
     *     assigned_staff_id?: int|null, resolved_at?: Carbon|null, closed_at?: Carbon|null,
     *     closed_by?: string|null, review_notes?: string|null, hidden_from_public?: bool,
     *     named_party?: string|null, named_party_position?: string|null,
     *     publication_status?: string|null, publication_requested_at?: Carbon|null,
     *     publication_requested_by?: int|null, publication_decided_at?: Carbon|null,
     *     publication_decided_by?: int|null, publication_note?: string|null,
     *     redacted_title?: string|null, redacted_description?: string|null,
     *     resolved_at?: Carbon|null, hold_reason?: string|null, hold_note?: string|null,
     *     hold_internal_note?: string|null, hold_expected_resume_at?: string|null,
     *     hold_started_at?: Carbon|null, hold_started_by?: int|null, hold_total_minutes?: int,
     *     spam_status?: string, spam_score?: int, spam_reasons?: array|null,
     *     similarity_score?: float, duplicate_of_id?: int|null,
     * } $data
     */
    public static function complaint(array $data): Complaint
    {
        $department = Department::where('code', $data['department_code'])->firstOrFail();

        $barangay = $data['barangay'] ?? null;
        $filedAt = $data['filed_at'];

        $complaint = Complaint::create([
            'user_id' => $data['user']->id,
            'department_id' => $department->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'barangay' => $barangay,
            'address_text' => $barangay !== null ? $barangay.', Daet, Camarines Norte' : 'Daet, Camarines Norte',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'intake_mode' => config('complaint_intake_policy.categories.'.$data['category'], 'physical_required'),
            'work_mode' => config('complaint_work_policy.categories.'.$data['category'], 'report_only'),
            'urgency' => $data['urgency'] ?? 'Medium',
            'status' => $data['status'],
            'is_public' => $data['is_public'] ?? false,
            'hidden_from_public' => $data['hidden_from_public'] ?? false,
            'suggested_priority' => $data['suggested_priority'] ?? 'routine',
            'confirmed_priority' => $data['confirmed_priority'] ?? ($data['suggested_priority'] ?? 'routine'),
            'review_status' => $data['review_status'] ?? 'pending',
            'reviewed_by' => $data['reviewed_by'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? null,
            'review_notes' => $data['review_notes'] ?? null,
            'verified_at' => $data['verified_at'] ?? null,
            'verifying_staff_id' => $data['verifying_staff_id'] ?? null,
            'verification_method' => $data['verification_method'] ?? null,
            'assigned_staff_id' => $data['assigned_staff_id'] ?? null,
            'resolved_at' => $data['resolved_at'] ?? null,
            'closed_at' => $data['closed_at'] ?? null,
            'closed_by' => $data['closed_by'] ?? null,
            'named_party' => $data['named_party'] ?? null,
            'named_party_position' => $data['named_party_position'] ?? null,
            'publication_status' => $data['publication_status'] ?? null,
            'publication_requested_at' => $data['publication_requested_at'] ?? null,
            'publication_requested_by' => $data['publication_requested_by'] ?? null,
            'publication_decided_at' => $data['publication_decided_at'] ?? null,
            'publication_decided_by' => $data['publication_decided_by'] ?? null,
            'publication_note' => $data['publication_note'] ?? null,
            'redacted_title' => $data['redacted_title'] ?? null,
            'redacted_description' => $data['redacted_description'] ?? null,
            'spam_status' => $data['spam_status'] ?? 'clear',
            'spam_score' => $data['spam_score'] ?? 0,
            'spam_reasons' => $data['spam_reasons'] ?? null,
            'similarity_score' => $data['similarity_score'] ?? 0,
            'duplicate_of_id' => $data['duplicate_of_id'] ?? null,
            'hold_reason' => $data['hold_reason'] ?? null,
            'hold_note' => $data['hold_note'] ?? null,
            'hold_internal_note' => $data['hold_internal_note'] ?? null,
            'hold_expected_resume_at' => $data['hold_expected_resume_at'] ?? null,
            'hold_started_at' => $data['hold_started_at'] ?? null,
            'hold_started_by' => $data['hold_started_by'] ?? null,
            'hold_total_minutes' => $data['hold_total_minutes'] ?? 0,
        ]);

        $complaint->forceFill(['created_at' => $filedAt])->save();

        return $complaint->refresh();
    }

    /**
     * Write one immutable timeline entry, backdated.
     */
    public static function log(
        Complaint $complaint,
        ?User $actor,
        ?string $previous,
        string $next,
        string $comment,
        Carbon $at,
        bool $internal = false
    ): ComplaintLog {
        $log = ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $actor?->id,
            'previous_status' => $previous,
            'new_status' => $next,
            'comment' => $comment,
            'is_internal' => $internal,
        ]);

        $log->forceFill(['created_at' => $at])->save();

        return $log;
    }

    /**
     * Write one audit-trail entry, backdated.
     */
    public static function activity(
        ?User $actor,
        ?Department $department,
        string $action,
        ?Model $subject,
        string $description,
        array $metadata = [],
        ?Carbon $at = null
    ): ActivityLog {
        return ActivityLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->full_name ?? 'System',
            'actor_role' => $actor?->role ?? 'system',
            'department_id' => $department?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => $at ?? now(),
        ]);
    }

    /**
     * The barangay's reference point with small, realistic jitter — never an
     * exact home coordinate, matching the public register's own posture.
     *
     * @return array{0: float, 1: float}
     */
    public static function barangayPoint(string $barangay): array
    {
        $points = config('daet-barangays.points', []);
        $base = $points[$barangay] ?? [14.1122, 122.9553];

        return [
            round($base[0] + (mt_rand(-25, 25) / 100000), 5),
            round($base[1] + (mt_rand(-25, 25) / 100000), 5),
        ];
    }
}
