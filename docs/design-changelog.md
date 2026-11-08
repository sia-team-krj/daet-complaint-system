# Design Changelog

Newest entry first. Every change to `resources/views`, `resources/css`, `resources/js`
or `lang` that alters how the UI looks or behaves must add an entry here, with
before/after screenshots in `docs/design-log/`.

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