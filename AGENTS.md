# Daet Listens — Agent Guidelines

## UI work (MANDATORY before touching any page, view, component, style or Blade file)

1. **Read `docs/design-system.md` in full first.** The design system is the rule, not
   a reference to consult later. Everything you need is in it plus
   `resources/css/tokens.css`.
2. **Use only the tokens and components the guide defines.** No new colors, fonts,
   shadows, radii, spacing or one-off CSS. If the guide does not cover what you
   need, you do not guess — you see step 3.
3. **Missing component or token?** Add it to the design system first: guide section
   (with anatomy, variants, states and the why) + token in `tokens.css` + swatch on
   `/design-system`, then use it. Nothing undocumented lands in a view.
4. **Never** duplicate a list into mobile and desktop markup when a script reorders
   rows — one markup per list (see guide §5.14 and §7). Never fork the shared
   filter bar or report-row component; extend them.
5. **Every UI change ends with:** changelog entry in `docs/design-system.md` §12 +
   the Definition of Done checklist in §11. Screenshots are optional (waived by the
   owner 2026-11-09); verification evidence lives in the changelog entry — see
   `docs/ui-verification.md`.
6. **z-index contract is LOCKED** (navbar 900, drawer backdrop 1000, drawer 1010,
   toast/modal 1100, map wrappers `isolation: isolate; z-index: 0`). Overlays that
   belong to a map live inside the map.
7. **i18n:** all strings from lang keys in `en` + `fil`; statuses/categories are
   language-neutral keys translated on display; no raw keys on screen.
8. **Privacy on public screens:** barangay-level only, under-3-report areas
   neutral, nothing but the report in the public payload.

Also applicable: the repository runs a guard (`scripts/design-system-guard.php`)
that fails on NEW hard-coded hex colors, serif declarations, or map wrappers
without `isolation`. CI runs it; so should you when touching UI.

## Design changelog (MANDATORY)

Every change to `resources/views`, `resources/css`, `resources/js` or `lang` that
alters how the UI looks or behaves MUST add an entry to `docs/design-changelog.md`
in the same commit. Screenshots are optional (waived by the owner 2026-11-09); list
them when taken, or state the waiver inline in the entry (see `docs/ui-verification.md`
and the existing `NOT captured (waived by user)` precedent). See the template at the
top of `docs/design-changelog.md`. "Done" without a changelog entry is rejected.

## Everything else

- See `AGENT_GUIDE.md` for setup, architecture and operational notes.
- See `PRODUCT.md` / `docs/` for product context.
- PHP 8.5 · Laravel 12 · Livewire 4 · Tailwind v4 + plain CSS (`resources/css/app.css`) ·
  PostgreSQL + PostGIS · see `compose.yaml`.
