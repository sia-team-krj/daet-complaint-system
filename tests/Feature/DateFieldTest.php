<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The date field (docs/design-system.md §5.42).
 *
 * A native date input alone is two problems in one: it renders as bare
 * "dd/mm/yyyy" text until it is filled, and the format it shows follows the
 * browser's locale rather than ours — so the same office reads 12/15/2026 on
 * one device and 15/12/2026 on another. The field is therefore a text input
 * masked to dd/mm/yyyy with a native date input beside it: the calendar opens
 * the OS picker, and the server receives the value.
 *
 * The behaviour is executed, not read: a mask that lets 31/02 through, or a
 * typed date that never reaches the server, is exactly the kind of thing that
 * reads perfectly well in the source.
 */
class DateFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_the_date_field_behaves(): void
    {
        $auditScript = base_path('tests/Support/js/date-field-audit.mjs');

        $this->assertFileExists(
            $auditScript,
            "The {$auditScript} audit is missing, so the date field is never executed.",
        );

        try {
            $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $process = proc_open(
                ['node', $auditScript],
                $descriptors,
                $pipes,
                base_path(),
            );

            $this->assertIsResource($process, 'Could not start node to audit the date field.');

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);

            $this->assertSame(
                0,
                $status,
                "The date field audit failed.\n\n".trim($stdout)."\n".trim($stderr),
            );
        } finally {
            @unlink($auditScript.'.tmp');
        }
    }

    public function test_the_hold_panel_uses_the_date_field(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
            'can_verify' => true,
        ]);

        $complaint = Complaint::create([
            'ticket_id' => 'COMP-2026-00001',
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => $staff->department_id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => 'In Progress',
            'review_status' => 'verified',
            'verified_at' => now(),
            'is_public' => true,
            'content_fingerprint' => 'audit-fingerprint',
        ]);

        $response = $this->actingAs($staff)->get(route('staff.complaints.show', $complaint));

        $response->assertOk();

        // The visible field is a text input in our own format...
        $response->assertSee('placeholder="dd/mm/yyyy"', false);
        $response->assertSee('data-date-field-display', false);

        // ...the calendar is a native date input that carries the value...
        $response->assertSee('type="date" name="hold_expected_resume_at"', false);

        // ...and the old bare date input is gone.
        $response->assertDontSee('type="date" id="hold-expected-resume-at"', false);
    }
}
