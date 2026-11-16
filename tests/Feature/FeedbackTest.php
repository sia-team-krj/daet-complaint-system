<?php

namespace Tests\Feature;

use App\Enums\FeedbackStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Feedback;
use App\Models\User;
use App\Services\FeedbackService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Resident feedback.
 *
 * A rating counts from the moment it is given and is final; a suggestion waits
 * for the administration to read it and is limited to one per Daet day. A
 * resident rates only the offices that have resolved or closed a report for
 * them — a rating of unfinished work, or of an office they never dealt with,
 * says nothing about the service.
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

    public function test_a_resident_can_rate_the_portal_and_the_offices_that_finished_a_report(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $environment = Department::where('code', 'MENRO')->firstOrFail();

        $this->complaint($user, $engineering);
        $this->complaint($user, $environment, 'Closed');

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

    public function test_an_office_with_only_unfinished_work_cannot_be_rated(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $engineering = Department::where('code', 'ENG')->firstOrFail();

        foreach (['Submitted', 'Under Review', 'In Progress'] as $status) {
            $this->complaint($user, $engineering, $status);
        }

        $this->actingAs($user)->post(route('feedback.store'), [
            'office_ratings' => [$engineering->id => 5],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            0,
            Feedback::where('user_id', $user->id)->where('department_id', $engineering->id)->count(),
            'Work still in progress is not a finished service to rate.',
        );
    }

    public function test_only_resolved_or_closed_offices_are_offered(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $environment = Department::where('code', 'MENRO')->firstOrFail();
        $mayor = Department::where('code', 'OM')->firstOrFail();

        $this->complaint($user, $engineering);
        $this->complaint($user, $environment, 'Closed');
        $this->complaint($user, $mayor, 'In Progress');

        $offices = app(FeedbackService::class)->officesFor($user);

        $this->assertTrue($offices->contains('id', $engineering->id));
        $this->assertTrue($offices->contains('id', $environment->id));
        $this->assertFalse($offices->contains('id', $mayor->id));

        $this->actingAs($user)->get(route('feedback.index'))
            ->assertOk()
            ->assertSee($engineering->name)
            ->assertSee($environment->name)
            ->assertDontSee($mayor->name);
    }

    public function test_a_rating_cannot_be_changed_once_it_is_sent(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), ['portal_rating' => 2]);
        $this->actingAs($user)->post(route('feedback.store'), ['portal_rating' => 5]);

        $this->assertSame(1, Feedback::where('user_id', $user->id)->whereNull('department_id')->count());
        $this->assertSame(2, Feedback::where('user_id', $user->id)->whereNull('department_id')->value('rating'));
    }

    public function test_the_page_shows_an_existing_rating_as_a_locked_score(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $engineering = Department::where('code', 'ENG')->firstOrFail();

        $this->complaint($user, $engineering);

        $this->actingAs($user)->post(route('feedback.store'), [
            'portal_rating' => 4,
            'office_ratings' => [$engineering->id => 3],
        ]);

        $this->actingAs($user)->get(route('feedback.index'))
            ->assertOk()
            ->assertSee(__('feedback.rating_locked'))
            ->assertSee(__('feedback.rated_aria', ['rating' => 4]))
            ->assertSee('4 / 5')
            ->assertDontSee('name="portal_rating"', false)
            ->assertDontSee('name="office_ratings['.$engineering->id.']"', false);
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

    public function test_a_resident_can_send_one_suggestion_a_day(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'Please add SMS alerts.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'A second suggestion on the same day.',
        ])->assertSessionHas('error');

        $this->assertSame(
            1,
            Feedback::where('user_id', $user->id)->where('kind', Feedback::KIND_SUGGESTION)->count(),
        );
    }

    public function test_staff_are_limited_to_one_suggestion_a_day_too(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->post(route('feedback.store'), [
            'suggestion' => 'Please add SMS alerts.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($staff)->post(route('feedback.store'), [
            'suggestion' => 'A second suggestion on the same day.',
        ])->assertSessionHas('error');

        $this->assertSame(
            1,
            Feedback::where('user_id', $staff->id)->where('kind', Feedback::KIND_SUGGESTION)->count(),
        );
    }

    public function test_an_administrator_is_exempt_from_the_daily_suggestion_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('feedback.store'), [
            'suggestion' => 'First note from the administration.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('feedback.store'), [
            'suggestion' => 'Second note from the administration.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            Feedback::where('user_id', $admin->id)->where('kind', Feedback::KIND_SUGGESTION)->count(),
        );
    }

    public function test_the_page_disables_the_suggestion_box_once_one_has_been_sent_today(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'suggestion' => 'Please add SMS alerts.',
        ]);

        $this->actingAs($user)->get(route('feedback.index'))
            ->assertOk()
            ->assertSee(__('feedback.suggestion_limit'))
            ->assertDontSee(__('feedback.nothing_to_send'))
            ->assertSee('id="feedback-suggestion"', false)
            ->assertSee('disabled', false);
    }

    public function test_the_page_disables_submit_when_nothing_is_left_to_send(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'portal_rating' => 5,
            'suggestion' => 'Please add SMS alerts.',
        ]);

        $this->actingAs($user)->get(route('feedback.index'))
            ->assertOk()
            ->assertSee(__('feedback.suggestion_limit'))
            ->assertSee(__('feedback.already_submitted_all'))
            ->assertSee(__('feedback.already_submitted'))
            ->assertSee('disabled', false);
    }

    public function test_the_thank_you_is_rendered_as_a_toast(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->followingRedirects()
            ->actingAs($user)
            ->post(route('feedback.store'), ['portal_rating' => 5])
            ->assertOk()
            ->assertSee('data-toast', false)
            ->assertSee('toast--success', false)
            ->assertSee(__('feedback.sent_rating'));
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

            // An office can only be rated by someone it finished a report for.
            $this->complaint($user, $engineering);

            $this->actingAs($user)->post(route('feedback.store'), [
                'office_ratings' => [$engineering->id => $rating],
            ]);
        }

        $average = app(FeedbackService::class)->averageFor($engineering);

        $this->assertSame(4.0, $average['average']);
        $this->assertSame(3, $average['count']);
    }

    private function complaint(User $user, Department $department, string $status = 'Resolved'): Complaint
    {
        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'department_id' => $department->id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => $status,
            'review_status' => 'pending',
            'content_fingerprint' => \Illuminate\Support\Str::random(40),
            'resolved_at' => $status === 'Resolved' ? now() : null,
            'closed_at' => $status === 'Closed' ? now() : null,
        ]);
    }
}
