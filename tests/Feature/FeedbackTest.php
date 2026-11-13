<?php

namespace Tests\Feature;

use App\Enums\FeedbackStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Feedback;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Resident feedback.
 *
 * A rating counts from the moment it is given; a suggestion waits for the
 * administration to read it. A resident rates the portal and the offices they
 * have actually dealt with — a rating of an office you never wrote to says
 * nothing about the office.
 */
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_the_page_is_reachable_from_the_footer(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $response = $this->actingAs($user)->get(route('feedback.index'));

        $response->assertOk();
        $response->assertSee(__('feedback.portal_title'));
    }

    public function test_a_resident_can_rate_the_portal_and_the_offices_they_dealt_with(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $environment = Department::where('code', 'MENRO')->firstOrFail();

        $this->complaint($user, $engineering);
        $this->complaint($user, $environment);

        $response = $this->actingAs($user)->post(route('feedback.store'), [
            'portal_rating' => 5,
            'office_ratings' => [
                $engineering->id => 4,
                $environment->id => 2,
            ],
        ]);

        $response->assertRedirect(route('feedback.index'));
        $response->assertSessionHas('status');

        $this->assertSame(5, Feedback::where('user_id', $user->id)->whereNull('department_id')->value('rating'));
        $this->assertSame(4, Feedback::where('user_id', $user->id)->where('department_id', $engineering->id)->value('rating'));
        $this->assertSame(2, Feedback::where('user_id', $user->id)->where('department_id', $environment->id)->value('rating'));
    }

    public function test_an_office_the_resident_never_dealt_with_cannot_be_rated(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $business = Department::where('code', 'OM')->firstOrFail();

        $this->complaint($user, $engineering);

        $this->actingAs($user)->post(route('feedback.store'), [
            'office_ratings' => [$business->id => 1],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            0,
            Feedback::where('user_id', $user->id)->where('department_id', $business->id)->count(),
            'A rating of an office the resident never wrote to must not be recorded.',
        );
    }

    public function test_a_rating_replaces_the_residents_earlier_one(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), ['portal_rating' => 2]);
        $this->actingAs($user)->post(route('feedback.store'), ['portal_rating' => 5]);

        $this->assertSame(1, Feedback::where('user_id', $user->id)->whereNull('department_id')->count());
        $this->assertSame(5, Feedback::where('user_id', $user->id)->whereNull('department_id')->value('rating'));
    }

    public function test_a_suggestion_waits_to_be_read(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'The map is hard to use on a phone.',
        ])->assertSessionHasNoErrors();

        $suggestion = Feedback::where('user_id', $user->id)->where('kind', Feedback::KIND_SUGGESTION)->firstOrFail();

        $this->assertSame(FeedbackStatus::Pending, $suggestion->statusEnum());
        $this->assertNull($suggestion->published_at);
    }

    public function test_the_administration_publishes_a_suggestion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'The map is hard to use on a phone.',
        ]);

        $suggestion = Feedback::where('kind', Feedback::KIND_SUGGESTION)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.feedback.publish', $suggestion))
            ->assertRedirect(route('admin.feedback.index'));

        $suggestion->refresh();

        $this->assertSame(FeedbackStatus::Published, $suggestion->statusEnum());
        $this->assertNotNull($suggestion->published_at);
        $this->assertSame($admin->id, $suggestion->reviewed_by);
    }

    public function test_the_administration_can_keep_a_suggestion_off_the_register(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'Something we would rather not publish.',
        ]);

        $suggestion = Feedback::where('kind', Feedback::KIND_SUGGESTION)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.feedback.withhold', $suggestion));

        $this->assertSame(FeedbackStatus::Withheld, $suggestion->fresh()->statusEnum());
    }

    public function test_a_published_suggestion_is_visible_to_everyone(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'Please show the office on the map as well as the barangay.',
        ]);

        $suggestion = Feedback::where('kind', Feedback::KIND_SUGGESTION)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.feedback.publish', $suggestion));

        // A visitor who is not signed in still sees what was published.
        $this->get(route('transparency.complaints'))
            ->assertOk()
            ->assertSee('Please show the office on the map as well as the barangay.');
    }

    public function test_the_administration_can_open_the_feedback_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'Please show the office on the map as well as the barangay.',
        ]);

        $suggestion = Feedback::where('kind', Feedback::KIND_SUGGESTION)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.feedback.publish', $suggestion));

        // The queue the administration reads from must load the reviewer
        // without asking the users table for a column that is not there, and
        // must render the real strings — not the raw lang keys.
        $this->actingAs($admin)->get(route('admin.feedback.index'))
            ->assertOk()
            ->assertSee('Waiting to be read')
            ->assertSee('Please show the office on the map as well as the barangay.');
    }

    public function test_a_suggestion_that_has_not_been_read_is_not_public(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'Something nobody has read yet.',
        ]);

        $this->get(route('transparency.complaints'))
            ->assertOk()
            ->assertDontSee('Something nobody has read yet.');
    }

    public function test_the_average_is_the_mean_of_the_ratings(): void
    {
        $engineering = Department::where('code', 'ENG')->firstOrFail();

        foreach ([5, 4, 3] as $rating) {
            $user = User::factory()->create(['role' => 'citizen']);

            // An office can only be rated by someone who wrote to it.
            $this->complaint($user, $engineering);

            $this->actingAs($user)->post(route('feedback.store'), [
                'office_ratings' => [$engineering->id => $rating],
            ]);
        }

        $average = app(\App\Services\FeedbackService::class)->averageFor($engineering);

        $this->assertSame(4.0, $average['average']);
        $this->assertSame(3, $average['count']);
    }

    private function complaint(User $user, Department $department): Complaint
    {
        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'department_id' => $department->id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'content_fingerprint' => \Illuminate\Support\Str::random(40),
        ]);
    }
}
