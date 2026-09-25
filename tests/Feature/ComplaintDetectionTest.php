<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use App\Services\ComplaintDetectionService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintDetectionTest extends TestCase
{
    use RefreshDatabase;

    private ComplaintDetectionService $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = app(ComplaintDetectionService::class);
        $this->seed(DepartmentSeeder::class);
    }

    public function test_spam_is_flagged_without_deleting_the_complaint(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = Complaint::create([
            'user_id' => $resident->id,
            'department_id' => Department::where('code', 'GSO')->firstOrFail()->id,
            'category' => 'others',
            'title' => 'FREE MONEY click here now!!!',
            'description' => 'FREE MONEY click here https://spam.example.com guaranteed profit',
            'urgency' => 'Low',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
        ]);

        $result = $this->detector->evaluate($complaint);

        $this->assertSame('spam', $result['spam_status']);
        $this->assertGreaterThanOrEqual(60, $result['spam_score']);
        $this->assertNotEmpty($result['reasons']);
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'spam_status' => 'spam']);
    }

    public function test_similar_complaints_are_linked_without_being_blocked(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $first = Complaint::create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'category' => 'road_damage',
            'title' => 'Large pothole near the market entrance',
            'description' => 'A large pothole near the market entrance needs attention before it becomes dangerous.',
            'address_text' => 'Barangay VI, Daet',
            'urgency' => 'High',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
        ]);
        $this->detector->evaluate($first);

        $second = Complaint::create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'category' => 'road_damage',
            'title' => 'Large pothole near the market entrance',
            'description' => 'Another large pothole at the market entrance is dangerous for motorcycles.',
            'address_text' => 'Barangay VI, Daet',
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
        ]);
        $result = $this->detector->evaluate($second);

        $this->assertSame($first->id, $result['duplicate_of_id']);
        $this->assertGreaterThanOrEqual(0.65, $result['similarity_score']);
        $this->assertDatabaseHas('complaints', ['id' => $second->id, 'duplicate_of_id' => $first->id]);
    }

    public function test_public_and_private_visibility_are_separate(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);
        $department = Department::where('code', 'ENG')->firstOrFail();

        Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'is_public' => true,
        ]);
        Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'is_public' => false,
        ]);

        $this->assertSame(1, Complaint::public()->count());
        $this->assertSame(2, Complaint::count());
    }

    public function test_demo_seeder_creates_public_private_spam_and_similar_examples(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(8, Complaint::count());
        $this->assertGreaterThan(0, Complaint::public()->count());
        $this->assertGreaterThan(0, Complaint::where('is_public', false)->count());
        $this->assertSame(1, Complaint::where('title', 'FREE MONEY click here now!!!')->where('spam_status', 'spam')->count());
        $this->assertGreaterThan(0, Complaint::whereNotNull('duplicate_of_id')->count());
    }
}
