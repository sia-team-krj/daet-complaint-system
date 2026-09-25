# Mobile Navbar Repair Plan

**Date:** September 25, 2026
**Scope:** Fix oversized logo overlap and non-working mobile hamburger navigation
**Status:** Implemented; persistent shell controller and responsive containment awaiting device confirmation

## Goal

Make the public, resident, staff, and admin experiences usable on narrow screens without changing the desktop information architecture. This includes navigation state, contrast, and data-heavy views—not only the logo.

## Changes

- Add a mobile-only compact logo treatment.
- Prevent logo text and icon from forcing the navbar wider than the viewport.
- Keep the navbar height and horizontal alignment stable at 360px and below.
- Ensure the hamburger button has a stable hit area and visible open/closed state.
- Initialize mobile menu toggling defensively for both guest and authenticated navbar partials through a persistent static shell controller loaded by both layouts.
- Preserve Escape-key closing, outside navigation behavior, and menu accessibility.
- Verify no horizontal overflow and that the menu opens/closes on mobile markup.
- Use one navigation controller for guest, resident, staff, and admin shells.
- Remove touch-hover styling that can make a hamburger look permanently active.
- Increase contrast for the mobile shell, menu links, controls, and status surfaces.
- Make admin/staff tables horizontally safe and convert dense operational rows into readable mobile cards where appropriate.
- Restore an explicit horizontal flex rule for the desktop action group; removing duplicate partial styles had left the `<ul>` as a normal block, which stacked its children vertically.

## Verification

- Blade cache and production build pass.
- Mobile navbar markup contains the compact logo and toggle state.
- JavaScript syntax and navigation smoke checks pass.
- Headless Firefox screenshots at 360px and 320px show the seal constrained to a compact mark with no navbar overlap.
- Full Laravel suite passes: 60 tests / 266 assertions.
- Final device confirmation is intentionally left to the user; no further Firefox UI checks were run.
