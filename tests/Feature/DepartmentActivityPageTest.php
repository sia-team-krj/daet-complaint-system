<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * "My activity" and the office's trail are different questions.
 *
 * "My activity" answers what *I* did. The dashboard's "recent department
 * activity" card answers what the *office* did — but its "view all activity"
 * link pointed at my own trail, so the office's trail had no page to land on.
 */
class DepartmentActivityPageTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $colleague;
    private Department $engineering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);

        $this->engineering = Department::where('code', 'ENG')->firstOrFail();

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->engineering->id,
            'can_verify' => true,
        ]);

        $this->colleague = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->engineering->id,
            'can_verify' => true,
        ]);
    }

    public function test_the_office_page_shows_what_everyone_in_the_office_has_done(): void
    {
        $mine = $this->log($this->staff);
        $theirs = $this->log($this->colleague);

        $response = $this->actingAs($this->staff)->get(route('staff.department-activity.index'));

        $response->assertOk();
        $response->assertSee($mine->complaint->ticket_id);
        $response->assertSee($theirs->complaint->ticket_id);
    }

    public function test_my_activity_is_still_only_mine(): void
    {
        $mine = $this->log($this->staff);
        $theirs = $this->log($this->colleague);

        $response = $this->actingAs($this->staff)->get(route('staff.activity.index'));

        $response->assertOk();
        $response->assertSee($mine->complaint->ticket_id);
        $response->assertDontSee($theirs->complaint->ticket_id);
    }

    public function test_the_office_page_does_not_show_another_offices_work(): void
    {
        $otherOffice = Department::where('code', 'MENRO')->firstOrFail();
        $other = Complaint::create([
            'ticket_id' => 'COMP-2026-99999',
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => $otherOffice->id,
            'category' => 'garbage',
            'title' => 'Uncollected garbage',
            'description' => 'A long enough description of an uncollected garbage problem for the record.',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'content_fingerprint' => Str::random(40),
        ]);

        ComplaintLog::create([
            'complaint_id' => $other->id,
            'actor_id' => $this->colleague->id,
            'previous_status' => null,
            'new_status' => 'Under Review',
            'comment' => 'Picked this up.',
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.department-activity.index'));

        $response->assertOk();
        $response->assertDontSee('COMP-2026-99999');
    }

    public function test_the_dashboards_link_points_at_the_office_trail(): void
    {
        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertSee(route('staff.department-activity.index'), false);
    }

    public function test_an_administrator_reaches_the_offices_trail_through_the_audit_browser(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.departments.index'));

        $response->assertOk();
        $response->assertSee(
            route('admin.audit.index', ['department_id' => $this->engineering->id]),
            false,
            'The office directory must take the administrator to that office\'s trail.',
        );
    }

    public function test_the_office_page_says_how_much_has_happened(): void
    {
        $this->log($this->staff);

        $response = $this->actingAs($this->staff)->get(route('staff.department-activity.index'));

        $response->assertOk();
        $response->assertSee('Office activity');
        $response->assertSee('action recorded');
    }

    private function log(User $actor): ComplaintLog
    {
        $complaint = Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => $this->engineering->id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => 'Under Review',
            'review_status' => 'pending',
            'content_fingerprint' => Str::random(40),
        ]);

        return ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $actor->id,
            'previous_status' => 'Submitted',
            'new_status' => 'Under Review',
            'comment' => 'Picked this up.',
        ]);
    }
}
