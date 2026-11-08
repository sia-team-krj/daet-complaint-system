<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The design-system guard runs against the real repository: it fails when a
 * change introduces a hard-coded hex colour, a serif declaration, or a Leaflet
 * map without `isolation: isolate` *outside* the documented grandfathered
 * backlog (tests/Support/design-system-allowlist.txt).
 *
 * The backlog is expected to shrink as pages get redesigned; it must never
 * grow. New entries are added to it only deliberately, by editing the
 * allowlist with the flag --generate-allowlist and a reason in the PR.
 */
class DesignSystemGuardTest extends TestCase
{
    #[Test]
    public function the_design_system_guard_reports_no_new_violations(): void
    {
        $script = base_path('scripts/design-system-guard.php');

        $this->assertFileExists($script);

        $output = [];
        $exitCode = 1;

        exec('php '.escapeshellarg($script).' 2>&1', $output, $exitCode);

        $this->assertSame(
            0,
            $exitCode,
            "design-system guard found NEW violations:\n".implode("\n", $output),
        );
    }

    #[Test]
    public function the_tokens_file_declares_the_mandated_palette(): void
    {
        $tokens = (string) file_get_contents(resource_path('css/tokens.css'));

        foreach ([
            '--navy-950', '--navy-900', '--navy-800', '--navy-700', '--navy-600',
            '--navy-100', '--navy-50',
            '--gold-300', '--gold-500', '--gold-700',
            '--bg', '--surface', '--line', '--line-soft', '--text', '--muted', '--faint',
            '--radius-sm', '--radius', '--radius-lg', '--radius-pill',
            '--shadow-card', '--shadow-lift', '--ring', '--ring-inset',
            '--status-submitted-bg', '--status-review-bg', '--status-progress-bg',
            '--status-resolved-bg', '--status-rejected-bg', '--status-closed-bg',
            '--heat-1', '--heat-2', '--heat-3', '--heat-4',
            '--cat-road-bg', '--cat-flood-bg', '--cat-waste-bg', '--cat-streetlight-bg',
            '--z-navbar', '--z-drawer', '--z-drawer-backdrop', '--z-overlay',
            '--map-loading-bg',
        ] as $token) {
            $this->assertStringContainsString($token, $tokens, "tokens.css is missing {$token}");
        }
    }

    #[Test]
    public function no_serif_declaration_ships_in_the_components_token_file(): void
    {
        $tokens = (string) file_get_contents(resource_path('css/tokens.css'));

        $this->assertDoesNotMatchRegularExpression(
            '/Garamond|Cormorant|Playfair|Georgia|Times New Roman|DM Serif/i',
            $tokens,
            'tokens.css must not declare a serif face.',
        );
    }
}
