<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A pin is a claim that the problem is in Daet. The server must not take that
 * claim on trust just because the coordinate is a valid number.
 */
class ComplaintLocationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        Storage::fake('minio');
    }

    private function submit(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('complaints.store'), array_merge([
            'category' => 'road_damage',
            'title' => 'Pothole on the road',
            'description' => 'There is a deep pothole on the road near the public market.',
            'barangay' => User::BARANGAYS[0],
            'latitude' => 14.1122,
            'longitude' => 122.9553,
            'image' => $this->complaintEvidence(),
            'terms' => '1',
        ], $overrides));
    }

    public function test_a_pin_outside_daet_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->submit($user, [
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ])->assertSessionHasErrors('latitude');

        $this->assertSame(
            0,
            Complaint::count(),
            'An out-of-town pin must not create a complaint.',
        );
    }

    public function test_a_pin_inside_daet_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->submit($user)->assertSessionMissing('errors');

        $this->assertSame(1, Complaint::count());
    }

    public function test_a_pin_just_outside_the_simplified_boundary_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        // About 108 m outside the simplified southern edge, inside the 250 m
        // tolerance. A resident on the municipal edge should not be turned away.
        $this->submit($user, [
            'latitude' => 14.0640,
            'longitude' => 122.9305,
        ])->assertSessionMissing('errors');

        $this->assertSame(1, Complaint::count());
    }

    public function test_a_pin_beyond_the_edge_tolerance_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        // About 301 m outside the same edge.
        $this->submit($user, [
            'latitude' => 14.0620,
            'longitude' => 122.9305,
        ])->assertSessionHasErrors('latitude');

        $this->assertSame(0, Complaint::count());
    }

    public function test_a_complaint_without_a_pin_is_still_accepted(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        // The pin is optional; the barangay and landmark fields are the
        // fallback, and the boundary rule must not make them unreachable.
        $this->submit($user, [
            'latitude' => null,
            'longitude' => null,
        ])->assertSessionMissing('errors');

        $this->assertSame(1, Complaint::count());
    }

    public function test_the_form_ships_the_boundary_for_the_map_to_check(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $html = $this->actingAs($user)
            ->get(route('complaints.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="daet-boundary-data"', $html);
        $this->assertStringContainsString('toleranceMetres', $html);
        $this->assertStringContainsString('isLocationAllowed', $html);
        $this->assertStringContainsString('maxBounds', $html);
    }
}
