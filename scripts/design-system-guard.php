<?php
/**
 * Design-system guard — fails on NEW UI violations.
 *
 * What it checks (in resources/views, resources/css and resources/js):
 *   1. hard-coded hex colours    — tokens.css and app.css's documented
 *      department palette are allowed to define them; everything else is a
 *      violation unless grandfathered in tests/Support/design-system-allowlist.txt
 *   2. serif font declarations    — the portal is Inter-only (LOCKED)
 *   3. Leaflet maps without       — any file that builds/loads a Leaflet map
 *      `isolation: isolate`        must declare the map wrapper's stacking
 *                                  context, or the map re-opens the menu bug
 *
 * Usage:
 *   php scripts/design-system-guard.php                 report; exit 1 on violations
 *   php scripts/design-system-guard.php --report-only   same report, exit 0
 *   php scripts/design-system-guard.php --generate-allowlist
 *                                                     writes the allowlist from the
 *                                                     current repo (documented backlog,
 *                                                     don't run casually)
 *
 * The allowlist holds the violations that already existed when the guard was
 * introduced. It shrinks as pages get redesigned; it must never grow. A new
 * violation — not just a new hex inside an old file — fails the build.
 */

$root = dirname(__DIR__);
$allowFile = $root.'/tests/Support/design-system-allowlist.txt';
$mode = $argv[1] ?? '';

/** @var string[] $files */
$files = [];
foreach (['resources/views', 'resources/css', 'resources/js'] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        if ($file->isFile() && preg_match('/\.(blade\.php|css|js)$/', $file->getFilename())) {
            $files[] = str_replace($root.'/', '', $file->getPathname());
        }
    }
}

sort($files);

$violations = [];

foreach ($files as $rel) {
    $src = (string) file_get_contents($root.'/'.$rel);

    // Sanctioned definition files: tokens.css defines the palette, and the
    // reference page prints it as swatches. Department tokens are the one
    // documented hex family that may land in app.css.
    if ($rel === 'resources/css/tokens.css' || $rel === 'resources/views/pages/design-system.blade.php') {
        continue;
    }

    if ($rel === 'resources/css/app.css') {
        $src = preg_replace('/^\s*--dept-[A-Za-z0-9-]*:[^;]+;/m', '', $src) ?? $src;
    }

    preg_match_all('/#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b/', $src, $hexMatches);
    foreach (array_count_values($hexMatches[0]) as $hex => $count) {
        // Duplicates collapse to one signature per (file, hex): one allowed
        // occurrence means folklore stays allowed, never duplicated.
        $violations[] = 'hex|'.$rel.'|'.strtolower($hex).'|'.$count;
    }

    if (preg_match('/font-family[^;{]*?(Garamond|Cormorant|Georgia|Playfair|Times New Roman|DM Serif|Libre Baskerville)/i', $src)) {
        $violations[] = 'serif|'.$rel.'|declared';
    }

    $mentionsLeaflet = stripos($src, 'L.map(') !== false;

    if ($mentionsLeaflet && stripos($src, 'isolation: isolate') === false) {
        $violations[] = 'map-isolation|'.$rel.'|missing';
    }
}

sort($violations);
$violations = array_values(array_unique($violations));

if ($mode === '--generate-allowlist') {
    @mkdir(dirname($allowFile), 0777, true);
    file_put_contents($allowFile, implode("\n", $violations)."\n");
    echo "wrote {$allowFile} (".count($violations)." entries)\n";
    exit(0);
}

$allow = [];

if (is_file($allowFile)) {
    foreach (file($allowFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $allow[trim($line)] = true;
    }
}

$unlisted = array_values(array_filter($violations, fn (string $v): bool => ! isset($allow[$v])));
$listed = count($violations) - count($unlisted);

echo "design-system guard: ".count($files)." files scanned\n";
echo "violations found: ".count($violations)." ({$listed} grandfathered, ".count($unlisted)." NEW)\n";

if ($violations !== [] && ($mode === '--report-only' || $unlisted === [])) {
    $byClass = [];

    foreach ($violations as $v) {
        $byClass[explode('|', $v, 2)[0]][] = $v;
    }

    foreach ($byClass as $class => $list) {
        echo "\n── {$class} (".count($list).")\n";

        $perFile = [];

        foreach ($list as $v) {
            $perFile[explode('|', $v)[1]][] = $v;
        }

        ksort($perFile);

        foreach ($perFile as $file => $list) {
            $unlistedInFile = array_values(array_filter($list, fn (string $v): bool => ! isset($allow[$v])));
            $marker = $unlistedInFile === [] ? ' ' : '!';
            echo sprintf(" %s %s — %d\n", $marker, $file, count($list));
        }
    }
}

if ($unlisted !== []) {
    echo "\nNEW violations (not in the allowlist):\n";
    foreach ($unlisted as $v) {
        echo " ! {$v}\n";
    }

    exit(1);
}

exit(0);
