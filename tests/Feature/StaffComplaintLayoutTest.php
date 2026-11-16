<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use DOMDocument;
use DOMElement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The staff complaint ticket is one grid with two columns. A stray closing
 * tag once pushed the right-hand column out of that grid, so the "right"
 * cards rendered below the complaint column at full width while the grid
 * itself stayed narrow — the page looked unbalanced at every size. These
 * tests read the parsed document (not the source) so that cannot happen
 * again: an unbalanced tag changes the tree the browser builds.
 */
class StaffComplaintLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(User $staff): Complaint
    {
        $this->seed(DepartmentSeeder::class);
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $staff->update(['department_id' => $engineering->id]);

        $resident = User::factory()->create(['role' => 'citizen']);

        return Complaint::factory()->create([
            'department_id' => $engineering->id,
            'user_id' => $resident->id,
            'status' => ComplaintStatus::InProgress,
            'review_status' => ComplaintReviewStatus::Verified,
            'verified_at' => now(),
            // An ordinary staff member works the cases the office hands them.
            'assigned_staff_id' => $staff->id,
        ]);
    }

    private function ticketDom(User $staff, Complaint $complaint): DOMDocument
    {
        $html = $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->getContent();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return $dom;
    }

    private function hasClass(DOMElement $element, string $class): bool
    {
        $classes = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];

        return in_array($class, $classes, true);
    }

    private function elementWithClass(DOMDocument $dom, string $class): ?DOMElement
    {
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($this->hasClass($div, $class)) {
                return $div;
            }
        }

        return null;
    }

    private function closestWithClass(DOMElement $element, string $class): ?DOMElement
    {
        $node = $element->parentNode;

        while ($node instanceof DOMElement) {
            if ($this->hasClass($node, $class)) {
                return $node;
            }
            $node = $node->parentNode;
        }

        return null;
    }

    public function test_the_detail_grid_holds_exactly_the_two_columns(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $complaint = $this->ticketFor($staff);
        $dom = $this->ticketDom($staff, $complaint);

        $grid = $this->elementWithClass($dom, 'staff-detail-grid');
        $this->assertNotNull($grid, 'The complaint detail grid is missing.');

        $children = [];
        foreach ($grid->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        $this->assertCount(2, $children, 'The grid must hold exactly the two columns.');
        $this->assertTrue($this->hasClass($children[0], 'staff-detail-col--main'));
        $this->assertTrue($this->hasClass($children[1], 'staff-detail-col--side'));
    }

    public function test_the_official_response_card_lives_in_the_left_column(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $complaint = $this->ticketFor($staff);
        $dom = $this->ticketDom($staff, $complaint);

        $response = $this->elementWithClass($dom, 'staff-detail-card--response');
        $this->assertNotNull($response, 'The official response card is missing.');

        $this->assertNotNull(
            $this->closestWithClass($response, 'staff-detail-col--main'),
            'The official response card must sit in the left (complaint) column.'
        );
        $this->assertNull(
            $this->closestWithClass($response, 'staff-detail-col--side'),
            'The official response card must not sit in the right column.'
        );
    }

    public function test_every_card_carries_its_responsive_order_slot(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $complaint = $this->ticketFor($staff);
        $dom = $this->ticketDom($staff, $complaint);

        foreach ([
            'staff-detail-card--resident',
            'staff-detail-card--location',
            'staff-detail-card--description',
            'staff-detail-card--timeline',
            'staff-detail-card--review',
            'staff-detail-card--response',
        ] as $slot) {
            $this->assertNotNull(
                $this->elementWithClass($dom, $slot),
                "Missing responsive slot {$slot}."
            );
        }

        // The single-column rules are in the page stylesheet.
        $html = $this->ticketDom($staff, $complaint)->saveHTML();
        $this->assertStringContainsString('display: contents', $html);
        $this->assertStringContainsString('grid-column: 1 / -1', $html);
    }
}
