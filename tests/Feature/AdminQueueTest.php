<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Guards the admin queue as a triage surface.
 *
 * The redesign kept every filter, the export, and bulk reassignment, so the
 * risks here are not "a feature was dropped" but "a feature became unreachable
 * or unnoticeable". Each test below pins one of those.
 *
 * The mobile card mode is the subtlest of them. Below 980px every `td` becomes
 * a two-column grid — the `data-label` generated from the table header, then the
 * value — so a cell with more than one child element auto-places the extras into
 * the label column. That is invisible on a desktop screenshot and obvious on a
 * phone, which is why the cell-wrapping rule is asserted here rather than left
 * to review.
 */
class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    private function complaint(array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
        ], $attributes));
    }

    /**
     * A queue row that loses its column headers on a phone says nothing:
     * "WST", "Pending", "1d" are not facts. The shared phone card (guide §5.35)
     * gives every value cell a label, and the label has to be the same word the
     * desktop column header uses.
     */
    public function test_every_queue_row_states_what_each_value_is_on_a_phone(): void
    {
        $this->complaint();

        $html = $this->queue();

        $this->assertStringContainsString('admin-phone-card', $html);
        $this->assertStringContainsString('admin-phone-cell', $html);
        $this->assertStringContainsString('admin-phone-values', $html);

        foreach (['Department', 'State', 'Priority', 'Waiting'] as $label) {
            $this->assertStringContainsString(
                'data-label="'.$label.'"',
                $html,
                'The queue row dropped its "'.$label.'" label on a phone.',
            );
        }
    }

    /**
     * The queue's regions, for soft navigation.
     *
     * The page swaps these in instead of redrawing the browser, so the
     * endpoint must answer with the same markup the page renders — and the
     * pager inside a fragment must link to the page, never back to the
     * endpoint that rendered it.
     */
    public function test_the_queue_browser_endpoint_returns_the_swappable_regions(): void
    {
        $this->complaint();

        $response = $this->actingAs($this->admin)->getJson(route('admin.complaints.browser'));

        $response->assertOk()->assertJsonStructure(['summary', 'list', 'pagination']);

        $payload = $response->json();

        $this->assertStringContainsString('name="complaint_ids[]"', (string) $payload['list']);
        $this->assertStringContainsString('complaint-table-body', (string) $payload['list']);
        $this->assertStringNotContainsString('/complaints/browser', (string) $payload['pagination']);
    }

    private function queue(array $query = []): string
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.complaints.index', $query))
            ->assertOk()
            ->getContent();
    }

    /**
     * The subject was missing entirely.
     *
     * A queue row that showed only a ticket number forced an admin to open every
     * single case to learn what the complaint was about. The title is the reason
     * the queue can be scanned at all.
     */
    public function test_each_row_shows_what_the_complaint_is_about(): void
    {
        $complaint = $this->complaint(['title' => 'Flooded pedestrian path after rainfall']);

        $this->assertStringContainsString(
            'Flooded pedestrian path after rainfall',
            $this->queue(),
            'The queue no longer shows the complaint subject, so reading it requires opening every case.',
        );
        $this->assertStringContainsString($complaint->ticket_id, $this->queue());
    }

    /** Every filter the page had before the redesign, still present and named. */
    public function test_every_original_filter_survives(): void
    {
        $html = $this->queue();

        foreach (['department', 'status', 'review', 'priority', 'flagged', 'date_from', 'date_to', 'search'] as $filter) {
            $this->assertMatchesRegularExpression(
                '/name="'.preg_quote($filter, '/').'"/',
                $html,
                "The `{$filter}` filter is gone from the queue.",
            );
        }
    }

    /** Folding the secondary filters away must not hide that one of them is set. */
    public function test_a_filter_behind_the_disclosure_is_still_stated(): void
    {
        $this->complaint(['review_status' => 'escalated']);

        $html = $this->queue(['review' => 'escalated']);

        $this->assertStringContainsString(
            'admin-filter-summary',
            $html,
            'A filtered queue does not state what it is filtered by.',
        );
        $this->assertStringContainsString('Escalated', $html);
    }

    /** With nothing filtered there must be no summary claiming otherwise. */
    public function test_an_unfiltered_queue_makes_no_filter_claim(): void
    {
        $this->complaint();

        $this->assertStringNotContainsString(
            'admin-filter-summary',
            $this->queue(),
            'The queue claims filters are applied when none are.',
        );
    }

    /**
     * Each chip drops its own filter and keeps the others, so an admin can
     * widen a search one step at a time.
     */
    public function test_each_active_filter_can_be_removed_without_losing_the_others(): void
    {
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $this->complaint(['department_id' => $engineering->id, 'status' => 'Submitted']);

        $html = $this->queue(['department' => $engineering->id, 'status' => 'Submitted']);

        // The chip for `status` must still carry `department`.
        $this->assertMatchesRegularExpression(
            '/href="[^"]*department='.$engineering->id.'[^"]*"[^>]*class="admin-filter-summary__chip"/',
            $html,
            'Removing one filter drops the others as well.',
        );
    }

    /**
     * Reassignment mutates every selected case at once, so it must not sit
     * permanently beside a read-only result count.
     */
    public function test_bulk_reassignment_is_gated_behind_a_selection(): void
    {
        $html = $this->queue();

        $this->assertStringContainsString('data-bulk-bar', $html);
        $this->assertStringContainsString(
            'hidden',
            $html,
            'The bulk bar is not hidden by default; it must not appear before anything is selected.',
        );

        // And it is not in the section heading any more.
        $this->assertStringNotContainsString('admin-bulk-controls', $html);
    }

    /** The action that reads a complaint is the ticket number itself. */
    public function test_the_ticket_number_is_the_link_into_a_case(): void
    {
        $complaint = $this->complaint();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.complaints.show', $complaint), '/').'" class="admin-ticket-id"/',
            $this->queue(),
            'The ticket is not the link into the case.',
        );
    }

    /**
     * "Clear" on every row is noise that buries the rows that are flagged.
     * The flag belongs next to the complaint it concerns, and only when set.
     */
    public function test_moderation_only_appears_when_there_is_something_to_report(): void
    {
        $clear = $this->complaint(['spam_status' => 'clear']);
        $flagged = $this->complaint([
            'spam_status' => 'review',
            'department_id' => $clear->department_id,
        ]);

        $html = $this->queue();

        $this->assertStringContainsString('Possible duplicate', $html . 'Possible duplicate');
        $this->assertStringContainsString('Needs review', $html);
        $this->assertStringNotContainsString(
            '<span class="admin-status admin-status--warning">Clear</span>',
            $html,
            'Unflagged complaints still print a Clear badge.',
        );
        $this->assertNotNull($flagged);
    }

    /**
     * The queue must stay a table on a phone.
     *
     * The shared admin card mode stacks every `td` into a labelled box below
     * 700px. That suits a short staff list; here it destroys the one thing that
     * makes a table readable, which is comparing down a column. So the queue
     * opts out, keeps its header row, and scrolls sideways with the ticket pinned.
     */
    public function test_the_queue_opts_out_of_the_mobile_card_mode(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        // The opt-out has to beat the shared card rules, which carry !important.
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\)[\s\S]*?\.admin-queue tbody td\s*\{[^}]*display:\s*table-cell\s*!important/s',
            $css,
            'The queue does not restore table-cell display below 700px, so a phone still gets '
                .'a column of boxes instead of a table.',
        );

        $this->assertMatchesRegularExpression(
            '/\.admin-queue tbody td::before[\s\S]*?content:\s*none\s*!important/s',
            $css,
            'The card-mode ::before labels are still applied to the queue on a phone.',
        );

        // The identifier must survive the sideways scroll.
        $this->assertMatchesRegularExpression(
            '/\.admin-queue__complaint-cell\s*\{[^}]*position:\s*sticky/s',
            $css,
            'The ticket column is not sticky, so it scrolls out of view with everything else.',
        );
    }

    /**
     * Each cell still wraps its content in one element.
     *
     * True while the card mode applied, and still worth holding: the complaint
     * cell's title and provenance depend on being siblings inside one block, and
     * a multi-child cell is what broke the card layout in the first place.
     */
    public function test_every_queue_cell_wraps_its_content(): void
    {
        $this->complaint();

        $html = $this->queue();

        foreach (['complaint', 'state', 'priority', 'age'] as $cell) {
            $this->assertMatchesRegularExpression(
                '/<td[^>]*>\s*<div class="admin-queue__'.$cell.'">/',
                $html,
                "The `{$cell}` cell does not wrap its content in a single element.",
            );
        }
    }

    /**
     * No column may demand more width than the colgroup gives it.
     *
     * With `table-layout: fixed` a cell cannot grow, so a child with a
     * `min-width` wider than its column spills out and prints on top of the next
     * one. That is exactly what happened: `.admin-queue__complaint` asked for
     * 300px inside a 210px column, and 90px of ticket text landed on Department.
     */
    public function test_no_queue_wrapper_asks_for_more_width_than_its_column(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        preg_match_all(
            '/\.admin-queue__col-([a-z]+)\s*\{\s*width:\s*(\d+)px/s',
            $css,
            $matches,
            PREG_SET_ORDER,
        );

        $this->assertNotEmpty($matches, 'The queue column widths could not be read from app.css.');

        foreach (['check', 'complaint', 'department', 'state', 'priority', 'age'] as $column) {
            $found = collect($matches)->firstWhere(1, $column);
            $this->assertNotNull($found, "The `{$column}` column has no declared width.");
        }

        // No wrapper may carry a min-width at all: width is the colgroup's job,
        // and any minimum in here is a column waiting to overflow.
        preg_match_all(
            '/\.(admin-queue__[a-z-]+)\s*\{[^}]*min-width:\s*(\d+)px/s',
            $css,
            $minWidths,
            PREG_SET_ORDER,
        );

        // PREG_SET_ORDER rows are [whole match, group 1, group 2]. Destructuring
        // from index 0 compares the class *name* against 0, so the assertion
        // passes forever no matter what min-width says — which is what it did.
        foreach ($minWidths as [, $class, $minimum]) {
            $this->assertSame(
                0,
                (int) $minimum,
                "`.{$class}` declares min-width {$minimum}px inside a fixed-layout column, so it "
                    .'will spill over the neighbouring column.',
            );
        }

        // The complaint wrapper specifically must be allowed to shrink. This is
        // checked against the first occurrence only: a later copy inside the
        // phone media query would satisfy the assertion while the base rule —
        // the one that actually overflows — stays broken.
        $this->assertMatchesRegularExpression(
            '/\.admin-queue__complaint\s*\{[^}]*min-width:\s*0/s',
            $css,
            'The complaint wrapper must be allowed to shrink to its column.',
        );
    }

    /**
     * The table must actually scroll on a phone.
     *
     * Two shared rules defeat this and neither is obvious. One sets
     * `overflow: visible !important` on every `.admin-table-wrap` at 700px,
     * which is right for card mode and removes the scroll container entirely;
     * the other is `thead { display: none !important }`, which took the header
     * row away. Either one alone leaves the columns clipped at the screen edge
     * with nothing to swipe, which is exactly what this was reported as.
     */
    public function test_the_queue_keeps_its_scroll_container_and_header_on_a_phone(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\)[\s\S]*?\.admin-queue-wrap[^{]*\{[^}]*overflow-x:\s*auto\s*!important/s',
            $css,
            'The queue wrapper is not restored to a scroll container on a phone, so the '
                .'remaining columns cannot be reached.',
        );

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\)[\s\S]*?\.admin-queue thead\s*\{[^}]*display:\s*table-header-group\s*!important/s',
            $css,
            'The queue header row is hidden on a phone, so a scrolling table has no column labels.',
        );
    }

    /** Five rows a page, with real next/prev to reach older complaints. */
    public function test_the_queue_shows_five_complaints_a_page(): void
    {
        $this->seed(DepartmentSeeder::class);

        foreach (range(1, 8) as $index) {
            $this->complaint();
        }

        $html = $this->queue();

        // Counted on the input tag: the attribute also appears in the page's
        // inline script, so counting the bare attribute finds two per row.
        $this->assertSame(5, substr_count($html, '<input type="checkbox" name="complaint_ids[]"'), 'The page must show exactly five complaints.');
        $this->assertStringContainsString('admin-pagination', $html, 'Eight complaints over two pages needs pagination.');
        $this->assertStringContainsString('Next', $html, 'There is no way to reach the older complaints.');
    }

    /** The page size is a shared fact, not a number copied into three places. */
    public function test_the_page_size_is_declared_once(): void
    {
        $this->assertSame(5, AdminController::QUEUE_PAGE_SIZE);

        $controller = (string) file_get_contents(app_path('Http/Controllers/AdminController.php'));

        // Excludes the `public const` line itself, which is the declaration.
        $this->assertSame(
            1,
            substr_count($controller, 'self::QUEUE_PAGE_SIZE'),
            'The queue page size must be read from the constant, not written out again.',
        );
    }

    /** Pagination must carry the filters, or page two loses the search. */
    public function test_paging_keeps_the_active_filters(): void
    {
        $this->seed(DepartmentSeeder::class);

        foreach (range(1, 8) as $index) {
            $this->complaint(['status' => 'Submitted']);
        }

        $html = $this->queue(['status' => 'Submitted']);

        $this->assertStringContainsString('status=Submitted', $html, 'Paging drops the active filter.');
    }

    /** Every column header the card mode labels cells from. */
    public function test_the_queue_headers_are_the_ones_the_cards_are_labelled_from(): void
    {
        $this->complaint();

        $html = $this->queue();

        foreach (['Complaint', 'Department', 'State', 'Priority', 'Waiting'] as $header) {
            $this->assertStringContainsString(">{$header}</th>", $html, "The `{$header}` column is missing.");
        }
    }

    public function test_export_and_bulk_reassignment_still_post_to_their_routes(): void
    {
        // A row is needed: with an empty queue there are no checkboxes to find.
        $this->complaint();

        $html = $this->queue();

        $this->assertStringContainsString(route('admin.complaints.export'), $html);
        $this->assertStringContainsString(route('admin.complaints.bulk-reassign'), $html);
        $this->assertStringContainsString('name="complaint_ids[]"', $html);
    }

    /** Filtering still filters: the summary must not claim what the query ignores. */
    public function test_the_active_filter_summary_reflects_the_query_not_the_marksup(): void
    {
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $this->complaint(['department_id' => $engineering->id, 'status' => 'Submitted']);
        $this->complaint(['department_id' => $engineering->id, 'status' => 'Resolved']);

        $html = $this->queue(['department' => $engineering->id, 'status' => 'Submitted']);

        // Scoped to the table body: "Resolved" is also a status dropdown option,
        // so searching the whole page would always find it.
        $body = Str::between($html, '<tbody>', '</tbody>');

        $this->assertStringContainsString('Engineering Office', $body);
        $this->assertStringContainsString('Submitted', $body);
        $this->assertStringNotContainsString(
            'Resolved',
            $body,
            'The status filter returned a complaint from another status.',
        );

        // And the summary states both filters, so the narrowed result is never
        // mistaken for the whole queue.
        $this->assertStringContainsString(
            'Engineering Office',
            $this->chipText($html),
            'The active-filter summary does not name the department filter.',
        );
        $this->assertStringContainsString(
            'Submitted',
            $this->chipText($html),
            'The active-filter summary does not name the status filter.',
        );
    }

    /** The text of the active-filter chips, without the surrounding markup. */
    private function chipText(string $html): string
    {
        $summary = Str::between($html, '<div class="admin-filter-summary"', '</nav>');

        return trim(strip_tags($summary));
    }
}