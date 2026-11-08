# Pull request checklist

**UI changes (required for any page/component/style/Blade change)**

- [ ] I read `docs/design-system.md` before changing any UI, and followed it.
- [ ] Only tokens/components defined in the design system are used (no new colors, fonts, shadows, spacing, one-off CSS).
- [ ] If a component/token was missing, I added it to the design system first (guide + `tokens.css` + `/design-system`).
- [ ] One markup per list (no separate mobile/desktop copies); sorting/filtering still moves rows correctly.
- [ ] z-index contract respected (navbar 900 · drawer backdrop 1000 · drawer 1010 · toast/modal 1100 · map wrapper `isolation: isolate; z-index: 0`); drawer opens above maps.
- [ ] i18n: all new strings in `lang/en` AND `lang/fil`; no raw keys on screen; statuses/categories by key.
- [ ] Privacy on public surfaces: barangay-level only, under-3-report areas neutral, payload carries no coordinates/names/contacts.
- [ ] Screenshots at **393px and 1310px, en and fil**, with data and zero data, attached below (or the page is unchanged by this PR).
- [ ] Exactly one `<h1>`, exactly one footer; no horizontal scroll at 393px; no list bullets; visible focus ring; 44px touch targets; `prefers-reduced-motion` respected.
- [ ] AA contrast on new text; gold never on white (use `--gold-700` or navy); no serif; no vertical gold lines/side borders.
- [ ] Changelog entry appended to `docs/design-system.md` §12.
- [ ] Definition of Done checklist (`docs/design-system.md` §11) copied and ticked below.

**Every PR**

- [ ] Tests pass; **no new failures vs the baseline** (7 known unrelated baseline failures).
- [ ] Laravel 12 · PHP 8.5 · no `N+1`s you don't understand; queries scoped public on public pages.

## Definition of Done

- [ ] Screenshots: 393/1310 · en/fil · with data / zero data — attached.
- [ ] One h1, one footer; `scrollWidth ≤ innerWidth` at 393px.
- [ ] One language per locale in nav/footer; all keys exist in both locales.
- [ ] Filters persist in the URL; caret survives the Livewire poll; map unchanged when data unchanged.
- [ ] Map overlays live inside `.tr-map`; drawer opens above the map.
- [ ] AA contrast, focus ring, reduced motion, 44px targets.
