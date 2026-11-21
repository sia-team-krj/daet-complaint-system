<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\IdentityHashService;
use App\Services\IdentityVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityHashServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['identity.hash_key' => 'test-identity-hash-key']);
    }

    public function test_hash_normalizes_numbers(): void
    {
        $service = app(IdentityHashService::class);

        $this->assertSame($service->hash('PhilID', ' ABC-123.45 '), $service->hash('PhilID', 'abc12345'));
    }

    public function test_hash_differs_by_id_type(): void
    {
        $service = app(IdentityHashService::class);

        $this->assertNotSame($service->hash('PhilID', 'ABC12345'), $service->hash('PHTIN', 'ABC12345'));
    }

    public function test_duplicate_for_finds_same_id_hash_on_other_accounts(): void
    {
        $hash = app(IdentityHashService::class)->hash('PhilID', '12345678');
        $resident = User::factory()->create();
        $resident->forceFill(['identity_status' => 'verified', 'identity_verified_at' => now()])->save();
        $resident->identityVerifications()->create([
            'status' => \App\Models\IdentityVerification::APPROVED,
            'id_type' => 'PhilID',
            'id_number_hash' => $hash,
            'submitted_at' => now(),
        ]);
        $other = User::factory()->create();

        $duplicate = app(IdentityVerificationService::class)->duplicateFor($hash, $other->id);

        $this->assertNotNull($duplicate);
        $this->assertSame($resident->id, $duplicate->id);
    }
}
