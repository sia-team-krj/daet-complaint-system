# Staff Dashboard Redesign

## Context

The department dashboard (`/staff/dashboard`) is used by three operator roles in the staff shell:

- **Department Admin** - manages the office and can verify complaints.
- **Verifier** - verifies/triages complaints for the office.
- **Staff** - handles complaints assigned to them.

The page already had role-aware data, but its visual language was inconsistent with the newer admin workspace and the premium sidebar rail. It also inherited transparency/public styling patterns (`transparency-page`, `tr-*`, old `dept-*` hero) that felt heavier and less aligned with the clean workspace shell.

## Goals

- Make the dashboard feel premium, clear, and consistent across Department Admin, Staff, and Verifier.
- Keep all routes, permissions, data queries, and role-specific behavior unchanged.
- Reuse existing design tokens and established workspace patterns.
- Match the super-admin shell spacing and general visual discipline.
- Document the approach and verification.

## Constraints / rules followed

- Read `AGENTS.md` and `docs/design-system.md` before UI work.
- No new colors, fonts, shadows, radii, or one-off token values; use existing tokens from `resources/css/tokens.css`.
- No route/permission/behavior changes.
- One markup source for the dashboard list/content; role differences are data/labels, not separate pages.
- Add design-system/changelog documentation for UI changes.

## Approach

### 1. Audit before changing

Inspected:

- `resources/views/staff/dashboard/index.blade.php`
- `resources/views/staff/dashboard/partials/activity.blade.php`
- `app/Http/Controllers/StaffController.php` (`dashboard`)
- `resources/css/pages/staff-dashboard.css`
- live layout via Playwright for all three roles

Findings:

- The controller already computes role-aware stats (`isVerifier` / assigned-to-me for regular staff).
- The view used a transparency-style hero, custom `dept-*` layout, and a 320px third column that created large right spacing.
- Staff pages using `.transparency-page` were affected by a global `body:has(.transparency-page)` padding reset, causing navbar overlap. This was fixed as part of the alignment pass.

### 2. Define the hierarchy

The dashboard should answer, in order:

1. **Where am I / who am I?** - department name, welcome, role chip.
2. **What needs attention?** - KPI/stat cards.
3. **What should I work on now?** - priority complaints queue.
4. **What happened recently?** - activity panel.

This mirrors the calm admin workspace: header, metrics, work panels.

### 3. Keep role behavior, unify visual treatment

| Role | Primary KPI | Data scope | Visual treatment |
|---|---|---|---|
| Department Admin / Verifier | Pending review | Office queue, verified/open priority work | Same layout/components |
| Staff | Assigned to me | Assigned complaints only | Same layout/components |

The role difference is expressed in labels and query scope, not different card styles.

### 4. Visual system

Reused existing workspace patterns:

- **Surface/cards**: `--surface`, `--line-soft`, `--radius-lg`, `--shadow-card`.
- **Text hierarchy**: `--navy-900` headings, `--muted` metadata, `--faint` secondary cues.
- **Active/brand accent**: navy for primary actions/icons, subtle `--navy-50` tints.
- **Status/priority**: existing status tokens; critical/urgent use `--status-rejected-*`, elevated uses `--status-progress-*`.
- **Spacing/shell**: same super-admin shell feel — max width 1440px, 24px gutters, 16px top shell gap, 2-column content grid.

### 5. Implementation details

#### View

Updated `resources/views/staff/dashboard/index.blade.php`:

- Removed transparency-page hero wrapper.
- Added a clean page header: department eyebrow, welcome title, role chip, overview label.
- Kept the four stat cards, but simplified markup to `dept-stat` with a neutral icon container.
- Replaced the priority section with a clean `dept-panel`: title, description, queue link, search form, and compact complaint rows.
- Kept the activity aside as a secondary panel (`dept-panel--activity`).
- Preserved all existing route generation, role conditions, and complaint data fields.

#### CSS

Updated `resources/css/pages/staff-dashboard.css` with a redesign block at the end (later rules win in same file):

- Clean header, role chip, stat cards, panels, search/button, complaint rows, empty state.
- Responsive behavior: 4 stat columns desktop, 2 at tablet, 1 at mobile; content grid collapses to 1 column below 1280px.
- Activity panel styled as a normal workspace panel.

Updated `resources/css/app.css`:

- Excluded staff/admin bodies from `body:has(.transparency-page/.citizen-dashboard/.lp-root)` zero padding reset.
- Matched staff shell spacing to super admin: `max-width: 1440px`, `padding: 16px 24px 0`, `grid-template-columns: max-content minmax(0, 1fr)`.

### 6. Accessibility / behavior

- Stat cards remain links where appropriate, with `aria-label`s including value.
- Search form keeps label and submit button.
- Complaint rows remain single anchor targets.
- Focus uses existing `--ring`.
- No horizontal overflow at desktop test width.

## Files changed

- `resources/views/staff/dashboard/index.blade.php`
- `resources/css/pages/staff-dashboard.css`
- `resources/css/app.css`
- `docs/design-system.md`
- `docs/design-changelog.md`
- `docs/staff-dashboard-redesign.md`

## Verification

- `npm run build` - pass.
- Playwright smoke at 1440x900 after login:
  - `deptadmin.eng@daetlistens.gov.ph` - dashboard rendered, no horizontal overflow.
  - `staff.eng.1@daetlistens.gov.ph` - dashboard rendered, no horizontal overflow.
  - `verifier.eng@daetlistens.gov.ph` - dashboard rendered, no horizontal overflow.
- Staff alignment/layout check: `bodyPT 64px`, `shellTop 64`, `sidebarTop 80`, `contentTop 80`; shell `1440px`, grid `256px 1112px`, 24px right gutter.

## Notes / follow-up

- This pass redesigns the dashboard view and its core styling. Other staff pages still use older `dept-*`/legacy styles and can be migrated gradually to the same workspace language.
- `pages.transparency.partials.component-styles` is still included for shared status/category primitives used by dashboard rows; if those primitives become fully staff-scoped, this dependency can be removed.
- Legacy `dept-*` rules remain in `staff-dashboard.css` for older pages; cleanup should be done after confirming no remaining consumers.