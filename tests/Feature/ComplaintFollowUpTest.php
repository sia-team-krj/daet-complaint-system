<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private function followUpUsers(): array
    {
        $department = Department::factory()->create();
        $owner = User::factory()->create(['role' => 'citizen']);
        $staff = User::factory()->create(['role' => 'staff', 'department_id' => $department->id]);
        $other = User::factory()->create(['role' => 'citizen']);

        return [$department, $owner, $staff, $other];
    }

    public function test_stores_a_resident_follow_up_message_on_the_same_complaint(): void
    {
        Storage::fake('minio');
        [$department, $owner] = $this->followUpUsers();
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::Resolved->value,
        ]);

        $response = $this->actingAs($owner)->post(route('complaints.follow_up.store', $complaint), [
            'body' => 'The issue is still happening after the repair.',
            'images' => [UploadedFile::fake()->image('proof.jpg')],
        ]);

        $response->assertRedirect(route('complaints.show', $complaint));
        $this->assertSame(1, Complaint::count());
        $this->assertSame(1, ComplaintMessage::count());
        $msg = ComplaintMessage::first();
        $this->assertSame($complaint->id, $msg->complaint_id);
        $this->assertSame($owner->id, $msg->user_id);
        $this->assertSame('resident', $msg->sender_type);
        $this->assertCount(1, $msg->images);
    }

    public function test_moves_resolved_complaint_to_under_review_when_resident_posts_follow_up(): void
    {
        [$department, $owner] = $this->followUpUsers();
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('complaints.follow_up.store', $complaint), [
            'body' => 'Still not fixed. Please check again.',
        ])->assertRedirect();

        $complaint->refresh();
        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->status);
        $this->assertNull($complaint->resolved_at);
    }

    public function test_moves_closed_complaint_to_under_review_when_resident_posts_follow_up(): void
    {
        [$department, $owner] = $this->followUpUsers();
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::Closed->value,
            'closed_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('complaints.follow_up.store', $complaint), [
            'body' => 'I need to reopen this with more information.',
        ])->assertRedirect();

        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->fresh()->status);
    }

    public function test_does_not_change_status_for_non_terminal_resident_follow_up(): void
    {
        [$department, $owner] = $this->followUpUsers();
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::InProgress->value,
        ]);

        $this->actingAs($owner)->post(route('complaints.follow_up.store', $complaint), [
            'body' => 'Adding more details for your review.',
        ])->assertRedirect();

        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->fresh()->status);
    }

    public function test_allows_staff_to_post_a_reply_in_the_same_thread(): void
    {
        [$department, $owner, $staff] = $this->followUpUsers();
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::InProgress->value,
        ]);

        $this->actingAs($staff)->post(route('complaints.follow_up.store', $complaint), [
            'body' => 'Please send a clearer photo of the damage.',
        ])->assertRedirect();

        $msg = ComplaintMessage::first();
        $this->assertSame('staff', $msg->sender_type);
        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->fresh()->status);
    }

    public function test_forbids_another_resident_from_posting_on_someone_else_complaint(): void
    {
        [$department, $owner, , $other] = $this->followUpUsers();
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::Resolved->value,
        ]);

        $this->actingAs($other)->post(route('complaints.follow_up.store', $complaint), [
            'body' => 'I should not be able to post this.',
        ])->assertForbidden();

        $this->assertSame(0, ComplaintMessage::count());
    }
}
