# Staff Dashboard and Queue Separation Plan

**Date:** September 25, 2026
**Scope:** Separate staff overview, complaint queue, and personal activity routes
**Status:** Implemented and verified on September 25, 2026

## Problem

`/staff/dashboard` currently renders the department queue, and `/staff/complaints` delegates back to the same dashboard method. The sidebar therefore presents two different names for the same screen.

## Target Information Architecture

- `/staff/dashboard` — department overview: metrics, priority complaints, recent activity, and clear next actions.
- `/staff/complaints` — full department complaint queue with filters, search, and pagination.
- `/staff/activity` — complaints this staff member has handled.
- Legacy `/staff/dashboard?tab=my-activity` redirects to `/staff/activity`.

## Implementation

1. Change `StaffController@dashboard` to render an overview-specific view.
2. Make `complaintsIndex` render the queue-specific view and retain department scoping/filtering.
3. Add `activityIndex` for the personal activity view.
4. Update the staff sidebar links to the three distinct routes.
5. Reuse the shared staff layout and visual system.
6. Add route/view tests proving dashboard, queue, and activity are distinct and remain department-scoped.

## Acceptance Criteria

- Dashboard no longer renders the full complaint queue.
- Complaint Queue has its own URL and content.
- My Activity has its own URL and content.
- No duplicate dashboard/queue behavior remains.
- Department authorization and existing complaint workflows remain intact.
- Full tests and production build pass.
