<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the page-level overflow rules.
 *
 * `overflow-x: hidden` on an ancestor turns that ancestor into a scroll
 * container, which silently breaks `position: sticky` for everything inside it
 * and stops pages that rely on document scrolling — the register form on a
 * phone among them. `overflow-x: clip` contains the overflow without creating a
 * scroll container, so these two elements must always use it.
 *
 * This asserts the source stylesheet rather than the compiled bundle so the
 * guard survives a build and fails on the file a developer actually edits.
 */
class PageOverflowTest extends TestCase
{
    public function test_body_and_main_clip_horizontally_instead_of_scrolling(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertNotFalse($css, 'Could not read resources/css/app.css.');

        foreach (['body', 'main'] as $selector) {
            $block = $this->ruleBlock($css, $selector);

            $this->assertNotNull(
                $block,
                "No `{$selector} {{ ... }}` rule found in app.css.",
            );

            $this->assertMatchesRegularExpression(
                '/overflow-x:\s*clip\s*;/',
                $block,
                "`{$selector}` must clip horizontal overflow with `overflow-x: clip`. "
                    .'`hidden` makes it a scroll container, which breaks sticky descendants '
                    .'and document scrolling on the register page.',
            );

            $this->assertMatchesRegularExpression(
                '/overflow-y:\s*visible\s*;/',
                $block,
                "`{$selector}` must state `overflow-y: visible` so the x/y pair stays valid.",
            );
        }
    }

    public function test_the_register_page_scrolls_the_document_at_every_width_like_the_home_page(): void
    {
        $register = (string) file_get_contents(resource_path('views/auth/register.blade.php'));
        $home = (string) file_get_contents(resource_path('views/pages/home/guest.blade.php'));

        // The working reference: a static, auto-height wrapper that never becomes
        // a scroll container of its own.
        $this->assertMatchesRegularExpression(
            '/\.lp-root\s*\{[^}]*width:\s*100%/s',
            $home,
            'The home page root wrapper is the scrolling reference for this fix.',
        );
        $this->assertStringNotContainsString('position: fixed', $this->ruleFor($register, 'auth-root'));
        $this->assertMatchesRegularExpression(
            '/\.auth-root\s*\{[^}]*overflow:\s*visible\s*;/s',
            $register,
            'The register wrapper must use `overflow: visible` so it never becomes a scroll container.',
        );

        // A width-dependent scroll strategy is the original defect: the panel
        // used to be position:fixed with its own scrollbar above 768px and
        // document-scrolling below it.
        $this->assertStringNotContainsString(
            'overflow-y: auto',
            $register,
            'The register page must not run its own scrollbar; the document scrolls.',
        );

        // The first field has to clear the navbar, which is fixed at all widths.
        $this->assertMatchesRegularExpression(
            '/\.auth-root\s*\{[^}]*padding-top:\s*calc\(var\(--navbar-height\)/s',
            $register,
            'The register wrapper needs top padding to clear the fixed navbar.',
        );
    }

    private function ruleFor(string $css, string $class): string
    {
        $pattern = '/\.'.preg_quote($class, '/').'\s*\{([^}]*)\}/';

        return preg_match($pattern, $css, $matches) === 1 ? $matches[0] : '';
    }

    private function ruleBlock(string $css, string $selector): ?string
    {
        $pattern = '/(?:^|\n)'.preg_quote($selector, '/').'\s*\{([^}]*)\}/';

        return preg_match($pattern, $css, $matches) === 1 ? $matches[1] : null;
    }
}
