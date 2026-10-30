<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the notification dropdown's real rendered width.
 *
 * This exists because of a bug that was invisible to inspection. The panel
 * carries two classes — `nav-dropdown` (220px) and `nav-notification__dropdown`.
 * Both selectors are a single class, so they tie on specificity and the one
 * declared later in the stylesheet wins. The base rule is declared further down
 * the file, so a `width: 520px` written on the notification rule was silently
 * discarded and the panel rendered at 220px.
 *
 * Grepping the compiled stylesheet for the intended width proves nothing here:
 * the declaration was present in the output and still did not apply. This test
 * resolves the cascade the way a browser would and asserts the value that
 * actually lands.
 */
class NotificationDropdownWidthTest extends TestCase
{
    private const EXPECTED_WIDTH = '460px';

    public function test_the_notification_dropdown_width_is_not_overridden_by_the_base_dropdown(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $base = $this->declaredWidth($css, '.nav-dropdown');
        $panel = $this->declaredWidth($css, '.nav-dropdown.nav-notification__dropdown');

        $this->assertNotNull($panel, 'The qualified notification dropdown rule is missing from app.css.');

        // The failure this guards against: both rules match the same element and
        // the base one wins, so the panel falls back to 220px.
        $this->assertNotSame(
            $base,
            $panel,
            'The notification panel must not resolve to the base .nav-dropdown width. '
                .'It shares classes with the base rule, so an unqualified selector ties '
                .'on specificity and loses on source order.',
        );

        $this->assertSame(self::EXPECTED_WIDTH, $panel);
    }

    public function test_the_bell_marks_the_panel_with_both_classes(): void
    {
        // The cascade only matters because both classes are present. If the
        // panel ever stops carrying nav-dropdown, the test above would be
        // asserting against a rule that no longer competes for anything.
        $bell = (string) file_get_contents(
            resource_path('views/layouts/partials/notification-bell.blade.php'),
        );

        $this->assertMatchesRegularExpression(
            '/class="[^"]*\bnav-dropdown\b[^"]*\bnav-notification__dropdown\b[^"]*"/',
            $bell,
            'The notification panel must carry both the base and the notification class.',
        );
    }

    public function test_the_panel_still_never_exceeds_the_viewport(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        // A 460px panel on a phone must clamp, not overflow.
        $this->assertMatchesRegularExpression(
            '/\.nav-dropdown\.nav-notification__dropdown\s*\{[^}]*max-width:\s*calc\(100vw\s*-\s*\d+px\)/s',
            $css,
            'The panel needs a viewport-relative max-width so it cannot overflow a narrow screen.',
        );
    }

    /**
     * Guards the phone layout, where a clamp alone is not enough.
     *
     * `max-width: calc(100vw - 24px)` bounds the panel, but the panel was
     * absolutely positioned inside `.nav-notification` — a 34px wrapper that the
     * compact cluster pushes to the right edge of the bar. The clamp was honest
     * and the panel still rendered off the left of the screen, because `right: 0`
     * measured to the bell rather than to the viewport, so the 460px width grew
     * leftwards from a point 150px in from the edge.
     *
     * Releasing the wrapper with `position: static` promotes #navbar, which is
     * `position: fixed` and full width, to the containing block. `right` is then
     * measured against the screen, and the panel lands inside the viewport.
     */
    public function test_the_panel_is_measured_against_the_screen_and_not_the_bell_on_a_phone(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));
        $phone = implode("\n", $this->mediaBodies($css, '(max-width: 768px)'));

        $this->assertNotSame('', $phone, 'No `@media (max-width: 768px)` block found in app.css.');

        $this->assertMatchesRegularExpression(
            '/#navbar\s+\.nav-notification\s*\{[^}]*position:\s*static/s',
            $phone,
            'On a phone the bell wrapper must stop being the panel\'s containing block, '
                .'otherwise `right` is measured from a 34px box near the screen edge and the '
                .'panel runs off the left of the viewport.',
        );

        $panel = $this->ruleBody($phone, '#navbar .nav-dropdown.nav-notification__dropdown');

        $this->assertNotNull(
            $panel,
            'The panel needs a phone-width rule inside the 768px media block.',
        );

        // `left` has to be released, otherwise it wins over `right` and the
        // panel stretches across the bar from the bell's left edge instead.
        $this->assertMatchesRegularExpression(
            '/(?<![\w-])left:\s*auto\s*;/',
            $panel,
            'The phone rule must release `left` so the panel is placed by its right edge.',
        );

        $this->assertMatchesRegularExpression(
            '/(?<![\w-])right:\s*\d+px\s*;/',
            $panel,
            'The panel must be inset from the screen edge, not flush to it.',
        );

        // Same size as desktop, just clamped — the user asked for the desktop
        // width to be preserved, so the phone rule cannot quietly shrink it.
        $this->assertSame(
            self::EXPECTED_WIDTH,
            $this->declaredWidth($phone, '#navbar .nav-dropdown.nav-notification__dropdown'),
        );
    }

    /**
     * The bodies of every `@media <condition>` block in the stylesheet.
     *
     * Comments are stripped before walking, so a brace inside a comment cannot
     * throw the depth count off. The walk is by nesting depth rather than by
     * `[^}]*` because a media block contains rules of its own.
     */
    private function mediaBodies(string $css, string $condition): array
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        $pattern = '/@media\s*'.preg_quote($condition, '/').'\s*\{/';
        $bodies = [];

        if (preg_match_all($pattern, $css, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return $bodies;
        }

        foreach ($matches[0] as [$match, $offset]) {
            $body = $this->balancedBlock($css, $offset + strlen($match) - 1);

            if ($body !== null) {
                $bodies[] = $body;
            }
        }

        return $bodies;
    }

    /** The text between the braces of the block opening at `$braceOffset`. */
    private function balancedBlock(string $css, int $braceOffset): ?string
    {
        if (($css[$braceOffset] ?? null) !== '{') {
            return null;
        }

        $depth = 0;
        $length = strlen($css);

        for ($i = $braceOffset; $i < $length; $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($css, $braceOffset + 1, $i - $braceOffset - 1);
                }
            }
        }

        return null;
    }

    /** The text between the braces of the first block declaring `$selector`. */
    private function ruleBody(string $css, string $selector): ?string
    {
        $pattern = '/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/';

        return preg_match($pattern, $css, $matches) === 1 ? $matches[1] : null;
    }

    /**
     * The `width` declared by the first rule whose selector matches.
     *
     * Selectors are matched literally — the stylesheet does not vary its
     * whitespace — so this stays a plain escape rather than a pattern with
     * optional spacing, which is both clearer and easier to trust.
     */
    private function declaredWidth(string $css, string $selector): ?string
    {
        $pattern = '/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/';

        if (! preg_match($pattern, $css, $m)) {
            return null;
        }

        // Comments are stripped first. This project documents its rules inline,
        // and a comment sitting above the declaration defeats any pattern
        // anchored to the start of the block. The lookbehind then keeps
        // `max-width:` from being read as `width:`.
        $declarations = preg_replace('#/\*.*?\*/#s', '', $m[1]);

        if (! preg_match('/(?<![\w-])width:\s*([^;]+);/', (string) $declarations, $w)) {
            return null;
        }

        return trim($w[1]);
    }
}
