# Mobile X-Axis Overflow Fix Plan

**Date:** September 25, 2026
**Scope:** Prevent the document viewport from swiping horizontally on narrow screens

## Changes

- Constrain `html`, `body`, `main`, navbar, and navbar inner wrappers to `100%` width.
- Keep root-level `overflow-x: hidden` as a final viewport boundary.
- Keep the global and staff/admin navigation handlers in a persistent static shell script so Livewire page transitions do not require a refresh.
- Hide the compact header CTA on extra-small screens with `hidden sm:flex`; keep the action available in the mobile menu.
- Ensure desktop-only navbar actions remain horizontal and never wrap into a vertical column.

## Verification

- Persistent shell controller now handles global, staff, and admin navigation after Livewire navigation and page restores.
- Blade cache, production build, JavaScript syntax, diff checks, and the full test suite pass (60 tests / 266 assertions).
- Rendered guest HTML includes the shared navbar controller and responsive width classes.
- No Firefox or other browser UI checks are used for this pass.
