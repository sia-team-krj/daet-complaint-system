# Guest Complaint Tracking — Plan

**Status:** planning (not yet implemented)
**Goal:** anyone holding a ticket number can see a complaint's progress without an account, and nothing more.

**Decisions (from the user):**

1. A guest sees the current status, the 5-step progress trail with dates, the office handling it, filed/last-updated dates, any hold notice, and timeline entries already marked public.
2. Lookup requires the ticket number only. No email, no second factor.
3. A signed-in owner/staff/admin who is allowed to view the full complaint is sent to the existing full ticket page; everyone else sees the limited progress page.

---

## 1. What exists today

- `GET /track` already exists, but it is **inside the authenticated group** (`routes/web.php:218`). A guest is redirected to login.
- `ComplaintController@track()` does a ticket lookup and then calls `$this->authorize('view', $complaint)`, returning the **full** `complaints.show` page. That page contains the title, description, photos, exact location, resident controls, and internal-facing metadata.
- `resources/views/complaints/track.blade.php` is a standalone page with inline CSS, hard-coded colours, and a "Back to My Complaints" link. It was written before the current design system and is one of the grandfathered files in the design-system guard allowlist.
- The legal documents already promise account-free tracking:
  - Terms §06: "it is what lets you track a report without an account."
  - Privacy policy: "Any visitor sees only what you have chosen to publish."
  - Both still say the ticket prefix is `DLT-`, but the model generates `COMP-`. That is a live inconsistency.
- Ticket IDs are already random (`COMP-2026-A7K9P`, 5 chars from a 32-char unambiguous alphabet), so enumeration is already expensive.
- The public transparency detail page already has the exact progress stepper, update timeline, hold notice, and "what this means" copy this feature needs. The resident detail page duplicates the same progress computation.

**The gap:** the product and the legal copy promise account-free progress tracking, but the only account-free ticket view is the public transparency page — and that only works for a complaint the resident has published and the office has verified. A private complaint cannot be tracked at all without logging in.

---

## 2. Product contract

### Shown to a guest

| Field | Why |
|---|---|
| Ticket ID | The thing they typed; confirms the right record. |
| Current status + status pill | The core answer. |
| 5-step progress trail + dates | "Where is it now?" and "when did it get there?" |
| Office handling it (or "Department pending") | "Who has it?" |
| Filed date, last-updated date | Context; no personal data. |
| Hold notice + public hold note + expected resume date | A hold is progress, and silence looks like neglect. |
| Timeline of **public** status updates (`is_internal = false`), with dates and public comments | The "what has happened" trail. |
| A neutral "waiting on the resident" notice when the office has an open information request | Explains a stall without showing the question. |
| A generic "sign in for the full report" CTA | The full record still belongs to the owner. |

### Never shown to a guest

- Title, description, category.
- Evidence photos, documentation photos, photo GPS.
- Exact address, coordinates, map, barangay.
- Resident name, display alias, email, phone, user ID.
- Internal logs (`is_internal = true`), internal notes, `review_notes`, staff names, assignment names.
- Priority, review status, spam/moderation flags, publication state.
- Feedback, ratings, visibility controls, information-request question text, reply form.
- Any staff action or admin control.

**Rule of thumb:** if it is not needed to answer "what stage is this at, and who has it?", it does not belong on the guest page.

---

## 3. Route and controller

### Route

Move `GET /track` out of the authenticated group and into the public section of `routes/web.php`. Keep the name `complaints.track` so the existing home hero, legal pages, dashboard form, and bookmarks keep working.

```php
Route::get('/track', [ComplaintController::class, 'track'])
    ->name('complaints.track')
    ->middleware('throttle:20,1');
```

- 20 lookups per minute per IP. That is far more than a person needs and still makes a 33.5M-space enumeration impractical from one address.
- The throttle applies to the whole page (including an empty form submission). That is acceptable and keeps the route declaration simple.
- No new table, column, or migration.

### Controller

Rewrite `ComplaintController@track()`:

1. Normalize the query value: `trim`, uppercase, strip everything that is not `A–Z` or `0–9`, cap at 30 characters. Do not reject by a strict format regex — legacy numeric tickets (`COMP-2026-00001`) and test/custom ids still exist. Because punctuation is stripped from both sides, `COMP-2026-A7K9P`, `COMP2026A7K9P`, `comp 2026 a7k9p` and `COMP–2026–A7K9P` (en/em dash) all resolve to the same record.
2. If the normalized value is empty, return the lookup view.
3. Look up through one model finder so normalization lives in a single place:

   ```php
   // app/Models/Complaint.php
   public static function findByTicketId(string $input): ?self
   {
       $normalized = substr(
           strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($input))),
           0,
           30
       );

       if ($normalized === '') {
           return null;
       }

       return static::query()
           ->whereRaw(
               "UPPER(REGEXP_REPLACE(ticket_id, '[^A-Za-z0-9]', '', 'g')) = ?",
               [$normalized]
           )
           ->whereNull('spam_confirmed_at')
           ->with('department')
           ->first();
   }
   ```

   - PostgreSQL-specific, which the project already requires.
   - This is a sequential scan; acceptable at LGU volume. If the table ever reaches six figures, add a stored normalized column + index in a later migration.
   - Generated tickets are canonical, so two tickets cannot normalize to the same value in practice. If custom ids ever become user-supplied, add a normalized unique constraint.
   - Soft-deleted rows are excluded by the default scope.
   - A complaint the admin confirmed as spam is quarantined; treat it as not found.
4. If found, resolve the full-ticket URL for the current user (see below). If there is one, redirect there.
5. Otherwise load the public timeline and return the limited view:
   - `$updates = $complaint->logs()->public()->reorder()->latest('created_at')->get();`
   - `$steps = ComplaintProgress::steps($complaint, $updates);`
6. If not found, return the same view with a generic "No complaint matches that ticket number" message. Do not echo the submitted value in the error copy, and do not distinguish "unknown" from "invalid format".

### Full-ticket redirect rule

Only redirect when the person is actually allowed to see the full record **and** can reach the target route:

| Viewer | Target | Condition |
|---|---|---|
| Admin | `admin.complaints.show` | Always (admin policy). |
| Staff / Department Administrator | `staff.complaints.show` | `Complaint::isVisibleToStaff($complaint, $user)` and the office matches. |
| Citizen owner | `complaints.show` | `$complaint->user_id === $user->id` and `$user->hasVerifiedEmail()` and `! $user->must_change_password`. |
| Anyone else | — | Limited progress view. |

The email/password checks matter because `complaints.show` sits behind `email.verified` and `force.password.change`. Without them, an unverified owner would be redirected away from the very progress page they are allowed to see.

### Response headers for the limited result

The ticket sits in the URL. Set these on the view response:

```php
->withHeaders([
    'Cache-Control' => 'no-store, private',
    'Referrer-Policy' => 'no-referrer',
    'X-Robots-Tag' => 'noindex, nofollow',
]);
```

Also push `<meta name="robots" content="noindex, nofollow">` into the page head. The ticket is shareable by design, but it must not be indexed or leak through a referrer.

---

## 4. One progress component, not a third copy

Today the progress calculation exists twice:

- `App\Http\Controllers\TransparencyController::progressSteps()` (private method), and
- an inline `@php` block in `resources/views/complaints/show.blade.php`.

The markup is also duplicated in both views. Adding a third copy for guest tracking would make the three pages drift. Extract instead:

### `app/Support/ComplaintProgress.php`

A small pure helper:

```php
final class ComplaintProgress
{
    /** @return array<int, array{status:string,label:string,date:string|null,state:string}> */
    public static function steps(Complaint $complaint, Collection $logs): array;

    public static function whatMeans(Complaint $complaint): string;
}
```

- Sorts logs newest-first internally so callers cannot get the order wrong.
- Uses the complaint's `created_at` for the Submitted step and the matching log date for the others.
- Keeps the existing complete/current/upcoming/rejected/stopped states.
- Uses `translatedFormat('M j, Y')` so dates follow the active locale.
- Reuses the existing `transparency.show.what_*` copy for the "What this means" note.

**Pre-existing bug to fix while extracting:** the rejected branch currently uses `->reject(...)->last()` on a newest-first collection, which returns the **oldest** log. It should use the rejection log's `previous_status` (or the newest non-rejected log) to decide where the trail stopped. Add a test for a complaint that reached In Progress and was then rejected.

### `resources/views/partials/complaint-steps.blade.php`

The `<ol class="pt-steps">` block, shared by:

- `resources/views/complaints/show.blade.php`
- `resources/views/pages/transparency/show.blade.php`
- the new tracking result

Each caller keeps its own surrounding card and heading; only the step list is shared. This is the smallest safe extraction and removes the markup duplication.

### Tracking timeline

Use the existing `.pt-updates` / `.pt-update` classes, with a small `resources/views/complaints/partials/track-updates.blade.php`:

- status pill (from `ComplaintStatus::tryFrom`), date, `publicDisplayComment()`;
- **no** documentation photos, actor names, or evidence;
- empty state using the existing `.pt-empty` pattern and `transparency.show.no_updates` / `next_update` copy.

Reusing the classes keeps the visual contract; the data subset is deliberately narrower than the public/resident timeline.

---

## 5. View and design system

### Lookup state

`resources/views/complaints/track.blade.php` is rebuilt. It extends `$mainLayout` (guest or auth), sets `body-class="page-complaint-track"`, and has two states in one file.

Anatomy:

1. `.pt` page wrapper, `min-height: calc(100svh - var(--navbar-height))`, background `--bg`.
2. `.pt-banner` with:
   - `h1` — "Track a complaint" (translated);
   - one-line lede — "Enter the ticket number from your receipt. No account needed.";
   - optional ticket example.
3. `.pt-card.track-card` overlapping the banner (`margin-top: -32px`), max width 640px, centered:
   - label + input + submit button (stacked on phones);
   - hint: "It looks like COMP-2026-A7K9P. Hyphens and capitals don't matter.";
   - inline error when not found;
   - privacy note: "This page shows progress only — never the report's description, photos, location, or the resident's identity."
4. A link home / to the transparency register.

### Result state

Same `.pt` shell:

1. `.pt-back-row` → "Track another complaint" (route to the blank form).
2. `.pt-banner`:
   - ticket pill;
   - `h1` — "Complaint progress";
   - status pill;
   - neutral chips: office, filed date, last updated.
3. `.pt-container.track-result` (one column, overlapping the banner):
   - **Progress card**: hold notice (if any), waiting-on-resident notice (if any), the shared 5-step stepper, "What this means".
   - **Updates card**: the public timeline, newest first, 5 visible with a "show earlier" disclosure if needed (or all entries — there are rarely many; keep it simple in v1).
   - **Privacy card**: the one-paragraph rule above plus "If this is your report, sign in to see the full record."
   - **Actions**: "Track another complaint", "Sign in" (guest), "File a complaint" (guest).
4. Rejected tickets: keep the stepper's "Stopped here" state and add a neutral note that the office did not accept the report and the owner can sign in for the reason.
5. Resolved tickets: add a neutral note that the owner can sign in to confirm the resolution.

### New CSS

`resources/css/pages/complaint-track.css`, imported from `resources/css/app.css`, scoped to `.track-page` / `.page-complaint-track`:

- lookup width, form stacking, input focus, primary submit, hint, error, privacy note;
- result grid gap and banner overlap.

Everything uses existing tokens: `--bg`, `--surface`, `--line`, `--line-soft`, `--text`, `--muted`, `--navy-*`, `--gold-500`, `--status-rejected-*`, `--radius-*`, `--ring`. No new colours, fonts, shadows, radii, or spacing scale.

### Design system updates

- Add **§5.53 Public ticket tracking — `.track-*`** with purpose, anatomy for both states, state table (empty / found / not found / on hold / waiting / rejected / resolved / closed), tokens, and the why.
- Add a compact swatch to `/design-system` showing the lookup card and the result progress card.
- Remove the now-unused `resources/views/complaints/track.blade.php` entries from `tests/Support/design-system-allowlist.txt` (the page should have no hard-coded hex or serif declaration after the rebuild).
- Add the required entry to `docs/design-changelog.md` with verification evidence and the standing screenshot waiver.

### Discovery

- The home hero already links to `complaints.track`; it will now work for guests.
- Add a **"Track a complaint"** link to the shared site footer (`footer.track_report` in `en` + `fil`), next to the Transparency Register. The footer is on every public page and does not crowd the navbar.
- Do **not** add a link to the transparency register page: `TransparencyPageTest` deliberately asserts the register does not link to the tracking page, and the register is for public reports, not private tickets.
- The existing dashboard track form keeps working; signed-in owners will be redirected to their full ticket after lookup.

---

## 6. Privacy and security

### Ticket-only is the accepted trade-off

- The ticket is the secret. The legal terms already say to treat it like a password; update the wording to be precise about what it unlocks.
- A random 5-character ID from 32 characters is ~33.5M possibilities per year. With 20 guesses/minute/IP it is not practically enumerable, and the page exposes only low-sensitivity progress.
- Residual risk: a distributed attacker could theoretically enumerate. If the LGU later wants stronger privacy, add a second factor (e.g. the account email) behind a separate route; do not silently change the current promise.

### Other controls

- `throttle:20,1` on the route.
- Generic not-found response; no distinction between malformed and unknown.
- Punctuation-insensitive lookup normalizes both the input and `ticket_id`; it broadens typing tolerance, not the payload or the set of matchable records.
- `noindex`, `no-store`, `Referrer-Policy: no-referrer`.
- No ticket or complaint data in analytics, audit logs, or notifications from this page. Guest reads are not audit events.
- Soft-deleted complaints and admin-confirmed spam are not trackable.
- A complaint that is private, hidden from the public register, or awaiting verification is still trackable by its ticket holder — that is the point. The ticket holder is not the public.
- No photos are streamed from this page, so no evidence route is reachable from it.
- Do not render the submitted ticket in the error message. Do not log the submitted value.

---

## 7. i18n

New file `lang/en/complaint_track.php` and `lang/fil/complaint_track.php` covering:

- page title and meta description;
- lookup heading, lede, label, placeholder, hint (including the "hyphens and capitals don't matter" reassurance), submit, privacy note;
- not-found title/body;
- result heading, ticket/office/filed/last-updated labels;
- waiting-on-resident, rejected, resolved, closed notes;
- track-another, sign-in, file-a-complaint actions;
- empty-updates copy (or reuse `transparency.show.no_updates` / `next_update`).

Reuse existing keys where they already say the right thing:

- statuses → `statuses.complaint_status.*` via `ComplaintStatus::label()`;
- "Resolution progress", "Public updates", "Done", "Current stage", "Upcoming", "Stopped here", "Department pending" → `transparency.show.*`;
- "Filed :date · :office" → `complaint_show.page.filed_with_office` (or its two parts).

No raw keys on screen. Add both languages in the same change.

---

## 8. Legal copy

### Terms §06 — Tracking and ticket numbers

- Change `DLT-` to `COMP-` and say hyphens and capitals are optional when tracking.
- Replace "Only you and the staff handling the complaint can see it" with the accurate split:
  - the full report (description, photos, exact location, identity) is visible only to the owner and the staff handling it;
  - anyone with the ticket number can see **progress only** — current status and updates the office has marked public;
  - treat the ticket number like a password.
- Keep the existing promise that the ticket is what the Municipality asks for on follow-up.

### Privacy policy

- Fix the `DLT-` reference in the file's explanatory comment.
- In "What the public register shows" / "Who can see your information", add the ticket-lookup path:
  - a visitor who knows the ticket number can see progress without an account;
  - that page does not show the report's description, photos, exact location, or the resident's identity;
  - full visibility still follows the resident's publication choice and staff moderation.
- Do not weaken the existing statement that a private report's contents stay private.

### Tests

`tests/Feature/LegalPagesTest.php` asserts `DLT-`; update it to `COMP-` and add an assertion that the terms mention progress-only tracking. Add a privacy-policy assertion that the new ticket-lookup sentence is present.

---

## 9. Tests

New `tests/Feature/GuestComplaintTrackingTest.php` (RefreshDatabase, seed departments):

**Access**

1. A guest can open `/track` and sees the form (200).
2. The form is `GET` and targets `complaints.track`.
3. The page is `noindex` and the result response carries `no-store` and `no-referrer`.

**Lookup**

4. A valid ticket returns 200 and shows the status label, office, filed date, and the 5 steps.
5. The same ticket is found as `COMP-2026-A7K9P`, `COMP2026A7K9P`, `comp 2026 a7k9p`, and `COMP–2026–A7K9P` (en dashes).
6. A legacy numeric ticket (`COMP-2026-00001`) still works, with or without hyphens.
7. An unknown ticket shows the generic not-found message and no complaint data.
8. A soft-deleted complaint is not found.
9. An admin-confirmed spam complaint is not found.

**Privacy**

10. The result does **not** contain the title, description, address, resident name, resident email, category, photo markup, or `is_internal` note text.
11. A public update comment **is** shown; an internal update's comment is **not**.
12. No evidence/documentation photo URLs or image tags appear.
13. A public but unverified complaint is still trackable (the ticket holder is not the public register).

**Status and progress**

14. Submitted, Under Review, In Progress, Resolved, Closed each produce the right current step.
15. A rejected complaint stops at its last reached status (the bug fix above).
16. A hold shows the public hold note and expected resume date; the internal hold note is not shown.
17. An open information request shows the neutral "waiting on the resident" notice, not the question text.
18. An empty timeline shows the empty-state copy.

**Signed-in behavior**

19. The citizen owner is redirected to `complaints.show`.
20. An unverified owner or an owner who must change their password stays on the limited page.
21. Another citizen is not redirected and sees the limited page.
22. An authorized staff member is redirected to `staff.complaints.show`.
23. A staff member from another office is not redirected, and the limited page does not leak their office's data.
24. An admin is redirected to `admin.complaints.show`.

**Throttle**

25. The 21st lookup in a minute returns 429.

**i18n**

26. The Filipino locale renders translated copy, not raw keys.

Run alongside:

```bash
php artisan test --filter=GuestComplaintTrackingTest
php artisan test --filter='TransparencyPageTest|ComplaintTicketIdTest|LegalPagesTest|LocalizationTest|DesignSystemGuardTest'
php scripts/design-system-guard.php
npm run build
php artisan view:cache
php -l app/Http/Controllers/ComplaintController.php
php -l app/Support/ComplaintProgress.php
```

---

## 10. Files to touch

| File | Change |
|---|---|
| `routes/web.php` | Move `/track` to public; add throttle. |
| `app/Http/Controllers/ComplaintController.php` | Rewrite `track()`; add redirect resolver. |
| `app/Models/Complaint.php` | Add `findByTicketId()` with punctuation-insensitive normalization. |
| `app/Support/ComplaintProgress.php` | New shared progress helper. |
| `app/Http/Controllers/TransparencyController.php` | Use the helper; remove the private duplicate. |
| `resources/views/complaints/track.blade.php` | Rebuild as the public lookup + result page. |
| `resources/views/complaints/partials/track-updates.blade.php` | New progress-only timeline. |
| `resources/views/partials/complaint-steps.blade.php` | New shared stepper markup. |
| `resources/views/complaints/show.blade.php` | Use the helper + shared stepper. |
| `resources/views/pages/transparency/show.blade.php` | Use the shared stepper. |
| `resources/css/pages/complaint-track.css` | New scoped page styles (tokens only). |
| `resources/css/app.css` | Import the new page CSS. |
| `resources/views/layouts/partials/site-footer.blade.php` | Add the tracking link. |
| `lang/en/complaint_track.php`, `lang/fil/complaint_track.php` | New strings. |
| `lang/en/footer.php`, `lang/fil/footer.php` | Footer link string. |
| `resources/views/pages/legal/terms.blade.php` | Fix prefix and tracking contract. |
| `resources/views/pages/legal/privacy-policy.blade.php` | Fix prefix and add ticket-lookup disclosure. |
| `resources/views/pages/design-system.blade.php` | Add the tracking swatch. |
| `docs/design-system.md` | Add §5.53; update the changelog. |
| `docs/design-changelog.md` | Required entry. |
| `tests/Support/design-system-allowlist.txt` | Remove the rebuilt page's old entries. |
| `tests/Feature/LegalPagesTest.php` | Update prefix assertion + new copy. |
| `tests/Feature/GuestComplaintTrackingTest.php` | New suite. |

No migration. No new dependency. No queue or notification change.

---

## 11. Implementation order

1. **Backend first.** Move the route, rewrite `track()`, add the redirect resolver, add `ComplaintProgress`, and write the feature tests. Keep the old view temporarily if needed; the tests will drive the contract.
2. **Shared UI.** Extract the stepper markup and update the two existing pages; run the detail-page tests.
3. **Guest views.** Rebuild `track.blade.php`, add the tracking timeline, add `complaint-track.css`, and add the design-system swatch.
4. **Discovery and legal.** Footer link, terms/privacy copy, `LegalPagesTest` update, i18n.
5. **Verify.** Guard, build, targeted tests, full suite if time allows; add the design-system changelog entry.

The backend can ship independently of the visual polish, but the legal copy must ship in the same release because it is the promise the feature fulfills.

---

## 12. Risks and rollback

| Risk | Mitigation |
|---|---|
| Ticket enumeration | Random 5-char IDs + 20/min/IP + progress-only payload. Residual risk documented. |
| Shared IP throttling (office/school NAT) | 20/min is comfortably above human use; tune the route constant if the LGU reports lockouts. |
| Referrer/log leakage of the ticket | `Referrer-Policy: no-referrer`; no external links or assets on the page; no-store. GET still puts the ticket in browser history and server access logs — accepted for shareability, and the terms already treat the ticket as a secret. |
| Accidentally showing detail | Tests assert the absence of title/description/photos/address/identity/internal notes. The view only receives a narrow set of fields. |
| Progress pages drift | Shared `ComplaintProgress` helper + shared stepper partial. |
| Existing ticket links break | Keep the `complaints.track` route name and `/track` URL. |
| Legal copy overpromises | Update terms/privacy in the same change; `LegalPagesTest` asserts the prefix and the progress-only rule. |

**Rollback:** restore the `/track` route to the auth group and the old `track()` behavior; no schema change to undo. The new helper/partials can stay unused or be reverted independently.

---

## 13. Out of scope

- Guest complaint filing. The filing form still requires an account; this feature only tracks an existing ticket.
- A second lookup factor (email/phone) or a separate tracking PIN.
- Showing title/description/photos/location to guests.
- Email/SMS lookup, notifications, or reminders.
- Changes to the public transparency register, publication rules, or moderation.
- A QR code on the receipt (possible follow-up: encode `/track?ticket_id=...`).

---

## 14. Effort

Roughly **1.5–2 days**:

- backend + tests: 0.5 day;
- shared progress extraction + existing-page regression tests: 0.5 day;
- guest views + CSS + design-system swatch: 0.5 day;
- legal copy, i18n, footer, changelog, verification: 0.5 day.
