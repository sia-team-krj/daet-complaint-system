<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use App\Services\DepartmentRouter;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards department administration.
 *
 * Two of the rules here exist because of what the schema does rather than what
 * a controller does. `invitation_codes.department_id` is ON DELETE RESTRICT and
 * the other three references are ON DELETE SET NULL, so deleting a department
 * would either be refused by the database or quietly null the department behind
 * every complaint, staff account, and audit row that office ever held. That is
 * why there is no delete anywhere in this feature and archive takes its place.
 *
 * The routing assertions matter for the same reason. DepartmentRouter maps
 * complaint categories to office *codes*, so archiving an office that the router
 * resolves silently changes where new complaints land — which is the entire
 * point of archiving, and invisible from the departments page.
 */
class DepartmentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ── Create ──────────────────────────────────────────────

    public function test_an_admin_can_create_a_department(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.departments.store'), [
                'name' => 'Liaison Office',
                'code' => 'LTO',
                'description' => 'Inter-agency liaison.',
            ])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseHas('departments', [
            'name' => 'Liaison Office',
            'code' => 'LTO',
            'is_active' => true,
        ]);
    }

    public function test_creating_a_department_is_logged_with_the_values_written(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), [
            'name' => 'Liaison Office',
            'code' => 'LTO',
            'description' => 'Inter-agency liaison.',
        ]);

        $log = ActivityLog::where('action', 'department.created')->latest('id')->first();

        $this->assertNotNull($log, 'Creating a department must leave an audit record.');
        $this->assertSame('Liaison Office', $log->metadata['name']);
        $this->assertSame('LTO', $log->metadata['code']);
        $this->assertTrue($log->metadata['is_active']);
        $this->assertSame(
            Department::where('code', 'LTO')->first()?->id,
            $log->department_id,
            'The audit record must point at the office that was created.',
        );
    }

    public function test_a_duplicate_code_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.departments.store'), ['name' => 'Duplicate', 'code' => 'ENG'])
            ->assertSessionHasErrors('code');
    }

    /**
     * Creation has its own page.
     *
     * It used to be a panel below the directory, which on a phone meant adding
     * an office meant scrolling past every existing office first. The directory
     * is for scanning offices; the form is a separate destination now.
     */
    public function test_creating_a_department_has_its_own_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.departments.create'))
            ->assertOk()
            ->assertSee('Create department')
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="description"', false);
    }

    /**
     * The code's consequence is stated where the code is chosen.
     *
     * Only five codes auto-route complaints. An admin who picks a different one
     * and finds out weeks later that the new office received nothing has no way
     * back to this page, so the page has to say it up front.
     */
    public function test_the_create_page_explains_that_only_five_codes_route_automatically(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.departments.create'))
            ->assertOk()
            ->getContent();

        foreach (DepartmentRouter::ROUTABLE_CODES as $code) {
            $this->assertStringContainsString($code, $html, "The routable code {$code} is not named on the create page.");
        }

        $this->assertStringContainsString(
            'receives nothing on its own',
            $html,
            'The create page does not warn that a non-routable code receives nothing automatically.',
        );
    }

    /** The codes already taken are listed, so uniqueness is checkable by eye. */
    public function test_the_create_page_lists_the_codes_already_in_use(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.departments.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'ENG',
            $html,
            'The create page does not list the codes already in use, so an admin cannot avoid a collision by eye.',
        );
    }

    /**
     * The directory must not carry a second copy of the form.
     *
     * Asserted on the form's action attribute, not on the store URL as a
     * substring: the store URL is `/admin/departments`, which is a prefix of
     * every per-office link on the page, so a plain "does not contain" check can
     * never pass no matter what the page renders.
     */
    public function test_the_directory_no_longer_renders_the_create_form(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            0,
            substr_count($html, 'action="'.route('admin.departments.store').'"'),
            'The directory still renders a form that posts to the create endpoint.',
        );

        $this->assertStringContainsString(
            route('admin.departments.create'),
            $html,
            'The directory no longer links to the create page, so there is no way to reach it.',
        );
    }

    // ── Edit ────────────────────────────────────────────────

    public function test_an_admin_can_edit_a_department(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.departments.update', $department), [
                'name' => 'Social Welfare and Development Office',
                'code' => 'SWDO',
                'description' => 'Renamed description.',
            ])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertSame('Renamed description.', $department->fresh()->description);
    }

    public function test_editing_is_logged_with_before_and_after_values(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();
        $originalName = $department->name;

        $this->actingAs($this->admin)->put(route('admin.departments.update', $department), [
            'name' => 'Social Welfare Office',
            'code' => 'SWDO',
            'description' => 'Shortened.',
        ]);

        $log = ActivityLog::where('action', 'department.updated')->latest('id')->first();

        $this->assertNotNull($log, 'Editing a department must leave an audit record.');
        $this->assertSame($originalName, $log->metadata['before']['name']);
        $this->assertSame('Social Welfare Office', $log->metadata['after']['name']);
    }

    /**
     * The code is a routing key, not a label.
     *
     * DepartmentRouter maps categories to codes. Renaming ENG would leave
     * road_damage, flooding, and streetlight resolving to nothing — and the
     * complaint would be filed with no department at all, which is the failure
     * this refusal prevents.
     */
    public function test_a_routable_code_cannot_be_renamed(): void
    {
        $engineering = Department::where('code', 'ENG')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.departments.update', $engineering), [
                'name' => 'Engineering Office',
                'code' => 'ENGR',
                'description' => null,
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame('ENG', $engineering->fresh()->code, 'The routing code must be unchanged.');
    }

    public function test_a_non_routable_code_can_be_renamed(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.departments.update', $department), [
                'name' => $department->name,
                'code' => 'SWDO2',
                'description' => $department->description,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('SWDO2', $department->fresh()->code);
    }

    public function test_an_edit_cannot_take_another_offices_name_or_code(): void
    {
        $social = Department::where('code', 'SWDO')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.departments.update', $social), [
                'name' => 'Health Office',
                'code' => 'HLTH',
                'description' => null,
            ])
            ->assertSessionHasErrors(['name', 'code']);
    }

    // ── Archive ─────────────────────────────────────────────

    public function test_an_admin_can_archive_a_department(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.departments.archive', $department))
            ->assertRedirect();

        $this->assertFalse($department->fresh()->is_active);
    }

    public function test_archiving_is_logged(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.departments.archive', $department));

        $log = ActivityLog::where('action', 'department.archived')->latest('id')->first();

        $this->assertNotNull($log, 'Archiving a department must leave an audit record.');
        $this->assertTrue($log->metadata['previous_is_active']);
        $this->assertFalse($log->metadata['is_active']);
    }

    /**
     * Archiving is reversible, and there is no delete route anywhere in this
     * feature — this is the assertion that keeps it that way.
     */
    public function test_an_archived_department_can_be_restored(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.departments.archive', $department));
        $this->actingAs($this->admin)->post(route('admin.departments.restore', $department));

        $this->assertTrue($department->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'department.restored']);
    }

    /**
     * With no active office the router has nowhere to send a complaint, so every
     * new filing would land unassigned. Refused at the controller, because the
     * alternative is a queue that silently stops routing.
     */
    public function test_the_last_active_department_cannot_be_archived(): void
    {
        $keep = Department::query()->firstOrFail();

        Department::where('id', '!=', $keep->id)->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->from(route('admin.departments.index'))
            ->post(route('admin.departments.archive', $keep))
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('error');

        $this->assertTrue($keep->fresh()->is_active);
    }

    /** Work in flight needs an owner, so it blocks archiving rather than orphaning it. */
    public function test_a_department_with_open_complaints_cannot_be_archived(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        // The stored value is "In Progress", not the enum case name. The
        // controller's open-case filter reads the stored value, so a test that
        // writes "InProgress" would create a complaint the filter never sees —
        // and the guard would appear to be broken when it is working.
        Complaint::factory()->create([
            'department_id' => $department->id,
            'status' => ComplaintStatus::InProgress->value,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.departments.index'))
            ->post(route('admin.departments.archive', $department))
            ->assertSessionHas('error');

        $this->assertTrue($department->fresh()->is_active);
    }

    public function test_closed_complaints_do_not_block_archiving(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();

        Complaint::factory()->create([
            'department_id' => $department->id,
            'status' => ComplaintStatus::Resolved->value,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.departments.archive', $department));

        $this->assertFalse($department->fresh()->is_active);
    }

    public function test_archived_departments_are_hidden_from_the_directory_by_default(): void
    {
        $archived = Department::where('code', 'SWDO')->firstOrFail();
        $archived->update(['is_active' => false]);
        $active = Department::where('code', 'WST')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertDontSee($archived->name)
            ->assertSee($active->name);

        $this->actingAs($this->admin)
            ->get(route('admin.departments.index', ['archived' => 1]))
            ->assertOk()
            ->assertSee($archived->name)
            // The archived tab must exclude active offices. Returning both lists
            // under one label is how this tab shipped reading "Archived" while
            // showing the active directory.
            ->assertDontSee($active->name);
    }

    /**
     * No delete route exists for departments, at any verb.
     *
     * Asserted over the route table rather than per-request because the danger
     * is the route existing at all: three of four references are ON DELETE SET
     * NULL, so a delete that worked would silently erase the routing history
     * behind every complaint and staff account that office ever held.
     */
    public function test_there_is_no_route_that_deletes_a_department(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'departments'));

        $this->assertSame(
            [],
            $routes->filter(fn ($route) => in_array('DELETE', $route->methods(), true))->toArray(),
            'A DELETE route exists for departments. Delete is refused or silently nulls '
                .'the office behind every complaint, staff account, and audit record behind it.',
        );

        // Archive and restore must be reachable, or the guard above is just an
        // absent feature rather than a deliberate substitute.
        $verbs = $routes->map(fn ($route) => $route->uri().' => '.implode('|', $route->methods()));
        $this->assertTrue(
            collect($verbs)->contains(fn ($v) => str_contains($v, '{department}/archive')),
            'There is no archive route for an individual department.',
        );
        $this->assertTrue(
            collect($verbs)->contains(fn ($v) => str_contains($v, '{department}/restore')),
            'There is no restore route for an individual department.',
        );
    }

    // ── Routing behaviour ───────────────────────────────────

    /** The whole reason archiving matters: a retired office stops receiving work. */
    public function test_an_archived_department_stops_receiving_routed_complaints(): void
    {
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.departments.archive', $engineering));

        $routed = DepartmentRouter::resolve('road_damage');

        $this->assertNotNull($routed, 'Routing must still land somewhere after an office is archived.');
        $this->assertNotSame('ENG', $routed->code, 'Routing sent a complaint to an archived office.');
    }

    public function test_an_archived_department_cannot_receive_staff_invitations(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();
        $department->update(['is_active' => false]);

        $listed = InvitationCode::query();
        $options = Department::where('is_active', true)->orderBy('name')->pluck('code');

        $this->assertNotContains('SWDO', $options->all());
    }

    /** An archived office must not appear as somewhere to send new work. */
    public function test_assignment_dropdowns_exclude_archived_offices(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();
        $department->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.staff.create'))
            ->assertOk()
            ->assertDontSee($department->name);
    }

    /**
     * But historical work stays findable. Filtering complaints by an office that
     * has since been archived must still work, or the archive erases the record
     * of what that office was handling.
     */
    public function test_the_complaint_filter_still_offers_archived_offices(): void
    {
        $department = Department::where('code', 'SWDO')->firstOrFail();
        $department->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee($department->name);
    }
}