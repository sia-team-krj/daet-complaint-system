# Daet Listens — System Knowledge Base (Compiled)

**Compiled:** 2026-11-18
**Branch:** `main` @ `ffb446d` — *Mobile navbar — Administrator badge next to the logo, bell by the hamburger*
**Status:** Living implementation reference. The 2026-11-18 taxonomy change is implemented: departments, problem types, categories, auto-routing and intake behaviour are database-managed and editable from `/admin/categories` (super admin) and `/staff/office/categories` (Department Administrator). This document records the current facts and the boundaries that remain.

---

## 1. Why this document exists

The system has absorbed the first wave of **department**, **category**, **category auto-routing** and **admin-defined intake** work. This file consolidates what the system actually is after that change — not what stale planning notes say it is. It must be updated as reality changes; it is a **compile**, not a replacement for `docs/design-system.md` (which stays the canonical design authority) or the code.

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

Four roles:

| Role | Meaning |
|---|---|
| `admin` | Full control. Nullable `department_id` (not scoped). |
| `department_admin` | Department Administrator. Scoped to one office (`department_id`); manages that office's categories on `/staff/office/categories` and its office surface. |
| `staff` | Department staff. Has `can_verify` boolean; verification rights can be toggled by admin (`/admin/staff/{user}/toggle-verification`). |
| `citizen` | Resident filer + tracker. |

Capability split that used to look like "approver vs worker": now expressed as **`can_verify`** on staff (delegate/verify flow) plus `verification_method` on the complaint. Admin keeps verification rights as fallback.

Middleware: `AdminMiddleware`, `StaffMiddleware`, `RoleMiddleware` (`auth`,`email.verified`,`force.password.change`), `EnsureEmailIsVerified`, `ForcePasswordChange`, `SetLocale`.

### 3.3 Domain model — tables (≈20 real tables)

`users`, `departments`, `complaints`, `complaint_logs`, `complaint_category_groups`, `complaint_categories`, `complaint_evidence`?, `complaint_information_requests`, `activity_logs`, `notifications`, `invitation_codes`, `email_verification_codes`, `system_settings`, plus framework tables. (AGENT_GUIDE's "12 tables" is wrong.)

### 3.4 Enums

- `App\Enums\ComplaintStatus` — `Submitted`, `UnderReview`, `InProgress`, `Resolved`, `Rejected`, `Closed`; transitions enforced (`canTransitionTo`); `isOpen()` / `isClosed()`.
- `App\Enums\ComplaintReviewStatus` — `Pending`, `Verified`, `NeedsInformation`, `Duplicate`, `Rejected`, `Escalated`.
- `App\Enums\ComplaintPriority` — `Routine`, `Elevated`, `Urgent`, `Critical` (+rank +badgeClass).
- `App\Enums\WorkExecutionMode` — `labor_required`, `report_only`, `referral`.
- `App\Enums\InformationRequestStatus`, `VerificationMethod`, `ComplaintSlaState` — present, used by workflow/notifications.

### 3.5 Workflow pipeline (as implemented)

1. **Intake** (`ComplaintController@store`): category → `DepartmentRouter` auto-route through `ComplaintTaxonomy`; the category's `complaint_categories.intake_mode` decides what the resident must supply (`physical_required`, `proof_optional`, `sensitive_intake`, `follow_up_optional`; each resolves through `config/complaint_intake_policy.php` to `photos_required`, `photos_allowed`, `location_required`, `barangay_required`, `address_text_required`, `show_photos_step`, `show_location_steps`). The config map remains the fallback for a missing/unknown mode; mode is frozen on the complaint as `intake_mode`.
2. **Moderation / spam** on save: `ComplaintDetectionService` (content fingerprint, spam status/score/reasons, similarity score, duplicate of).
3. **Review** (`StaffController@review` / `ComplaintVerificationController`): review_status transitions; verification; information requests (resident answers via `/complaints/{id}/information`); delegation (`verification/delegate`).
4. **Work** (`StaffController@update` / `@complaintState`, `ComplaintWorkService`): work execution mode decides proof owed — `labor_required` demands site photo for InProgress/Resolved; `report_only` demands written action record; `referral` demands written referral. SLA via `ComplaintSlaService` + console commands (`FlagOverdueWork`, `FlagStaleInformationRequests`, `AutoCloseResolvedComplaints` — auto-close after 7 days post-resolution unless resident confirms).
5. **Closure**: resolved → resident closes (`/complaints/{id}/close`) or auto-closes.
6. **Transparency**: public register (`/transparency`, Livewire `TransparencyRegister`, polled by `TransparencyFeed`), detail (`/transparency/complaints/{id}`), evidence endpoints; privacy: barangay-level only, under-3-report areas neutral, resident PII withheld/redacted (`hidden_from_public`, redacted_title/description, withheld_image_paths).
7. **Audit**: immutable `complaint_logs` (per-ticket status/note; `is_internal`) + cross-cutting `activity_logs` (actor snapshots, department, action, subject, metadata, ip/ua; append-only).

### 3.6 Routing, taxonomy, and policy resolution

- `App\Services\ComplaintTaxonomy` — one cached snapshot of the database taxonomy (groups, categories, offices, styles, labels), used by everything below. Model events on `ComplaintCategoryGroup`, `ComplaintCategory` and `Department` flush it; tests flush it in `TestCase::setUp`.
  - `resolve($category)` — the category's office; archived categories/offices stop receiving new work; falls back to `OM` (`DepartmentRouter::FALLBACK_CODE`) then any active office.
  - `activeCategoryKeys()` — keys a resident may submit now (category active **and** its group active **and** the office active).
  - `residentGroups()` — the two-level picker: active groups → active children, with localized labels/helpers, icons, colours and stable keys.
  - `categoryOptions()` — the same active categories grouped by the receiving office name (legacy form select).
  - `categoryLabels()` / `categoryLabel($key)` — every known category, archived included, so past complaints keep their own name.
  - `categoryStyle($key)` — configured `icon` + `color` for public, staff and admin icons.
  - `intakeModeFor($key)` — the database intake mode, if one exists.
- `App\Services\DepartmentRouter` — thin compatibility facade over `ComplaintTaxonomy`. It has **no** `CATEGORY_MAP` and **no** `ROUTABLE_CODES`; routing is an office id on the category row, so renaming an office code cannot strand a mapping. `getCategoryOptions()`, `residentGroups()`, `activeCategoryKeys()`, `categoryLabels()`, `categoryLabel()`, `styleFor()` and `intakeModeFor()` delegate to the service.
- **Data shape** (migration `2026_11_18_000000_create_complaint_taxonomy.php`):
  - `complaint_category_groups`: `key`, `label_en`, `label_fil`, `helper_en`, `helper_fil`, `icon`, `color`, `sort_order`, `is_active`.
  - `complaint_categories`: stable `key`, `group_id`, `department_id`, `label_en`, `label_fil`, `helper_en`, `helper_fil`, `icon`, `color`, `intake_mode`, `sort_order`, `is_active`.
  - Keys are generated once from the English label (slug with underscores) and never edited; a rename changes labels only. Archiving a group hides every child; archiving a category hides only that row; both keep labels and history.
- `App\Services\ComplaintCategoryManager` — the write path shared by the admin and scoped staff controllers: unique-key generation, validation against `ComplaintTaxonomyOptions`, office assignment, archive/restore and `activity_logs` audit entries.
- `App\Support\ComplaintTaxonomyOptions` — the only allowed icon/color/intake-mode values, used by both validation and the pickers so the form cannot offer what the server rejects.
- `config/complaint_intake_policy.php` — the definitions of each intake mode and the fallback map for a category that predates the taxonomy. The category row is authoritative when it has a mode.
- `config/complaint_work_policy.php` — modes per category (what the **office** must supply) — frozen per ticket via `work_mode`.
- `config/complaint_privacy_policy.php` — the categories that **name somebody** (`plastic_usage` → a business; `staff_treatment` → a person, withdrawn with its office). Their name is stored in `complaints.named_party` and never reaches the public register; the report is filed private. Settling a verified report hands it to the Mayor's office **automatically** (no department request); the Mayor's office — not the handling office — redacts the public copy and approves or declines publication (`ComplaintPrivacyService` + `ComplaintPublicationService`, `PublicationStatus` enum). New categories cannot be made privacy-gated through the category manager; that remains a config decision.
- `config/complaints.php` — `location_conflict_threshold_metres` (100 m EXIF-vs-pin conflict threshold).
- **On hold** (`App\Services\ComplaintHoldService`, `App\Enums\HoldReason`) — a complaint the office cannot finish today (no funds, no materials, another body's work). It is a **marker, not a status**: `complaints.hold_reason` + `hold_note` (public) + `hold_internal_note` + `hold_expected_resume_at` + `hold_total_minutes`. The status does not move, so the report never moves backwards in the resident's progress tracker. `ComplaintSlaService` **pauses the clock**: a held complaint is not overdue (`ComplaintSlaState::OnHold`), and the minutes it waited are added back to the deadline when the hold is lifted.
- **The staff queue** (`StaffController::complaintsQueue`) — two scopes: `open` (Submitted, Under Review, In Progress — the default) and `finished` (Resolved, Rejected, Closed), switched by a chip. The **order is staleness** (`updated_at` ascending), not priority: the complaint nothing has touched for the longest comes first. Priority remains a filter only. `Closed` was missing from the status filter before this.
- **The office's trail** (`/staff/department-activity`, `StaffController::departmentActivity`) — every action anyone in the office has taken, from `complaint_logs`, grouped by ticket (`groupActivityEntries`), paginated. Distinct from `/staff/activity` ("my activity"), which is the member's own trail. The dashboard's "recent department activity" card is a preview of it, and an administrator can open any office's trail at `/admin/departments/{office}/activity`.
- **The public register** (`Complaint::scopePublic`) — `is_public` (the resident's consent) **and the report is verified** (`verified_at` set, or it has already been settled — settling implies verification) — or `publication_status = approved` (the Mayor's office's decision) for reports that name somebody, minus spam, duplicates and moderation-hidden content, **minus rejected complaints** — a rejected report is not a municipal matter. Resolved and closed stay: they are the record of a problem that was fixed. An unverified ordinary consented report is not shown, even though the resident asked to be public. The filing form says so.
- **The daily commands** — `complaints:flag-stale-requests` (a report waiting on the resident), `complaints:auto-close-resolved` (resolved + 7 days, closed for them), `complaints:flag-overdue-work` (the office's deadline slipped), `complaints:remind-resolution` (resolved + 3 days, the resident is asked to confirm — once per report).
- **The admin analytics is live; the staff analytics is parked** (`App\Http\Middleware\AnalyticsPaused`) — the super admin gets `/admin/analytics` (municipal comparison plus each office's report and per-staff breakdown) from the top-level **Analytics** sidebar link and from every department-directory row. The staff-facing `/staff/analytics*` pages, service and styles remain, but their routes answer 403, the staff sidebar does not link to them, and `AnalyticsPaused::STAFF_PAUSED` is what tests read to skip them. Removing the middleware from the staff group and setting that constant to false brings the staff surface back.

**Taxonomy management (delivered 2026-11-18):** problem types and categories are database rows, edited on `/admin/categories` by a super admin and on `/staff/office/categories` by a Department Administrator scoped to their own office. Admin manages groups, categories, offices and intake modes; Department Administrators may add categories under existing problem types for their own office, but cannot create problem types or change the receiving office. Creation is immediate for residents (the wizard reads the same active rows); removal is archive/restore, never delete, and historical complaints keep their stored key, label and department. The public/staff icon shape and tint come from the row, not a key switch. Privacy gating remains config-driven and is not exposed in this manager.

### 3.7 Admin surfaces relevant to the change

- `/admin/departments` (index/create/store/update/archive/restore) — codes are labels now; categories reference the office by id; archive/restore still controls who receives new work.
- `/admin/categories` — super-admin taxonomy manager: create/update/archive/restore problem types and categories, choose the receiving office, icon, colour and intake mode, and see the resident-facing preview.
- `/admin/settings` — system settings and a link to the taxonomy manager (category CRUD removed from this page).
- `/admin/staff`, `/admin/users`, `/admin/invitations`, `/admin/complaints` (queue, browser, export, bulk-reassign, detail, state, update, moderation, verification request-info), `/admin/audit-trail` (+`people`, `+browser`).
- Staff: `/staff/complaints`, `+browser`, `+state`, `+show`, `PUT review`, `PUT update`, moderation, verification request-info/delegate; `/staff/office` and `/staff/office/categories` for a Department Administrator's own office.

### 3.8 Cross-cutting systems

- i18n: `lang/en` + `lang/fil` (parity check done: only known drift — `fil/transparency.php` has extra key `show.not_started`). Language switcher POST `/locale`; staff/admin shells inherit but don't show switcher. Statuses/categories are language-neutral keys translated on display.
- Design guard: hard-coded hex (except tokens.css + documented dept palette), serif declarations, map wrappers without `isolation: isolate`. Allowlist grandfathered; must not grow.
- Soft navigation: fragment endpoints for admin audit / admin complaints / transparency feed (URL-as-truth query strings, graceful degradation).
- Tests: ~50 Feature tests incl. DesignSystemGuardTest, LocalizationTest, Complaint* suites; PR template lists 7 known baseline failures.
- Deploy: Tailscale Funnel public URL; `public/` PHP server + raised upload limits; `restart-daet-listen.sh`, `keep-device-awake.sh`.

---

## 4. The taxonomy change — decisions that are now in force

1. **Departments:** archive = stop receiving new work; the taxonomy skips archived offices; no code rename protection (routing is by office id); fallback routing (OM → any active office, and archived office as last-resort work target for existing tickets).
2. **Problem types and categories:** two database levels — `complaint_category_groups` (problem types) and `complaint_categories` (categories) — drive the resident tiles/select, the receiving office, the icon/colour and the intake mode. Stable keys are generated from the English label and never edited.
3. **Category auto-routing:** `complaint_categories.department_id` is the routing authority; `DepartmentRouter` is a facade over `ComplaintTaxonomy`. Legacy keys keep their labels and history.
4. **Admin decides required information:** each category stores an `intake_mode`; `Complaint::intakePolicyFor()` reads it and falls back to config only when the row has no usable mode. Work mode and privacy gating remain config-driven.
5. **Management surfaces:** `/admin/categories` is the super-admin manager; `/staff/office/categories` is the scoped Department Administrator surface. Archive/restore, not delete. No category CRUD on `/admin/settings` anymore.
6. **Existing complaints:** report category keys and department ids are preserved; archived labels and offices stay readable on history and public/staff detail pages.

**Deliberately still outside this change:**
- Editing work mode (`labor_required`, `report_only`, `referral`) per category in the manager.
- Making a new category privacy-gated (`named_party`, position prompt, publication flow) from the manager.
- Re-keying existing categories or migrating historical complaints to new keys.
- Per-(category × department) policy matrices or a separate policy table.

## 5. Open questions for the owner (remaining)

1. **Work-policy editing:** should the manager eventually expose `work_mode` per category, or stay config-driven until the workflow rules settle?
2. **Privacy gating:** should a super admin be able to mark a category as naming a party, with the position prompt, from the manager? Today it is config-only by design.
3. **Department Administrator limits:** should they be able to create a *new* problem type scoped to their office, or continue adding only categories under existing types?
4. **Deprecation visibility:** should an archived category remain visible to the office in history filters (it does) and to admins in the manager (it does), and should residents ever see a “withdrawn” note?
5. **Translation workflow:** category labels require both `label_en` and `label_fil` in the manager; who supplies Filipino copy when a category is created quickly?

---

## 6. Guardrails to preserve during those changes

- Design-system guard must not gain new violations; grandfathered allowlist must not grow.
- Design changelog entry required in the same commit for any view/CSS/JS/lang change.
- z-index contract, map-wrapper `isolation`, privacy rules on public payload, i18n key parity (en/fil), soft-navigation invariants, one-markup-per-list.
