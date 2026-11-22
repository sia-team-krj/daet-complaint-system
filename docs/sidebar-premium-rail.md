# Sidebar Premium Rail

## Summary

Redesigned the admin and staff/departmental/verifier sidebars into a shared premium SaaS-style rail. The goal was to make navigation calmer, clearer, and more consistent across operator roles without changing routes, permissions, or business behavior.

The redesign covers:

- **Admin**: `resources/views/admin/partials/sidebar.blade.php`
- **Staff / Departmental accounts / Verifiers**: `resources/views/staff/partials/sidebar.blade.php` (same staff layout, role/capability-specific items preserved)

## Design intent

The direction was "Linear/Stripe-inspired" but still grounded in the Daet Listens design system. The sidebar should feel like a deliberate product surface, not a default browser/list component.

Key goals:

- **Clear hierarchy**: one obvious primary item, grouped sections, and a pinned Settings action.
- **Quiet confidence**: white panel, subtle border/shadow, navy/neutral text, restrained active state.
- **No navigation clutter**: group headers are real sections, not links; submenu items are indented and visually connected.
- **Consistent behavior**: smooth accordion, persistent collapse state, tooltips in icon-only mode, and no layout jumps.
- **No new visual language**: reuse existing tokens and patterns from `docs/design-system.md` and `resources/css/tokens.css`.

## Why this pattern

The previous sidebars exposed many links at the same level. That made priority hard to scan, especially in admin where operations and people-management links competed visually.

The rail pattern solves this by giving the user a map:

1. **Primary destination** - Overview/Dashboard is always visible.
2. **Grouped work areas** - Manage/People for admin; Workspace/Office for staff roles.
3. **Persistent utility** - Settings stays pinned at the bottom.

This keeps frequent actions close while making the navigation easier to understand at a glance.

## Visual decisions

| Area | Decision | Rationale |
|---|---|---|
| Panel | White `--surface` panel, `1px --line-soft`, `--radius-lg`, `--shadow-card` | Creates a clean, premium surface without heavy contrast or a dark legacy rail. |
| Rail width | 256px expanded, 72px collapsed | 256px gives labels room; 72px keeps icons legible and touch/click targets stable. |
| Header | Small workspace eyebrow + 32px ghost collapse chevron | Keeps the rail branded/quiet; collapse control is available but not loud. |
| Primary rows | 44px min height, 18px icons, 14px/600 labels | Comfortable desktop density with accessible target size. |
| Submenus | 22px deeper indent, 16px icons, 13.5px/500 labels, 40px min height, 1px guide line | Makes hierarchy visible while keeping long labels readable and wrapped. |
| Active state | `--navy-50` fill, `--navy-800` text, `--navy-700` icon, 2px inset navy left indicator | Clear but restrained; avoids gold side borders and heavy fills. |
| Hover | `--surface-soft` background, navy icon/text emphasis | Subtle feedback without changing layout. |
| Focus | Existing `--ring` | Keeps keyboard focus visible and consistent with the design system. |
| Motion | 0.22s cubic-bezier width/accordion/chevron/tooltip transitions | Smooth, modern movement; covered by the global reduced-motion rule. |

## Information architecture

### Admin

- **Overview** - `admin.dashboard`
- **Manage** - All complaints, Departments, Complaint categories, Identity verification queue, Audit trail, Resident feedback, FAQs
- **People** - User management, Staff accounts, Staff invitations
- **Settings** - pinned footer

### Staff / Departmental / Verifier

- **Dashboard** - `staff.dashboard`
- **Workspace** - Needs verification (only when `canVerifyComplaints()`), Complaint queue/My work, My activity, Department activity
- **Office** - department admins only: Manage office, Manage categories, Office statistics, Office audit
- **Publication queue** - only for offices authorized by `ComplaintPrivacyService::canApprove()`
- **Settings** - pinned footer

All existing routes, active detection, and permission checks were preserved.

## Behavior

### Accordion groups

- Group headers are native `<details>/<summary>` elements.
- The entire header row is clickable.
- The native disclosure marker is removed; a custom chevron rotates 180° when open.
- Open/close uses a CSS grid `0fr -> 1fr` transition with `visibility` toggled, so closed links are not tabbable.
- Multiple groups can be open.
- Groups with an active child route get `.is-current` and are forced open on load, even if localStorage had them closed.

### Independent scrolling

- `.admin-sidebar__scroll` / `.staff-sidebar__scroll` is the only scroll container.
- The header and Settings footer remain visible.
- This prevents long navigation lists from pushing Settings off-screen.

### Collapse mode

- Collapse button toggles `.is-collapsed` on the rail.
- Expanded width: 256px; collapsed width: 72px.
- The shell grid uses `grid-template-columns: max-content minmax(0, 1fr)`, so content reclaims space smoothly.
- In collapsed mode, labels are visually clipped (not removed) so accessible names remain.
- Submenus and chevrons are hidden in collapsed mode.
- Clicking a collapsed group header expands the rail and opens that group instead of exposing an invisible submenu.
- Collapse state persists in localStorage:
  - Admin: `daet-admin-sidebar-collapsed`
  - Staff: `daet-staff-sidebar-collapsed`

### Tooltips

- Tooltips are rendered as a fixed-position portal element on `<body>` (`.admin-sidebar-tooltip`).
- They appear on hover or keyboard focus only when the rail is collapsed at desktop width.
- Portal positioning prevents clipping by the sidebar scroll container.
- Tooltips hide on mouse out, blur, scroll, resize, Escape, or when the rail expands.

## Responsive behavior

### Admin

- **>=768px**: sticky rail, collapse control available.
- **641-767px**: fixed drawer, always expanded, collapse control hidden.
- **<=640px**: admin rail hidden; shared authenticated mobile drawer handles navigation.

### Staff / Departmental / Verifier

- **>=1024px**: sticky rail, collapse control available.
- **<1024px**: fixed drawer, always expanded, collapse control hidden.
- Staff mobile toolbar/overlay behavior is preserved.

## Accessibility

- Semantic navigation landmarks: `<aside aria-label="...">`, `<nav aria-label="...">`.
- Collapse button uses `aria-controls`, `aria-expanded`, and translated `aria-label`/`title`.
- Group summaries keep `aria-expanded` in sync.
- Active links use `aria-current="page"`.
- Focus ring uses `--ring`.
- Labels remain in the DOM for collapsed icon-only mode.
- Closed accordion bodies are `visibility: hidden`, keeping hidden links out of the tab order.
- Motion respects the global `prefers-reduced-motion` rule.

## Files changed

- `resources/views/admin/partials/sidebar.blade.php`
- `resources/views/staff/partials/sidebar.blade.php`
- `resources/css/pages/admin-workspace.css`
- `resources/css/app.css`
- `public/js/shell.js`
- `docs/design-system.md`
- `docs/design-changelog.md`
- `docs/sidebar-premium-rail.md`

## Verification

### Automated / build

- `node --check public/js/shell.js`
- `npm run build`
- `php artisan view:cache`
- `php artisan test --filter="AdminMobileNavigationTest|IdentityVerificationTest|LocalizationTest"` - 23 passed, 1 risky, 141 assertions
- `php scripts/design-system-guard.php` - 0 NEW violations in touched files; 5 pre-existing NEW violations remain in untouched owner-modified complaint-show files.

### Playwright

- **Admin rail**: 43/43 assertions passed at 1440x900, 1440x500, 700x900, 393x844. Checked width, collapse, tooltip, scroll, active group, persisted state, drawer/mobile behavior, and no horizontal overflow.
- **Staff/dept/verifier rail**: 21/21 assertions passed for departmental admin, staff, and verifier accounts. Checked 256px rail, collapse to 72px, tooltip, panel/footer, and role access.
- **Staff alignment fix**: verified `/staff/dashboard`, `/staff/activity`, `/staff/department-activity` at 1440x900 - `bodyPT 64px`, `shellTop 64`, `sidebarTop 80`, `contentTop 80`; dashboard shell `1440px`, grid `256px 1112px`, 24px right gutter, no horizontal overflow.

## Follow-up fixes documented during implementation

1. **Staff content hidden under navbar** - staff dashboard/activity pages contained `.transparency-page`, which triggered `body:has(.transparency-page)` padding reset. Fixed by excluding staff/admin bodies from that zero-padding rule.
2. **Large right spacing on staff dashboard** - an unused 320px third column made the workspace narrow. Fixed by matching super-admin shell spacing: `max-width: 1440px` and 2-column `max-content minmax(0, 1fr)`.

## Notes

- No new design tokens were added.
- No routes, permissions, or navigation destinations were removed/renamed.
- Staff-dashboard legacy CSS still contains older sidebar selectors for old class names; the new premium rail uses `staff-sidebar__*` classes and overrides the legacy behavior where needed. A future cleanup can remove dead legacy selectors after confirming no dependent markup remains.