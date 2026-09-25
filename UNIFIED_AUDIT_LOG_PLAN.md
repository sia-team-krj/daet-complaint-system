# Unified Audit Logging Plan

**Date:** September 25, 2026
**Scope:** All administrative and staff actions across all departments
**Status:** Implemented and verified on September 25, 2026

## Goal

Create one immutable activity trail for staff and administrators. The admin audit page will show who acted, which department was involved, what changed, when it happened, and the affected record. Complaint-specific status history remains in `complaint_logs`; the new activity log provides the cross-department administrative record.

## Current Gap

`complaint_logs` records complaint status transitions and notes, but it does not capture:

- Department creation or changes
- Staff account creation, activation/deactivation, or department reassignment
- Invitation generation, revocation, or redemption
- Admin complaint reassignment, status changes, assignments, and notes
- Staff complaint status changes, internal notes, and public responses
- The actor's IP address and request context

## Design

Create an immutable `activity_logs` table with:

- `actor_id` nullable foreign key to the acting user
- Snapshot fields for `actor_name` and `actor_role` so the record remains readable if an account changes
- `department_id` nullable foreign key for the acting or affected department
- `action` such as `department.created`, `staff.deactivated`, or `complaint.reassigned`
- `subject_type` and `subject_id` for the affected model
- `description` for a human-readable summary
- `metadata` JSON for structured before/after values
- `ip_address`, `user_agent`, and `created_at`

The model will disable `updated_at` and never expose update/delete operations. New activity rows are append-only.

## Write Paths to Instrument

1. Complaint submission and initial routing
2. Staff complaint status updates, notes, and assignment-related actions
3. Admin complaint updates, department routing, and staff assignment
4. Department creation
5. Direct staff account creation
6. Staff activation/deactivation and department reassignment
7. Invitation generation, revocation, and public redemption

Each write will be recorded in the same transaction as the action where practical. A small `ActivityLogger` service will centralize actor/request context and metadata formatting rather than duplicating logging code across controllers.

## Admin Audit Experience

Update the existing audit page to:

- Show unified activity records, newest first
- Filter by department
- Filter by actor
- Filter by action type
- Search by ticket, subject, or description
- Display actor, department, action, affected record, timestamp, and metadata summary
- Link complaint subjects to the complaint detail page
- Show internal complaint notes only to the admin audit view
- Retain the complaint-specific timeline on each complaint detail page

## Tests

Add feature tests proving:

- Staff and admin actions create activity rows
- Department and actor snapshots are present
- Department filters return only matching activity
- Complaint actions are linked to the correct complaint
- Invitation lifecycle actions are recorded
- Non-admin users cannot access the unified audit page
- Activity rows cannot be updated or deleted through the model API

## Acceptance Criteria

- Every staff/admin write path listed above creates an activity record.
- The admin audit page provides global and department-scoped visibility.
- Logs are immutable and include actor/action/department/time context.
- Existing complaint audit history continues to work.
- Full test suite and production build pass.
