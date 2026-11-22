<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The field ticket's four quick actions — Update Status, Take Photo,
 * Add Findings, Submit Report.
 *
 * They live in the staff bottom nav, which a field ticket replaces with the
 * four actions — the officer's four ways into the ticket, always reachable.
 * Off the ticket the tab bar is untouched. Pinned: the four anchors are the
 * ticket's own controls; the nav is replaced only in field mode; and when
 * the ticket cannot be worked the actions are replaced by an honest note
 * rather than a dead control.
 */
class StaffTicketActionsTest extends TestCase
{
    use RefreshDatabase;

    private function staffForOffice(array $overrides = []): User
    {
        $this->seed(DepartmentSeeder::class);
        $engineering = Department::where('code', 'ENG')->firstOrFail();

        return User::factory()->create(array_merge(
            ['role' => 'staff', 'department_id' => $engineering->id], $overrides,
        ));
    }

    private function ticketFor(User $staff, array $overrides = []): Complaint
    {
        $engineering = $staff->department;
        $resident = User::factory()->create(['role' => 'citizen']);

        return Complaint::factory()->create(array_merge([
            'department_id' => $engineering->id,
            'user_id' => $resident->id,
            'status' => ComplaintStatus::InProgress,
            'review_status' => ComplaintReviewStatus::Verified,
            'verified_at' => now(),
            'assigned_staff_id' => $staff->id,
        ], $overrides));
    }

    private function ticketDom(User $staff, Complaint $complaint, string $workspace): DOMDocument
    {
        $html = $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint).'?workspace='.$workspace)
            ->assertOk()
            ->getContent();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return $dom;
    }

    private function elementsWithClass(DOMDocument $dom, string $class): array
    {
        $out = [];
        foreach ($dom->getElementsByTagName('*') as $element) {
            $classes = preg_split('/\s+/', trim((string) $element->getAttribute('class'))) ?: [];
            if (in_array($class, $classes, true)) {
                $out[] = $element;
            }
        }

        return $out;
    }

    public function test_field_ticket_replaces_the_tab_bar_with_the_four_actions(): void
    {
        $staff = $this->staffForOffice();
        $complaint = $this->ticketFor($staff);
        $dom = $this->ticketDom($staff, $complaint, 'field');

        $this->assertCount(1, $this->elementsWithClass($dom, 'staff-bottom-nav'),
            'The staff bottom nav must render on a field ticket.');

        $actions = $this->elementsWithClass($dom, 'ticket-action');
        $this->assertCount(4, $actions, 'All four task actions render.');

        $this->assertEquals(
            ['#status', '#proof-capture', '#complaint-update-form', '#complaint-update-form'],
            array_map(fn ($a) => $a->getAttribute('href'), $actions),
            'Each action is one of the ticket\'s own controls, in reference order.',
        );

        // "Add Findings" leads to the official response (the response note);
        // "Submit Report" is the response form's submit button — the two
        // share the form but are not the same control.
        $this->assertSame('note', $actions[2]->getAttribute('data-ticket-focus'));
        $this->assertSame('submit', $actions[3]->getAttribute('data-ticket-focus'));
        $this->assertNull($actions[0]->getAttribute('data-ticket-focus'));
        $this->assertNull($actions[1]->getAttribute('data-ticket-focus'));

        // The default tab items are replaced by the actions, and the only
        // remaining nav item is the location tab alongside them.
        $this->assertCount(1, $this->elementsWithClass($dom, 'staff-bottom-nav__item'),
            'Only the location tab remains — the four action tiles replace the rest.');
        $this->assertContains('Complaint location', array_map(
            fn ($a) => trim($a->textContent), $this->elementsWithClass($dom, 'staff-bottom-nav__item'),
        ));
        $this->assertCount(0, $this->elementsWithClass($dom, 'field-action-bar'),
            'The sticky action bar is removed from the ticket.');
    }

    public function test_verifier_workspace_keeps_the_default_tab_bar(): void
    {
        $staff = $this->staffForOffice(['can_verify' => true]);
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = Complaint::factory()->create([
            'department_id' => $staff->department_id,
            'user_id' => $resident->id,
            'status' => ComplaintStatus::InProgress,
            'review_status' => ComplaintReviewStatus::Verified,
            'verified_at' => now(),
            'assigned_staff_id' => $staff->id,
        ]);

        $dom = $this->ticketDom($staff, $complaint, 'verifier');

        $this->assertCount(4, $this->elementsWithClass($dom, 'staff-bottom-nav__item'),
            'Verifier workspace keeps the tab bar.');
        $this->assertCount(0, $this->elementsWithClass($dom, 'ticket-action'),
            'No task actions outside the field workspace.');
        $this->assertCount(0, $this->elementsWithClass($dom, 'field-action-bar'));
    }

    public function test_verified_ticket_with_no_transition_notes_the_report_is_done(): void
    {
        $staff = $this->staffForOffice();
        $complaint = $this->ticketFor($staff, ['status' => ComplaintStatus::Resolved]);
        $dom = $this->ticketDom($staff, $complaint, 'field');

        $this->assertCount(0, $this->elementsWithClass($dom, 'ticket-action'),
            'No actions when there is nothing left to do.');
        $notes = $this->elementsWithClass($dom, 'ticket-action-note');
        $this->assertNotEmpty($notes, 'An honest note replaces the dead actions.');
        $this->assertStringContainsString('No status change is left', $notes[0]->textContent);
    }

    public function test_unverified_ticket_notes_the_form_is_not_open_yet(): void
    {
        $staff = $this->staffForOffice();
        $complaint = $this->ticketFor($staff, [
            'status' => ComplaintStatus::Submitted,
            'review_status' => ComplaintReviewStatus::Pending,
            'verified_at' => null,
        ]);
        $dom = $this->ticketDom($staff, $complaint, 'field');

        $this->assertCount(0, $this->elementsWithClass($dom, 'ticket-action'),
            'No actions while the report is still unverified.');
        $notes = $this->elementsWithClass($dom, 'ticket-action-note');
        $this->assertNotEmpty($notes, 'An honest note replaces the dead actions.');
        $this->assertStringContainsString('Waiting on verification', $notes[0]->textContent);
    }
}
