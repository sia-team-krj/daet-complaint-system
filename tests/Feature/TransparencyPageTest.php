<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Community reports,')
            ->assertSee($publicOne->title)
            ->assertSee($publicTwo->title)
            ->assertSee(route('transparency.complaints.show', $publicOne), false)
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
            ->assertSee('1 photo', false)
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
