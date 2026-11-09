<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The error page is the one surface that has to render when something else is
 * broken. These tests guard the parts that are easy to lose: the custom view is
 * actually selected, it keeps the portal shell out of the database-backed
 * chrome, it has exactly one h1, and it is translated.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_page_renders_the_custom_error_shell(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertNotFound();
        $response->assertSee('data-error-page', false);
        $response->assertSee(__('errors.404.title'));
        $response->assertSee(__('errors.action_home'));
        $response->assertSee('class="error-footer"', false);

        $this->assertSame(
            1,
            substr_count($response->getContent(), '<h1'),
            'The error page must have exactly one h1.',
        );
    }

    public function test_the_error_page_follows_the_active_locale(): void
    {
        // The locale switcher POST sets the long-lived cookie that
        // SetLocale reads on the next request; withSession() alone is not
        // picked up by the middleware for unmatched (404) routes.
        $this->post(route('locale.update'), ['locale' => 'fil']);

        $response = $this->get('/this-page-does-not-exist');

        $response->assertNotFound();
        $response->assertSee(__('errors.404.title', [], 'fil'));
        $response->assertDontSee(__('errors.404.title', [], 'en'));
    }

    public function test_forbidden_and_server_errors_use_their_own_copy(): void
    {
        Route::get('/_test/forbidden', fn () => abort(403));
        Route::get('/_test/server-error', fn () => abort(500));

        $this->get('/_test/forbidden')
            ->assertForbidden()
            ->assertSee(__('errors.403.title'));

        $this->get('/_test/server-error')
            ->assertStatus(500)
            ->assertSee(__('errors.500.title'));
    }

    public function test_the_error_page_does_not_render_database_backed_chrome(): void
    {
        $html = $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->getContent();

        $this->assertStringNotContainsString('class="site-footer"', $html);
        $this->assertStringNotContainsString('id="navbar"', $html);
        $this->assertStringNotContainsString('wire:', $html);
    }
}
