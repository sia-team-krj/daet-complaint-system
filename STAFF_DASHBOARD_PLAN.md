# Staff Dashboard UI Plan

**Date:** September 25, 2026
**Scope:** Department-scoped staff workspace for all municipal departments
**Status:** Implemented and verified on September 25, 2026

## Goal

Give staff members a consistent, persistent workspace comparable to the admin panel while preserving department isolation. Each staff account must see only the complaints and activity belonging to its assigned department.

## Current Gaps

- Staff dashboard is a single embedded page without a reusable application shell.
- Navigation between queue, activity, profile, and sign-out is not consistently available.
- Desktop and mobile behavior are not defined for staff users.
- The dashboard needs clearer department context, status hierarchy, and queue actions.

## Implementation

### Shared staff layout

Create:

- `resources/views/staff/layouts/app.blade.php`
- `resources/views/staff/partials/sidebar.blade.php`

The layout will:

- Extend the global `layouts.app` shell.
- Render the staff sidebar once, below the fixed global navbar.
- Keep the sidebar persistent and non-scrolling on desktop.
- Use an off-canvas sidebar with explicit menu controls on mobile.
- Keep the main content as the only document-scrolling region.
- Show the signed-in staff member, role, and assigned department.

### Staff navigation

Include functional destinations for:

- Department dashboard
- Complaint queue
- My activity
- Profile
- Sign out

Add a staff complaint index route as a real queue destination, scoped to the authenticated staff member’s department. Preserve admin access behavior through the existing role middleware.

### Dashboard UI

- Add a department context header.
- Keep queue and my-activity tabs as first-class navigation.
- Make status filters, search, complaint rows, urgency, assignment, and update actions easier to scan.
- Preserve current pagination and department authorization.
- Add empty states for no queue items and no personal activity.
- Keep the existing complaint status and mail behavior unchanged.

### Verification

- Add feature coverage for staff shell rendering and department-scoped queue access.
- Verify a staff member cannot see another department’s queue through the new route.
- Run the full test suite and production build.

## Acceptance Criteria

- Every department staff account gets a consistent persistent workspace.
- Sidebar navigation is separate from page content and does not scroll independently on desktop.
- Queue and activity remain department-scoped.
- Mobile navigation is reachable and keyboard dismissible.
- Tests and build pass.
