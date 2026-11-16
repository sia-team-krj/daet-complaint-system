<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\ComplaintVerificationService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The department may go back to the resident up to three times before the
 * case is escalated to an administrator. Any more than that and the
 * ping-pong stop is deliberate: the resident should not be asked for
 * evidence forever.
 */
class InformationRoundsCapTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $staff;

    private User $resident;

    private Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);

        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->complaint = Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'road_damage',
            'status' => ComplaintStatus::UnderReview->value,
            'review_status' => 'verified',
        ]);
    }

    public function test_the_cap_is_three(): void
    {
        $this->assertSame(3, ComplaintVerificationService::MAX_INFORMATION_ROUNDS);
    }

    public function test_the_third_follow_up_is_allowed_and_the_fourth_is_refused(): void
    {
        // Both previous rounds were opened, answered and settled — a new
        // follow-up is otherwise refused because of the outstanding reply.
        $this->clearedRound(1);
        $this->clearedRound(2);

        $third = $this->service()->requestInformation(
            $this->complaint->fresh(),
            $this->staff,
            'Please also send a photograph of the damage from a few metres back.',
        );

        $this->assertSame(3, $third->round);

        $this->clearedRound(3);

        $this->expectException(ValidationException::class);

        $this->service()->requestInformation(
            $this->complaint->fresh(),
            $this->staff,
            'One more clarification before we can verify this.',
        );
    }

    public function test_the_ask_form_disappears_once_three_rounds_are_answered(): void
    {
        foreach ([1, 2, 3] as $round) {
            $this->clearedRound($round);
        }

        $page = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $this->complaint));

        $page->assertOk()
            ->assertSee(__('staff.verification.rounds_exhausted'))
            ->assertDontSee('name="question"', false);
    }

    public function test_the_third_round_is_offered_while_two_are_cleared(): void
    {
        $this->clearedRound(1);
        $this->clearedRound(2);

        $page = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $this->complaint));

        $page->assertOk()
            ->assertSee('name="question"', false);
    }

    private function clearedRound(int $round): void
    {
        $this->complaint->informationRequests()->create([
            'requested_by' => $this->staff->id,
            'question' => "Round {$round}: please send a clearer photograph of the site.",
            'status' => InformationRequestStatus::Resolved->value,
            'round' => $round,
            'response_text' => 'Here is a clearer photograph of the site.',
            'responded_at' => now(),
            'resolved_at' => now(),
        ]);
    }

    private function service(): ComplaintVerificationService
    {
        return app(ComplaintVerificationService::class);
    }
}
