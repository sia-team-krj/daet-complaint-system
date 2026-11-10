<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The staff actions the November rewrite dropped.
 *
 * The backend for asking the resident for evidence, delegating verification
 * and moderating the public copy has always existed. These tests pin the
 * surfaces that reach it — and the rule that a resident's visibility choice
 * is never turned into an office control.
 */
class StaffActionPanelsTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $verifier;

    private User $regularStaff;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        $this->regularStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => false,
        ]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
    }

    public function test_a_verifier_sees_the_ask_form_and_only_eligible_delegates(): void
    {
        $otherVerifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
        ]);
        $assignedWorker = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => false,
        ]);

        $complaint = $this->complaint([
            'assigned_staff_id' => $assignedWorker->id,
        ]);

        $response = $this->actingAs($this->verifier)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $response->assertSee(route('staff.complaints.verification.request-information', $complaint), false);
        $response->assertSee(route('staff.complaints.verification.delegate', $complaint), false);
        $response->assertSee('name="question"', false);
        $response->assertSee('name="verifying_staff_id"', false);
        $response->assertSee('Ask the resident for more evidence');
        $response->assertSee('Hand verification to another verifier');
        $response->assertSee($otherVerifier->full_name);

        // The select must not offer the viewer or the assigned worker: the
        // service refuses both, so the page must not advertise them.
        $values = $this->delegateOptionValues($response->getContent());

        $this->assertContains((string) $otherVerifier->id, $values);
        $this->assertNotContains((string) $this->verifier->id, $values);
        $this->assertNotContains((string) $assignedWorker->id, $values);
    }

    public function test_a_regular_staff_member_does_not_get_verification_controls(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->regularStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertDontSee(route('staff.complaints.verification.request-information', $complaint), false)
            ->assertDontSee(route('staff.complaints.verification.delegate', $complaint), false)
            ->assertDontSee('name="verifying_staff_id"', false);
    }

    public function test_an_open_request_shows_the_question_and_waits_for_the_resident(): void
    {
        $complaint = $this->complaint();
        $question = 'The streetlight photo is too dark to read the pole number. Please send a closer shot.';

        ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $this->verifier->id,
            'question' => $question,
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $this->actingAs($this->verifier)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Waiting on the resident')
            ->assertSee($question)
            ->assertDontSee('name="question"', false);
    }

    public function test_an_answered_request_shows_the_residents_reply(): void
    {
        $complaint = $this->complaint();
        $reply = 'Here is a closer photo taken from the other side of the pole.';

        ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $this->verifier->id,
            'question' => 'Please send a closer shot of the pole number.',
            'status' => InformationRequestStatus::Answered->value,
            'round' => 1,
            'response_text' => $reply,
            'responded_at' => now(),
        ]);

        $this->actingAs($this->verifier)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('The resident answered')
            ->assertSee($reply);
    }

    public function test_the_official_response_form_carries_the_internal_note_field(): void
    {
        $complaint = $this->complaint([
            'review_status' => 'verified',
            'verified_at' => now(),
        ]);

        $this->actingAs($this->regularStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('name="internal_note"', false)
            ->assertSee('Internal note')
            ->assertSee('not visible to the resident');
    }

    public function test_an_internal_note_is_saved_but_kept_off_the_resident_timeline(): void
    {
        Mail::fake();
        $secret = 'Contractor reference ENG-2026-014; do not publish.';

        $complaint = $this->complaint([
            'review_status' => 'verified',
            'verified_at' => now(),
        ]);

        $this->actingAs($this->regularStaff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew has been dispatched to inspect the site today.',
                'internal_note' => $secret,
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertDatabaseHas('complaint_logs', [
            'complaint_id' => $complaint->id,
            'actor_id' => $this->regularStaff->id,
            'comment' => $secret,
            'is_internal' => true,
        ]);

        $this->actingAs($this->resident)
            ->get(route('complaints.show', $complaint))
            ->assertOk()
            ->assertDontSee($secret);
    }

    /**
     * The values offered by the delegation select, parsed rather than grepped:
     * a user id can also appear in an unrelated data attribute.
     *
     * @return array<int, string>
     */
    private function delegateOptionValues(string $html): array
    {
        $previous = libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $values = [];

        foreach ($xpath->query('//select[@name="verifying_staff_id"]/option') as $option) {
            $value = $option->getAttribute('value');

            if ($value !== '') {
                $values[] = $value;
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $values;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function complaint(array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'noise_complaint',
            'status' => ComplaintStatus::UnderReview->value,
            'review_status' => 'pending',
            'confirmed_priority' => 'elevated',
        ], $attributes));
    }
}
