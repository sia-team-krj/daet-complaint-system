<?php

namespace Tests\Feature;

use App\Http\Controllers\ComplaintController;
use App\Models\Complaint;
use App\Models\User;
use App\Services\PhotoLocationService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A photo's own GPS and the pin the resident placed are two different claims
 * about the same place, and only the resident knows which one the pothole is at.
 *
 * Before this, the photo won silently and unconditionally: take a photo at home,
 * carry it to a broken road, and the complaint was filed at home with no warning
 * and no way to correct it.
 */
class ComplaintLocationChoiceTest extends TestCase
{
    use RefreshDatabase;

    /** Daet town centre, and a point about 2 km away. */
    private const PIN_LAT = 14.1122;

    private const PIN_LNG = 122.9553;

    private const PHOTO_LAT = 14.1300;

    private const PHOTO_LNG = 122.9700;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        Storage::fake('minio');
    }

    private function withPhotoAt(float $lat, float $lng): void
    {
        $this->app->instance(PhotoLocationService::class, new class($lat, $lng) extends PhotoLocationService
        {
            public function __construct(private float $lat, private float $lng) {}

            public function extract(UploadedFile $photo): ?array
            {
                return ['latitude' => $this->lat, 'longitude' => $this->lng];
            }
        });
    }

    private function submit(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('complaints.store'), array_merge([
            'category' => 'road_damage',
            'title' => 'Pothole on the road',
            'description' => 'There is a deep pothole on the road near the public market.',
            'barangay' => User::BARANGAYS[0],
            'latitude' => self::PIN_LAT,
            'longitude' => self::PIN_LNG,
            'image' => $this->complaintEvidence(),
            'terms' => '1',
        ], $overrides));
    }

    // ── the question gets asked ───────────────────────────────────────

    public function test_a_photo_far_from_the_pin_stops_the_submission_to_ask(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user)->assertSessionHasErrors('location_choice');

        $this->assertSame(
            0,
            Complaint::count(),
            'Nothing may be filed until the resident says which location they meant.',
        );
    }

    public function test_the_prompt_names_both_locations_as_barangays(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user);

        $response = $this->actingAs($user)->get(route('complaints.create'));

        $response->assertOk()
            ->assertSee('Where is the problem?')
            // The pin they placed.
            ->assertSee('The point I marked on the map')
            // And the location the photo knows about.
            ->assertSee('Where the photo was taken');

        // Assert against the prompt alone, not the whole page. The coordinate
        // is still present in the hidden latitude/longitude inputs, which is
        // correct — the choice has to be submitted against it — but none of it
        // may be shown to the resident as text.
        $prompt = $this->promptBlock($response->getContent());

        $this->assertStringNotContainsString(
            '14.1122',
            $prompt,
            'The prompt must not show a raw latitude.',
        );
        $this->assertStringNotContainsString(
            '122.9553',
            $prompt,
            'The prompt must not show a raw longitude.',
        );

        // Each candidate is labelled with a barangay from the app's own list, so
        // the spelling matches what they chose during registration.
        $this->assertMatchesRegularExpression(
            '/class="location-choice__coords"[^>]*>\s*Barangay/s',
            $prompt,
            'Each option should be labelled with a barangay name.',
        );
    }

    /** Just the conflict prompt, with the rest of the page stripped out. */
    private function promptBlock(string $html): string
    {
        $matched = preg_match('/<div class="location-choice">.*?<\/fieldset>/s', $html, $m);

        $this->assertSame(1, $matched, 'The location prompt was not rendered.');

        return $m[0];
    }

    public function test_the_prompt_explains_the_disagreement_without_exposing_precision(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user);

        $response = $this->actingAs($user)->get(route('complaints.create'));

        $response->assertOk()
            ->assertSee('taken in a different area from the point you marked', false);

        // "Somewhere else" is not enough to choose on, but the fix is naming the
        // two barangays — not printing the distance between two precise points,
        // which is a precise fact about where one of them is.
        $this->assertStringNotContainsString('km away', $response->getContent());
    }

    // ── both answers are honoured ──────────────────────────────────────

    public function test_choosing_the_pin_files_it_at_the_pin(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user)->assertSessionHasErrors('location_choice');
        $this->submit($user, ['location_choice' => ComplaintController::LOCATION_CHOICE_MAP])
            ->assertSessionMissing('errors');

        $complaint = Complaint::sole();

        $this->assertEqualsWithDelta(self::PIN_LAT, (float) $complaint->latitude, 0.00001);
        $this->assertEqualsWithDelta(self::PIN_LNG, (float) $complaint->longitude, 0.00001);
    }

    public function test_choosing_the_photo_files_it_at_the_photo_location(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user)->assertSessionHasErrors('location_choice');
        $this->submit($user, ['location_choice' => ComplaintController::LOCATION_CHOICE_PHOTO])
            ->assertSessionMissing('errors');

        $complaint = Complaint::sole();

        $this->assertEqualsWithDelta(self::PHOTO_LAT, (float) $complaint->latitude, 0.00001);
        $this->assertEqualsWithDelta(self::PHOTO_LNG, (float) $complaint->longitude, 0.00001);
    }

    public function test_the_resident_does_not_have_to_reattach_their_photos(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user)->assertSessionHasErrors('location_choice');

        // allFiles(), not files(): the uploads sit under a per-session token
        // directory, and files() only lists the immediate level.
        $held = Storage::disk('minio')->allFiles('pending-complaint-uploads');
        $this->assertNotEmpty($held, 'The photos should be held, not discarded.');

        // Answer with no upload at all — the evidence must come from the holding area.
        $this->actingAs($user)->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Pothole on the road',
            'description' => 'There is a deep pothole on the road near the public market.',
            'barangay' => User::BARANGAYS[0],
            'latitude' => self::PIN_LAT,
            'longitude' => self::PIN_LNG,
            'visibility' => 'private',
            'location_choice' => ComplaintController::LOCATION_CHOICE_MAP,
            'terms' => '1',
        ])->assertSessionMissing('errors');

        $complaint = Complaint::sole();
        $this->assertNotEmpty($complaint->allImagePaths());
        $this->assertCount(1, $complaint->allImagePaths());
    }

    public function test_the_held_photos_are_cleaned_up_after_the_choice(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user);
        $this->submit($user, ['location_choice' => ComplaintController::LOCATION_CHOICE_MAP]);

        $this->assertSame(
            [],
            Storage::disk('minio')->allFiles('pending-complaint-uploads'),
            'The holding area must not accumulate orphans.',
        );
    }

    // ── no question when there is nothing to ask about ─────────────────

    public function test_a_photo_near_the_pin_is_applied_without_asking(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        // A few metres off: GPS noise, not a disagreement.
        $this->withPhotoAt(self::PIN_LAT + 0.0003, self::PIN_LNG + 0.0003);

        $this->submit($user)->assertSessionMissing('errors');

        $this->assertSame(1, Complaint::count());
    }

    public function test_a_photo_with_no_gps_falls_through_to_the_pin(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(0.0, 0.0);
        $this->app->instance(PhotoLocationService::class, new class extends PhotoLocationService
        {
            public function extract(UploadedFile $photo): ?array
            {
                return null;
            }
        });

        $this->submit($user)->assertSessionMissing('errors');

        $complaint = Complaint::sole();
        $this->assertEqualsWithDelta(self::PIN_LAT, (float) $complaint->latitude, 0.00001);
    }

    public function test_a_photo_and_no_pin_is_applied_without_asking(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PHOTO_LAT, self::PHOTO_LNG);

        $this->submit($user, ['latitude' => null, 'longitude' => null])
            ->assertSessionMissing('errors');

        $complaint = Complaint::sole();
        $this->assertEqualsWithDelta(self::PHOTO_LAT, (float) $complaint->latitude, 0.00001);
    }

    /*
     * The locate-me control.
     *
     * Nothing in a PHP test can watch a browser call the Geolocation API, so what
     * is asserted is that the control exists, is wired to the API rather than to a
     * mock of it, and that the secure-context failure is explained instead of
     * silently doing nothing. That last one matters because this portal is
     * routinely opened on a plain-http LAN address while testing, where the API
     * refuses and a bare "location unavailable" would read as a broken button.
     */
    public function test_the_form_offers_to_use_the_browsers_location(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $html = $this->actingAs($user)->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Use my current location')
            ->getContent();

        $this->assertStringContainsString('id="locate-me"', $html);
        $this->assertStringContainsString('navigator.geolocation.getCurrentPosition', $html);

        // The button must exist even with nothing staged, and must not be a
        // second way to submit the form.
        // Order-independent: attribute order is presentation, not contract.
        $this->assertMatchesRegularExpression(
            '/<button(?=[^>]*\bid="locate-me")(?=[^>]*\btype="button")[^>]*>/',
            $html,
            'The locate control must be type=button so it cannot submit the form.',
        );
    }

    public function test_a_refused_geolocation_explains_itself(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Finding your location...', false)
            // Every failure path still tells the resident what to do instead.
            ->assertSee('You can still place the pin on the map', false)
            ->assertSee('Please place the pin on the map', false)
            // And names the one failure that is really a setup problem.
            ->assertSee('secure (https) connection', false);
    }

    public function test_the_locate_status_is_announced_to_assistive_technology(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($user)->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('id="locate-status"', false)
            // A location result is not useful if a screen reader never hears it.
            ->assertSee('aria-live="polite"', false);
    }

    public function test_the_prompt_does_not_appear_without_a_disagreement(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->withPhotoAt(self::PIN_LAT, self::PIN_LNG);

        $this->submit($user)->assertSessionMissing('errors');

        $this->actingAs($user)->get(route('complaints.create'))
            ->assertOk()
            ->assertDontSee('Where is the problem?');
    }
}
