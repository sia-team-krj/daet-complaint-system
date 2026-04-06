<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ComplaintDepartmentRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private User $engineeringStaff;
    private User $gsoStaff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed departments
        Department::create(['code' => 'ENGR', 'name' => 'Engineering Office', 'is_active' => true]);
        Department::create(['code' => 'GSO', 'name' => 'General Services Office', 'is_active' => true]);
        Department::create(['code' => 'BPLS', 'name' => 'Business Permits & Licensing', 'is_active' => true]);
        Department::create(['code' => 'PNP', 'name' => 'Philippine National Police', 'is_active' => true]);
        Department::create(['code' => 'MAO', 'name' => 'Municipal Agriculture Office', 'is_active' => true]);

        // Create users
        $this->citizen = User::factory()->create(['role' => 'citizen']);
        $this->engineeringStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'ENGR')->first()->id,
        ]);
        $this->gsoStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'GSO')->first()->id,
        ]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function road_damage_category_resolves_to_engineering(): void
    {
        $dept = DepartmentRouter::resolve('road_damage');
        $this->assertNotNull($dept);
        $this->assertEquals('ENGR', $dept->code);
        $this->assertEquals('Engineering Office', $dept->name);
    }

    /** @test */
    public function flooding_category_resolves_to_engineering(): void
    {
        $dept = DepartmentRouter::resolve('flooding');
        $this->assertNotNull($dept);
        $this->assertEquals('ENGR', $dept->code);
    }

    /** @test */
    public function streetlight_category_resolves_to_engineering(): void
    {
        $dept = DepartmentRouter::resolve('streetlight');
        $this->assertNotNull($dept);
        $this->assertEquals('ENGR', $dept->code);
    }

    /** @test */
    public function garbage_category_resolves_to_gso(): void
    {
        $dept = DepartmentRouter::resolve('garbage');
        $this->assertNotNull($dept);
        $this->assertEquals('GSO', $dept->code);
    }

    /** @test */
    public function sanitation_category_resolves_to_gso(): void
    {
        $dept = DepartmentRouter::resolve('sanitation');
        $this->assertNotNull($dept);
        $this->assertEquals('GSO', $dept->code);
    }

    /** @test */
    public function business_permit_category_resolves_to_bpls(): void
    {
        $dept = DepartmentRouter::resolve('business_permit');
        $this->assertNotNull($dept);
        $this->assertEquals('BPLS', $dept->code);
    }

    /** @test */
    public function noise_complaint_category_resolves_to_pnp(): void
    {
        $dept = DepartmentRouter::resolve('noise_complaint');
        $this->assertNotNull($dept);
        $this->assertEquals('PNP', $dept->code);
    }

    /** @test */
    public function stray_animals_category_resolves_to_agriculture(): void
    {
        $dept = DepartmentRouter::resolve('stray_animals');
        $this->assertNotNull($dept);
        $this->assertEquals('MAO', $dept->code);
    }

    /** @test */
    public function unknown_category_falls_back_to_gso(): void
    {
        $dept = DepartmentRouter::resolve('unknown_category');
        $this->assertNotNull($dept);
        $this->assertEquals('GSO', $dept->code);
    }

    /** @test */
    public function complaint_is_automatically_routed_on_submission(): void
    {
        $this->actingAs($this->citizen);

        $response = $this->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Large pothole on main road',
            'description' => 'There is a dangerous pothole that needs immediate attention. It has been there for weeks.',
            'urgency' => 'High',
            'terms' => '1',
        ]);

        $response->assertRedirect();

        $complaint = Complaint::latest()->first();
        $this->assertNotNull($complaint->department_id);
        $this->assertEquals(
            Department::where('code', 'ENGR')->first()->id,
            $complaint->department_id
        );
        $this->assertEquals('Submitted', $complaint->status->value);
    }

    /** @test */
    public function complaint_log_is_created_on_submission(): void
    {
        $this->actingAs($this->citizen);

        $this->post(route('complaints.store'), [
            'category' => 'garbage',
            'title' => 'Uncollected garbage',
            'description' => 'Garbage has not been collected for 3 days in our barangay.',
            'urgency' => 'Medium',
            'terms' => '1',
        ]);

        $complaint = Complaint::latest()->first();
        $log = ComplaintLog::where('complaint_id', $complaint->id)->first();

        $this->assertNotNull($log);
        $this->assertNull($log->previous_status);
        $this->assertEquals('Submitted', $log->new_status);
        $this->assertStringContainsString('General Services Office', $log->comment);
        $this->assertEquals($this->citizen->id, $log->changed_by);
    }

    /** @test */
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

    /** @test */
    public function staff_from_wrong_department_cannot_view_complaint(): void
    {
        $complaint = Complaint::factory()->create([
            'department_id' => $this->engineeringStaff->department_id,
            'user_id' => $this->citizen->id,
        ]);

        $this->actingAs($this->gsoStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertRedirect();
    }

    /** @test */
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

    /** @test */
    public function success_page_shows_routed_department(): void
    {
        $this->actingAs($this->citizen);

        $this->post(route('complaints.store'), [
            'category' => 'business_permit',
            'title' => 'Business permit issue',
            'description' => 'Need help with my business permit renewal.',
            'urgency' => 'Low',
            'terms' => '1',
        ]);

        $complaint = Complaint::latest()->first();

        $response = $this->actingAs($this->citizen)
            ->get(route('complaints.success', $complaint));

        $response->assertOk()
            ->assertSee($complaint->ticket_id)
            ->assertSee('Business Permits');
    }

    /** @test */
    public function get_category_options_returns_grouped_structure(): void
    {
        $options = DepartmentRouter::getCategoryOptions();

        $this->assertArrayHasKey('Engineering', $options);
        $this->assertArrayHasKey('General Services', $options);
        $this->assertArrayHasKey('Business Permits', $options);
        $this->assertArrayHasKey('Peace & Order', $options);
        $this->assertArrayHasKey('Agriculture', $options);

        $this->assertEquals('road_damage', $options['Engineering'][0]['value']);
        $this->assertEquals('Road Damage', $options['Engineering'][0]['label']);
    }
}
