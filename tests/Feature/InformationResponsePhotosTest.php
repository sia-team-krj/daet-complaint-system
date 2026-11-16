<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The photos the resident attaches to a verification reply are evidence the
 * verifier asked for. They were stored on the reply but never rendered, so
 * the office could read "here are clearer shots" and never see a single one.
 * They are not public: only the owner, the department and an admin may ever
 * stream them.
 */
class InformationResponsePhotosTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Department $otherDepartment;

    private User $resident;

    private User $staff;

    private User $otherStaff;

    private User $admin;

    private Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->otherDepartment = Department::where('code', 'MENRO')->firstOrFail();

        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        $this->otherStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->otherDepartment->id,
            'can_verify' => true,
        ]);
        $this->admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);

        $this->complaint = Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'road_damage',
            'status' => ComplaintStatus::UnderReview->value,
            'review_status' => 'verified',
        ]);
    }

    public function test_the_staff_panel_shows_the_reply_photos(): void
    {
        $this->answeredRequestWithPhotos(2);

        $page = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $this->complaint));

        $page->assertOk()
            ->assertSee(__('staff.verification.reply_photos'))
            ->assertSee(route('complaints.information.evidence', [$this->complaint, $this->firstRequest(), 0]), false)
            ->assertSee(route('complaints.information.evidence', [$this->complaint, $this->firstRequest(), 1]), false);
    }

    public function test_the_resident_sees_their_own_reply_photos(): void
    {
        $this->answeredRequestWithPhotos(1);

        $page = $this->actingAs($this->resident)
            ->get(route('complaints.show', $this->complaint));

        $page->assertOk()
            ->assertSee(route('complaints.information.evidence', [$this->complaint, $this->firstRequest(), 0]), false);
    }

    public function test_the_staff_member_can_stream_a_reply_photo(): void
    {
        $request = $this->answeredRequestWithPhotos(1);

        $response = $this->actingAs($this->staff)
            ->get(route('complaints.information.evidence', [$this->complaint, $request, 0]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_an_administrator_can_stream_a_reply_photo(): void
    {
        $request = $this->answeredRequestWithPhotos(1);

        $this->actingAs($this->admin)
            ->get(route('complaints.information.evidence', [$this->complaint, $request, 0]))
            ->assertOk();
    }

    public function test_another_department_cannot_stream_a_reply_photo(): void
    {
        $request = $this->answeredRequestWithPhotos(1);

        $this->actingAs($this->otherStaff)
            ->get(route('complaints.information.evidence', [$this->complaint, $request, 0]))
            ->assertForbidden();
    }

    public function test_the_photo_index_must_exist(): void
    {
        $request = $this->answeredRequestWithPhotos(1);

        $this->actingAs($this->staff)
            ->get(route('complaints.information.evidence', [$this->complaint, $request, 5]))
            ->assertNotFound();
    }

    public function test_a_request_from_a_different_complaint_is_not_found(): void
    {
        $request = $this->answeredRequestWithPhotos(1);
        $otherComplaint = Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'streetlight',
            'status' => ComplaintStatus::InProgress->value,
            'review_status' => 'verified',
        ]);

        $this->actingAs($this->staff)
            ->get(route('complaints.information.evidence', [$otherComplaint, $request, 0]))
            ->assertNotFound();
    }

    private function answeredRequestWithPhotos(int $photoCount): ComplaintInformationRequest
    {
        $paths = [];

        for ($i = 0; $i < $photoCount; $i++) {
            $path = 'complaints/information/test-'.Str::random(16).'.png';

            Storage::disk('minio')->put(
                $path,
                file_get_contents(public_path('images/lgulogo.png')),
            );

            $paths[] = $path;
        }

        return $this->complaint->informationRequests()->create([
            'requested_by' => $this->staff->id,
            'question' => 'The photo is too dark to read the pole number. Please send a closer shot.',
            'status' => InformationRequestStatus::Answered->value,
            'round' => 1,
            'response_text' => 'Sent a closer shot of the pole number.',
            'response_image_paths' => $paths,
            'responded_at' => now(),
        ]);
    }

    private function firstRequest(): ComplaintInformationRequest
    {
        return $this->complaint->informationRequests()->latest('id')->firstOrFail();
    }
}
