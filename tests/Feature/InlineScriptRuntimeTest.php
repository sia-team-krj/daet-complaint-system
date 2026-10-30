<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pages' inline scripts are executed, not read.
 *
 * Everything else in this suite inspects templates as text. That is the right
 * tool for most things and useless for one in particular: a temporal dead zone.
 * A `const` referenced by a function that is *called* before the declaration is
 * reached throws a ReferenceError at runtime, and the source reads perfectly
 * fine.
 *
 * That is not hypothetical. `updateSummary` in the filing form was defined
 * ~170 lines above the `const latitudeInput` it used, and it was called in
 * between. Optional chaining did not help — `latitudeInput?.value` throws
 * exactly like `latitudeInput.value` when the binding is still in its dead zone.
 * The exception aborted `initializeFilingPage` before it reached
 * `initializeMap`, so the resident saw a form with no map on it and no message
 * explaining why. Every template-level test passed, because every template-level
 * test was looking at text.
 *
 * So these render the real pages and run the real scripts. A thrown error fails
 * the test even when the page still looks complete in the markup.
 */
class InlineScriptRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_the_filing_form_scripts_run_without_throwing(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $html = $this->actingAs($resident)
            ->get(route('complaints.create'))
            ->assertOk()
            ->getContent();

        $this->audit($html, 'complaints/create');
    }

    public function test_the_public_transparency_scripts_run_without_throwing(): void
    {
        $this->audit(
            $this->get(route('transparency'))->assertOk()->getContent(),
            'transparency',
        );
    }

    public function test_the_resident_complaint_scripts_run_without_throwing(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $resident = User::factory()->create(['role' => 'citizen']);

        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'user_id' => $resident->id,
            'latitude' => 14.1264,
            'longitude' => 122.9432,
        ]);

        $this->audit(
            $this->actingAs($resident)
                ->get(route('complaints.show', $complaint))
                ->assertOk()
                ->getContent(),
            'complaints/show',
        );
    }

    public function test_the_staff_complaint_scripts_run_without_throwing(): void
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
            'latitude' => 14.1264,
            'longitude' => 122.9432,
        ]);

        $this->audit(
            $this->actingAs($staff)
                ->get(route('staff.complaints.show', $complaint))
                ->assertOk()
                ->getContent(),
            'staff/complaints/show',
        );
    }

    /**
     * The "Ready to submit" row names a place, not a coordinate.
     *
     * This drives the form as a resident does — drops a pin, reads the row — for
     * every barangay in the table, and then checks no coordinate ever reaches
     * it. Reading the markup could never have found the version of this that
     * displayed `14.12640, 122.94320`: the markup contained precisely what it
     * was written to contain.
     */
    public function test_the_ready_to_submit_row_names_the_barangay(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $html = $this->actingAs($resident)
            ->get(route('complaints.create'))
            ->assertOk()
            ->getContent();

        $this->runAudit('filing-summary-audit.mjs', $html, 'the Ready to submit row');
    }

    /**
     * The form asks for the barangay, and does not guess one.
     *
     * Guessing is what this replaced. A single reference point per barangay is
     * wrong wherever a point sits further from its own barangay's reference point
     * than from a neighbour's, and Daet is 10 km across, so it showed residents
     * the wrong area for their own home. OpenStreetMap has one label point per
     * Daet barangay and no boundaries, so there is no offline source better than
     * asking — and the resident already told us at registration.
     */
    public function test_the_form_asks_for_the_barangay_rather_than_guessing_one(): void
    {
        $resident = User::factory()->create([
            'role' => 'citizen',
            'barangay' => 'Borabod',
        ]);

        $html = $this->actingAs($resident)
            ->get(route('complaints.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'name="barangay"',
            $html,
            'The form does not ask which barangay, so the only alternative is a guess.',
        );

        // Prefilled from the account, because the resident said it there.
        $this->assertMatchesRegularExpression(
            '/id="barangay"[^>]*value="Borabod"|value="Borabod"[^>]*id="barangay"/s',
            $html,
            'The barangay field is not prefilled from the resident\'s own registration.',
        );

        // And no client-side guess is left behind, because it would be a second,
        // worse answer to a question the form now asks properly.
        $this->assertStringNotContainsString(
            'nearestBarangay',
            $html,
            'The form still derives a barangay from the pin, which is the inaccurate behaviour '
                .'this replaced.',
        );

        $this->assertStringNotContainsString(
            'barangay-reference-data',
            $html,
            'The form still ships the reference-point table for a lookup nothing performs.',
        );
    }

    /**
     * Render a page's scripts in jsdom and fail on anything they throw.
     *
     * The failure message is the point. A bare "assertion failed" on a template
     * test sends you looking at the wrong file; "ReferenceError:
     * latitudeInput" sends you to the exact line.
     */
    private function audit(string $html, string $label): void
    {
        $this->runAudit('inline-script-audit.mjs', $html, "the {$label} page");
    }

    private function runAudit(string $script, string $html, string $label): void
    {
        $auditScript = base_path("tests/Support/js/{$script}");

        $this->assertFileExists(
            $auditScript,
            "The {$script} audit is missing, so nothing on this page is ever executed.",
        );

        $htmlFile = tempnam(sys_get_temp_dir(), 'daet-audit-').'.html';
        file_put_contents($htmlFile, $html);

        try {
            $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $process = proc_open(
                ['node', $auditScript, $htmlFile],
                $descriptors,
                $pipes,
                base_path(),
            );

            $this->assertIsResource($process, 'Could not start node to audit the page.');

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);

            $this->assertSame(
                0,
                $status,
                "An audit of {$label} failed. An exception here aborts the rest of the page's "
                    ."initialisation, so the symptom usually shows up somewhere else entirely — a "
                    ."missing map, a dead button, a row that never fills in — with nothing on the "
                    ."page saying why.\n\n"
                    .trim($stdout)."\n".trim($stderr),
            );
        } finally {
            @unlink($htmlFile);
        }
    }
}
