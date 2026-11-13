<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The resident is asked whether the work was actually done.
 *
 * The office marks the work resolved and the case waits for the resident:
 * they confirm it, or they say it is not fixed. Without a reminder the only
 * people who know the case is waiting are the office — and seven days later
 * the system closes it for them, which reads as the office deciding the
 * resident was satisfied.
 */
class ResolutionReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_the_resident_is_reminded_to_confirm_a_resolution(): void
    {
        $complaint = $this->resolvedComplaint(daysAgo: 4);

        $this->artisan('complaints:remind-resolution')->assertSuccessful();

        $this->assertSame(
            1,
            Notification::where('type', Notification::COMPLAINT_RESOLUTION_REMINDER)
                ->where('subject_id', $complaint->id)
                ->count(),
            'The resident was not reminded that the report is waiting on them.',
        );
    }

    public function test_a_report_resolved_recently_is_not_reminded_yet(): void
    {
        $this->resolvedComplaint(daysAgo: 1);

        $this->artisan('complaints:remind-resolution')->assertSuccessful();

        $this->assertSame(
            0,
            Notification::where('type', Notification::COMPLAINT_RESOLUTION_REMINDER)->count(),
        );
    }

    public function test_the_resident_is_reminded_only_once(): void
    {
        $this->resolvedComplaint(daysAgo: 4);

        $this->artisan('complaints:remind-resolution')->assertSuccessful();
        $this->artisan('complaints:remind-resolution')->assertSuccessful();

        $this->assertSame(
            1,
            Notification::where('type', Notification::COMPLAINT_RESOLUTION_REMINDER)->count(),
            'The resident must not be nagged: one reminder per report.',
        );
    }

    public function test_a_closed_report_is_not_reminded(): void
    {
        $complaint = $this->resolvedComplaint(daysAgo: 4);
        $complaint->update([
            'status' => ComplaintStatus::Closed->value,
            'closed_at' => now(),
            'closed_by' => 'system',
        ]);

        $this->artisan('complaints:remind-resolution')->assertSuccessful();

        $this->assertSame(
            0,
            Notification::where('type', Notification::COMPLAINT_RESOLUTION_REMINDER)->count(),
            'A report that is already closed has nothing to confirm.',
        );
    }

    private function resolvedComplaint(int $daysAgo): Complaint
    {
        $complaint = Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => ComplaintStatus::Resolved->value,
            'review_status' => 'verified',
            'verified_at' => now()->subDays($daysAgo + 2),
            'resolved_at' => now()->subDays($daysAgo),
            'is_public' => true,
            'content_fingerprint' => Str::random(40),
        ]);

        return $complaint->fresh();
    }
}
