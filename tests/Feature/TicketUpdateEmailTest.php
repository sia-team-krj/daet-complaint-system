<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Mail\ComplaintUpdatedMail;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Every update a staff member or administrator makes on a ticket tells the
 * resident by email, not just by the in-app bell. The in-app notifications
 * are pinned elsewhere (NotificationDeliveryTest); these tests pin the
 * email channel that runs alongside them.
 */
class TicketUpdateEmailTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $verifier;

    private User $resident;

    private Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->complaint = $this->makeComplaint();
    }

    public function test_a_staff_status_change_emails_the_resident(): void
    {
        Mail::fake();
        $this->complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.update', $this->complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'A crew has been assigned and the site was inspected today.',
            ])
            ->assertRedirect(route('staff.complaints.show', $this->complaint));

        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return $mail->hasTo($this->resident->email)
                && str_contains($mail->subjectLine, $this->complaint->ticket_id)
                && str_contains($mail->subjectLine, 'Under Review')
                && $mail->payload['note'] === 'A crew has been assigned and the site was inspected today.'
                && $mail->payload['ticketId'] === $this->complaint->ticket_id;
        });
    }

    public function test_an_admin_status_change_emails_the_resident(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);

        $this->complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $this->complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'staff_note' => 'The report was checked and is now being scheduled.',
            ])
            ->assertRedirect(route('admin.complaints.show', $this->complaint));

        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return $mail->hasTo($this->resident->email)
                && str_contains($mail->subjectLine, $this->complaint->ticket_id)
                && $mail->payload['note'] === 'The report was checked and is now being scheduled.';
        });
    }

    public function test_a_staff_review_decision_emails_the_resident(): void
    {
        Mail::fake();

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $this->complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'elevated',
            ])
            ->assertRedirect(route('staff.complaints.show', $this->complaint));

        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return $mail->hasTo($this->resident->email)
                && str_contains($mail->subjectLine, $this->complaint->ticket_id)
                && str_contains($mail->payload['summary'], 'Verified');
        });
    }

    public function test_an_admin_review_decision_emails_the_resident_once(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);

        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $this->complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'elevated',
            ])
            ->assertRedirect(route('admin.complaints.show', $this->complaint));

        // The decision also advances Submitted -> Under Review. The resident
        // gets one email for the decision, mirroring the in-app channel.
        Mail::assertSent(ComplaintUpdatedMail::class, 1);
        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return str_contains($mail->payload['summary'], 'Verified');
        });
    }

    public function test_an_information_request_emails_the_resident_the_question(): void
    {
        Mail::fake();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $this->complaint), [
                'question' => 'The streetlight photo is too dark to read the pole number.',
            ])
            ->assertRedirect();

        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return $mail->hasTo($this->resident->email)
                && str_contains($mail->subjectLine, $this->complaint->ticket_id)
                && $mail->payload['note'] === 'The streetlight photo is too dark to read the pole number.';
        });
    }

    public function test_the_auto_close_command_emails_the_resident(): void
    {
        Mail::fake();
        $this->complaint->update([
            'status' => ComplaintStatus::Resolved->value,
            'resolved_at' => now()->subDays(8),
        ]);

        $this->artisan('complaints:auto-close-resolved', ['--days' => 7])
            ->assertSuccessful();

        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return $mail->hasTo($this->resident->email)
                && str_contains($mail->subjectLine, $this->complaint->ticket_id)
                && str_contains($mail->payload['summary'], '7');
        });
    }

    public function test_the_email_uses_the_residents_saved_language(): void
    {
        Mail::fake();
        $this->resident->update(['locale' => 'fil']);

        $this->complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.update', $this->complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'Nag-assign ang crew at na-inspeksyon ang site ngayon.',
            ])
            ->assertRedirect();

        Mail::assertSent(ComplaintUpdatedMail::class, function (ComplaintUpdatedMail $mail): bool {
            return str_starts_with($mail->subjectLine, 'Update sa inyong reklamo');
        });
    }

    public function test_a_mailer_failure_neither_blocks_the_update_nor_hides_the_bell_notification(): void
    {
        // Force the Brevo delivery path and make it fail, the way a dead
        // mail host used to strand the resident with nothing at all.
        config(['services.brevo.api_key' => 'test-key']);
        Http::fake([
            '*api.brevo.com*' => Http::response(['code' => 'unauthorized'], 401),
        ]);

        $this->complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.update', $this->complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'A crew has been assigned and the site was inspected today.',
            ])
            ->assertRedirect(route('staff.complaints.show', $this->complaint));

        $this->assertSame(
            ComplaintStatus::UnderReview->value,
            $this->complaint->refresh()->statusEnum->value,
        );

        $notification = Notification::where('user_id', $this->resident->id)->sole();
        $this->assertSame(Notification::COMPLAINT_STATUS_CHANGED, $notification->type);
    }

    private function makeComplaint(): Complaint
    {
        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'Streetlight',
            'title' => 'Streetlight not working near the hall',
            'description' => 'The streetlight beside the barangay hall has been out for a week.',
            'address_text' => 'Beside the barangay hall',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'is_public' => false,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
