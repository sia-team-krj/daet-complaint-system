<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Three audiences, three different amounts of location, and the boundaries
 * between them are not negotiable.
 *
 *   - Whoever is dispatched to fix the problem needs the exact position. A
 *     barangay is a division of a small municipality, so the address, the pin
 *     and the coordinates are all of it. Without them the work cannot be done,
 *     and a field worker whose phone has no signal needs digits to type in, not
 *     a marker to look at.
 *   - The resident needs to see exactly what they filed, so they can recognise
 *     it and object if it is wrong.
 *   - The public gets the barangay and nothing finer. A heat map and a public
 *     feed are what make a government accountable, but the moment a public page
 *     pins an individual report to a road it stops being a map of problems and
 *     becomes a list of the people who complained, in the places they live.
 *
 * These boundaries were wrong twice, so each is pinned here. The public map
 * rounded each complaint's own coordinate to two decimal places and plotted
 * that, which sounds coarse but is about 1.1 km — a ninth of a municipality
 * spanning roughly 10 km, and for the smaller barangays the whole barangay with
 * a road attached. And the filing form wrote the coordinate into the free-text
 * address field, so the address a dispatcher read was not an address, and the
 * privacy scrub that works by stripping the address from the public copy had
 * nothing to strip.
 */
class ExactLocationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Inside Gahonon, but deliberately not on Gahonon's reference point
     * (14.12373, 122.94668) and not the town centre the public map opens on
     * (14.1122, 122.9553). If the complaint's own digits are the only thing that
     * makes a page fail these tests, then the tests have real power — otherwise
     * a coincidental match with the map centre would pass a leak.
     */
    private const LAT = 14.126400;

    private const LNG = 122.943200;

    private const BARANGAY = 'Gahonon';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);

        // Preconditions the whole class relies on, asserted once so a change to
        // the reference table cannot quietly neuter the tests below.
        $this->assertNotSame(
            [self::LAT, self::LNG],
            config('daet-barangays.points')[self::BARANGAY] ?? null,
            'The fixture coordinate must not sit on a barangay reference point.',
        );
    }

    // ── the people doing the work ──────────────────────────────────────

    public function test_staff_see_the_exact_position(): void
    {
        [$complaint, , $staff] = $this->complaint();

        $body = $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Complaint Location')
            ->getContent();

        // Scoped to the coordinate readout, not the whole page. A pin on a map
        // cannot be typed into a handheld GPS, so the figures have to be there
        // as readable text — and a `hidden` element is not readable, however
        // much of it is in the markup.
        $readout = $this->slice($body, 'complaint-coords');

        $this->assertStringContainsString($this->formatted(self::LAT), $readout);
        $this->assertStringContainsString($this->formatted(self::LNG), $readout);
        $this->assertStringNotContainsStringIgnoringCase(
            'hidden',
            $readout,
            'The coordinates are in the markup but hidden from the field worker.',
        );
    }

    public function test_admins_see_the_exact_position(): void
    {
        [$complaint] = $this->complaint();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Exact coordinates')
            ->assertSee($this->formatted(self::LAT), false)
            ->assertSee($this->formatted(self::LNG), false);
    }

    // ── the resident's own copy ────────────────────────────────────────

    public function test_the_resident_sees_the_exact_position_they_filed(): void
    {
        [$complaint, $resident] = $this->complaint();

        // Their own report, at full precision. They need to be able to say "that
        // is not what I filed", which they cannot do if the page only offers a
        // barangay they already know.
        $body = $this->actingAs($resident)
            ->get(route('complaints.show', $complaint))
            ->assertOk()
            ->getContent();

        // The card is <div class="detail-card"><div class="detail-card-header">…
        // <span class="detail-card-title">Location</span>…</div><div
        // class="detail-card-body">…coordinates…</div></div>, so the marker sits
        // inside the header. Slicing from the card that contains the *body*
        // picks up the whole thing, header included.
        $card = $this->sliceContaining(
            $body,
            'detail-card',
            'detail-card-title">Location<',
        );

        $this->assertStringContainsString(
            'detail-card-body',
            $card,
            'The location card was sliced to its header, which cannot hold the coordinates.',
        );

        $this->assertStringContainsString($this->formatted(self::LAT), $card);
        $this->assertStringContainsString($this->formatted(self::LNG), $card);
    }

    // ── the public ─────────────────────────────────────────────────────

    public function test_the_public_transparency_page_names_the_barangay_and_not_the_position(): void
    {
        [$complaint] = $this->complaint();

        $body = $this->get(route('transparency'))->assertOk()->getContent();

        $this->assertPublicPageOmits($body);
        $this->assertStringContainsString(self::BARANGAY, $body);
    }

    public function test_the_public_complaint_page_names_the_barangay_and_not_the_position(): void
    {
        [$complaint] = $this->complaint();

        $body = $this->get(route('transparency.complaints.show', $complaint))
            ->assertOk()
            ->getContent();

        $this->assertPublicPageOmits($body);
        $this->assertStringContainsString(self::BARANGAY, $body);
    }

    /**
     * The heat map is the one place the public page receives a coordinate, so it
     * is checked structurally against the bundled table rather than by eye: a
     * marker is only allowed to sit exactly where a barangay's own reference
     * point sits, which is a shared landmark rather than anyone's report.
     */
    public function test_the_public_heat_map_only_marks_barangay_reference_points(): void
    {
        [$complaint] = $this->complaint();

        $body = $this->get(route('transparency'))->assertOk()->getContent();

        // The payload rides on a data attribute rather than a
        // <script type="application/json"> island. That island had to go when
        // the register became a Livewire component: Livewire treats a script tag
        // it finds inside a component as code to evaluate, and this is JSON, not
        // code. What it carries is unchanged, and the privacy assertions below
        // are the point of this test, so they are applied to the new carrier
        // exactly as they were to the old one.
        $matched = preg_match(
            '/id="transparency-map-data"[^>]*\sdata-points="([^"]*)"/s',
            $body,
            $matches,
        );
        $this->assertSame(1, $matched, 'The map payload was not rendered.');

        // Blade escapes attribute output, so the JSON arrives with its quotes as
        // entities. It has to be decoded before it can be parsed.
        $payload = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertIsArray($payload, 'The map payload was not valid JSON.');

        $reference = config('daet-barangays.points', []);
        $this->assertNotEmpty($reference, 'The barangay reference table is missing.');

        foreach ($payload['points'] ?? [] as $point) {
            $expected = $reference[$point['barangay']] ?? null;

            $this->assertNotNull(
                $expected,
                "The map plotted a point for an unknown barangay: {$point['barangay']}",
            );

            $this->assertSame(
                [$expected[0], $expected[1]],
                [$point['lat'], $point['lng']],
                "The map plotted {$point['barangay']} away from its own reference point, which "
                    .'means it is showing where a report is rather than where the barangay is.',
            );
        }
    }

    // ── the form ───────────────────────────────────────────────────────

    /**
     * The filing form must not write coordinates into the free-text address
     * field. Asserted against the template because the behaviour is inline
     * JavaScript that a request test cannot run — but the template is what
     * decides it, so that is where the guarantee lives.
     */
    public function test_the_filing_form_never_writes_coordinates_into_the_address_field(): void
    {
        $source = $this->createSource();

        foreach (['lat', 'long', 'toFixed'] as $hint) {
            $this->assertDoesNotMatchRegularExpression(
                '/addressInput\s*\.\s*value\s*=[^;]*' . $hint . '/i',
                $source,
                "The address field is being filled with a coordinate (matched on '{$hint}').",
            );
        }
    }

    /**
     * The "Ready to submit" summary is the resident's last look at what they are
     * about to send, so it reports the exact position. Someone who only tapped
     * the map and typed no address must still see that a location registered —
     * otherwise a silently dropped pin looks like a successful submission.
     */
    /**
     * The exact position still goes into the form.
     *
     * The "Ready to submit" row deliberately does *not* show it — a resident
     * cannot confirm a pair of decimal degrees, so it names the barangay their
     * point falls in instead. That display rule is exercised end to end by
     * tests/Support/js/filing-summary-audit.mjs, which drives the real form.
     * What is asserted here is the part a display test cannot see: that the
     * hidden fields are still populated at full precision, because the
     * department is given those numbers and nothing else.
     */
    public function test_the_exact_position_is_still_submitted_even_though_the_row_names_a_place(): void
    {
        $source = $this->createSource();

        $this->assertMatchesRegularExpression(
            '/latitudeInput\.value\s*=\s*latitude\.toFixed\(8\)/',
            $source,
            'The hidden latitude field is no longer written at full precision, so the '
                .'department would lose the exact position.',
        );

        $this->assertMatchesRegularExpression(
            '/longitudeInput\.value\s*=\s*longitude\.toFixed\(8\)/',
            $source,
            'The hidden longitude field is no longer written at full precision.',
        );

        // The row names a place the resident chose, not one derived from the
        // pin. A single reference point per barangay is wrong across most of a
        // large barangay, and Daet is only 10 km across, so a pin-derived name
        // showed residents the wrong area for their own home.
        $this->assertStringContainsString(
            "const barangay = barangayInput?.value?.trim()",
            $source,
            'The summary no longer shows the barangay the resident named, so a resident '
                .'cannot read their own report back to themselves before sending it.',
        );

        $this->assertStringNotContainsString(
            'nearestBarangay',
            $source,
            'The form derives a barangay from the pin again. That is the inaccurate behaviour '
                .'this replaced, and it is what residents were being shown.',
        );
    }

    // ── helpers ────────────────────────────────────────────────────────

    private function createSource(): string
    {
        $source = file_get_contents(resource_path('views/complaints/create.blade.php'));

        $this->assertIsString($source, 'Could not read the filing form template.');

        return $source;
    }

    /**
     * The complaint's own position must not appear on a public page.
     *
     * Checked by pulling every coordinate out of the page and demanding that
     * each one is a known landmark, rather than by searching for the complaint's
     * digits. Searching for digits cannot work here: Daet spans about 0.05
     * degrees of latitude, so a two-decimal prefix of the complaint is a prefix
     * of some barangay reference point regardless of where the complaint is, and
     * the check either fails on a legitimate landmark or passes on a real leak.
     * Asking "is this number a landmark we recognise" has no such ambiguity.
     */
    private function assertPublicPageOmits(string $body): void
    {
        preg_match_all('/\b1(?:4|22)\.\d{3,}\b/', $body, $found);

        $landmarks = $this->landmarks();

        foreach (array_unique($found[0]) as $number) {
            $this->assertContains(
                $number,
                $landmarks,
                "The public page contains {$number}, which is not a barangay reference point or "
                    .'the map centre. Only landmarks may be published.',
            );
        }

        // And the precise position the resident filed is nowhere in the page.
        $this->assertStringNotContainsString(
            $this->formatted(self::LAT),
            $body,
            'The public page contains the complaint\'s exact latitude.',
        );
        $this->assertStringNotContainsString(
            $this->formatted(self::LNG),
            $body,
            'The public page contains the complaint\'s exact longitude.',
        );
    }

    /**
     * Every coordinate the public pages are allowed to contain: the reference
     * point of each barangay, plus the town centre the public map opens on.
     */
    private function landmarks(): array
    {
        $allowed = [];

        foreach (config('daet-barangays.points', []) as [$lat, $lng]) {
            $allowed[] = (string) $lat;
            $allowed[] = (string) $lng;
        }

        // The map viewport centre, which is Daet's town centre — a public
        // landmark, not anybody's address.
        $allowed[] = '14.1122';
        $allowed[] = '122.9553';

        return $allowed;
    }

    private function formatted(float $value): string
    {
        return number_format($value, 6);
    }

    /**
     * The markup of the element carrying a class that contains a given marker.
     *
     * The resident's page has sixteen `detail-card` elements, only one of which
     * is the location, so slicing to the first match would read whichever card
     * happened to come first and prove nothing about location.
     */
    private function sliceContaining(string $html, string $class, string $marker): string
    {
        $markerAt = strpos($html, $marker);
        $this->assertNotFalse($markerAt, "The marker '{$marker}' was not rendered.");

        // The nearest opening tag of this class before the marker.
        $pattern = $this->classPattern($class);

        preg_match_all($pattern, substr($html, 0, $markerAt), $opens, PREG_OFFSET_CAPTURE);
        $this->assertNotEmpty($opens[0], "No element with the class '{$class}' precedes the marker.");

        $last = end($opens[0]);
        $from = $last[1];
        $name = $opens[1][count($opens[1]) - 1][0];

        return $this->elementAt($html, $name, $from);
    }

    /**
     * A pattern for an opening tag whose class attribute contains a whole token.
     *
     * A plain \b boundary is not enough: in "detail-card-header" the position
     * after "card" is a boundary, because "-" is not a word character, so a
     * search for the class "detail-card" also matches every "detail-card-header"
     * on the page. Tokens are compared inside the attribute instead.
     */
    private function classPattern(string $class): string
    {
        $token = preg_quote($class, '/');

        // Either the class is the whole attribute, or it sits between two
        // characters that cannot be part of a class name.
        $attribute = 'class="(?:[^"]*[\s\'"])?'
            .$token
            .'(?![\w-])(?:[\s\'"][^"]*)?"';

        return '/<(\w+)[^>]*'.$attribute.'/';
    }

    /**
     * The markup of the first element carrying a class, up to the end of the
     * block it opens.
     *
     * Scoping assertions to a region rather than the whole page is what makes
     * them meaningful. The complaint page prints the resident's coordinates in
     * more than one place, so a page-wide search for the digits passes even
     * after the location card has been reduced to a barangay.
     */
    private function slice(string $html, string $class): string
    {
        $tag = preg_match($this->classPattern($class), $html, $open);
        $this->assertSame(1, $tag, "No element with the class '{$class}' was rendered.");

        $from = strpos($html, $open[0]);

        return $this->elementAt($html, $open[1], $from);
    }

    /**
     * The element of a given name starting at an offset, through to the tag that
     * closes it. Tags are counted rather than searched for, so the block ends
     * where the element does and not at the first closing tag of any kind.
     */
    private function elementAt(string $html, string $name, int $from): string
    {
        $depth = 0;
        $offset = $from;

        while ($offset < strlen($html)) {
            $next = preg_match('/<(\/?)' . $name . '\b[^>]*>/i', $html, $tagMatch, PREG_OFFSET_CAPTURE, $offset);

            if ($next !== 1) {
                break;
            }

            $isClose = $tagMatch[1][0] === '/';
            $offset = $tagMatch[0][1] + strlen($tagMatch[0][0]);
            $depth += $isClose ? -1 : 1;

            if ($depth === 0) {
                return substr($html, $from, $offset - $from);
            }
        }

        return substr($html, $from);
    }

    private function complaint(): array
    {
        $department = Department::where('code', 'ENG')->firstOrFail();

        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);

        // The resident's own barangay matches where the pin is, so the public
        // page's "which barangay" answer is consistent whichever source it uses.
        $resident = User::factory()->create([
            'role' => 'citizen',
            'barangay' => self::BARANGAY,
        ]);

        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'user_id' => $resident->id,
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'address_text' => 'In front of Daet Public Market',
            'is_public' => true,
        ]);

        return [$complaint, $resident, $staff];
    }
}
