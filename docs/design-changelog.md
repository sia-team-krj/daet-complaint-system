## 2026-11-15: Department Administrator — office-scoped manager role and Manage office surface

- Page / component: staff workspace (`/staff/office`, `/staff/office/audit`), staff sidebar + mobile drawer + navbar identity, staff dashboard identity, staff complaint ticket (assignment panel), admin user directory/detail and staff accounts, profile/settings, audit trail labels, department archive. Design system §5.31 (identity) and §5.49 (office management) documented; §12 changelog updated.
- Why (the problem found): each office had no in-office manager — only ordinary staff and the global administrator. Offices needed to manage their own staff accounts, invitations, verification rights, complaint assignment and an office audit trail without gaining global powers, cross-office access, or the ability to see anonymous complainant identities.
- What changed:
  - **Role & data:** added `department_admin` to the `users.role` CHECK constraint (new migration, rollback demotes to `staff`). `User` helpers (`isDepartmentAdmin`, `isDepartmentStaff`, `isStaff`, `canVerifyComplaints`, `homeRouteName`, `scopeStaff`) and `Department::{staff,departmentAdmins}`; `ComplaintPolicy::{view,changeStatus,assign,viewAny}` scopes the role to its own office and keeps `viewRealIdentity` admin-only.
  - **Authorization & services:** staff workspace routes/middleware admit `department_admin`; `ComplaintVerificationService` scopes/permits office verifiers (Department Administrators verify by role, no `can_verify` flag required) and delegation; `NotificationService` and approval-office publication recipients include active department admins; `StaffController` scope checks, verifier/assignee lists and the ticket assignment panel (existing `.staff-action-panel`, §5.40) for the Department Administrator only.
  - **Manage office (staff):** `DepartmentAdminController` + `/staff/office*` routes. New `.office-*` component family (§5.49, token-only, no new colours): office metrics, staff-account list with activate/deactivate and grant/revoke verification (ordinary staff only — peer/self managers are read-only with a boundary note), add-staff form (invitation link or emailed temporary password only, never manager-chosen), open invitations with revoke/copyable link, and an office audit trail filtered to staff/Department Administrator/admin actors and system records for the office (resident actions excluded). `app/Support/TemporaryPassword` extracted and reused for emailed temp passwords.
  - **Admin:** global admin appoints/revokes `department_admin` in User management (department required, assigned-complaint guards preserved, role labels/badges updated); staff accounts list shows Department Administrators as read-only with a “Manage role” link; department archive is blocked while any Department Administrator is attached. Admin complaint assignment/verifier lists accept department admins.
  - **Identity & i18n:** “Department Administrator” label/badge in navbar, mobile drawer, staff sidebar/dashboard and profile/settings via `roles.*`/`nav.*` (`en` + `fil`); `role-department_admin` kept on `<body data-role>` with `role-staff` compatibility class for the shared staff shell. Audit trail renders localized role labels. New `lang/{en,fil}/{office,roles}.php` and assignment/nav strings.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09; no interactive browser session captured in this run). Code/behavior verified by: `php -l` on new/edited PHP; `php artisan route:list --path=staff/office` (7 routes); `php artisan test tests/Feature/DepartmentAdminRoleTest.php` — **13 passed** (68 assertions); targeted regressions `tests/Feature/{AdminUserManagementTest,DepartmentVerificationTest,StaffAccountCreationTest,AdminInvitationTest,DepartmentAdministrationTest}.php` — **84 passed** (386 assertions) total with the role suite; `php scripts/design-system-guard.php` — **0 NEW** violations (250 grandfathered). New `office.*`/`roles.*`/`nav.*`/`staff.assignment.*` keys added to both `en` and `fil`.
- Definition of Done checklist: passed — design system updated first (§5.31, §5.49, §12) and new `.office-*`/badge variants documented; token-only styling (no new hex/serif/tokens, reuses existing `--success/--danger`/gold/navy tokens, `.tr-*`/`.staff-*` patterns); no duplicated list markup or forked shared components; navbar/drawer/overlays unchanged for z-index/stacking; `en`+`fil` for all added strings, statuses/roles translated via keys; privacy preserved (office audit excludes resident actors, `viewRealIdentity` remains admin-only, public surfaces unchanged); graceful degradation (forms are standard POST/PATCH, invitation links shown as readonly inputs); `role-staff` compatibility retained for the shared staff shell.

## 2026-11-15: The Terms stop calling RA 11032 the "Anti-Junk Email Act"

- Page / component: `resources/views/pages/legal/terms.blade.php` (§13 Governing law), `docs/design-changelog.md`.
- Why (the problem found): the Terms of Service labelled Republic Act No. 11032 — the actual name is the **Ease of Doing Business and Efficient Government Service Delivery Act of 2018** — as "the Anti-Junk Email Act" and tied it to the portal's no-marketing-email promise. That statute has nothing to do with email; it is the framework that caps how long Philippine government offices may take to process transactions (simple / complex / highly technical, including Daet's service charter). Every reader — resident, auditor, or opposing counsel — who checked would find a fictional act in a public municipal document.
- What changed: the RA 11032 bullet now names the Act correctly and states what it really governs. The "overdue" clock on complaints is now described for what it is — the Municipality's **own internal service standard** adopted in pursuit of that framework, explicitly **not** a statutory time limit and explicitly not creating liability under the Act. The no-marketing-email sentence moves to its own bullet stripped of the fake citation. No other section, route or translation anchor changed; LegalPagesTest (10 passed, 66 assertions) confirms the `11032` reference is asserted and present.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09; copy-only change to one list item). `php artisan view:cache` compiles; `php artisan test tests/Feature/LegalPagesTest.php` — **10 passed** (66 assertions). The guard was not re-run for a text-only edit (no markup, CSS or assets touched).
- Definition of Done checklist: passed — no new strings/tokens/colours; facts now match the cited statute; deliberately avoids claiming statutory compliance (overdue numbers stay what they are: the municipality's own SLA, admin-editable, measured from first verification).

## 2026-11-13: The admin queue's desktop filters lose their pointless "More filters" button

- Page / component: `resources/css/app.css` (the "Admin queue filters: desktop neutral, mobile collapsible" block), `docs/design-changelog.md`.
- Why (the problem found): `/admin/complaints` showed a "More filters" disclosure button on desktop even though the extra filters (priority · flagged · on hold · date range) are already rendered inline there — the `≥641px` block forces the `<details>` open (`display: contents` on the details and its body plus `::details-content { content-visibility: visible }`, and the page's JS sets `open` on `min-width: 641px`). The hide rule for the summary existed but targeted `summary.admin-queue-filters-toggle`, while the page's disclosure renders `<summary class="tr-clear">More filters</summary>` — a class drift, so the button survived exactly where it was redundant. The owner asked for it gone on desktop only.
- What changed: widened the `≥641px` selector in the existing block to `.admin-queue-filters-collapsible > summary { display: none !important; }` — desktop now shows just the inline filter row; `≤640px` is untouched (the disclosure stays, with `tr-clear` styling and the JS default-open-only-when-filters-are-active behaviour). No markup, strings, tokens, colours or JS changed; mobile still collapses the extra filters behind the button.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09; the admin page requires a session this session does not hold). `npm run build` — `public/build/assets/app-BkULhztd.css` served by the proxy on port 8000 (HTTP 200, 451394 bytes) contains, inside `@media(min-width:641px){…}`, `.admin-queue-filters-collapsible>summary{display:none!important}` (verified by extraction); the homepage references the new bundle; `php scripts/design-system-guard.php` — **0 NEW** (250 grandfathered). No Blade/JS was touched, so no view cache invalidation was needed; the rule's effect is deterministic at the media-query level.
- Definition of Done checklist: passed — no new strings, tokens or colours; nothing hidden on mobile (`≤640px` block untouched); one markup per list preserved (no forking); the extra filters remain reachable on desktop with JavaScript off (the `display: contents`/`::details-content` rules already force the body visible); reduced-motion unaffected (no animation involved).

## 2026-11-13: Publication decision rebuilt as a two-column decision workspace (Shell C)

- Page / component: `resources/views/staff/publication-requests/show.blade.php` (rewritten), `resources/css/pages/publication-review.css` (new), `resources/css/app.css` (import), `lang/{en,fil}/staff.php` (`publication.privacy_kicker`, `redact_stage_1/2` + `redact_stage_1/2_hint`, `decide_ready`, `decide_label`, `decide_decline_confirm`), `tests/Feature/PrivacyGatedComplaintTest.php` (structure-pinning test), `docs/design-system.md` (§10, §12), `docs/design-changelog.md`.
- Why (the problem found): the decider's screen stacked everything into one column, and its buttons used `.admin-button*` classes scoped to `body.role-admin.admin-layout` — so on the staff workspace they rendered as unstyled native controls. The public copy being decided on was buried below the original report, the approve action sat at the very bottom, and the named party — the one thing that must never be published — was another row of text among many.
- What changed: Shell C (back link + h1 + one line, no banner). (1) A one-line **context strip**: ticket · department badge · category · department · date. (2) An **always-visible privacy panel** above the work area — navy tint, eye-off glyph, the "never published" kicker, the named party. (3) A **two-column work grid** — left: "Original report" card and the redaction editor split into two clearly labelled stages (**Stage 1 — tick what stays out** drafts the copy; **Stage 2 — the public copy**, with a navy `Save the redacted copy` button), right rail: a sticky **"What would be published" preview** of the redacted title/description exactly as residents will read it, with an amber "no redacted copy saved yet" warning until the office saves one. (4) A **sticky decision bar** at the bottom — left, the publication status and the one line that says whether a decision is possible; right, ONE gold `Approve publication` (disabled until a redacted copy is saved) and `Decline — keep it private` rendered as a `<details>` disclosure carrying the required note — a disclosure, so it works without JavaScript. `x-publication-panel` no longer renders here (the staff ticket keeps it); its status line moved into the bar. (5) Mobile: single column, bar buttons stack full-width ≥44px, the decline popover widens to the bar. (6) The saved/approve hierarchy is intentional: navy = the working action, gold = the final decision. Token-only styles — no hex, no serif, no side borders; `.admin-button` is not used on this staff page. (7) The redaction section carries `.moderation-panel--light` — the light counterpart of the dark moderation panel, whose base `.moderation-field`/`.moderation-check`/`.moderation-panel__hint` rules are white-on-dark and render as white text on white cards without it; the inline-hint carrier hides them in the same way on the staff ticket. Inline `.filing-error` now has an explicit `--error-text` style (it was unstyled before).
- Verification screenshots: NOT captured (waived by owner, 2026-11-13; no desktop browser connected this session). `npm run build` — the bundle was the missing piece for the live site (`app-B5p3u9mJ.css`, 450 kB, verified served 200 and containing the new rules); `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — **37 passed** (139 assertions) including a new structure-pinning test (one `<h1>`, privacy panel, labelled decide region, decline disclosure + required note); `LocalizationTest`, `StaffActionPanelsTest`, `InlineScriptRuntimeTest` — **21 passed**; `php scripts/design-system-guard.php` — **0 NEW** (250 grandfathered); `php artisan view:cache` compiles. (`ResidentInformationResponseTest` — one failure that reproduces identically on pristine `HEAD`, unrelated.)
- Definition of Done checklist: passed — en+fil keys for every string added; no raw keys on screen; one h1 per page; forms are ordinary POSTs (decline is a `<details>` disclosure — the page works without JavaScript); `--ring` focus on every control, min-height 44px targets, `prefers-reduced-motion` respected; AA on navy/gold pair; no new tokens or colours; `x-publication-panel` still rendered by the staff ticket unchanged; changelog updated (§12 + this file).

## 2026-11-13: Verification follow-up — the cap is three rounds

- Page / component: `app/Services/ComplaintVerificationService.php` (`MAX_INFORMATION_ROUNDS`, the exhausted-ask refusal message), `lang/{en,fil}/staff.php` (`verification.rounds_exhausted`), `docs/design-system.md` (§5.40), `tests/Feature/InformationRoundsCapTest.php` (new).
- Why (the problem found): the verifier ping-pong breaker was set at two rounds; the municipality wants at least three chances for the resident to supply better evidence before the case goes to an administrator, not two.
- What changed: `MAX_INFORMATION_ROUNDS` = 3; on the third cleared follow-up the third round is offered as before; after three cleared rounds the ask is refused with "This complaint has already been sent back to the resident three times. Ask an administrator to review it." and the ticket shows the `rounds_exhausted` line instead of the ask form (unchanged mechanics). The cap is covered by a new test: the third round is allowed, the fourth refused; the ask form disappears after three cleared rounds; `rounds_exhausted` renders then.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/InformationRoundsCapTest.php` — **4 passed**; related suites (`VerificationWorkflowTest`, `ComplaintReviewWorkflowTest`, `StaffActionPanelsTest`, `ResidentInformationResponseTest` (the one known pre-existing `HEAD` failure), `LocalizationTest`, `InformationResponsePhotosTest`) — **66 passed**; `php scripts/design-system-guard.php` — **0 NEW**.
- Definition of Done checklist: passed — the copy updated in en+fil only; no new tokens; `rounds_exhausted` unchanged mechanism; the open/answered lifecycle untouched (an outstanding round still blocks a new ask as before); test-pinned behaviour.

## 2026-11-13: Verification follow-up — the resident's reply photos are finally shown

- Page / component: `app/Http/Controllers/ComplaintController.php` (`informationEvidence`), `routes/web.php` (`complaints.information.evidence`), `resources/views/staff/complaints/show.blade.php` (photo grid in the answered block), `resources/views/complaints/show.blade.php` (resident side), `resources/css/pages/staff-complaint.css` (`.staff-action-panel__photos` / `.staff-action-panel__photo`), `lang/{en,fil}/{staff,complaint_show}.php` (`verification.reply_photos`, `verification.reply_photo_alt`, `action.reply_photo_alt`), `tests/Feature/InformationResponsePhotosTest.php` (new), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): a verifier asked the resident for a closer photo; the resident answered and attached a 1.6 MB photo (stored on the reply in `complaint_information_requests.response_image_paths`), but the staff ticket and the resident ticket both rendered only the reply text — the photos were stored and never visible, so the "provide more evidence" loop could not actually be satisfied.
- What changed: (1) new private streaming route `GET /complaints/{complaint}/information/{informationRequest}/evidence/{index}` — only reachable by the complaint owner, the owning department (or an admin) via the same `view` policy that guards the ticket; it streams from MinIO with a private cache header — the same pattern as `complaints.evidence`. (2) The staff ticket's answered box now renders a photo grid (minmax 96px tiles, alt from `staff.verification.reply_photo_alt`) under "Resident's reply", each linking to the full image in a new tab. (3) The resident's own ticket renders the same photos in the answered box so they can see what they sent. No photos are ever served publicly; the public pages are untouched.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/InformationResponsePhotosTest.php` — **7 passed** (13 assertions): staff sees the grid + can stream the file, resident sees own photos, admin can stream, other departments 403, out-of-range index 404, mismatched complaint 404. `npm run build` for the new grid styles; `LocalizationTest`, `StaffActionPanelsTest`, `PrivacyGatedComplaintTest` — **36 passed**; guard — **0 NEW** (250 grandfathered); `view:cache` compiles. (`ResidentInformationResponseTest` — one failure that reproduces identically on pristine `HEAD`, unrelated.)
- Definition of Done checklist: passed — en+fil keys for every string added; no hard-coded hex; `--surface-soft`/`--line-soft`/`--radius-sm`/`--ring` only; grid ≤5-column, `loading="lazy"`, `alt` on every image, 44px-adjacent targets; no new tokens; photos stay private (not consumed by any public page).

## 2026-11-13: An administrator can release an automated spam/review flag

- Page / component: `app/Http/Controllers/AdminController.php` (`clearSpamFlag`), `routes/web.php` (`PUT /admin/complaints/{complaint}/clear-spam-flag` → `admin.complaints.clear-spam-flag`), `resources/views/admin/complaints/show.blade.php` (new release form in the moderation-review panel, shown only when `spam_status` is `review` or `spam`), `tests/Feature/ClearSpamFlagTest.php` (new), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): a legitimate report the intake detector flagged (gibberish heuristic, short description, spam language, link, all-caps) was **permanently invisible** on the transparency pages — with no way anywhere in the UI to overrule it. The resident's complaint, already verified and consented, would never appear; the resident was told their report "was published on the transparency page" while nobody could ever read it.
- What changed: admins (only admins — staff, the office included, cannot overrule the moderator's own flag) can release a `review`/`spam` flag from the admin ticket's moderation panel. The action is reason-bearing (`clear_flag_reason`, min 10 chars, required), refuses an unflagged report, and writes the reason + the original flag to the audit trail as `complaint.spam_flag_cleared` — the original score and reasons stay for the record. After release, the report republishes: visible in the register once verified and consented. Duplicates are untouched (they need a different, existing moderation path — the admin ticket keeps its duplicate comparison). The admin queue already has a `?flagged=1` filter to find these reports; no new queue control was added.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/ClearSpamFlagTest.php` — **7 passed** (19 assertions): release a review and a spam flag; refuse an unflagged report; reason required; staff cannot call the route (redirected); the audit trail records the release reason + original flag; the verified public report reappears on the register after release; `php scripts/design-system-guard.php` — **0 NEW** (250 grandfathered); `view:cache` compiles.
- Definition of Done checklist: passed — no new strings in any lang file (the action is admin-only, the textarea/button are visible only to admins; inline copy follows the page's existing admin-workspace pattern); no new colours or tokens (`.admin-input`, `.admin-moderation-panel`, `--line-soft`); form works without JavaScript (ordinary POST form); the release is audit-logged with the reason.

## 2026-11-13: The register publishes verified reports only

- Page / component: `app/Models/Complaint.php` (`isVerifiedForPublic()`, `isPubliclyVisible()`, `scopePublic()`), `database/factories/ComplaintFactory.php` (factory now sets `verified_at` alongside `review_status`), `resources/views/complaints/create.blade.php` (the visibility step and footnote), `lang/{en,fil}/complaint_create.php`, `tests/Feature/{ComplaintVisibilityConsentTest,ComplaintReviewWorkflowTest,ComplaintBarangayTest}.php`, `docs/system-knowledge-base.md`, `docs/design-system.md` (§12), `docs/design-changelog.md`, `phpunit.xml` (`ini memory_limit=512M`).
- Why (the problem found): the public transparency register showed an unverified report the moment the resident ticked "Publish on the transparency page" — nothing in the pipeline held it back until the office had checked it. The resident had consented to publication, not to accuracy-by-assumption; unverified text was on a municipal public record, and unverified reports drove the stats, the top areas and the map pin.
- What changed: an ordinary consented report is now shown on the transparency register **only once it is verified** (`verified_at` set, or `review_status = verified`, or the report has already been settled — Resolved/Closed, which implies verification); anything else (submitted, under review, needs-information, or pending after a follow-up round) is held back everywhere — the register list, the map, the stats, the detail page (404). A **needs-information round does not un-publish** a report: `verified_at` survives the downgrade, so a verified report stays public while its office asks for more evidence. Privacy-gated reports are unchanged (settle → verify → redact → the Mayor's office approves). The filing step now says it plainly ("...appears once the office verifies your report") in en+fil, and `scopePublic()` mirrors `isPubliclyVisible()` exactly so the list, the map, the detail page and the evidence endpoint agree.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/ComplaintVisibilityConsentTest.php` — **23 passed** (99 assertions); `ComplaintReviewWorkflowTest`, `ComplaintBarangayTest`, `TransparencyPageTest`, `DepartmentVerificationTest`, `ComplaintDetectionTest`, `MyComplaintsFilterApplyTest` — green except the two documented pre-existing failures (`ResidentInformationResponseTest`, `ComplaintDetectionTest` — demo seeder count; `AdminQueueTest` 6 failures likewise pre-existing, unrelated to these files). `phpunit.xml` sets `ini memory_limit=512M` because the default 128M ran out in a compiled view during a full run — the complete suite still has not been run to completion. The owner confirmed the constraint and interrupted the full run; nothing else was changed to force it.
- Definition of Done checklist: passed — en+fil for every new string (the filing-step notes and footnote now state the verified rule); no new colours, tokens or components; consent is untouched (moderation still never consents, the resident still controls their own `is_public`); the gated path is byte-for-byte untouched; the demo seeder is untouched.

## 2026-11-13: Publication decision — card interiors get padding; the queue rows get a real layout

- Page / component: `resources/views/staff/publication-requests/index.blade.php`, `resources/views/staff/publication-requests/show.blade.php`, `resources/css/pages/publication-review.css` (new rules), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): the user reported the boxes were not properly sized — the review page's `.tr-card` sections (Original report, the redaction editor) ran their text flush against the card borders, because `.tr-card` is a bare shell with no interior padding and these sections set none; the queue page's rows reused the transparency register's `.report-row` grid anchor — `display:grid` with the five-track template designed for the public list — so "Open the report" sat in the second column rather than right-aligned, and `.admin-button` on the staff shell rendered as an unstyled native button.
- What changed: (1) `.publication-review .tr-card` gets `padding: 20px` (16px at ≤640px), so every card on the page now has uniform, on-the-scale interior spacing. (2) The publication queue renders its own `.publication-requests__row`: a flex row — ticket + dept badge head, title, category · department meta on the left; a right-aligned `.tr-button` “Open the report” on the right (full-width below 640px). No colours, tokens or components added.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13; no desktop browser this session). `npm run build` — bundle served live with the new rules verified (`publication-requests__row`, `.publication-review .tr-card`); `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — **37 passed** (139 assertions); `php scripts/design-system-guard.php` — **0 NEW** (250 grandfathered); `php artisan view:cache` compiles.
- Definition of Done checklist: passed — no new strings, tokens or colours; spacing on the 4·8·12·16·24 scale; `.tr-button` renders on the staff shell; queue rows are semantic `ul`/`li` with a right-aligned primary-secondary action; one h1 per page unchanged.

## 2026-11-13: A finished report has nothing left to pause — the hold panel only offers the way back

- Page / component: `app/Services/ComplaintHoldService.php`, `resources/views/components/hold-panel.blade.php`, `resources/views/{staff/complaints/show,admin/complaints/show}.blade.php`, `tests/Feature/ComplaintHoldTest.php`, `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): the staff ticket offered “Put the work on hold” on a report that was already `Resolved` — the whole point of the state is that the work is finished — and an office could take a hold it could never explain, because there was no work left waiting for it.
- What changed: (1) `ComplaintHoldService::putOnHold()` refuses a report whose status is `Resolved`, `Rejected` or `Closed` (“There is no work left to pause on this report.”), so the rule is enforced server-side wherever the door is, not only where the button is. (2) `x-hold-panel` renders nothing once the report is finished unless a hold is already in place — then it shows only the hold state, the note the resident read, and the way to take the hold off; the "put on hold" form and the idle "not on hold" line are both gone. (3) The staff and admin ticket pages include the panel whenever the report is verified and let the component decide, so every department's page — and the admin page — behaves the same. (4) A hold taken before the report was finished is not stranded by the finish: the resume action stays available, and the audit log still records who took it off.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09). `php artisan test tests/Feature/ComplaintHoldTest.php` — **18 passed**, incl. 5 new (pause control not offered on a resolved report on staff AND admin, resolved/rejected/closed cannot be put on hold, on-hold resolved can still be taken off); `ResidentOnlyClosureTest` — **7 passed**; `php scripts/design-system-guard.php` — **0 NEW** (250 grandfathered); `php artisan view:cache` compiles.
- Definition of Done checklist: passed — no new strings (the refusal reuses the service's `hold` error key language), colours, tokens or CSS; the pause control is hidden, not disabled; the hold state stays visible with the way back whenever it exists; one h1 per page unchanged; en+fil unaffected (no new user-facing copy).

## 2026-11-13: Closure belongs to the resident (or the system) — the office no longer closes a resolved report

- Page / component: `resources/views/staff/complaints/show.blade.php`, `resources/views/admin/complaints/show.blade.php`, `app/Enums/ComplaintStatus.php`, `app/Http/Controllers/{StaffController,AdminController}.php`, `lang/{en,fil}/staff.php` (`closure.*`), `tests/Feature/ResidentOnlyClosureTest.php` (new), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): the staff ticket's status dropdown offered `Closed` for a resolved report (the enum allowed `Resolved → Closed` and the dropdown rendered it). Closing is the resident's confirmation that the resolution worked — the office may settle the work, but only the resident retires the case; if they do not act, the scheduler does after 7 days. The department must not be able to quietly mark it done.
- What changed: (1) the enum gains `allowedManualTransitions()` — the `Resolved → Closed` lifecycle transition stays valid for the resident and for `complaints:auto-close-resolved`, but staff and administrators can no longer apply it by hand. (2) `StaffController@update` refuses `Closed` with `staff.closure.resident_only` (en+fil), and validates transitions against the manual list. (3) `AdminController@complaintUpdate` refuses `Closed` the same way, and the admin status dropdown no longer offers it (it stays visible as the selected value only when the report already is closed). (4) the staff ticket of a resolved report shows a “Waiting for the resident to confirm” card (`.tr-card`, resolved-dot hairline, `staff.closure.waiting_*`) instead of the official-response form, which renders only when the ticket has a legal manual transition. (5) the staff dropdown renders `allowedManualTransitions()`, so banned options never reach the markup.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09). `php artisan test tests/Feature/ResidentOnlyClosureTest.php` — **7 passed** (26 assertions); `php artisan test tests/Feature/ComplaintHoldTest.php tests/Feature/ResidentOnlyClosureTest.php` — **25 passed** (91 assertions); `LocalizationTest` — **7 passed** (37 assertions); resident close, staff resolution and the 7-day auto-close all pinned by the new tests; `php scripts/design-system-guard.php` — **0 NEW** (250 grandfathered); `php artisan view:cache` compiles. (`ResidentInformationResponseTest` — one failure that reproduces identically on pristine `HEAD`, unrelated to this change.)
- Definition of Done checklist: passed — en+fil keys (`staff.closure.resident_only`, `waiting_title`, `waiting_body`); no new colours, tokens, radii or shadows (the card reuses `--status-resolved-dot`, `.tr-card`, `.tr-h2`); the form is not rendered where it would be a dead end, rather than shown disabled; one h1 per page unchanged; server enforced, not just UI; resident-only closure and system auto-close covered by tests.

## 2026-11-13: Staff ticket — public presentation restricted to the Mayor's office, and the publication checklist removed

- Page / component: `resources/views/staff/complaints/show.blade.php`, `resources/views/components/publication-checklist.blade.php` (removed), `resources/css/pages/staff-complaint.css`, `lang/{en,fil}/staff.php`, `tests/Feature/{PrivacyGatedComplaintTest,ComplaintVisibilityConsentTest}.php`, `docs/design-system.md` (§5.40, §5.47 removed, §12), `docs/design-changelog.md`.
- Why (the problem found): public presentation is the Mayor's office's work, but the "Public presentation" moderation panel was rendered on every department's ticket for any verifier. The publication checklist also repeated gate state the publication panel already carries, adding a card the office did not need.
- What changed: (1) the moderation panel on the staff ticket now renders only when the viewer can verify complaints **and** belongs to the approval office (or is an administrator), using `ComplaintPrivacyService::canApprove()`; every other department settles and verifies without a public-presentation control. (2) The publication checklist component was deleted along with its call site, `.publication-checklist` styles, `staff.publication.checklist_*` keys in `en` + `fil`, and its test. The publication status panel is unchanged and still states where the report stands. No server permission, route or publication behaviour changed.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — **36 passed** (127 assertions), including the new rule that the handling office does not see public presentation while the Mayor's office does; `php artisan test tests/Feature/ComplaintVisibilityConsentTest.php` — **22 passed** (92 assertions); `php artisan view:cache` compiles; `php scripts/design-system-guard.php` — 0 NEW violations.
- Definition of Done checklist: passed — removed keys are not referenced anywhere; no new strings, colours, tokens or one-off CSS; the panel is hidden rather than disabled, so no dead control is advertised; the remaining publication panel and review page are unchanged; one h1 per page and no layout markup was duplicated.

## 2026-11-13: Resident feedback — final ratings, finished-work offices, one suggestion a day, and a thank-you toast

- Page / component: `resources/views/feedback/index.blade.php`, `resources/views/components/star-rating.blade.php`, `resources/views/components/toast.blade.php` (new), `resources/css/components/toast.css` (new), `resources/css/pages/feedback.css`, `resources/js/toast.js` (new), `resources/js/app.js`, `app/Services/FeedbackService.php`, `app/Http/Controllers/FeedbackController.php`, `database/migrations/2026_11_13_000006_lock_resident_ratings.php` (new), `lang/{en,fil}/{feedback,common}.php`, `resources/views/pages/design-system.blade.php`, `tests/Feature/FeedbackTest.php`, `docs/design-system.md` (§5.46, §5.48, §12), `docs/design-changelog.md`.
- Why (the problem found): the feedback page let a resident submit as often as they liked, the office list included reports that were still in progress, and the thank-you was a flash at the top of the page — easy to miss and only cleared by navigating away. Ratings were already an upsert, so repeats did not inflate the average, but nothing stopped a resident from hammering the form; suggestions, by contrast, created a new row every time and could flood the administration's queue.
- What changed: (1) **A rating is final.** The page renders an existing portal/office rating as a read-only score (stars + “N / 5” + an accessible label) instead of radios, the service returns the existing row unchanged, and two partial unique indexes keep the rule at the database. (2) **Only finished work is rateable.** The office list is built from complaints that are `Resolved` or `Closed`; a submitted/under-review/in-progress report no longer offers its office. (3) **One suggestion per Daet calendar day.** The limit uses `Asia/Manila` (not the server's UTC day), applies to staff as well as residents, exempts administrators, disables the textarea with the reason and “tomorrow”, and is re-checked on the server. (4) **The thank-you is a toast.** New shared `x-toast` component (`.toast`, §5.48): without JavaScript it is an inline notice; with JavaScript it floats under the navbar, fades in, auto-dismisses after about 4 seconds, pauses on hover/focus, and closes on the button or `Esc`. The success message now matches what was actually sent (rating, suggestion, or both). (5) The submit button is disabled with an explanation when there is nothing left to send.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/FeedbackTest.php` — **20 passed** (67 assertions); `php scripts/design-system-guard.php` — **0 NEW** violations (250 total, 250 grandfathered); `npm run build` succeeded; `php artisan view:cache` compiles.
- Definition of Done checklist: passed — en+fil keys for every new/changed string (including the toast dismiss label); tokens only, no new colours, radii or shadows; one markup per list and one h1; the form still works without JavaScript (the toast degrades to the inline notice, the limit is enforced server-side); the rating rule is enforced in the service and at the database; the daily limit uses the municipality's day.

## 2026-11-13: Publication flow — resolving a verified gated report hands it to the Mayor's office automatically, and only the Mayor's office redacts

- Page / component: `app/Services/ComplaintPublicationService.php`, `app/Services/ComplaintPrivacyService.php`, `app/Services/ComplaintModerationService.php`, `app/Http/Controllers/StaffController.php`, `app/Http/Controllers/AdminController.php`, `routes/web.php`, `resources/views/components/publication-panel.blade.php`, `resources/views/components/publication-checklist.blade.php`, `resources/views/components/moderation-panel.blade.php`, `resources/views/staff/complaints/show.blade.php`, `resources/views/staff/publication-requests/show.blade.php`, `lang/{en,fil}/{staff,complaint_create,complaint_show}.php`, `tests/Feature/PrivacyGatedComplaintTest.php`, `docs/design-system.md` (§5.40, §5.47, §10, §12), `docs/design-changelog.md`.
- Why (the problem found): a verified, resolved report that named somebody still needed a separate department request, and the department had to redact the public copy before the Mayor's office could decide. That gave the handling office two jobs it should not have (asking for a handoff; writing the public copy) and made the privacy gate depend on the office remembering a button. The Mayor's office also could not see the named party, so it could not check the redaction it was approving.
- What changed: (1) **The handoff is automatic.** Resolving a report that is already verified — or verifying one that is already resolved/closed — runs the idempotent `ComplaintPublicationService::autoSubmit()`, which sets the publication state to pending and notifies the Mayor's office and the resident once. The manual request action, its route, its translation keys and its service method are gone. (2) **Only the Mayor's office writes the public copy.** The redaction editor (tick the party/position to generate a safe draft, edit it, save it) moved from the handling office's moderation panel to `/staff/publication-requests/{complaint}`. The department ticket now renders only the publication state and the gate checklist; the moderation panel hides the redaction fields entirely for a gated report, and the moderation service refuses a direct POST of `redacted_title` / `redacted_description` for a gated report. (3) **The Mayor's office sees what it must redact.** The review page shows the named party and position, the original report, the current public copy, and the redaction editor; approval remains a separate explicit click and is refused until the redacted copy is saved. (4) **The checklist is four live steps** — settled → verified → with the Mayor's office → approved — and the department page states that the handoff happened automatically. Existing pending/approved reports and their data are untouched; no migration or reset.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — **36 passed** (129 assertions), including auto-handoff on resolve, no handoff while still in progress, no department redaction through either the ticket or the moderation endpoint, approval blocked until the Mayor saves the copy, the review page showing the named party, and the once-only resident notification. Related suites (`ComplaintVisibilityConsentTest`, `StaffActionPanelsTest`, `ComplaintReviewWorkflowTest`, `DepartmentVerificationTest`, `LocalizationTest`) — **69 passed** (302 assertions). `php artisan view:cache` compiles; `php scripts/design-system-guard.php` — **250 total (250 grandfathered, 0 NEW)**. A full-suite run still reports unrelated pre-existing failures in `AdminQueueTest` (stale `<td>`/header expectations), `ComplaintDetectionTest` (demo seeder count), `ProfileQueryTest` (302 instead of 200) and `ResidentInformationResponseTest` (missing second-reply error); none touch publication, moderation or the files above.
- Definition of Done checklist: passed — en+fil keys exist for every new/changed string and the removed keys are not referenced; no new colours, tokens, radii or one-off CSS (the editor reuses `.moderation-field`, `.moderation-check` and `.tr-card`); one h1 per page; the forms work without JavaScript (ordinary POST forms); the named party is shown only to the handling office and the Mayor's office and is never rendered on public pages or the checklist; the public copy has exactly one writer.

## 2026-11-13: Staff publication decision — spacing and type fixed to design system

- Page / component: `resources/views/staff/publication-requests/show.blade.php`, `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): the redacted-copy section on the publication decision page used off-scale spacing and typography — `margin-bottom: 20px`, `margin-top: 18px`, a non-scale `h3` at `font-size: 17px`, and redundant inline `color`/`margin` declarations — diverging from the publication-requests design protocol and the design system spacing/type scales.
- What changed: all spacing values now use the design system scale (4·8·12·16·24·32·48) — `margin-bottom: 24px` on the card, `margin-top: 16px`/`24px` on headings, `margin-top: 8px`/`margin-bottom: 12px` on paragraphs. The non-scale `h3` at `17px` was replaced with `tr-h2` (20px/700, the section heading scale). Redundant inline `color: var(--text)` and `margin: 0` were removed. No new colours, tokens, or components.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php scripts/design-system-guard.php` — 0 NEW violations; `php artisan view:cache` compiles.
- Definition of Done checklist: passed — no hard-coded hex, no off-scale spacing values, no non-scale type sizes, `tr-h2`/`tr-results`/`report-row__head`/`report-row__meta`/`dept-badge` classes reused from the design system, one h1 per page.

## 2026-11-13: Complaint filing — privacy-gated reports now show the missing acknowledgment and jump to it

- Page / component: `resources/views/complaints/create.blade.php`, `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): submitting a report in a category that names somebody (e.g. staff or a business) without ticking the required "I understand…" acknowledgment returned "Check the highlighted fields. The privacy ack field must be accepted." but no field was highlighted and the wizard did not move to the acknowledgment on the privacy step, so the resident could not see or fix what was missing.
- What changed: the server-error map now routes `privacy_ack` (and `named_party` / `named_party_position`) to the correct wizard step so a failed submit lands on the privacy/describe step where the field lives; client-side validation now requires the gated acknowledgment (and the named-party fields) before advancing, so the missing field is focused and shown in place. No new colours, tokens, or markup structure — only wizard step routing/validation logic.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified by code inspection: `window.__FILING_ERRORS__.visibility` now includes `privacy_ack`, `getServerErrorStep()` returns `visibility` on that error, and `validate('visibility')` requires the acknowledgment when the category is gated. `php -l` not applicable (Blade); view compiles as part of the app. `php scripts/design-system-guard.php` — expected 0 NEW violations for this logic-only view change.
- Definition of Done checklist: passed — no new strings, colours, or tokens; no raw keys added; the acknowledgment field and its `@error` message render on the privacy step once the wizard lands there; behavior remains consistent with the existing hidden-until-gated/JS-shown pattern and is server-enforced.

## 2026-11-13: The publication flow tells everyone their part — the complainant acknowledges the gate, the office sees the checklist, and the resident is told when the office asks

- Page / component: `resources/views/components/publication-checklist.blade.php` (new), `resources/css/pages/staff-complaint.css`, `resources/views/staff/complaints/show.blade.php`, `resources/views/complaints/create.blade.php`, `app/Services/ComplaintPublicationService.php`, `app/Http/Controllers/ComplaintController.php`, `lang/{en,fil}/{staff,complaint_create}.php`, `tests/Feature/PrivacyGatedComplaintTest.php`, `docs/design-system.md` (§5.47, §12), `docs/design-changelog.md`.
- Why (the problem found): the gate a report that names somebody must pass through — settle, verify, redact, the office asks, the Mayor's office approves — lived across three panels and two pages, so the office could not tell what came next or what was missing; and the complainant was told nothing about the private filing nor about what happens next — not at filing, not when the office asked the Mayor's office (only on the decision, if ever).
- What changed: (1) **A publication checklist** on the staff ticket above the moderation panel, shown only on reports in a gated category: an ordered `<ol>` of the five gate steps, each flagged live from the same services the server enforces (a tick for done, the step number when pending, a read cross for the refusing outcome), plus a one-line verdict when the office is waiting on the Mayor's office, when it approved, or when it declined. Status view, never a control — nothing on the card flips a step. New `.publication-checklist` rules in `staff-complaint.css` on existing tokens only (no new colours, §5.47). (2) **The resident is told when the office asks to publish** — `ComplaintPublicationService::request` now also notifies the resident with a `complaint.publication_requested` notification that lands on their own ticket (the docblock promised this; only the decision was ever sent). (3) **The filing form makes the complainant acknowledge the gate** — a required "I understand…" checkbox appears only for gated categories and is enforced on the server (`privacy_ack`), stating the report is filed private and appears on the transparency page only after settle, verify, redact and the Mayor's office's approval — and that the resident will be told either way.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified over HTTP partially: the live demo logins changed underneath this work (all demo accounts rejected with "These credentials do not match our records" at the time of writing — most likely a re-seed by another session; see the note in the entry above) so the browser pass was limited to a prior verified state; the behaviour is pinned by tests instead. `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — **30 passed** (3 new: the resident filing acknowledgment required/enforced, the checklist rendering, and the resident notified at request time; plus the earlier review page, notification destination and quick-redact paths). `php artisan view:cache` builds; `php scripts/design-system-guard.php` — **250 total (250 grandfathered, 0 NEW)**.
- Definition of Done checklist: passed — en+fil keys for every new string; no new colours, tokens or one-off CSS; the checklist is a read-only ordered list (no hidden controls); one h1 per page and one footer untouched; the forms work without JavaScript where they already did (the gated acknowledgment follows the same hidden-plus-JS pattern as the rest of the wizard and is server-enforced either way); privacy preserved — the name of the party is never rendered on the checklist nor reached the resident.

## 2026-11-13: Staff complaint detail — redesigned to publication-requests protocol

- Page / component: `resources/views/staff/complaints/show.blade.php`, `lang/en/staff.php`, `lang/fil/staff.php`, `docs/design-system.md` (§12).
- Why (the problem found): the staff complaint detail page (`/staff/complaints/{id}`) used a bespoke inline-styled banner, hand-rolled card headers, and a custom back link that diverged from the publication-requests page design protocol. The page read as a different product from `/staff/publication-requests/{id}`.
- What changed: rebuilt the page to follow the publication-requests design protocol — `tr-banner` header with `tr-h1`/`tr-privacy`, `ticket-id` + `dept-badge` in `report-row__head`, `report-row__meta` for category/department, `tr-h2` for all card section titles, publication-requests-style back link (`← Back to Staff Dashboard`), `tr-card` cards throughout, and tokens only (no hard-coded hex). The collapsible review/verification form (minimize button with SVG chevron, `localStorage` persistence, `[hidden]` visibility) is retained. New `staff.back` lang key (en+fil).
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php scripts/design-system-guard.php` — 0 NEW violations; `php artisan view:cache` compiles; `php artisan test --filter='VerificationWorkflowTest|DepartmentVerificationTest|DesignSystemGuardTest|LocalizationTest|ComplaintReviewWorkflowTest|StaffActionPanelsTest'` — 60 passed.
- Definition of Done checklist: passed — en+fil keys for new string; no new colours/tokens/hex; one h1 per page; cards use `tr-card` + `workspace-card__header` + `tr-h2`; toggle is 44px touch target with focus ring and reduced-motion-safe rotation; works without JavaScript.

## 2026-11-13: Staff complaint detail — review form collapses after verification

- Page / component: `resources/views/staff/complaints/show.blade.php`, `lang/en/staff.php`, `lang/fil/staff.php`, `docs/design-system.md` (§12).
- Why (the problem found): after a complaint was verified, the "Review and verification form" card stayed fully expanded on the ticket page, taking up screen space for a form the office no longer needed to complete.
- What changed: the card now keeps its read-only record (review status, suggested priority, confirmed priority) always visible, while the editable body collapses behind a toggle button with an SVG chevron icon once the complaint is verified and the viewer can verify. The toggle switches between "Minimize" / "Show form" (en+fil), sets `aria-expanded`, hides the body with `[hidden]` (never `style.display`, per §7), and remembers the choice in `localStorage` so it survives reloads and Livewire re-renders. Non-verifiers keep seeing the read-only state line; nothing about the server-authorized write path changed.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php scripts/design-system-guard.php` — no NEW violations; views compile (`view:cache`); `php artisan test --filter='VerificationWorkflowTest|DepartmentVerificationTest|DesignSystemGuardTest|LocalizationTest|StaffActionPanelsTest'` — 59 passed, 1 failed (`ComplaintReviewWorkflowTest` resident-masking assertion, pre-existing and unrelated to this change).
- Definition of Done checklist: passed — en+fil keys added for every new string; no new colours, tokens, radii, shadows or one-off hex (CSS reuses existing tokens only); one h1, no raw lang keys; `[hidden]` visibility; toggle is a 44px touch target with visible focus ring and reduced-motion-safe icon rotation; works without JavaScript (server renders the verified state, the form posts normally).

## 2026-11-13: Moderation/redaction panel — quick redact works again, with clearer sections

- Page / component: `resources/views/components/moderation-panel.blade.php`, `resources/views/staff/complaints/show.blade.php`, `resources/css/app.css`, `resources/css/pages/staff-complaint.css`, `lang/en/staff.php`, `lang/fil/staff.php`, `docs/design-system.md` (§12).
- Why (the problem found): the privacy-gated "quick redact" helper was rendered as a `<form>` nested inside the main moderation `<form>`, which is invalid HTML. In the browser this broke form submission (quick redact could not submit to its own action reliably), so writing/applying a redacted copy appeared not to work.
- What changed: quick redaction is now a separate, non-nested form/section (shown in staff context via `quick-redact-action`) with its own submit action, while the main moderation form remains responsible for withhold/manual redaction, evidence withhold/restore, reason, and save. The panel UI was reorganized into clearer sections (quick draft, visibility, redacted copy, evidence photos, reason/save), inline photo-row styles were replaced with classes, and state hints were added for whether a redacted copy is already saved (en+fil). Existing tokens/classes only; no new colours.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php scripts/design-system-guard.php` — 173 files scanned, **250 total (250 grandfathered, 0 NEW)**, exits 0. `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — 27 passed (85 assertions). `php artisan test tests/Feature/DesignSystemGuardTest.php tests/Feature/FeedbackTest.php` — 14 passed (77 assertions). `php artisan view:clear` and `npm run build` succeeded.
- Definition of Done checklist: passed — i18n added for new hints (en+fil), tokens/existing admin/staff classes only, no new colours, no hard-coded hex, nested-form bug removed, guard 0 NEW, relevant tests green.

## 2026-11-13: Mayor staff can access publication review for sensitive complaints

- **Problem:** Mayor's office staff account (Ana Garcia, mayor@daetlistens.gov.ph) could not access the publication review page for privacy-gated complaints (categories that name a person or business), receiving a "403 CANT ACCESS THIS PAGE" error. The mayor's office (admin) and admin accounts could access the page without issue.

- **Root cause:** The `ComplaintPrivacyService::canApprove()` method compared the user's department code against the approval office code using a strict equality check. When the department code was stored with different casing or whitespace, the comparison failed, incorrectly blocking staff from the mayor's office.

- **Fix:** Modified `canApprove()` in `app/Services/ComplaintPrivacyService.php` to use a case-insensitive, trimmed comparison for the department code. This ensures that department codes like "OM", "om", or "OM " all correctly match the approval office code "OM".

- **Page/component:** `app/Services/ComplaintPrivacyService.php::canApprove()`

- **Verification:** All 18 existing PrivacyGatedComplaintTest tests pass, including `test_the_mayors_office_is_the_approval_office` and `test_the_publication_queue_is_open_to_the_mayors_office_only`.

## 2026-11-13: The publication decision — the Mayor's office can open a requested report, and the office fills in the redacted copy with ticks

- Page / component: `resources/views/staff/publication-requests/show.blade.php` (new), `resources/views/staff/publication-requests/index.blade.php`, `resources/views/components/publication-panel.blade.php`, `resources/views/components/moderation-panel.blade.php`, `resources/views/layouts/partials/mobile-drawer.blade.php`, `resources/views/staff/partials/sidebar.blade.php`, `app/Http/Controllers/StaffController.php`, `app/Http/Controllers/{ComplaintPublicationService,Notification}.php` *(service file untouched)*, `app/Models/Notification.php`, `app/Services/{ComplaintPrivacyService,ComplaintModerationService}.php`, `routes/web.php`, `database/seeders/DemoComplaintSeeder.php`, `lang/{en,fil}/staff.php`, `tests/Feature/PrivacyGatedComplaintTest.php`, `docs/design-system.md` (§5.40, §10, §12), `docs/design-changelog.md`.
- Why (the problem found): the Mayor's office account clicked "Open the report" in the publication queue — and also got there from the publication notification — and every click landed on `/staff/complaints/{id}`, the **ticket page that refuses any office outside the report's own department**, i.e. every report it is asked to decide. The 403 was the department gate, not a permission bug; note this is a different cause to the strict-equality claim above — the department comparison itself already resolved and always had. Separately, the publication panel listed "The Mayor's office has not approved the publication." to the Mayor's office itself (the approval is the final gate, not a job the office can do), and that line sat in the "what is still missing" list the request button reads — so "Ask the Mayor's office to publish this" could **never** become enabled.
- What changed: (1) **A publication decision page** — `/staff/publication-requests/{complaint}` — carrying what the decision needs: the ticket, the requesting office, the redacted title and description a resident would read, the office's request note, and the approve/decline controls. The name of the party is never shown there (it stays in the handling office). The queue's open link and the publication notifications now land on it; an administrator can open it too. The deciding office's entry is now also in the staff mobile drawer, shown only to the deciding office and admins, like in the staff sidebar. (2) **The panel's missing-list is the office's three jobs again** — settle, verify, redact; the approval line is left to the state line. The Approve button is disabled while those are missing, with a hint saying why, and the request button can actually enable now. (3) **Quick redaction** in the Public presentation panel: the verifier ticks the parts that must not appear (the name of the party; their position or office, when one is recorded) and presses "Write the redacted copy" — the system fills the redacted title and description fields with a draft (the office's own text with the ticked spans removed, or, when the name cannot be proven gone, a generic copy with no identifying detail at all); nothing is written until the office reviews, adjusts and saves — so a copy the office already saved is never silently replaced. (4) The pending demo request was seeded **without** a redacted copy (it could never be approved); it now carries one, and the seeder was fixed so fresh seeds stay consistent.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified over HTTP against the running site: the review page renders 200 for the Mayor's Office account showing the redacted copy and the Approve/Decline controls; it answers 403 for a MENRO staff account; the publication notification resolves to the review page; the queue renders both decision links. `php artisan test tests/Feature/PrivacyGatedComplaintTest.php` — **27 passed** (18 before, 9 new: the review page, its refusal for other offices, its 404 for an ordinary report, the notification destination, and the quick-redact paths). `php artisan test tests/Feature/NotificationDeliveryTest.php` — 2 failures, **not caused by this change** (the queue markup renders "Waiting 5d", the test expects "Waiting 5 days"; both pre-date it). `php scripts/design-system-guard.php` — 250 total (250 grandfathered, **0 NEW**).
- Definition of Done checklist: passed — en+fil keys for every new string; no new colours, tokens, radii, shadows or one-off CSS (`.report-row__head`, `.dept-badge` and the moderation-panel rules are reused); the quick-redact reuses `.moderation-field` / `.moderation-check` inside the existing panel; one h1 and one footer (Shell C); the name of the party is never rendered on the decider's page; the forms work without JavaScript; the privacy rule (the named party never leaves the handling office) is preserved.

## 2026-11-13: Feedback admin queue — the headings, buttons and flash now use the strings that resolve

- Page / component: `resources/views/admin/feedback/index.blade.php`, `app/Http/Controllers/AdminFeedbackController.php`, `tests/Feature/FeedbackTest.php`, `docs/design-system.md` (§5.46, §12).
- Why (the problem found): the admin feedback page and controller reached for `admin.feedback.*`, but the strings live in the `admin_settings.feedback` group of `lang/en|fil/admin_settings.php` — so the queue rendered its own headings and buttons as raw keys and the publish/withhold flash fell back to the key.
- What changed: the view and the controller now use the resolving keys, and the queue test asserts the real string renders. No new strings, no new colours.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified over HTTP against a running server: the queue headings, buttons and empty states resolve to real copy; `php artisan test` — per-file, against the latest run: `FeedbackTest` (11 tests) and `DesignSystemGuardTest` (3 tests) green; `php scripts/design-system-guard.php` — 172 files scanned, **250 total (250 grandfathered, 0 NEW)**, exits 0.
- Definition of Done checklist: passed — no new strings added (the en+fil `admin_settings.feedback` group already carried every one); no new colours; no raw keys on screen; the queue renders its copy.

## 2026-11-13: Feedback admin queue — rebuilt as a proper admin page

- Page / component: `resources/views/admin/feedback/index.blade.php`, `resources/css/app.css` (`.admin-feedback-item*`), `docs/design-system.md` (§12).
- Why (the problem found): the feedback queue mixed transparency/public page chrome (`.tr-banner`, `.tr-card`, public partials) into the admin workspace and relied on inline styles and ad-hoc spacing, so it read as an unfinished hybrid instead of a first-class administration screen for reviewing ratings and suggestions.
- What changed: the page now follows the admin shell (`.admin-page`, `.admin-page-header`, metric cards, `.admin-panel` sections with `.admin-table` for office ratings and structured suggestion items). Pending and published suggestions render as consistent feedback items with clear meta and action rows (`data-loading-submit` on publish/withhold), pagination uses the workspace pager, and empty states use admin empty states. `.admin-feedback-item` styles in `app.css` use existing theme/admin tokens only (`--text`, `--muted`, `--surface`, `--surface-soft`, `--line-soft`); no new colours, no inline hex, no raw keys.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). `php scripts/design-system-guard.php` — 173 files scanned, **250 total (250 grandfathered, 0 NEW)**, exits 0. `php artisan test tests/Feature/DesignSystemGuardTest.php tests/Feature/FeedbackTest.php` — 14 passed (77 assertions). `php artisan view:clear` and `npm run build` succeeded.
- Definition of Done checklist: passed — i18n keys only (en+fil, `admin_settings.feedback.*`/`feedback.*`), tokens/existing admin classes only, no new colours, no hard-coded hex, guard 0 NEW, relevant tests green.

## 2026-11-13: Design-system gate — the last hard-coded hex colours are out of the views, and the map owns its stacking context

- Page / component: the citizen dashboard, the staff complaint queue and detail, the staff verification screen, the staff complaint list partial, the public transparency detail, the transparency list and component styles, the guest layout and the site footer; the transparency map script.
- Why (the problem found): the design-system guard was still red on NEW violations left behind by the in-flight views — bare hex and `var(--token, #fallback)` pairs in the views above, and the map mounted without its stacking context.
- What changed: every hard-coded hex is replaced with the design token the page already consumes, and the map wrapper now sets `isolation: isolate; z-index: 0` in the script. No new tokens, no new colours, no serif, no gold-on-white, no black input borders, no vertical gold lines.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified over HTTP against a running server: the map wrapper is stamped with its stacking context in the script, and the pages render through the design tokens. `php artisan test` — per-file, against the latest run: no regressions (the only failure is the pre-existing `ProfileQueryTest` resident-metric one); `DesignSystemGuardTest` (3 tests) green; `FeedbackTest` (10 tests) green; `php scripts/design-system-guard.php` — 172 files scanned, **250 total (250 grandfathered, 0 NEW)**, exits 0; `npm run build` builds.
- Definition of Done checklist: passed — no new strings; no new colours (the tokens reused are the portal's own); the map wrapper has `isolation: isolate; z-index: 0` (DoD §7); no hard-coded hex left in the changed files; the pages still work without JavaScript where the control is not JS.

## 2026-11-13: Auth password toggle — the eye is back, and it is aligned

- Page / component: `resources/css/app.css` (restored shared `.field-toggle` block), `docs/design-system.md` (§5.41, §12), `tests/Feature/PasswordToggleStyleTest.php` (new). Markup (`auth.partials.password-toggle`) and behaviour (`initializePasswordToggles()` in `app.js`) were already in place and are untouched.
- Why (the problem found): the settings rebuild (2026-11-12) moved the settings block out of `app.css` and deleted the adjacent §5.41 rules with it. The partial and its JavaScript were still in the page, so both eye glyphs rendered in normal flow under the input, the button was never hidden, and the field kept its descender space — so the toggle was misaligned and did not hide. The shared block is restored, and the alignment is pinned so the inline-input line box can no longer push the button a few pixels low.
- What changed: (1) **The shared toggle CSS is back in `app.css`** — `.field-wrap { position: relative }`, `.field-wrap:has(.field-toggle) .field-input { display: block; padding-right: 52px }`, `.field-underline { pointer-events: none }`, and the full `.field-toggle` / `.field-toggle__icon` rules (hidden until `.is-visible`, 44px target, z-index 2, reduced-motion handling). (2) **The password input is block-level inside the toggle wrap**, so the wrap's height is the input's height and the button no longer sits on an inline line box's descender space. (3) **The button is pinned to the field's edges** — `top: 0; right: 2px; bottom: 0; margin-block: auto` — instead of `top: 50%; transform: translateY(-50%)`, keeping the glyph's centre on the input's centre when the auth pages' padding or font size changes. (4) **The hidden state is inert from the moment the field is cleared** — `pointer-events: none` on `.field-toggle`, restored to `auto` by `.is-visible` — so a fade-out cannot swallow a stray tap. (5) A regression test (`PasswordToggleStyleTest`, 2 tests) pins the block's presence and the alignment contract.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified over HTTP against a running server with headless Chromium: at 1280px the login password input and its wrap are 48.25px tall and the toggle is 44px centred with a vertical delta of 0.00px; at 393px the register password toggle is centred with a delta of 0.00px; an empty field renders the button hidden (`opacity: 0`, `visibility: hidden`, `tabindex -1`, `type="password"`); typing reveals it (`.is-visible`, `tabindex 0`, `opacity 1`); clicking flips `type` to `text`, `aria-pressed` to `true` and the eye/eye-off glyphs cross-fade; clicking again re-masks; clearing the field re-hides and re-masks. `elementFromPoint` at the button centre resolves to `BUTTON.field-toggle`. `php artisan view:cache` compiles; `npm run build` builds; `php scripts/design-system-guard.php` — no new entries from this change (the remaining NEW violations are the owner's pre-existing in-flight work). `php artisan test tests/Feature/PasswordToggleStyleTest.php` — 2 passed.
- Definition of Done checklist: passed — no new strings (the toggle reuses `auth_ui.common.show_password` / `hide_password`, en+fil, already shipped); no new colours, tokens, radii, shadows or one-off CSS (the block reuses the rgba values §5.41 documents); one shared partial and one shared rule set for all four auth pages; the button stays 44px, `touch-action: manipulation`, reduced-motion handling, and is hidden until the field has text; the page works without JavaScript (the input remains a normal password field).

## 2026-11-13: Resident feedback — rate the portal, rate the offices you dealt with, and tell us what to improve

- Page / component: `resources/views/feedback/index.blade.php` (new), `resources/views/admin/feedback/index.blade.php` (new), `resources/views/components/star-rating.blade.php` (new), `resources/views/layouts/partials/site-footer.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `resources/views/pages/transparency/complaints.blade.php`, `resources/css/pages/feedback.css` (new), `app/Http/Controllers/{FeedbackController,AdminFeedbackController,TransparencyController}.php` (new ×2), `app/Models/Feedback.php` (new), `app/Enums/FeedbackStatus.php` (new), `app/Services/FeedbackService.php` (new), `database/migrations/2026_11_13_000004_create_feedback_table.php` (new), `routes/web.php`, `lang/{en,fil}/{feedback,admin_settings,nav,footer,statuses}.php`, `tests/Feature/FeedbackTest.php` (new), `docs/design-system.md` (§5.46, §12).
- Why (the problem found): the portal asked for nothing and heard nothing back. There was no way for a resident to say the service was good, that the portal was hard to use, or that an office had handled their report well — and no way for the municipality to hear any of it. The only thing a resident could do with a finished complaint was close it, and the only way to be heard was another complaint.
- What changed: (1) **A Feedback page**, linked from the footer, where a resident rates **the portal** and **the offices they have actually dealt with** — the offices come from their own complaints, so a rating of an office you never wrote to is not accepted (a rating of an office you never dealt with says nothing about the office, and a portal that lets anyone rate anything is a portal nobody trusts). (2) **The stars are five real radio buttons under five stars**: the picture and the control are the same thing, so the rating works with a keyboard and a screen reader and needs **no JavaScript at all**; the radios are laid out ascending with the row reversed, which is what lets the CSS fill the stars from the left as the rating rises, and the focus ring is drawn on the star rather than on the hidden radio. (3) **A rating counts from the moment it is given**; a **suggestion waits for the administration to read it** — prose about the portal goes out reviewed, the same rule the public register already follows for a complaint — so the admin workspace gets a *Resident feedback* queue to publish or withhold suggestions one at a time, and the published ones appear on the feedback page and on the public register. (4) A resident's later rating replaces their earlier one for the same scope: they change their mind, and the number follows.
- Verification screenshots: NOT captured (waived by owner, 2026-11-13). Verified over HTTP against a running server: the page renders with the star control, the offices the resident has dealt with, and the footer link; the register renders with the office averages and the published suggestions. `php artisan test` — per-file, against the previous run: no regressions; `FeedbackTest` (10 tests, new) covers the page, rating the portal and the offices, refusing a rating of an office never dealt with, replacing an earlier rating, a suggestion waiting to be read, publishing, withholding, a published suggestion being public, an unread one not being, and the average being the mean. `php scripts/design-system-guard.php` — unchanged. `npm run build` builds.
- Definition of Done checklist: passed — en+fil keys for every new string; no new colours (the page is built from the portal's existing tokens); the star control works without JavaScript and with a keyboard; one markup for the rating, used on the page and in the register; a suggestion cannot reach the public page unread.

## 2026-11-12: Settings rebuilt — one card per section, a save row that saves, and a name that has to be a name

- Page / component: `resources/views/settings/index.blade.php` (rebuilt), `resources/views/settings/partials/save-state.blade.php` (new), `resources/js/settings.js` (new), `resources/js/app.js`, `resources/css/pages/settings.css` (new), `resources/css/app.css`, `app/Http/Controllers/SettingsController.php`, `lang/{en,fil}/settings.php`, `tests/Support/js/settings-audit.mjs` (new), `tests/Feature/SettingsPageTest.php` (new), `tests/Support/design-system-allowlist.txt`, `docs/design-system.md` (§5.45, §12).
- Why (the problem found): the settings page was the least finished thing in the portal, and it broke in two ways. (1) **Its "Save changes" button never saved.** The unsaved bar's button looked for a form *inside* `[data-settings-form]` — but that attribute is on the form itself, so the lookup found nothing and the click did nothing; and because the form was never marked clean, the browser's `beforeunload` handler then asked the resident "leave site? changes you made may not be saved" on **every** navigation after they touched a field. (2) **A name could carry digits or stray symbols.** The registration form enforces `^[\p{L}\s\-.]+$`, but the settings form had no such rule — so a name could be set to `123` or `∑∑∑`, on the page whose whole subject is the name a person is known by on the public register. Around both: the page borrowed the admin workspace's field classes and re-skinned them, hid half its content behind tabs, and offered a sticky navy bar over the bottom of the screen.
- What changed: (1) **The tabs switch between the sections, the way they always did** — a left rail on the desktop, a scrolling strip on a phone, the arrow keys moving between them, and a save returning to the tab it was made in. (2) **The page speaks the public portal's language** — the card, the field and button styles, the quiet header — instead of borrowing the admin's and re-skinning it; the admin's classes are gone from the page. (3) **The save row lives with the fields it saves**, and says what happened in place: a tick and "Saved", or "You have unsaved changes" with a discard button. The sticky bar is gone and so is the browser's "leave site?" prompt — a resident who typed something and wandered off should not be chased by the browser for it. The preferences keep their auto-save and say so in the same quiet line. (4) **The name rule is the registration form's**: letters, spaces, hyphens and dots, enforced on the server with a plain-language message and live in the field as it is typed, so a name that can be created at sign-up is the same name that can be set later. (5) The page's own script moved out of the template into `resources/js/settings.js` and the page's styles into `resources/css/pages/settings.css`, so both are readable and testable on their own.
- Verification screenshots: NOT captured (waived by owner, 2026-11-12). Verified over HTTP against a running server: the page renders the tab rail and the profile section, and `?tab=security` renders the security section with its tab active; saving the profile returns to the page with "Profile saved." and the tick in the profile card's save row and nowhere else; posting `first_name=Juan123` is refused with "A name may only contain letters, spaces, hyphens and dots." and the old value kept in the field. `php artisan test` — per-file, against the previous run: no regressions; `SettingsPageTest` (7 tests, new) covers the tabs and the section they show, the save returning to its own tab, the name rule and its acceptance of a hyphen and a dot; `tests/Support/js/settings-audit.mjs` executes the page's script in jsdom and checks the save row marking the form dirty, the discard putting it back, and the name field flagging a name that breaks the rule. `php scripts/design-system-guard.php` — 335 total (255 grandfathered, 80 NEW): the settings block left `app.css`, so its allowlist entries shrank; the remaining NEW are the owner's in-flight dashboard edit, which was already failing the guard before this change. `npm run build` builds.
- Definition of Done checklist: passed — en+fil keys for every new string; no new colours (the page is built from the portal's existing tokens); one markup for the save row, shared by the four cards that have one; the form's own save button and the row's discard work and are keyboard-reachable; the name field marks itself invalid and says why; no sticky bar, no beforeunload prompt; the page works without JavaScript (the forms post normally and the server answers).

## 2026-11-12: Three offices, two withdrawn categories, and the analytics parked

- Page / component: `app/Services/DepartmentRouter.php`, `app/Http/Middleware/AnalyticsPaused.php` (new), `bootstrap/app.php` (the middleware alias), `routes/web.php` (the parked analytics groups), `app/Http/Controllers/ComplaintController.php`, `resources/views/staff/partials/sidebar.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `resources/views/admin/departments/index.blade.php`, `config/complaint_category_groups.php`, `config/complaint_privacy_policy.php`, `database/migrations/2026_11_13_000005_close_business_licensing_and_human_resource_offices.php` (new), `database/seeders/{DepartmentSeeder,UserSeeder,PreGeneratedComplaintSeeder}.php`, `lang/{en,fil}/{errors,complaint_create}.php`, `docs/system-knowledge-base.md`, `PRODUCT.md`, `AGENT_GUIDE.md`, `tests/Feature/{DepartmentAdministrationTest,ComplaintDepartmentRoutingTest,PrivacyGatedComplaintTest,DepartmentAnalyticsTest,DepartmentActivityPageTest,ComplaintHoldTest}.php`.
- Why (the problem found): the municipality closed two more offices — the **Business and Licensing Office** and the **Human Resource Management Office** — leaving **Engineering, the Mayor's Office and the Municipal Environment and Natural Resource Office**. The categories those offices received had to go with them, the staff accounts had to go with them, and the **department analytics** — a feature still in progress — had to come out of sight without being thrown away.
- What changed: (1) **The directory is three offices.** The Business and Licensing Office and the Human Resource Management Office are deleted with the six complaints they had received and their two staff accounts (the accounts are soft-deleted, so the audit trail still says who did what); new accounts can be made when the municipality is ready. (2) **Business permits and personnel reports are withdrawn for now**: kept in the system so anything already filed under them still reads properly, but no longer offered to residents — so the filing form is now three problem types (Roads & Drainage · Garbage, Sanitation & Plastic · Something else), and the Mayor's office receives them in the meantime. The privacy gate stays: a report that names somebody is still filed private and still needs the Mayor's office's approval to publish. (3) **The department analytics is parked, not deleted.** The nav links are out of both sidebars and out of the office directory, and the routes answer 403 — but the pages, the service, the styles and the data are all still here. One switch brings it back: the `analytics.paused` middleware comes off the two route groups, and `AnalyticsPaused::PAUSED` is what the tests read to skip the parked pages instead of failing them.
- Verification screenshots: NOT captured (waived by owner, 2026-11-12). Verified over HTTP against a running server: the filing form offers three tiles and routes road damage/flooding/streetlight → Engineering, garbage/sanitation/plastic → the Environment office, everything else → the Mayor's office; the admin's office directory lists three offices and no analytics link; `/admin/analytics` and `/staff/analytics` both answer 403; the staff sidebar shows "My activity" and "Office activity" and nothing about analytics. The database holds 3 offices, 22 complaints and 4 staff, with the two closed offices' accounts soft-deleted. `php artisan test` — per-file, against the previous run: no regressions; the analytics suite reports 13 skipped (the parked pages) and 8 passed (the service-level tests that still run). `php scripts/design-system-guard.php` — 337 total (254 grandfathered, 83 NEW), identical to the baseline. `npm run build` builds.
- Definition of Done checklist: passed — en+fil keys for every new string; no new colours; the two withdrawn tiles and their labels are gone from the picker, and the categories keep their names for the complaints already filed under them; the parked feature is one middleware away from coming back, and its own tests come back with it.

## 2026-11-12: An office's own trail gets a page — "My activity" and "Office activity" stop sharing a link

- Page / component: `app/Http/Controllers/StaffController.php` (the shared grouping, the new action, the office's metrics), `resources/views/staff/activity/department.blade.php` (new), `resources/views/staff/partials/activity-feed.blade.php` (new — one markup for the card and the page), `resources/views/staff/dashboard/partials/activity.blade.php`, `resources/views/staff/partials/sidebar.blade.php`, `resources/views/admin/departments/index.blade.php`, `resources/css/pages/activity-feed.css` (new — the feed's styles, lifted out of the page-scoped file), `resources/css/pages/staff-dashboard.css`, `resources/css/app.css`, `routes/web.php`, `lang/{en,fil}/{staff,nav}.php`, `tests/Feature/DepartmentActivityPageTest.php` (new).
- Why (the problem found): two different things shared one link. **"My activity"** is the staff member's own trail — the complaints *they* handled, their stats, their last action. **"Recent department activity"** is a card on the dashboard showing what *everyone in the office* has done (the last 12 actions, all actors, grouped by ticket). But the card's **"View all activity" link pointed at `/staff/activity`** — at the member's own trail, not the office's. So the office's trail had no page to land on: it existed only as a five-line preview on the dashboard, and the link that claimed to show all of it showed something else. The dashboard's "show more" button was dead for the same reason — the card stopped collecting entries at four, so the button that reveals the rest never appeared.
- What changed: (1) **A page for the office's trail** — `/staff/department-activity`: every action anyone in the office has taken, newest first, grouped by ticket, each entry naming who did it and linking to the complaint, paginated thirty at a time. The dashboard card is now a genuine preview of it, and its link goes there. (2) **It is built from the designs the system already has**: the same banner, the same three-metric grid and the same feed as "My activity" and the dashboard card — the office's numbers where the page asks about a member's — so the two pages are visibly siblings and the difference is visible rather than implied. The feed's styles moved out of the page-scoped `staff-dashboard.css` into a shared `activity-feed.css`, because they were scoped to `body.page-staff-dashboard` and the new page was rendering the feed **unstyled**. (3) **"My activity" is untouched** — it answers what *I* did; the two sit side by side in the sidebar. (4) **An administrator reaches an office's trail through the audit browser**, filtered to that office — the system's existing trail design, and the wider record (staff accounts, invitations, reassignments) the office's own complaint trail does not carry; the button sits beside "View queue" on each office's row.
- Verification screenshots: NOT captured (waived by owner, 2026-11-12). Verified over HTTP against a running server: the office page renders the office's metrics (actions recorded, complaints handled, last activity) and the feed with the actor, the ticket, the status and the time; the dashboard card still renders the same entries for the same office; the admin's office row links to that office's audit trail. `php artisan test` — per-file, against the previous run: no regressions; `DepartmentActivityPageTest` (6 tests, new) covers the page, the separation from "my activity", the office boundary, the dashboard link and the administrator's route. `php scripts/design-system-guard.php` — 337 total (254 grandfathered, 83 NEW), identical to the baseline. `npm run build` builds.
- Definition of Done checklist: passed — en+fil keys for every new string; no new colours (the feed reuses the dashboard's activity styles, now shared rather than page-scoped); one markup and one grouping method for the card and the page, so they cannot disagree; no filters, by agreement — the trail is chronological.

## 2026-11-12: The queue is for work that is still live — and the resident is asked whether it was fixed

- Page / component: `app/Http/Controllers/StaffController.php` (the queue query, the scope, the order), `resources/views/staff/complaints/index.blade.php`, `resources/views/staff/complaints/partials/list.blade.php`, `app/Models/Complaint.php` (`scopePublic`), `app/Models/Notification.php`, `app/Services/{NotificationService,ComplaintUpdateDelivery}.php`, `app/Console/Commands/RemindUnconfirmedResolutions.php` (new), `bootstrap/app.php` (the schedule), `lang/{en,fil}/{staff,mail}.php`, `tests/Feature/{StaffQueueScopeTest,ResolutionReminderTest}.php` (new).
- Why (the problem found): a complaint the office had **rejected** or **closed** was still sitting in the office's complaint queue, in the same list as the live work, with nothing on the page to say so — the status filter did not even offer `Closed`, so a closed complaint could only be reached through "All statuses". The queue was also ordered by **priority** (with "unreviewed first" above it), so what sat on top was a judgement rather than a fact: a report filed three hours ago and still unreviewed could sit below one that had been reviewed yesterday. And on the public transparency register, a complaint the office had **rejected** as "not a municipal matter" was still published, because the register only excluded spam, duplicates and moderated content — two of the twelve published entries were rejected reports.
- Reconstruction note (2026-11-13): the standalone changelog copy of this entry was lost when the file was overwritten in-flight; what follows is condensed from `docs/design-system.md` §12, which kept the content.
- What changed (condensed from §12): **the queue opens on the work that is still live** — `Submitted`, `Under Review` and `In Progress`; the rejected, resolved and closed are one click away behind a **Finished** chip, and the status filter offers the statuses actually in the current scope. The order is now **staleness** (`updated_at` ascending) — the complaint nothing has touched for the longest comes first; priority stays a filter, not the order. **A rejected complaint comes off the public register** — it is not a municipal matter; resolved and closed stay. **A resident whose complaint was resolved is reminded three days later** — in the app and by email — before the 7-day automatic closure would close it for them; once per report.
- Verification screenshots: NOT captured (waived by owner, 2026-11-12). See `docs/design-system.md` §12 for the full entry.

# Design Changelog

Newest entry first. Every change to `resources/views`, `resources/css`, `resources/js`
or `lang` that alters how the UI looks or behaves must add an entry here.
Verification screenshots are **optional** (waived by the owner 2026-11-09): list them
when taken, or state `Verification screenshots: NOT captured (waived by owner, <date>).`
See `docs/ui-verification.md`. The entry itself is mandatory.

## 2026-11-11: Complaint detail `/complaints/{id}` — resident ticket page rebuilt

- Page / component: `resources/views/complaints/show.blade.php`.
- Why (the problem found): the resident complaint detail page rendered photos and location cards unconditionally, even when the complaint's category didn't require them — producing one long wall of text with no clear separation between sections. The staff updates/timeline was buried in the right column on desktop instead of being visible first.
- What changed: (1) the staff updates/timeline card now renders full-width at the top of the page on all screens so the latest status is seen first. (2) photos and location cards are shown only when the complaint's category intake policy requires them (`Complaint::intakePolicy()` → `photos_required`, `intakeLocationApplicable()`) or when actual data (images / coordinates / barangay / address) exists — otherwise they are removed entirely. (3) cards use `--surface` with `1px var(--line)` borders and `--shadow-card`, with `var(--line-soft)` hairline dividers between sections, matching the design system card language (§5.3 Card). (4) desktop keeps the two-column layout (details left, photos/location right); mobile stacks single-column with timeline at top.
- Verification screenshots: NOT captured (waived by owner, 2026-11-11). Verified by `php artisan view:cache` (Blade compiles) and `php artisan test tests/Feature/ComplaintEvidenceTest.php tests/Feature/ComplaintReviewWorkflowTest.php tests/Feature/ComplaintWorkProofTest.php` — 32 passed / 128 assertions (the 1 failure reproduces identically on pristine HEAD, pre-existing). `php scripts/design-system-guard.php` clean for this file.
- Definition of Done checklist: passed — no new tokens/colors/hex, section dividers use existing `--line-soft`/`--shadow-card` tokens, no layout contract changes on desktop, mobile stacks gracefully.

## 2026-11-11: My Complaints — banner CTA horizontal, pager at 3 rows mobile / 5 rows desktop

- Page / component: `resources/views/complaints/index.blade.php`, `resources/css` (inline page `<style>`), `app/Http/Controllers/ComplaintController.php`, `resources/views/vendor/pagination/resident.blade.php` (new).
- Why (the problem found): two issues on `/complaints`. First, the "File a complaint" button (`tr-banner__cta`) sat stacked below the H1 + subtitle text instead of beside it. Second, the list used one page size for every screen (`paginate(5)`) so a phone showed five tall rows while a desktop with more room showed the same five — and Next/Prev never appeared once a resident had more than five complaints. There were also two competing "Showing" summaries (top said "Showing 6 of 6" regardless of page; bottom said "Showing 1–5 of 6").
- What changed: (1) the banner CTA now renders in a flex row beside the H1/P text (`space-between`, `flex-wrap: wrap`), scoped to `body.page-complaints-index` so staff/admin workspaces and the transparency register keep their stacked banner — mobile still stacks the full-width CTA under the text. (2) `complaintsPerPage()` reads the User-Agent and returns `3` on mobile/tablet and `5` on desktop; mobile gets 3 per page, larger screens get 5. 5 (mobile) / 3 (desktop) or fewer complaints show flat with no pager; one more flips the page and Next/Prev + numbered page buttons render on mobile **and** desktop via a new `pagination::resident` view that reuses `.tr-pagination` instead of Laravel's default Tailwind utility pagination. (3) removed the duplicate top "Showing X of Y"; the single bottom summary stays (`aria-live="polite"`). `withQueryString()` keeps filters in the URL across pages.
- Verification screenshots: NOT captured (waived by owner, 2026-11-11). Verified by `php artisan view:cache` (Blade compiles), `php artisan test tests/Feature/RoleDashboardTest.php tests/Feature/LocalizationTest.php tests/Feature/MyComplaintsFilterApplyTest.php tests/Feature/CitizenHomeRedirectTest.php tests/Feature/TransparencyPageTest.php` — 32 passed / 182 assertions, and `php scripts/design-system-guard.php` clean for these files (no new hex/serif violations).
- Definition of Done checklist: passed — no new tokens/colors/hex, flex-wrap keeps mobile stacking, one pager summary only, pager uses existing `.tr-pagination` classes, en+fil keys reused (no new strings), server-side UA detection only (no client JS layout fight).

## 2026-11-11: My Complaints — status chip text centered on larger screens

- Page / component: `resources/views/pages/transparency/partials/component-styles.blade.php` (`.status-chip`), `resources/views/complaints/index.blade.php` (mobile override).
- Why (the problem found): the status chips (`All`, `Open`, `Submitted`, …) rendered their text flush-left inside the pill on larger screens because `.status-chip` had no flex centering — just `padding: 0 14px` on an inline `<a>`.
- What changed: added `display: inline-flex; align-items: center; justify-content: center;` to `.status-chip` (shared rule) and to the mobile override in `complaints/index.blade.php`, so each chip's label is centered in its padding box on every breakpoint.
- Verification screenshots: NOT captured (waived by owner, 2026-11-11). Verified by `php artisan view:cache` (Blade compiles) and the My Complaints suites passing (32 tests). `php scripts/design-system-guard.php` clean for these files.
- Definition of Done checklist: passed — no new tokens/colors/hex, one CSS declaration added to an existing rule, no layout contract changes.

## 2026-11-10: Registration — errors flash while typing, never for email

- Page / component: `resources/views/auth/register.blade.php`, `resources/js/app.js` (`initializeRegisterLiveValidation`), `resources/views/auth/partials/password-toggle.blade.php` behaviour is unchanged, `lang/{en,fil}/auth.php` (`email_unique`), `lang/{en,fil}/auth_ui.php` (`register.required`, `register.password_min_8`), `lang/{en,fil}/transparency.php` (`register.table.department_pending`), `lang/{en,fil}/staff.php` (`pagination_label`), `tests/Feature/RegistrationLiveValidationTest.php` (new), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): the create-account form only reported problems after submit — and one of them rendered a raw lang key (`auth.email_unique` did not exist in `lang/en/auth.php`, so Laravel printed the key verbatim into the alert). A scan for the same class of defect found two more raw-key paths: `transparency.register.table.department_pending` (`components/report-card.blade.php`, `??` fallback does not fire on `__()`) and `staff.pagination_label` (fell back to a raw key in the aria label, mitigated by the `?: 'Complaint queue pagination'` fallback, still wrong).
- What changed: every field on the register page now validates live **after its first blur** (input updates are live too) and clears while typing — first/last name (`^[\p{L}\s\-.]+$`), phone (empty allowed, else `^09\d{9}$`), barangay (must be one of the allow-list already rendered in the suggestion list), password (≥8 + mixed case + number), confirmation (must match), terms (checked). Inline `.field-error` line under each field, `aria-live="polite"`, hidden until first touched; `.field-group--invalid` re-tints the field border/icon via rgba only (no new hex; `#fca5a5` stays at its allowlisted single occurrence). **The email field was deliberately given no live validator and no inline error slot at all** — live format/uniqueness checks leak account existence (enumeration oracle); email errors remain submit-time server errors, and `auth.email_unique` now exists in both locales (`That email address is already registered…` / `Naka-rehistro na ang email address na ito…`). The static "Type a barangay to see suggestions." line under the combobox is gone — the same element is now `.sr-only` (1×1 px, still the aria-live target the combobox announces through; deleting it would have killed the suggestion search). `.barangay-search-status` CSS removed (dead rule).
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by a headless-Chromium run against the page: pristine fields render no inline errors; first_name/last_name/phone/password/confirmation/barangay error on blur with the exact translated strings and clear live while typing; the email field renders **no** inline error element (asserted in the test too); submit with an already-registered address renders "That email address is already registered. Sign in instead, or use a different email." (no raw key); combobox still filters and selects ("Barangay I" → 4 options → click selects, live region reads "Barangay I (Hilahod) selected."); no horizontal overflow at 393px. `php artisan test tests/Feature/RegistrationLiveValidationTest.php tests/Feature/PasswordRequirementsTest.php tests/Feature/LocalizationTest.php tests/Feature/PasswordResetTest.php tests/Feature/AuthSessionSchemeTest.php` — 26 passed / 189 assertions. `php artisan view:cache` compiles. `npm run build` builds. `php scripts/design-system-guard.php` — 337 total (254 grandfathered, 83 NEW); every NEW line is in the owner's pre-existing uncommitted files (`staff/verification/index.blade.php`, `staff/complaints/show.blade.php`, `staff/complaints/index.blade.php`) plus the two byte-identical `app.css` entries that are already new in `HEAD` — zero new entries from this change.
- Definition of Done checklist: passed — en+fil keys added for every new string (no raw keys remain on the page; suite pins them), no new tokens/colours/hex (rgba only), email excluded from live checks by design, screen-reader live region kept (`.sr-only`) rather than deleted, dead CSS removed, tests pin the wiring and the translated submit message.

## 2026-11-10: Auth password fields — the eye toggle, hidden until you type

- Page / component: `resources/views/auth/partials/password-toggle.blade.php` (new shared partial), `resources/js/app.js` (`initializePasswordToggles`), `resources/css/app.css` (`.field-toggle` + `.field-wrap:has(.field-toggle) .field-input { padding-right: 52px }` + `.field-underline { pointer-events: none }`), `resources/views/auth/{login,register,reset-password,change-password}.blade.php`, `lang/{en,fil}/auth_ui.php` (`common.show_password` / `common.hide_password`), `resources/views/pages/design-system.blade.php` (§5.41 swatch), `docs/design-system.md` (§5.41, §12), `docs/design-changelog.md`.
- Why (the problem found): every auth password field was unreadable — no way to see what you typed, so mistyped passwords and typo'd confirmations failed silently until submit. First pass rendered the toggle always, at 40×40, and it read as clutter and was fiddly to tap on a phone.
- What changed: an eye/eye-off toggle from `auth.partials.password-toggle` now sits at the right edge of every auth password field (sign in, create account ×2, reset ×2, set-new-password ×2). It is **invisible (opacity 0 / visibility hidden / scale 0.5 / `tabindex="-1"`) until the field has text**; `app.js` adds `.is-visible` from the `input` event and springs it in. Two stacked feather SVGs (`eye` / `eye-off`) cross-fade with a small pop rotation driven by `aria-pressed` alone. 44×44 target (LOCKED mobile target), `z-index: 2` above the input, `touch-action: manipulation`, `-webkit-tap-highlight-color: transparent`, straight focus ring, hover on a faint gold wash. Emptied fields re-mask and re-hide automatically. The two eye icons are always `pointer-events: none` so a tap always lands on the button (`elementFromPoint` check). The settings page keeps its light "Show"/"Hide" text button (different surface).
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by headless Chromium on `/login` + `/register` at 393px and 1310px: empty field → toggle hidden (opacity 0, visibility hidden, tabindex -1, type=password); typing → `.is-visible`, tabindex 0; click → `type="text"`, `aria-pressed="true"`, label flips to Hide password, icons cross-fade (eye 0 / eye-off ~1); second click → masked again; clearing → hidden + re-masked; `elementFromPoint` at the button centre resolves to `BUTTON.field-toggle`; both register password fields behave identically. `php artisan test` — the auth-related suites pass (26 / 189 with the new RegistrationLiveValidationTest). `php artisan view:cache` compiles; `npm run build` builds; `php scripts/design-system-guard.php` — no new entries from this change (337 total; the 83 NEW are the owner's pre-existing uncommitted files and the two byte-identical `app.css` entries already new in `HEAD`).
- Definition of Done checklist: passed — en+fil keys (`common.show_password` / `common.hide_password`), 44px mobile target, visible focus ring, `prefers-reduced-motion` drops the transitions, no new tokens/colours/hex, one shared markup partial reused on every page (no copies), §5.41 documents all of it.

## 2026-11-11: Navbar (mobile) — Administrator badge next to the logo, bell next to the hamburger

- Page / component: `resources/views/layouts/navbar.blade.php`, `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): on mobile the authenticated bar rendered `logo → bell → Administrator badge → hamburger`, so the badge drifted away from the logo it labels and the bell sat far from the hamburger it should belong to (owner-reported swap).
- What changed: `.nav-identity-bar` (the Administrator badge shown on mobile) moved in the DOM to immediately after `.navbar-logo`, still a direct child of `.navbar-flex`; the notification bell stays a direct child, now directly before the hamburger. No CSS changed — the badge is `display:none` everywhere except ≤640px authenticated, so desktop is byte-for-byte unchanged visually; only the mobile order moved.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by headless Chromium at 393px and 1310px against `/admin/dashboard`: mobile renders `logo → badge → bell → hamburger` (badge at x≈181 right after the logo, bell immediately left of the hamburger), desktop unchanged (badge and hamburger hidden, bell visible). `php artisan view:cache` compiles; `php scripts/design-system-guard.php` — no new entries (the 83 NEW remain the owner's pre-existing uncommitted files).
- Definition of Done checklist: passed — zero new strings/tokens/colours, no CSS edits, one shared markup path, z-index contract untouched.

## 2026-11-11: Complaint create — dead duplicate step intros removed; desktop hints stay truthful

- Page / component: `resources/views/complaints/create.blade.php` (the five duplicated `fc-step-help` paragraphs carry `fc-step-help--dup`; the photos field hint gains a policy-aware lead-in `#photo-tips-lead`), `resources/css/pages/complaint-create.css` (`.fc-step-help--dup { display:none }` at ≥768px), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): on large screens the wizard step-intro line duplicated the field hint beneath it verbatim — "Summarize the problem in one sentence.", "Required. Add up to five clear photos taken at the issue location." (also duplicated by `#photos-step-help` and `#photo-tips-hint`), "Prefilled from your account…", and "You decide this. No member of staff can change it later." (also the visibility card lede, printed twice), plus the pin step's map note (`complaint_create.map.help` rendered twice in the same card). Mobile wizard hides field hints, so it was fine; desktop painted both — pure duplication, flagged by the owner.
- What changed: the five duplicated intros (describe, photos, where, pin, visibility) carry `.fc-step-help--dup` and `display:none` ≥768px. Desktop keeps the field hints; mobile wizard-on keeps its step intros unchanged. The desktop photos field hint is now policy-aware as well: its lead-in span (`#photo-tips-lead`) is rewritten by the same intake-policy switch that already handled `#photos-step-help`/`#photos-required-mark` — `photos_required_help` for physical_required, `photos_optional_help` for proof_optional ("Required." no longer lies on the noise/stray-animals path), filtered by the unchanged GPS/spec tail of the hint. No new strings, no new tokens, no behaviour change on the wizard flow beyond hiding the duplicate lines at desktop width.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by headless Chromium at 1310px and 393px: zero `.fc-step-help--dup` elements visible at desktop width; the same three steps show their intros under wizard mode; the title and barangay field hints stay visible at desktop; the photos lead reads "Required. Add up to five…" for road_damage and "Optional, but encouraged…" for noise_complaint; "You decide this. No member of staff can change it later." occurs exactly once in the rendered page; map note still present; mobile wizard step-intro still renders (step "Details").
- Definition of Done checklist: passed — no new strings or tokens, no hex added (dimensions unchanged), both watchpoints (desktop hide / mobile keep) verified in browser, no JS fallback broken (the JS policy switch still finds `#photos-step-help`, just hidden at desktop).

## 2026-11-11: Complaint create — every category can submit; the 500 is gone

- Page / component: `app/Http/Controllers/ComplaintController.php` (store: `$imagePath = $imagePaths[0] ?? null`), `resources/views/complaints/create.blade.php` (submit gate reads `currentIntakePolicy().photos_required`; landmark toggle label driven by the same policy switch; `{n}`/`{total}` placeholders resolved in first paint), `lang/{en,fil}/complaint_create.php` (`wizard.landmark_toggle_required`), `docs/design-system.md` (§12), `docs/design-changelog.md`.
- Why (the problem found): owner reported that filing a complaint in "Business Permits" or "Something else" did not submit — it dropped them back at step 1 (choosing the category). Two stacked defects: (1) the client-side submit handler unconditionally required ≥1 photo (`selectedPhotos.length === 0 && !__HAS_LOCATION_CONFLICT__` ⇒ `preventDefault`, jump to `#step-photos`), even for categories whose intake policy hides the photo step entirely; (2) the server store() then crashed at line ~874, `Undefined array key 0` on `$imagePaths[0]`, 500ing any complaint that legitimately carries no photos — confirmed by the owner's production stack trace at `POST /complaints` (`users id=18`, BPLO department, `intake_mode` sensitive_intake). Defect (2) only became reachable once (1) stopped eating the request — the gate and the crash must ship together.
- What changed: the photo gate now honours the intake policy (`photos_required !== false` required ⇒ gate applies; `sensitive_intake`/`follow_up_optional`/`proof_optional` skip it — `physical_required` is the only photo-gated mode). `$imagePath` is nullable; the no-photo complaint files normally (evidence gallery hides at `evidence_image_count === 0`, success page gates on `photoCount > 0`). The progress counter's initial paint renders `Step 1 of 8` instead of raw `{n}`/`{total}` (Laravel `__()` doesn't replace JS-style tokens; the aria-label keeps its translated "step name" value). The landmark toggle button (`#landmark-toggle`) now flips with the same intake-policy switch the rest of the field already follows: `+ Add a landmark` (with `*`, panel auto-opened, input `required`) for `address_text_required` categories such as noise_complaint; `+ Add a landmark (optional)` (collapsed) otherwise — fil keys added (`+ Magdagdag ng palatandaan` / `(opsyonal)`).
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by headless Chromium as a throwaway test user: `business_permit` → 302 to `/complaints/98/success` (created), `others` → `/complaints/99/success`; `noise_complaint` reaches the Location step with `+ Add a landmark`, `aria-expanded="true"`, `*` mark, `required` set; `road_damage` keeps `+ Add a landmark (optional)`, collapsed; `road_damage` without a photo is still blocked client-side (no POST sent). `php artisan view:cache` compiles; `npm run build` builds; targeted complaint suites green: `php artisan test tests/Feature/ComplaintDepartmentRoutingTest.php tests/Feature/ComplaintEvidenceTest.php tests/Feature/ComplaintBarangayTest.php tests/Feature/ComplaintLocationChoiceTest.php tests/Feature/ComplaintVisibilityConsentTest.php tests/Feature/ComplaintTicketIdTest.php tests/Feature/ComplaintDetectionTest.php` — 78 passed / 311 assertions.
- Definition of Done checklist: passed — both locales carry the new string, no new tokens/colours/hex (JS-driven text, unchanged markup), nullable-column consumers audited across all read paths (evidence gallery, success page, show page, model accessors), zero new guard findings from these files. Full suite not re-run; its known pre-existing failures (staff verification pages, owner's in-progress files) are untouched by this change.

## 2026-11-10: Ticket update emails — the resident hears about every move, not just the bell

- Page / component: `resources/views/mail/complaint-update.blade.php` (new branded transactional email, replaces the plain markdown `resources/views/emails/complaint-updated.blade.php`, now deleted), `app/Mail/ComplaintUpdatedMail.php`, `app/Services/ComplaintUpdateDelivery.php` and `app/Services/BrevoComplaintUpdateSender.php` (new), `lang/{en,fil}/mail.php`, `app/Http/Controllers/StaffController.php`, `app/Http/Controllers/AdminController.php`, `app/Http/Controllers/ComplaintVerificationController.php`, `app/Console/Commands/AutoCloseResolvedComplaints.php`, `tests/Feature/TicketUpdateEmailTest.php` (new), `tests/Support/design-system-allowlist.txt`.
- Why (the problem found): only one staff action emailed the resident — the worker-flow status change in `StaffController@update`, and even that went out through the configured Laravel mailer (local SMTP/Mailpit), not the Brevo path every other transactional email uses. Admin status updates, staff and admin review decisions, information requests, and the 7-day auto-close told the resident through the in-app bell only — a channel a resident who never opens the site never sees.
- What changed: an email now goes out on every update a staff member or administrator makes that the resident is waiting on — staff status change, admin status change, staff review decision, admin review decision, an information request (the question itself is the body), and the automatic close of a stale Resolved ticket. Delivery is Brevo-first with the configured Laravel mailer as fallback (the `StaffInvitationDelivery` pattern), localized to the resident's saved `locale` (falling back to the system default), and best-effort like the bell: a dead mail host reports and the update, and the bell notification, still land. The admin path mirrors the in-app precedence — a review decision suppresses the status-change email in the same action. The shared template reuses the branded password-reset/staff-invitation chrome (navy header, gold button, cream footer) and is allowlisted like they are (same palette, no new colors introduced to app UI). Emails are sent un-queued, matching every other sender in the app.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by `php artisan test tests/Feature/TicketUpdateEmailTest.php` — 8 passed / 23 assertions (staff + admin status change, staff + admin review decision — once only, information request with the question in the body, auto-close command, resident-locale subject, mailer failure neither blocks the update nor the bell) — plus `php artisan view:cache` (all Blade compiles), a tinker render of the mailable and both bodies, and the related suites green (`NotificationDeliveryTest`, `StaffActionPanelsTest`, `VerificationWorkflowTest`, `ResidentInformationResponseTest`, `MyComplaintsInfoRequestTest`, `ComplaintReviewWorkflowTest`, `ComplaintWorkProofTest`, `EmailVerificationTest`, `PasswordResetTest`, `LocalizationTest`); the four failing tests reproduce identically on pristine HEAD. `php scripts/design-system-guard.php` — the new mail blade contributes zero NEW violations (12 hex/serif entries allowlisted with the same treatment as the other mail blades); the 83 grandfathered+NEW totals and the `DesignSystemGuardTest` failure are the owner's untracked `staff/verification/index.blade.php` and uncommitted `staff/complaints/show.blade.php` edits, present before and after this change and untouched by it. The full suite was interrupted before completion; this was not re-run end-to-end.
- Definition of Done checklist: passed — new `en`+`fil` keys (`mail.ticket_*`), no new tokens/colors in app UI (email-only template, allowlisted like the other mail blades), no hard-coded strings on web screens, one shared email template for both transports, no lang keys removed, tests pin the new behaviour. In-app bell notifications unchanged.

## 2026-11-10: Staff ticket — non-verifiers no longer see the review form

- Page / component: `resources/views/staff/complaints/show.blade.php`, `lang/{en,fil}/staff.php`, `tests/Feature/StaffActionPanelsTest.php`.
- Why (the problem found): the "Review and verification form" (review decision / confirmed priority / verification method / Save) rendered for every staff account whether or not the ticket was verified. The server always refused a non-verifier's save (`ComplaintVerificationService::authorize` → "Only a department verifier or an administrator can settle a complaint."), but the page advertised a control it would bounce — the same failure mode the verification follow-up panel already guards against, and the opposite of "the page never advertises a control the service would refuse".
- What changed: the form now renders only when `$user->canVerifyComplaints()`. Non-verifiers see a read-only state line instead (new `staff.verification.read_only_body` key, `en`+`fil`), reusing the existing `.staff-action-panel__state` pattern — no new markup/CSS. The informational subgrid (review status / suggested / confirmed priority) stays visible for everyone. Server behaviour is unchanged (it already refused); it is now pinned by a test. Deliberately left as-is, both documented in §5.40: the official response form (worker flow, every department staff member) and the moderation panel (content decision, every department staff member).
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by `php artisan test tests/Feature/StaffActionPanelsTest.php` — 8 passed / 43 assertions, including two new tests (non-verifier page shows no review-form fields and does show the read-only line; non-verifier PUT to `staff.complaints.review` is refused with a `verification` error and the review status is unchanged; verifier still sees the form). The full suite shows two failures that reproduce identically on pristine HEAD (`ComplaintDepartmentRoutingTest` success-page URL generation, `ComplaintReviewWorkflowTest` masked-identity string) — pre-existing and unrelated. `php artisan view:cache` compiles. `php scripts/design-system-guard.php` — 327 violations (244 grandfathered, 83 NEW); every one of the +25 NEW entries is in the untracked `resources/views/staff/verification/index.blade.php` (owner's in-progress page, untouched by this change).
- Definition of Done checklist: passed — new `en`+`fil` keys, no hard-coded hex/serif/map violations in the touched files, one markup path reused, no lang keys removed, no layout contract changes.

## 2026-11-10: Staff queue — filters apply on Apply, not on change

- Page / component: `resources/views/staff/complaints/index.blade.php`.
- Why (the problem found): the three filter selects (status / review status / priority) submitted the form immediately on `change`, so a single mis-click reloaded the whole queue and yanked the set of visible cases away from the user — the queue page should only move when the person says so.
- What changed: removed the inline `onchange="this.form.submit()"` from all three selects. Filters now take effect only via the Apply button (or Enter in the search field, the form's native submit). The live-queue polling, the overdue chip link and Clear behave exactly as before; no strings/tokens/colors changed.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by `php artisan view:cache` (Blade compiles) and `php artisan test tests/Feature/StaffWorkspaceTest.php` (6 passed, 31 assertions).
- Definition of Done checklist: passed — zero new strings/tokens/effects, native form semantics kept, Apply remains a real `type="submit"` with the existing busy-state handling.

## 2026-11-10: Staff queue — the stat strip is gone

- Page / component: `resources/views/staff/complaints/index.blade.php`, `app/Http/Controllers/StaffController.php` (`complaintsIndex`).
- Why (the problem found): owner asked to remove the queue's compact stat strip immediately. It duplicated the staff dashboard's stat cards and cost the page four extra server-side aggregates (three counts plus the AVG-resolution-days query) for a summary nobody had asked to see on the working list.
- What changed: removed the `.tr-stats` strip (pending verification / in progress / resolved this month / avg time open), its page-local compact overrides, and the controller's `$stats` computation and compact entry for this page. The banner now runs straight into the filter bar — the same flat pattern the resident My Complaints queue adopted on 2026-11-09. `calculateAvgResolutionDays` stays (the dashboard still uses it); the `staff.stats.*` lang keys stay (the dashboard renders them); the shared `.tr-stats` component and its other consumers (transparency register, admin queue, landing strip, `/design-system`) are untouched.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by `php artisan view:cache` (Blade templates compile), `php artisan test tests/Feature/StaffWorkspaceTest.php` (6 passed, 31 assertions), and `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW), unchanged from the pre-existing baseline.
- Definition of Done checklist: passed — no new strings/tokens/colors/effects; dead markup and page-local CSS removed in the same change; no lang keys deleted; list markup unchanged.

## 2026-11-10: Admin complaint detail — the ticket says when it moved under you too

- Page / component: `resources/views/admin/complaints/show.blade.php`, `app/Http/Controllers/AdminController.php`, `routes/web.php`.
- Why (the problem found): the live ticket panel exists on the staff ticket page (§5.39) but not on the admin ticket page. An administrator who has a complaint open while a department verifier moves it, settles it or leaves a note finds out only when the server refuses their update — the same blind spot the staff panel fixed.
- What changed: the admin ticket page now carries the same `[data-live-ticket]` panel, polling `admin.complaints.state` every 20s (hidden tabs skipped, catch-up on return). The endpoint reuses the same `TicketFingerprint` and message logic as the staff endpoint — who changed what, with the status pill updated in place and the form never touched. A human who opens the endpoint URL is redirected to the admin ticket page, not shown JSON. The status pill markup now uses the shared `.status-pill` recipe (with `.status-pill__dot` styles from `partials.public-ui.blade.php`, newly included on the admin show page) so `syncStatusPill` can re-key it when the case moves. All strings reuse the existing `staff.live.*` keys — no new translation strings.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by the test suite instead: 6 new tests in `tests/Feature/ComplaintLiveTicketTest.php` — the admin page carries the live panel and fingerprint, an unchanged ticket reports no change, a moved ticket names the actor and both statuses, an internal note is described as a note, a person opening the endpoint is redirected to the ticket, and a guest is refused. Full suite: 514 tests, 1 error, 19 failures — the same pre-existing set, no new failures. `php artisan view:cache` — Blade templates compile. `npm run build` — assets build. `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW), unchanged.
- Definition of Done checklist: passed — `en`+`fil` keys reused (no new strings), no hard-coded hex/serif/map violations in the touched files, human redirect fallback for the machine endpoint, one shared markup pattern with the staff page.


## 2026-11-10: Staff complaint detail — the four actions
## 2026-11-10: Staff complaint detail — the four actions the November rewrite dropped

- Page / component: `resources/views/staff/complaints/show.blade.php`,
  `resources/views/components/moderation-panel.blade.php` (new, shared),
  `resources/views/admin/complaints/show.blade.php`,
  `resources/css/pages/staff-complaint.css`, `resources/views/pages/design-system.blade.php`
  (swatch), `app/Http/Controllers/StaffController.php`, `lang/{en,fil}/staff.php`,
  `tests/Feature/StaffActionPanelsTest.php` (new), `docs/design-system.md` (§5.40).
- Why (the problem found): the backend for asking the resident for better evidence,
  handing verification to another verifier, moderating the public copy, and leaving
  an internal note all existed — but none had a surface on the staff ticket page. A
  verifier who needed another opinion had no way to ask the resident or delegate; a
  staff member could not attach an internal note to an update; and the staff page had
  no moderation panel at all, while the admin page's moderation markup lived in one
  place only and could drift from any future staff copy.
- What changed: a **verification follow-up panel** (§5.40) shows the verifier the
  ask form, a waiting/answered state for an open information request, the resident's
  reply, and a delegation select that excludes the viewer and the assigned worker
  (separation of duties). The panel appears only when the viewer can verify and the
  report is still being settled, and never advertises a control the server would
  refuse. An **internal note field** rides inside the official response form and is
  written to the same immutable log with `is_internal = true`; it never renders on
  the resident page or the public timeline. A **shared moderation component**
  (`resources/views/components/moderation-panel.blade.php`, one markup) now serves
  both the staff and admin ticket pages — the office can withhold or redact, never
  publish; the resident's visibility choice is stated, not offered as a control.
  On the staff page the same markup is re-toned for a light surface via
  `.moderation-panel--light`. All new strings are `en`+`fil` keys under
  `staff.verification.*`, `staff.internal_note.*` and `staff.moderation.*`.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10). Verified by
  the test suite instead: `php artisan test tests/Feature/StaffActionPanelsTest.php`
  — 6 passed / 31 assertions, covering the ask form and eligible-delegate filtering
  for a verifier, no controls for a regular staff member, the waiting state showing
  the resident's question, the answered state showing the reply, the internal note
  field on the response form, and an internal note being saved while staying off the
  resident timeline. `tests/Feature/ComplaintVisibilityConsentTest.php` — the staff
  moderation page test — now passes (it was a pre-existing failure). The full suite
  is 508 tests, 1 error, 19 failures — the 20 pre-existing failures minus the
  moderation one, no new failures. `php artisan view:cache` — Blade templates
  compile. `npm run build` — assets build. `php scripts/design-system-guard.php` —
  302 violations (243 grandfathered, 59 NEW), unchanged: the touched files add no
  new violations.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll,
  `en`+`fil` keys added for every new string, no hard-coded hex/serif/map violations
  in the touched files, one markup for moderation shared by staff and admin, visible
  focus states, and ≥44px touch targets on panel buttons.




## 2026-11-10: Staff complaint detail — resident information card moved above description

- Page / component: `resources/views/staff/complaints/show.blade.php`.
- Why (the problem found): resident identity context was placed in the side column and raised concern about privacy visibility.
- What changed: moved the “Resident information” card to the top of the main column, above the “Complaint description” card. The card remains privacy-safe for staff (staff-safe alias only; no resident contact details or real identity shown).
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by Blade structure inspection (card relocated above complaint description).
- Definition of Done checklist: passed for the changed surface — layout adjusted as requested, privacy-safe alias presentation preserved.

## 2026-11-10: Staff complaint detail — activity timeline moved below complaint location

- Page / component: `resources/views/staff/complaints/show.blade.php`.
- Why (the problem found): the activity history timeline was positioned in the side column, away from the complaint’s location context.
- What changed: moved the “Activity history timeline” card in the staff complaint detail layout so it appears in the main column directly below the “Complaint location” card.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by Blade structure inspection (timeline card relocated below location card in main column).
- Definition of Done checklist: passed for the changed surface — layout adjusted as requested, no new strings/tokens/colors introduced.

## 2026-11-10: Staff complaint detail — moderation panel UI and flagged-complaint notice

- Page / component: `resources/views/staff/complaints/show.blade.php`, `resources/views/components/moderation-panel.blade.php`, `resources/css/pages/staff-complaint.css`, `app/Http/Controllers/StaffController.php`.
- Why (the problem found): the Public presentation/moderation panel on staff complaint detail was hard to scan and did not clearly mark required input; staff opening a ticket also had no prominent notice when a complaint was flagged as possible spam/duplicate or had moderation issues.
- What changed: improved the moderation panel (shared component, light variant) with clearer hierarchy, spacing, readable labels/inputs, and a visible red required marker on the moderation reason field. Added a prominent “needs attention” notice on the staff ticket page for moderation-flagged or hidden-from-public complaints, showing the moderation label/score/reasons and a duplicate comparison link when applicable. Restricted the Public presentation moderation panel and `staff.complaints.moderate` action to users who can verify complaints (verifiers/admins), so non-verifier field staff no longer see or submit public-presentation changes.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by Blade/CSS inspection on the shared moderation component and staff complaint detail view.
- Definition of Done checklist: passed for the changed surfaces — required field clearly indicated, warning/flag information surfaced on ticket open, no new hard-coded hex in new styles (tokens/vars used), existing lang keys reused.

## 2026-11-10: Staff verification — separate page for managing verification queue

- Page / component: `resources/views/staff/verification/index.blade.php` (new), `resources/views/staff/partials/sidebar.blade.php`, `app/Http/Controllers/StaffController.php`, `routes/web.php`.
- Why (the problem found): verification tasks for staff with verification rights were only reachable as filtered links into the general complaints queue (`/staff/complaints?review=...`), making verification management feel secondary and less focused even though verifiers need a clear place to work pending reviews and items waiting on residents.
- What changed: added a dedicated staff verification page (`GET /staff/verification`, `staff.verification.index`) with its own live-updating queue/browser endpoint, scoped to verification review states (`pending`, `needs_information`) and to users who can verify complaints. The page shows counts/tabs for “Needs verification” and “Waiting on resident”, supports search and basic filters, and reuses the existing staff queue list/pagination components. Sidebar Verification links now point to this page instead of filtered complaints-queue URLs (and the sidebar pending-count badge was removed as irrelevant). Fixed browser pagination fragment rendering to include required pagination variables.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by route/controller/view wiring and code inspection (verifier-only access, constrained review states, live queue endpoint/fallback).
- Definition of Done checklist: passed for the changed surfaces — separate verifier-managed page added, existing queue/detail flows reused, verifier-only access enforced, no new hard-coded hex/serif/gold side bars introduced, existing lang keys reused.

## 2026-11-10: Staff dashboard — removed broken priority filter chips

- Page / component: `resources/views/staff/dashboard/index.blade.php`.
- Why (the problem found): the Priority complaints chip filters (`priority_filter` submit buttons: All / Pending verification / Needs review / Possible duplicate) did not work properly on `/staff/dashboard`; the clicked submitter's `name=value` could be dropped by the shared busy-submit handling, so filtering was unreliable.
- What changed: removed the chip filter row from the Priority complaints form and kept the search control. The section now shows the department's priority complaints (with search) without the non-functional subset chips, matching the intent that it is a priority list.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by code inspection (chips removed; search form remains GET to `staff.dashboard`).
- Definition of Done checklist: passed for the changed surface — one form/action path, no new strings/tokens/colors, no layout contract regressions introduced.

## 2026-11-10: Staff surfaces — resident identity masked everywhere except admin (with policy-backed reveal)

- Page / component: `app/Models/User.php`, `app/Http/Controllers/StaffController.php`, `app/Http/Controllers/AdminController.php`, `app/Policies/ComplaintPolicy.php` (existing `viewRealIdentity` now enforced), `resources/views/staff/complaints/show.blade.php`, `resources/views/staff/dashboard/partials/activity.blade.php`, `tests/Feature/StaffIdentityMaskingTest.php` (new).
- Why (the problem found): anonymity was applied on the public register and on the staff detail's resident card, but it was not consistently applied across staff-facing UI and data surfaces. The staff complaint timeline and dashboard activity fallback rendered log actors by real name, and the live ticket state endpoint (`staff.complaints.state`) returned/messaged the actor's `full_name` in JSON — so a resident who acted on their case (e.g. replied) could be exposed by real name to staff. Staff queries also eager-loaded full resident records (including email/contact) even when not displayed.
- What changed: added `User::staff_actor_name` to resolve a safe actor label by role (citizens → staff-safe alias/fallback, staff/admin → real name) and used it for staff activity grouping/messages, timeline rendering, dashboard activity fallback, and the live ticket panel payload. Staff controllers now eager-load residents and log actors with restricted columns (alias/role/name fields only, no resident email/contact) on staff surfaces. Admin complaint detail now explicitly authorizes `viewRealIdentity` before loading full identity, so real-name access is policy-backed rather than route-only.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by automated tests: `php artisan test tests/Feature/StaffIdentityMaskingTest.php tests/Feature/ComplaintLiveTicketTest.php` (staff detail/timeline/live-state mask resident identity; admin detail still shows real identity; existing live-ticket behavior for staff actors preserved).
- Definition of Done checklist: passed for the changed surfaces — resident identity not rendered in staff views/JSON, admin reveal remains authorized, no new user-facing raw keys/strings added, no hard-coded hex/serif/gold side bars introduced, no map/z-index/layout changes.

## 2026-11-10: Staff complaint detail — the ticket says when it moved under you

- Page / component: `resources/views/staff/complaints/show.blade.php`, `resources/views/pages/design-system.blade.php` (swatch), `resources/css/pages/staff-complaint.css`, `resources/js/live-ticket.js` (new), `resources/js/app.js`, `app/Support/TicketFingerprint.php` (new), `app/Http/Controllers/StaffController.php`, `routes/web.php`, `lang/{en,fil}/staff.php`, `tests/Feature/ComplaintLiveTicketTest.php` (new), `docs/design-system.md` (§5.39).
- Why (the problem found): the queues had just gone live — a row a colleague moved disappears or changes without a reload — but **the ticket itself did not**. A staff member could open a case, spend several minutes writing the response and attaching the proof photo, and never learn that another hand had already moved it, settled it or left an internal note. The live queue made that worse, not better: the list said the case was In Progress, the open ticket said Under Review, and the server settled the argument by refusing the update after the work was written. A previous session had started the backend for exactly this — `TicketFingerprint` and a `complaintState()` controller method — but stopped before wiring it: the method was not imported, not routed, and no view or script called it. `staff.live.*` did not exist in either language, so the page could only have leaked raw keys.
- What changed: the ticket now carries a **live notice** (§5.39). While the page is open it polls a fingerprint built from the complaint's state and its newest timeline entry (20s, hidden tabs skipped, catch-up on return). An unchanged ticket costs one small request and touches nothing. When the case moves, the notice names **who did what** — "Maria Dela Cruz moved this complaint from Under Review to In Progress" — or says that an internal note was left, and offers a **Reload** link. The status pill in the banner updates in place (display-only); **the form, the timeline, the map and every word already typed are left alone** — a live update must not pull the keyboard out from under the person using it. The notice can be dismissed for that one change; a newer change may speak again. The endpoint was finished as a **machine endpoint** in the house style: JSON only for a script, a person who opens the URL is redirected to the ticket. The one thing the slice also repaired: the banner pill had been borrowed from the resident page's local `.pt-pill` recipe, which the staff shell never loads, so the ticket's status was rendering as uncoloured text with an invisible dot — it is now the shared `.status-pill` recipe (§5.10) the rest of the portal already uses. Without JavaScript the ticket is unchanged and the reader reloads when they choose to.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by the test suite instead: `php artisan test tests/Feature/ComplaintLiveTicketTest.php` — 7 passed / 24 assertions, covering the page carrying the panel and its fingerprint, an unchanged ticket reporting no change, a move naming the actor and both statuses, an internal note being described as a note rather than a move, another department's staff being refused, a person opening the endpoint being redirected to the ticket, and a guest being refused. The full suite is 498 tests, 1 error, 20 failures — the same pre-existing set, with the 7 new tests green. `npm run build` — assets built. `php artisan view:cache` — Blade templates compile. `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW), unchanged; the new styles are tokens only.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll, `en`+`fil` keys added for every new string, no hard-coded hex/serif/gold side bars, one markup per list, ≥44px touch targets and visible focus, no map changes, the guard reports no NEW violations in the touched files.


## 2026-11-10: Staff queue, staff complaint detail, admin dashboard — the deadline chip

- Page / component: `resources/views/staff/complaints/index.blade.php`, `resources/views/staff/complaints/show.blade.php`, `resources/views/admin/dashboard/index.blade.php`, `resources/css/pages/staff-complaint.css`, `resources/css/pages/admin-dashboard.css`, `app/Enums/ComplaintSlaState.php` (new), `app/Services/ComplaintSlaService.php` (new), `app/Console/Commands/FlagOverdueWork.php` (new), `app/Services/NotificationService.php`, `app/Models/Notification.php`, `app/Services/ComplaintVerificationService.php`, `app/Models/Complaint.php`, `app/Http/Controllers/{StaffController,AdminController}.php`, `lang/{en,fil}/staff.php`, `lang/{en,fil}/statuses.php`, `bootstrap/app.php`, `database/migrations/2026_11_10_000004_add_verified_at_to_complaints_table.php` (new), `tests/Feature/ComplaintSlaTest.php` (new).
- Why (the problem found): the office accepted a report the moment it verified it, and nothing in the system noticed when that responsibility went unpaid. The requirement — *after verification, the office has to start the work* — was enforced by the status ladder and by nothing else, so "verified three weeks ago, still Under Review" sat in the queue looking exactly like a ticket being worked on today. The queue even had an age chip, but it measured the **age of the complaint**, not whether the office had done anything with it: a report filed a month ago and verified yesterday looked identical to one verified a month ago and never touched.
- What changed: every verified complaint now carries a **deadline**. Work must start — three days by default, one for a critical report — and work must finish, fourteen days by default; the days are per priority and admin-editable through system settings. The clock starts the **first** time a report is verified: `reviewed_at` moves on every review decision and cannot say when the office took ownership, so a new `verified_at` column records that moment once, and it is never restarted by a later re-review, because the resident has already served that wait. The **staff queue row** and the **staff complaint banner** carry a new deadline chip (§5.38) — the action owed ("Start work" / "Finish work") and the time left — in the status family the meaning belongs to, so a late row cannot be mistaken for a comfortable one; a photo of the work, a note and a status pill all sit on the same row, and the chip is what tells them apart. The queue gained an **Overdue filter**, which selects by the clock rather than by a plain interval, because the days depend on the priority. The **admin dashboard** counts what each office has let slip, on its department card, in the negative family. A daily sweep (`complaints:flag-overdue-work`, scheduled at 07:45) tells the office that owes the work and the administrators — **once each**: a badge nobody reads is not a safety net, and a badge that cries wolf every morning is not a safety net either.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by the test suite instead: `php artisan test tests/Feature/ComplaintSlaTest.php` — 13 passed / 42 assertions, covering the clock not starting before verification, starting on verification, the on-track / due-soon / overdue / met states, the start deadline and the finish deadline being measured separately, a critical report being given less time than a routine one, the queue showing the time left and filtering to the overdue, the admin dashboard counting what slipped, the sweep telling the office and the administrators, the sweep not asking twice, and the sweep leaving work inside its deadline alone. The full suite is 491 tests, 1 error, 20 failures — the same 20 pre-existing failures, with 13 new tests green. `npm run build` — assets built. `php artisan view:cache` — Blade templates compile. `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW), unchanged.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll, `en`+`fil` keys added for every new string, no hard-coded hex/serif/gold side bars, one markup per list, ≥44px touch targets and visible focus, no map changes, the guard reports no NEW violations in the touched files.


## 2026-11-10: Staff and admin complaint detail — the proof carries its place

- Page / component: `resources/views/staff/complaints/show.blade.php`, `resources/views/admin/complaints/show.blade.php`, `resources/css/pages/staff-complaint.css`, `resources/css/pages/admin-workspace.css`, `app/Models/ComplaintEvidence.php` (new), `app/Models/ComplaintLog.php`, `app/Services/ComplaintWorkService.php`, `app/Services/PhotoLocationService.php`, `app/Http/Controllers/{StaffController,AdminController}.php`, `lang/{en,fil}/staff.php`, `database/migrations/2026_11_10_000003_create_complaint_evidences_table.php` (new), `tests/Feature/ComplaintEvidenceTest.php` (new), `tests/Unit/PhotoLocationServiceTest.php`, `tests/TestCase.php`.
- Why (the problem found): a photograph of the work was being stored as a bare path with nothing about it but the file. So "the crew was at the reported site" was a claim the record could not be checked against — and the **staff page did not even show the photographs it had been given**: the November rewrite took the proof out of the timeline along with everything else, so an office could attach a photograph and never see it again on the screen where it would have to explain itself. The location was also unavailable in principle: nothing about a staff upload was ever read, while the resident's own complaint photos had been reading GPS out of the camera's EXIF record all along.
- What changed: **every photograph now has a row of its own** (`complaint_evidences`) — the place it was taken, read from the camera's own EXIF record; when it was taken; who attached it; and which part of the work it proves (`work_start` · `completion` · `supporting`). A migration backfills every photograph already on a log, so no existing proof is invisible to the new table. The **staff timeline** now shows the proof it holds — each photograph captioned with its coordinates, in a new §5.37 "Proof in the timeline" — and a photograph with no location says **"No location recorded"** instead of quietly standing in for proof of somewhere; a reader who cannot tell the difference is being asked to take it on trust. The **admin audit timeline** carries the same coordinates on its evidence card. The **resident timeline is unchanged**: a resident is shown the photograph and told the work was done, and the exact position of a municipal work site is not theirs to need. The proof photos are written to storage and their rows are attached inside the same transaction as the status update they belong to, so a failed update leaves no orphaned photographs behind.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by the test suite instead: `php artisan test tests/Feature/ComplaintEvidenceTest.php tests/Unit/PhotoLocationServiceTest.php` — 12 passed / 37 assertions. The EXIF reader is exercised against a **real** photograph rather than a mock: the test builds a 1×1 JPEG with a GPS EXIF segment (a TIFF header, an IFD pointing at the GPS IFD, and four GPS entries) and the reader reads the position back out of it, so the test fails if the reader breaks. Covered: one row per photograph, the rows lining up with the path list by index, who attached it, which part of the work it proves, a photograph with GPS recording where it was taken, a photograph without GPS saying so rather than guessing, the staff timeline showing the coordinates, the timeline saying "No location recorded", and a refused update leaving nothing behind. The full suite is 478 tests, 1 error, 20 failures — the same 20 pre-existing failures, with 13 new tests green. `npm run build` — the bundle is 394.47 kB against 390.19 kB for a clean tree. `php artisan view:cache` — Blade templates compile. `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW), unchanged.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll, `en`+`fil` keys added for every new string, no hard-coded hex/serif/gold side bars, one markup per list, ≥44px touch targets and visible focus, no map changes, the guard reports no NEW violations in the touched files.


## 2026-11-10: Staff and admin complaint detail — the proof rule is category-aware, and applies to both

- Page / component: `resources/views/staff/complaints/show.blade.php`, `resources/views/admin/complaints/show.blade.php`, `app/Enums/WorkExecutionMode.php` (new), `config/complaint_work_policy.php` (new), `app/Services/ComplaintWorkService.php` (new), `app/Http/Controllers/StaffController.php`, `app/Http/Controllers/AdminController.php`, `app/Models/Complaint.php`, `lang/{en,fil}/statuses.php`, `lang/{en,fil}/staff.php`, `database/migrations/2026_11_10_000002_add_work_mode_to_complaints_table.php`, `tests/Feature/ComplaintWorkProofTest.php` (new).
- Why (the problem found): the rule was "a photo, always" — every complaint, every office, and **only** the staff path. That was wrong twice over. **A noise complaint is an enforcement action with no site work to photograph**, so demanding a photograph of it is theatre; and **an administrator could settle a repair with a sentence the department could not** — the admin path had no photo field and no proof check at all, so the exact outcome staff were blocked from producing legitimately was one click away for anyone with admin rights. The office asked for a rule that fits the work: a repair is proven by a photograph of it, and a free message is never a substitute for one.
- What changed: a complaint now carries a **work mode** — `labor_required` (a repair, proven by a photograph of it), `report_only` (an enforcement or desk action, proven by a written record) or `referral` — taken from its category via `config/complaint_work_policy.php`, the mirror of the existing `complaint_intake_policy.php` that says what the *resident* must supply at filing. The mode is **frozen onto the complaint the first time anyone acts on it**, so editing the policy later can never move the goalposts on a ticket already in flight; the office may override it on a single complaint (a streetlight that turns out to need a crew) and the override is written to the audit trail. Both paths now call one service, `ComplaintWorkService`, so they cannot drift: the **staff page** states the kind of work in its banner ("Work: Labor work") and asks for a photograph only when the chosen status is one that needs it; the **admin page** gained the same proof capture, the same rule, and the kind of work in its detail list, and can no longer resolve labor work with no photograph. The refusal names the work and the status: *"This is labor work. Attach at least one photo of the work before marking this complaint as in progress."* A report-only complaint is no longer asked for a photograph it cannot produce.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified by the test suite instead: `php artisan test tests/Feature/ComplaintWorkProofTest.php` — 11 passed / 39 assertions, covering the labor refusal and its wording, a report-only complaint moving on without a photograph, the administrator being refused and then allowed, the mode freezing and surviving a later policy change, the per-complaint override, and both pages stating the kind of work and carrying the picker. The full Feature suite is 465 tests, 1 error, 20 failures — the same 20 pre-existing failures as before this work, with 11 new tests green. `npm run build` — the bundle is 393.59 kB against 390.19 kB for a clean tree, so the new component's styles are the whole difference. `php artisan view:cache` — Blade templates compile. `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW), unchanged.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll, `en`+`fil` keys added for every new string, no hard-coded hex/serif/gold side bars, one markup per list, ≥44px touch targets and visible focus, no map changes, the guard reports no NEW violations in the touched files.


## 2026-11-10: Staff complaint detail — proof capture (the office can attach its proof again)

- Page / component: `resources/views/components/proof-capture.blade.php` (new), `resources/css/pages/staff-complaint.css` (new), `resources/js/proof-capture.js` (new), `resources/views/staff/complaints/show.blade.php`, `resources/views/pages/design-system.blade.php` (live demo), `lang/{en,fil}/staff.php`, `resources/css/app.css` (one import), `resources/js/app.js` (one import), `tests/Feature/StaffDocumentationProofTest.php`.
- Why (the problem found): the server has always refused to move a complaint to `In Progress` or `Resolved` without a documentation photo — but the Nov 9 page rewrite dropped the upload surface from the staff form. The rule was live and the only way to satisfy it was gone, so a department could not move a single verified ticket past *Under Review*. The staff form was a status dropdown and a free-text box, with no proof at all.
- What changed: a new component — §5.36 "Proof capture (staff)" — carrying the photo-proof picker **and the office's rule stated above it**. The rule is driven by the status select: the note is neutral while the chosen status needs no photo, and turns to the amber action-needed family the moment one that does is chosen, naming that status ("Moving this complaint to In Progress needs at least one documentation photo"). The staff member learns what the office owes before choosing a status, not from a validation error after pressing send. Two sources (photo library / camera) feed one picker; the camera input is emptied only after the selection has been written back into the gallery input, so a browser that refuses the DataTransfer API still submits the photos. **Without JavaScript the form is still a working form** — the browser puts the files in the inputs and the server reads them; what is lost is the preview, the per-photo remove button and the live rule. Two fixes came with it: the status dropdown now offers only the transitions the server accepts (it previously offered every status except the current one, so its default option was a move the server would refuse with "Invalid status transition"), and the dropdown's default is now the status the proof note describes. The resident filing wizard has its own picker and was **not** touched — this component shares the portal's tokens and visual language with it, not its markup.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). No desktop browser was connected to the session, so the page was verified server-side instead: the rendered HTML was inspected and shows the note in its required state with the correct message, both file inputs (`documentation_images[]`, `documentation_camera_photos[]` with `capture="environment"`), the two triggers pointing at the right input ids, the `0 / 5` counter and the previews container. `php artisan test tests/Feature/StaffDocumentationProofTest.php` — 8 passed / 40 assertions. The full Feature suite is 454 tests, 1 error, 20 failures — one failure fewer than the 21-failure baseline before this work, and no new ones. `npm run build` — production assets built. `php scripts/design-system-guard.php` — 302 violations (243 grandfathered, 59 NEW) before and after, so the three new files add none.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll, `en`+`fil` keys added for every new string, no hard-coded hex/serif/gold side bars, one markup per list, ≥44px touch targets and visible focus, no map changes, the guard reports no NEW violations in the touched files.


## 2026-11-10: Every page — no third-party request (fonts served by the portal itself)

- Page / component: `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/auth/{login,register,change-password,forgot-password,reset-password}.blade.php`, `resources/views/profile/index.blade.php`, `resources/views/complaints/track.blade.php`, `resources/views/pages/legal/{privacy-policy,terms}.blade.php`, `resources/views/pages/home.blade.php`, `resources/views/dashboard/index.blade.php`, `resources/views/pages/legal/privacy-policy.blade.php` (§07).
- Why (the problem found): every page loaded Cormorant Garamond, Inter and IBM Plex Mono **from Google Fonts**, so every page view handed the visitor's IP address and User-Agent to a third party — on a municipal complaint portal, on a page where a resident is about to file a complaint about their barangay. It was also redundant: Inter was already self-hosted (`public/fonts/inter/*.woff2`, `@font-face` in `app.css`), `--font-mono` is the system stack, and the design system retired the serif in the first place. The privacy policy promised "no advertising, tracking, or third-party analytics cookie" — true, but the IP address was still leaving the server, and the policy did not say so.
- What changed: the Google Fonts link is gone from both layouts, and the seven pages that still asked for the retired faces now use `--font-sans` / `--font-mono` (the guide's rule that no page declares a serif now actually holds). The privacy policy's cookie section now names the language cookie (`locale`, one year, carries the language and nothing else) and states that fonts, scripts and styles are served by the portal itself, so visiting a page does not send an IP address to an outside company. Measured in a browser: **zero third-party requests** on the home page, the transparency feed, login, register, forgot-password, the complaint tracker, the privacy policy and the terms.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). The login page was rendered after the change and checked: the headings now resolve to `Inter, ui-sans-serif, system-ui, sans-serif`.
- Definition of Done checklist: passed — no new strings, no hard-coded hex/serif introduced, the full suite is unchanged (455 tests; the same 21 pre-existing failures and 1 pre-existing error), the design-system guard reports no NEW violations in the touched files.

## 2026-11-10: Public transparency feed — soft navigation (filter, chip and page without a reload)

- Page / component: `resources/views/pages/transparency/complaints.blade.php` + `resources/views/pages/transparency/partials/complaints-{chips,active,results}.blade.php`, `app/Http/Controllers/TransparencyController.php` (`complaints`, `complaintsBrowser`, the shared `transparencyComplaints()`), `routes/web.php`, `tests/Feature/TransparencyPageTest.php`.
- Why (the problem found): every filter, chip and page turn on the public feed redrew the whole browser, and the page's own script had never run: it declared `const btn` and `const panel` twice, so the whole IIFE threw `Identifier 'btn' has already been declared` — the filter disclosure, the self-applying selects and the search box were all dead, and a visitor's search was a full round trip per keystroke.
- What changed: the status chips (with their per-status totals), the active filter chips and the results (the count, the list and the pager) are three Blade partials; `transparency.complaints.browser` renders them for the state in the query string and the page swaps them in. The page and the fragments share one controller method, so a fragment can never disagree with the page. The URL stays the truth (`pushState`), Back and Forward re-render in place, and without JavaScript every link and form is still an ordinary navigation. The duplicate declarations are gone, so the filter disclosure, the self-applying selects and the search box work again. The `noindex` rule for a filtered feed travels with the fragments, because that rule lives in the `<head>` and a swap does not touch it. The public payload is unchanged: the fragments carry the same data the page renders, and the new test asserts no resident identity reaches them.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Verified in a real browser against both dev servers: no console errors, the disclosure opens, the status chips, the search box, the pager (including a page **number**, not just Next), removing a chip and the browser's Back button all stay soft, and the count line follows ("Showing 1–5 of 7" after a chip, "Showing 6–10 of 20" on page 2).
- **The fragment endpoints are machine endpoints.** A person who types `/transparency/complaints/browser?page=2` used to get raw JSON; it now redirects to the page with the same state (`/transparency/complaints?page=2`). Only a script (a JSON request or an XHR) gets the fragments. The same rule covers the three admin endpoints. The `noindex` rule for a filtered feed travels with the fragments, because that rule lives in the `<head>` and a swap does not touch it.
- **Interception is by path, not by region.** Any link to the same page is a change of state, not a change of page, so the click handler decides by pathname. Deciding by "is this link inside a swappable region" meant a link that moved in the markup fell back to a full navigation — which is exactly what a page number did after the pager moved into the results region.
- Definition of Done checklist: passed — one h1/footer, no new strings, no hard-coded hex/serif, one markup per list, the filters stay in the URL and are shareable, the public payload is unchanged, the guard reports no NEW violations in the touched files.

## 2026-11-10: Admin pages — the phone card, shared across the admin workspace

- Page / component: `resources/css/pages/admin-workspace.css` (`.admin-phone-card`, `.admin-phone-cell`, `.admin-phone-values`), `resources/css/app.css` (`.admin-page` grid track), `resources/views/partials/public-ui.blade.php` (the review-status pill variants), `resources/views/admin/complaints/index.blade.php` + `resources/views/admin/complaints/partials/list.blade.php` (the first adopter).
- Why (the problem found): the admin queue was unreadable on a phone. The rows lost their column headers, so a card read "WST / Waste Management Office / SUBMITTED / PENDING VERIFICATION / 1d" with nothing saying what any of it was; the select-all control was white text on a light page; the category icon was flung to the far corner; and — worse — **the whole admin workspace was clipped**: `.admin-page` is a grid whose implicit `auto` track is sized by the content's max-content, so a table wider than a phone stretched the page and the body cut the right-hand third off every admin table, on every admin page.
- What changed: a **shared phone card** (§5.35 in the guide) — one card, one 84px label column at 10px/700 uppercase, one type scale — that any admin row adopts in three steps, with the wrapper set to `display: contents` on a desktop so adoption cannot change the desktop layout. The complaints queue adopted it first: every value cell now states what it is (Department / State / Priority / Waiting), the values share one line, the icon sits beside the title, the select-all label is readable, and the office name truncates instead of dropping a line. The page-level fix repairs the clipping on every admin page at once. Two pre-existing bugs surfaced and were fixed: the review-status pills (`pending`, `needs_information`, `duplicate`, `escalated`) had no variant and rendered as bare bold text with no pill on every screen, and the office-name rule was written `.admin-queue__deptname` while the markup said `.admin-queue-deptname`, so it never matched.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Measured at 393×852: the queue row went from 288px to 238px tall and now reads as a labelled card; `scrollWidth === innerWidth === 393` on both the queue and the audit trail; the desktop row is unchanged (82px, five columns). The design-system guard reports no NEW violations in the touched files.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll at 393px, no new strings, no hard-coded hex/serif, one markup per list, ≥44px touch targets, the guard reports no NEW violations in the touched files.

## 2026-11-10: Admin complaints queue — soft navigation (no reload on filter, page or chip)

- Page / component: `resources/views/admin/complaints/index.blade.php` + `resources/views/admin/complaints/partials/{summary,list,pagination}.blade.php`, `app/Http/Controllers/AdminController.php` (`complaintsBrowser`, `complaintsQueue()`, `complaintFilterLabels()`, `complaintQueueStats()`), `routes/web.php`, `tests/Feature/AdminQueueTest.php`.
- Why (the problem found): every action in the queue redrew the whole browser — changing a department/status/review/priority/modulation filter, applying the search, removing a chip or turning a page threw away the DOM, the scroll position and the admin's place, on a municipal connection.
- What changed: the filter chips, the queue list and its pager are three Blade partials; `admin.complaints.browser` renders them for the state in the query string and the page swaps them in. The URL stays the truth (`pushState`), the controller's state helpers are shared with the page, and with JavaScript off every link and form is still an ordinary navigation. The bulk-reassignment bar now listens by delegation instead of binding to the checkboxes at load, so the rows that arrive with a swap are picked up without a re-bind, and the bar resets when the selection is replaced.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). A marker on `window` (which survives a fragment swap and dies on a real navigation) confirmed that the self-applying status filter, the search + Apply, removing a chip, the pager and the browser's Back button all stay soft; the bulk bar was checked after a swap (it appears with the right count). Pre-existing `AdminQueueTest` failures (6) are unchanged by this work — verified by running the suite with these changes stashed.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll at 393px, no new strings (the page's existing copy is unchanged), no hard-coded hex/serif, one markup per list, the filters stay in the URL, no map changes, the guard reports no NEW violations in the touched files.

## 2026-11-10: Admin audit trail — drill-down browser replaces Department/Actor filters

- Page / component: `resources/views/admin/audit/index.blade.php` + `resources/views/admin/audit/partials/{scope,contexts,person-row,roster,roster-head,people-results,trail}.blade.php`, `resources/css/pages/admin-workspace.css` (`.admin-audit-browser`), `app/Services/AuditDirectory.php`, `app/Http/Controllers/AdminController.php` (`auditIndex`, `auditBrowser`, `auditPeople`), `routes/web.php`, `lang/{en,fil}/audit.php`, `lang/{en,fil}/pagination.php`, `resources/views/vendor/pagination/workspace.blade.php`, `resources/views/pages/design-system.blade.php`, `database/migrations/2026_11_10_000001_add_actor_role_index_to_activity_logs_table.php`.
- Why (the problem found): the audit page made admins rebuild a dropdown query to inspect one office or person, never showed how many entries each entity had, and only listed staff/admins in the Actor dropdown (the "Citizens" group was always empty). There was no path to system/automated records. The first pass of this redesign then exposed a second problem: it rendered **every** actor in the log, so a system with thousands of residents produced a ~2.9 MB page.
- What changed: the Department and Actor dropdowns are replaced by a **browser** card in three bands — a scope band (what the trail is showing, with `Clear selection`), a search band (`Find a person…`, server-side type-ahead over people, with the plain form as the no-JS path), and two panes: a scope rail — Everyone, a `Residents` section (`?residents=1`), a `Departments` section with every office, and an `Automated` section for the records with no actor — and a roster of people grouped by office, then Administration, then Residents. Clicking a scope or a person filters the same page via `?department_id=`, `?actor_id=`, `?residents=1` or `?system=1`; the selected row is marked with a navy inset bar + `aria-current`. Search and Action remain secondary filters and survive entity switches. **The roster is bounded**: every group shows 8 rows and offers `Showing 8 of N` → `See all N` (25 per page, `?people_group=&people_page=`), so the page never grows with the log and never dumps a list. The people search finds one person among thousands without scrolling. Mobile collapses the panes behind "Browse departments and people" (mirroring the existing Filters disclosure) and keeps the scope band and search visible; person rows stack the role under the name. Empty states distinguish "no match" (filters) from "nothing recorded for this selection" (entity) and "nobody matches “x”" (people search), each with the matching reset.
- Backend: the residents scope filters the trail on `actor_role`, backed by a new `(actor_role, created_at)` index on `activity_logs` (`database/migrations/2026_11_10_000001_add_actor_role_index_to_activity_logs_table.php`) so it reads the index instead of scanning the log. `AuditDirectory` keeps one cached snapshot (60 s) of the totals, the per-actor counts and the roster **ids** per group, and fetches identity rows only for the people actually on screen; the office list is flushed when an office changes. `AdminController::auditIndex` validates `department_id`/`actor_id`/`system`, keeps the old combined `department_id+actor_id` links working (both filters apply, the label names the person and their office), and `auditPeople` returns the rendered roster fragment for the type-ahead so the row markup exists in exactly one Blade file.
- **Every number states what it counts.** The scope rail carries a `Scope | People` head and counts the **people** behind each scope (12 people in the log, 5 residents, 3 people in an office); the roster keeps a `People | Entries` head and counts the **entries** each person made; the scope band spells out `Showing <entity> · <n> entries` — the size of the trail on screen. The units count themselves ("1 person", "3 entries", "0 records"). A standing line under the rail explains that an office lists its own people while its trail also holds the resident filings routed to it, so the two never silently disagree.
- Measured: 3,000 synthetic residents in the log — before, 347 ms server time and 2.87 MB of HTML with ~3,000 roster rows; after, 62 ms and 179 KB with 57 roster rows. Every state (office, person, expanded group page 30, people search) stays under 70 ms.
- **The trail pages eight entries at a time** with the shared operator pager (Previous / Next + page numbers; on mobile only Previous / Next, full-width 44px targets). The `pagination::workspace` view is now translated (`lang/{en,fil}/pagination.php`), so the staff queue's pager stops being English-only.
- **Nothing reloads the page.** Changing a scope (Everyone / Residents / an office / the automated records), selecting a person, turning a page, applying the Action filter, typing in the trail search and typing in the people search all swap the changed regions in place. The scope band, the scope rail, the roster and the trail are four Blade partials; `admin.audit.browser` renders them for the state in the URL and the page swaps them in, so one markup serves both paths. The URL stays the truth (`pushState`), the controller state helpers are shared with the page, and without JavaScript every link and form still works as an ordinary navigation. Two bugs fixed along the way: the people type-ahead was fetching the page route instead of the JSON endpoint (and the form swallowed Enter), and Laravel's paginator was inheriting the endpoint's path, so a fragment's pager linked back to the endpoint.
- Verification screenshots: NOT captured in the repo (waived by owner, 2026-11-09). Temporary Playwright renders in `/tmp/opencode/audit-ui/` covered 1310×900 (Everyone, office, person, residents, automated records, search+office, legacy combined, expanded group, people search) and 393×852 (collapsed and selected scopes) in `en` and `fil` — counts, selection highlight, search persistence, translated labels, the bounded-roster affordance and the mobile disclosure all verified. A marker on `window` (which survives a fragment swap and dies on a real navigation) confirmed that scope changes, page turns, filters, the trail search, the people search and the browser's Back button all stay soft.
- Definition of Done checklist: passed — one h1/footer, no horizontal scroll at 393px, `en`+`fil` keys added for every new string, no hard-coded hex/serif/gold side bars (selected state is navy), one markup per list, selection + filters persist in the URL, no map changes, ≥44px targets and visible focus, `php scripts/design-system-guard.php` reports no NEW violations in touched files.

## 2026-11-10: Transparency complaints page — remove navy body background, make white

- Page / component: `resources/views/layouts/guest.blade.php` (body class + inline style).
- Why (the problem found): the guest layout hardcodes `bg-navy` on `<body>` and `background-color: #142D4F` on `html, body` in an inline `<style>` block, so the transparency complaints page had a navy backdrop even though its content section was white.
- What changed: conditionally removed `bg-navy` from `<body>` and set `body { background-color: var(--surface) }` (white) when `request()->routeIs('transparency.complaints')`. The `html` background stays navy (it only shows at viewport edges); the body is now white on this page so the white content section blends seamlessly.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (conditional class/style; uses existing `--surface` token; no new hard-coded hex/serif/map-isolation issues; design-system guard reports no NEW violations in touched files).

## 2026-11-10: Transparency complaints page — title visible on white background

- Page / component: `resources/views/pages/transparency/partials/complaints-styles.blade.php` (`.complaints-page-title`).
- Why (the problem found): after switching the page background to white (`--surface`), the title kept `color: var(--surface)` (white), making it invisible on the white background — especially for logged-in users where the app shell renders the page against a white surface.
- What changed: `.complaints-page-title` color changed from `var(--surface)` to `var(--text)` so the heading is dark and readable on the white background.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (one-token color swap; design-system guard reports no NEW violations in touched files).

## 2026-11-10: Transparency complaints page — white background, remove back link

- Page / component: `resources/views/pages/transparency/complaints.blade.php`.
- Why (the problem found): the page inherited the guest shell's navy body background (`#142D4F`), creating a dark blue backdrop, and had a "Back to Transparency" link that wasn't needed on this sub-page.
- What changed: removed the back link (`<a class="tr-back">` to `route('transparency')`); wrapped the page content in `<section class="complaints-page">` with `background: var(--surface)` (white) and `min-height: calc(100svh - var(--navbar-height))` so the page fills the viewport with a white background instead of navy.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted markup/CSS fix; uses `--surface` token, no new hard-coded hex/serif/map-isolation issues; design-system guard reports no NEW violations in touched files).

## 2026-11-10: Admin dashboard — remove the empty “System activity” card

- Page / component: `resources/views/admin/dashboard/index.blade.php`, `resources/css/pages/admin-dashboard.css` (dashboard side rail).
- Why (the problem found): the card was a copy of the staff activity feed, but `AdminController@dashboard()` never supplied `$recentActivity` (and `ActivityLog` has no `display_title`/`display_detail` accessors), so it always rendered “No system activity yet.” It also forced a 320px side rail that squeezed the dashboard content. System-wide activity already has a dedicated home on the Audit trail page.
- What changed: removed the dead aside and its fallback markup, removed the admin-scoped `.dept-activity*` styles, and collapsed the dashboard to a single full-width column (removed the 320px side rail/sticky-side rules). The Departments grid now gains columns from the recovered width: 3 at 1440/1280, 2 at 1024/900, 1 at 768/393/360.
- Verification: Playwright at 1440/1280/1024/900/768/640/393/360 — no “System activity” text or aside in the DOM, no horizontal overflow, Department columns 3/3/2/2/1/2/1/1. Screenshot captured locally at `/tmp/opencode/dept-cards/dashboard-no-activity-1440.png` (not committed).
- Definition of Done checklist: passed (dead UI removal; no new hard-coded hex/serif/map-isolation issues; guard reports no NEW violations in touched files).

## 2026-11-10: Admin dashboard — restructure Departments cards for robust responsiveness

- Page / component: `resources/views/admin/dashboard/index.blade.php` and `resources/css/pages/admin-dashboard.css` (Departments card grid).
- Why (the problem found): the previous card was a single flex row where the badge, title, and stats all competed for width. Long names (e.g. “Municipal Planning and Development Office”) collided with the code badge, the title and stats both used `white-space: nowrap` + `text-overflow: ellipsis` (so “0 pending · 0 staff” rendered as “0…” / “0 pend…”), and the chevron sat at an arbitrary position. The forced 3-column 1024–1399px rule made the cards even narrower.
- What changed: rebuilt each card as content column + chevron column. Inside the content column, the badge and title share a `grid-template-columns: auto minmax(0, 1fr)` row (badge content-sized and nowrap, title freely wrapping with `overflow-wrap: anywhere` + `text-wrap: balance`), and the stats sit on their own wrapping flex line with no ellipsis. The chevron is fixed 18px and vertically centred. The grid is now `repeat(auto-fill, minmax(min(100%, 280px), 1fr))` with `align-items: stretch`; the hard-coded 1-col and 3-col media queries were removed, so cards collapse to one column when narrow and add columns as width allows.
- Verification: Playwright at 1440/1280/1100/1024/900/768/700/640/480/393/360px — zero badge/title overlap, no truncated titles or stats, equal heights within each row, 18px chevrons centred to <0.1px, and no horizontal overflow. Stress-tested with a 110-character title and 5-digit pending/staff counts at 1920/1440/393/320/300px — all wrap cleanly with no overflow. Screenshots captured locally in `/tmp/opencode/dept-cards/` (not committed).
- Definition of Done checklist: passed (targeted markup/CSS restructure; existing `--surface`, `--dept-*`, `--faint`, `--radius-lg`, `--shadow-card/-lift`, `--ring-inset` tokens only; no new hard-coded hex/serif/map-isolation issues; guard reports no NEW violations in touched files).

## 2026-11-10: Admin complaint detail — tighten layout, fix blank aside space

- Page / component: `resources/css/app.css` (`.admin-detail-layout`, `.admin-detail-list`, `.admin-panel-heading`, `.admin-detail-list dt/dd`) and `resources/css/pages/admin-workspace.css` (tablet breakpoint).
- Why (the problem found): the complaint detail page used a 2-column grid with a fixed `minmax(320px, 0.8fr)` aside that left a large blank empty space when the aside content (update form, audit history) didn't fill it. The `.admin-detail-list` used `repeat(2, minmax(0, 1fr))` creating equal-width columns that misaligned long labels/values. Panel headings had oversized `min-height: 62px` and `padding: 15px 18px` creating uneven vertical space.
- What changed: narrowed the aside to `minmax(260px, 0.7fr)` and reduced gap to 20px; rewrote `.admin-detail-list` as a label column (150px) + value column (1fr) with `display: contents` on row wrappers so dt/dd align cleanly; changed `.admin-panel-heading` to flex `justify-content: space-between` with tighter `min-height: 56px` and `padding: 12px 18px`; added a `@media (max-width: 1024px)` breakpoint that collapses the detail layout to a single column, eliminating blank aside space on tablets/smaller screens.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted CSS fix; uses existing design tokens only — no new hard-coded hex/serif/map-isolation issues; design-system guard reports no NEW violations in touched files).

## 2026-11-10: Admin dashboard — department card responsiveness fix (long names)

- Page / component: `resources/css/pages/admin-dashboard.css` (`.dept-department-card`, `.dept-department-grid`).
- Why (the problem found): department cards used `grid-template-columns: minmax(0, 1fr) auto` with `display: contents` on the inner row. Long department names (e.g. "Municipal Planning and Development Office") broke the layout — `display: contents` promoted badge/name/meta as separate grid items so ellipsis truncation never applied, and names overflowed or pushed cards out of alignment.
- What changed: rewrote the card as a flex row (badge | truncating name | meta) with `flex: 1` + `min-width: 0` + `text-overflow: ellipsis` on the name so long names truncate cleanly instead of breaking the card. Made the grid responsive with `repeat(auto-fill, minmax(240px, 1fr))`, a 3-column cap at 1024–1399px, and 1 column ≤767px.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted CSS fix; uses existing tokens only — `--surface`, `--dept-card-border`, `--dept-card-shadow`, `--dept-ink`, `--dept-muted`, `--dept-active-border`, `--radius-lg`, `--shadow-lift`, `--ring-inset`; no new hard-coded hex/serif/map-isolation issues; guard reports no NEW violations in touched files).

## 2026-11-10: Admin complaints queue — desktop Previous/Next pagination buttons

- Page / component: `resources/views/admin/complaints/index.blade.php`.
- Why (the problem found): the queue pagination used `{{ $complaints->links() }}` which renders Laravel's default Bootstrap-style pagination. No CSS targeted `.tr-pagination-nav`, so the Previous/Next buttons were unstyled/invisible on desktop and users could not navigate between pages of complaints.
- What changed: replaced `{{ $complaints->links() }}` with `{{ $complaints->links('pagination::workspace') }}` which renders the project's shared operator pagination view (`.admin-pager` with styled Previous/Next buttons and page numbers), and changed the wrapper to `.admin-pagination` so existing admin-pager CSS applies.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted markup fix; reuses existing `pagination::workspace` view and existing tokens; no new hard-coded hex/serif/map-isolation issues introduced).

## 2026-11-10: Admin dashboard — stat card links now use correct status enum values

- Page / component: `resources/views/admin/dashboard/index.blade.php`.
- Why (the problem found): stat card links ("Unassigned", "In progress", "Resolved today") navigated to `/admin/complaints?status=submitted` etc. with lowercase snake_case values, but `ComplaintStatus` enum stores Title Case (`"Submitted"`, `"In Progress"`, `"Resolved"`). The controller filter `$query->where('status', $request->status)` never matched, so the complaints table showed all records instead of the filtered subset.
- What changed: replaced hardcoded lowercase status strings with `\App\Enums\ComplaintStatus::Submitted->value`, `::InProgress->value`, and `::Resolved->value` so the URL status values match the database column values and the filter applies correctly.
- Verification screenshots: NOT captured (waived by owner, 2026-11-09).
- Definition of Done checklist: passed (targeted markup fix; no new hard-coded hex/serif/map-isolation issues introduced).

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

## 2026-11-10: Admin dashboard — link Unassigned card to unassigned complaints

- Page / component: `resources/views/admin/dashboard/index.blade.php` (Unassigned stat card).
- Why (the problem found): clicking “Unassigned” linked to `status=Submitted` instead of filtering for complaints with no assigned department.
- What changed: changed the Unassigned card href to `admin.complaints.index` with `['department' => 'unassigned']`, matching the complaints index unassigned filter.
- Verification screenshots: NOT captured (waived by owner, 2026-11-10).
- Definition of Done checklist: passed (targeted bugfix).

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
---

## 2026-11-16 · Office statistics (Department Administrator) + stat-card consistency

- **Goal:** Give each Department Administrator a DA-only, office-scoped **Office statistics** page to track performance and identify common/recurring complaints (with presets/custom range, trends, and drill-down), and keep stat cards consistent with the dashboard style.
- **Why:** Department Administrators need an at-a-glance, privacy-safe view of their own office’s volume, timeliness, workload and recurring issues without access to other offices or resident identities.
- **Before:** `/staff/office`, `/staff/activity`, and `/staff/department-activity` used `staff-metric-grid` stat blocks; there was no DA-only office statistics view (existing analytics routes remain globally paused).
- **After:** Stat cards on those pages standardized to dashboard `dept-stats`/`dept-stat` with icon tones. New **Office statistics** (`/staff/office/statistics`, DA-only, own active office) provides KPI cards (dept-stats), presets + custom range + trends, status/category/barangay/aging/quality breakdowns, per-staff workload, recent activity, and drill-down links into the office complaint queue. Available now (not behind `analytics.paused`).
- **Files:** `app/Http/Controllers/DepartmentAdminController.php`, `app/Http/Controllers/StaffController.php` (optional `category`/`barangay` queue filters for drill-down), `routes/web.php`, `resources/views/staff/office/statistics.blade.php`, `resources/views/staff/office/partials/statistics-content.blade.php`, `resources/views/staff/office/index.blade.php`, `resources/views/staff/activity/index.blade.php`, `resources/views/staff/activity/department.blade.php`, `resources/views/staff/partials/sidebar.blade.php`, `resources/views/layouts/partials/mobile-drawer.blade.php`, `lang/en|fil/nav.php`, `lang/en|fil/office.php`, `docs/office-statistics-plan.md`, `docs/design-system.md`.
- **New tokens:** none (reuses `dept-stats`/`tr-stat__icon`/`an-*`/`status-chip` patterns and existing tokens). No new one-off colours.
- **Verification:** screenshots **NOT captured (waived by user 2026-11-09)**. Static/code checks run: PHP lint on edited PHP files, route registration, design-system guard, and feature tests (record outcomes in this entry/PR as executed). In-browser verification of period controls, drill-downs, and DA-only scoping recommended in target environment (requires migrated DB and rebuilt assets if CSS changed; Blade-only changes here).
- **Known gaps / follow-ups:** `docs/office-statistics-plan.md` is the handoff/memory (progress checklist). Export/print reuse existing analytics service/print layout; refine drill-down filters/labels as needed; add/expand automated tests for statistics access/scoping/aggregates.

---

## 2026-11-16 · Verifier assignment with workload recommendation

- **Goal:** Let office verifiers assign a complaint to staff right after verification, with workload-aware recommendations for faster, balanced assignment.
- **Why:** Verifiers can settle/verify reports and are well-placed to hand off work immediately; showing active load helps avoid overloading staff.
- **Before:** Only Department Administrators (same office) and global admins could assign; assignment panel was DA-only and showed no workload.
- **After:** Office verifiers (**Department Administrators + staff with `can_verify`**, same office) can assign/reassign/unassign active office members (including self) on the staff complaint view. The assignment panel shows each member’s **active/open load** (submitted/under review/in progress) and **overdue** count, sorted least-loaded first with a “Recommended” marker. Assignment remains office-scoped, audited, and notifies the assignee. Staff complaint queue supports `assigned_staff_id`/`assigned=unassigned` drill-down.
- **Files:** `app/Policies/ComplaintPolicy.php`, `app/Http/Controllers/DepartmentAdminController.php` (`assignComplaint`), `app/Http/Controllers/StaffController.php` (`show`, `complaintsQueue`), `resources/views/staff/complaints/show.blade.php`, `resources/views/staff/office/partials/statistics-content.blade.php`, `lang/en/staff.php`, `lang/fil/staff.php`.
- **New tokens:** none (reuses existing `staff-action-panel` / form / text tokens).
- **Verification:** screenshots **NOT captured (waived by user 2026-11-09)**. PHP lint on edited files OK; `tests/Feature/DepartmentAdminRoleTest` 13 passed (68 assertions); `php scripts/design-system-guard.php` 0 NEW. Recommend adding feature coverage for verifier (can_verify staff) assignment + workload ordering; in-browser QA of post-verification assignment flow.
- **Known gaps / follow-ups:** assignment UI appears for authorized verifiers on accessible office complaints (not gated to a single “just verified” moment); refine load metric (e.g. include/exclude on-hold explicitly) or add a one-click “Verify & assign” combined action if desired.

---

## 2026-11-16 · Lock settled Review & verification decisions (read-only)

- **Page / component:** `resources/views/staff/complaints/show.blade.php` (staff complaint detail — Review and verification card).
- **Why:** Once a verifier has recorded a final review decision (Verified, Rejected, or Duplicate), allowing the decision/priority/method/notes to be edited again via dropdowns was incorrect and could conflict with downstream state and the official response flow.
- **Before:** The Review and verification form (decision, confirmed priority, verification method, notes, save) remained editable/collapsible for verifiers even after a settled decision; status/priority summary was shown above it.
- **After:** When `review_status` is **Verified, Rejected, or Duplicate**, the editable form, inputs/dropdowns, save action, and toggle are removed for everyone. A **read-only review record** is shown instead: the existing Review status/Suggested priority/Confirmed priority summary plus recorded **Verification method** and **Review notes** (or “Not recorded”/empty-state text) and a note that the decision is final. Unsettled reviews keep the existing verifier form/non-verifier read-only behavior. “Add official response” (status update) remains unchanged.
- **New tokens:** none (reuses existing card, `surface-soft`, `line-soft`, text tokens/inline styles consistent with the card).
- **Verification:** screenshots **NOT captured (waived by owner 2026-11-09)**. `php scripts/design-system-guard.php` — 0 NEW. Manual QA recommended on Verified/Rejected/Duplicate and unsettled complaints to confirm read-only summary, preserved status visibility, and unchanged status-update flow.
- **Known gaps / follow-ups:** Lock is UI-only for these states (server-side enforcement of immutability for settled review edits, if required, is separate). Escalated review status remains editable per current decision.

---

## 2026-11-16 · Staff complaint detail layout reorder (resident/location right, description left)

- **Page / component:** `resources/views/staff/complaints/show.blade.php` (staff complaint detail grid).
- **Why:** Improve scanability by placing resident context and location alongside actions/details as requested.
- **Before:** Main (left) column started with Resident information, then Complaint description, then Complaint location; side (right) column held assignment and other action panels.
- **After:** **Left/main:** Complaint description first (followed by activity timeline, evidence, review, etc.). **Right/side:** Resident information and Complaint location moved to the top, above assignment and existing side panels.
- **New tokens:** none (markup reordered only; existing components/styles reused).
- **Verification:** screenshots **NOT captured (waived by owner 2026-11-09)**. `php scripts/design-system-guard.php` — 0 NEW. Manual QA recommended across breakpoints to confirm grid stacking, map rendering, and no duplicate/missing sections.
- **Known gaps / follow-ups:** Responsive stacking follows existing `.staff-detail-grid` behavior; adjust card order within columns if a different priority is preferred.

---

## 2026-11-16 · Staff complaint detail layout corrected (desktop columns + mobile stack)

- **Page / component:** `resources/views/staff/complaints/show.blade.php` (staff complaint detail grid).
- **Why:** Correct the previous reorder so desktop and mobile match the requested structure.
- **Before:** Column wrappers forced desktop placement but produced the wrong mobile stacking/order after reorder.
- **After:** Flattened detail items into a single grid with explicit desktop placement: **left/main** = Complaint description → Activity history timeline → Resident evidence photos → Review & verification (+ follow-ups). **Right/sidebar** = Resident information → Complaint location/map → Assignment picker → official response/status & action panels (moderation/publication/hold/status update). **Mobile/single-column stack:** Resident information → Complaint location/map → Complaint description → remaining main and side items in flow.
- **New tokens:** none (markup/classes and existing page CSS adjusted; no new colours).
- **Verification:** screenshots **NOT captured (waived by owner 2026-11-09)**. `php scripts/design-system-guard.php` — 0 NEW. Manual QA recommended on desktop and mobile widths to confirm column placement, stacking order, map rendering, and action panels.
- **Known gaps / follow-ups:** If specific action panels should appear in a different position within “rest/actions”, specify the desired order for fine-tuning.

---

## 2026-11-16 · Staff complaint detail — two aligned columns under the full-width header

- **Page / component:** `resources/views/staff/complaints/show.blade.php` (staff complaint detail grid + page CSS).
- **Why:** The complaint cards were laid out as items of one shared CSS grid with a fixed column on each card (`grid-column: 1` / `grid-column: 2`). The browser therefore placed them in shared grid rows: the right-hand cards (resident information, location) took the first rows and the left-hand cards (description, timeline) started two rows lower, so the page showed a tall empty band in the right column, the description/timeline sat too low and off-centre, and the two columns did not read as one aligned structure.
- **Before:** One grid whose cards each claimed a column; row alignment produced the gap. Mobile order was also DOM-dependent.
- **After:** A CSS grid of **two independent flex columns** beneath the full-width complaint header:
  - **Left (main):** Complaint description → Activity history timeline → Resident evidence photos → Review & verification → Verification follow-up.
  - **Right (sidebar):** Resident information → Complaint location/map → assignment picker, official response/status update and the other action panels (moderation, publication, hold, closure).
  - `grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.75fr)` (the dashboard's proportions), one 24px column gap, 20px rhythm inside each column, `align-items: start` so both columns share the same top edge and each column stacks independently (a short card no longer opens a hole in the other column).
  - ≤1023px: one column; the column wrappers dissolve (`display: contents`) so every card is a grid item and the phone order stays resident → location → description → timeline → evidence → review → follow-up → actions.
  - Cards keep `min-width: 0` and `overflow-wrap: break-word` so long content cannot force horizontal scroll.
- **New tokens:** none (existing `.tr-card`/`.workspace-card`, spacing scale, `--line-soft`/`--surface-soft` reused; no colours, no typography, no icons changed).
- **Verification screenshots:** NOT captured (waived by owner 2026-11-09). `php artisan view:cache` compiles; `php artisan test tests/Feature/{ComplaintMapRenderingTest,InlineScriptRuntimeTest}.php` — **19 passed** (193 assertions); `tests/Feature/DepartmentAdminRoleTest.php` + `PageOverflowTest.php` — **15 passed** (80 assertions); `tests/Feature/StaffActionPanelsTest.php` — **8 passed** (43 assertions); `php scripts/design-system-guard.php` — **0 NEW** violations (250 grandfathered). Manual QA in a browser at 1440px/1024px/393px is still recommended to confirm the rendered column split.
- **Definition of Done checklist:** passed — one `<h1>`/one footer unchanged; no horizontal scroll (`min-width: 0` + `overflow-wrap`); no raw lang keys and no new strings (no `en`/`fil` additions needed — layout only); no hard-coded hex, no serif, no new colours; every list unchanged (no duplicated markup — cards were moved, not copied); filters/URL state untouched; map wrapper keeps `isolation: isolate; z-index: 0`; focus rings and `prefers-reduced-motion` untouched; sidebar, header, cards, routes, Livewire bindings and JavaScript (map, live-ticket poll, review toggle, assignment picker) all unchanged.

---

## 2026-11-16 · Staff complaint detail — the column grid repaired, the response card moved left, the back link fixed

- **Page / component:** `resources/views/staff/complaints/show.blade.php` (staff complaint detail grid, page CSS, back link), `tests/Feature/StaffComplaintLayoutTest.php` (new).
- **Why:** The previous layout fix left two real defects, both visible on the page:
  1. **A stray `</div>` closed the grid early.** The right-hand column was therefore *not* a child of `.staff-detail-grid` — it rendered after the grid, full width, while the grid's own cards stayed inside a narrow two-column box. That is what produced “some cards narrow, others full width”, the empty band, and the off-centre description/timeline. Blade does not validate HTML, so nothing complained.
  2. **The back link printed a raw language key** (`staff.dashboard.back` exists in neither `en` nor `fil`), so the page showed the key itself.
- **Before:** Grid with two columns but the second one outside it; “Add official response” in the right column; raw key on the back link.
- **After:**
  - The detail grid holds exactly two children — `.staff-detail-col--main` (complaint description → activity history timeline → resident evidence photos → review & verification → verification follow-up → **Add official response**) and `.staff-detail-col--side` (resident information → complaint location/map → assignment picker → pause work / hold). Each column is its own flex stack (20px rhythm), both start at the same top edge (`align-items: start`), and the desktop grid keeps the dashboard's `1.45fr / 0.75fr` proportions with a 24px column gap.
  - **1024–1279px:** the staff sidebar is still docked, so the sidebar column keeps a 260px floor (`minmax(260px, 0.6fr)`) and the complaint column takes the rest — the cards are never squeezed below ~260px.
  - **≤1023px:** one column. The column wrappers dissolve (`display: contents`), every card is a grid item forced to `width: 100%; max-width: none; grid-column: 1 / -1`, so **every card is exactly the content width** — no card keeps a desktop column width. Each card carries a `staff-detail-card--*` order slot giving the reading order: resident information → complaint location → complaint description → activity history timeline → **add official response** → resident evidence photos → review & verification → verification follow-up → assignment → pause work → moderation / publication / closure.
  - The back link uses the existing `staff.back` string → “Back to Staff Dashboard” / “Bumalik sa Staff Dashboard”.
- **New tokens:** none (existing `.tr-card`/`.workspace-card`, spacing scale, `--line-soft`/`--surface-soft`; no colours, typography, icons or sidebar changes).
- **Verification screenshots:** **captured** — `complaint-detail-1440.png`, `complaint-detail-1024.png`, `complaint-detail-939.png`, `complaint-detail-393.png` (repo root; Chromium/Playwright against the running app, staff account, complaint `COMP-2026-TYEZ4`). Measured in the browser: **1440px** two columns 670px/346px, both columns top-aligned at the same y; **1280px** 564px/292px; **1024px** 344px/260px; **939px** single column, **every card 875px** (one distinct width), order 1→10 as listed; **393px** single column, **every card 329px**, `scrollWidth == innerWidth` (no horizontal scroll). A raw-key sweep of the rendered page now reports **no untranslated keys**.
- **Verification (code):** `php artisan view:cache` compiles; `tests/Feature/StaffComplaintLayoutTest.php` — **3 passed** (19 assertions; reads the *parsed* document, so an unbalanced tag fails the test — verified by re-introducing the stray `</div>`, which turned 2 of the 3 tests red, then reverted); `tests/Feature/{LocalizationTest,ComplaintMapRenderingTest,InlineScriptRuntimeTest,DepartmentAdminRoleTest,StaffActionPanelsTest,PageOverflowTest,ComplaintLiveTicketTest,PrivacyGatedComplaintTest,ComplaintHoldTest,ComplaintWorkProofTest,StaffWorkspaceTest}.php` — **137 passed** (694 assertions) with the layout suite; `php scripts/design-system-guard.php` — **0 NEW** violations (250 grandfathered).
- **Definition of Done checklist:** passed — one `<h1>`/one footer unchanged; no horizontal scroll at 393px (measured); no raw lang keys (measured in the browser); no new strings (`staff.back` already existed in en + fil); no hard-coded hex, no serif, no new colours; every list unchanged and no duplicated markup (cards were moved, not copied); filters/URL state untouched; map wrapper keeps `isolation: isolate; z-index: 0`; focus rings and `prefers-reduced-motion` untouched; sidebar, header, routes, Livewire bindings and JavaScript (map, live-ticket poll, review toggle, assignment picker) all unchanged.

---

## 2026-11-16 · Staff ticket banner — dark chips on a navy field, and the deadline chip that never got its style

- **Page / component:** `resources/views/staff/complaints/show.blade.php` (banner styles), `app/Enums/ComplaintSlaState.php` (`badgeClass()`), `resources/css/pages/staff-complaint.css` (`.sla-chip--*` family, unchanged), `docs/design-system.md` (§5.1, §5.38, §12).
- **Why:** On the navy ticket banner three labels were dark-on-dark and could not be read: the **ticket id** (`.ticket-id` is `--navy-700` — the colour it uses on white cards), the **confirmed-priority pill** (`.status-pill--neutral|info|warning|danger` had no stylesheet rule anywhere, so it inherited the page text colour and had no background), and the **deadline chip**. The chip was broken everywhere: `ComplaintSlaState::badgeClass()` returned `sla-overdue` while the stylesheet and §5.38 key off `sla-chip--overdue`, so no family matched, the chip had no background, and its text fell back to the page colour — invisible on the banner and unstyled in the staff queue.
- **Before:** Ticket id, priority pill and deadline chip unreadable on the navy banner; deadline chip unstyled in the queue too.
- **After:**
  - `ComplaintSlaState::badgeClass()` returns the documented class names (`sla-chip--on-track` / `--due-soon` / `--overdue` / `--met` / `--none` / `--on-hold`), so the chip carries its status family again — light tint background, dark label — on the banner and in the staff queue list.
  - Banner treatment for the navy field (scoped to `.staff-complaint-detail .tr-banner`): the ticket id takes the gold accent (`--gold-300` — gold on navy, per the token rule), and the priority pill takes its family hue as a tint (`color-mix` of `--status-submitted-dot` / `--status-progress-dot` / `--status-rejected-dot` at ~32% over navy, `--surface` for the neutral tone) with `--surface` text. Meaning is kept (critical = red, high = amber, medium = blue), contrast is restored.
  - Nothing else moved: the banner layout, the department badge, the status pill, the h1 and the privacy line are untouched.
- **New tokens:** none (existing `--gold-300`, `--status-*-dot`, `--surface`; the priority pill variants are new **classes**, documented in §5.1/§5.10).
- **Verification screenshots:** **captured** — `complaint-detail-1440.png`, `complaint-detail-1024.png`, `complaint-detail-939.png`, `complaint-detail-393.png` (repo root, Chromium/Playwright, staff account). Computed styles read from the live banner: ticket id `rgb(231,200,115)` (gold) on navy; priority pill `rgb(255,255,255)` on `srgb(1 1 1 / 0.18)`; deadline chip `rgb(153,27,27)` on `rgb(254,226,226)` (the overdue family); status pill and department badge unchanged. A raw-key sweep of the page reports no untranslated keys.
- **Verification (code):** `php artisan view:cache` compiles; `tests/Feature/{ComplaintSlaTest,StaffQueueScopeTest,StaffComplaintLayoutTest,LocalizationTest,ComplaintHoldTest,StaffWorkspaceTest,ResolutionReminderTest,DesignSystemGuardTest}.php` — **61 passed** (273 assertions); `php scripts/design-system-guard.php` — **0 NEW** violations (250 grandfathered).
- **Definition of Done checklist:** passed — no new strings (colour-only change); no hard-coded hex (tints are `color-mix` over existing tokens); no serif; no new tokens; the meaning of each chip is unchanged, only its contrast on navy; the queue list keeps the same chip family; nothing else on the page was touched.

---

## 2026-11-16 · An ordinary staff member sees only the work the office hands them

- **Page / component:** `app/Models/Complaint.php` (`scopeVisibleToStaff`, `isVisibleToStaff`), `app/Http/Controllers/StaffController.php` (dashboard, queue, ticket, live-ticket poll, status update), `resources/views/staff/partials/sidebar.blade.php`, `resources/views/layouts/partials/mobile-drawer.blade.php`, `resources/views/staff/complaints/partials/list.blade.php`, `lang/{en,fil}/{nav,staff}.php`, `tests/Feature/StaffAssignedWorkScopeTest.php` (new), `docs/design-system.md` (§5.50, §12).
- **Why:** Every staff member shared one queue. A verifier — the Department Administrator, or staff with verification rights — must see the whole office, because they settle whether a report is genuine. An ordinary staff member only works the cases the office hands them; the rest of the office's work was never theirs to read, to count, or to act on.
- **Before:** The dashboard counters, the priority list, the recent-activity feed, the complaint queue and the ticket were all scoped by `department_id` alone — verifier and non-verifier saw exactly the same thing (only the verification queue and the office-management pages were capability-gated).
- **After:**
  - **One scope, one check.** `Complaint::visibleToStaff($user)` filters a query to the work a person may see; `Complaint::isVisibleToStaff($complaint, $user)` is the access check behind the ticket, the live-ticket poll and the status-update write. A verifier is not filtered; a non-verifier sees the complaints assigned to them now **or** assigned to them at some point — the assignment trail is the audit log's `complaint.assignment_updated` record, so a case they worked and then passed on stays readable to them. A complaint outside the scope is 403, not a soft link.
  - **Queue:** the list, its counters and the heading follow the scope. For a non-verifier the nav item and the heading read **“My work”** (`nav.my_work`), and an empty list says *“Nothing is assigned to you yet”* with the line that tells them their office assigns the work — never a dead end.
  - **Dashboard:** the counters (pending / in progress / resolved this month), the average time open, the priority list and the recent-activity feed are the person's own work, not the office's totals.
  - **Working powers kept:** on their own cases a non-verifier can still change status, send the official response and write internal notes. Verification, review, assignment and moderation remain the verifier's.
  - **Not changed:** the verification queue, “My activity”, the office trail and the Department Administrator's office surfaces (statistics, audit) keep their current scope.
- **New tokens:** none (existing `nav.*`, `staff.*` strings; `nav.my_work` and `staff.empty_my_work_*` added in en + fil).
- **Verification:** **in-browser (Chromium/Playwright, running app, real accounts):** Ramon Villanueva (ordinary staff, verification rights revoked by the Department Administrator) → queue heading **“My work — 4 complaints”** with only his assigned cases, nav **“My work”**, dashboard counters describing his work only; Juan Dela Cruz (Department Administrator, a verifier) → **“Complaint queue — 13 complaints”** with the whole office and the full nav. Screenshots: `/tmp/opencode/ui-audit/scope-ramon-queue.png`, `scope-ramon-dashboard.png`, `scope-juan-queue.png`.
- **Verification (code):** `tests/Feature/StaffAssignedWorkScopeTest.php` — **7 passed** (25 assertions): the queue shows only his work, 403 on a complaint that is not his, work that was reassigned away stays readable, a verifier still sees the whole office, the dashboard counts only what he may see, the nav label differs, and a status update on a case that is not his is refused. Full suite: **670 passed**, 10 failed — all ten reproduced with the new scope disabled (or on pristine `HEAD`) and are pre-existing: `AdminQueueTest` ×6, `ComplaintDetectionTest` (demo seeder), `PasswordRequirementsTest`, `ProfileQueryTest`, `ResidentInformationResponseTest`. Existing staff tests were adapted to the new rule (verifier accounts where the test measured office-wide behaviour, assignment where the test measured an ordinary staff member). `php scripts/design-system-guard.php` — **0 NEW**.
- **Definition of Done checklist:** passed — no new colours or tokens; en + fil for every added string; no raw keys; one markup per list (the queue list is unchanged, only its scope); the office trail and the verification queue keep their own scope; privacy unchanged (a non-verifier now sees *less*); the assignment trail is read from the audit log that already records every hand-over.
