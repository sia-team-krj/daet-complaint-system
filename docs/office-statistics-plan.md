# Office statistics (Department Administrator) — implementation plan & memory

Status: **in progress (full build requested 2026-11-16)**. This document is the handoff/reference if implementation is interrupted. Update the checklist and “Progress log” as work lands.

## Goal
Give each **Department Administrator** a DA-only, office-scoped **Office statistics** page to track performance and identify common/recurring complaints (categories, areas, trends), with time controls and drill-down to actionable complaint lists — while preserving privacy (no anonymous resident identities, office-internal aggregates only).

## Decisions (confirmed)
- **Scope:** “Everything useful” for performance + common complaints (see Feature set below).
- **Placement:** New **Office statistics** page in the **Office** section (staff sidebar + mobile drawer), alongside Manage office / Office audit.
- **Access:** **Department Administrators only**, scoped to their own active office (controller-enforced; never another office). Global admin uses existing admin analytics.
- **Availability:** **Available now** as its own office-scoped page (not behind `analytics.paused`), because it’s an office-management tool for DAs. (Existing staff/admin analytics routes remain paused as-is.)
- **Time/detail:** Preset ranges + custom date range, trends over time, and drill-down to filtered complaint lists.
- **UI consistency:** Top KPI/stat cards use the **dashboard stat-card pattern** (`dept-stats` / `dept-stat` with `tr-stat__icon` tones), same as standardized on `/staff/office`, `/staff/activity`, `/staff/department-activity`. Detailed analytics sections reuse existing `analytics.partials.*` / `an-*` patterns where they fit, adapted for office/DA context.

## Feature set (full)
1. **Performance overview (KPIs, selected period):** received/filed, open, pending review/verification, resolved, closed, overdue/SLA-breached, on hold, avg time to first action/resolve (and/or median), on-time rate where applicable.
2. **Common complaints:** by category (top counts/share/trend), by barangay/area (counts, aggregated; no resident identity), trend/volume over time.
3. **Workflow & timeliness:** status/review-status mix, aging of open complaints, resolution/closure over time.
4. **Workload & process quality:** per-staff workload in the office (assigned/open/resolved/overdue/avg days) to balance work; needs-information requests, verification/rejection signals, holds placed/resumed — process quality indicators.
5. **Trends:** charts/tables by day/week/month (per `AnalyticsPeriod` granularity).
6. **Drill-down:** metrics/bars/rows link to office-scoped complaint/queue views with filters (status, category, barangay, overdue, etc.) using existing staff complaint filters where possible; privacy-safe.
7. **Time controls:** presets (7d/30d/90d/this month/ytd/all time if added) + custom date range (reusing `AnalyticsPeriod` semantics/URL truth).

## Data & reuse
- **Primary service:** `app/Services/DepartmentAnalyticsService` + `app/Support/AnalyticsPeriod` (and SLA/related services) — already compute metrics, trend, status mix, quality, categories, barangays, aging, workload (team/assigned/unassigned/per-staff, optional staff breakdown), activity; `export` and `department()` exist. Reuse/extend rather than duplicate counting rules.
- **Staff breakdown:** service supports `$withStaff`; for DA (office manager) expose per-staff rows for **their own office only** (names/loads are office-internal management data). Do not expose to other offices or residents.
- **Holds/on-hold KPI:** ensure on-hold counts visible in overview if not already prominent (service/partials may need a small addition).
- **Privacy:** no anonymous complainant identities, no resident PII; categories/barangays are aggregates; drill-down goes to existing staff views which already enforce masking/scoping.

## Architecture / files (planned)
- **Routes:** `staff.office.statistics` (index) + `staff.office.statistics.browser` (fragment/soft-nav JSON, optional) + export/print if desired — under staff middleware group but **not** `analytics.paused`; DA-only enforced in controller via `office()`/`isDepartmentAdmin()` + office scoping.
- **Controller:** `App\Http\Controllers\DepartmentAdminController` (extend) or a dedicated `OfficeStatisticsController` (preferred if cleaner) — payload similar to `StaffAnalyticsController` but DA-gated, `$withStaff=true`, office from DA, and with drill-down URLs.
- **Views:** `resources/views/staff/office/statistics.blade.php` (and partials as needed). Layout: staff shell + `tr-banner`, period controls, **`dept-stats` KPI cards** (dashboard style), then detailed sections (trend, status mix, categories, barangays, aging, quality, workload/staff) reusing/adapting `analytics.partials.*`. Drill-down links wired to `route('staff.complaints.index', [...filters...])`.
- **Nav:** add “Office statistics” link in `resources/views/staff/partials/sidebar.blade.php` (Office group, DA-only) and `resources/views/layouts/partials/mobile-drawer.blade.php`.
- **Lang:** add `office.statistics.*` (and any `analytics.*` reuse/keys needed) in `lang/en` + `lang/fil`.
- **CSS:** reuse existing `dept-stats`/`dept-stat`, `an-*`, `tr-*` classes; avoid new one-off colours/tokens. Any new component must follow design-system process.
- **Tests:** feature tests for DA-only access, office scoping (no cross-office), period filtering, key aggregates/drill-down params, and that residents/staff/admin-without-DA-role cannot access as DA (as applicable).

## UI consistency log (design docs)
- **2026-11-16:** Standardized top stat cards on staff/office surfaces to the **dashboard pattern** `dept-stats` / `dept-stat` + `tr-stat__icon` tones (replaced `staff-metric-grid`/`staff-metric` on `/staff/office`, `/staff/activity`, `/staff/department-activity`). **Office statistics** must use the same `dept-stats` KPI cards for its overview. Record in `docs/design-system.md` (stat card guidance/components) and `docs/design-changelog.md`.

## Progress log
- [x] Requirements clarified; decisions captured (DA-only, own office, Office section, full stats, available now separate from paused analytics).
- [x] Stat-card UI standardized to dashboard `dept-stats` on Manage office + My activity + Department activity.
- [x] Docs scaffolded: this plan/memory, design-doc updates pending/linked.
- [x] Routes/controller payload (DA-gated, office-scoped, period, staff breakdown, drill-down URLs). `staff.office.statistics.{index,browser,export,print}` added outside `analytics.paused`; `DepartmentAdminController@statistics*` reuses `DepartmentAnalyticsService::department(..., withStaff: true)` and `AnalyticsPeriod`.
- [x] Office statistics view (dept-stats KPIs + reused analytics partials/sections + drill-down + time controls). `resources/views/staff/office/statistics.blade.php` + `partials/statistics-content.blade.php` (dashboard-style `dept-stats` KPIs, quick filters, trend/status/categories/barangays/aging/quality/workload/activity; soft-nav browser endpoint).
- [x] Sidebar/mobile nav + i18n (en/fil). Office group links added; `nav.office_statistics` and `office.statistics.*` keys added.
- [x] Service/partial extensions as needed. On-hold surfaced as KPI from status mix; `StaffController@complaintsQueue` accepts optional `category`/`barangay` for drill-down (office-scoped). Core counting remains in `DepartmentAnalyticsService`.
- [ ] Tests (access, scoping, aggregates/period, drill-down). **Not yet added in this pass** — recommended: DA-only access, own-office scoping, period params, and key payload/links.
- [x] Design-system & design-changelog entries for the new page/component usage and stat-card consistency (2026-11-16).
- [~] Build assets if CSS/JS touched; run guard/tests. Blade-only/CSS-in-view changes; no Tailwind/build step required for this pass — verify in target env. Run guard/tests recorded below when executed.

## Implementation notes (2026-11-16)
- **Availability:** Office statistics is intentionally **not** behind `analytics.paused` (DA office-management). Existing `/staff/analytics*` and admin analytics remain paused.
- **Privacy/scoping:** enforced by `DepartmentAdminController::office()` (DA + active own office). No resident identities exposed; aggregates/categories/barangays only; per-staff workload limited to same office. Drill-down uses existing staff complaint views/filters.
- **Drill-down:** status/review/overdue link to existing queue params; category/barangay use added optional queue params. Some metrics (e.g. avg days) are informational without a direct list filter.
- **Continuation:** if extending, add feature tests, consider richer queue filter UI for category/barangay, and align export/print/soft-nav UX with staff analytics once unpaused. See `docs/design-system.md` §5.x dashboard/`dept-stats` usage.

## How to continue if interrupted
1. Read this file and the decisions above; inspect `app/Services/DepartmentAnalyticsService.php`, `app/Support/AnalyticsPeriod.php`, `app/Http/Controllers/StaffAnalyticsController.php`, and `resources/views/analytics/*` for reusable patterns.
2. Pick the next unchecked item in “Progress log”, implement, then tick it and append a short note here (date, files, what’s done/left).
3. Keep UI consistent with dashboard `dept-stats` KPIs and existing `an-*`/`office-*`/`staff-*` patterns; follow `docs/design-system.md` (no new tokens/one-off colours) and update changelog/design docs.
