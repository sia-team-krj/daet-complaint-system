# Sidebar and Navbar Cleanup Plan

**Date:** September 25, 2026
**Scope:** Remove duplicate identity/account controls and role navigation
**Status:** Implemented and verified on September 25, 2026

## Changes

- Remove the admin sidebar brand title and seal; the global navbar already carries the application identity.
- Remove the staff sidebar brand title and seal for the same reason.
- Remove sidebar account summary and sign-out controls from both workspaces; the global navbar already provides profile and logout.
- Keep department context in the staff sidebar because it is operational information, not a duplicate account control.
- Hide the generic global `Dashboard` link for admins and staff because `/dashboard` redirects to their role dashboard.
- Keep one role-specific global destination: `Admin Dashboard` or `Staff Dashboard`.
- Keep `Transparency`, `Rewards`, complaint filing, profile, and logout in the global navbar.

## Verification

- Render admin and staff workspaces and confirm the sidebar contains navigation only.
- Confirm staff navbar has one dashboard destination.
- Confirm account/profile/logout remain available in the global navbar.
- Run feature tests and production build.
