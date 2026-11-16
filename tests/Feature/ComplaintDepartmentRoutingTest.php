<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentRouter;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintDepartmentRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private User $engineeringStaff;
    private User $menroStaff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');

        // Seed official LGU departments
        $this->seed(DepartmentSeeder::class);

        // Create users
        $this->citizen = User::factory()->create(['role' => 'citizen']);
        $this->engineeringStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'ENG')->first()->id,
            // A verifier carries the office's whole queue; an ordinary staff
            // member sees only the work assigned to them.
            'can_verify' => true,
        ]);
        $this->menroStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'MENRO')->first()->id,
        ]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function road_damage_category_resolves_to_engineering(): void
    {
        $dept = DepartmentRouter::resolve('road_damage');
        $this->assertNotNull($dept);
        $this->assertEquals('ENG', $dept->code);
        $this->assertEquals('Engineering Office', $dept->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function flooding_category_resolves_to_engineering(): void
    {
        $dept = DepartmentRouter::resolve('flooding');
        $this->assertNotNull($dept);
        $this->assertEquals('ENG', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function streetlight_category_resolves_to_engineering(): void
    {
        $dept = DepartmentRouter::resolve('streetlight');
        $this->assertNotNull($dept);
        $this->assertEquals('ENG', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function garbage_category_resolves_to_menro(): void
    {
        $dept = DepartmentRouter::resolve('garbage');
        $this->assertNotNull($dept);
        $this->assertEquals('MENRO', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sanitation_category_resolves_to_menro(): void
    {
        $dept = DepartmentRouter::resolve('sanitation');
        $this->assertNotNull($dept);
        $this->assertEquals('MENRO', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function plastic_usage_category_resolves_to_menro(): void
    {
        $dept = DepartmentRouter::resolve('plastic_usage');
        $this->assertNotNull($dept);
        $this->assertEquals('MENRO', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_withdrawn_business_permit_category_resolves_to_the_mayors_office(): void
    {
        $dept = DepartmentRouter::resolve('business_permit');
        $this->assertNotNull($dept);
        $this->assertEquals('OM', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function noise_complaint_category_resolves_to_the_mayors_office(): void
    {
        $dept = DepartmentRouter::resolve('noise_complaint');
        $this->assertNotNull($dept);
        $this->assertEquals('OM', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_set_aside_category_falls_back_to_the_mayors_office(): void
    {
        // Park maintenance, stray animals and the catch-all are kept in the
        // system but no office owns them yet, so they land at the general desk.
        foreach (['park_maintenance', 'stray_animals', 'others'] as $category) {
            $dept = DepartmentRouter::resolve($category);
            $this->assertNotNull($dept);
            $this->assertEquals('OM', $dept->code, "Category {$category} should fall back to the Mayor's Office.");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_retired_categories_are_no_longer_offered_to_residents(): void
    {
        $offered = collect(DepartmentRouter::getCategoryOptions())->flatten(1)->pluck('value');

        foreach (['noise_complaint', 'park_maintenance', 'stray_animals', 'business_permit', 'staff_treatment'] as $retired) {
            $this->assertFalse($offered->contains($retired), "{$retired} should no longer be offered.");
        }

        // They keep their names, so the complaints already filed under them
        // still render properly on the public register.
        $labels = DepartmentRouter::categoryLabels();
        $this->assertSame('Noise Complaint', $labels['noise_complaint']);
        $this->assertSame('Stray Animals', $labels['stray_animals']);
        $this->assertSame('Park / Public Area Maintenance', $labels['park_maintenance']);
        $this->assertSame('Business Permit Issues', $labels['business_permit']);
        $this->assertSame('How You Were Treated at the LGU', $labels['staff_treatment']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unknown_category_falls_back_to_the_mayors_office(): void
    {
        $dept = DepartmentRouter::resolve('unknown_category');
        $this->assertNotNull($dept);
        $this->assertEquals('OM', $dept->code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function complaint_is_automatically_routed_on_submission(): void
    {
        $this->actingAs($this->citizen);

        $response = $this->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Large pothole on main road',
            'description' => 'There is a dangerous pothole that needs immediate attention. It has been there for weeks.',
            'image' => $this->complaintEvidence(),
            'urgency' => 'High',
            'terms' => '1',
        ]);

        $response->assertRedirect();

        $complaint = Complaint::latest()->first();
        $this->assertNotNull($complaint->department_id);
        $this->assertEquals(
            Department::where('code', 'ENG')->first()->id,
            $complaint->department_id
        );
        $this->assertEquals('Submitted', $complaint->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function complaint_log_is_created_on_submission(): void
    {
        $this->actingAs($this->citizen);

        $this->post(route('complaints.store'), [
            'category' => 'garbage',
            'title' => 'Uncollected garbage',
            'description' => 'Garbage has not been collected for 3 days in our barangay.',
            'image' => $this->complaintEvidence(),
            'urgency' => 'Medium',
            'terms' => '1',
        ]);

        $complaint = Complaint::latest()->first();
        $log = ComplaintLog::where('complaint_id', $complaint->id)->first();

        $this->assertNotNull($log);
        $this->assertNull($log->previous_status);
        $this->assertEquals('Submitted', $log->new_status);
        $this->assertStringContainsString('Municipal Environment and Natural Resource Office', $log->comment);
        $this->assertEquals($this->citizen->id, $log->actor_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_from_correct_department_can_view_complaint(): void
    {
        $complaint = Complaint::factory()->create([
            'department_id' => $this->engineeringStaff->department_id,
            'user_id' => $this->citizen->id,
        ]);

        $this->actingAs($this->engineeringStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_from_wrong_department_cannot_view_complaint(): void
    {
        $complaint = Complaint::factory()->create([
            'department_id' => $this->engineeringStaff->department_id,
            'user_id' => $this->citizen->id,
        ]);

        $this->actingAs($this->menroStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_any_complaint_regardless_of_department(): void
    {
        $complaint = Complaint::factory()->create([
            'department_id' => $this->engineeringStaff->department_id,
            'user_id' => $this->citizen->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_page_shows_routed_department(): void
    {
        $this->actingAs($this->citizen);

        $this->post(route('complaints.store'), [
            'category' => 'others',
            'title' => 'A general concern for the barangay',
            'description' => 'There is something in the barangay that needs attention from the office.',
            'image' => $this->complaintEvidence(),
            'urgency' => 'Low',
            'terms' => '1',
        ]);

        $complaint = Complaint::latest()->first();

        $response = $this->actingAs($this->citizen)
            ->get(route('complaints.success', $complaint));

        $response->assertOk()
            ->assertSee($complaint->ticket_id)
            ->assertSee("Mayor's Office");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_category_options_returns_grouped_structure(): void
    {
        $options = DepartmentRouter::getCategoryOptions();

        // The group key is the office name the resident is told about, so it
        // has to match the name in the department directory.
        $this->assertArrayHasKey('Engineering Office', $options);
        $this->assertArrayHasKey('Municipal Environment and Natural Resource Office', $options);
        $this->assertArrayHasKey("Mayor's Office", $options);

        $this->assertEquals('road_damage', $options['Engineering Office'][0]['value']);
        $this->assertEquals('Road Damage', $options['Engineering Office'][0]['label']);

        $this->assertContains(
            'plastic_usage',
            array_column($options['Municipal Environment and Natural Resource Office'], 'value'),
        );

        // The withdrawn categories are not offered any more.
        $offered = collect($options)
            ->flatten(1)
            ->pluck('value')
            ->all();
        $this->assertNotContains('business_permit', $offered);
        $this->assertNotContains('staff_treatment', $offered);
    }
}
