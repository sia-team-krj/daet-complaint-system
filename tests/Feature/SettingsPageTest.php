<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The settings page.
 *
 * Two things it has to get right. The save: the old page's "Save changes"
 * button in the unsaved bar was wired to a form nested inside the form it
 * pointed at, so it looked like it would save and did nothing — and because
 * the form was never marked clean, every later navigation asked the resident
 * whether they wanted to leave. And the name: a name that carries digits or
 * stray symbols is not a name, and the person who carries it on the public
 * register is the one who lives with it.
 */
class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_the_page_switches_between_sections_with_the_tabs(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'email' => 'resident.'.fake()->unique()->numerify('##########').'@daetlistens.gov.ph',
        ]);

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertOk();
        $response->assertSee('settings-tabs', false);
        $response->assertSee('data-settings-tab="profile"', false);
        $response->assertSee('data-settings-tab="security"', false);
        $response->assertSee('data-settings-tab="notifications"', false);
        $response->assertSee('data-settings-tab="preferences"', false);
        $response->assertSee('data-settings-tab="privacy"', false);

        // One section at a time: the profile card is here, the security one
        // is not until its tab is clicked.
        $response->assertSee('id="tab-profile"', false);
        $response->assertDontSee('id="tab-security"', false);

        $response = $this->actingAs($user)->get(route('settings', ['tab' => 'security']));

        $response->assertOk();
        $response->assertSee('id="tab-security"', false);
        $response->assertDontSee('id="tab-profile"', false);
    }

    public function test_a_save_comes_back_to_the_tab_it_was_made_in(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'email' => 'resident.'.fake()->unique()->numerify('##########').'@daetlistens.gov.ph',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
        ]);

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('settings', ['tab' => 'profile']));
        $response->assertSessionHas('status_for', 'profile');
    }

    public function test_the_page_script_behaves(): void
    {
        $auditScript = base_path('tests/Support/js/settings-audit.mjs');

        $this->assertFileExists(
            $auditScript,
            "The {$auditScript} audit is missing, so the settings page is never executed.",
        );

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(['node', $auditScript], $descriptors, $pipes, base_path());

        $this->assertIsResource($process, 'Could not start node to audit the settings page.');

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        $this->assertSame(
            0,
            $status,
            "The settings page audit failed.\n\n".trim($stdout)."\n".trim($stderr),
        );
    }

    public function test_a_name_with_digits_is_refused(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'email' => 'pedro.'.fake()->unique()->numerify('##########').'@daetlistens.gov.ph',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
        ]);

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'first_name' => 'Juan123',
            'last_name' => 'Dela Cruz',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('first_name');

        $this->assertSame('Pedro', $user->fresh()->first_name);
    }

    public function test_a_name_with_symbols_is_refused(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'email' => 'pedro.'.fake()->unique()->numerify('##########').'@daetlistens.gov.ph',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
        ]);

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'first_name' => '∑∑∑',
            'last_name' => 'Dela Cruz',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('first_name');

        $this->assertSame('Pedro', $user->fresh()->first_name);
    }

    public function test_a_real_name_is_accepted(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'email' => 'pedro.'.fake()->unique()->numerify('##########').'@daetlistens.gov.ph',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
        ]);

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'first_name' => 'Juan Carlo',
            'last_name' => 'Dela Cruz',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame('Juan Carlo', $user->fresh()->first_name);
        $this->assertSame('Dela Cruz', $user->fresh()->last_name);
    }

    public function test_a_hyphen_and_a_dot_are_allowed(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'email' => 'pedro.'.fake()->unique()->numerify('##########').'@daetlistens.gov.ph',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
        ]);

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'first_name' => 'Maria-St.',
            'last_name' => 'Dela Cruz',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame('Maria-St.', $user->fresh()->first_name);
    }
}
