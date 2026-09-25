<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransparencyPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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
            ->assertSee('Community reports,')
            ->assertSee($publicOne->title)
            ->assertSee($publicTwo->title)
            ->assertSee('All public complaints.')
            ->assertSee('Barangay VI, Daet')
            ->assertSee('Community neighbor')
            ->assertSee('leaflet.heat', false)
            ->assertSee('14.11', false)
            ->assertDontSee($private->title)
            ->assertDontSee($publicSpam->title)
            ->assertDontSee('PrivateIdentity')
            ->assertDontSee('private-identity@example.test')
            ->assertDontSee('09123456789')
            ->assertDontSee('14.112234')
            ->assertDontSee('10.123456')
            ->assertDontSee('11.222222')
            ->assertDontSee(route('complaints.track'), false);

        $response->assertViewHas('reports', function ($reports): bool {
            return $reports->count() === 2
                && $reports->contains('title', 'Public road safety report')
                && $reports->contains('title', 'Public drainage follow-up');
        });

        $response->assertViewHas('mapPoints', function ($points): bool {
            return $points->count() === 1
                && $points->first()['count'] === 2
                && $points->first()['lat'] === 14.11;
        });

        $this->assertSame(2, substr_count($response->getContent(), '<section class="transparency-section'));
    }

    public function test_transparency_page_explains_an_empty_public_register(): void
    {
        $response = $this->get(route('transparency'));

        $response->assertOk()
            ->assertSee('No public complaints to show.')
            ->assertSee('No approximate public areas are available yet.')
            ->assertViewHas('reports', fn ($reports): bool => $reports->isEmpty())
            ->assertViewHas('mapPoints', fn ($points): bool => $points->isEmpty());
    }

    public function test_authenticated_visitors_receive_the_map_styles(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('transparency'));

        $response->assertOk()
            ->assertSee('leaflet.css', false)
            ->assertSee('transparency-root', false);
    }
}
