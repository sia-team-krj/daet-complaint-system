# Admin Shell and Invitation Workflow Plan

**Date:** September 25, 2026
**Scope:** Persistent admin navigation, department administration, and secure staff invitations
**Mode:** Operate
**Status:** Implemented and verified on September 25, 2026

## Goal

Move all admin navigation out of individual dashboard pages and into a persistent application shell. The sidebar must be a separate Blade partial, fixed below the global navbar, independent of the main content scroll, and accessible on mobile through a deliberate menu control.

Administrators must be able to manage departments, staff accounts, complaints, invitation codes, and the complaint audit trail from this navigation. A newly generated invitation must be single-use, expire after 10 minutes, and create a staff account only in its assigned department.

## Current-State Findings

- `admin.partials.sidebar` is embedded in three individual admin views and carries duplicated CSS.
- The dashboard, complaint list, and staff list each construct their own `.admin-root` shell, causing inconsistent spacing and navbar overlap.
- The global navbar exposes both `Dashboard` and `Admin Dashboard` to administrators. `/dashboard` redirects administrators to `/admin/dashboard`, so the two links are functionally identical.
- `InvitationCode` exists but has no database table, controller, routes, views, or redemption flow.
- The current database supports `citizen`, `staff`, and `admin`; the invitation flow will create `staff` accounts. It will not silently create administrator access.
- `created_by` is not present on users, so it will be added as a nullable audit reference when invitations are implemented.

## Implementation

### 1. Persistent admin layout

Create `resources/views/admin/layouts/app.blade.php`:

- Extend the global `layouts.app` shell to preserve the existing top navbar.
- Include `admin.partials.sidebar` once, beside the content region.
- Use a two-column admin shell below the 64px global navbar.
- Keep the sidebar `position: sticky`, full viewport height, and non-scrolling.
- Allow only the main content column to scroll with the document.
- On narrow screens, use an explicit mobile menu button and off-canvas sidebar instead of hiding navigation.

Refactor `resources/views/admin/partials/sidebar.blade.php`:

- Make it markup-only; all styles belong in the admin layout or `app.css`.
- Use inline SVG icons consistently; no Unicode/emoji substitutes.
- Include working destinations for Overview, Complaints, Departments, Staff Accounts, Invitations, and Audit Trail.
- Keep sign out at the bottom of the persistent sidebar.
- Mark the current section with both an active class and `aria-current="page"`.

Refactor all current admin views to extend `admin.layouts.app` and render only page content. This removes the repeated sidebar and fixes the navbar overlap consistently.

### 2. Remove duplicate admin navigation

Update `resources/views/layouts/navbar.blade.php`:

- Do not show the general `Dashboard` link to administrators because it redirects to the admin dashboard.
- Retain `Transparency`, `Rewards`, the appropriate staff/admin destination, complaint filing, profile, and logout.
- Keep the sidebar as the primary admin navigation.

### 3. Department administration

Add admin department index and creation actions:

- List departments with active status, staff count, open complaint count, and invitation count.
- Allow an admin to add a department with a unique code and optional description.
- Do not expose deletion in this change because complaints and audit records must retain valid department references.

### 4. Secure invitation lifecycle

Create migration `database/migrations/2026_09_25_000001_create_invitation_codes_table.php`:

- `code`: unique 32-character cryptographically random string.
- `department_id`: required foreign key; fixed at generation time.
- `created_by`: required admin foreign key with null-on-delete preservation only if the admin is removed.
- `expires_at`: generation time plus 10 minutes.
- `max_uses`: fixed to 1 for this workflow.
- `used_count`: starts at 0.
- `redeemed_by` and `redeemed_at`: nullable audit fields.
- `is_active`: defaults to true; set to false after redemption or revocation.
- Timestamp columns and lookup indexes for code, department, and expiry.

Update `InvitationCode`:

- Add missing fillable/cast fields (`redeemed_at`, `is_active`).
- Make state helpers null-safe.
- Add useful state labels for the admin list.
- Add model relationships to department, creator, and redeemer.

Add `User::createdInvitations()` and `User::redeemedInvitation()` relationships. Add nullable `created_by` to users for onboarding provenance.

Create `AdminInvitationController`:

- `index()` lists newest invitations with department and creator relations.
- `store()` validates an active department, creates a single-use code expiring in 10 minutes, and records the generating admin.
- `revoke()` deactivates a code without deleting its audit record.

Create `InvitationRedemptionController`:

- Public GET form at `/invitations/{code}` displays the assigned department without exposing administrative data.
- Public POST redemption validates names, email, optional PH mobile, and password confirmation.
- A database transaction locks the invitation row before checking status and creating the user.
- Rejects expired, revoked, or already-redeemed invitations.
- Creates a `staff` user with the invitation's department, active status, and `created_by` provenance.
- Marks the invitation redeemed in the same transaction, then logs the staff user in.
- Never permits an admin or department reassignment through public input.

### 5. Tests

Create `tests/Feature/AdminInvitationTest.php` covering:

1. Admin can generate a department-bound 10-minute single-use invitation.
2. A valid invitation creates one staff account in the assigned department.
3. A second redemption is rejected.
4. Expired invitations cannot create accounts.
5. Revoked invitations cannot create accounts.
6. Non-admin users cannot access invitation administration.
7. The shared admin shell exposes the invitation navigation to administrators.

Run the full test suite and production asset build after implementation.

## Acceptance Criteria

- The admin sidebar is a separate reusable partial included only by the admin layout.
- The sidebar stays visible and non-scrolling while the content area scrolls.
- No admin page includes the sidebar directly.
- The global navbar no longer presents duplicate administrator destinations.
- Every sidebar destination works and has no placeholder route.
- Admin can create and revoke a one-time invitation for a selected department.
- A valid invitation creates exactly one staff account in that department.
- Expired, revoked, and reused invitations cannot create accounts.
- All existing and new feature tests pass; frontend production build succeeds.
