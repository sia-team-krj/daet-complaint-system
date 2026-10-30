<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A map that is absent and a map that is present but blank look the same to the
 * person using it: an empty box. Both have already happened here.
 *
 * The staff complaint page rendered a #complaint-map div, and the CSS that gives
 * it a height, but never loaded Leaflet and never built a map inside it. A staff
 * member dispatched to a complaint saw nothing where the location should be, on
 * every device. It survived because an empty box reads as a styling problem, so
 * nobody goes looking for a missing script tag.
 *
 * And no map on the site re-measured its container. Leaflet measures once, when
 * the map is built, and caches that. A desktop viewport has settled by then; a
 * phone has not — the URL bar collapses, scrollbars come and go, a late webfont
 * reflows the card around the map. A map sized from a stale measurement renders
 * as a blank band or a strip of clipped tiles.
 *
 * These are asserted against the templates, not the rendered pages, because the
 * behaviour is client-side JavaScript that a request test cannot execute. The
 * template is what decides it, so that is where the guarantee belongs.
 */
class ComplaintMapRenderingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every page that draws a map, and the element its map lives in.
     *
     * @var array<string, array{0: string, 1: string}> template, container id
     */
    private const MAPS = [
        'filing form' => ['views/complaints/create.blade.php', 'complaint-map'],
        'resident complaint' => ['views/complaints/show.blade.php', 'complaint-map'],
        'public transparency' => ['views/pages/transparency/index.blade.php', 'transparency-map'],
        'staff complaint' => ['views/staff/complaints/show.blade.php', 'complaint-map'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    /**
     * No map may fetch Leaflet from a CDN.
     *
     * It used to be pulled from unpkg on every page that draws a map — a
     * third-party request on the critical path of a complaint being filed, and
     * the least reliable link in the chain on the device that matters most: a
     * phone on mobile data, behind a captive portal, or with an ad blocker that
     * reads url paths as trackers. When it failed, the map silently did not
     * appear. It is bundled now, and this is what stops that regressing.
     */
    public function test_no_map_fetches_its_library_from_a_cdn(): void
    {
        foreach (self::MAPS as $name => [$template]) {
            $source = $this->template($template);

            $this->assertStringNotContainsString(
                'unpkg.com',
                $source,
                "The {$name} loads a map library from a CDN. It should be bundled, so that a "
                    .'phone on a poor connection still gets a map.',
            );

            $this->assertStringNotContainsString(
                'cdn.jsdelivr.net',
                $source,
                "The {$name} loads a map library from a CDN.",
            );

            $this->assertStringNotContainsString(
                'cdnjs.cloudflare.com',
                $source,
                "The {$name} loads a map library from a CDN.",
            );
        }
    }

    public function test_leaflet_is_actually_bundled(): void
    {
        $bundle = $this->template('js/leaflet.js');
        $global = $this->template('js/leaflet-global.js');

        $this->assertStringContainsString(
            'from "leaflet"',
            $global,
            'Nothing imports leaflet, so there is no library to draw a map with.',
        );

        $this->assertStringContainsString(
            "import \"leaflet/dist/leaflet.css\"",
            $bundle,
            'The leaflet bundle does not import leaflet.css, so the tiles lay out wrong.',
        );

        $this->assertStringContainsString(
            'marker-icon',
            $bundle,
            'The default marker image is not bundled, so it renders as a broken image.',
        );

        // The bundle's half of the handshake. The queue itself is a classic
        // script in the layout, because a deferred module cannot be reached by
        // the page scripts that draw the maps.
        $this->assertStringContainsString(
            '__flushLeafletWaiters',
            $bundle,
            'The leaflet bundle never flushes the handshake queue, so every map on the site '
                .'waits for a callback that is never called.',
        );

        $this->assertStringContainsString(
            'window.leafletReady',
            $bundle,
            'The leaflet bundle does not set window.leafletReady, so a page that loads later '
                .'would queue forever.',
        );

        // And the default marker needs explicit paths, because Leaflet sniffs
        // them from the stylesheet at runtime and a bundler has rewritten them.
        $this->assertStringContainsString(
            '_getIconUrl',
            $bundle,
            "Leaflet's runtime icon sniffer is still enabled, so the default marker will be a "
                .'broken image once the stylesheet URLs are rewritten by the bundler.',
        );
    }

    public function test_every_map_waits_for_the_bundle_rather_than_reaching_for_window_l(): void
    {
        foreach (self::MAPS as $name => [$template]) {
            $source = $this->template($template);

            $this->assertStringContainsString(
                'whenLeafletReady',
                $source,
                "The {$name} does not wait for the bundled Leaflet. A module is deferred and the "
                    .'inline script is not, so the page runs first, finds window.L undefined, and '
                    .'renders no map with no error.',
            );

            $this->assertStringNotContainsString(
                "typeof window.L === 'undefined'",
                $source,
                "The {$name} checks for window.L and bails, which is the silent failure being "
                    .'removed. It should wait for the bundle instead.',
            );
        }
    }

    /**
     * A map that never initialises and says nothing is the failure this whole
     * change exists to prevent: it is indistinguishable from a map that captured
     * the location, and a resident who cannot tell the difference has no reason
     * to trust that their report has one.
     */
    public function test_every_map_says_so_when_leaflet_cannot_load(): void
    {
        $expected = [
            'views/complaints/create.blade.php' => 'You can still submit',
            'views/complaints/show.blade.php' => 'Your coordinates are still listed below',
            'views/staff/complaints/show.blade.php' => 'handheld GPS',
            'views/pages/transparency/index.blade.php' => 'Interactive map unavailable',
        ];

        foreach ($expected as $template => $marker) {
            $source = $this->template($template);

            $this->assertStringContainsString(
                'onUnavailable',
                $source,
                "{$template} waits for Leaflet but never handles its absence, so the map is "
                    .'simply blank with no explanation.',
            );

            $this->assertStringContainsString(
                $marker,
                $source,
                "{$template} has no message for when the map cannot load.",
            );
        }
    }

    public function test_every_page_with_a_map_actually_initialises_it(): void
    {
        foreach (self::MAPS as $name => [$template, $container]) {
            $source = $this->template($template);

            $this->assertMatchesRegularExpression(
                '#L\\.map\\(#',
                $source,
                "The {$name} loads Leaflet but never calls L.map, so #{$container} stays empty.",
            );

            $this->assertMatchesRegularExpression(
                '/tileLayer\(/',
                $source,
                "The {$name} builds a map but never adds a tile layer, so it is a blank canvas.",
            );
        }
    }

    /**
     * The mobile half. A map that never re-measures is correct on a desktop and
     * wrong on a phone, which is why this cannot be found by looking at it.
     */
    public function test_every_map_re_measures_its_container(): void
    {
        foreach (self::MAPS as $name => [$template]) {
            $source = $this->template($template);

            $this->assertStringContainsString(
                'invalidateSize',
                $source,
                "The {$name} never calls invalidateSize, so the map keeps a measurement taken "
                    .'before the phone viewport settled and renders blank or clipped.',
            );

            $this->assertMatchesRegularExpression(
                "/addEventListener\(\s*['\"]resize['\"]/",
                $source,
                "The {$name} does not re-measure on resize, so rotating the device leaves a "
                    .'map drawn for the old orientation.',
            );

            $this->assertMatchesRegularExpression(
                "/addEventListener\(\s*['\"]orientationchange['\"]/",
                $source,
                "The {$name} does not re-measure on orientationchange.",
            );
        }
    }

    public function test_a_re_measure_does_not_animate(): void
    {
        foreach (self::MAPS as $name => [$template]) {
            $this->assertMatchesRegularExpression(
                '/invalidateSize\(\s*\{\s*animate:\s*false\s*\}\s*\)/',
                $this->template($template),
                "The {$name} animates its re-measure. A resize is not a user gesture and "
                    .'should not zoom the map at someone, and the animation is a bounce '
                    .'the craft floor rules out anyway.',
            );
        }
    }

    public function test_the_staff_map_is_built_at_the_complaints_coordinates(): void
    {
        $source = $this->template('views/staff/complaints/show.blade.php');

        // The map is only useful if it is at the right place. Handing the
        // coordinates over as data attributes keeps the coordinate in one place
        // and out of a JavaScript literal that could drift from the markup.
        $this->assertStringContainsString('data-latitude=', $source);
        $this->assertStringContainsString('data-longitude=', $source);
        $this->assertStringContainsString('setView([latitude, longitude]', $source);
    }

    /**
     * Every map container needs a height, on every page. A zero-height container
     * is the single most common way a Leaflet map "does not show", and it looks
     * exactly like a map that was never initialised.
     */
    /**
     * Every map container needs a real height, at every breakpoint.
     *
     * A zero-height container is the most common way a Leaflet map "does not
     * show", and it is indistinguishable from a map that was never built. The
     * check is deliberately per-declaration rather than per-class: a class that
     * declares a height at desktop and `height: 0` below it has a mobile bug
     * even though the word "height" and a pixel value both appear somewhere in
     * the file for it.
     */
    public function test_no_map_container_is_collapsed_at_any_breakpoint(): void
    {
        $containers = [
            'views/complaints/create.blade.php' => 'filing-map',
            'views/complaints/show.blade.php' => 'map-container',
            'views/pages/transparency/index.blade.php' => 'transparency-map-frame',
            'views/staff/complaints/show.blade.php' => 'map-container',
        ];

        foreach ($containers as $template => $containerClass) {
            $source = $this->template($template);

            preg_match_all(
                '/\.' . preg_quote($containerClass, '/') . '\s*\{([^}]*)\}/',
                $source,
                $rules,
            );

            $this->assertNotEmpty(
                $rules[1],
                "No rule for .{$containerClass} was found in {$template}, so its height is "
                    .'whatever it inherits and the map may as well be invisible.',
            );

            foreach ($rules[1] as $rule) {
                if (! preg_match('/height\s*:\s*([^;]+);/', $rule, $height)) {
                    continue;
                }

                $value = trim($height[1]);

                $this->assertDoesNotMatchRegularExpression(
                    '/^0(px|%|rem|em|vh|vw)?$/',
                    $value,
                    "A rule for .{$containerClass} in {$template} sets height: {$value}, which "
                        .'collapses the map to nothing at that breakpoint. A map with no height '
                        .'is invisible and looks exactly like one that was never built.',
                );

                $this->assertMatchesRegularExpression(
                    '/^\d+(\.\d+)?(px|%|rem|em|vh|vw)$/',
                    $value,
                    "A rule for .{$containerClass} in {$template} sets height: {$value}, which is "
                        .'not a usable length. Leaflet measures the container in pixels and needs '
                        .'a height it can read.',
                );
            }
        }
    }

    /**
     * The handshake has to be a classic script, above the module.
     *
     * Vite emits `type="module"`, which the browser defers until after the
     * document is parsed. The pages' inline scripts that draw the maps are
     * ordinary classic scripts and run first. So the queue they call has to
     * already exist by then, which means it cannot live in the module — it has
     * to be in the document head, above the `@vite` tag.
     *
     * Nothing else in the repo can catch this. Every other test here reads a
     * template in isolation and reasons about it; a test that saw the queue
     * defined in the bundle would pass while the real page silently rendered no
     * map on every device.
     */
    public function test_every_layout_provides_the_handshake_before_the_bundle(): void
    {
        foreach (['views/layouts/app.blade.php', 'views/layouts/guest.blade.php'] as $layout) {
            $source = $this->template($layout);

            $this->assertStringContainsString(
                'partials.leaflet-bootstrap',
                $source,
                "{$layout} never includes the Leaflet handshake, so the pages that draw maps "
                    .'have nothing to call and the maps silently never appear.',
            );

            $bootstrap = strpos($source, 'partials.leaflet-bootstrap');
            // Matched on the directive with its parenthesis, not the bare word: a
            // comment explaining this ordering is likely to mention @vite, and
            // matching that would make the test assert against its own prose.
            $vite = strpos($source, '@vite(');

            $this->assertNotFalse($vite, "{$layout} has no @vite tag.");

            $this->assertLessThan(
                $vite,
                $bootstrap,
                "{$layout} loads the bundle before the handshake. The bundle is a deferred "
                    .'module, so the page scripts run first, find no queue, and no map is built.',
            );
        }
    }

    public function test_the_handshake_is_a_classic_script_and_not_a_module(): void
    {
        $source = $this->template('views/partials/leaflet-bootstrap.blade.php');

        $this->assertStringContainsString('<script>', $source);

        // Matched on the tag, not the bare words: the file explains in a comment
        // that the bundle is a module, and that comment is the point of it.
        $this->assertDoesNotMatchRegularExpression(
            '/<script[^>]*type="module"/',
            $source,
            'The handshake is itself deferred, so it does not exist when the page scripts that '
                .'draw the maps run, and no map is built.',
        );

        // A queue that flushes, and a timeout that says so.
        $this->assertStringContainsString('__leafletWaiters', $source);
        $this->assertStringContainsString('onUnavailable', $source);
    }

    /**
     * leaflet.heat reads a bare `L` global at evaluation time, so the global has
     * to be set before it is imported. Getting this wrong throws
     * "L is not defined" and takes the whole map down with it.
     */
    public function test_the_heat_plugin_is_loaded_after_the_global_it_reads(): void
    {
        $source = $this->template('js/leaflet.js');

        $this->assertStringContainsString(
            'import L from "./leaflet-global"',
            $source,
            'The leaflet bundle does not import the module that sets the global.',
        );

        $this->assertLessThan(
            strpos($source, 'import "leaflet.heat"'),
            strpos($source, 'import L from "./leaflet-global"'),
            'leaflet.heat is imported before the global it reads. It is a UMD plugin that '
                .'references a bare `L` at evaluation time and will throw otherwise.',
        );

        // Not a dynamic import: that would split it into a second request and let
        // the map build before the heat layer exists.
        $this->assertStringNotContainsString(
            'await import("leaflet.heat")',
            $source,
            'The heat plugin is lazily loaded, which adds a second network request to a public '
                .'page and leaves the map building without its heat layer.',
        );
    }

    public function test_the_global_module_actually_sets_the_global(): void
    {
        $source = $this->template('js/leaflet-global.js');

        $this->assertMatchesRegularExpression(
            '/globalThis\.L\s*=/',
            $source,
            'The global module does not set globalThis.L, so leaflet.heat throws.',
        );
    }

    // ── fixture and helper ─────────────────────────────────────────────

    /**
     * A template as Blade will actually render it, includes and all.
     *
     * These assertions are about code that decides whether a map appears, and
     * that code is free to live in a partial. The transparency register moved
     * its map script into one when the register became a Livewire component, and
     * a helper that read only the page file went on asserting things about code
     * that was no longer there — which is how a test suite can pass for the
     * wrong reason, or fail for a reason that has nothing to do with maps.
     */
    private function template(string $path, int $depth = 0): string
    {
        $source = file_get_contents(resource_path($path));

        $this->assertIsString($source, "Could not read {$path}.");

        if ($depth >= 4) {
            return $source;
        }

        $resolved = preg_replace_callback(
            '/@include(?:If)?\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            function (array $matches) use ($depth): string {
                $relative = str_replace('.', '/', $matches[1]);

                foreach (['.blade.php', ''] as $suffix) {
                    $candidate = "views/{$relative}{$suffix}";

                    if (is_file(resource_path($candidate))) {
                        // The include line is kept and the content appended, not
                        // swapped for it. Some assertions here are about the
                        // include existing by name — the leaflet handshake is
                        // pulled into every layout that way — and replacing the
                        // directive with its body silently deletes the very
                        // string they look for.
                        return $matches[0] . "\n" . $this->template($candidate, $depth + 1);
                    }
                }

                // Not a path we can read (a section marker, say). Leave it be
                // rather than silently dropping it from the source.
                return $matches[0];
            },
            $source,
        );

        return $resolved ?? $source;
    }
}
