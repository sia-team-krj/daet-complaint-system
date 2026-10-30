<?php

namespace Tests\Feature;

use App\Livewire\TransparencyRegister;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The barangay a complaint is about is the one the resident said.
 *
 * This replaced a guess. The only Daet-specific data available offline is a
 * single label point per barangay — OpenStreetMap has no boundary polygons for
 * any of the 25 — so a complaint was named by finding the nearest of those 25
 * points. That is wrong whenever a point sits further from its own barangay's
 * label than from a neighbour's, which is most of the area of a large barangay,
 * and Daet spans only about 10 km. Residents were shown the wrong area for their
 * own home, on the form and on the public page.
 *
 * A centroid cannot be fixed; there is nothing to be more accurate with. So the
 * form asks, prefilled from what the resident already gave at registration, and
 * that answer is what is stored and published. The coordinate is untouched and
 * remains the exact position the department is given.
 */
class ComplaintBarangayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_a_filed_complaint_records_the_barangay_the_resident_chose(): void
    {
        $complaint = $this->file(user: $this->resident('Borabod'), fields: [
            'barangay' => 'Gahonon',
        ]);

        $this->assertSame('Gahonon', $complaint->barangay);
    }

    /**
     * A resident can be anywhere while reporting a problem somewhere else, so
     * what they choose on the form wins over where they live. Getting this
     * backwards would file a report about a flooded street in their own
     * barangay.
     */
    public function test_the_chosen_barangay_beats_the_one_on_their_account(): void
    {
        $complaint = $this->file(user: $this->resident('Borabod'), fields: [
            'barangay' => 'Dogongan',
        ]);

        $this->assertSame('Dogongan', $complaint->barangay);
        $this->assertNotSame(
            $complaint->user->barangay,
            $complaint->barangay,
            'The complaint took the barangay from the account rather than the form.',
        );
    }

    /**
     * A form that predates the field — a bookmarked page, an old client — still
     * files, and files under the resident's own stated barangay rather than a
     * guess. Null must never mean "we inferred one".
     */
    public function test_a_form_without_a_barangay_falls_back_to_the_residents_own(): void
    {
        $complaint = $this->file(user: $this->resident('Mancruz'), fields: [
            'barangay' => null,
        ]);

        $this->assertSame('Mancruz', $complaint->barangay);
    }

    /**
     * Without this the column is free text that ends up on a public page, and an
     * invented or hostile value is published as a place name.
     */
    public function test_a_barangay_that_is_not_a_real_one_is_rejected(): void
    {
        $user = $this->resident('Borabod');

        $this->filing($user, ['barangay' => 'Barangay Who Dis'])
            ->assertSessionHasErrors('barangay');

        $this->assertSame(0, Complaint::count(), 'A complaint was filed with an invented barangay.');
    }

    public function test_the_form_asks_and_prefills_from_the_account(): void
    {
        $this->actingAs($this->resident('Pamorangon'))
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('name="barangay"', false)
            ->assertSee('value="Pamorangon"', false);
    }

    /**
     * The pin must not decide. Two complaints at the same coordinate, each
     * filed for a different barangay, have to stay in those two barangays —
     * which is precisely what a nearest-reference-point lookup cannot do.
     */
    public function test_the_same_coordinate_can_belong_to_two_different_barangays(): void
    {
        $pin = ['latitude' => 14.1264, 'longitude' => 122.9432];

        // Distinct wording, because two identical reports are duplicates by the
        // fingerprint and a duplicate is kept off the public feed — which would
        // quietly reduce this to a single point and prove nothing.
        $g = $this->file(user: $this->resident('Borabod'), fields: $pin + [
            'barangay' => 'Gahonon',
            'visibility' => 'public',
            'title' => 'Flooded drain on the Gahonon road',
            'description' => 'The drain has backed up and the whole verge is under water.',
        ]);

        $c = $this->file(user: $this->resident('Borabod'), fields: $pin + [
            'barangay' => 'Calasgasan',
            'visibility' => 'public',
            'title' => 'Collapsed barangay fence in Calasgasan',
            'description' => 'A section of the perimeter fence came down in the wind.',
        ]);

        $this->assertTrue($g->isPubliclyVisible(), 'The first complaint is not on the public feed.');
        $this->assertTrue($c->isPubliclyVisible(), 'The second complaint is not on the public feed.');

        // The page itself renders no data now; the register is a component that
        // re-reads the database on a timer. The assertion is unchanged — same
        // two barangays, same expectation — it just follows the data to where it
        // moved rather than being dropped.
        Livewire::test(TransparencyRegister::class)
            ->assertViewHas('mapPoints', function ($points): bool {
                $barangays = collect($points)->pluck('barangay')->sort()->values()->all();

                return $barangays === ['Calasgasan', 'Gahonon'];
            });
    }

    public function test_the_public_page_names_the_barangay_the_resident_said(): void
    {
        // A coordinate whose nearest reference point is a different barangay, so
        // a lookup-based implementation would name the wrong one.
        $complaint = $this->file(user: $this->resident('Borabod'), fields: [
            'barangay' => 'Gahonon',
            'latitude' => 14.1264,
            'longitude' => 122.9432,
            'visibility' => 'public',
        ]);

        $locator = app(\App\Services\BarangayLocator::class);
        $guessed = $locator->locate($complaint->latitude, $complaint->longitude);

        $body = $this->get(route('transparency.complaints.show', $complaint))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Gahonon', $body);

        if ($guessed !== null && $guessed !== 'Gahonon') {
            $this->fail(
                "This fixture no longer distinguishes the two: the nearest reference point is "
                ."{$guessed}, which is also what the resident said. Pick a coordinate whose "
                .'nearest reference point is a different barangay.',
            );
        }
    }

    /**
     * The coordinate is the department's, and the whole point of the form.
     * Adding a human-readable name must not cost precision.
     */
    public function test_the_exact_coordinate_is_still_stored(): void
    {
        $complaint = $this->file(user: $this->resident('Borabod'), fields: [
            'barangay' => 'Gahonon',
            'latitude' => 14.12641234,
            'longitude' => 122.94329876,
        ]);

        $this->assertEqualsWithDelta(14.12641234, (float) $complaint->latitude, 0.0000001);
        $this->assertEqualsWithDelta(122.94329876, (float) $complaint->longitude, 0.0000001);
    }

    public function test_every_official_barangay_can_be_filed(): void
    {
        foreach (User::BARANGAYS as $barangay) {
            $complaint = $this->file(user: $this->resident('Borabod'), fields: [
                'barangay' => $barangay,
            ]);

            $this->assertSame($barangay, $complaint->barangay);
        }
    }

    // ── fixtures ───────────────────────────────────────────────────────

    private function resident(string $barangay): User
    {
        return User::factory()->create([
            'role' => 'citizen',
            'barangay' => $barangay,
        ]);
    }

    private function file(User $user, array $fields = []): Complaint
    {
        $this->filing($user, $fields)->assertSessionHasNoErrors();

        return Complaint::latest('id')->firstOrFail();
    }

    private function filing(User $user, array $fields = [])
    {
        return $this->actingAs($user)->post(route('complaints.store'), array_merge([
            'category' => 'road_damage',
            'title' => 'Pothole on the road',
            'description' => 'There is a deep pothole near the public market.',
            'latitude' => 14.1264,
            'longitude' => 122.9432,
            'image' => $this->complaintEvidence(),
            'terms' => '1',
        ], $fields));
    }
}
