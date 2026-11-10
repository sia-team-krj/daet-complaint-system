<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditDirectory;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        // The directory snapshot is cached across the suite; every test starts
        // from the log as it is right now.
        AuditDirectory::flush();
    }

    public function test_department_creation_is_logged_with_actor_and_department(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), [
            'name' => 'Office of Public Information',
            'code' => 'OPI',
            'description' => 'Public information services.',
        ])->assertRedirect(route('admin.departments.index'));

        $log = ActivityLog::where('action', 'department.created')->sole();

        $this->assertSame($this->admin->id, $log->actor_id);
        $this->assertSame($this->admin->full_name, $log->actor_name);
        $this->assertSame('admin', $log->actor_role);
        $this->assertSame('OPI', $log->subject?->code);
        $this->assertNotNull($log->created_at);
    }

    public function test_staff_complaint_actions_create_activity_and_correct_complaint_logs(): void
    {
        Mail::fake();

        $complaint = Complaint::factory()->create([
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => $this->department->id,
            'status' => ComplaintStatus::Submitted->value,
        ]);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'The field team has been dispatched to inspect the site.',
                'internal_note' => 'Contractor reference: ENG-2026-014.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.status_updated',
            'actor_id' => $this->staff->id,
            'subject_id' => $complaint->id,
            'subject_type' => $complaint->getMorphClass(),
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.internal_note_added',
            'actor_id' => $this->staff->id,
            'subject_id' => $complaint->id,
        ]);

        $publicLog = ComplaintLog::where('complaint_id', $complaint->id)->where('is_internal', false)->sole();
        $internalLog = ComplaintLog::where('complaint_id', $complaint->id)->where('is_internal', true)->sole();

        $this->assertSame($this->staff->id, $publicLog->actor_id);
        $this->assertSame($this->staff->id, $internalLog->actor_id);
        $this->assertSame(ComplaintStatus::Submitted->value, $internalLog->new_status);
    }

    public function test_admin_staff_account_changes_are_logged(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.staff.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@example.com',
            'contact_number' => '09171234567',
            'department_id' => $this->department->id,
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'maria.santos@example.com')->sole();
        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $staff->id,
            'purpose' => 'registration',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.staff.toggle', $staff))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'staff.created',
            'actor_id' => $this->admin->id,
            'subject_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'staff.deactivated',
            'actor_id' => $this->admin->id,
            'subject_id' => $staff->id,
        ]);
    }

    public function test_admin_audit_page_can_filter_by_department(): void
    {
        $complaint = Complaint::factory()->create([
            'department_id' => $this->department->id,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
        ]);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'subject_type' => $complaint->getMorphClass(),
            'subject_id' => $complaint->id,
            'description' => 'Complaint reviewed by the department.',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', ['department_id' => $this->department->id]))
            ->assertOk()
            ->assertSee('Complaint reviewed by the department.')
            ->assertSee($this->department->name);
    }

    public function test_admin_audit_directory_lists_departments_people_and_counts(): void
    {
        $gso = Department::where('code', 'GSO')->firstOrFail();
        $citizen = User::factory()->create(['role' => 'citizen']);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Engineering staff entry.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => $citizen->id,
            'actor_name' => $citizen->full_name,
            'actor_role' => 'citizen',
            'department_id' => $this->department->id,
            'action' => 'complaint.filed',
            'description' => 'Resident entry.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => null,
            'actor_name' => null,
            'actor_role' => null,
            'department_id' => $this->department->id,
            'action' => 'system.sweep',
            'description' => 'Automated entry.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.audit.index'));

        $response->assertOk()
            ->assertSee(__('audit.everyone'))
            ->assertSee(__('audit.departments'))
            ->assertSee(__('audit.automated'))
            ->assertSee($this->department->name)
            ->assertSee($gso->name)
            ->assertSee($this->staff->full_name)
            ->assertSee($citizen->full_name)
            ->assertSee(__('audit.automated_records'));

        // The rail counts PEOPLE: one member of staff in that office (the
        // resident belongs to the residents scope, not to the office).
        $html = $response->getContent();
        $name = preg_quote(e($this->department->name), '/');
        $this->assertMatchesRegularExpression(
            '/admin-audit-browser__context-name">'.$name.'<\/span>\s*<span class="admin-audit-browser__context-count">1/',
            $html,
        );

        // The trail counts ENTRIES: staff entry + resident filing + automated
        // record all carry ENG.
        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', ['department_id' => $this->department->id]))
            ->assertOk()
            ->assertSee(trans_choice('audit.entries', 3));
    }

    public function test_admin_audit_roster_is_bounded_and_expands_on_demand(): void
    {
        $residents = User::factory()->count(55)->create(['role' => 'citizen']);

        foreach ($residents as $resident) {
            ActivityLog::create([
                'actor_id' => $resident->id,
                'actor_name' => $resident->full_name,
                'actor_role' => 'citizen',
                'department_id' => $this->department->id,
                'action' => 'complaint.filed',
                'description' => 'Resident entry by '.$resident->full_name.'.',
                'created_at' => now(),
            ]);
        }

        AuditDirectory::flush();

        // The preview never renders the whole group.
        $preview = $this->actingAs($this->admin)->get(route('admin.audit.index'));

        $preview->assertOk()
            ->assertSee(__('audit.showing_rows', ['shown' => 8, 'total' => 55]))
            ->assertSee(__('audit.see_all', ['count' => 55]))
            ->assertSee('people_group=residents', false);

        $this->assertSame(
            8,
            preg_match_all('/class="admin-audit-browser__person[ "]/', $preview->getContent()),
            'The roster preview must stay bounded at 8 rows per group.',
        );

        // Asking for the group opens it, 25 people to a page — never a dump.
        $expanded = $this->actingAs($this->admin)->get(route('admin.audit.index', [
            'people_group' => 'residents',
            'people_page' => 1,
        ]));

        $expanded->assertOk()
            ->assertSee(__('audit.collapse'))
            ->assertSee(__('audit.showing_range', ['from' => 1, 'to' => 25, 'total' => 55]))
            ->assertSee(__('audit.page_of', ['current' => 1, 'last' => 3]));

        $this->assertSame(
            25,
            preg_match_all('/class="admin-audit-browser__person[ "]/', $expanded->getContent()),
            'An expanded group lists one page of people.',
        );

        $second = $this->actingAs($this->admin)->get(route('admin.audit.index', [
            'people_group' => 'residents',
            'people_page' => 2,
        ]));

        $second->assertOk()
            ->assertSee(__('audit.page_of', ['current' => 2, 'last' => 3]))
            ->assertSee(__('audit.showing_range', ['from' => 26, 'to' => 50, 'total' => 55]));

        $this->assertSame(
            25,
            preg_match_all('/class="admin-audit-browser__person[ "]/', $second->getContent()),
            'The second page holds the next slice.',
        );

        $last = $this->actingAs($this->admin)->get(route('admin.audit.index', [
            'people_group' => 'residents',
            'people_page' => 3,
        ]));

        $last->assertOk()
            ->assertSee(__('audit.showing_range', ['from' => 51, 'to' => 55, 'total' => 55]));

        $this->assertSame(
            5,
            preg_match_all('/class="admin-audit-browser__person[ "]/', $last->getContent()),
            'The last page holds the remainder.',
        );
    }

    public function test_admin_audit_people_search_finds_only_people_with_activity(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Searchable',
            'last_name' => 'Resident',
        ]);

        $silent = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Silent',
            'last_name' => 'Resident',
        ]);

        ActivityLog::create([
            'actor_id' => $citizen->id,
            'actor_name' => $citizen->full_name,
            'actor_role' => 'citizen',
            'department_id' => $this->department->id,
            'action' => 'complaint.filed',
            'description' => 'A filed complaint.',
            'created_at' => now(),
        ]);

        AuditDirectory::flush();

        $response = $this->actingAs($this->admin)->get(route('admin.audit.index', [
            'people_q' => 'Searchable',
        ]));

        $response->assertOk()
            ->assertSee($citizen->full_name)
            ->assertDontSee($silent->full_name);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', ['people_q' => 'Zzzznotfound']))
            ->assertOk()
            ->assertSee(__('audit.no_person_matches', ['query' => 'Zzzznotfound']));
    }

    public function test_admin_audit_browser_endpoint_returns_the_fragments(): void
    {
        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Staff entry.',
            'created_at' => now(),
        ]);

        AuditDirectory::flush();

        $response = $this->actingAs($this->admin)->getJson(route('admin.audit.browser', [
            'department_id' => $this->department->id,
        ]));

        $response->assertOk()->assertJsonStructure(['scope', 'rail', 'roster', 'trail']);

        $payload = $response->json();

        $this->assertStringContainsString(e($this->staff->full_name), (string) $payload['roster']);
        $this->assertStringContainsString('Staff entry.', (string) $payload['trail']);
        $this->assertStringContainsString(e($this->department->name), (string) $payload['rail']);

        // The pager inside the fragment links to the page, never to the
        // endpoint that rendered it.
        $this->assertStringNotContainsString('/audit-trail/browser', (string) $payload['trail']);
    }

    public function test_admin_audit_people_endpoint_returns_the_roster_fragment(): void
    {
        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Staff entry.',
            'created_at' => now(),
        ]);

        AuditDirectory::flush();

        $response = $this->actingAs($this->admin)->getJson(route('admin.audit.people', ['q' => '']));

        $response->assertOk()
            ->assertJsonStructure(['query', 'roster']);

        $this->assertStringContainsString(e($this->staff->full_name), (string) $response->json('roster'));
    }

    public function test_admin_audit_page_filters_by_actor_and_system_records(): void
    {
        $otherStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Staff-only entry.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => $otherStaff->id,
            'actor_name' => $otherStaff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Other staff entry.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => null,
            'actor_name' => null,
            'actor_role' => null,
            'department_id' => $this->department->id,
            'action' => 'system.sweep',
            'description' => 'Automated entry.',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', ['actor_id' => $this->staff->id]))
            ->assertOk()
            ->assertSee('Staff-only entry.')
            ->assertDontSee('Other staff entry.')
            ->assertDontSee('Automated entry.');

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', ['system' => 1]))
            ->assertOk()
            ->assertSee('Automated entry.')
            ->assertDontSee('Staff-only entry.')
            ->assertDontSee('Other staff entry.');
    }

    public function test_legacy_combined_department_and_actor_url_filters_both(): void
    {
        $gso = Department::where('code', 'GSO')->firstOrFail();
        $otherStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $gso->id,
            'action' => 'complaint.reviewed',
            'description' => 'Same actor in another office.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => $otherStaff->id,
            'actor_name' => $otherStaff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Another actor in the selected office.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Matching pair entry.',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', [
                'department_id' => $this->department->id,
                'actor_id' => $this->staff->id,
            ]))
            ->assertOk()
            ->assertSee('Matching pair entry.')
            ->assertDontSee('Same actor in another office.')
            ->assertDontSee('Another actor in the selected office.');
    }

    public function test_admin_audit_directory_groups_people_without_duplicating_them(): void
    {
        $adminWithOffice = User::factory()->create([
            'role' => 'admin',
            'department_id' => $this->department->id,
        ]);
        $adminWithoutOffice = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);
        $citizen = User::factory()->create(['role' => 'citizen']);

        foreach ([$adminWithOffice, $adminWithoutOffice, $citizen] as $actor) {
            ActivityLog::create([
                'actor_id' => $actor->id,
                'actor_name' => $actor->full_name,
                'actor_role' => $actor->role,
                'department_id' => $this->department->id,
                'action' => 'complaint.reviewed',
                'description' => 'Entry by '.$actor->full_name.'.',
                'created_at' => now(),
            ]);
        }

        $html = $this->actingAs($this->admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->getContent();

        // Three people with entries. The automated records live in the scope
        // rail, not in the people roster.
        $this->assertSame(3, preg_match_all('/class="admin-audit-browser__person[ "]/', $html));

        // The office-less admin sits in Administration; the admin assigned to
        // ENG sits under ENG and is not repeated in the Administration group.
        $start = strpos($html, 'admin-audit-browser__group-name">Administration<');
        $end = strpos($html, 'admin-audit-browser__group-name">', $start + 10);
        $administration = substr($html, $start, $end - $start);

        $this->assertStringContainsString(e($adminWithoutOffice->full_name), $administration);
        $this->assertStringNotContainsString(e($adminWithOffice->full_name), $administration);
    }

    public function test_admin_audit_residents_scope_filters_to_resident_activity(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Staff entry.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => $citizen->id,
            'actor_name' => $citizen->full_name,
            'actor_role' => 'citizen',
            'department_id' => $this->department->id,
            'action' => 'complaint.filed',
            'description' => 'Resident entry.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => null,
            'actor_name' => null,
            'actor_role' => null,
            'department_id' => $this->department->id,
            'action' => 'system.sweep',
            'description' => 'Automated entry.',
            'created_at' => now(),
        ]);

        AuditDirectory::flush();

        $response = $this->actingAs($this->admin)->get(route('admin.audit.index', ['residents' => 1]));

        $response->assertOk()
            ->assertSee('Resident entry.')
            ->assertDontSee('Staff entry.')
            ->assertDontSee('Automated entry.')
            ->assertSee(__('audit.residents_all'))
            ->assertSee(__('audit.residents'));

        // The rail offers the scope, and the roster narrows to it.
        $this->assertStringContainsString('residents=1', $response->getContent());
        $this->assertStringContainsString('data-group="residents"', $response->getContent());
    }

    public function test_admin_audit_drill_down_ignores_unknown_entity_ids(): void
    {
        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'description' => 'Visible entry.',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', [
                'department_id' => 999999,
                'actor_id' => 999999,
            ]))
            ->assertOk()
            ->assertSee('Visible entry.')
            ->assertSee(__('audit.everyone'));
    }

    public function test_audit_trail_is_admin_only(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get(route('admin.audit.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_activity_records_cannot_be_updated_or_deleted(): void
    {
        $log = ActivityLog::create([
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->full_name,
            'actor_role' => 'admin',
            'action' => 'test.record',
            'description' => 'Test activity record.',
            'created_at' => now(),
        ]);

        $this->expectException(\LogicException::class);
        $log->update(['description' => 'Tampered description.']);
    }
}
