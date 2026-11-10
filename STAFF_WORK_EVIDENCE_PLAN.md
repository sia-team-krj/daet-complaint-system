# Evidence-Based Staff Action Workflow — Plan

**Date:** November 10, 2026
**Status:** PLANNING — no code written yet
**Scope:** the staff complaint action flow — verification → work start → completion → closure
**Supersedes:** `STAFF_DOCUMENTATION_PROOF_PLAN.md` (that plan's proof-photo feature is
still the intent; this plan says what evidence is required *when*, and rebuilds the
surface that was lost)

---

## 1. The requirement, restated

1. **Documentation is required once a complaint is verified.** After the department
   settles a report as genuine, every further action on it must be evidenced.
2. **Verification alone does not move the ticket.** A status update must follow the
   verification decision.
3. **Verification obliges the office to start the work.** A verified complaint cannot
   sit idle — the office must move it into "work in progress" under a deadline.
4. **Labor work demands evidence that the work is being done.** A photo of the site
   with the work underway, not a sentence saying it is.
5. **No free-message updates for work that needs labor.** "Sir, we have fixed it" is
   not an acceptable action on a repair ticket. The system must refuse it.
6. **Reports that are not labor work get a lighter path** — but still a documented one.

Requirement 5 is the heart of it. Today the staff form *is* a free-message box with a
status dropdown, and the only thing standing between a ticket and a bare status flip
is a photo upload the UI no longer renders.

---

## 2. Baseline: what exists, what is broken

| # | Capability | State |
|---|---|---|
| 1 | Photo requirement on `In Progress` / `Resolved` | **Enforced in code** (`StaffController.php:638-654`), but **the upload UI was deleted** in commit `7f4a842` — staff cannot complete the action at all |
| 2 | Photo storage (MinIO), immutability, streaming routes | Working |
| 3 | Review decision (verified / rejected / priority / method / notes) | Working |
| 4 | Separation of duties — the assigned worker cannot verify their own assignment | Working (`ComplaintVerificationService::authorizeNoSelfConflict`) |
| 5 | Ask-the-resident-for-evidence + delegation | Backend + routes exist, **no UI** (0 views) |
| 6 | Moderation panel | Backend + routes exist, **no UI** (0 views) |
| 7 | Admin path | **Bypasses all proof** — an admin can mark `Resolved` with no photo and no note |
| 8 | GPS on staff proof photos | **Does not exist.** `PhotoLocationService` is wired only into resident filing |
| 9 | Structured work description | **Does not exist** — one free-text `note`, min 10 chars |
| 10 | Public attribution | **Department only** on the resident timeline; **none** on the public transparency timeline |
| 11 | Deadline to start work | **Does not exist** — a verified ticket can sit forever |
| 12 | Resident-facing status labels | **Raw enum values leak** (`in_progress → resolved`) |

Baseline test state: **452 Feature tests, 1 error, 21 failures** — 9 of them on the
staff complaint page, all traceable to the Nov 9 rewrite.

---

## 3. The core model: a work execution mode per category

The repo already has the exact pattern to copy: `config/complaint_intake_policy.php`
classifies each category by what the *resident* must supply at filing. The same idea
applies to what the *office* must supply at each action.

**New:** `config/complaint_work_policy.php` + an `App\Enums\WorkExecutionMode` enum.

### Modes

| Mode | Meaning | Evidence duty |
|---|---|---|
| `labor_required` | Physical remediation — the office must physically work on site | Photo evidence **mandatory** at verification (if site visit), work start, and completion |
| `report_only` | Enforcement, regulatory or desk work — there is no site work to photograph | A written, structured record **mandatory**; site photos optional |
| `referral` | Handed to another body (PNOC, BFP, DPWH, private utility) | A documented referral with the receiving party **mandatory**; no work photos |

### Default category mapping (admin-overridable)

| Category | Mode | Reasoning |
|---|---|---|
| `road_damage` | `labor_required` | Patch/repair is physical work |
| `flooding` | `labor_required` | Clearing, dredging, drainage work |
| `streetlight` | `labor_required` | Pole/equipment work — though see §11 D3 |
| `garbage` | `labor_required` | Collection and disposal |
| `sanitation` | `labor_required` | Same |
| `park_maintenance` | `labor_required` | Same |
| `stray_animals` | `labor_required` | Capture and removal is physical work |
| `noise_complaint` | `report_only` | Enforcement action (citation, warning) — nothing to repair |
| `business_permit` | `report_only` | Regulatory review — nothing to repair |
| `others` | `report_only` | Default; admin flips per complaint if it turns out to need labor |

The mapping is **snapshotted onto the complaint** at filing (`complaint.work_mode`,
mirroring the existing `complaint.intake_mode`), so changing the policy later never
retroactively changes the rules on a ticket already in flight.

> **Why snapshot it:** a ticket filed in November must be judged by the rules the
> office knew in November. Otherwise an admin edits a setting and every open ticket
> silently changes under the staff working it — the exact drift the intake policy
> was built to prevent.

---

## 4. The evidence ladder — what each action demands

The transition rules live in **one** service, mirroring `ComplaintVerificationService`:
*"The one place the rules about settling a complaint live."* New:
`App\Services\ComplaintWorkService`.

| Action | Trigger | `labor_required` | `report_only` | `referral` |
|---|---|---|---|---|
| **Verify** | review → `verified` | verification method + **≥1 site photo if `site_visit`** + review notes + confirmed priority | same, photo optional | same |
| **Start work** | status → `In Progress` | **≥1 work-start photo with location** + work record (summary, crew, date on site, site confirmed) | work record (summary, date handled) | referral note (party, date, reference no.) |
| **Complete work** | status → `Resolved` | **≥1 completion photo with location** + work record (summary, materials used, crew, hours, date completed) | work record (summary, outcome) | referral outcome |
| **Reject** | status → `Rejected` | reason (min 20 chars) | same | same |
| **Close** | resident confirms / auto-close | no action | no action | no action |

### The rule that implements requirement 5

> **A status change is refused server-side unless the evidence bundle for that
> transition is complete. Free text alone is never sufficient.**

Concretely, `POST /staff/complaints/{c}` with `status=in_progress` and a 400-character
note and no photo returns a validation error naming the missing item. There is no
"add a note instead" escape hatch, because the note was never the thing being
questioned — the *evidence* is.

**Where the rule is enforced:** `ComplaintWorkService::assertRequirementsMet()`,
called from `StaffController::update()`, `AdminController::complaintUpdate()` and
`StaffController::review()`. One implementation, three call sites — the admin path
stops being a bypass because it calls the same code.

---

## 5. Data model

### 5.1 New table `complaint_evidences` (immutable, one row per photo)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `complaint_id` | FK → complaints | cascade |
| `log_id` | FK → complaint_logs | the update this photo belongs to |
| `stage` | string(24) | `verification` · `work_start` · `completion` · `supporting` |
| `path` | string | MinIO key — the file itself |
| `caption` | string(200) nullable | "Crew patching the shoulder at KM 2" |
| `latitude` / `longitude` | decimal(8) nullable | per-photo, from EXIF or a manual pin |
| `location_source` | string(16) | `exif` · `manual` · `none` |
| `captured_at` | timestamp nullable | EXIF `DateTimeOriginal`, not upload time |
| `uploaded_by` | FK → users | who took it |
| `created_at` | timestamp | |

**Why a table and not more JSON on `complaint_logs`:** the location belongs to *the
photo*, not to the update. Five photos can be taken at five places. A JSON blob on the
log cannot carry one location per photo, cannot be queried for "was any evidence
taken near the reported site", and cannot be indexed.

### 5.2 `complaint_logs` — two new columns

| Column | Type | Notes |
|---|---|---|
| `action_type` | string(24) nullable | `verify` · `start_work` · `complete_work` · `reject` · `note` |
| `work_record` | json nullable | the structured record (see §8.4) |

The timeline table stays the single immutable spine of the product. Evidence is a
satellite of it, not a replacement.

### 5.3 `complaint_logs.documentation_paths` → deprecated

Backfill existing rows into `complaint_evidences` (stage inferred from the log's
`new_status`), then point the two documentation streaming routes
(`complaints.updates.evidence` and `transparency.complaints.updates.evidence`) at
`complaint_evidences` ordered by id. **The URL contract `/{log}/{index}` does not
change** — index resolves to the nth evidence row of that log, which is stable.
`complaints.evidence` is untouched: it serves the resident's own report photos, not
staff documentation.

### 5.4 `complaints` — one new column

| Column | Type | Notes |
|---|---|---|
| `work_mode` | string(32) nullable | snapshot of the policy mode at filing |

### 5.5 System settings (admin-editable, `system_settings`)

| Key | Type | Default | Purpose |
|---|---|---|---|
| `work_mode_overrides` | json | `{}` | per-category mode overrides, admin editable |
| `staff_public_attribution` | `name` \| `office` | `name` | who residents see as the actor (§8.5) |
| `sla_start_work_days` | int | `3` | days from verification to work start |
| `sla_resolve_days` | int | `14` | days from verification to resolution |
| `require_verification_photo` | bool | `true` | force a photo on `site_visit` verification |
| `require_second_signoff` | bool | `false` | completion must be signed off by someone other than the worker (§11 D4) |

---

## 6. The service

`App\Services\ComplaintWorkService` — the single authority for what an action needs
and what it produces.

```php
requirementsFor(Complaint $complaint, ComplaintStatus $from, ComplaintStatus $to): ActionRequirements
assertRequirementsMet(Complaint $complaint, ActionRequirements $r, array $input): void
recordAction(Complaint $complaint, User $actor, ActionRequirements $r, array $input): ComplaintLog
```

`ActionRequirements` is a small value object: `requiresPhoto`, `minPhotos`,
`requiresLocation`, `requiredFields`, `mode`. The Blade form and the validator read
the *same* object — so the checklist the staff sees is the checklist the server
enforces, and they cannot drift.

**Why a service and not controller logic:** the admin path, the staff path and any
future field-worker path must agree exactly. One service is the only way to guarantee
that. This is the repo's own precedent, twice over
(`ComplaintVerificationService`, `DepartmentRouter`).

---

## 7. Enforcement points

| Endpoint | Change |
|---|---|
| `PUT /staff/complaints/{c}` (`StaffController::update`) | Delegates entirely to `ComplaintWorkService`; the ad-hoc documentation logic is removed |
| `PUT /admin/complaints/{c}` (`AdminController::complaintUpdate`) | Same service — **closes the admin bypass** |
| `PUT /staff/complaints/{c}/review` (`StaffController::review`) | Verification evidence per §4 |
| `POST /staff/complaints/{c}/verification/request-information` | Unchanged, but reattached to the UI |
| `POST /staff/complaints/{c}/moderation` | Unchanged, reattached to the UI |

**Rejection paths must be honest.** When the action is refused, the resident is not
notified and no timeline entry is written. A refused action leaves no trace on the
public timeline, because nothing happened.

---

## 8. The interface

### 8.1 The staff complaint page rebuild

Shell C (detail page) per §10 of the design system. Three zones:

- **Main column** — the report as filed: description, site map, resident evidence
  (read-only), verification decision, and the **action timeline**
- **Action rail** — the single place staff act: **the Next Action panel**
- **Header** — ticket, status, priority, department, and the **SLA chip**

The Nov 9 page is rebuilt rather than patched, because the surface was already
carrying four orphaned panels (moderation, ask-for-evidence, delegation, internal
note) and now also needs the evidence uploader.

### 8.2 The Next Action panel — `.work-action` (new component)

The core of the redesign. It answers one question for a staff member who has just
opened a ticket: *what does my office owe this report right now, and what do I have
to attach to close it out?*

Anatomy:

- kicker — "Action required" / "Recorded" / "Nothing owed"
- title — "Start the work" · "Record completion" · "Verify the report" ·
  "Refer to another office"
- one-line explanation of *why* (e.g. "This report was verified on Nov 8. Labor work
  needs a photo of the site before it can move to In Progress.")
- **requirement checklist** — one row per requirement: ✓ met / ○ outstanding, each
  with the label and a one-line helper
- the contextual form (fields + evidence capture)
- **one** primary submit, disabled with an inline explanation while anything is
  outstanding

States: `required` (form shown) · `ready` (all requirements met, submit live) ·
`recorded` (the action is done; show the stored record instead of the form) ·
`not_applicable` (report-only mode, no evidence needed) · `blocked` (an earlier
action is outstanding — e.g. verified but work not started, so completion cannot be
recorded yet).

**The checklist and the validator read the same `ActionRequirements` object.** That
is the whole trick: the staff member can never satisfy the form and still be
refused, and can never be refused for something the form did not ask for.

### 8.3 Evidence capture — `.evidence-capture` (new component)

Rebuild of the component lost in `7f4a842`, plus per-photo metadata:

- two sources, as the old one had: **Photo library** (gallery) and **Use camera**
  (`capture="environment"`)
- multi-select, instant previews, per-photo remove before submission
- **a location chip per photo**: `GPS found` (green) / `No GPS — tap to place`
  (amber) / `Placed manually`
- tapping a photo without GPS opens the map pin step, pre-seeded with the
  complaint's reported location
- an optional caption per photo (≤200 chars) — "crew patching the shoulder"
- max 5 photos per action, per the existing rule

**The camera/gallery pattern already exists in the complaint wizard**
(`resources/views/complaints/create.blade.php`). It is extracted into a shared
partial and reused — never forked. Design-system rule §4.4: *"Never fork the shared
filter bar or report-row component; extend them."*

### 8.4 The structured work record — `.work-record` (new component)

Replaces the free-message box. Fields vary by action and by mode:

| Field | Labor | Report | Referral |
|---|---|---|---|
| What was done (summary, 20–500 chars) | ✓ | ✓ | ✓ |
| Crew on site (number) | ✓ | | |
| Equipment / materials used | ✓ | | |
| Date on site | ✓ | ✓ | |
| Hours worked | ✓ | | |
| Site location confirmed | ✓ | | |
| Referred to (party) | | | ✓ |
| Referral reference number | | | ✓ |

All labels come from `work.*` lang keys in **en + fil**. The summary field is where
the old free text goes — it is one field among several, required, and it cannot
substitute for the missing photo. That is what "no free message" means in practice:
the message stops being the action.

### 8.5 The resident and public timelines

Each public update becomes a **record card**, not a paragraph:

- status pill + date (translated labels — fixes the raw `in_progress` leak)
- **who acted** — staff member's name and office, or office only, per
  `staff_public_attribution`
- the structured work summary
- the evidence grid (§5.21) with a GPS chip on each photo

The public transparency timeline shows the same record, minus anything the office
marks internal. The resident timeline is the same card with the resident's own
controls.

**Attribution is a policy decision, so it becomes a setting** rather than an
accident of which field the template happens to print. The current state — department
on one page, nobody on another — is not a decision, it is drift.

### 8.6 The staff queue

- a new **"Action owed"** column: the next action, in words ("Start work", "Record
  completion", "Verify")
- an **SLA chip**: time left before the office is overdue — green → amber → red
- a filter chip: **Overdue**

### 8.7 The SLA clock (requirement 3)

The clock starts the moment `review_status` becomes `verified` — that is the moment
the office has accepted the report and owes the resident work.

| Deadline | Default | Measured from | Ends at |
|---|---|---|---|
| Work must start | 3 days | verification | status reaches `In Progress` |
| Work must finish | 14 days | verification | status reaches `Resolved` |

Overdue surfaces in three places: the staff queue chip (amber/red), the admin
dashboard (a department-level "overdue work" figure), and a notification to the
department's supervisors and to admins.

**A verified ticket that nobody has started is the failure mode this whole plan
exists to prevent.** Right now nothing in the system notices it.

### 8.8 Admin parity

The admin complaint detail gets the same Next Action panel, the same evidence
capture, and the same validation. An admin can no longer produce a resolution that
the staff path could not.

---

## 9. Design system additions

Per AGENTS.md, nothing lands undocumented. Four new entries in §5 of
`docs/design-system.md`, plus a `/design-system` demo for each:

| # | Component | Class |
|---|---|---|
| §5.36 | Next action panel | `.work-action` |
| §5.37 | Evidence capture with GPS chips | `.evidence-capture` |
| §5.38 | Structured work record display | `.work-record` |
| §5.39 | SLA / deadline chip | `.sla-chip` |

**No new colours.** Everything binds to the existing `--status-*`, `--navy-*`,
`--gold-*` tokens. The mode and stage indicators use the existing status families:
`--status-progress-*` for "work in progress", `--status-resolved-*` for "complete",
`--status-review-*` for "verification".

---

## 10. Rollout

| Phase | What | Unblocks |
|---|---|---|
| **0** | Restore the deleted proof-photo form to the staff page | Staff can complete a ticket *today*. Closes the `StaffDocumentationProofTest` failure. **Delivered 2026-11-10** — see §10.1. |
| **1** | `WorkExecutionMode` enum, `config/complaint_work_policy.php`, `complaint_evidences` migration, `ComplaintWorkService` | The rule engine exists, server-side. **Delivered 2026-11-10** — see §10.2 and §10.3. |
| **2** | Next Action panel + evidence capture + work record UI | The staff experience exists |
| **3** | Admin parity; SLA clock + queue column + overdue filter | Admin stops being a bypass; idle verified tickets become visible. **Delivered 2026-11-10** — see §10.4. |
| **4** | Resident + public timeline record cards; attribution setting | "Who did this" becomes answerable |
| **5** | Referral mode; second-signoff option; data migration off `documentation_paths` | The long tail |

### Phase 0 — restoring the proof form (the confirmed first delivery)

**Why it stands alone.** A department that cannot attach a photo cannot move a ticket
past `Under Review` at all. This is a restoration of a working feature that a
refactor deleted, not a new design — so it carries no redesign risk.

**What was lost, and where it lives.** The whole capture block was removed by commit
`7f4a842` from `resources/views/staff/complaints/show.blade.php`. It is fully
recoverable at `git show bfcf2b7:resources/views/staff/complaints/show.blade.php`
(markup at lines ~1214-1253, CSS at ~419-578, JS at ~1500-1600). **The old CSS is
not copied verbatim** — it carries hard-coded hex values that the design-system guard
(`scripts/design-system-guard.php`) fails on. It is rebuilt against tokens.

**Scope of Phase 0:**

1. **Evidence capture block** in the "Add official response" form:
   - `documentation_images[]` — gallery, `multiple`, `accept="image/jpeg,image/png,image/webp"`
   - `documentation_camera_photos[]` — camera, `capture="environment"`, `multiple`
   - `.documentation-previews` — instant previews, per-photo remove button
   - a `0 / 5` selected counter with `aria-live="polite"`
   - a client-side guard against a sixth selection
   - both inputs are hidden behind visible buttons (`data-documentation-trigger`), so
     the form is keyboard- and screen-reader-reachable
2. **The requirement stated in the form**, not only in the error: for `In Progress`
   and `Resolved`, the panel says a photo is required and names the two statuses that
   demand it. Copy from `work.*` lang keys in **en + fil**.
3. **Server-side** — already in place at `StaffController.php:638-654`. Phase 0
   changes no controller logic; it restores the input the controller already
   requires.
4. **Design system** — a new §5 entry for the capture block (`.documentation-*`,
   token-based) with a `/design-system` demo, plus the `docs/design-changelog.md`
   entry the AGENTS.md requires.
5. **Tests** — `StaffDocumentationProofTest::test_staff_response_form_exposes_gallery_camera_and_preview_controls`
   goes green again; the five behavioural tests in that file must stay green
   (they prove the server rule was never the thing that broke).
6. **Guard** — `php scripts/design-system-guard.php` reports no new violations in the
   touched files.

**Explicitly out of scope for Phase 0** (they stay broken, and are named in the plan
so they are not forgotten): the moderation panel, the ask-for-evidence panel, the
verification delegation panel, the internal-note input, the admin bypass, the missing
attribution, and the SLA clock. Each has a home in Phases 2-4.

**One open risk, stated plainly:** restoring the form makes the *current* rule
usable — a photo is required for `In Progress` and `Resolved`, and nowhere else. That
is a smaller rule than the one this plan eventually lands. Phase 0 is deliberately
that small: it unblocks the department today without pretending the evidence ladder
exists.

### 10.1 Phase 0 as delivered (2026-11-10)

Phase 0 was **built from the design system**, not recovered from the deleted markup:
the old block's CSS carried hard-coded hex values the guard fails on, and its
`data-documentation-*` contract was the deleted component's private vocabulary.

What shipped:

| Piece | Where |
|---|---|
| The component | `resources/views/components/proof-capture.blade.php` — the picker **and** the rule stated above it |
| Its styles | `resources/css/pages/staff-complaint.css` (tokens only, no new colours) |
| Its controller | `resources/js/proof-capture.js` — binds to the markup, staff-only |
| The rule, stated | Driven by the status select: neutral while the chosen status needs no photo, amber action-needed once it does, naming that status |
| The guide | `docs/design-system.md` §5.36 (anatomy, states, the why) + §12 changelog |
| The demo | `/design-system` → "Proof capture (staff)", live |
| The tests | `tests/Feature/StaffDocumentationProofTest.php` — 8 passed, 40 assertions |

Two fixes came with it, both on the staff page:

- **The status dropdown offered transitions the server refuses.** It listed every
  status except the current one, so its default option was a move the server would
  reject with *"Invalid status transition"*. It now offers exactly the complaint's
  legal transitions (`allowedTransitions()`), so the default option is a move that
  works — and, for a complaint under review, that default is `In Progress`, which is
  precisely the status the proof note describes.
- **The dropdown had no `id`**, so the proof note could not be tied to it.

Verified: the full Feature suite is **454 tests, 1 error, 20 failures** — one
failure fewer than the 21-failure baseline, none added. `npm run build` and
`php artisan view:cache` both clean. The design-system guard reports the same 302
violations (243 grandfathered, 59 NEW) before and after, so the three new files add
none.

**Resident views were not touched.** The filing wizard keeps its own picker; the two
share the portal's tokens and visual language, not their markup. The shared files
(`app.css`, `app.js`) gained one import line each and nothing else.

### 10.2 Phase 1, part delivered (2026-11-10) — the rule engine

The engine exists and is enforced. What landed:

| Piece | Where |
|---|---|
| The kinds of work | `app/Enums/WorkExecutionMode.php` — `labor_required` · `report_only` · `referral` |
| Which kind a category is | `config/complaint_work_policy.php` — the mirror of `config/complaint_intake_policy.php` |
| The one authority | `app/Services/ComplaintWorkService.php` — the mode, the freeze, the refusal, the photo collection |
| The freeze | `complaints.work_mode` (new column) — settled the first time anyone acts on the ticket |
| Staff path | `StaffController@update` now calls the service; the rule is category-aware |
| Admin path | `AdminController@complaintUpdate` calls the **same** service — **the bypass is closed** |
| The form | Both the staff and the admin page carry the proof capture and state the kind of work |
| The tests | `tests/Feature/ComplaintWorkProofTest.php` — 11 passed / 39 assertions |

The rule now reads: **a repair is proven by a photograph of it, and a free message
is never a substitute for one** — and it says so in the refusal (*"This is labor
work. Attach at least one photo of the work before marking this complaint as in
progress."*). A report-only complaint is no longer asked for a photograph it
cannot produce.

**Still open in Phase 1:** the per-photo caption (a text field beside each
photograph in the picker) and a manual location for photographs whose camera
stripped the GPS. Both are already carried by the table — `caption` and
`location_source = manual` — they just have no input yet.

**Carried forward from the plan and not yet built:** the structured work record
(fields for crew, materials, hours), the per-complaint mode override **UI** (the
service supports it and it is audited, but no control sets it yet), and the SLA
clock.

### 10.3 Phase 1, second slice delivered (2026-11-10) — the proof carries its place

A photograph of the work was being stored as a bare path. Nothing said where it
was taken, when, or who attached it — so *"the crew was at the reported site"*
was a claim the record could not be checked against. And the staff page did not
even show the photographs it had been given: the November rewrite took the proof
out of the timeline, so an office could attach a photograph and never see it
again on the screen where it would have to explain itself.

What landed:

| Piece | Where |
|---|---|
| One row per photograph | `complaint_evidences` — location, capture time, who attached it, which part of the work it proves |
| Reading the camera's record | `PhotoLocationService::inspect()` — GPS and capture time out of the EXIF |
| Storing it | `ComplaintWorkService::storeEvidence()` / `attachEvidence()` — shared by the staff and admin paths |
| Showing it | §5.37 "Proof in the timeline" — the staff timeline shows each photograph with its coordinates; the admin audit timeline carries them on its evidence card |
| Saying what is missing | A photograph with no location reads **"No location recorded"** rather than standing in for proof of somewhere |
| The backfill | The migration gives every photograph already on a log a row of its own, so no existing proof is invisible |
| The tests | `ComplaintEvidenceTest` (8) + `PhotoLocationServiceTest` (4) — 12 passed / 37 assertions |

The EXIF reader is exercised against a **real photograph**, not a mock: the test
builds a 1×1 JPEG carrying a GPS EXIF segment and reads the position back out
of it, so the test fails if the reader breaks.

**Deliberately unchanged:** the resident timeline. A resident is shown the
photograph and told the work was done; the exact position of a municipal work
site is not theirs to need. Public coordinates are a decision that belongs with
the attribution work (Phase 4), not a by-product of storing them.

### 10.4 Phase 3 delivered (2026-11-10) — the clock on the work a department owes

The requirement — *"after verification, the office has to start the process of
fixing the complaint"* — was enforced by the status ladder and by nothing else.
A report filed a month ago and verified yesterday looked identical to one
verified a month ago and never touched, because the queue's age chip measured
the age of the **complaint**, not whether the office had done anything with it.

What landed:

| Piece | Where |
|---|---|
| The deadlines | `ComplaintSlaService` — work must start (3 days, 1 for critical) and work must finish (14 days), per priority, admin-editable |
| When the clock starts | `complaints.verified_at` — set once, on the first verification. `reviewed_at` moves on every review decision and cannot say when the office took ownership |
| The chip | §5.38 "Deadline chip" — the action owed and the time left, in the status family the meaning belongs to |
| Where it shows | Staff queue row · staff complaint banner · admin dashboard department card |
| The filter | An **Overdue** filter on the staff queue, selecting by the clock rather than by a plain interval |
| The telling | `complaints:flag-overdue-work`, daily at 07:45 — the office that owes the work and the administrators, **once each** |
| The tests | `ComplaintSlaTest` — 13 passed / 42 assertions |

**Why the clock does not restart:** a later re-review does not clear the wait
the resident has already served. `verified_at` is written once and never
moved, so a report that goes verified → needs information → verified keeps the
clock it started with.

**A badge that cries wolf is not a safety net either.** The sweep raises each
complaint once, keyed on its ticket id in the payload — the subject stays the
complaint so the link lands correctly, but the sweep recognises it already
warned about this one.


---

## 11. Acceptance criteria

1. A staff member cannot move a verified complaint to `In Progress` without the
   evidence bundle for that transition. The refusal names what is missing.
2. A staff member **cannot** move a labor-mode complaint to `In Progress` or
   `Resolved` with a text message and no photos. There is no override path.
3. A `report_only` complaint *can* be progressed with a written record and no photo.
4. Every stored evidence photo carries a location — from EXIF or a manual pin — and
   the timeline shows it.
5. A staff member cannot mark `Resolved` on a complaint whose work has not been
   started. The action ladder is enforced, not just the photo rule.
6. A verified complaint that has not started work within the SLA window is visible
   as overdue in the staff queue and the admin dashboard.
7. An admin performing a status change is subject to exactly the same rules as staff.
8. The resident timeline shows the actor (per the attribution setting), the
   structured summary, the evidence, and translated status labels — never a raw
   enum value.
9. Every action produces exactly one immutable `complaint_logs` row, with the actor
   id, the action type, the work record, and the evidence attached.
10. The staff page renders with JavaScript off, and every action is reachable
    without it.

---

## 12. Test plan

| Area | Tests |
|---|---|
| Rules | `ComplaintWorkServiceTest` — every (mode × transition) pair, plus the refusal messages |
| Evidence | storage, EXIF location, manual pin, caption, per-photo location independence, deletion on failed transaction |
| Ladder | work-start-before-completion, reject paths, the no-override guarantee |
| SLA | clock start, overdue calculation, the queue filter |
| Attribution | name vs office setting, on resident and public timelines |
| Permissions | admin parity, department scoping, separation of duties |
| UI | the Next Action panel renders the same checklist the server enforces |
| Regression | the 21 currently-failing tests, plus the full suite green |

---

## 13. Risks

| Risk | Mitigation |
|---|---|
| Staff resistance to a blocked status dropdown | The checklist explains *why*; the restore (Phase 0) means nothing regresses |
| Small departments (1–2 staff) cannot satisfy a second-signoff rule | `require_second_signoff` defaults **off**; decided per LGU |
| EXIF stripped by messaging apps | Manual pin fallback; the location chip makes the absence visible rather than silent |
| The policy changes mid-flight | The mode is snapshotted onto the complaint |
| Two sources of truth for photos | `documentation_paths` is backfilled and deprecated in one phase, not left to drift |

---

## 14. Decisions

**Confirmed with the owner, November 10, 2026:**

| # | Decision | Outcome |
|---|---|---|
| **D1** | Public attribution | **Name + office, admin-toggleable.** The timeline shows "Juan Dela Cruz · Engineering Office". `staff_public_attribution` switches the portal to office-only. |
| **D3** | Work-mode granularity | **Per category by default; the office may override per complaint.** An override is recorded in the audit trail and shown on the staff page. |
| **D6** | First delivery | **Phase 0 only — restore the proof form.** A restoration, not a redesign. The engine follows as a separate reviewed change. |

**Assumed defaults (stated, not asked — say the word if any is wrong):**

| # | Decision | Default |
|---|---|---|
| **D2** | Photo location | **Both.** EXIF when the photo carries it, a manual pin when it does not, and the chip always shows which of the two it is. |
| **D4** | Second sign-off | **Capability ships disabled.** `require_second_signoff` defaults `false`; the LGU turns it on. |
| **D5** | Roles | **Keep three roles** (`citizen`, `staff`, `admin`) for this cycle. The existing `can_verify` flag is the approver/worker split. Building the `worker` role `PRODUCT.md` describes is its own programme, not this one. |

---

## 15. Documentation obligations

Every phase that touches `resources/views`, `resources/css`, `resources/js` or `lang`
adds an entry to `docs/design-changelog.md` in the same commit, and a component
entry to `docs/design-system.md` §5 with a `/design-system` demo. Definition of Done
(§11) is printed as a checklist in the PR description.
