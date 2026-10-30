<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the shared navbar controller, by running it.
 *
 * `public/js/shell.js` arbitrates between the notification panel, the account
 * popover and the mobile drawer from one delegated click listener. Each
 * behaviour is a handful of calls inside one branch, and no amount of reading
 * the source establishes that a branch makes the right calls — only executing
 * it does.
 *
 * That is not theoretical. The hamburger branch closed the account menus and
 * the notification branch closed the drawer, so each looked complete, but the
 * hamburger branch never closed the notification panel. On a phone the panel
 * stayed open under the drawer, with nothing on the page explaining why. This
 * test loads the real controller into jsdom against the real markup contract
 * and fails on any surface that survives a tap it should have closed.
 */
class ShellControllerTest extends TestCase
{
    public function test_the_shell_controller_never_leaves_two_surfaces_open(): void
    {
        $audit = base_path('tests/Support/js/shell-controller-audit.mjs');

        $this->assertFileExists(
            $audit,
            'The shell controller audit is missing, so nothing on this controller is ever executed.',
        );

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            ['node', $audit, public_path('js/shell.js'), 'the navbar shell controller'],
            $descriptors,
            $pipes,
            base_path(),
        );

        $this->assertIsResource($process, 'Could not start node to audit the shell controller.');

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        $this->assertSame(
            0,
            $status,
            "The navbar shell controller left a surface open that it should have closed.\n\n"
            .trim($stdout)."\n".trim($stderr),
        );
    }
}
