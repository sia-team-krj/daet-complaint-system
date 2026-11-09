<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyComplaintsInfoRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
    }
    private function makeComplaint(User $resident, ?string $departmentCode = 'ENG'): Complaint
    {
        $department = Department::where('code', $departmentCode)->firstOrFail();

        return Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
        ]);
    }

    private function makeVerifier(): User
    {
        $department = Department::where('code', 'ENG')->firstOrFail();

        return User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);
    }

    public function test_resident_sees_the_question_and_reply_form_for_an_open_request(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->makeComplaint($resident);

        ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $this->makeVerifier()->id,
            'question' => 'The photo is too dark to read the pole number near the gate.',
            'status' => 'open',
            'round' => 1,
        ]);

        $this->actingAs($resident)
            ->get(route('complaints.show', $complaint))
            ->assertOk()
            ->assertSee('The photo is too dark to read the pole number near the gate.')
            ->assertSee('Send to the department');
    }

    public function test_resident_can_answer_and_the_request_is_marked_answered(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->makeComplaint($resident);

        $request = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $this->makeVerifier()->id,
            'question' => 'Please share the street address of the streetlight you reported.',
            'status' => 'open',
            'round' => 1,
        ]);

        $this->actingAs($resident)
            ->post(route('complaints.information.store', $complaint), [
                'response' => 'It is the post directly in front of the covered court, beside the basketball court.',
            ])
            ->assertRedirect();

        $this->assertSame('answered', $request->fresh()->status->value);
        $this->assertStringContainsString('covered court', $request->fresh()->response_text);
    }

    public function test_orphan_needs_information_materialises_a_question_when_the_resident_replies(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->makeComplaint($resident);
        $complaint->forceFill(['review_status' => ComplaintReviewStatus::NeedsInformation->value])->save();

        // The resident reply must still have somewhere to land even though the
        // ticket was flagged without a question in the legacy path.
        $this->actingAs($resident)
            ->post(route('complaints.information.store', $complaint), [
                'response' => 'The address is Purok 3, beside the covered court, across from the bakery.',
            ])
            ->assertRedirect();

        $request = $complaint->informationRequests()->latest('id')->first();
        $this->assertNotNull($request);
        $this->assertSame(1, (int) $request->round);
        $this->assertSame('answered', $request->status->value);
    }

    public function test_admin_can_ask_the_resident_from_the_complaint_page(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->makeComplaint($resident);

        $this->actingAs($admin)
            ->post(route('admin.complaints.verification.request-information', $complaint), [
                'question' => 'The streetlight photo is too dark to read the pole number. Please send a closer shot of the pole.',
            ])
            ->assertRedirect();

        $request = $complaint->informationRequests()->first();
        $this->assertNotNull($request);
        $this->assertSame('open', $request->status->value);
        $this->assertSame(ComplaintReviewStatus::NeedsInformation->value, $complaint->fresh()->review_status);
    }

    public function test_staff_review_dropdown_no_longer_offers_needs_information(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->makeComplaint($resident);

        $html = $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('value="needs_information"', $html);
    }
}
