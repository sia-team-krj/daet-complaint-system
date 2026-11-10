<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Livewire\TransparencyRegister;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TransparencyPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
    }

    public function test_transparency_page_uses_approximate_public_areas_and_lists_all_public_complaints(): void
    {
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'PrivateIdentity',
            'last_name' => 'Resident',
            'email' => 'private-identity@example.test',
            'contact_number' => '09123456789',
            'barangay' => 'Barangay VI (Centro)',
            'display_alias' => 'Community neighbor',
        ]);
        $department = Department::where('code', 'ENG')->firstOrFail();

        $publicOne = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Public road safety report',
            'description' => 'A public description of a road safety concern.',
            'address_text' => 'Barangay VI, Daet, Camarines Norte',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'latitude' => 14.112234,
            'longitude' => 122.955345,
        ]);

        $publicTwo = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Public drainage follow-up',
            'description' => 'A second public report in the same broad area.',
            'address_text' => 'Barangay VI, Daet, Camarines Norte',
            'status' => ComplaintStatus::Resolved->value,
            'is_public' => true,
            'latitude' => 14.112567,
            'longitude' => 122.955789,
        ]);

        $private = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Private household report',
            'description' => 'This description must never be rendered publicly.',
            'address_text' => 'Barangay VI, Daet',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
            'latitude' => 10.123456,
            'longitude' => 20.654321,
        ]);

        $publicSpam = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Public but likely spam report',
            'description' => 'This report is public but should be excluded while moderated.',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
            'spam_status' => 'spam',
            'latitude' => 11.222222,
            'longitude' => 21.333333,
        ]);

        $response = $this->get(route('transparency'));

        $response->assertOk()
            ->assertSee($publicOne->title)
            ->assertSee($publicTwo->title)
            ->assertSee(route('transparency.complaints.show', $publicOne), false)
            // Single markup only: the register renders one row set that is
            // restyled per breakpoint, never a second mobile-only copy.
            ->assertSee('class="complaint-list"', false)
            ->assertSee($publicOne->ticket_id, false)
            ->assertSee('Recent public complaints')
            ->assertSee('Barangay VI')
            // Reporter aliases are no longer rendered on the register at all —
            // the resident's name never reaches this page.
            ->assertDontSee('Community neighbor')
            // The heat plugin is bundled rather than linked, so what matters is
            // that the page ships the bundle that contains it.
            ->assertSee('build/assets/app-', false)
            ->assertSee('14.11', false)
            ->assertDontSee($private->title)
            ->assertDontSee($publicSpam->title)
            ->assertDontSee('PrivateIdentity')
            ->assertDontSee('private-identity@example.test')
            ->assertDontSee('09123456789')
            // Exact per-report coordinates must not reach the public page in any
            // form, including the old two-decimal rounding.
            ->assertDontSee('14.11,')
            ->assertDontSee('14.112234')
            ->assertDontSee('10.123456')
            ->assertDontSee('11.222222')
            ->assertDontSee(route('complaints.track'), false);

        // The register's data now belongs to the component that re-renders it,
        // not to the controller. These assertions still describe the same thing
        // they always did — which complaints reach the public, and what the map
        // is allowed to know about them — so they follow the data rather than
        // being loosened into mere HTML matching.
        Livewire::test(TransparencyRegister::class)
            ->assertViewHas('reports', function ($reports): bool {
                return $reports->count() === 2
                    && $reports->contains('title', 'Public road safety report')
                    && $reports->contains('title', 'Public drainage follow-up');
            });

        // The heat map is aggregated to the barangay, not to a rounded pin.
        // Two reports in the same area produce ONE point carrying the barangay's
        // own reference coordinate, which is the only coordinate the browser is
        // ever handed. A per-report pin rounded to 1.1 km would have narrowed a
        // report to a ninth of the municipality.
        Livewire::test(TransparencyRegister::class)
            ->assertViewHas('mapPoints', function ($points): bool {
                if ($points->count() !== 1 || $points->first()['count'] !== 2) {
                    return false;
                }

                $point = $points->first();
                $reference = config('daet-barangays.points')[$point['barangay']] ?? null;

                return $point['barangay'] !== null
                    && $reference !== null
                    && $point['lat'] === $reference[0]
                    && $point['lng'] === $reference[1];
            });

        $this->assertSame(2, substr_count($response->getContent(), '<section'));
    }

    /**
     * The public feed's regions, for soft navigation.
     *
     * A visitor filtering or paging the feed keeps the page, the scroll
     * position and the text they typed — the changed regions are swapped in
     * instead. The endpoint must answer with the same markup the page renders,
     * and the fragments must never carry more than the page does.
     */
    public function test_the_public_feed_browser_endpoint_returns_the_swappable_regions(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $resident = User::factory()->create(['role' => 'citizen']);

        Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'A public complaint for the feed',
            'description' => 'A public description of a complaint.',
            'barangay' => 'Barangay VI (Centro)',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
        ]);

        // getJson: a script asks for fragments, a person gets the page.
        $response = $this->getJson(route('transparency.complaints.browser'));

        $response->assertOk()->assertJsonStructure(['chips', 'active', 'results']);

        $payload = $response->json();

        $this->assertStringContainsString('cf-chip', (string) $payload['chips']);
        $this->assertStringContainsString('cf-results', (string) $payload['results']);
        $this->assertStringContainsString('A public complaint for the feed', (string) $payload['results']);

        // The fragment is the public payload: no resident identity in it.
        $this->assertStringNotContainsString($resident->email, (string) $payload['results']);
    }

    /**
     * The fragment endpoint is a machine endpoint: a person who types its URL
     * gets the page, not a wall of JSON.
     */
    public function test_the_fragment_endpoint_sends_a_person_to_the_page(): void
    {
        $this->get(route('transparency.complaints.browser', ['page' => 2]))
            ->assertRedirect(route('transparency.complaints', ['page' => 2]));

        $this->get(route('transparency.complaints.browser'))
            ->assertRedirect(route('transparency.complaints'));

        // A script still gets the fragments.
        $this->getJson(route('transparency.complaints.browser'))
            ->assertOk()
            ->assertJsonStructure(['chips', 'active', 'results', 'head']);
    }

    public function test_public_complaint_detail_exposes_safe_updates_documentation_and_dates(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $resident = User::factory()->create([
            'role' => 'citizen',
            'display_alias' => 'Public neighbor',
        ]);
        $complaint = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Public drainage detail',
            'description' => 'A public description with visible resolution details at 123 Main Street, Barangay VI, Daet.',
            'address_text' => '123 Main Street, Barangay VI, Daet',
            'is_public' => true,
            'review_status' => 'verified',
            'image_path' => 'complaints/public-proof.png',
            'image_paths' => ['complaints/public-proof.png'],
            'created_at' => now()->subDays(3),
        ]);
        $log = ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $resident->id,
            'previous_status' => ComplaintStatus::InProgress->value,
            'new_status' => ComplaintStatus::Resolved->value,
            'comment' => 'The public repair update is documented.',
            'documentation_paths' => ['complaint-updates/'.$complaint->id.'/public-proof.png'],
            'is_internal' => false,
        ]);
        Storage::disk('minio')->put('complaints/public-proof.png', 'public-evidence');
        Storage::disk('minio')->put('complaint-updates/'.$complaint->id.'/public-proof.png', 'public-documentation');

        $this->get(route('transparency.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Public drainage detail')
            ->assertSee('Resolution progress')
            ->assertSee('Resident evidence')
            ->assertSee('Public updates')
            ->assertSee('The public repair update is documented.')
            ->assertSee('Exact location withheld')
            ->assertDontSee('123 Main Street')
            ->assertSee('1 Photo', false)
            ->assertSee('Public neighbor')
            ->assertSee('Barangay VI, Daet');

        $this->get(route('transparency.complaints.evidence', [$complaint, 0]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->get(route('transparency.complaints.updates.evidence', [$complaint, $log, 0]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $private = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Private detail that must stay hidden',
            'is_public' => false,
        ]);

        $this->get(route('transparency.complaints.show', $private))->assertNotFound();
        $this->get(route('transparency.complaints.evidence', [$private, 0]))->assertNotFound();
    }

    public function test_admin_navigation_does_not_show_public_transparency_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('transparency'), false);
    }

    public function test_transparency_page_explains_an_empty_public_register(): void
    {
        $response = $this->get(route('transparency'));

        $response->assertOk()
            ->assertSee('No public complaints yet')
            ->assertSee('No approximate public areas are available yet.');

        Livewire::test(TransparencyRegister::class)
            ->assertViewHas('reports', fn ($reports): bool => $reports->isEmpty())
            ->assertViewHas('mapPoints', fn ($points): bool => $points->isEmpty());
    }

    /**
     * The register must deliver new data when it is asked to re-read.
     *
     * This is the behaviour the page's poll exists for. Before the register was a
     * component it was rendered once per page load, so a complaint filed — or one
     * switched back to private — changed nothing a reader was looking at until
     * they reloaded by hand. Asserted through `$refresh` because that is exactly
     * what the poll issues, and because it fails loudly here instead of quietly
     * rendering a stale snapshot.
     */
    public function test_register_reflects_newly_public_complaints_and_drops_the_one_made_private(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $department = Department::where('code', 'ENG')->firstOrFail();

        $component = Livewire::test(TransparencyRegister::class);

        $fresh = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'Filed after the page loaded',
            'description' => 'A report that appears without a manual reload.',
            'barangay' => 'VI (Centro)',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
            'latitude' => 14.112234,
            'longitude' => 122.955345,
        ]);

        $component->call('$refresh')
            ->assertSee('Filed after the page loaded')
            // The count in the header moves with the rows, or the page contradicts itself.
            ->assertSee('Showing 1 of 1 public complaints', false);

        // Now the other half of the complaint: a report going private must stop
        // being public, which means it has to disappear from the register.
        $fresh->update(['is_public' => false]);

        $component->call('$refresh')
            ->assertDontSee('Filed after the page loaded')
            ->assertSee('No public complaints yet');
    }

    /**
     * The poll has to stay cheap enough to leave on a public page.
     *
     * A register that re-reads the database every few seconds re-runs an
     * unbounded query — every public complaint, no limit — for every visitor,
     * on a page anyone can load. So the interval carries a floor rather than
     * being left as a number somebody can drop to two while chasing something
     * that feels more responsive.
     *
     * This is not a hypothetical guard. It failed on a temporary five-second
     * value that was set deliberately, which is the only way to find out whether
     * a check like this actually works rather than merely reading as if it does.
     *
     * The second assertion is the one that earns its keep on its own: without it,
     * somebody could leave the constant alone and type a different interval
     * straight into the markup, and the constant would go on reading correctly
     * in every test while the page polled on something else entirely.
     */
    public function test_the_register_polls_at_a_defensible_interval(): void
    {
        $this->assertGreaterThanOrEqual(
            15,
            TransparencyRegister::REFRESH_SECONDS,
            'The public register polls more often than every fifteen seconds, which is a '
                .'load no public page should carry. Raise this deliberately, with the cost in mind.',
        );

        $this->assertStringContainsString(
            'wire:poll.{{ \\App\\Livewire\\TransparencyRegister::REFRESH_SECONDS }}s',
            (string) file_get_contents(resource_path('views/livewire/transparency-register.blade.php')),
            'The poll must read its interval from the constant, not hard-code one.',
        );
    }

    /**
     * The map library is bundled rather than linked from a CDN, so the guarantee
     * is not that a third-party stylesheet is requested but that the page ships
     * the compiled bundle carrying Leaflet's CSS — and that the handshake the map
     * depends on is present before the bundle, since a deferred module cannot be
     * reached by the inline script that draws the map.
     */
    public function test_authenticated_visitors_receive_the_map_styles(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('transparency'));

        $response->assertOk()->assertSee('transparency-page', false);
        $body = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<link[^>]+rel="stylesheet"[^>]+build\/assets\/app-[^"]+\.css/',
            $body,
            'The transparency page does not load the compiled stylesheet, so the map has no '
                .'Leaflet CSS and the tiles lay out wrong.',
        );

        $this->assertStringNotContainsString(
            'unpkg.com',
            $body,
            'The map library is being fetched from a CDN. A phone on a poor connection, behind '
                .'a captive portal, or with an ad blocker gets no map at all.',
        );

        $this->assertMatchesRegularExpression(
            '/<script>(?:\s|.)*?whenLeafletReady/s',
            $body,
            'The page has no Leaflet handshake, so the inline script that draws the map has '
                .'nothing to call and the map silently never appears.',
        );
    }
}
