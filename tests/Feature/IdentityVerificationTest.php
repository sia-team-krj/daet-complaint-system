<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\IdentityHashService;
use App\Services\TwoFactorAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['identity.hash_key' => 'test-identity-hash-key']);
        config(['identity.verification_required' => true]);
        config(['identity.max_submissions_per_day' => 3]);
        Storage::fake('minio');
    }

    public function test_resident_can_view_verification_page(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->get(route('identity.verify.show'))
            ->assertOk()
            ->assertSee('Verify your identity');
    }

    public function test_resident_can_submit_id_and_selfie(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $response = $this->actingAs($resident)
            ->post(route('identity.verify.store'), [
                'id_type' => 'PhilID',
                'id_number' => '123-45-678',
                'id_front' => $this->idPhoto('id-front.jpg'),
                'selfie' => $this->idPhoto('selfie.jpg'),
                'terms' => '1',
                'consent_purpose' => true,
                'consent_access' => true,
                'consent_retention' => true,
            ]);

        $response->assertRedirect(route('identity.verify.show'))
            ->assertSessionHas('status', __('identity.verify.submitted'));

        $resident->refresh();
        $verification = $resident->latestIdentityVerification();

        $this->assertNotNull($verification);
        $this->assertSame('pending', $verification->status);
        $this->assertSame('PhilID', $verification->id_type);
        $this->assertSame(app(IdentityHashService::class)->hash('PhilID', '123-45-678'), $verification->id_number_hash);
        $this->assertNotNull($verification->id_front_path);
        $this->assertNotNull($verification->selfie_path);
        $this->assertTrue(Storage::disk('minio')->exists($verification->id_front_path));
        $this->assertTrue(Storage::disk('minio')->exists($verification->selfie_path));
        $this->assertSame('unverified', $resident->identity_status);
    }

    public function test_unverified_citizen_cannot_file_complaints(): void
    {
        $resident = User::factory()->create(['role' => 'citizen', 'identity_status' => 'unverified', 'identity_verified_at' => null]);

        $this->actingAs($resident)
            ->get(route('complaints.create'))
            ->assertRedirect(route('identity.verify.show'));
    }

    public function test_verified_citizen_can_file_complaints(): void
    {
        $resident = User::factory()->create([
            'role' => 'citizen',
            'identity_status' => 'verified',
            'identity_verified_at' => now(),
        ]);

        $this->actingAs($resident)
            ->get(route('complaints.create'))
            ->assertOk();
    }

    public function test_admin_can_approve_a_pending_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(TwoFactorAuthentication::class); // no-op to keep service warm in tests

        $resident = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($resident)
            ->post(route('identity.verify.store'), [
                'id_type' => 'PhilID',
                'id_number' => '12345678',
                'id_front' => $this->idPhoto('id-front.jpg'),
                'selfie' => $this->idPhoto('selfie.jpg'),
                'terms' => '1',
                'consent_purpose' => true,
                'consent_access' => true,
                'consent_retention' => true,
            ]);

        $verification = $resident->fresh()->latestIdentityVerification();
        $this->assertSame('pending', $verification->status);

        $this->actingAs($admin)
            ->post(route('admin.identity.approve', $verification))
            ->assertRedirect(route('admin.identity.show', $verification));

        $resident->refresh();
        $verification->refresh();

        $this->assertSame('approved', $verification->status);
        $this->assertNotNull($verification->reviewed_at);
        $this->assertSame('verified', $resident->identity_status);
        $this->assertNotNull($resident->identity_verified_at);
        $this->assertFalse(Storage::disk('minio')->exists($verification->id_front_path));
        $this->assertFalse(Storage::disk('minio')->exists($verification->selfie_path));
        $this->assertNotNull($verification->files_purged_at);
    }

    public function test_admin_can_reject_a_pending_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->post(route('identity.verify.store'), [
                'id_type' => 'PhilID',
                'id_number' => '12345678',
                'id_front' => $this->idPhoto('id-front.jpg'),
                'selfie' => $this->idPhoto('selfie.jpg'),
                'terms' => '1',
                'consent_purpose' => true,
                'consent_access' => true,
                'consent_retention' => true,
            ]);

        $verification = $resident->fresh()->latestIdentityVerification();

        $this->actingAs($admin)
            ->post(route('admin.identity.reject', $verification), [
                'reason' => 'The selfie does not match.',
            ])->assertRedirect(route('admin.identity.show', $verification));

        $verification->refresh();
        $resident->refresh();

        $this->assertSame('rejected', $verification->status);
        $this->assertSame('unverified', $resident->identity_status);
        $this->assertNull($resident->identity_verified_at);
        $this->assertFalse(Storage::disk('minio')->exists($verification->id_front_path));
    }

    public function test_admin_can_view_identity_queue_and_review_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->post(route('identity.verify.store'), [
                'id_type' => 'PhilID',
                'id_number' => '12345678',
                'id_front' => $this->idPhoto('id-front.jpg'),
                'selfie' => $this->idPhoto('selfie.jpg'),
                'terms' => '1',
                'consent_purpose' => true,
                'consent_access' => true,
                'consent_retention' => true,
            ]);

        $verification = $resident->fresh()->latestIdentityVerification();

        $this->actingAs($admin)
            ->get(route('admin.identity.index'))
            ->assertOk()
            ->assertSee($resident->full_name);

        $this->actingAs($admin)
            ->get(route('admin.identity.show', $verification))
            ->assertOk()
            ->assertSee($resident->full_name)
            ->assertSee('ID type');
    }

    public function test_admin_can_revoke_verified_resident_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->verifiedCitizen()->create();
        $resident->identityVerifications()->create([
            'status' => \App\Models\IdentityVerification::APPROVED,
            'id_type' => 'PhilID',
            'id_number_hash' => app(IdentityHashService::class)->hash('PhilID', '12345678'),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.identity.revoke', $resident), [
                'reason' => 'Rejected by the municipal office.',
            ])
            ->assertRedirect(route('admin.users.show', $resident));

        $resident->refresh();

        $this->assertSame('unverified', $resident->identity_status);
        $this->assertNull($resident->identity_verified_at);
        $this->actingAs($resident)->get(route('complaints.create'))->assertRedirect(route('identity.verify.show'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'identity.revoked']);
    }

    public function test_admin_can_stream_document_with_private_headers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->post(route('identity.verify.store'), [
                'id_type' => 'PhilID',
                'id_number' => '12345678',
                'id_front' => $this->idPhoto('id-front.jpg'),
                'selfie' => $this->idPhoto('selfie.jpg'),
                'terms' => '1',
                'consent_purpose' => true,
                'consent_access' => true,
                'consent_retention' => true,
            ]);

        $verification = $resident->fresh()->latestIdentityVerification();

        $this->actingAs($admin)
            ->get(route('admin.identity.document', ['verification' => $verification, 'side' => 'id_front']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Content-Disposition', 'inline; filename="identity-document.jpg"');

        $this->actingAs($admin)
            ->get(route('admin.identity.document', ['verification' => $verification, 'side' => 'selfie']))
            ->assertOk();
    }

    public function test_resident_can_resubmit_after_rejection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['role' => 'citizen']);

        $submit = fn () => $this->actingAs($resident)->post(route('identity.verify.store'), [
            'id_type' => 'PhilID',
            'id_number' => '12345678',
            'id_front' => $this->idPhoto('id-front.jpg'),
            'selfie' => $this->idPhoto('selfie.jpg'),
            'terms' => '1',
            'consent_purpose' => true,
            'consent_access' => true,
            'consent_retention' => true,
        ]);

        $submit();
        $first = $resident->fresh()->latestIdentityVerification();
        $this->assertSame('pending', $first->status);

        $this->actingAs($admin)->post(route('admin.identity.reject', ['verification' => $first]), [
            'reason' => 'The selfie does not match the ID.',
        ]);
        $this->assertSame('rejected', $first->refresh()->status);

        $submit();
        $second = $resident->fresh()->latestIdentityVerification();
        $this->assertSame('pending', $second->status);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_resident_can_file_a_complaint_after_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->post(route('identity.verify.store'), [
                'id_type' => 'PhilID',
                'id_number' => '12345678',
                'id_front' => $this->idPhoto('id-front.jpg'),
                'selfie' => $this->idPhoto('selfie.jpg'),
                'terms' => '1',
                'consent_purpose' => true,
                'consent_access' => true,
                'consent_retention' => true,
            ]);

        $verification = $resident->fresh()->latestIdentityVerification();

        $this->actingAs($admin)
            ->post(route('admin.identity.approve', $verification))
            ->assertRedirect(route('admin.identity.show', $verification));

        $resident->refresh();
        $this->assertSame('verified', $resident->identity_status);

        $this->actingAs($resident)->get(route('complaints.create'))->assertOk();
    }

    public function test_admin_sidebar_has_identity_verification_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('/admin/identity')
            ->assertSee(__('identity.admin.queue_title'));
    }

    public function test_identity_reset_all_command_resets_residents_to_unverified(): void
    {
        $resident = User::factory()->verifiedCitizen()->create();

        $this->artisan('identity:reset-all', ['--apply' => true])->assertSuccessful();

        $resident->refresh();
        $this->assertSame('unverified', $resident->identity_status);
        $this->assertNull($resident->identity_verified_at);
        $this->assertFalse($resident->isIdentityVerified());
        $this->assertFalse($resident->canFileComplaints());
    }

    private function idPhoto(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::ONE_PIXEL_JPEG));
    }
}
