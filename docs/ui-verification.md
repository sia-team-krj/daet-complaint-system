# UI Verification — Daet Listens

**Status:** canonical for process, 2026-11-09. Screenshots are optional; changelog
entries are not.

The owner waived screenshot capture on 2026-11-09 (along with the browser-automation
toolchain it depended on). What a UI change must deliver now:

1. **A `docs/design-changelog.md` entry in the same commit** (see the template at the
   top of that file). A change without one is not done. The entry documents:
   - the page/component and why the change was made,
   - what was verified **and how** — what you rendered and interacted with in a real
     browser, what tests or checks passed — e.g.
     `resident login → /complaints → tap Filters → select BPLO → Apply → row count 11 → 1 ✓;
     Esc discards the draft ✓; scrollWidth ≤ innerWidth at 393px ✓; LocalizationTest 7/7 ✓;
     design-system-guard: no NEW violations ✓`,
   - screenshots are optional: when taken, list them (before/after, 393/1310, en/fil);
     when not, write **`Verification screenshots: NOT captured (waived by owner, YYYY-MM-DD).`**
2. **Live behavior pass** — no automation committed to the repo. Exercise the flow a
   resident performs (or, for an admin/staff page, that role): open the page, click
   every changed control, check focus, keyboard escape paths and the mobile breakpoint
   at ~393px by narrowing the window. Record what you saw, not what you expected.
3. **Code checks** that need no browser:
   - `php artisan test` (or at minimum `--filter=LocalizationTest` plus the page's
     feature test),
   - `php scripts/design-system-guard.php` — the NEW count must not increase,
   - `scrollWidth ≤ innerWidth` at 393px — no horizontal scroll,
   - one `<h1>`, one footer, no raw lang keys rendered, in `en` and `fil`.
4. **Desktop parity** — confirm ≥641px renders as expected side-by-side with your
   mobile target so mobile changes never leak desktop layout.
5. Guide sections §11 (Definition of Done) and §12 (changelog) reference this file;
   keep both in sync if the process changes.
