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
 * The queue is for the work that is still live.
 *
 * A complaint that has been rejected, resolved or closed is finished, and
 * burying it in the same list is how a queue becomes a graveyard — the live
 * work disappears under the settled cases. The finished ones stay on the
 * record, one click away.
 *
 * The order is staleness: the complaint nothing has touched for the longest
 * comes first, so a report filed hours ago and still unreviewed climbs the
 * queue as the hours pass.
 */
class StaffQueueScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
            'can_verify' => true,
        ]);
    }

    public function test_the_queue_opens_on_the_work_that_is_still_live(): void
    {
        $live = $this->complaint(ComplaintStatus::Submitted->value);
        $this->complaint(ComplaintStatus::InProgress->value);
        $finished = $this->complaint(ComplaintStatus::Rejected->value);

        $response = $this->actingAs($this->staff)->get(route('staff.complaints.index'));

        $response->assertOk();
        $response->assertSee('Open work');
        $response->assertSee($live->ticket_id);
        $response->assertDontSee($finished->ticket_id);
    }

    public function test_a_finished_complaint_is_not_in_the_open_queue(): void
    {
        $finished = $this->complaint(ComplaintStatus::Rejected->value);

        $response = $this->actingAs($this->staff)->get(route('staff.complaints.index'));

        $response->assertOk();
        $response->assertDontSee($finished->ticket_id);
    }

    public function test_the_finished_complaints_are_one_click_away(): void
    {
        $rejected = $this->complaint(ComplaintStatus::Rejected->value);
        $resolved = $this->complaint(ComplaintStatus::Resolved->value);
        $closed = $this->complaint(ComplaintStatus::Closed->value);

        $response = $this->actingAs($this->staff)->get(route('staff.complaints.index', ['scope' => 'finished']));

        $response->assertOk();
        $response->assertSee($rejected->ticket_id);
        $response->assertSee($resolved->ticket_id);
        $response->assertSee($closed->ticket_id);
    }

    public function test_the_status_filter_offers_the_statuses_in_the_current_scope(): void
    {
        $response = $this->actingAs($this->staff)->get(route('staff.complaints.index'));

        $response->assertOk();
        $response->assertSee('value="Submitted"', false);
        $response->assertDontSee('value="Rejected"', false);

        $response = $this->actingAs($this->staff)->get(route('staff.complaints.index', ['scope' => 'finished']));

        $response->assertOk();
        $response->assertSee('value="Rejected"', false);
        $response->assertSee('value="Closed"', false);
        $response->assertDontSee('value="In Progress"', false);
    }

    public function test_the_queue_is_ordered_by_how_long_the_complaint_has_sat(): void
    {
        // Three complaints, each touched at a different time. The one nothing
        // has touched for the longest must come first.
        $oldest = $this->complaint(ComplaintStatus::Submitted->value, touchedAt: now()->subHours(9));
        $middle = $this->complaint(ComplaintStatus::Submitted->value, touchedAt: now()->subHours(4));
        $newest = $this->complaint(ComplaintStatus::Submitted->value, touchedAt: now()->subHour());

        $response = $this->actingAs($this->staff)->get(route('staff.complaints.index'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertLessThan(
            strpos($html, $middle->ticket_id),
            strpos($html, $oldest->ticket_id),
            'The complaint that has gone longest without an update must come first.',
        );

        $this->assertLessThan(
            strpos($html, $newest->ticket_id),
            strpos($html, $middle->ticket_id),
            'The order is by how long each complaint has sat, not by when it was filed.',
        );
    }

    public function test_a_complaint_the_office_has_touched_sinks_down_the_queue(): void
    {
        // Filed an hour ago but already reviewed, against one filed five hours
        // ago that nobody has looked at. The untouched one is more urgent.
        $touched = $this->complaint(ComplaintStatus::UnderReview->value, touchedAt: now()->subHour());
        $untouched = $this->complaint(ComplaintStatus::Submitted->value, touchedAt: now()->subHours(5));

        $html = $this->actingAs($this->staff)
            ->get(route('staff.complaints.index'))
            ->getContent();

        $this->assertLessThan(
            strpos($html, $touched->ticket_id),
            strpos($html, $untouched->ticket_id),
            'A complaint the office has already touched must sink below one nobody has looked at.',
        );
    }

    public function test_a_rejected_complaint_is_off_the_public_register(): void
    {
        $rejected = $this->complaint(ComplaintStatus::Rejected->value, isPublic: true);
        $resolved = $this->complaint(ComplaintStatus::Resolved->value, isPublic: true);

        $this->assertFalse(
            Complaint::public()->where('id', $rejected->id)->exists(),
            'A complaint the office rejected is not a municipal matter and does not belong on the register.',
        );

        $this->assertTrue(
            Complaint::public()->where('id', $resolved->id)->exists(),
            'A resolved complaint is the record of a problem that was fixed, and stays on the register.',
        );
    }

    private function complaint(
        string $status,
        ?\Illuminate\Support\Carbon $touchedAt = null,
        bool $isPublic = false,
    ): Complaint {
        $complaint = Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => $this->staff->department_id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => $status,
            'review_status' => 'pending',
            'is_public' => $isPublic,
            'content_fingerprint' => Str::random(40),
        ]);

        if ($touchedAt !== null) {
            // updated_at is not fillable, so it has to be set outright.
            $complaint->forceFill(['updated_at' => $touchedAt])->saveQuietly();
        }

        return $complaint->fresh();
    }
}
