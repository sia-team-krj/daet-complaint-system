<?php

namespace Tests\Feature;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Public visibility is the resident's decision. Moderation of what the public
 * sees is a staff and admin decision. These are deliberately separate powers,
 * owned by separate people, and the tests below exist to stop them merging
 * back together — which is how the system behaved before, with a single
 * `is_public` flag written by whichever office handled the complaint.
 */
class ComplaintVisibilityConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Department routing and the staff permission checks both need real
        // departments to exist.
        $this->seed(DepartmentSeeder::class);
    }

    // ── The resident's half ───────────────────────────────────────────

    public function test_a_filed_complaint_is_private_by_default(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $complaint = $this->fileComplaint($user);

        $this->assertFalse(
            $complaint->is_public,
            'A complaint must default to private. Publishing by default would '
                .'expose a resident to the public page without them asking.',
        );
        $this->assertFalse($complaint->isPubliclyVisible());
    }

    public function test_a_complaint_filed_without_the_visibility_field_is_private(): void
    {
        // An old bookmarked form, or a client that predates the field, must not
        // publish anything. Absence of consent is not consent.
        $user = User::factory()->create(['role' => 'citizen']);

        $complaint = $this->fileComplaint($user, ['visibility' => null]);

        $this->assertFalse($complaint->is_public);
    }

    public function test_a_resident_can_publish_their_own_complaint_when_filing(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);

        $complaint = $this->fileComplaint($user, ['visibility' => 'public']);

        $this->assertTrue($complaint->is_public);
        $this->assertTrue($complaint->isPubliclyVisible());
    }

    public function test_a_resident_can_change_their_own_visibility_after_filing(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->fileComplaint($user);

        $this->actingAs($user)
            ->put(route('complaints.visibility.update', $complaint), ['visibility' => 'public'])
            ->assertRedirect(route('complaints.show', $complaint))
            ->assertSessionHas('status');

        $this->assertTrue($complaint->fresh()->is_public);

        $this->actingAs($user)
            ->put(route('complaints.visibility.update', $complaint), ['visibility' => 'private'])
            ->assertRedirect(route('complaints.show', $complaint));

        $this->assertFalse($complaint->fresh()->is_public);
    }

    public function test_another_resident_cannot_change_someone_elses_visibility(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $stranger = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->fileComplaint($owner);

        $this->actingAs($stranger)
            ->put(route('complaints.visibility.update', $complaint), ['visibility' => 'public'])
            ->assertForbidden();

        $this->assertFalse($complaint->fresh()->is_public);
    }

    public function test_neither_staff_nor_admin_can_change_public_visibility(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->fileComplaint($owner);
        $department = Department::where('code', 'ENG')->firstOrFail();

        $actors = [
            'staff' => User::factory()->create([
                'role' => 'staff',
                'department_id' => $department->id,
                'can_verify' => true,
            ]),
            'admin' => User::factory()->create(['role' => 'admin']),
        ];

        foreach ($actors as $role => $actor) {
            $this->actingAs($actor)
                ->put(route('complaints.visibility.update', $complaint), ['visibility' => 'public'])
                ->assertForbidden();

            $this->assertFalse(
                $complaint->fresh()->is_public,
                "A {$role} must not be able to publish a resident's complaint.",
            );
        }
    }

    // ── The moderation half ───────────────────────────────────────────

    public function test_staff_can_hide_a_public_complaint_with_a_reason(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'hidden_from_public' => '1',
                'moderation_reason' => 'Description contains a slur.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('status');

        $complaint->refresh();

        $this->assertTrue($complaint->isHiddenFromPublic());
        $this->assertFalse($complaint->isPubliclyVisible());
        $this->assertFalse(Complaint::public()->whereKey($complaint->id)->exists());

        // The consent is untouched — moderation withholds, it never consents.
        $this->assertTrue($complaint->is_public);
    }

    public function test_moderation_requires_a_reason(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        // Anything removed from the transparency page has to be explainable.
        // An unexplained disappearance is the exact failure this system exists
        // to prevent, so it is refused rather than merely discouraged.
        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'hidden_from_public' => '1',
            ])
            ->assertSessionHasErrors('moderation_reason');

        $this->assertFalse($complaint->fresh()->isHiddenFromPublic());
    }

    public function test_moderation_cannot_publish_a_private_complaint(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();
        $complaint->update(['is_public' => false]);

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'hidden_from_public' => '0',
                'redacted_title' => 'A replacement title',
                'moderation_reason' => 'Original title named a private individual.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertFalse(
            $complaint->fresh()->is_public,
            'Redacting content must not become a back door to publication.',
        );
    }

    public function test_a_redacted_field_shows_the_replacement_publicly_and_the_original_to_the_resident(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();
        $originalTitle = $complaint->title;
        $originalDescription = $complaint->description;

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'redacted_title' => 'Damage to public facility',
                'redacted_description' => 'A public facility was damaged. Details withheld.',
                'moderation_reason' => 'Original text named the complaining resident.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $complaint->refresh();

        $this->assertTrue($complaint->isRedacted());

        // The public reads the cleaned version.
        $this->assertSame('Damage to public facility', $complaint->publicTitle());
        $this->assertStringNotContainsString($originalDescription, $complaint->publicDescription());

        // The resident's own record is never edited. Their words are theirs.
        $this->assertSame($originalTitle, $complaint->title);
        $this->assertSame($originalDescription, $complaint->description);
    }

    public function test_a_withheld_photo_is_hidden_publicly_but_kept_for_the_resident(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        $first = 'complaints/evidence/one.jpg';
        $second = 'complaints/evidence/two.jpg';
        $complaint->update(['image_path' => $first, 'image_paths' => [$first, $second]]);

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'withheld_image_paths' => [$first],
                'moderation_reason' => 'First photo shows a bystander who did not consent.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $complaint->refresh();

        $this->assertSame([$second], $complaint->publicImagePaths());
        $this->assertCount(2, $complaint->allImagePaths(), 'The resident keeps every photo they uploaded.');
    }

    public function test_moderation_cannot_withhold_a_photo_the_complaint_does_not_own(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        $mine = 'complaints/evidence/mine.jpg';
        $complaint->update(['image_path' => $mine, 'image_paths' => [$mine]]);

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'withheld_image_paths' => ['complaints/evidence/someone-elses.jpg'],
                'moderation_reason' => 'Attempt to withhold unrelated evidence.',
            ]);

        $this->assertSame(
            [],
            $complaint->fresh()->withheldImagePaths(),
            'A crafted request must not be able to withhold another complaint\'s photo.',
        );
    }

    public function test_asking_a_resident_for_more_evidence_does_not_unpublish_their_complaint(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        // Asking for more evidence used to force is_public to false, which meant
        // an office could withdraw a resident's consent just by opening a
        // dialogue with them. The complaint stays exactly as the resident left it.
        $this->actingAs($staff)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'Please send a photo showing the full width of the damaged road.',
            ])
            ->assertSessionHas('status');

        $complaint->refresh();

        $this->assertTrue(
            $complaint->is_public,
            'Requesting more evidence must not un-publish a complaint.',
        );
        $this->assertTrue($complaint->isPubliclyVisible());
    }

    public function test_a_withheld_photo_is_not_reachable_by_its_old_url(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        // Real bytes on the same disk the streaming endpoint reads, so the
        // assertion is about authorisation rather than a missing file.
        $first = 'complaints/evidence/one.png';
        $second = 'complaints/evidence/two.png';
        Storage::disk('minio')->put($first, 'first-photo-bytes');
        Storage::disk('minio')->put($second, 'second-photo-bytes');
        $complaint->update(['image_path' => $first, 'image_paths' => [$first, $second]]);

        // Two photos are addressable while nothing is withheld.
        $this->assertCount(2, $complaint->publicImagePaths());
        $this->get(route('transparency.complaints.evidence', [$complaint, 1]))->assertOk();

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'withheld_image_paths' => [$first],
                'moderation_reason' => 'Photo shows a bystander who did not consent.',
            ]);

        $complaint->refresh();

        // The public list is what the evidence endpoint indexes into, so this
        // list is the authorisation boundary. Asserted here rather than on the
        // streamed bytes, which a test response does not capture.
        $this->assertSame(
            [$second],
            $complaint->publicImagePaths(),
            'The withheld photo must leave the list the public endpoint serves from.',
        );

        // The list shrank, so the old index 1 no longer resolves at all — a URL
        // that once served a held-back photo cannot keep working by accident.
        $this->get(route('transparency.complaints.evidence', [$complaint, 1]))->assertNotFound();

        // Index 0 still resolves, because a permitted photo remains.
        $this->get(route('transparency.complaints.evidence', [$complaint, 0]))->assertOk();
    }

    public function test_a_complaint_hidden_by_staff_404s_from_the_public_pages(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        $this->get(route('transparency.complaints.show', $complaint))->assertOk();

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'hidden_from_public' => '1',
                'moderation_reason' => 'Content violates community guidelines.',
            ]);

        // The listing, the detail page, and the photo endpoint must all agree.
        $this->get(route('transparency.complaints.show', $complaint))->assertNotFound();
        $this->get(route('transparency.complaints.evidence', [$complaint, 0]))->assertNotFound();
    }

    public function test_a_redacted_title_is_what_the_public_register_lists(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();
        $complaint->update(['title' => 'Original title naming a private individual']);

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'redacted_title' => 'Damage to a public facility',
                'moderation_reason' => 'Original title identified a bystander.',
            ]);

        $complaint->refresh();

        // The register's list payload carries the cleaned title.
        $this->get(route('transparency'))
            ->assertOk()
            ->assertSee('Damage to a public facility')
            ->assertDontSee('Original title naming a private individual');
    }

    public function test_the_resident_page_shows_their_visibility_control(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('complaints.show', $complaint));

        $response->assertOk()
            // The control itself, wired to the owner-only route.
            ->assertSee(route('complaints.visibility.update', $complaint), false)
            ->assertSee('Make this private', false);

        // The staff-facing wording must never leak into the resident's page.
        $response->assertDontSee('Allow this verified report', false);
    }

    public function test_the_resident_page_explains_a_moderation_without_rewriting_their_copy(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
            'hidden_from_public' => true,
            'redacted_title' => 'Road damage reported by a resident',
            'moderation_reason' => 'Original title identified a bystander.',
        ]);

        $response = $this->actingAs($owner)->get(route('complaints.show', $complaint));

        $response->assertOk()
            // The notice states plainly that their own submission is unchanged.
            ->assertSee('your original submission, unchanged', false)
            ->assertSee('Original title identified a bystander.', false)
            // And their own words are still what the page shows them.
            ->assertSee($complaint->title, false)
            ->assertDontSee('Road damage reported by a resident', false);
    }

    public function test_the_staff_page_offers_moderation_and_not_publication(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);
        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'is_public' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee(route('staff.complaints.moderate', $complaint), false)
            // The resident's decision is stated, and there is no control for it.
            ->assertSee('The resident published this', false)
            ->assertDontSee('name="is_public"', false);
    }

    public function test_the_admin_page_offers_moderation_and_not_publication(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $complaint = Complaint::factory()->create([
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'is_public' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee(route('admin.complaints.moderate', $complaint), false)
            // The resident's decision is stated as a fact, not an input.
            ->assertSee('Published by the resident', false)
            ->assertDontSee('name="is_public"', false);
    }

    public function test_an_admin_can_also_moderate(): void
    {
        [, $complaint] = $this->publicComplaintForStaff();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.complaints.moderate', $complaint), [
                'hidden_from_public' => '1',
                'moderation_reason' => 'Reported as a duplicate of an existing case.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint));

        $this->assertTrue($complaint->fresh()->isHiddenFromPublic());
    }

    public function test_moderation_is_recorded_in_the_activity_trail(): void
    {
        [$staff, $complaint] = $this->publicComplaintForStaff();

        $this->actingAs($staff)
            ->post(route('staff.complaints.moderate', $complaint), [
                'hidden_from_public' => '1',
                'moderation_reason' => 'Content violates community guidelines.',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.moderated',
            'subject_type' => Complaint::class,
            'subject_id' => $complaint->id,
        ]);

        // The reason travels with it, so the decision is explainable later
        // rather than being an unexplained gap in the public record.
        $entry = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->where('action', 'complaint.moderated')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertStringContainsString('community guidelines', (string) $entry->description);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * File a complaint as the given resident and return it.
     *
     * The store action redirects to the success page rather than back, so the
     * row is read back by the resident's id instead of from the response.
     */
    private function fileComplaint(User $user, array $overrides = []): Complaint
    {
        $before = Complaint::where('user_id', $user->id)->max('id') ?? 0;

        $this->actingAs($user)->post(route('complaints.store'), array_merge([
            'category' => 'road_damage',
            'title' => 'Pothole outside the market',
            'description' => 'There is a deep pothole on the road outside the public market entrance.',
            'barangay' => User::BARANGAYS[0],
            'latitude' => 14.1129,
            'longitude' => 122.9570,
            'address_text' => 'In front of Daet Public Market',
            'image' => $this->complaintEvidence(),
            'terms' => '1',
        ], $overrides))->assertRedirect();

        $complaint = Complaint::where('user_id', $user->id)
            ->where('id', '>', $before)
            ->latest('id')
            ->first();

        $this->assertNotNull($complaint, 'The complaint was not filed.');

        return $complaint->fresh();
    }

    private function publicComplaintForStaff(): array
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);

        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'is_public' => true,
            'review_status' => ComplaintReviewStatus::Verified->value,
            'confirmed_priority' => ComplaintPriority::Routine->value,
        ]);

        return [$staff, $complaint];
    }
}
