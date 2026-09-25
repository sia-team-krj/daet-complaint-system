# Admin User Management Plan

**Date:** September 25, 2026
**Status:** Implemented and verified on September 25, 2026

## Verification

- `php artisan test`: 68 tests / 310 assertions passing.
- `npm run build`: production CSS and JavaScript generated successfully.
- `php artisan view:cache`: Blade templates compile successfully.
- Public Funnel smoke checks: `/admin/users` and a user detail route return HTTP 200 with the compiled stylesheet.
- Mechanical design detection was run on the changed UI targets; reported warnings are pre-existing styles outside the new user-management block.
**Mode:** Operate
**Surface:** `/admin/users`

## Confirmed scope

- Manage residents, staff, and administrators from one admin directory.
- Search and filter by name, email, role, account status, and department.
- View a user detail page with account metadata and related activity.
- Edit safe profile/contact fields.
- Change role and department with validation and audit logging.
- Activate or deactivate accounts without deleting records.
- Keep the existing direct staff creation and single-use invitation onboarding flows.
- Preserve the current `citizen`, `staff`, and `admin` role architecture.

## Product / job

Admins arrive from the admin navigation to answer three operational questions quickly: who has access, which department does each staff member serve, and which accounts need attention. The directory should make a user’s current state legible before any edit, then make the safest next action one click away. Resident identity is visible only inside this admin surface; no resident data is exposed to staff workspaces or public pages.

## Direction contract

**THESIS:** An access directory, not a generic profile gallery: identity, role, department, and status form one scannable operational record.
**OWN-WORLD:** The existing Daet Listens navy/gold institutional interface, using restrained dark panels, gold rules, compact uppercase labels, and familiar table/form controls.
**STORY:** The admin can move from “all accounts” to a trustworthy account decision without losing context; every consequential change is confirmed, validated, and recorded.
**FIRST VIEWPORT:** A clear “User management” heading, compact summary metrics, filter/search controls, and the first rows of the user directory; primary onboarding actions remain visible at the top.
**FORM:** The existing admin shell and component vocabulary, extended with a responsive directory and a focused user detail view.
**FINISH:** The directory is complete only when empty, filtered, error, success, permission, and mobile states are all represented, the responsive build has no horizontal page overflow, and the final implementation and audit behavior are covered by feature tests.

## Backend / security rules

- Only authenticated active admins can access the directory and mutations.
- Use route-model binding for users; verify the target is not the current admin for self-deactivation or destructive self-role changes.
- Prevent removal of the last active administrator.
- A citizen cannot be assigned a department; staff require a valid active department; admins have no department requirement.
- Role transitions and activation changes must be logged in `activity_logs` with before/after metadata.
- Email uniqueness must ignore the current user when editing.
- Never expose password hashes, remember tokens, or resident identity outside admin-only responses.
- Keep the existing invitation and direct-create actions as separate onboarding paths.

## Planned implementation

1. Add admin user directory/query methods with pagination, search, role/status/department filters, and summary counts.
2. Add user detail and update/toggle endpoints with safe validation and activity logging.
3. Add routes and a primary User management navigation entry while retaining the focused legacy staff view and onboarding routes.
4. Build responsive directory, detail/edit, and reusable admin form/status/table styles.
5. Add feature coverage for access control, filtering, updates, self-protection, last-admin protection, and audit entries.
6. Run the full test suite, Blade/PHP checks, Vite production build, and responsive smoke verification.

## Acceptance criteria

- `/admin/users` returns 200 for an active admin and redirects non-admins.
- Admins can find users by name/email and filter by role, status, and department.
- Admins can open a user, update permitted fields, change role/department, and activate/deactivate safely.
- The current admin cannot deactivate or demote themselves, and the last active admin cannot be removed.
- All user-management mutations create immutable activity records.
- Existing staff creation and invitation workflows continue to pass.
- Mobile layouts use contained tables/cards with no horizontal viewport overflow.
