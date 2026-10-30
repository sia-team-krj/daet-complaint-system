<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Guards the scheme/auth round trip that every login and registration depends on.
 *
 * The failure this prevents is subtle and total. Forcing https from APP_URL while
 * the browser is on plain http means a form rendered over http posts to https://,
 * which is a different schemeful origin. The session cookie is SameSite=lax, so
 * the browser withholds it on that cross-site POST, CSRF cannot be validated, and
 * the request dies on 419 — with no way for the user to sign in at all.
 */
class AuthSessionSchemeTest extends TestCase
{
    private const PLAIN_HTTP = 'http://plain-http.test/login';

    protected function setUp(): void
    {
        parent::setUp();

        // Providers are booted once when the test application is created, and at
        // that point the container's "request" is a synthetic one derived from
        // APP_URL — which is https here. That leaves URL::forceScheme('https')
        // latched for the whole test and makes every request look https no
        // matter what URL it asks for, which would hide exactly the regression
        // these tests exist to catch.
        //
        // Clearing the latch and re-running the provider against a real
        // plain-HTTP request reproduces what AppServiceProvider::boot() actually
        // sees when a resident opens the site over http.
        URL::forceScheme(null);
        $this->app->instance('request', Request::create(self::PLAIN_HTTP));
        (new AppServiceProvider($this->app))->boot();
    }

    public function test_the_provider_does_not_force_https_from_app_url(): void
    {
        // APP_URL is https in this project. The provider must ignore it.
        $this->assertStringStartsWith('https://', (string) config('app.url'));

        $this->bootProviderFor(Request::create(self::PLAIN_HTTP));

        $this->assertNull(
            $this->forcedScheme(),
            'A plain-HTTP request must not have https forced on it. Doing so makes '
                .'the form post to a different schemeful origin, the SameSite=lax '
                .'session cookie is withheld, and login dies on 419.',
        );

        $this->assertStringStartsWith('http://plain-http.test', $this->loginFormAction());
    }

    public function test_the_provider_forces_https_only_for_a_genuinely_secure_request(): void
    {
        $this->bootProviderFor(Request::create('https://secure.test/login'));

        $this->assertSame('https://', $this->forcedScheme());
    }

    public function test_a_real_plain_http_request_is_answered_with_an_http_form_action(): void
    {
        $response = $this->get(self::PLAIN_HTTP);

        $response->assertOk();

        $this->assertStringStartsWith(
            'http://plain-http.test',
            $this->formAction($response->getContent()),
            'A plain-HTTP visitor must be given an http:// form action so the POST '
                .'returns to the same origin and the session cookie is sent.',
        );

        $response->assertDontSee((string) config('app.url'), false);
    }

    public function test_generated_urls_follow_the_forwarded_scheme_behind_a_tls_proxy(): void
    {
        // Tailscale serve terminates TLS and forwards over http, so the scheme
        // only ever arrives in X-Forwarded-Proto. Trusting that header is what
        // keeps https visitors on https; without it the same cookie problem
        // returns in mirror image.
        $response = $this->get('http://proxied.test/login', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'leaf-1.tail05a1ab.ts.net',
        ]);

        $response->assertOk();

        $this->assertStringStartsWith(
            'https://leaf-1.tail05a1ab.ts.net',
            $this->formAction($response->getContent()),
        );
    }

    public function test_the_session_cookie_is_not_marked_secure_on_a_plain_http_request(): void
    {
        $cookie = $this->get(self::PLAIN_HTTP)->getCookie(config('session.cookie'));

        $this->assertNotNull($cookie, 'No session cookie was issued.');
        $this->assertFalse(
            $cookie->isSecure(),
            'A Secure cookie is dropped by the browser over http, which would '
                .'strip the session from the POST and cause a 419.',
        );
    }

    public function test_a_csrf_failure_returns_to_the_form_with_a_reason_instead_of_a_419_page(): void
    {
        // CSRF verification is skipped while the app env is 'testing', so it has
        // to be switched off to exercise the failure path at all.
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $response = $this->from(self::PLAIN_HTTP)->post(self::PLAIN_HTTP, [
                '_token' => 'deliberately-wrong-token',
                'email' => 'resident@example.com',
                'password' => 'correct-horse-battery',
            ]);
        } finally {
            $this->app['env'] = $original;
        }

        // The handler in bootstrap/app.php is keyed on HttpExceptionInterface
        // because Laravel rewrites TokenMismatchException into HttpException(419)
        // before render callbacks run. A 419 status here means that keying
        // regressed and the raw error page is back.
        $response->assertRedirect(self::PLAIN_HTTP);
        $response->assertSessionHasErrors('session');
        $response->assertSessionHasInput('email', 'resident@example.com');

        // The message has to be visible, not just flashed. The auth views render
        // $errors and none of them read session('error'), so a failure routed
        // through the wrong channel would look like the form did nothing.
        $response->assertSessionHasErrors([
            'session' => 'Your session expired. Please try again.',
        ]);

        $this->assertArrayNotHasKey(
            'password',
            $this->app['session.store']->get('_old_input', []),
        );
    }

    public function test_a_csrf_failure_lands_on_the_form_not_the_home_page(): void
    {
        // Found in the server log: with no Referer and the session already
        // replaced, back() resolved to "/" and dumped someone mid-registration
        // on the home page with a message about a form they could no longer see.
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $this->post('/register', [
                '_token' => 'deliberately-wrong-token',
                'first_name' => 'Half',
                'last_name' => 'Registered',
                'email' => 'half.registered@daetlistens.gov.ph',
            ])->assertRedirect('/register');

            $this->post('/login', [
                '_token' => 'deliberately-wrong-token',
                'email' => 'someone@daetlistens.gov.ph',
            ])->assertRedirect('/login');
        } finally {
            $this->app['env'] = $original;
        }
    }

    public function test_a_csrf_failure_will_not_follow_a_referer_pointing_off_site(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            // The referer is used to work out where to return someone, so it
            // must never be trusted as a redirect target.
            $this->call(
                'POST',
                '/login',
                ['_token' => 'deliberately-wrong-token'],
                [],
                [],
                ['HTTP_REFERER' => 'https://evil.example.com/steal'],
            )->assertRedirect('/login');
        } finally {
            $this->app['env'] = $original;
        }
    }

    private function bootProviderFor(Request $request): void
    {
        $this->app->instance('request', $request);

        (new AppServiceProvider($this->app))->boot();
    }

    private function forcedScheme(): ?string
    {
        $property = (new \ReflectionClass(URL::getFacadeRoot()))
            ->getProperty('forceScheme');
        $property->setAccessible(true);

        return $property->getValue(URL::getFacadeRoot());
    }

    private function loginFormAction(): string
    {
        return $this->formAction($this->get('/login')->getContent());
    }

    private function formAction(?string $html): string
    {
        $this->assertNotNull($html);

        $matched = preg_match('/<form[^>]*method="POST"[^>]*action="([^"]+)"/i', $html, $m);

        $this->assertSame(1, $matched, 'No POST form action found in the response.');

        return html_entity_decode($m[1]);
    }
}
