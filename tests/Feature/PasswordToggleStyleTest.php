<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the auth password-visibility toggle's styles.
 *
 * The settings rebuild (2026-11-12) moved the old settings block out of
 * app.css and deleted the adjacent §5.41 rules with it. The partial and its
 * JavaScript were still in the page, so both eye glyphs rendered in normal
 * flow under the input and the button was never hidden. This test fails if
 * the shared block is dropped again, and pins the alignment contract: the
 * password input is block-level in the toggle wrap and the button is pinned
 * to the field edges with auto block margins, rather than sitting a few
 * pixels low on an inline line box.
 */
class PasswordToggleStyleTest extends TestCase
{
    private function css(): string
    {
        return (string) file_get_contents(resource_path('css/app.css'));
    }

    private function rule(string $selector): ?string
    {
        $css = $this->css();
        $pattern = '/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/s';

        return preg_match($pattern, $css, $m) ? $m[1] : null;
    }

    #[Test]
    public function the_shared_toggle_styles_exist(): void
    {
        $css = $this->css();

        $toggle = $this->rule('.field-toggle');
        $this->assertNotNull($toggle, 'The .field-toggle rule is missing from app.css.');
        $this->assertMatchesRegularExpression('/position:\s*absolute;/', $toggle);
        $this->assertMatchesRegularExpression('/top:\s*0;/', $toggle);
        $this->assertMatchesRegularExpression('/right:\s*2px;/', $toggle);
        $this->assertMatchesRegularExpression('/bottom:\s*0;/', $toggle);
        $this->assertMatchesRegularExpression('/z-index:\s*2;/', $toggle);
        $this->assertMatchesRegularExpression('/width:\s*44px;/', $toggle);
        $this->assertMatchesRegularExpression('/height:\s*44px;/', $toggle);
        $this->assertMatchesRegularExpression('/margin-block:\s*auto;/', $toggle);
        $this->assertMatchesRegularExpression('/pointer-events:\s*none;/', $toggle);
        $this->assertMatchesRegularExpression('/opacity:\s*0;/', $toggle);
        $this->assertMatchesRegularExpression('/visibility:\s*hidden;/', $toggle);

        $visible = $this->rule('.field-toggle.is-visible');
        $this->assertNotNull($visible, 'The .field-toggle.is-visible rule is missing.');
        $this->assertMatchesRegularExpression('/pointer-events:\s*auto;/', $visible);
        $this->assertMatchesRegularExpression('/opacity:\s*1;/', $visible);
        $this->assertMatchesRegularExpression('/visibility:\s*visible;/', $visible);

        $input = $this->rule('.field-wrap:has(.field-toggle) .field-input');
        $this->assertNotNull($input, 'The password input padding rule is missing.');
        $this->assertMatchesRegularExpression('/display:\s*block;/', $input);
        $this->assertMatchesRegularExpression('/padding-right:\s*52px;/', $input);

        $this->assertMatchesRegularExpression('/\.field-underline\s*\{[^}]*pointer-events:\s*none;/', $css);
        $this->assertMatchesRegularExpression('/\.field-toggle__icon\s*\{[^}]*grid-area:\s*1 \/ 1;/', $css);
    }

    #[Test]
    public function the_toggle_partial_stacks_both_glyphs_and_carries_the_data_attributes(): void
    {
        $partial = (string) file_get_contents(
            resource_path('views/auth/partials/password-toggle.blade.php'),
        );

        $this->assertStringContainsString('class="field-toggle"', $partial);
        $this->assertStringContainsString('data-password-visibility-toggle="{{ $target }}"', $partial);
        $this->assertStringContainsString('data-label-show', $partial);
        $this->assertStringContainsString('data-label-hide', $partial);
        $this->assertStringContainsString('field-toggle__icon--show', $partial);
        $this->assertStringContainsString('field-toggle__icon--hide', $partial);
        $this->assertStringContainsString('aria-hidden="true"', $partial);
    }
}