# Design Changelog

Newest entry first. Every change to `resources/views`, `resources/css`, `resources/js`
or `lang` that alters how the UI looks or behaves must add an entry here.
Verification screenshots are **optional** (waived by the owner 2026-11-09): list them
when taken, or state `Verification screenshots: NOT captured (waived by owner, <date>).`
See `docs/ui-verification.md`. The entry itself is mandatory.


## 2026-11-10: Staff & admin navbars — remove desktop language switcher

- Page / component: `resources/views/staff/partials/sidebar.blade.php`, `resources/views/admin/partials/sidebar.blade.php`.
- Why (the problem found): user requested removal of the language changer from the desktop staff and admin navbars only (mobile kept intact).
- What changed: removed the `{{ __('nav.language') }}` label and `<div class="staff-sidebar-language">` / `<div class="admin-sidebar-language">` containing `@include('layouts.partials.language-switcher', ['variant' => 'compact'])` from both sidebars. Mobile language switcher (`variant => 'mobile'` in drawers) untouched.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted markup removal; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Auth pages — remove divider and eyebrow elements

- Page / component: `resources/views/auth/register.blade.php`, `resources/views/auth/login.blade.php`.
- Why (the problem found): user requested removal of brand divider and form eyebrow elements from register and login pages.
- What changed: removed `<div class="auth-brand-divider fu d2"></div>` from both register and login brand sections; removed `<div class="auth-form-eyebrow fu d1">` with "Gumawa ng Account" from register and "Ligtas na Pag-access" from login.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted markup removal; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-10: Staff dashboard — limit Department Activity to 4 items

- Page / component: `app/Http/Controllers/StaffController.php`, `resources/views/staff/dashboard/partials/activity.blade.php` (staff dashboard Department Activity).
- Why (the problem found): the dashboard showed up to 5 recent department activity items with a show-more threshold at 3, making the section longer than desired.
- What changed: limited recent activity collection to 4 items in the controller and aligned the dashboard partial to show 4 items without a show-more toggle (extras/toggle now apply only above 4).
- Verification screenshots: NOT captured (waived by owner, 2026-11-10).
- Definition of Done checklist: passed (targeted limit/UI fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-10: Staff complaint queue — desktop pagination controls

- Page / component: `resources/views/staff/complaints/index.blade.php` (staff complaint queue pagination).
- Why (the problem found): desktop/tablet view had no usable Prev/Next (and inconsistent) pagination controls for moving between queue pages.
- What changed: switched desktop pagination to the shared workspace paginator view (`pagination::workspace`) and added light, token-aligned styles for `admin-pager` controls within `.staff-pagination-desktop`, so Previous/Next and page numbers are clearly available on desktop while mobile keeps the existing compact controls.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10).
- Definition of Done checklist: passed (targeted pagination UI fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Transparency complaint detail — reduce gap above back link

- Page / component: `resources/views/pages/transparency/show.blade.php` (`.pt`, `.pt-back-row`).
- Why (the problem found): an unusually large gap appeared between the fixed navbar and the back link (`a.pt-back`) on complaint detail pages (e.g. `/transparency/complaints/68`).
- What changed: made the body padding-top cancellation explicit (`!important`) for `body.page-transparency-show`, used the canonical `--navbar-h` fallback for `.pt` offset, and tightened `.pt-back-row` vertical padding (`4px 0 8px`, `2px 0 8px` on mobile) to remove excess space while preserving navbar clearance.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted spacing fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Transparency complaint cards — responsive layout/spacing fix

- Page / component: `resources/views/pages/transparency/partials/complaints-styles.blade.php` (shared complaint card/list styles used by the transparency register/complaints pages).
- Why (the problem found): some complaint cards (`.complaint-card__link` / `cc-*`) became cramped or misaligned depending on text length (long titles/barangays, translated status labels) and variable badges (verified/department). The desktop layout used a fragile fixed-column `display: contents` grid that did not map reliably to conditional row2 items, and mobile row2 used `flex-wrap: nowrap`.
- What changed: rebuilt card layout to be content-aware. Mobile keeps the stacked structure but allows `cc-row2` to wrap cleanly, improves meta/title wrapping (`overflow-wrap: anywhere`, safer barangay truncation), and uses token divider color. Desktop (`>=768px`) uses a stable 3-column grid (`icon | content | status/meta`) with `cc-row1`/`cc-title-col` as `display: contents` and `cc-row2` as a flexible end-aligned container, so conditional badges/date/chevron no longer break column alignment.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted shared CSS fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Transparency register — spacing before footer after “View all” button

- Page / component: `resources/views/pages/transparency/partials/component-styles.blade.php` (`.tr-view-all`, used by `resources/views/livewire/transparency-register.blade.php`).
- Why (the problem found): the “Tingnan ang lahat ng … pampublikong reklamo” / view-all button sat too close to the footer on the transparency page.
- What changed: added `padding-bottom: 48px` to `.tr-view-all` to create clear spacing between the button and the footer.
- Follow-up: sized `.tr-view-all__btn` to content (`width: auto`, `max-width: 100%`, `min-height`) and allowed wrapping on mobile so long translated labels don’t render as a full-width bar; desktop keeps nowrap padding.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted spacing/sizing fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Landing hero — reassurance text no longer overlapped by stats strip

- Page / component: `resources/views/pages/home/guest.blade.php` (landing hero).
- Why (the problem found): `.lp-hero__reassurance` (“Libre. Hindi ipinapakita sa publiko ang iyong pangalan.”) was overlapped by the `.tr-stats` strip, which uses `margin-top: -32px` to overlap the hero.
- What changed: increased `.lp-hero` bottom padding (`56px` base, `64px` at `>=1024px`) to reserve space for the overlapping stats strip; added small bottom padding and `position: relative; z-index: 1` to `.lp-hero__reassurance` as a safety guard.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted CSS fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Transparency complaints register — heading alignment fix; logo name remains untranslated

- Page / component: `resources/views/pages/transparency/complaints.blade.php`, `resources/views/pages/transparency/partials/complaints-styles.blade.php`.
- Why (the problem found): the “All public complaints” page heading (`tr-h1`, translated) used inline `padding: 0 16px` outside the shared container, causing horizontal misalignment with the back link/content. User also requested that the logo name never be translated.
- What changed: moved the heading into `.tr-container` and removed the ad-hoc inline padding; added `.complaints-page-title` (navy text on light page, consistent spacing) so it aligns with the register layout. Confirmed the visible logo mark remains hardcoded as `DAET LISTENS` in navbar/drawer templates (no translation key used for the logo name).
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted markup/CSS fix; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-09: Admin panel — full light redesign aligned to the staff workspace (dashboard, sidebar, all admin pages)

- Page / component: `/admin` workspace shell and all admin subpages — dashboard, complaint queue/detail, staff accounts/create/created, users list/detail, departments, audit trail, settings, invitations. Navbar and footer unchanged.
  - Views: `resources/views/admin/layouts/app.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `resources/views/admin/dashboard/index.blade.php`, `resources/views/admin/complaints/index.blade.php`.
  - CSS: `resources/css/pages/admin-workspace.css` (new), `resources/css/pages/admin-dashboard.css` (scoped/fixed), `resources/css/app.css` (imports + removed duplicated dashboard block).
- Why (the problem found): the admin panel mixed a legacy dark shell (`.admin-shell`/`.admin-sidebar` navy + gold on dark surfaces, dark tables/forms, dark mobile table cards and audit timeline) with newer light/shared markup. The dashboard also used staff-style markup whose CSS was scoped to `body.page-staff-dashboard`; `admin-dashboard.css` was not imported and its content had been duplicated at the end of `app.css`, leaving the dashboard unstyled/inconsistent and creating duplicate/conflicting rules.
- What changed:
  - **Shell & sidebar (staff-matched):** admin now uses the same light workspace treatment as staff — `--bg` page, white card sidebar with `--line-soft`/`--shadow-card`, navy text, `--navy-50` hover, gold active rail (`--gold-500`), tokenized language switcher, and the same responsive strategy. Desktop `>=768px` keeps a persistent sticky sidebar (`240px` tablet, `248px` desktop); `641–767px` uses the admin mobile toolbar + drawer via existing `data-admin-sidebar-toggle`/`shell.js` with `--z-drawer`/`--z-drawer-backdrop`; `<=640px` defers to the existing shared authenticated mobile drawer. Body/navbar offset normalized so the admin workspace sits below the fixed navbar without the legacy double-padding.
  - **Dashboard repaired & aligned:** dashboard body class includes `admin-layout page-admin-dashboard`; dashboard CSS is properly imported (duplicate EOF block removed from `app.css`) and scoped to `body.role-admin.admin-layout.page-admin-dashboard`. Hero now matches staff treatment (navy band + repeating pattern, `dept-hero-card__bg/__inner`), with gold primary / light secondary actions and the existing stat/department/complaint/activity sections using the shared tokenized `dept-*`/`tr-*`/status components.
  - **All admin pages light-themed:** `admin-workspace.css` provides high-specificity light overrides for legacy `admin-*` components — page headers, metrics, panels, filters, inputs/selects/textareas/checkboxes, buttons, table wraps/headers/rows, status/role pills (mapped to design-system `--status-*`/`--error-*` tokens), flashes, empty states, breadcrumbs, pagination, detail/timeline/evidence, department directory/editor/archive, invitations, staff create/created method cards, moderation panel, settings/category lists, and user directory/profile controls.
  - **Responsive/mobile parity:** legacy dark mobile table-card rules are overridden to white cards on `--line-soft`; audit trail mobile timeline/cards/filters/chips are overridden to light token colors at `<=640px` while preserving the existing timeline behavior; metrics/forms/filter grids collapse cleanly; no horizontal overflow introduced.
  - **Complaint queue cleanup:** removed inline layout style attributes from `admin/complaints/index.blade.php` (container padding/spacing, export button/date fields, bulk bar spacing) and moved the admin-specific queue spacing/export styling into `admin-workspace.css`; kept the existing light transparency/queue component structure and behavior.
  - **Design-system compliance:** all new styling uses existing tokens from `resources/css/tokens.css`/workspace token system; no new hard-coded hex colors, serif declarations, or map-wrapper isolation regressions in admin files. Preserved existing admin routes, controllers, data bindings, and Livewire/public component contracts.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09). Captured local Playwright evidence in `/tmp/opencode/admin-shots/` (model cannot display image input):
  - Desktop 1440: `/admin/dashboard`, `/admin/complaints`, `/admin/departments`, `/admin/staff`, `/admin/users`, `/admin/audit-trail`, `/admin/invitations`, `/admin/settings`, `/admin/staff/create` all render with `body` background `rgb(244,246,250)`, `body` padding-top `64px`, `.admin-shell` CSS grid (`248px` sidebar + content), sticky sidebar, white `.admin-sidebar-card`, and light navy headings where present; no horizontal overflow.
  - Dashboard responsive: 1024px grid `248px/704px`, toolbar hidden; 820px grid `240px/528px`, toolbar hidden; 700px block shell with fixed drawer (hidden off-canvas transform) and toolbar `flex`; 640px/390px block shell with toolbar hidden (shared mobile drawer range); no horizontal overflow.
  - Users responsive: table wrap white at desktop/tablet, mobile rows render as white cards; no horizontal overflow at 1024/820/700/640/390.
  - Audit 390 and complaints 390 full-page captures taken; Playwright run reported no console/page errors.
- Definition of Done checklist:
  - [x] Uses design-system tokens/components only for new admin styling (no new hard-coded hex/serif introduced by this change).
  - [x] Existing admin routes/behavior/data preserved; no navbar/footer changes.
  - [x] Responsive evidence recorded (desktop/tablet/mobile widths) and no horizontal overflow detected in automated checks.
  - [x] `php scripts/design-system-guard.php`: 52 pre-existing NEW violations remain in unrelated files; **0 NEW violations in files changed by this admin redesign**.
  - [x] `npm run build` succeeded.
  - [x] Local visual verification run completed with Playwright (`/tmp/opencode/admin-shots/*`); screenshots optional/waived for chat display by owner policy.


## 2026-11-09: Staff Activity History page (`/staff/activity`) — redesigned complaint list layout, information hierarchy, and responsive behavior matching Daet Listens Complaint Queue card design

- Page / component: `resources/views/staff/activity/index.blade.php` (Staff Activity History).
- Why (the problem found): staff activity history page used scattering arbitrary columns for handled complaints, inconsistent header hierarchy, and lacked the polished Daet Listens Complaint Queue card design structure while preserving existing statistics without adding any stat cards.
- What changed: redesigned `/staff/activity` header to use standard `tr-banner`/`tr-h1` hierarchy, kept the three existing statistics exactly as implemented without adding any new stat cards or widgets, and redesigned handled complaint cards using the established Daet Listens Complaint Queue card design (`staff-queue-item`, `staff-row`, category icon, status/verification pills, latest activity description and timestamp, view complaint action).
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.


## 2026-11-09: Staff dashboard — removed fixed mobile bottom navigation bar and restored vertical screen space

- Page / component: `resources/views/staff/dashboard/index.blade.php`, `resources/css/pages/staff-dashboard.css` (Staff Dashboard).
- Why (the problem found): the fixed bottom navigation bar on mobile staff dashboard occupied valuable screen space and partially obscured complaint cards and dashboard content.
- What changed: removed the mobile bottom navigation bar markup (`.dept-bottom-nav`) and associated mobile bottom padding/clearance rules, restoring full vertical screen space and ensuring complaint cards are fully visible without obstruction.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.


## 2026-11-09: Staff dashboard — aligned stat cards sizing & horizontal flex layout with the complaint queue (`.dept-stat` matching `.tr-stat`)

- Page / component: `resources/views/staff/dashboard/index.blade.php`, `resources/css/pages/staff-dashboard.css` (Staff Dashboard).
- Why (the problem found): dashboard stat cards (`.dept-stat`) had different sizing and vertical stacking proportions compared to the complaint queue stat cards (`.tr-stat`).
- What changed: updated dashboard stat cards (`.dept-stat`) to use the exact same horizontal flex layout (icon on the left, value and label stacked vertically on the right) and identical padding/sizing as the complaint queue cards (`.tr-stat`).
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Staff dashboard — made recent activity items clickable links to complaint details

- Page / component: `app/Http/Controllers/StaffController.php`, `resources/views/staff/dashboard/partials/activity.blade.php`.
- Why (the problem found): recent activity items on the staff dashboard were non-clickable `<div>` elements, preventing staff from accessing the corresponding complaint detail workspace directly from activity feed entries.
- What changed: attached complaint models to activity group entries in `StaffController`, and wrapped activity items in anchor tags linking directly to `route('staff.complaints.show', $complaint)`.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Staff dashboard — welcome banner department identity refinement (removed translucent container card/border)

- Page / component: `resources/css/pages/staff-dashboard.css` (Staff Dashboard).
- Why (the problem found): department info was enclosed inside a large translucent bordered rectangle inside the welcome banner.
- What changed: removed the border, translucent background, and box padding around department information (`.dept-identity-card`), displaying department icon, name, and GSO badge inline directly against the navy banner, with "Staff" displayed cleanly beneath.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Staff dashboard — mobile layout restructuring (Non-overlapping welcome banner & horizontal icon-and-number stat card composition)

- Page / component: `resources/css/pages/staff-dashboard.css` (Staff Dashboard).
- Why (the problem found): the mobile dashboard layout had an awkward overlapping welcome banner and statistics grid, and cards used a tall vertical arrangement.
- What changed: restructured mobile dashboard layout by removing the overlap (standalone welcome banner with positive `.dept-stats` margin-top of 14px), and redesigning stat cards into a horizontal composition (icon and number side-by-side in row 1, label beneath in row 2).
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Public transparency & staff queue — mobile statistics cards size and spacing optimization (`.tr-stats` / `.tr-stat`)

- Page / component: `resources/views/partials/public-ui.blade.php` (Public transparency & Staff queue stat cards).
- Why (the problem found): mobile statistics cards using `.tr-stats` / `.tr-stat` were too large and consumed excessive vertical screen space on mobile viewports.
- What changed: optimized mobile dimensions for `.tr-stats` / `.tr-stat` to a 2x2 grid (`repeat(2, minmax(0, 1fr))`), 12px padding, 32px icon containers, 16px icons, 24px numbers, and 10.5px labels.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Staff dashboard — mobile statistics cards size and spacing optimization (2x2 grid, compact 12px padding, 32px icons, 24px numbers)

- Page / component: `resources/css/pages/staff-dashboard.css` (Staff Dashboard).
- Why (the problem found): mobile statistics cards on the staff dashboard were too large and consumed too much vertical screen space.
- What changed: optimized mobile statistics cards within the existing 2x2 grid (`grid-template-columns: repeat(2, minmax(0, 1fr))`) with reduced padding (12px), smaller icon containers (32px), 16px icons, 24px statistic numbers, and 10.5px labels.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

- Page / component: `app/Http/Controllers/StaffController.php`, `resources/views/staff/complaints/index.blade.php`.
- Why (the problem found): staff dashboard priority complaints and complaint queue pages displayed 5 items by default, causing excessive vertical scrolling.
- What changed: limited dashboard priority complaints query to 3 items (`limit(3)`) and queue pagination to 3 items per page (`paginate(3)`), alongside compact stat strip styling.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

- Page / component: `resources/views/staff/complaints/index.blade.php` (Staff Complaint Queue).
- Why (the problem found): staff complaint queue pagination needed distinct responsive presentations for desktop and mobile without crowding the footer or overflowing on small screens.
- What changed: implemented horizontal desktop pagination with dynamic range calculation (`Showing 1–5 of 15`), and a dedicated mobile two-row pagination with touch-friendly 44px buttons (`Previous`, `Page X of Y`, `Next`), plus generous footer spacing (`margin-bottom: 60px`).
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Staff complaint queue page — UI/UX card redesign and mobile layout optimization matching Staff Dashboard

- Page / component: `resources/views/staff/complaints/index.blade.php` (Staff Complaint Queue).
- Why (the problem found): staff complaint queue cards had layout, wrapping, and responsiveness problems on mobile, with text/badge overflow and poor visual hierarchy.
- What changed: redesigned complaint cards into structured sections (top icon/title/ID/category, middle status/review/priority badges with flex wrap, right-aligned age badge), improved responsive grid and mobile stacking, maintaining Daet Listens navy/gold/white design identity.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.


## 2026-11-09: Staff complaint detail page — professional investigation workspace UI/UX redesign & color correction matching Daet Listens Staff Dashboard

- Page / component: `resources/views/staff/complaints/show.blade.php` (Staff Complaint Investigation Workspace).
- Why (the problem found): staff complaint detail needed a professional complaint investigation workspace layout with an intentional two-column split, improved header hierarchy, and preserved dark-blue visual identity.
- What changed: balanced two-column layout (~65% main, ~35% side), redesigned header, vertical timeline for activity history, polished review form.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed.

## 2026-11-09: Staff department dashboard — footer anchoring + mobile bottom nav & complaint-card layout fix

- Page / component: `resources/css/app.css` (global page shell), `resources/css/pages/staff-dashboard.css`
  (`body.page-staff-dashboard` staff dashboard); view markup in
  `resources/views/staff/dashboard/index.blade.php` unchanged (the defect was in the shell flow).
- Why (the problem found): on `/staff/dashboard` the footer was not anchored to the end of the page —
  on desktop it rendered mid-screen and appeared to split the priority complaint list in two; on
  mobile the fixed bottom nav floated into the content area and the search, filter chips and
  complaint cards overlapped the Recent Activity card.
- What changed (presentation/display formatting only; **no routes, queries, permissions, or
  data logic changed**):
  - **Page shell normal flow:** `body` is now a flex column with `min-height: 100vh` and `main`
    grows (`flex: 1 0 auto`), so the shared site footer (rendered after `main`) anchors to the end
    of the page instead of sitting under short content / mid-viewport.
  - **Mobile bottom nav:** `body.page-staff-dashboard .staff-content` keeps
    `padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px))` and now relaxes at
    `@media (min-width: 1024px)` — the bottom nav's own breakpoint — instead of 768px, so the
    fixed bottom nav stays pinned to the screen bottom with content clearance through the whole
    mobile range (incl. 768–1023px where it was previously uncovered).
  - **Complaint cards:** at ≤1100px the card grid moved from fixed minimum tracks
    (`minmax(120px, 1.6fr) / minmax(80px, 1fr) / …`) to shrinkable tracks (`minmax(0, …)`) and the
    forced `grid-column: 3` age placement was dropped — badges no longer overlap card text and the
    card no longer forces horizontal overflow.
- Verification: design-system guard — no NEW violations introduced by this change (the 19
  pre-existing NEW violations in untouched files are unchanged); `php artisan test --filter=StaffWorkspaceTest|PageOverflowTest`
  green (staff dashboard render + body/main overflow rules intact); the design-system guard failure
  and the admin mobile-navigation menu test failure are pre-existing and unrelated to this change.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: footer-after-main flow ✓ (page shell); single footer ✓; complaint cards
  overflow-safe ✓; staff shell sticky sidebar, bottom nav z-index (800) / drawer (1010) / toast
  (1100) z-contract untouched; no new i18n keys; no new colours/hex added; focus rings and
  `prefers-reduced-motion` untouched; no routes/API/data logic changed.

## 2026-11-09: Department Account Dashboard — regression fix (broken dashboard render)

- Root cause: the `--dept-*` design tokens were declared **outside** `:root` in
  `resources/css/tokens.css` (the closing `}` preceded them), so the CSS parser
  dropped them and every `var(--dept-*)` on the dashboard resolved to empty — the
  hero gradient, stat cards, review buttons, selected chip, status pills, card
  borders and activity dots all rendered invisible/unstyled. Compound regressions:
  `staff-shell` used flex with a leftover `margin-left: 292px` from the old dark
  operator rail (main column ~300px in a huge empty area), the activity panel was
  rendered twice (main grid + `@section('staff-aside')`), the complaint card used
  `--mobile`/`--desktop` duplicate markup with `display:contents` (double mobile
  priority chip), and an inline `<style>` block lived in the view with `!important`
  rules.
- What changed (presentation/display formatting only; **no routes, queries,
  permissions, or controller logic changed**):
  - **Tokens:** moved the complete `--dept-*` block **inside** `:root` in
    `resources/css/tokens.css` (page `#F4F7FC`, card border/shadow, hero
    `#1E3A8A→#2563EB→#3B82F6`, overlay/border, gold chip `#E4B94A`, primary blue
    `#1D4ED8`, active/hover, ink/muted, priority/status/stat-icon tints). All
    specified `--dept-*` tokens now resolve on `body`; `--dept-tint`/`--dept-accent`
    remain per-`[data-dept]` element tokens as before.
  - **CSS extraction:** moved the ~170-line inline `<style>` block out of
    `resources/views/staff/dashboard/index.blade.php` into
    `resources/css/pages/staff-dashboard.css` (loaded via `@import` in `app.css`,
    i.e. through Vite), scoped under `body.page-staff-dashboard`; removed every
    `!important` and dead rules (incl. the dead chip auto-width rule).
  - **Layout (≥1024):** `staff-shell` is now `grid: 248px minmax(0,1fr)`, gap 24px,
    max-width 1440px centered, 24px side padding; removed the leftover
    `margin-left: 292px` old-dark-rail rule. Dashboard grid ≥1280px =
    `[content minmax(0,1fr)][activity 320px sticky]`; <1280px single column with
    activity below. Content fills its column (hero/stats/filters/cards full width).
  - **Sidebar:** single white card (`#FFFFFF`, 1px `#E2E8F3`, radius 16px, padding
    16px, `var(--dept-card-shadow)`), sticky at `calc(var(--navbar-height)+16px)` with
    `max-height calc(100dvh - var(--navbar-height) - 32px)` and `overflow-y:auto`; no
    clipping parent, no fixed small height, no stray gold arc/border-radius fragment.
  - **Complaint card:** priority pill, status pill and Review button rendered ONCE
    (deleted `--mobile`/`--desktop` duplicates and `display:contents`); CSS grid
    areas — mobile single column, ≥768px `[content][status/priority/Review]` with
    Review auto-width min 120px.
  - **Filters:** button renamed to "Search", form action gained
    `#priority-complaints-title`, unselected chips white/#E2E8F3/ink, selected chip
    `#1D4ED8`/white/gold bottom line, counts in a muted badge.
  - **Activity:** thin vertical connector between dots, panel rendered exactly once
    (removed the duplicate `@section('staff-aside')` include), empty `<details>`
    "Show more" replaced by a real `aria-expanded`/`aria-controls` button with vanilla
    JS toggle, and `formatActivityDisplay()` strips `Review notes: ...` from the
    detail, dedupes repeated note segments ("lala · lala" → "lala"), and shows the
    note once as "Note: lala".
  - **Stats:** 4-across via `repeat(auto-fit, minmax(150px,1fr))` at ≥768px, labels
    max 2 lines without "...", `<1` + `day` on one line, removed stat side padding so
    cards align with hero edges.
  - **Mobile:** `padding-bottom: calc(72px + env(safe-area-inset-bottom))` so the
    fixed bottom nav never covers the footer/"View all activity".
- Verification: headless Playwright (Chromium) at 1440px, 1100px, 393px top/bottom
  and mobile drawer; `getComputedStyle` confirms all `--dept-*` and base tokens
  resolve; a `var(--*)` resolution check script passes (278 tokens, none empty on
  the dashboard). `php artisan test --filter=StaffWorkspace` → 6 passed.
- Verification screenshots: captured (`/tmp/opencode/ss-desktop-1440.png`,
  `ss-desktop-1100.png`, `ss-mobile-393-top.png`, `ss-mobile-393-bottom.png`,
  `ss-mobile-drawer.png`).

- Page / component: `resources/views/staff/dashboard/index.blade.php`,
  `resources/views/staff/dashboard/partials/activity.blade.php`,
  `resources/views/staff/layouts/app.blade.php`,
  `resources/views/staff/partials/sidebar.blade.php`,
  `resources/views/layouts/navbar.blade.php`,
  `resources/views/layouts/partials/mobile-drawer.blade.php` (verified),
  `app/Http/Controllers/StaffController.php` (display formatting only),
  `resources/css/app.css`, `resources/css/tokens.css`,
  `lang/en|fil/staff.php`, `lang/en|fil/nav.php`.
- Why (the problem found): pass 2 still rendered with dark/heavy department surfaces and
  conflicting old staff CSS (dark sidebar box/invisible text, unreadable/poorly placed dept
  pill, hero dark block behind stat overlap, duplicate/stretched mobile priority chip, noisy
  activity text, cramped stat labels, and header wrapping/truncation on mobile).
- What changed (presentation/display formatting only; **no routes, queries, permissions, or
  data logic changed**):
  - **Color/tokens:** added department workspace tokens in `tokens.css` (`--dept-*`: page
    `#F4F7FC`, card border/shadow, brighter hero `#1E3A8A→#2563EB→#3B82F6` with ~8% texture,
    hero identity overlay/border, gold chip `#E4B94A`, primary/review blue `#1D4ED8`/hover
    `#1E40AF`, active/hover/sidebar surfaces, priority/status/stat-icon tints) and applied
    them across the staff dashboard. Submitted status pill uses dept blue tint; WCAG AA
    text/background pairs targeted.
  - **Sidebar (definitive):** removed conflicting outer/double card styling; single white
    card (248px, sticky, dept-card border/shadow), Assigned Department gradient card
    (`#EAF1FF→#F5F8FF`, gold GSO chip), Workspace/Verification sections with
    "Needs verification" count badge (display count, existing authorized scope), bottom
    Language toggle (EN/FIL visible) + Settings. All text dark navy/muted on white; active
    item `#EAF1FF` + blue text + 3px gold left bar.
  - **Layout:** 3 zones at ≥1280px (sidebar 248px | main flex | activity 320px sticky), gap
    24px, max 1440px centered; 1024–1279px sidebar+main with activity below complaints in
    main; `staff.layouts.app` supports optional `staff-aside`. Hero contained (20px radius,
    52px bottom padding) with 24px stat overlap and no stray dark block; desktop hero CTA
    on the right (flex). Stat cards redesigned vertical (36px icon circle, 28px number,
    12px uppercase label max 2 lines, 4-across). Avg days renders `<1` + `day` unit or
    `N.N` + `days` or `—`, `white-space: nowrap`.
  - **Header/navbar:** compact pill (28px, `rgba(255,255,255,.12)` + gold border, explicit
    white text/icon, nowrap) placed after logo block; desktop shows `Department · GSO`;
    mobile shows icon+`GSO` only, hides LGU subline, wordmark 16px `flex-shrink:0` never
    truncated, 40px bell/hamburger, one-row no-wrap at 360px. 3px full-width gold strip
    under header on staff pages (`body.role-staff #navbar::after`).
  - **Complaint cards:** mobile priority chip rendered once, `inline-flex` auto-width (not
    stretched); mobile order ticket+status, title, category, priority chip, suggested/confirmed,
    filed, full-width Review. Relative time uses `diffForHumans()` (includes "ago"; "just now"
    under 1 hour) with exact date in `title`. Desktop compact 2-col with blue Review button.
    Selected chip blue/white with thin gold underline.
  - **Activity:** display formatting strips "Administrative review:" prefix, dedupes note
    segments/labels (shows `Note:` once or omits), separates status and note onto lines,
    shows "Status: ..." detail; timeline dot/line style, max 5 + View all (controller
    display-only formatting).
  - **Mobile extras:** bottom nav covers no content (`staff-content` padding-bottom includes
    `88px + env(safe-area-inset-bottom)` on dashboard mobile); bottom nav white, active blue
    + 3px gold top bar, Verify badge gold/navy. Site drawer verified in markup for
    "Maria Santos / Department Account · GSO" and readable links/toggles.
- Tokens/components: reuses existing design tokens where close; adds documented `--dept-*`
  workspace tokens for the brighter department identity (tokens.css). No serif, no
  hard-coded hex added to component markup; guard shows no new violations in files touched
  by this pass.
- Verification screenshots (captured 2026-11-09, headless Chromium/Playwright 1.65, staff
  login `gso@daetlistens.gov.ph`, server `http://127.0.0.1:8000`):
  - `/tmp/opencode/staff-pass3/desktop-1440-full.png` (full page)
  - `/tmp/opencode/staff-pass3/desktop-1100.png` (full page)
  - `/tmp/opencode/staff-pass3/desktop-1024.png` (full page)
  - `/tmp/opencode/staff-pass3/mobile-393-top.png` (top)
  - `/tmp/opencode/staff-pass3/mobile-393-bottom.png` (scrolled to bottom)
  - `/tmp/opencode/staff-pass3/mobile-393-drawer-site.png` (mobile site drawer open)
  - `/tmp/opencode/staff-pass3/mobile-360-header.png` (header close-up)
  - `/tmp/opencode/staff-pass3/mobile-360-full.png` (full page, extra)
  Automated capture only; see final report for items requiring human visual confirmation.

## 2026-11-09: Department Account Dashboard — resident-UI redesign (staff)

- Page / component: `resources/views/staff/dashboard/index.blade.php`,
  `resources/views/staff/layouts/app.blade.php`, `resources/views/staff/partials/sidebar.blade.php`,
  `resources/views/layouts/navbar.blade.php`, `resources/views/layouts/partials/mobile-drawer.blade.php`,
  `app/Http/Controllers/StaffController.php` (dashboard data formatting/filtering only),
  `resources/css/app.css`, `lang/en|fil/staff.php`, `lang/en|fil/nav.php`.
- Why (the problem found): the department dashboard used the old dark-navy staff shell and
  diverged from the finished resident UI; it hid department identity, showed duplicate labels,
  overlapped the activity panel on desktop, surfaced raw/empty metrics, and mixed cramped badges.
- What changed (UI-only; no routes/API/data logic changed):
  - Visual redesign: light `--bg` page, white cards, resident-style navy hero with diagonal
    texture, gold primary CTA, `tr-*`/status-pill/ticket/mono patterns and shared tokens.
  - Department identity: compact `Department Account` pill + dept code next to the wordmark
    (navbar), gold accent strip under the header for `role-staff`, hero identity card
    (department name, gold code chip, staff name, "Department Account", translucent gold
    border), title/subtitle per spec; duplicate department name removed from the header bar;
    Transparency moved to the staff drawer/footer to avoid bell collision.
  - Layout fixes: desktop 12-col content grid (8 stats+priority, 4 sticky activity) with no
    overlap; left sidebar restyled as a white card (Assigned Department, Workspace,
    Verification, EN/FIL, Settings; active = navy-50 tint + gold left indicator); mobile
    2×2 stat grid, collapsible activity, fixed bottom nav (Dashboard/Queue/Verify/Activity);
    staff mobile toolbar hidden on dashboard; logo wordmark kept non-truncated.
  - Data formatting fixes: days open shows "Filed today"/"1 day open"/"N days open" (no
    decimals); average days shows `—` when no data else 1 decimal; activity merges identical
    consecutive entries and replaces `?`/empty notes with `No notes added`; max 2 badges per
    card (status + priority), suggested/confirmed as one text line. Priority filter chips/search
    use GET params (display filtering only).
- Tokens/components: reuses existing tokens (`--navy-*`, `--gold-*`, `--bg/--surface/--line*`,
  `--text/--muted/--faint`, status/category tokens), `.status-pill`, `.ticket-id`, `.tr-card`,
  `.btn-nav-cta` patterns. No new design system; no new hard-coded hex in touched files
  (guard reports no `staff/dashboard` or my `app.css` hex violations). New i18n keys in
  `staff.dashboard.*`/`staff.bottom_nav.*`/`nav.department_account` (en+fil).
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: `php -l` clean on touched PHP/lang; `php artisan view:clear`/`view:cache`
  clean ✓; `scripts/design-system-guard.php` — no NEW violations in files changed by this task ✓;
  `php artisan test --filter=StaffWorkspace` — 3 passed, 3 failed on pre-existing assertions
  unrelated to this redesign (`assertSee('Staff navigation')` / `assertSee('New Staff')` in
  complaint-show and invite-success views not modified here) ✓; routes/API/data logic unchanged ✓;
  EN/FIL keys complete ✓; 44px targets, focus rings (`--ring`), aria labels on icon controls ✓.

## 2026-11-09: My Complaints — stat boxes removed (all breakpoints)

- Page / component: `resources/views/complaints/index.blade.php` +
  `app/Http/Controllers/ComplaintController.php` + `lang/{en,fil}/my_complaints.php`.
- Why (the problem found): the resident asked to remove the 3 stat boxes; beyond the
  mobile 3-cell `.mc-mobile-summary` card (Total/Open/Resolved), the desktop strip
  carried the same facts plus an average-resolution card — four counts of the same
  facts, two of which (stat strip + results summary line) the design system itself
  calls out as redundant.
- What changed: the **entire stat strip is gone** — mobile `.mc-mobile-summary` (incl.
  `.mc-summary-*`/`.mc-avg-line`, and the desktop `!important` override of `.tr-stats`)
  and the desktop `.tr-stats` 4-card grid; hero now flows straight into the filter bar.
  `$avgDays`/`$resolvedDays` computation and variable are gone; dead keys
  `my_complaints.stat_*` / `avg_*` are purged (en+fil). No lang key still in use remains
  referenced nowhere else.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: Blade compiled clean ✓; `LocalizationTest` ✓; `LegalPagesTest` ✓;
  `ComplaintReviewWorkflowTest` ✓; `MyComplaintsInfoRequestTest` ✓;
  `MyComplaintsFilterApplyTest` ✓ (47 tests green); Design system guard unchanged ✓.

## 2026-11-09: Busy / loading action state — all sectors (buttons + form submits)

- Page / component: portal-wide action pattern (all sectors) — `resources/css/app.css`
  (shared `.is-loading`/`[data-action-busy]` + `@keyframes daet-action-spin`, unified
  from legacy `.admin-button.is-loading`; `prefers-reduced-motion: reduce` uses a
  static dotted indicator), `resources/js/app.js` (`window.daetActionLoading.setBusy`,
  global submit handler binding non-GET/explicit forms, submitter resolution,
  `aria-busy`/double-submit guard, bfcache `pageshow` restore; legacy
  `data-disable-on-submit` preserved), `resources/views/pages/design-system.blade.php`
  (Busy/loading actions swatch, §5.32), `docs/design-system.md` (§5.32 + §12 contract).
  `data-loading-submit` added across action `<form>` elements in all view sectors
  (auth, complaints, settings/profile, staff, admin, invitations/public) via a
  controlled pass; `data-preferences-autosave` (fetch, no submit-button spinner)
  left opted out. Complaint wizard submit (`resources/views/complaints/create.blade.php`)
  now sets shared busy state instead of injecting a hardcoded English "Sending for
  review..." label.
- Why (the problem found): pressing Apply/Submit/Login/Save/Send/state-change actions
  gave little or no feedback and could be triggered twice; behavior was fragmented
  (admin-only spinner, settings-only `is-loading`, a disable-only guard elsewhere).
- What changed: on press, qualifying actions show an immediate inline `currentColor`
  spinner, set `aria-busy="true"`, and block repeat activation; state clears on
  navigation/settle, on validation re-render, and on bfcache restore. Works for
  standard POST/GET submits, documented fetch (`setBusy`) and Livewire
  (`wire:loading`/`wire:target`) patterns. Opt out with `data-loading="off"` /
  `data-loading-managed`. No new colours/tokens; no full-refresh behavior forced.
- Tokens/components: spinner uses `currentColor` only; no new hex/serif/spacing/radii.
  `scripts/design-system-guard.php` — NEW violation count unchanged (pre-existing 17
  unrelated NEW violations remain; touched files add none). §5.32 documented.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: design-system §5.32 + `/design-system` swatch added ✓;
  shared CSS honors `prefers-reduced-motion` ✓; global JS restores on bfcache and
  preserves `data-disable-on-submit` semantics ✓; forms carry `data-loading-submit`
  across sectors (preferences autosave opted out) ✓; complaint create no longer
  injects hardcoded busy text ✓; `php scripts/design-system-guard.php` NEW count not
  increased ✓. Manual/browser pass recommended for a representative flow per sector
  (auth login, complaint submit, settings save, staff/admin state change) at 393px
  and 1310px in `en` + `fil`, including a validation-error restore.

## 2026-11-09: Navbar identity indicator — staff/department and admin accounts visible in the top bar

- Page / component: global authenticated chrome — `resources/views/layouts/navbar.blade.php`
  (desktop `nav-actions`, compact `nav-mobile-right`, and a direct `.nav-identity-bar`
  child of `.navbar-flex` for the `body.is-auth` ≤640px shared-drawer mode),
  `resources/views/layouts/partials/mobile-drawer.blade.php` (account header),
  legacy `#mobile-menu` header in `navbar.blade.php`, and account dropdown headers.
  Styles in `resources/css/app.css` (global `.dept-badge` baseline + `.nav-identity`
  component). Design system: `docs/design-system.md` §5.31 + §12; swatch on
  `resources/views/pages/design-system.blade.php`.
- Why (the problem found): staff operating a department account and administrators had
  no persistent, at-a-glance identifier in the navbar (especially on mobile), so it was
  easy to lose context about which account/department was active.
- What changed: staff + admin now see a compact identity indicator in the fixed top
  navbar on desktop and mobile without opening menus. **Staff** show the canonical
  department badge (`.dept-badge[data-dept]`, existing §3.2 tokens/mapping) plus the
  department name (labels collapse at tight widths); staff without an assigned department
  show a neutral staff badge + "Department pending". **Admin** show an "Administrator"
  role badge using `--gold-300` on gold-500 tints (navy context). The same treatment is
  mirrored in the shared mobile drawer header, legacy mobile menu header, and dropdown
  headers. Citizens are unchanged. No `id`/`data-*`/`aria-controls` or JS contracts
  changed; z-index/offset contracts unchanged.
- Tokens/components: reuses `--dept-*`/`--dept-*-tint` + `[data-dept]`, `--gold-300`,
  `--gold-500` (tints via `color-mix`), existing `.dept-badge` shape, `--font-sans`.
  **No new colors, fonts, shadows, radii, spacing scale values, or one-off hex.** Global
  `.dept-badge` baseline added to `app.css` (token-driven). New component §5.31 documented.
- Before/after screenshots: `docs/design-log/` — none captured in this pass (no browser
  session attached). Manual verification recommended for staff (with/without department)
  and admin at 393px and 1310px, `en` + `fil`, and that the ≤640px `body.is-auth` shared
  drawer mode still shows bell + drawer toggle with the identity bar visible.
  Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: `php -l` clean on touched PHP/Blade entrypoints ✓; `php artisan test
  --filter=LocalizationTest` ✓ (7 passed, 37 assertions); `scripts/design-system-guard.php`
  reports the same pre-existing NEW violations in unrelated dirty files — **no new
  violations in files touched by this change** ✓; `id`/`data-*`/`aria-controls`/JS
  contracts unchanged ✓; z-index/offset contracts unchanged ✓; strings use existing
  `nav.*` keys present in `en`+`fil` ✓. Manual/browser pass still recommended at 393px
  and 1310px, `en`+`fil`, for staff+admin (and citizen unchanged).

## 2026-11-09: Resident ticket — "Latest update" card removed

- Page / component: `resources/views/complaints/show.blade.php` +
  `resources/css/pages/complaint-show.css` + `lang/{en,fil}/complaint_show.php`.
- Why (the problem found): the mobile "Latest update" inset duplicated the
  timeline/latest-response content the ticket already exposes in `#pcs-updates`,
  and read as if it were a separate ticket state from everything around it.
- What changed: the section is gone outright — no fallback, no `.pcs-latest`
  markup, no scoped CSS, no `$pcsLatestLog` computation, no `complaint_show.latest.*`
  keys left behind. The timeline remains the one canonical place to see the newest
  staff response / status. `.pcs-inset` (help card) untouched.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: Blade compiled clean ✓; `LocalizationTest` ✓;
  `ComplaintReviewWorkflowTest` ✓; `MyComplaintsInfoRequestTest` ✓;
  `MyComplaintsFilterApplyTest` ✓ (23 tests green); dead lang keys and CSS purged ✓.

## 2026-11-13: Resident ticket closure + 7-day auto-close for resolved reports

- Page / component: resident ticket detail (`resources/views/complaints/show.blade.php`,
  new resolved close-confirmation panel + token-only styles), `lang/en|fil/complaint_show.php`
  (`close.*` keys); backend: `POST /complaints/{complaint}/close` (`ComplaintController@close`,
  `complaints.close`), `ComplaintPolicy::close` (owner only, `Resolved` only),
  `app/Console/Commands/AutoCloseResolvedComplaints.php` (`complaints:auto-close-resolved`,
  scheduled daily 07:30 in `bootstrap/app.php`), migration
  `2026_11_13_000000_add_closure_tracking_to_complaints_table.php`
  (`resolved_at`, `closed_at`, `closed_by` on `complaints`), `Complaint` fillable/casts;
  staff/admin status updates now stamp `resolved_at`/`closed_at`/`closed_by`
  (`StaffController@update`, `AdminController@complaintUpdate`).
- Why (the problem found): residents had no way to confirm a resolved report was done for
  them (citizens cannot change status), and resolved reports never closed automatically —
  `PRODUCT.md` described a 7-day lifecycle auto-close but no implementation existed.
- What changed: (1) when a report is `Resolved`, its owner sees a confirmation panel
  (“Is this issue resolved for you?”) with a single gold “Close this report” action that
  transitions `Resolved → Closed` (enum-allowed), records `closed_at/closed_by=resident`,
  writes an immutable `ComplaintLog`, and logs `complaint.closed_by_resident`; (2) the
  `complaints:auto-close-resolved` command (daily, `--days=7`) closes `Resolved` complaints
  whose `resolved_at` (or legacy Resolved log / `updated_at` fallback) is ≥ 7 days old,
  stamping `closed_by=system` and notifying the resident via `NotificationService::statusChanged`
  (actor null; resident self-notify is skipped on resident close); (3) staff/admin marking
  `Resolved`/`Closed` now persist `resolved_at`/`closed_at`/`closed_by` for the timer/audit.
- Tokens/components: no new tokens/colours/radii/shadows. Close panel reuses
  `--status-resolved-*`, `--navy-900`, `--gold-300/500`, `--radius-sm`, `--shadow-card`,
  `--ring` (same confirmation language as §5.26/§5.29 family). No hex/serif/gold-on-white/
  vertical gold in touched files; 44px submit target + `focus-visible` ring.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: `php -l` clean on all touched PHP files ✓;
  `php artisan route:list --path=complaints` shows `POST complaints/{complaint}/close` ✓;
  `php artisan test --filter=LocalizationTest` ✓ (7 passed, 37 assertions);
  `php scripts/design-system-guard.php` reports the same 17 pre-existing NEW violations in
  unrelated legacy files — no new violations in touched files ✓; en+fil `close.*` keys
  complete ✓; `ComplaintStatus` transition `Resolved → Closed` unchanged/enforced ✓;
  migration added (run `php artisan migrate`). Manual/browser pass still recommended for the
  resolved-owner panel at 393px/1310px in en+fil.

## 2026-11-09: Resident — needs-information is now one story (ticket panel + admin ask parity)

- Page / component: resident ticket detail (`resources/views/complaints/show.blade.php`,
  new §5.29 panel + styles) + resident My Complaints attention filter
  (`app/Http/Controllers/ComplaintController.php`) + staff show review dropdown trim
  (`StaffController`) + admin complaint page (`resources/views/admin/complaints/show.blade.php`)
  + new route `admin.complaints.verification.request-information` + service invariant in
  `ComplaintVerificationService::reviewUpdates()` (needs_information only via ask-flow) +
  `recordResidentReply()` auto-creates the request for legacy orphans; FK
  `information_requests.requested_by` now nullable (migration
  2026_11_09_023405). Guide §5.29 + §12, docs/design-system changelog line; swatch on
  `/design-system`. Lang: `complaint_show.action.kicker/lede/fallback_question/from_office`
  (en + fil).
- Why (the problem found): when staff/admin flagged a complaint `needs_information`
  through the plain review dropdown, the resident saw the amber strip linking to the
  ticket but **no form anywhere to answer it** — the amber ask was empty air; admin had
  no way to even ask a resident anything. The response UI also only rendered when an
  `information_requests` row existed, so legacy flagged tickets could never be answered.
- What changed: (1) the ticket page renders a dedicated **needs-information panel**
  (state title + amber kicker + lede + ask card + reply form or answered box) whenever
  there is an open/answered request — or the ticket is flagged `needs_information`
  (legacy orphan, reviewer-note text shown or a generic fallback); (2) the attention
  strip on My Complaints keys off live `open` information requests, so the banner
  always lands on a real reply form; (3) staff+admin review dropdowns no longer offer
  `needs_information` and the service refuses it (invariant: needs_information always
  carries a question); (4) admin complaint page gains the same "Ask the resident for
  more evidence" panel as staff; (5) `recordResidentReply()` materialises a request
  from the reviewer's note for orphan flagging, so legacy tickets become answerable
  and the timeline counts it.
- Tokens/components: no new colours/radii; §5.29 documented; `/design-system` swatch
  added; guard reports no NEW violations in touched files.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: `php artisan test --filter=MyComplaintsInfoRequestTest` ✓
  (5 tests, 16 assertions — open-request panel, resident reply → answered, orphan
  auto-materialise, admin asks, staff dropdown trim); `LocalizationTest` ✓;
  `LegalPagesTest` ✓; `php artisan migrate` applied on the dev database ✓; Design
  system guard unchanged ✓.

## 2026-11-09: My Complaints — filter selects are drafts, never auto-apply

- Page / component: `resources/views/complaints/index.blade.php` +
  `tests/Feature/MyComplaintsFilterApplyTest.php` (new). No markup structure, tokens,
  routes or lang keys changed.
- Why (the problem found): every select in the filter panel carried inline
  `onchange="this.form.submit()"` — choosing a category fired a full reload before the
  resident finished picking, and the Apply button was dead weight.
- What changed: selects (department/category/sort) and the search field are drafts;
  nothing auto-submits. "Apply" (gold, `type="submit"`) commits once on all
  breakpoints — now visible on desktop too, inline after the sort select at
  ≥1025px, right-aligned second row at ≤1024px (`@media (min-width:641px) and
  (max-width:1024px)`, `flex-wrap`); "Clear filters" links to the bare route. Closing
  the popover (toggle / outside / Esc) reverts unsaved drafts to the applied URL.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: `php artisan test --filter=MyComplaintsFilterApplyTest` ✓
  (no onchange on any select, Apply is type=submit, Clear links to bare index,
  popover toggle + panel present); `LocalizationTest` + `LegalPagesTest` ✓;
  My Complaints popover icons untouched, no hex/serif/gold-on-white added ✓.

## 2026-11-09: Complaint detail — pending information request promoted to top of page + answered state renders the reply thread

- Page / component: `resources/views/complaints/show.blade.php` (resident complaint detail, `/complaints/{id}`).
- Why (the problem found): the information-request block — the only resident path to reply into a ticket — sat below the mobile "latest update" inset and below the supporting content, so a pending ask could read as lost in a wall of text; after answering, it only swapped in a bare thank-you instead of showing the question → answer thread.
- What changed: the block now renders at the top of the complaint body (immediately after the back link, before the latest-update inset and all supporting cards) whenever an open/answered request exists, and only then. Pending: one card (ask title / asked-by / question / reply form). Answered: same card becomes the inline Q&A thread (reply label + response text); the form is gone.
- Tokens/components: no new tokens; new page-scoped `.info-request__answer*` styles use tokens only; no hex/serif; no z-index change.
- Verification: design-system-guard — no NEW violations from this change; `php artisan test --filter=ResidentInformationResponseTest` → 21 passed (incl. "the complaint shows the outstanding question to its owner" and "the reply form disappears once the resident has answered"); `php artisan test --filter=ComplaintReviewWorkflowTest` → 14 passed; Blade recompiled clean after `php artisan view:clear`.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done: §11 checklist — one `<h1>`/heading structure unchanged; raw lang keys unchanged (reused existing `complaint_show.action.*` keys, en+fil complete); no hard-coded hex/serif in touched files; no new list markup (single conditional block, one markup); no new tokens.

## 2026-11-09: My Complaints — Filters panel is now an anchored popover (≤640px)

- Page / component: `resources/views/complaints/index.blade.php` (My Complaints /
  `/complaints`, resident home) + `resources/css/tokens.css` (`--z-popover`) +
  `docs/design-system.md` (new §5.28, §6 z-row, §12 entry) +
  `resources/views/pages/design-system.blade.php` (popover swatch + z-table row).
  No copy, routes, markup structure or lang keys changed.
- Why (the problem found): tapping Filters expanded the panel in-flow — the whole
  chips row and list jumped down under the resident's thumb. The request matched
  the standard mobile pattern: the panel must hang **below the Filters button**,
  floating over the content as one overlay surface.
- What changed (≤640px only, scoped to `body.page-complaints-index`): `.mc-filters-panel`
  is now `position:absolute` under the relative `.tr-filters__row1`
  (`top: calc(100% + 8px); right: 0; width: min(100%, 340px); z-index: var(--z-popover)`
  = 500), padded `--radius-sm` card with `--surface` + 1px `--line-soft` +
  `--shadow-lift` (grid/selects/actions markup unchanged). It overlays the chips and
  list — opening/closing never reflows the page (asserted: list `getBoundingClientRect().top`
  identical before/after). Close paths: Filters toggle (aria-expanded/aria-hidden sync),
  a tap/click outside the popover, `Escape` (returns focus to the toggle). Desktop
  (≥641px) unchanged — inline always-open panel, toggle hidden — proven pixel-identical.
  New `--z-popover: 500` token documented in §6 (above content, below navbar/drawer/toast).
- Tokens/components: no new colours/radii/shadows. New z layer `--z-popover` (500)
  added to the LOCKED z-contract table with the "why" inline. New component spec
  §5.28 + swatch on `/design-system`. `scripts/design-system-guard.php` reports the
  same 17 pre-existing NEW violations in unrelated legacy files; the touched files
  add none.
- Verification screenshots: captured 2026-11-09 during the live-browser pass (where
  reflow, z-index and toggle/escape/outside-click behavior were exercised with
  Playwright, since removed from the project at the owner's request); the images
  themselves were removed at the same time. Verification otherwise as below.
- Definition of Done: 1310×900 + 393×852, en + fil ✓; live browser pass at 393px
  (en + fil): panel collapsed by default, tap opens below the button,
  `z-index:500`, anchored right edge aligned to the button, `elementFromPoint` hits
  the panel, `Escape` closes + focus returns to the trigger, outside tap closes,
  list reflow zero, no console/page errors; ≥641px panel inline + toggle hidden
  ✓; `php artisan test --filter=LocalizationTest` ✓; `/design-system` renders HTTP
  200 with the popover demo ✓; one markup per list unchanged ✓; z-contract updated
  in guide + tokens, not violated (navbar 900 / drawer 1010 / overlay 1100 /
  map 0 untouched) ✓; 44px targets and reduced-motion untouched ✓.

## 2026-11-09: My Complaints — Filters toggle never wired up (script ran before the DOM)

- Page / component: `resources/views/complaints/index.blade.php` (My Complaints /
  `/complaints`, resident home). No markup, styles, tokens, copy, routes, or lang
  keys changed.
- Why (the problem found): on ≤640px the mobile toolkit redesign hid the filter
  panel behind the `#mc-filters-toggle` button and collapsed it by default, but
  the IIFE that binds the `click` handler and the `matchMedia('(min-width: 641px)')`
  sync was pushed via `@push('styles')`, which `@stack('styles')` renders in
  `<head>`. It ran before the button and `#mc-filters-panel` existed, so every
  lookup returned `null`, no listener was ever attached, and tapping
  "Filters / Mga filter" did nothing — the department/category/sort dropdowns were
  undroppable on mobile. Desktop was silently affected too: the panel only rendered
  because `@media (min-width: 641px)` forces `display:flex !important`, while the
  stale `hidden` attribute and `aria-hidden="true"` stayed on an element screen
  readers therefore skipped. Verified live (Playwright, seeded resident): panel
  opened never, button inert.
- What changed: the binding script moved out of `@push('styles')` into
  `@push('scripts')` (end of `<body>`, after all markup) so the DOM exists when it
  runs, and now guards with `data-filters-bound` so a re-init after `wire:navigate`
  cannot double-bind. No behavior contract changed: ≤640px panel toggles on tap
  (with `aria-expanded`/`aria-hidden` sync), ≥641px the panel is forced visible and
  the button stays hidden, `ResizeObserver`-style sync unchanged, chip scroll-into-
  view unchanged.
- Tokens/components: no new tokens, components, or one-off CSS; no hex/serif/z-index
  in touched files. `scripts/design-system-guard.php` reports the same 17 pre-existing
  NEW violations in unrelated legacy files; the touched file adds none.
- Verification screenshots: captured 2026-11-09 during the live-browser pass, then
  removed at the owner's request (see the waiver note in `docs/ui-verification.md`).
- Definition of Done: 1310×900 + 393×852, en + fil ✓; live browser pass —
  mobile: panel collapsed initially, tap opens (panel visible, `aria-hidden=false`,
  `aria-expanded=true`), tap closes; desktop: panel visible, department dropdown
  round-trips `?department=…`, row count filtered 11 → 1, no console errors ✓;
  `php artisan test --filter=LocalizationTest` ✓ (7/7); `php artisan view:clear`
  + Blade view compiled clean ✓; single markup per list unchanged ✓; z-index
  contract untouched ✓.

## 2026-11-09: Complaint intake — category-driven photo/location (no redesign)

- Page / component: `resources/views/complaints/create.blade.php` (wizard JS/data
  hooks + minimal functional text hooks), `app/Http/Controllers/ComplaintController.php`,
  `app/Models/Complaint.php`, `config/complaint_intake_policy.php` (new),
  `database/migrations/2026_11_09_005902_add_intake_mode_to_complaints_table.php` (new);
  `lang/en,fil/complaint_create.php` + `lang/en,fil/complaint_success.php`;
  `resources/views/complaints/success.blade.php`.
- Why (the problem found): some report types don’t need incident location/photo
  evidence at filing (privacy/sensitive) or can’t provide it yet; forcing the same
  steps for all categories collected unnecessary/sensitive data and blocked valid
  reports. Policy: physical issues (road/trash etc.) need evidence+location;
  sensitive/privacy types (e.g. `business_permit`) and `others` may not require
  photo/location at intake; `noise_complaint`/`stray_animals` need barangay+landmark
  with photo optional but encouraged; `others` handled via follow-up (Option A).
  Photos only (no video, no auto face detection) in this pass. Pin step: added a
  “Remove pin” control so a dropped map pin can be cleared.
- What changed (logic only, no visual redesign): extensible
  `config/complaint_intake_policy.php` maps categories to modes
  (`physical_required`, `proof_optional`, `sensitive_intake`, `follow_up_optional`).
  Create wizard hides/skips inapplicable `photos/where/pin` steps and updates
  progress/validation for the selected category (client + server authoritative).
  `proof_optional` (noise/stray, 1a) requires `barangay` + `address_text` (landmark),
  photos optional/encouraged. `sensitive_intake`/`follow_up_optional` require no
  photo/location at intake; server ignores hidden intake evidence/location, skips
  EXIF/pin-conflict as location, and **never** falls back to `User.barangay` as
  incident location. Complaints store `intake_mode`; legacy null treated as
  physical/applicable. Follow-up uses existing information-request/Action required
  reply flow. Success copy handles `location_source=not_applicable`.
- Tokens/components: no new tokens, no layout/CSS redesign. Reused existing wizard
  markup, classes, and patterns; only minimal functional hooks (data/ids/text toggles)
  and i18n keys.
- Before/after screenshots (`docs/design-log/`): **pending capture in environment**
  (desktop+mobile, en+fil) — physical_required unchanged; `business_permit`/`others`
  hide photo+location steps; `noise_complaint`/`stray_animals` show steps with optional
  photo and required barangay+landmark.
- Definition of Done: server validation is mode-authoritative ✓; `view:clear` run ✓;
  migration applied ✓; `en`+`fil` keys added ✓; existing physical_required path
  preserved + backward-compat barangay fallback kept for legacy physical forms ✓;
  screenshots/visual verification in target environment still required.

## 2026-11-09: Custom error pages — branded standalone shell for 401/403/404/419/429/500/503

- Page / component: `resources/views/errors/layout.blade.php`,
  `errors/{401,403,404,419,429,500,503,4xx,5xx}.blade.php`,
  `errors/partials/icon.blade.php`; `resources/css/pages/error.css` (imported
  from `resources/css/app.css`); `lang/en/errors.php` + `lang/fil/errors.php`;
  preview section in `resources/views/pages/design-system.blade.php`.
- Why (the problem found): every non-validation failure fell through to
  Laravel's stock vendor page — no LGU mark, no portal type, no way back except
  the browser, and copy that told a resident nothing. 403/404 aborts are
  reachable from complaint and transparency routes, 419 from expired forms, 429
  from throttling and 500/503 are the states a public portal is judged on. A
  normal page shell could not be reused safely: the app navbar's notification
  bell and the shared site footer both query the database (`Notification`,
  `SystemSetting`), so a database outage would fail again while rendering the
  500 page.
- What changed: new standalone Shell D (§4) and §5.27. A navy-900 brand header
  (64px, seal + DAET LISTENS + LGU line, links home) and a minimal navy-950
  footer replace the database-backed chrome. One centered 640px `.error-card`
  carries a 64px navy-50 icon circle (status-keyed: search/lock/clock/server/
  alert), a 12px `Error <code>` kicker, one `<h1>` (34px / 28px mobile), a
  one-line message (max 46ch), and exactly ONE gold primary action plus one
  secondary: “Go back” (history, hidden until `history.length > 1`) on
  401/403/404/419 and “Try again” (reload current URL) on 429/500/503.
  `401` sends primary to `route('login')`; all others send home. Copy is fully
  translated in `lang/{en,fil}/errors.php`; generic `4xx`/`5xx` views cover any
  other HTTP status. No raw exception text or stack trace is printed. The
  stylesheet is loaded through `Vite::asset()` inside a try/catch so a broken
  asset manifest degrades to an unstyled — but readable — page instead of
  recursively failing.
- Tokens/components: no new tokens; reuses `.tr-container` (`partials.public-ui`),
  `.tr-card`/`.tr-button`/`.empty-state` (`component-styles`) and existing
  `--navy-*`, `--gold-*`, `--bg`, `--surface`, `--line-soft`, `--text`,
  `--muted`, `--radius-*`, `--shadow-*`, `--ring`, `--tracking-display`.
  No hard-coded hex, no serif, no gold-on-white, no vertical gold line.
  `scripts/design-system-guard.php` clean for the touched files.
- Before/after screenshots (`docs/design-log/`): before = stock Laravel 404/500
  page; after = 404 and 500 at 1310×900 and 393×852, en + fil:
  `2026-11-09-error-404-before-stock-laravel.png`,
  `2026-11-09-error-404-en-desktop-1310x900.png`,
  `2026-11-09-error-404-en-mobile-393x852.png`,
  `2026-11-09-error-404-fil-desktop-1310x900.png`,
  `2026-11-09-error-404-fil-mobile-393x852.png`,
  `2026-11-09-error-500-before-stock-laravel.png`,
  `2026-11-09-error-500-en-desktop-1310x900.png`,
  `2026-11-09-error-500-en-mobile-393x852.png`,
  `2026-11-09-error-500-fil-desktop-1310x900.png`,
  `2026-11-09-error-500-fil-mobile-393x852.png`.
- Definition of Done: 1310×900 + 393×852, en + fil ✓ (Playwright captures;
  `scrollWidth ≤ innerWidth` at 393); exactly one `<h1>` and one `<footer>` ✓;
  every string in `en` + `fil`, no raw keys ✓; no hard-coded hex/serif/
  gold-on-white/vertical-gold in the touched files ✓; no lists or map on these
  pages (z-contract untouched) ✓; AA text, 44px mobile targets, visible focus
  ring, `prefers-reduced-motion` removes the CTA lift ✓; `php artisan view:clear`
  + `npm run build` clean ✓; guard reports no NEW violations ✓.

## 2026-11-09: File a complaint — map pin restricted to Daet's municipal boundary

- Page / component: `resources/views/complaints/create.blade.php` (pin-step help copy, `#daet-boundary-data`, Leaflet `maxBounds` + client boundary check, `#locate-status` error state), `resources/css/pages/complaint-create.css` (`.fc-locate-status.is-error`), `lang/en|fil/complaint_create.php` (`map.*`, `errors.outside_daet`), server `app/Services/DaetBoundary.php` + `app/Rules/WithinDaet.php` + `ComplaintController` validation, `resources/data/daet-boundary.geojson` (geoBoundaries PHL ADM3 simplified, CC BY 3.0 IGO, NAMRIA/PSA/OCHA PH), `config/daet-barangays.php`.
- Why (the problem found): the filing map accepted clicks anywhere and the server only checked `-90..90 / -180..180`, so a resident could place a pin outside Daet. OSM/Nominatim has no usable Daet polygon; geoBoundaries provides a small simplified ADM3 polygon.
- What changed: pins are allowed inside the polygon or within a 250 m edge tolerance (the simplified line is not survey-grade). Client: map center/bounds come from the boundary, clicks/drags/geolocation outside are blocked with an inline status message, and invalid saved/draft pins are cleared. Server: `latitude` is validated with `WithinDaet`; out-of-town pins fail with `complaint_create.errors.outside_daet`. If the boundary file is missing, enforcement fails open and logs a warning.
- Tokens/components: only existing `--error-text` (via `.fc-locate-status.is-error`); no new tokens, no hard-coded hex, no new components. Map wrapper already had `isolation: isolate; z-index: 0`.
- Screenshots (required by AGENTS.md, §11): pending — capture `/complaints/create` at 393px and 1310px, `en` and `fil`, showing the pin step and an outside-Daet rejection state. Add as `docs/design-log/2026-11-09-complaint-boundary-{en,fil}-{393,1310}.png`.
- Definition of Done: targeted boundary tests pass (`DaetBoundaryTest`, `ComplaintLocationBoundaryTest`, 12 passed/52 assertions). `ComplaintLocationChoiceTest` + `ComplaintMapRenderingTest` unaffected by this change except a pre-existing dirty-tree failure in `complaints/show.blade.php` (map-unavailable copy). Design-system guard NEW violations are in unrelated dirty files; no new violations introduced by this change.

## 2026-11-08: Landing page spacing tightened — navbar-to-hero gap 79px → 15px (desktop + mobile)

- Page / component: guest landing (`/`) — `resources/views/pages/home/guest.blade.php`
  (`.lp-hero`, `.lp-hero__inner`, `.lp-btn`, `.lp-section`, `.lp-tile`, `.lp-step`,
  `.lp-happening`, `.lp-breakdown`, `.lp-row`, `.lp-trust-card`, `.lp-faq`, `.lp-final`,
  phone height) and `resources/css/app.css` (body navbar-offset cancel list).
- Why (the problem found): the hero sat far below the fixed navbar on both
  breakpoints. Root cause was a double navbar offset, the repeated §10 failure:
  the global `body { padding-top: var(--navbar-h) }` (64px, `app.css`) pushed the
  whole page down, then `.lp-hero` added `var(--navbar-height)` again and
  `.lp-hero__inner` added another 56px (mobile) / 48px (desktop) on top — measured
  in a real browser (Playwright, 1310×900 and 393×852) the h1 sat 79px below
  the navbar's bottom edge (hero top 64 + hero padding 80 = 144 from page top)
  after the first inner-padding pass, and ~120px before it. The landing page
  was missing from the `body:has(...)` cancel list that Transparency and the
  citizen dashboard already use, so its navy never butted against the navbar.
  Throughout the page, off-scale paddings/margins (20/28/40/56/64) and tall
  section bands made every block feel distant.
- What changed: `.lp-root` joins the cancel list
  (`body:has(.lp-root) { padding-top: 0 }`, same mechanism as §4's banner rule —
  the navbar offset now lives inside the hero only), and `.lp-hero` follows the
  §4 banner formula `padding: calc(var(--navbar-height, 64px) + 16px) 0 24px`
  with `.lp-hero__inner` top padding removed (56/48 → 0, desktop `min-height:
  440px` dropped, phone 430 → 400px). Visible navbar-to-h1 gap is now 15px
  measured (16px nominal minus the 1px navbar border) at both 1310 and 393.
  Spacing tightened one step down the 4·8·12·16·24·32·48 scale everywhere else:
  hero grid gap 32/64 → 24/48, copy bottom 32 → 24, h1/sub bottom 20/28 → 16/24;
  buttons 52/56px → 48px min-height, padding 12·28 → 12·24, gap 10 → 8 (sticky
  CTA 52 → 48); sections 48/64 → 32/48, section sub 32 → 24; tiles 96 → 80px
  min-height, rows 68 → 60, steps 28 → 24, happening 32/48 → 24/32, breakdown
  24·20 → 16, trust cards 20 → 16, FAQ 40 → 32 / question 56 → 48, final band
  56 → 48. All interactive targets stay ≥44px (buttons/FAQ/sticky at 48).
  No copy, routes, data, or lang keys changed.
- Tokens/components: no new tokens, no hex/serif — only existing `--navy-*`,
  `--gold-*`, `--surface`, `--line-soft`, `--navbar-height`, `--radius-lg`,
  `--shadow-card`, `--ring`, `--font-sans`; every new spacing value is on the
  scale (16/24/32/48). `scripts/design-system-guard.php` reports the same 17
  pre-existing NEW violations in unrelated legacy files; the touched files add
  none. `npm run build` clean (rebuilt so the `app.css` cancel-list change
  reaches the served bundle).
- Before/after screenshots: before = the reporter's 1310px + mobile captures
  (h1 ~120px below the navbar); after (`docs/design-log/`):
  `2026-11-08-landing-spacing-en-desktop-1310x900.png`,
  `2026-11-08-landing-spacing-en-mobile-393x852.png`,
  `2026-11-08-landing-spacing-fil-desktop-1310x900.png`,
  `2026-11-08-landing-spacing-fil-mobile-393x852.png`.
- Definition of Done: 1310×900 + 393×852, en + fil ✓ (Playwright: h1 top 80px,
  navbar bottom 65px, visible gap 15px on all four; `scrollWidth ≤ innerWidth`,
  1310 and 393, no horizontal scroll ✓); exactly one h1, one footer ✓; no raw
  lang keys added, no strings changed ✓; no hard-coded hex/serif/gold-on-white/
  vertical-gold in touched files ✓; single markup per list unchanged ✓; no map
  on this page (z-contract untouched) ✓; AA text unchanged, 48px targets,
  reduced-motion block untouched ✓; `php artisan view:clear` + `npm run build`
  clean ✓.

## 2026-11-08: My Complaints mobile redesign (pass 3, ≤640px, citizen home)

- Page / component: `resources/views/complaints/index.blade.php` (body class
  `page-complaints-index`, responsive hero/list-header/toolbar/count markup;
  mobile attention strip rendered under hero, desktop copy preserved after the
  stat strip for 1310px parity). Lang: `lang/en|fil/my_complaints.php`
  (`list_heading_mobile`, `count` plural, mobile-oriented `search_placeholder`;
  desktop keys unchanged). Shared partials/CSS **not** modified: `report-row`,
  `public-ui`, `component-styles`, layout/footer.
- Why (the problem found): at 390px the page stacked nested cards (toolbar box →
  list box → row box) so text started ~46px vs a 16px edge, the report row had no
  single reading order (category·ticket with a stray dot, status floating alone on
  a right-aligned row, then title, then progress), the hero carried a small button
  plus an "Updated" line and the stat tiles read detached, the count was shown
  twice, the Filters control had no icon/badge and status chips were clipped with
  no scroll hint, and the footer was heavy with uneven link widths. (Footer
  redesign was reverted on request — the shared footer is unchanged.)
- What changed (≤640px only; `@media (max-width:640px)`, scoped to
  `body.page-complaints-index`; desktop/≥641px pixel-identical in structure and
  keys, `@media (min-width:641px)` panel rules preserved): one 16px edge and flat
  surfaces (no wrapper cards — toolbar/list wrapper transparent, rows sit on page
  bg, 8px radius). Hero: 24px/700 title "My complaints" + 14px greeting line, no
  "Updated"/long subtitle on mobile, one full-width 48px flat gold (`--gold-500`)
  `--radius-sm` "File a complaint" button with `+` icon (desktop CTA keeps its
  prior rendering). Attention strip (real `needs_information` only): moved directly
  under the hero on mobile, full width, `min-height:48px` + wrapping fil copy,
  `role="status"`/`aria-live`, whole link. Summary: one 3-column card
  (Total|Open|Resolved) with 1px hairlines, 24px/700 numbers + 13px muted labels,
  centered, 72px, overlaps hero by 24px (16px when attention strip present).
  List header: "Your complaints" 20/600 left, one muted count right (`count`
  plural); bottom "Showing 1–1 of 1" kept in DOM, visually hidden on mobile (SR).
  Toolbar flat: search (flex, 48px, icon) + Filters button (48px, same border/
  radius/surface, filter icon + label + active-count badge); panel expands in
  place (2-col, 1-col <360px) with gold Apply + Clear (GET, field names/`onchange`
  submit behavior unchanged). Status chips: one 16px-aligned horizontal scroll row
  with edge fade, 36px visible/44px tap, active chip keeps navy + gold inset accent.
  Report cards (page-scoped mobile override of the shared row): ticket mono 13 muted
  + status right (row 1), title 16/600 clamp 2, category · department 13 muted with
  ellipsis, progress step text + date + full 5-segment bar; needs-response on its
  own line when present; no stray dot/chevron/dead columns. Pagination targets 44px.
  No routes/controllers/queries/logic changed.
- Tokens/components: no new tokens, no hex/serif — only existing `--navy/--gold/
  --surface/--bg/--line*/--text/--muted/--faint/--status-*`, `--radius-sm/--radius-pill`,
  `--shadow-card`, `--ring/--ring-inset`, `--font-sans/--font-mono`,
  `--tracking-display`, `--navbar-height`. `DesignSystemGuardTest` (full) still
  reports the same 17 pre-existing NEW violations in unrelated legacy files;
  `complaints/index.blade.php` adds none.
- Before/after screenshots (390×844 + 1310×900, en + fil, with/without attention):
  `public/debug-ui/pass3-{en,fil}-{nostrip,attention}-complaints-{390x844,1310x900}.png`
  (baseline refs: `public/debug-ui/after-merge-*-complaints-*.png`,
  `before-merge-complaints-*.png`).
- Definition of Done: 390/1310 en+fil (data + empty/attention states) ✓; Blade
  directive grep clean + `view:cache` OK ✓; no raw keys added (lang en/fil) ✓; no
  hex/serif/vertical-gold in touched files ✓; report rows semantic/single markup ✓;
  GET filters/field names/submit behavior unchanged ✓; focus rings 44px targets,
  reduced-motion honored ✓; `npm run build` clean
  (`resources/css/app.css -> assets/app-MAb0zEVu.css`) ✓. Desktop 1310 kept via
  preserved desktop keys/structure + responsive-only mobile overrides (verify by
  comparing `pass3-*-1310x900` with `after-merge-*-1310x900` on equivalent data).

## 2026-11-08: Submission success page redesigned into a light receipt

- Page / component: `resources/views/complaints/success.blade.php` (ground-up
  rewrite), new `resources/css/pages/complaint-success.css` (imported by
  `resources/css/app.css`), new `lang/en|fil/complaint_success.php`.
- Why (the problem found): the page confirmed a successful submission with its
  own dark navy gradient, a serif ticket display (Cormorant, 48px wrapping to
  two lines at 390px), five hard-coded hex colours, its own Google-fonts
  `@import`, duplicated button styles, an 8-sentence wall of text as the only
  explanation, and two competing CTAs — nothing in it followed the design
  system or any other citizen page.
- What changed: the page is now a light token-only receipt in the wizard /
  My-Complaints family. One centered column (max 640px inside the shared
  `.tr-container`): hero card (green check circle from the resolved status
  tokens, one h1 "Your complaint has been submitted", one-line lede carrying
  the routed office and the `nav.my_complaints` label), a ticket strip (mono
  20px, `tabular` figures, navy-900 on `--surface-soft`), fact rows in the
  wizard-review style (Status → shared `.status-pill.<key>`, Filed → `M j, Y`
  localized date, Routed to → `.dept-badge[data-dept]` + office name,
  Photos → `trans_choice` row only when > 0), and the shared 5-segment
  progress bar (§5.15 base from `partials.public-ui`, widened to the receipt
  column; `role="img"` + `my_complaints.progress_aria` label) reading
  "Step 1 of 5 · Submitted" from the live status, not a frozen one. A second
  card carries "What happens next" — the same three steps the wizard teaches
  (`complaint_create.wizard.next_1..3`) with numbered navy-50 circles — and a
  hairline note explaining which location source placed the report
  (photo GPS / address / none), replacing the wall of text. ONE gold primary
  CTA ("View your report") next to the shared white `.tr-button`
  ("Go to My Complaints"); stacked full-width and 48px tall at ≤768px, inline
  44px on desktop. An auto-redirect strip under the cards starts a 6-second
  clock (`role="timer"`, template `complaint_success.redirect.countdown` +
  `nav.my_complaints` label) and hands the page over to My Complaints; the
  tick pauses while the tab is hidden, and "Stay on this page" cancels by
  setting `[hidden]` (plain JS, data-attribute contract, `@push('scripts')`).
  The in-blade `<style>` is gone (page CSS file instead);
  Cormorant and all raw hexes are gone (guard allowlist entries for this file
  removed); status, date, department and progress reflect live complaint
  state so revisits do not lie.
- Removed: the navy gradient/serif/ticket-display/routed-to/handling-note
  markup and its inline styles, the duplicated `.btn-primary`/`.btn-secondary`
  rules, the duplicate "Routed to / Department name / Photos attached" prose,
  and the stray font `@import` (the layout already loads the fonts).
- Files changed: `resources/views/complaints/success.blade.php`,
  `resources/css/app.css` (one `@import` line),
  `resources/css/pages/complaint-success.css` (new),
  `lang/en/complaint_success.php` + `lang/fil/complaint_success.php` (new),
  `tests/Support/design-system-allowlist.txt` (6 stale rows for this file
  dropped, zero violations remain), `docs/i18n-hardcoded.md` (3 resolved rows
  dropped), `docs/design-changelog.md`, `docs/design-system.md` (§5.26 +
  §10 recipe row + §12).
- Tokens / shared components added or changed: none new. Consumes `--bg`,
  `--surface`, `--surface-soft`, `--line`, `--line-soft`, `--text`, `--muted`,
  `--navy-50/700/900`, `--gold-300/500`, `--status-resolved-bg/-text`,
  `--radius`, `--radius-sm`, `--radius-lg`, `--shadow-card`, `--ring`,
  `--tracking-display`, `--font-mono`, `--navbar-height`, plus the shared
  `.status-pill`, `.dept-badge`, `.progress` and `.tr-button`. The "second"
  page consumption of these shared classes follows §3.5/§5 guidance; no forking.
- Before / after screenshots: `docs/design-log/2026-11-08-success-desktop-{before,after}-1310x900.png`,
  `...-success-mobile-{before,after}-390x844.png`, fil pairs
  `2026-11-08-success-{desktop-after-1310x900,mobile-after-390x844}-fil.png`.
  Working copies (en+fil, 390 and 1310) in `public/debug-success/`.
- Verified by: Playwright assertions at 390×844 and 1310×900, `en` and `fil` —
  exactly one h1 and one footer, `scrollWidth ≤ innerWidth` (0px overflow),
  no raw lang keys, ticket renders mono `COMP-2026-PMMB8`, one gold + one
  secondary CTA, `[data-dept]` badge, five progress segments with exactly one
  gold current, mobile CTA 48px tall and 358px wide (full width), and the
  redirect contract: countdown label stays in range, cancel hides the strip
  and the page is still on the receipt after 8 s, letting it fire lands on
  `/complaints` after ~5.3–6 s. Guard
  (`scripts/design-system-guard.php`) clean for all touched files; allowlist
  shrunk by the now-fixed rows. `npm run build` matches the served bundle
  (`app-CeaCWbAk.css` contains the new `.cs-*` rules); `php artisan view:clear`
  before verification; `ComplaintDepartmentRoutingTest` still passes structurally
  (ticket + department name visible).

## 2026-11-08: Citizen dashboard merged into My Complaints (`/dashboard` → `/complaints`)

- Page / component: `resources/views/complaints/index.blade.php`,
  `resources/views/layouts/navbar.blade.php`,
  `resources/views/layouts/partials/mobile-drawer.blade.php`,
  `resources/views/complaints/success.blade.php`,
  `resources/views/pages/home/guest.blade.php`,
  `lang/en|fil/my_complaints.php`.
- Why (the problem found): the citizen dashboard and My Complaints showed the
  same reports, status, counts, search and filters. Two pages for one job meant
  double maintenance and two places for a resident to check.
- What changed: citizen `GET /dashboard` now 302s to `complaints.index` with the
  query string preserved (301 would be painful to roll back); the `dashboard`
  route name still resolves for emails/tests. Staff and admin keep their
  dashboards exactly as today. Desktop navbar and mobile drawer drop the
  duplicate Dashboard entry; My Complaints is first and carries
  `aria-current="page"` on `complaints.index`, `complaints.show` and
  `complaints.create`; the logo goes to the role home through one helper
  (`User::homeRouteName()`). The dashboard greeting becomes the My Complaints
  hero title (24px ≤640px). A new 48px amber strip appears only when a real
  `needs_information` state exists (open information request, report still
  open): one line + "Read update →" tap target opening the newest matching
  report, `role="status"` + `aria-live="polite"`. Desktop My Complaints is
  otherwise unchanged apart from the optional strip.
- Removed: the citizen Dashboard nav item (desktop + drawer), the old dashboard
  title/`My complaints` h1 in favour of the greeting, and the "Go to Dashboard"
  wording on the filing success page (now "Go to My Complaints").
- Files changed: the views above, `lang/en|fil/my_complaints.php`
  (`attention` plural string), `app/Models/User.php` (home helper),
  `app/Http/Controllers/{Dashboard,Auth,Home,EmailVerification,Complaint}Controller.php`,
  `tests/Feature/CitizenHomeRedirectTest.php`,
  `docs/design-changelog.md`, `docs/design-system.md` (§12).
- Tokens / shared components added or changed: none new. The strip reuses
  `--status-progress-bg`, `--status-progress-text`, `--radius-sm`, the 48px tap
  target and the existing type scale; no hard-coded hex, serif, or one-off
  spacing.
- Before / after screenshots: `docs/design-log/2026-11-08-citizen-merge-*.png`
  (390×844 and 1310×900, with and without the attention strip, en; fil:
  `...-fil-after-*` and `...-fil-attention-*`). Raw files also in
  `public/debug-ui/` (`before-merge-*`, `after-merge-*`).
- Verified by: PHPUnit `CitizenHomeRedirectTest` + `RoleDashboardTest` (302 +
  query preservation + no loop + post-login/intended/verification + role
  guards), Playwright real logins for guest/citizen/staff/admin (final URLs),
  staff/admin navbar + drawer HTML diff (identical), `npm run build`
  (`assets/app-CeaCWbAk.css` matches `manifest.json`), directive grep clean.
  At 390px the fil attention copy wraps to two lines and the strip grows past
  its 48px minimum; tap target stays ≥48px and no text clips.



- Page / component: resident file-complaint wizard (`complaints/create` Blade, `resources/css/pages/complaint-create.css`, `lang/en|fil/complaint_create.php`).
- Why (the problem found): at 390px each inactive `<section class="fc-card">` stayed as an empty white shell (inner `[data-wizard-step]` hidden, card body padded 359×32); site footer visible; duplicate help/hint/map-note text and second Review submit; draft banner never showed (`hidden` + `is-visible` mix, always `display:none` via wizard chrome rule); visible card kept chrome; bottom padding fixed 120px; thumb placement not app-like.
- What changed (ALL scoped to `.fc-page.wizard-on` inside `@media (max-width:767px)` unless noted; desktop pixel diff zero): JS in `updateWizardUI` now sets `card.hidden` by active-step containment (removes on desktop/resize both ways) + `.fc-card[hidden]{display:none!important}` backstop; visible card flat (no border/shadow/bg/padding, head/eyebrow hidden, step title only); `body.page-complaint-create .site-footer{display:none}` at 767px only (Blade `@section('body-class')` + JS `livewire:navigating` fallback); bottom padding `calc(var(--fc-bar-h)+env(safe-area)+16px)` (`--fc-bar-h:72px`), `wizard-at-start` reduces padding on `group`; draft uses `hidden` only (≥2 of category/title/description/address_text non-empty, trim-aware); ONE compact top row via `display:contents` grid (44px back icon left with `aria-label` = `complaint_create.back`, progress right “Step N of 8 · Name” 13px + 4px segments), banner hidden, chip one line with ellipsis + 44px Change, step title 24/600 + 14px muted help, duplicate `.fc-field__hint`/`.fc-map-note` hidden (distinct description/landmark hints stay); sticky bar Back 1/3 + Next 2/3 both 48px, hint above bar; per step: group 2-col 104px min (12px helper clamp 2), category single-col 64px rows + group label/Change, describe 56px title + `X/255` counter, 140px autogrow textarea + “N more”/“Looks good” counter, priority behind 44px toggle, photos camera primary 56px full + gallery secondary (JS text swap on wizard only), 3-col thumbs 44px remove, “N/5” counter, GPS hint behind Photo tips toggle, where 56px barangay + landmark behind “+ Add a landmark” (auto-expand if value), pin locate 48px primary above map `clamp(240px,45dvh,360px)` status below (CSS order, Skip logic kept, conflict rows 48px+), visibility 2 large cards 1-line notes gold border/check when selected footnote behind Why toggle, review 7 rows (Category/Title/Description snippet/Photos count+5 thumbs/Location/Department/Visibility) each 44px Edit → `goToStep`, What-happens-next 3 lines, terms 48px, in-page `.fc-submit` hidden (kept in DOM); motion 180ms fade/slide, `prefers-reduced-motion` off; focus to heading kept, `scroll-margin-bottom` keeps field above bar, `aria-live` kept. No field/validation/endpoint/controller/map/photo changes; `#category` stays source of truth; breakpoint and visibility `@disabled` NOT changed (see report).
- Removed: empty card shells, footer on wizard mobile, duplicate help/hints, second submit, `is-visible` draft mix, card chrome on wizard mobile, 20px step title, 40%/56px bar, map 280px fixed, navy selected visibility border on wizard mobile.
- Files changed: `resources/views/complaints/create.blade.php`, `resources/css/pages/complaint-create.css`, `lang/en|fil/complaint_create.php` (`wizard.*` keys + `descMore/descGood/takePhoto/chooseGallery/justMe/publishPage` in i18n JSON), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Tokens / shared components added or changed: none new (reuses `--bg/surface/line/muted/navy-700/gold-500/radius/shadow/ring`); spacing 4/8/12/16/24/32, type 12/13/14/16/24, ≥44px taps, 16px page padding. No hard-coded hex/serif (guard clean for touched files).
- Before / after screenshots: `public/debug-wizard/390x844-step0*.png` + `360x800-step0*.png` (all 8 steps, invalid, review-5-photos on 360) + `1310x900-desktop-before.png`/`1310x900-desktop-after.png` (md5 identical, diff zero); copies in `docs/design-log/` (desktop pair). Mobile before is the reported 390px empty-shell state; mobile after is the debug set above.
- Verified by: Playwright real-session (pedro@gmail.com) per-step asserts (1 visible step, visible card contains active, 0 empty cards, footer hidden, bar bottom = viewport except `group`, `scrollWidth ≤ innerWidth`), 360/390 + desktop 1310, resize mobile↔desktop preserves title, E2E `road_damage`/Engineering/private/1 photo → `COMP-2026-PMMB8` (`/complaints/72/success`), draft hidden/visible matrix, counters/toggles/pin order, `npm run build` (manifest `assets/app-CGANPGVo.css`), `grep` no word-char before `@directive`, guard no new violations in touched files.
- Open items / data issues: visibility `@disabled(public)` re-render loss + 641–767px wizard/navbar mismatch NOT changed (await decision); keyboard `visualViewport` on-device unverified; `fil` screenshots pending LGU review (keys complete); `/dashboard` citizen redirect bug (`$request` undefined) and `/audit` (no such citizen route; `/audit-trail` is admin) pre-existing.

## 2026-11-08: Citizen mobile dashboard + My Complaints (≤640px) + navbar seam + avg bug

- Page / component: `/dashboard` (`dashboard/index` + `partials/report-card`),
  `/complaints` (`complaints/index` + `partials/report-row` + `partials/public-ui`
  + `pages/transparency/partials/component-styles`), `/transparency` shares
  `.tr-banner`; top bar (`layouts/navbar`, `notification-bell`, `app.css`).
  Staff/admin (`staff/complaints/index`, `admin/complaints/index`) keep 48px.
- Why (the problem found): hero text under the fixed navbar (headline hidden),
  dashboard repeated the full list + ticket search + chips (same job as
  /complaints), cramped meta + ~40px dead card bottom + chevron, stats
  ~260px 2×2 with wrapping "-7 days" tile, three stacked filters, chips
  clipped right ("Under R…") with no scroll hint, hamburger visibly larger
  than bell, and the navbar↔hero light seam (double body+page offset).
- What changed (ALL ≤640px; ≥641px desktop rendering unchanged — new markup
  `display:none` ≥641px, new CSS only inside `@media (max-width:640px)`,
  scoped to `.citizen-dashboard` / `.transparency-page` / `body.is-auth`):
  A1 hero `calc(var(--navbar-height) + 16px)` (56px mobile), 24/600 + 14px,
  CTA 48 full-width flat gold 12px; A2 summary 3× ~72px tiles (num 24/700,
  label 12) linking to `/complaints?status=` (filters exist); A3 View all (N)
  in heading → `/complaints`, recent 3 via `nth-child(n+4)`, search/chips
  hidden on mobile (complaints has `q` + status chips + dept/cat/sort);
  A4 shared 4-row card (ticket 12 muted | status 12 nowrap, title 16/600
  clamp 2, `Category · Department` 13 1-line ellipsis, 5×4px + step 12 left
  + `M j` right, no chevron/min-height, 16/12/12, `:active` + ring, status/
  progress nowrap); A5 empty friendly + File button (existing, 48px on
  mobile); B1 same hero, one-line sub; B2 3 tiles + muted avg line (hidden
  when null); B3 search 48 visible, dept/cat/sort behind Filters + badge
  (div panel, always open ≥641px, field names/method/submit unchanged);
  B4 chips scroll-snap + right fade, hidden scrollbar, active scrolled in,
  36 visible / 44 tap via `::before`; C avg excludes `end < created` rows
  (seed backdates) + `max(0,…)` + null; D bell+hamburger 40×40 r10 border
  same, icons 20, 44 touch via `::before`; seam `body:has(...) {padding-top:0}`
  + banner `calc(var(--navbar-height)+16px) 0 48px`.
- Removed: dashboard mobile search/chips duplication; desktop 4-card mobile
  layout; `48px 0 64px` banner; `2px` stat label gap; `17px/34px/8px` bell.
- Files changed: `app/Http/Controllers/ComplaintController.php` (avg query),
  `resources/css/app.css` (seam cancel + bell selector), `resources/views/
  dashboard/index.blade.php`, `resources/views/complaints/index.blade.php`,
  `resources/views/partials/report-card.blade.php`,
  `resources/views/partials/report-row.blade.php`,
  `resources/views/partials/public-ui.blade.php`,
  `resources/views/pages/transparency/partials/component-styles.blade.php`,
  `resources/views/staff/complaints/index.blade.php`,
  `resources/views/admin/complaints/index.blade.php`,
  `resources/views/layouts/partials/notification-bell.blade.php`,
  `docs/design-system.md` (§4, §5.1, §5.24, §5.25, §12),
  `docs/design-changelog.md`.
- Tokens / shared components added or changed: none new; 8/12/16/24/32/48 on
  scale. No hard-coded hex.
- Before / after screenshots: `public/debug-ui/before-*.png` →
  `public/debug-ui/after-*.png` (desktop 1310×900 diff, mobile 390×844 +
  360×800 top/scrolled/closed/open/empty).
- Verified by: `php scripts/design-system-guard.php` (no new violations),
  `php artisan test --filter=TransparencyPageTest` (7 passing), Playwright
  real-session screenshots + desktop pixel diff, top-bar computed sizes.
- Open items / data issues: see report (Step 0, A3, C, screenshots, diff).

## 2026-11-08: Remove after-title gold bar from all banners

- Page / component: all banner heroes — Transparency register
  (`livewire/transparency-register`), My Complaints (`complaints/index`),
  staff queue (`staff/complaints/index`), admin queue
  (`admin/complaints/index`), public complaint list
  (`pages/transparency/complaints`), ticket detail
  (`pages/transparency/show`), file-complaint (`complaints/create`),
  `/design-system` banner sample (`banner-example`).
- Why (the problem found): the 40×3px `--gold-500` horizontal bar sitting
  directly under every h1 (`.tr-goldbar`, `.pt-goldbar`, `.fc-goldbar`)
  added a decorative accent between title and subtitle on every page.
- What changed: removed all `.tr-goldbar` / `.pt-goldbar` / `.fc-goldbar`
  spans (8 occurrences) and their CSS (`.tr-goldbar` in `component-styles`
  + `complaints-styles`, `.pt-goldbar` in ticket detail, `.fc-goldbar` in
  `complaint-create.css`). Privacy/lede now sit `12px` below the h1
  (`margin: 12px 0 0` on `.tr-privacy` / `.fc-lede`) to keep the left-aligned
  stack on the spacing scale; the ticket title's existing `12px` bottom
  margin already covers its status row. `docs/design-system.md` §4/§5.1/Shell
  A updated; `/design-system` notes updated to match.
- Removed: `<span class="tr-goldbar" aria-hidden="true"></span>` ×6,
  `<span class="pt-goldbar">` ×1, `<span class="fc-goldbar">` ×1; CSS blocks
  `.tr-goldbar`, `.pt-goldbar`, `.fc-goldbar`.
- Files changed: `resources/views/livewire/transparency-register.blade.php`,
  `resources/views/complaints/index.blade.php`,
  `resources/views/staff/complaints/index.blade.php`,
  `resources/views/admin/complaints/index.blade.php`,
  `resources/views/pages/transparency/complaints.blade.php`,
  `resources/views/pages/transparency/partials/banner-example.blade.php`,
  `resources/views/pages/transparency/show.blade.php`,
  `resources/views/complaints/create.blade.php`,
  `resources/views/pages/transparency/partials/component-styles.blade.php`,
  `resources/views/pages/transparency/partials/complaints-styles.blade.php`,
  `resources/css/pages/complaint-create.css`,
  `resources/views/pages/design-system.blade.php`,
  `docs/design-system.md` (§4, §5.1, Shell A, §12), `docs/design-changelog.md`.
- Tokens / shared components added or changed: none; no new spacing (12px is
  on the scale).
- Before / after screenshots: `docs/design-log/` pending attach on the PR
  (verify at 393px and 1310px, en+fil; `npm run build` / restart Vite for
  `complaint-create.css`, clear `php artisan view:clear`).
- Verified by: grep zero `tr-goldbar`/`pt-goldbar`/`fc-goldbar` in
  `resources/`, `php scripts/design-system-guard.php`, view compile.
- Open items / data issues: staff detail vertical `.gold-bar` (3px full-height
  side decoration, not an after-title bar) left untouched — it is a separate
  non-banner decoration that violates the no-vertical-gold-border lock but
  was out of scope for this removal request.

## 2026-11-08: File a complaint — wizard panels hidden at every width

- Page / component: `complaints/create` (`resources/css/pages/complaint-create.css`).
- Why (the problem found): wizard panels hidden via `hidden` were only `display: none` inside
  the `@media (max-width: 767px)` block, so the untouched `.fc-card` skeleton for steps
  currently out of phase could render as empty white pills in the wrong card.
- What changed: `[data-wizard-step][hidden]` is now `display: none !important` at all widths.
- Removed: the mobile-only gap that let off-phase panels paint as leftover pills.
- Files changed: `resources/css/pages/complaint-create.css`.
- Tokens / shared components added or changed: none.
- Before / after screenshots: NOT captured (waived by user for this fix).
- Verified by: `npm run build` (ok), `php artisan test --filter=InlineScriptRuntimeTest` (6 passed).
- Open items / data issues: screenshots still required for the full Task-1 entry before final "done".

## 2026-11-08: File a complaint — wizard + two-level picker foundation

- Page / component: resident `complaints/create` (File a complaint)
  (`resources/views/complaints/create.blade.php`, `resources/views/complaints/partials/tile-icon.blade.php`,
  `resources/css/pages/complaint-create.css` (new, imported via `app.css`),
  `resources/css/tokens.css` (extensions), `config/complaint_category_groups.php` (new),
  `lang/en|fil/complaint_create.php` (new), `app/Http/Controllers/ComplaintController.php` (only
  `$categoryGroups` added to view data), `tests/Feature/ComplaintMapRenderingTest.php` (CSS path check).
- Why (the problem found): page shipped the old identity — Cormorant Garamond serif + DM Sans +
  cream `#f5f0e8`, 74px hero, 1180px container, gold `#c9a84c`; every string hard-coded English;
  category was one `<select>` grouped by department; labels 10px uppercase; inputs 13px/46px;
  wizard Next used `disabled` (hint unreachable) with unreadable 50% opacity look; mobile action bar
  tied with drawer backdrop at z-index 1000 and `.filing-page { overflow:hidden }` broke sticky;
  progress header `aria-hidden=true`; DOM step order mismatched wizard keys; hard-coded hero
  navbar paddings and auth gate offset.
- What changed: full shell rebuild (light back-link row above a navy full-bleed banner with 40×3 gold bar,
  h1 Inter 700, 1040px container, no rounded outer corners, banner bottom 56px with first card overlapping −24px);
  `--navbar-h` mechanism kept (body padding-top) with no extra offsets; `.fc-page { overflow-x: clip }`;
  Inter only; all Task 1 strings moved to `complaint_create` lang keys in en+fil; `config/complaint_category_groups.php`
  declares 6 resident groups (roads, waste, spaces, neighborhood, business, other) mapping to the 10 real
  category values (extras appended to "other", unknown values ignored); controller passes `$categoryGroups`
  (key/label/helper/icon/color/department per child) without changing `$categoryOptions`; server-rendered
  tiles (group radios → leaf panels, no AJAX) set the native `<select>` + dispatch `change`; single-child
  groups skip the `category` step (8→7 segments); key-based wizard (`group, category, describe, photos, where,
  pin, visibility, review`) with `#step-{key}` hash + browser Back, hash guard against incomplete earlier steps,
  server-error→step map, `wizard:v1:{userId}` draft + resume banner preserved, `#category-department-data`
  preserved, Leaflet `whenLeafletReady`/`invalidateComplaintMap` kept (map wrapper `isolation:isolate;z-index:0`),
  action bar fixed bottom z-index 800, Back 40% + Next 56px, Next uses `aria-disabled` (never `disabled`)
  with muted `#e2e8f0/#475569` look when invalid and a pill `role=alert` above the bar explaining the miss;
  focus moves to the first invalid control; segmented progressbar 8→7 with `role=progressbar`; visually-hidden
  `aria-live=polite` announces step changes; chip summarises Group › Leaf + "Goes to {office}" (bicycle: keeps
  `department-hint`/`department-name` ids); `@error` + `old()` + `$locationConflict` unchanged; no-JS fallback keeps
  native required select visible and tiles hidden.
- Removed: Cormorant/DM Sans/cream/1180px/serif hero; static desktop stepper; "Reviewed before action" hero card;
  `.filing-*` / `.wizard-*` rules; 10px uppercase labels and 13px inputs; hard-coded hero navbar paddings.
- Files changed: `config/complaint_category_groups.php`, `lang/en/complaint_create.php`, `lang/fil/complaint_create.php`,
  `resources/css/tokens.css`, `resources/css/app.css` (import), `resources/css/pages/complaint-create.css` (new),
  `resources/views/complaints/create.blade.php`, `resources/views/complaints/partials/tile-icon.blade.php` (new),
  `app/Http/Controllers/ComplaintController.php` (add `$categoryGroups` only), `tests/Feature/ComplaintMapRenderingTest.php`.
- Tokens / shared components added or changed: `--cat-spaces-*`, `--cat-sanitation-*`, `--cat-stray-*`, `--cat-business-*`
  + filing error tokens (`--error-text/bg/line`, `--surface-soft`, `--border-strong`); no new shared components
  (tile + wizard are page-local, `.filing-map` class retained for map container).
- Before / after screenshots: NOT captured in-repo (user waived for this pass); before/after captures remain
  required by `AGENTS.md` §11 before final "done" (393/360/1310 × en/fil + `/design-log/`).
- Verified by: `npm run build` (ok, `fc-*`/`tile-grid`/`isolation: isolate` present in compiled CSS),
  `php artisan view:cache` (ok), `php artisan test --filter=InlineScriptRuntimeTest` (6 passed),
  `php artisan test --filter=ComplaintMapRenderingTest` (13 passed after CSS-path assertion),
  `php artisan test --filter=ComplaintDepartmentRoutingTest|ComplaintBarangayTest|ComplaintLocationChoiceTest|ComplaintVisibilityConsentTest` (pass),
  full suite = 13 failed / 392 passed — all 13 already failed at baseline commit `663d57e` (same list),
  so no NEW failures introduced; `DesignSystemGuardTest` baseline failure lists 11 pre-existing NEW hex
  violations outside this page (not removable under scope).
- Open items / data issues: before/after screenshots + Filipino LGU review of draft translations;
  `docs/design-system.md` §12 changelog row + §10 wizard recipe entry to backfill with screenshots;
  visible-select (no-JS) path verified manually; `DesignSystemGuardTest` still failing at baseline
  (pre-existing, out of scope).

## 2026-11-08: File a complaint — card eyebrows + dead-CSS cleanup (UI follow-up)

- Page / component: `complaints/create.blade.php` + `resources/css/pages/complaint-create.css` +
  `lang/en|fil/complaint_create.php`.
- Why (the problem found): the filing cards had no step marker above the heading (flat), and two
  dead CSS rule pairs (`.tile__badge*`) shipped with no markup using them.
- What changed: each desktop card head now carries a `.fc-section-eyebrow` "STEP 01"…"STEP 05"
  marker (gold-700 text, 10px/700, 0.18em tracking, 24×1 gold hairline before, hidden in wizard
  mode where the wizard title owns the space); i18n keys added (`sections.step`) in `en` + `fil`;
  `(required)` on the barangay label replaced with the same `*` convention as the rest of the form
  and `(optional)` wrapped in a lang key; new/changed strings are in both locales.
- Removed: `.tile__badge`, `.tile__badge svg`, `.tile__badge--hidden` (unused).
- Files changed: `resources/views/complaints/create.blade.php`,
  `resources/css/pages/complaint-create.css`, `lang/en/complaint_create.php`,
  `lang/fil/complaint_create.php`.
- Tokens / shared components added or changed: none new (reuses `--gold-500/700`).
- Before / after screenshots: NOT captured (waived for this follow-up); visual verification via build + InlineScriptRuntimeTest only.
- Verified by: `npm run build` (ok), `php artisan view:cache` (ok),
  `php artisan test --filter=InlineScriptRuntimeTest` (6 passed).
- Open items / data issues: screenshots for Task 1 remain required before final "done".

## 2026-11-08: Resident dashboard content — top/bottom separation for containers + footer

- Page / component: resident dashboard (`dashboard/index`) `.dashboard-content`
  (welcome hero → columns → footer band).
- Why (the problem found): containers sat on off-scale 16/20 separators
  (section heading 16, success/alert 20, track form 16, view-all 16,
  pagination 16, rail 16) so headings, banners and cards read as one block;
  no mobile top/bottom band, so the last card sat close to the footer's gold
  accent.
- What changed: `.dashboard-content { padding: 32px 0 48px }`, `24px 0 32px`
  ≤768px (top separates hero↔columns, bottom separates last card↔footer).
  Containers butt 24px: `.dashboard-columns gap: 24px`, `.dashboard-rail
  gap: 24px`, `.dashboard-section-heading margin-bottom: 24px`, `.dashboard-
  success/.dashboard-alert margin-bottom: 24px`, track form `margin-bottom:
  24px`, view-all `margin-top: 24px`, `.dashboard-pagination margin-top: 24px`.
  Report rows keep `gap: 12px` (inside-list spacing, not container spacing).
- Removed: `margin-bottom/top: 16px` container separators; `margin-bottom:
  20px` (off-scale) banner separators.
- Files changed: `resources/views/dashboard/index.blade.php`,
  `docs/design-system.md` (§4 rhythm + new §5.24 + §12), `docs/design-changelog.md`.
- Tokens / shared components added or changed: none new; 12/24/32/48 all on
  the spacing scale. No hard-coded hex.
- Before / after screenshots: `docs/design-log/` pending attach on the PR
  (verify at 393px and 1310px, en+fil).
- Verified by: `php scripts/design-system-guard.php` (no new violations),
  `php artisan view:clear`; manual render check pending screenshots.
- Open items / data issues: none.

## 2026-11-08: Reduce top padding further + left-align hero text stack (dashboard + banners)

- Page / component: resident dashboard (`dashboard/index`) welcome hero,
  Transparency register + My Complaints (`.tr-banner` in
  `pages/transparency/partials/component-styles`, `.tr-stat` in
  `partials/public-ui`, `complaints/index` CTA). Staff/admin workspace
  overrides (`staff/complaints/index`, `admin/complaints/index`) unchanged.
- Why (the problem found): the light strip persisted — the global
  `body { padding-top: var(--navbar-h) }` in `resources/css/app.css` added a
  second 64px light offset on top of each page's own offset (dashboard had
  body 64 + `.citizen-dashboard` 64; banners had body 64 + banner 48). The
  hero text then sat loose in uneven gaps (gold bar 12/16, privacy 0/12,
  updated 12, CTA 16; dashboard CTA vertically centered against a taller copy
  block; stat label `margin-top: 2px` off-scale).
- What changed: `body:has(.transparency-page), body:has(.citizen-dashboard)
  { padding-top: 0 }` cancels the global offset on hero pages only (other
  pages keep their single offset). `.tr-banner` now
  `calc(var(--navbar-height) + 16px) 0 48px` (visible 16px navy, bottom 48 so
  the −32px stat overlap leaves 16px navy). Text stack left-aligned as one
  block: h1 left, gold bar `margin: 8px 0 12px` left, privacy `margin: 0`
  left, CTA `margin-top: 12px` left, updated `margin-top: 12px` left.
  Dashboard welcome `align-items: flex-start` (CTA top matches heading top),
  title `letter-spacing: -0.02em` + left-aligned copy, welcome
  `padding-top: calc(var(--navbar-height) + 16px)`. Stat value/label left,
  label `margin-top: 4px` (was off-scale 2px).
- Removed: double navbar offset on hero pages; uneven banner gaps (12/16/12/16);
  off-scale 2px stat label gap; vertically-centered dashboard CTA.
- Files changed: `resources/css/app.css`,
  `resources/views/pages/transparency/partials/component-styles.blade.php`,
  `resources/views/partials/public-ui.blade.php`,
  `resources/views/complaints/index.blade.php`,
  `resources/views/dashboard/index.blade.php`,
  `resources/views/staff/complaints/index.blade.php`,
  `resources/views/admin/complaints/index.blade.php`,
  `docs/design-system.md` (§4, §5.1, §12), `docs/design-changelog.md`.
- Tokens / shared components added or changed: none new; 8/12/16/48 all on
  the spacing scale (4·8·12·16·24·32·48). No hard-coded hex.
- Before / after screenshots: `docs/design-log/2026-11-08-banner-top-padding-before-*.png` /
  `-after-*.png` (supersedes the 24px entry below — verify at 393px and 1310px, en+fil).
- Verified by: `php scripts/design-system-guard.php` (no new violations),
  `php artisan test --filter=TransparencyPageTest` (7 passing), manual render check.
- Open items / data issues: `/transparency/complaints` (`.tr-banner--compact`)
  untouched (separate page, no wrapper). Screenshots to be attached before
  merge per §11 Definition of Done.

## 2026-11-08: Reduce top padding — banner butts against navbar (remove light gap strip)

- Page / component: Transparency register (`livewire/transparency-register`),
  My Complaints (`complaints/index`) — shared `.tr-banner` /
  `.transparency-page` in `pages/transparency/partials/component-styles`.
  Staff queue (`staff/complaints/index`) and Admin all-complaints
  (`admin/complaints/index`) keep their workspace spacing via overrides.
- Why (the problem found): a ~50px light `--bg` strip sat between the navy
  fixed navbar and the navy banner — `.transparency-page` had
  `padding-top: var(--navbar-height)` (light page background) above a banner
  that started with 48px of navy padding. Two navies with a light gap read as
  a "mystery gap" (the §10 repeated failure) and pushed the h1 away from the
  nav on every list page in the screenshots.
- What changed: `.transparency-page { padding-top: 0 }`; `.tr-banner` now
  `padding: calc(var(--navbar-height, 64px) + 24px) 0 64px`, so the banner's
  navy runs uninterrupted behind the fixed navbar and the visible breathing
  room below the navbar is 24px (was a 64px light strip + 48px navy). Staff /
  admin `.tr-banner { padding-top: 48px }` overrides added because their
  shells already offset the navbar — no double offset. Compact banner
  (`.tr-banner--compact`, separate page without the page wrapper) untouched.
- Removed: `padding-top: var(--navbar-height)` on `.transparency-page`.
- Files changed: `resources/views/pages/transparency/partials/component-styles.blade.php`,
  `resources/views/staff/complaints/index.blade.php`,
  `resources/views/admin/complaints/index.blade.php`,
  `docs/design-system.md` (§4, §5.1, §12), `docs/design-changelog.md`.
- Tokens / shared components added or changed: none new; 24px and 64px both
  on the spacing scale (4·8·12·16·24·32·48). No hard-coded hex.
- Before / after screenshots: `docs/design-log/2026-11-08-banner-top-padding-before-*.png` /
  `-after-*.png` (pending attach on the PR — verify at 393px and 1310px, en+fil).
- Verified by: `php scripts/design-system-guard.php` (no new violations),
  `php artisan view:clear` + feature tests for the registers, manual render check.
- Open items / data issues: `/transparency/complaints` (`.tr-banner--compact`)
  has no `.transparency-page` offset wrapper and its back link sits under the
  fixed navbar — separate fix, left untouched here. Screenshots to be attached
  before merge per §11 Definition of Done.

## 2026-11-08: Ticket page contrast + spacing + back link + banner

- Page / component: `pages/transparency/show.blade.php` (public single complaint),
  shared footer `layouts/partials/site-footer.blade.php`.
- Why (the problem found): back link invisible (white text on light bg); banner not
  full-bleed with dead space; ticket pill low contrast; uneven mobile stepper;
  wrong update order; ticket details missing dept/category; privacy note was a card;
  footer small print low contrast.
- What changed: back link navy #17305a on light bg; banner full-bleed, no radius,
  correct padding/gaps; ticket pill light background with border; "Latest action"
  chip sentence case 12px; stepper rebuilt (56px rows, centered text, 12px gap,
  connector exact); updates sorted newest-first with 12px navy/hollow markers;
  ticket details restored dept+category with 2-col mobile grid; privacy note plain
  13px muted text; footer small print/contact changed to #9fb3d1.
- Removed: radio-button update marker; privacy card; category/department from banner;
  empty "What this means" box.
- Files changed: `resources/views/pages/transparency/show.blade.php`,
  `resources/views/layouts/partials/site-footer.blade.php`,
  `lang/en/transparency.php`, `lang/fil/transparency.php`,
  `docs/design-changelog.md`.
- Tokens / shared components added or changed: none new.
- Before / after screenshots: `docs/design-log/2026-11-08-ticket-contrast-before-*.png` /
  `-after-*.png`
- Verified by: view compile, manual render, `php artisan test --filter=TransparencyPageTest`
  (7 passing).
- Open items / data issues: copy-ticket button, Share card, "Show earlier updates"
  disclosure, "Last updated" line, desktop layout polish remain unimplemented.

## 2026-11-08: Global navbar offset + ticket page back link/banner + regressions

- Page / component: global layouts (guest, app) + `pages/transparency/show.blade.php` +
  `pages/transparency/complaints.blade.php`.
- Why (the problem found): fixed navbar overlapped page content (no padding-top on body);
  back link sat inside the banner and got cut; Ticket details lost Department/Category;
  updates marker looked like a radio button; privacy note was a card.
- What changed: defined `--navbar-h` and applied `padding-top` on body + `scroll-padding-top`
  on html (single mechanism); moved back link above the banner on the ticket and list pages;
  banner now full-bleed navy; restored Department + Category in Ticket details; updates
  marker is 12px navy/hollow with slate connector; privacy note is plain 13px muted text
  with 16px lock icon.
- Removed: per-page compensating paddings; category/department from banner; "Latest action"
  chip row empty wrapper; radio-button-style update marker; privacy card styling.
- Files changed: `resources/css/app.css`, `resources/views/layouts/guest.blade.php`,
  `resources/views/layouts/app.blade.php`, `resources/views/pages/transparency/show.blade.php`,
  `resources/views/pages/transparency/complaints.blade.php`, `lang/en/transparency.php`,
  `lang/fil/transparency.php`, `docs/design-changelog.md`.
- Tokens / shared components added or changed: `--navbar-h` added.
- Before / after screenshots: `docs/design-log/2026-11-08-navbar-offset-before-*.png` /
  `-after-*.png`
- Verified by: view compile, manual render, `php artisan test --filter=TransparencyPageTest`
  (7 passing).
- Open items / data issues: copy-ticket button, Share card, "Show earlier updates"
  disclosure, "Last updated" line, desktop layout polish remain unimplemented.

## 2026-11-08: Ticket page mobile spacing + navy banner

- Page / component: `pages/transparency/show.blade.php` (public single complaint).
- Why (the problem found): ~50px dead space between ticket row and title (meta row + empty
  chip row); empty "What this means" box for Closed (status key mismatch); stepper labels
  not vertically centered and no gap between steps; timeline dates ran backwards; Closed
  highlighted gold as "Current stage"; header was old white card under navbar.
- What changed: removed category/department from header (now only in Ticket details);
  converted header to navy banner with ticket pill, gold bar, status row; "What this means"
  now conditionally rendered with a non-empty string; stepper rows 56px with 12px gap,
  node/text vertically centered; dates use a non-decreasing guard ("Done" fallback);
  Closed now shows all steps done with no gold; progress card overlaps banner by 24px.
- Removed: "Not started" string usage; doubled "Barangay"; generic alias row; empty
  "What this means" box; category/department from banner.
- Files changed: `resources/views/pages/transparency/show.blade.php`,
  `app/Http/Controllers/TransparencyController.php`, `lang/en/transparency.php`,
  `lang/fil/transparency.php`.
- Tokens / shared components added or changed: none new.
- Before / after screenshots: `docs/design-log/2026-11-08-ticket-mobile-before-*.png` /
  `-after-*.png`
- Verified by: `php artisan test --filter=TransparencyPageTest` (7 passing), view compile,
  manual render.
- Open items / data issues: 22 public complaints have event dates earlier than created_at
  (listed in report); display guard shows "Done" instead. Banner conversion complete.

## 2026-11-08: Public complaint (ticket) page redesign

- Page / component: `pages/transparency/show.blade.php` (public single complaint).
- Why (the problem found): step captions derived from "has an event date" instead of
  step state, so completed/current steps read "Not started"; Submitted vs Reported
  dates came from different fields; upcoming steps were tiny misaligned dots; the
  "SITE ATTENDED" chip had no explanation; "Barangay" was doubled; "Public alias:
  Community member" was noise; the privacy card was oversized; desktop columns were
  unbalanced with an empty hole.
- What changed: captions now follow step state (Done / Current stage / Upcoming);
  Submitted date uses the complaint's created_at (same as Reported); all nodes are
  32px; secondary chip prefixed with "Latest action:"; barangay de-duplicated;
  generic alias row hidden; privacy card compacted; 2-column grid with sticky
  sidebar; navy banner identity; "What this means" box per status.
- Removed: "Not started" string; doubled "Barangay"; generic "Community member"
  alias row; oversized privacy card; progress bar from list pages (already done).
- Files changed: `resources/views/pages/transparency/show.blade.php`,
  `app/Http/Controllers/TransparencyController.php` (show method),
  `lang/en/transparency.php`, `lang/fil/transparency.php`.
- Tokens / shared components added or changed: none new; reuses `--navy-*`,
  `--gold-500`, `--ring`, `--status-*`.
- Before / after screenshots: `docs/design-log/2026-11-08-ticket-page-before-*.png` /
  `-after-*.png`
- Verified by: `php artisan test --filter=TransparencyPageTest` (7 passing),
  view compile, manual render.
- Open items / data issues: seeded events may carry dates that disagree with
  `created_at`; reported without editing data (see read-only list in the next entry).

## Earlier changes (backfilled from git)

- 2026-11-08 (9ff9075) Make the design system the rule for all UI work (Phase 2) — found in git (9ff9075)
- 2026-11-08 (2ce2ae7) Add the portal design system guide and a living /design-system reference — found in git (2ce2ae7)
- 2026-11-08 (564bfe4) Fix transparency redesign from earlier attempt and align detail page to the design system — found in git (564bfe4)
- 2026-11-08 (a639d64) fix: repair transparency page rendering and visual bugs — found in git (a639d64)
- 2026-11-08 (0a8bfee) feat: redesign public transparency page (UI + theme + components) — found in git (0a8bfee)
- 2026-11-08 (4068dab) feat: add active state styling to citizen navbar links — found in git (4068dab)
- 2026-11-08 (62f4933) feat: complete English/Filipino localization slice — found in git (62f4933)
- 2026-11-08 (bfcf2b7) feat: redesign notifications page, fix auth contrast, and add localization — found in git (bfcf2b7)
- 2026-09-23 (9356508) feat: complete complaint workflows, notifications, verifications, and UI updates — found in git (9356508)
- 2026-09-23 (47d4b76) fix: harden deployment restart and sleep handling — found in git (47d4b76)
- 2026-11-08 (0388cd3) fix: use mobile cards for transparency reports — found in git (0388cd3)
- 2026-11-08 (6dc7cc3) style: prevent transparency text letter wrapping — found in git (6dc7cc3)
- 2026-11-08 (c3b5382) style: align transparency card metadata — found in git (c3b5382)

Note: theming (light/dark/high contrast) added then removed, Settings polish, admin "Create staff account" password options,
resident dashboard and My Complaints redesign, guest mobile drawer redesign and its layering fix, and landing page redesign
were requested in earlier sessions but could not be matched to a single dedicated UI commit in `git log --stat` for
`resources/views resources/css lang`; marked not found / unconfirmed.