# Daet Listens — System Knowledge Base (Compiled)

**Compiled:** 2026-11-12
**Branch:** `main` @ `ffb446d` — *Mobile navbar — Administrator badge next to the logo, bell by the hamburger*
**Status:** Living planning document. Compiled from the full codebase read + all `docs/` + root planning notes. Target of the upcoming planning wave: **departments, categories / sub-categories, category auto-routing, and admin-driven required information for the complaint/create form.**

---

## 1. Why this document exists

Before the next change cycle (expected: heavy work on **departments**, **categories**, **category auto-routing**, and an **admin-side that can define categories / sub-categories and decide what information a complaint must carry at intake**), the team needs one place that consolidates what the system actually is — not what stale planning notes say it is.

This file is that consolidation. It must be updated as reality changes; it is a **compile**, not a replacement for `docs/design-system.md` (which stays the canonical design authority) or the code.

---

## 2. Source-of-truth map (read this before acting)

| Document / artifact | Reality today | Verdict |
|---|---|---|
| `docs/design-system.md` | **Canonical** design authority (2,181 lines). Inter-only, navy/gold, no cream; tokens in `resources/css/tokens.css`; guard at `scripts/design-system-guard.php`; living reference at `/design-system`. | ✅ Current |
| `docs/design-changelog.md` | Newest-first changelog; entries must accompany UI changes. | ✅ Current |
| `docs/soft-navigation.md` + `.opencode/skills/soft-navigation/` | Canonical soft-nav pattern (fragment endpoints). | ✅ Current |
| `docs/localization.md` | Nov 2026 snapshot — some claims are now stale. | ⚠️ Partially current |
| `docs/transparency-redesign.md` | Design log for the transparency rewrite. | ✅ Current |
| `docs/audit-trail-redesign-plan.md`, `docs/audit-directory-scaling-plan.md` | Implemented drill-down audit browser design. | ✅ Current |
| `docs/ui-verification.md` | Verification process (screenshots waived by owner 2026-11-09). | ✅ Current |
| `docs/i18n-hardcoded.md` | Generated scan output; largely obsolete since strings were moved to keys. | ⚠️ Stale |
| `AGENTS.md` | Agent rules incl. mandatory design-system-before-UI-work, design-changelog requirement. | ✅ Current (rules) |
| `AGENT_GUIDE.md` | **Stale** — claims 4 roles, 12 tables, 8 migrations, unimplemented features; contradicts current code. | ❌ Superseded |
| `PRODUCT.md` | Describes retired 4-role model (admin/approver/worker/resident). | ❌ Superseded by code reality |
| `DESIGN.md` | Retired design system (Cormorant Garamond/DM Sans/cream). | ❌ Superseded by `docs/design-system.md` |
| `PLAN.md` / `UPNEXT.md` / `PROGRESS.md` / `PROJECT_CHANGES.md` | Historical snapshots (Sept–Apr 2026). | ❌ Historical |

**Rule of thumb:** code + `docs/design-system.md` win; anything else is history or must be updated.

---

## 3. Compiled system facts

### 3.1 Stack / environment

- Laravel 12.69.1 · PHP 8.5 · PostgreSQL 17 + PostGIS (`postgis/postgis:17-3.6-alpine`) · Redis · Mailpit · MinIO · Vite + Tailwind v4 + plain CSS (`resources/css/app.css`) + Livewire 4.
- `network_mode: host` in `compose.yaml` (no bridge); no `laravel.test` service (host runs `php artisan serve`).
- Frontend: self-hosted Inter font (`public/fonts/inter` — no third-party font/CDN requests, enforced by design guard + privacy note); Leaflet bundled locally via `resources/js/leaflet*`, exposed as `window.whenLeafletReady`.

### 3.2 Roles / capability model (current)

Three roles, not four:

| Role | Meaning |
|---|---|
| `admin` | Full control. Nullable `department_id` (not scoped). |
| `staff` | Department staff. Has `can_verify` boolean; verification rights can be toggled by admin (`/admin/staff/{user}/toggle-verification`). |
| `citizen` | Resident filer + tracker. |

Capability split that used to look like "approver vs worker": now expressed as **`can_verify`** on staff (delegate/verify flow) plus `verification_method` on the complaint. Admin keeps verification rights as fallback.

Middleware: `AdminMiddleware`, `StaffMiddleware`, `RoleMiddleware` (`auth`,`email.verified`,`force.password.change`), `EnsureEmailIsVerified`, `ForcePasswordChange`, `SetLocale`.

### 3.3 Domain model — tables (≈20 real tables)

`users`, `departments`, `complaints`, `complaint_logs`, `complaint_categories`, `complaint_evidence`?, `complaint_information_requests`, `activity_logs`, `notifications`, `invitation_codes`, `email_verification_codes`, `system_settings`, plus framework tables. (AGENT_GUIDE's "12 tables" is wrong.)

### 3.4 Enums

- `App\Enums\ComplaintStatus` — `Submitted`, `UnderReview`, `InProgress`, `Resolved`, `Rejected`, `Closed`; transitions enforced (`canTransitionTo`); `isOpen()` / `isClosed()`.
- `App\Enums\ComplaintReviewStatus` — `Pending`, `Verified`, `NeedsInformation`, `Duplicate`, `Rejected`, `Escalated`.
- `App\Enums\ComplaintPriority` — `Routine`, `Elevated`, `Urgent`, `Critical` (+rank +badgeClass).
- `App\Enums\WorkExecutionMode` — `labor_required`, `report_only`, `referral`.
- `App\Enums\InformationRequestStatus`, `VerificationMethod`, `ComplaintSlaState` — present, used by workflow/notifications.

### 3.5 Workflow pipeline (as implemented)

1. **Intake** (`ComplaintController@store`): category → `DepartmentRouter` auto-route; intake policy per category (`config/complaint_intake_policy.php`: `physical_required`, `proof_optional`, `sensitive_intake`, `follow_up_optional` — each with `photos_required`, `location_required`, `barangay_required`, `address_text_required`, `show_photos_step`, `show_location_steps`); mode frozen on complaint as `intake_mode`.
2. **Moderation / spam** on save: `ComplaintDetectionService` (content fingerprint, spam status/score/reasons, similarity score, duplicate of).
3. **Review** (`StaffController@review` / `ComplaintVerificationController`): review_status transitions; verification; information requests (resident answers via `/complaints/{id}/information`); delegation (`verification/delegate`).
4. **Work** (`StaffController@update` / `@complaintState`, `ComplaintWorkService`): work execution mode decides proof owed — `labor_required` demands site photo for InProgress/Resolved; `report_only` demands written action record; `referral` demands written referral. SLA via `ComplaintSlaService` + console commands (`FlagOverdueWork`, `FlagStaleInformationRequests`, `AutoCloseResolvedComplaints` — auto-close after 7 days post-resolution unless resident confirms).
5. **Closure**: resolved → resident closes (`/complaints/{id}/close`) or auto-closes.
6. **Transparency**: public register (`/transparency`, Livewire `TransparencyRegister`, polled by `TransparencyFeed`), detail (`/transparency/complaints/{id}`), evidence endpoints; privacy: barangay-level only, under-3-report areas neutral, resident PII withheld/redacted (`hidden_from_public`, redacted_title/description, withheld_image_paths).
7. **Audit**: immutable `complaint_logs` (per-ticket status/note; `is_internal`) + cross-cutting `activity_logs` (actor snapshots, department, action, subject, metadata, ip/ua; append-only).

### 3.6 Routing & policy resolution (the heart of the upcoming change)

- `App\Services\DepartmentRouter`:
  - `CATEGORY_MAP` — 7 offered categories → dept codes (`road_damage/flooding/streetlight → ENG`, `garbage/sanitation/plastic_usage → MENRO`, `others → OM`). `business_permit`, `staff_treatment`, `noise_complaint`, `park_maintenance` and `stray_animals` are **withdrawn**: not offered to residents, but still routable to OM and still labelled, because complaints already filed under them still exist.
  - `ROUTABLE_CODES = ['ENG', 'MENRO', 'OM']` — AdminController refuses renaming these (rename would silently strand mappings). An office an administrator creates is not a routing key: it receives nothing on its own.
  - `resolve()` skips archived departments, falls back OM (`FALLBACK_CODE`) → any active office.
  - `getCategoryOptions()` — groups for the create form, keyed by the **office name** the resident is shown (`Engineering Office`, `Municipal Environment and Natural Resource Office`, `Mayor's Office`); `categoryLabels()` adds the withdrawn categories back for display.
- `config/complaint_intake_policy.php` — modes per category (what the **resident** must supply).
- `config/complaint_work_policy.php` — modes per category (what the **office** must supply) — frozen per ticket via `work_mode`.
- `config/complaint_privacy_policy.php` — the categories that **name somebody** (`plastic_usage` → a business; `staff_treatment` → a person, withdrawn with its office). Their name is stored in `complaints.named_party` and never reaches the public register; the report is filed private. Settling a verified report hands it to the Mayor's office **automatically** (no department request); the Mayor's office — not the handling office — redacts the public copy and approves or declines publication (`ComplaintPrivacyService` + `ComplaintPublicationService`, `PublicationStatus` enum).
- `config/complaints.php` — `location_conflict_threshold_metres` (100 m EXIF-vs-pin conflict threshold).
- `config/complaint_category_groups.php` — two-level groups (key/icon/color/categories) for the create-form tiles.
- **On hold** (`App\Services\ComplaintHoldService`, `App\Enums\HoldReason`) — a complaint the office cannot finish today (no funds, no materials, another body's work). It is a **marker, not a status**: `complaints.hold_reason` + `hold_note` (public) + `hold_internal_note` + `hold_expected_resume_at` + `hold_total_minutes`. The status does not move, so the report never moves backwards in the resident's progress tracker. `ComplaintSlaService` **pauses the clock**: a held complaint is not overdue (`ComplaintSlaState::OnHold`), and the minutes it waited are added back to the deadline when the hold is lifted.
- **The staff queue** (`StaffController::complaintsQueue`) — two scopes: `open` (Submitted, Under Review, In Progress — the default) and `finished` (Resolved, Rejected, Closed), switched by a chip. The **order is staleness** (`updated_at` ascending), not priority: the complaint nothing has touched for the longest comes first. Priority remains a filter only. `Closed` was missing from the status filter before this.
- **The office's trail** (`/staff/department-activity`, `StaffController::departmentActivity`) — every action anyone in the office has taken, from `complaint_logs`, grouped by ticket (`groupActivityEntries`), paginated. Distinct from `/staff/activity` ("my activity"), which is the member's own trail. The dashboard's "recent department activity" card is a preview of it, and an administrator can open any office's trail at `/admin/departments/{office}/activity`.
- **The public register** (`Complaint::scopePublic`) — `is_public` (the resident's consent) **and the report is verified** (`verified_at` set, or it has already been settled — settling implies verification) — or `publication_status = approved` (the Mayor's office's decision) for reports that name somebody, minus spam, duplicates and moderation-hidden content, **minus rejected complaints** — a rejected report is not a municipal matter. Resolved and closed stay: they are the record of a problem that was fixed. An unverified ordinary consented report is not shown, even though the resident asked to be public. The filing form says so.
- **The daily commands** — `complaints:flag-stale-requests` (a report waiting on the resident), `complaints:auto-close-resolved` (resolved + 7 days, closed for them), `complaints:flag-overdue-work` (the office's deadline slipped), `complaints:remind-resolution` (resolved + 3 days, the resident is asked to confirm — once per report).
- **The department analytics is parked** (`App\Http\Middleware\AnalyticsPaused`) — the pages, the service and the styles are all still here, but the nav links are gone and the routes answer 403. Taking the middleware off the two route groups brings the feature back; `AnalyticsPaused::PAUSED` is what the tests read to skip the pages while it is out of sight.

**Critical gap for the planned feature:** intake mode, work mode, category→department routing, category groups and VALID_CATEGORIES are currently **hardcoded / config-keyed**. Admin category CRUD (`AdminSettingsController@storeCategory/updateCategory/toggleCategory` via `complaint_categories` table: `key`, `label_en`, `label_fil`, `is_active`, `sort_order`) manages **labels only** — it does not bind an intake mode, work mode, or department routing to a category. Any "admin decides required info" feature must build on this seam.

### 3.7 Admin surfaces relevant to the change

- `/admin/departments` (index/create/store/update/archive/restore) — codes + archive semantics.
- `/admin/settings` — system settings + complaint categories CRUD + departments list.
- `/admin/staff`, `/admin/users`, `/admin/invitations`, `/admin/complaints` (queue, browser, export, bulk-reassign, detail, state, update, moderation, verification request-info), `/admin/audit-trail` (+`people`, `+browser`).
- Staff: `/staff/complaints`, `+browser`, `+state`, `+show`, `PUT review`, `PUT update`, moderation, verification request-info/delegate.

### 3.8 Cross-cutting systems

- i18n: `lang/en` + `lang/fil` (parity check done: only known drift — `fil/transparency.php` has extra key `show.not_started`). Language switcher POST `/locale`; staff/admin shells inherit but don't show switcher. Statuses/categories are language-neutral keys translated on display.
- Design guard: hard-coded hex (except tokens.css + documented dept palette), serif declarations, map wrappers without `isolation: isolate`. Allowlist grandfathered; must not grow.
- Soft navigation: fragment endpoints for admin audit / admin complaints / transparency feed (URL-as-truth query strings, graceful degradation).
- Tests: ~50 Feature tests incl. DesignSystemGuardTest, LocalizationTest, Complaint* suites; PR template lists 7 known baseline failures.
- Deploy: Tailscale Funnel public URL; `public/` PHP server + raised upload limits; `restart-daet-listen.sh`, `keep-device-awake.sh`.

---

## 4. The area under planning — what the upcoming changes must preserve / decide

1. **Departments:** archive = stop receiving new work; router skips archived; rename protection on `ROUTABLE_CODES`; fallback routing (OM → any active).
2. **Categories / sub-categories:** flat keys today (`road_damage` … `others`); groups layer added in `complaint_category_groups.php` for the create form; `complaint_categories` admin table for labels/active/sort.
3. **Category auto-routing:** `DepartmentRouter::CATEGORY_MAP` hardcoded; needs to become admin-definable without breaking legacy tickets.
4. **Admin decides required information:** intake mode (`photos_required`, `location_required`, `barangay_required`, `address_text_required`, …) and work mode (`labor_required`, `report_only`, `referral`) currently keyed by category in config; needs an admin-editable binding layer.

**Required before any implementation doc is written:**
- Whether sub-categories are a new first-class concept or groupings layered over flat keys.
- Whether intake/work policy moves to `complaint_categories` rows / a policy table (schema change) or stays config-file driven with admin-editable values.
- How legacy tickets freeze their mode (already frozen via `intake_mode`/`work_mode` columns — reuse).
- How the create-form wizard (8 steps, groups, tiles, `data-loading-submit`) must consume admin-defined structure.

---

## 5. Series of open questions for the owner (please answer)

1. **Sub-category model:** flat category keys + admin grouping (current) vs. true sub-categories under a parent (new)?
2. **Admin-editable policy storage:** move intake/work policy into DB (`complaint_categories` or a new policy table) or keep Laravel config with admin write-back?
3. **Routing authority:** keep `DepartmentRouter::CATEGORY_MAP` as the default and let admin override per category/sub-category; how are overrides recorded for legacy tickets?
4. **Required-info semantics:** should "required" be per (category × department) or per (category × staff assignment / work mode)?
5. **Privacy/sensitive mode:** how do admin-created categories map to `sensitive_intake` behavior (no photos/location steps) — manual or rule-based?
6. **i18n scope:** new admin-created category labels need `label_en` + `label_fil` — who translates, where keys come from (`complaint_create.*`, `statuses.*`, `transparency.*`)?
7. **Migration/backfill:** how to handle the 10 existing categories + seeded demo complaints during re-keying.

---

## 6. Guardrails to preserve during those changes

- Design-system guard must not gain new violations; grandfathered allowlist must not grow.
- Design changelog entry required in the same commit for any view/CSS/JS/lang change.
- z-index contract, map-wrapper `isolation`, privacy rules on public payload, i18n key parity (en/fil), soft-navigation invariants, one-markup-per-list.
