# Personal Dashboard UX Plan

**Date:** September 25, 2026
**Scope:** Role-aware navbar destination and citizen-specific dashboard UI
**Status:** Implemented and verified on September 25, 2026

## Goal

Make the dashboard feel purpose-built for the signed-in user instead of presenting system-wide totals to every role.

## Changes

- Make the navbar logo role-aware:
  - Citizen → personal dashboard
  - Staff → staff workspace dashboard
  - Admin → admin workspace dashboard
- Keep the existing role workspace sidebars as the primary staff/admin navigation.
- Audit the citizen dashboard controller and view for system-wide metrics.
- Limit citizen dashboard totals, lists, charts, and copy to complaints belonging to the authenticated resident.
- Replace ambiguous “all filed” language with resident-specific language where needed.
- Preserve complaint status, progress, and quick actions.
- Add regression tests for role-aware logo destinations and citizen-only dashboard data.

## Verification

- Citizen logo points to `/dashboard`.
- Staff logo points to `/staff/dashboard`.
- Admin logo points to `/admin/dashboard`.
- Citizen dashboard does not expose other users’ complaint totals.
- Full test suite, Blade cache, production build, and diff checks pass.
