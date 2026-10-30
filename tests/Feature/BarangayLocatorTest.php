<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BarangayLocator;
use Tests\TestCase;

/**
 * The barangay table is the only thing that turns a coordinate into something a
 * resident recognises, and a misspelled key in it is invisible: the lookup still
 * returns *a* barangay, just the wrong one, or "not recognised" for a place
 * squarely inside the municipality. So the names are pinned to the app's own
 * list rather than trusted.
 */
class BarangayLocatorTest extends TestCase
{
    public function test_the_table_matches_the_official_barangay_list_exactly(): void
    {
        $coverage = app(BarangayLocator::class)->coverageAgainst(User::BARANGAYS);

        $this->assertSame(
            [],
            $coverage['missing'],
            'These barangays have no reference point, so a resident reporting from '
                .'there would be told an area they do not recognise. Found in: '
                .implode(', ', $coverage['missing']),
        );

        $this->assertSame(
            [],
            $coverage['unknown'],
            'These reference points do not match any official barangay name, so '
                .'they would never be used. Found in: '
                .implode(', ', $coverage['unknown']),
        );

        $this->assertSame(
            count(User::BARANGAYS),
            $coverage['covered'],
            'The table should cover every barangay in Daet.',
        );
    }

    public function test_it_names_the_barangay_a_coordinate_falls_in(): void
    {
        $locator = app(BarangayLocator::class);

        // Each of these is that barangay's own reference point, so the lookup
        // must return that barangay.
        foreach (config('daet-barangays.points') as $barangay => [$lat, $lng]) {
            $this->assertSame(
                $barangay,
                $locator->locate($lat, $lng),
                "A point at {$barangay}'s own reference point resolved elsewhere.",
            );
        }
    }

    public function test_it_never_returns_a_coordinate(): void
    {
        $locator = app(BarangayLocator::class);
        $label = $locator->describe(14.1122, 122.9553);

        $this->assertIsString($label);
        $this->assertStringNotContainsString('14.', (string) $label);
        $this->assertStringNotContainsString('122.', (string) $label);
        // The doubled prefix this replaced: names already say "Barangay".
        $this->assertStringNotContainsString('Barangay Barangay', (string) $label);
    }

    public function test_it_returns_nothing_for_points_nowhere_near_daet(): void
    {
        $locator = app(BarangayLocator::class);

        $this->assertNull($locator->locate(14.5995, 120.9842), 'Manila');
        $this->assertNull($locator->locate(13.0, 130.0), 'open ocean');
        $this->assertNull($locator->locate(10.3157, 123.8854), 'Cebu City');
    }

    public function test_it_returns_nothing_for_nonsense_input(): void
    {
        $locator = app(BarangayLocator::class);

        $this->assertNull($locator->locate(null, null));
        $this->assertNull($locator->locate('abc', 'def'));
        $this->assertNull($locator->describe('', ''));
    }

    public function test_a_point_near_daet_but_outside_still_gets_an_answer(): void
    {
        // The reference points are a lookup table, not a boundary. A complaint
        // just outside the municipality should still be given a barangay name
        // rather than an error, because the department still has to act on it.
        $locator = app(BarangayLocator::class);

        $this->assertIsString(
            $locator->locate(14.0700, 122.9600),
            'A point a few km from the table should still resolve to a barangay.',
        );
    }
}
