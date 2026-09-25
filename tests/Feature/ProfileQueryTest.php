<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_profile_resolution_metric_qualifies_both_joined_timestamps(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $resident = User::factory()->create(['role' => 'citizen']);
        $complaint = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'status' => ComplaintStatus::Resolved->value,
        ]);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $staff->id,
            'previous_status' => ComplaintStatus::InProgress->value,
            'new_status' => ComplaintStatus::Resolved->value,
            'comment' => 'Resolution recorded for the profile metric.',
        ]);

        $this->actingAs($staff)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Profile Settings')
            ->assertSee('Handled')
            ->assertSee('Avg. Days');
    }
}
