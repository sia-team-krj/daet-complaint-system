<?php

namespace Tests\Feature;

use App\Services\BarangayLocator;
use App\Services\DaetBoundary;
use Tests\TestCase;

/**
 * The map pin has to be in Daet, but the boundary is a simplified line.
 *
 * These tests keep the two things that can go wrong visible: a polygon that
 * does not cover the city's own barangays, and a tolerance so tight that a
 * resident standing on the municipal edge is told they are outside it.
 */
class DaetBoundaryTest extends TestCase
{
    public function test_the_polygon_covers_every_barangay_reference_point(): void
    {
        $boundary = app(DaetBoundary::class);
        $points = app(BarangayLocator::class)->points();

        $this->assertCount(25, $points, 'The barangay table changed; the boundary coverage check needs updating.');

        foreach ($points as $name => [$latitude, $longitude]) {
            $this->assertTrue(
                $boundary->contains($latitude, $longitude),
                "{$name} falls outside the Daet boundary. The polygon or the reference point is wrong.",
            );
        }
    }

    public function test_the_town_centre_is_inside(): void
    {
        $this->assertTrue(app(DaetBoundary::class)->contains(14.1122, 122.9553));
    }

    public function test_a_point_in_manila_is_outside(): void
    {
        $this->assertFalse(app(DaetBoundary::class)->contains(14.5995, 120.9842));
    }

    public function test_a_pin_just_outside_the_simplified_line_is_still_within_tolerance(): void
    {
        $boundary = app(DaetBoundary::class);

        // About 108 m outside the southern edge of the simplified polygon.
        $this->assertFalse($boundary->contains(14.0640, 122.9305));
        $this->assertTrue(
            $boundary->isWithin(14.0640, 122.9305),
            'A point just over the simplified line must not be rejected; the line is not survey-grade.',
        );
    }

    public function test_a_pin_beyond_the_edge_tolerance_is_rejected(): void
    {
        $boundary = app(DaetBoundary::class);

        // About 301 m outside the same edge.
        $this->assertFalse($boundary->contains(14.0620, 122.9305));
        $this->assertFalse($boundary->isWithin(14.0620, 122.9305));
    }

    public function test_the_boundary_exposes_a_bounding_box_for_the_map(): void
    {
        $bounds = app(DaetBoundary::class)->bounds();

        $this->assertNotNull($bounds);
        $this->assertLessThan($bounds[1][0], $bounds[0][0], 'The south edge must be below the north edge.');
        $this->assertLessThan($bounds[1][1], $bounds[0][1], 'The west edge must be left of the east edge.');
    }
}
