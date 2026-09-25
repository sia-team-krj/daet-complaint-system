# Admin Staff Route Fix Plan

**Date:** September 25, 2026
**Issue:** `GET /admin/staff` returned HTTP 500 because the staff template referenced an undefined route name.
**Status:** Fixed and verified on September 25, 2026

## Root Cause

`resources/views/admin/staff/index.blade.php` uses `route('admin.staff.department', $staffMember)`, but `routes/web.php` registers the endpoint as `admin.staff.update-department`.

## Fix

1. Change the staff department update form to target `admin.staff.update-department`.
2. Add a feature assertion that an administrator can render `/admin/staff`.
3. Run the full test suite and frontend build check.

## Acceptance Criteria

- `/admin/staff` returns HTTP 200 for an active administrator.
- The department update form targets an existing named route.
- All tests continue to pass.
