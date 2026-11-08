<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the two standing legal documents.
 *
 * These pages are the only place where the portal describes its own data
 * handling in words, which makes them the easiest thing in the repository to
 * let quietly go out of date. A behaviour changes, the code is right, and the
 * published promise is quietly wrong — a resident is told their photo will not
 * be published and it is, or told the audit trail cannot be edited and it can.
 *
 * So the claims are asserted against the system that has to keep them.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_documents_are_publicly_reachable_without_an_account(): void
    {
        foreach (['privacy', 'terms'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('<h1', false);
        }
    }

    public function test_the_privacy_policy_names_the_categories_the_system_actually_collects(): void
    {
        $html = $this->prose($this->get(route('privacy'))->assertOk()->getContent());

        // Each of these is a real column or storage behaviour, not a generic line.
        foreach ([
            'email address',
            'mobile number',
            'barangay',
            'photographs',
            'coordinates',
            'IP address',
            'audit trail',
        ] as $claim) {
            $this->assertStringContainsString(
                $claim,
                $html,
                "The Privacy Policy must disclose that the portal collects {$claim}.",
            );
        }
    }

    /**
     * The one claim that would cause real harm if it stopped being true.
     *
     * The public register is built from `display_alias` and aggregates the map
     * to the barangay. If either regressed, the portal would be publishing a
     * complainant's legal name or their exact coordinates while the policy
     * promised it does neither.
     */
    public function test_the_privacy_policy_does_not_promise_anonymity_the_register_cannot_deliver(): void
    {
        $html = $this->prose($this->get(route('privacy'))->assertOk()->getContent());

        $this->assertStringContainsString('display alias', $html);
        $this->assertStringContainsString('barangay level', $html);
        $this->assertStringContainsString('not a precise coordinate', $html);

        // And the register itself must be showing the alias, not the name.
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();

        $resident = User::factory()->create([
            'role' => 'citizen',
            'display_alias' => 'Concerned resident',
        ]);

        $complaint = \App\Models\Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'is_public' => true,
        ]);

        $register = $this->get(route('transparency'))->assertOk()->getContent();

        // The register list does not name the reporter at all; the alias
        // appears only on the public detail page.
        $this->assertStringNotContainsString(
            'Concerned resident',
            $register,
            'The public register list must not show the reporter at all.',
        );
        $this->assertStringNotContainsString(
            $resident->first_name.' '.$resident->last_name,
            $register,
            'The public register must not show the complainant\'s legal name.',
        );

        $detail = $this->get(route('transparency.complaints.show', $complaint))->assertOk()->getContent();

        $this->assertStringContainsString(
            'Concerned resident',
            $detail,
            'The public detail page must show the display alias, as the policy promises.',
        );
        $this->assertStringNotContainsString(
            $resident->first_name.' '.$resident->last_name,
            $detail,
            'The public detail page must not show the complainant\'s legal name.',
        );
    }

    public function test_the_terms_state_the_rules_the_portal_actually_enforces(): void
    {
        $html = $this->prose($this->get(route('terms'))->assertOk()->getContent());

        // Upload limits come from the controller's validation rules; the ticket
        // prefix comes from the model's generator; visibility starts private.
        $this->assertStringContainsString('up to five', $html);
        $this->assertStringContainsString('DLT-', $html);
        $this->assertStringContainsString('starts private', $html);
        $this->assertStringContainsString('5 MB', $html);
    }

    /**
     * Terms must not promise a service level the code cannot measure.
     *
     * There is no SLA mechanism anywhere in the application — no escalation
     * timer, no service-level record — so any deadline written here would be a
     * commitment the Municipality cannot keep or evidence.
     */
    public function test_the_terms_do_not_promise_a_response_time(): void
    {
        $html = $this->prose($this->get(route('terms'))->assertOk()->getContent());

        $this->assertStringContainsString(
            'do not promise that a complaint will be upheld',
            $html,
            'The Terms must state plainly that no particular response time is guaranteed.',
        );

        foreach (['within 24 hours', 'within 48 hours', 'within 72 hours', 'business days'] as $promise) {
            $this->assertStringNotContainsString(
                $promise,
                $html,
                "The Terms promise a response time ('{$promise}') that the system does not measure.",
            );
        }
    }

    public function test_both_documents_name_the_governing_laws(): void
    {
        $privacy = $this->prose($this->get(route('privacy'))->assertOk()->getContent());
        $this->assertStringContainsString('10173', $privacy);
        $this->assertStringContainsString('Data Privacy Act', $privacy);

        $terms = $this->prose($this->get(route('terms'))->assertOk()->getContent());
        $this->assertStringContainsString('10173', $terms);
        $this->assertStringContainsString('11032', $terms);
    }

    /** Consent must be given to something readable, not to a dead link. */
    public function test_the_registration_consent_links_to_both_documents(): void
    {
        $register = (string) file_get_contents(
            resource_path('views/auth/register.blade.php'),
        );

        $this->assertStringContainsString("route('terms')", $register);
        $this->assertStringContainsString("route('privacy')", $register);

        // The placeholder that used to sit here was the whole reason a policy
        // nobody could reach looked like a policy that existed.
        $this->assertStringNotContainsString(
            'href="#">Privacy Policy',
            $register,
            'The registration consent still links to a dead anchor.',
        );
    }

    /**
     * The documents live in the footer, not the navbar.
     *
     * They were in the navbar first and were moved out: two more items crowded
     * the links residents actually use and pushed the account controls into the
     * compact menu sooner. This asserts the decision in both directions — the
     * footer must carry them, and the navbar must not — so the placement cannot
     * quietly revert.
     */
    public function test_the_footer_carries_both_documents_and_the_navbar_does_not(): void
    {
        $footer = (string) file_get_contents(
            resource_path('views/layouts/partials/site-footer.blade.php'),
        );

        foreach (['privacy', 'terms'] as $route) {
            $this->assertStringContainsString(
                "route('{$route}')",
                $footer,
                "The shared footer does not link to route({$route}).",
            );
        }

        foreach ([
            'layouts/guest-navbar.blade.php',
            'layouts/navbar.blade.php',
        ] as $partial) {
            $navbar = (string) file_get_contents(resource_path("views/{$partial}"));

            $this->assertStringNotContainsString(
                "route('terms')",
                $navbar,
                "{$partial} links the Terms in the navbar again. They belong in the footer.",
            );
            $this->assertStringNotContainsString(
                "route('privacy')",
                $navbar,
                "{$partial} links the Privacy Policy in the navbar again. It belongs in the footer.",
            );
        }
    }

    /**
     * Exactly one footer, or the legal links appear twice.
     *
     * Six pages render the footer themselves because it has to sit inside their
     * own full-height wrapper, and declare `@section('site-footer')` so the shell
     * does not add a second. That section has to hold something: `@hasSection`
     * compiles to a non-empty content check, so an empty marker is ignored and
     * the footer renders twice. This is the exact failure, so it is asserted.
     */
    public function test_no_page_renders_the_footer_twice(): void
    {
        $pages = [
            ['/', 'guest'],
            ['/login', 'guest'],
            ['/register', 'guest'],
            ['/forgot-password', 'guest'],
            ['/transparency', 'guest'],
            ['/privacy', 'guest'],
            ['/terms', 'guest'],
        ];

        foreach ($pages as [$path, $layout]) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(
                1,
                substr_count($html, 'class="site-footer"'),
                "{$path} does not render exactly one site footer.",
            );
        }
    }

    /**
     * A footer on the guest pages is not notice if it is missing where a signed-in
     * resident is already looking — the dashboard and the workspaces.
     */
    public function test_authenticated_pages_render_the_footer_too(): void
    {
        $this->seed(DepartmentSeeder::class);

        $resident = User::factory()->create([
            'role' => 'citizen',
            'email_verified_at' => now(),
        ]);

        $html = $this->actingAs($resident)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="site-footer"', $html);
        $this->assertStringContainsString(route('terms'), $html);
        $this->assertStringContainsString(route('privacy'), $html);
    }

    /**
     * The rendered text of a page, with whitespace collapsed.
     *
     * These are prose pages written as wrapped Blade, so a phrase like "does not
     * promise" is split across a source line break and never appears literally in
     * the HTML. Asserting on the raw markup would make every assertion here fail
     * for a reason that has nothing to do with what is being guarded.
     */
    private function prose(string $html): string
    {
        $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/si', ' ', $html);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5);

        // `&nbsp;` decodes to U+00A0, which `\s` does not match without the /u
        // modifier. Left in place it silently breaks every "5 MB" style claim.
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}