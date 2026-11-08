# Daet Listens — Design System

**Status:** canonical. All UI work follows this document.
**Source of truth:** this guide + the code it describes. If the two ever disagree,
the code that renders correctly wins, and the guide is updated — but only after the
match is proven on screen with screenshots.

This guide exists so a future developer (or agent) can build a new page without
seen the Transparency page. Every rule carries a one-line **why**. Rules marked
**LOCKED** are not preferences — they prevent specific failures we already hit.

Transparency page (`resources/views/livewire/transparency-register.blade.php`) is
the reference implementation. When in doubt, copy its markup and its CSS.

**Living reference:** `/design-system` renders every token and component on this
page against the real stylesheet (internal tool — admin-only in production,
unrestricted in local). If a token or class changes there, check that page before
any redesign ships.

---

## 1. Principles

1. **Official but human.** Navy authority, gold as the single accent, and colour
   for *meaning* (status, department, category, heat) — never decoration.
   *Why: a government portal must look like one, but grouped colour signals are
   the only way a resident can read 40 rows at a glance.*
2. **Show every fact once per screen.** No repeated counts, privacy notes, or
   duplicate actions. **One primary action per screen.**
   *Why: repetition trains readers to skim past the thing that mattered.*
3. **Privacy first on public screens.** Barangay-level only, areas with under 3
   reports rendered neutral, no coordinates/names/contacts in the payload.
   *Why: this page is the municipality's legal promise. Breaking it unpublishes nothing
   — but it can endanger people.*
4. **Mobile first.** Every component has a mobile and a desktop spec; layouts are
   designed at 393px before 1310px.
   *Why: Daet residents mostly hold Android phones on mobile data.*
5. **Truth before polish.** Never fake a state (skeletons that never resolve, a
   map that silently dies, an empty button that does nothing). Every state the
   page can be in is designed: loading, empty, no-results, error.
   *Why: a citizen who cannot tell "it broke" from "there's nothing" cannot file.*
6. **If it is in the DOM, it is real.** No invisible controls, no `disabled`
   decorations, no links to `href="#"`.
   *Why: invisible surface still costs attention, and dead links destroy trust.*

---

## 2. Design tokens

**File:** `resources/css/tokens.css` (imported by `resources/css/app.css` once).
Components consume tokens; they never declare raw hex values. **LOCKED: no
hard-coded hex in components.** Font stack is `--font-sans` (Inter) — **LOCKED: no
serif declarations anywhere** (Cormorant/Garamond era is over). **LOCKED: no
vertical gold lines or gold side borders** (§4 anti-patterns). **LOCKED: never
gold text on white** (use `--gold-700` or navy).

### Colour

| Token | Value | Use |
|---|---|---|
| `--navy-950` | `#071226` | Deepest backdrop (heatmap legend text, footer-adjacent) |
| `--navy-900` | `#0b1a33` | Banner gradient start; headings on banners |
| `--navy-800` | `#102545` | Sidebar/navbar fills (navbar uses `#102847`, see note) |
| `--navy-700` | `#17305a` | Banner gradient mid; selected chip; bar fills; dept-proof primary text |
| `--navy-600` | `#1e3f73` | Banner gradient end (highlight, not a surface) |
| `--navy-100` | `#dbe4f3` | Hairlines on navy surfaces; pressed chip border |
| `--navy-50` | `#eef3fb` | Card headers, icon tints, selected-row hover, table head row |
| `--gold-300` | `#e7c873` | Gold **on navy** only (disclosure summary, links) |
| `--gold-500` | `#d4a947` | Gold accent: progress current segment, buttons, gradient end of legend |
| `--gold-700` | `#8a6a12` | Gold **text on light surfaces** (AA) |
| `--bg` | `#f4f6fa` | Page background everywhere |
| `--surface` | `#ffffff` | Cards, inputs, buttons |
| `--line` | `#d9e0ec` | Interactive borders (inputs, controls) |
| `--line-soft` | `#e8edf5` | Hairline dividers, in-card borders |
| `--text` | `#0f172a` | Body ink |
| `--muted` | `#475569` | Labels, secondary text |
| `--faint` | `#64748b` | De-emphasized text (chevrons, hints, rank numbers) |
| `--map-loading-bg` | `#eef1f6` | Opaque map overlay (loading + no-areas message) |

> **Navbar note:** both navbars use `#102847`/`#102847`-found fill with a gold-tinted
> border (`rgba(201,168,76,.30)`), slightly darker than the page navy for chrome
> separation. The gold there is translucent — not a solid side border (§4).

### Shape, depth, focus

| Token | Value | Use |
|---|---|---|
| `--radius-sm` | `8px` | Controls (buttons, chips, inputs, badges' inner pills) |
| `--radius` | `12px` | Medium cards (How-this-data disclosure card, buttons on bare pages) |
| `--radius-lg` | `16px` | Cards (stat strip, filter card, list cards, area cards) |
| `--radius-pill` | `999px` | Pills, chips, ranks |
| `--shadow-card` | `0 1px 2px rgba(11,26,51,.06), 0 8px 24px -12px rgba(11,26,51,.18)` | Default card elevation |
| `--shadow-lift` | `0 2px 4px rgba(11,26,51,.08), 0 16px 32px -14px rgba(11,26,51,.28)` | Popovers, hover lift |
| `--ring` | `0 0 0 3px rgba(212,169,71,.35)` | Focus ring on interactive elements |
| `--ring-inset` | `inset 0 0 0 3px rgba(212,169,71,.35)` | Focus ring for full-width rows |

### Spacing scale
**Only:** `4 · 8 · 12 · 16 · 24 · 32 · 48`. No other multiples of 4 or 8 escape review.
(24/32 are also the desktop/mobile container paddings.)

### Type
Font: **Inter** (`--font-sans`) with `font-feature-settings "tnum"` on ticket IDs
and all counters. Mono for IDs: `--font-mono`.

| Role | Size / weight / tracking |
|---|---|
| h1 (banner) | 34px / 700 / −0.02em · 28px mobile |
| h2 (section) | 20px / 700 / −0.01em |
| Body | 15px / 400 |
| Small | 13px |
| Label | 12px / 600 / uppercase / +0.05em, `--muted` |
| Numbers (counts, IDs, dates) | tabular lining numerals |

AA contrast is the floor for body text, and is checked for anything a user must
read to act. `--faint` is for decoration only (chevrons), not for prose.

---

## 3. Colour systems for meaning

All values are tokens (status tokens `--status-*`, category tokens `--cat-*` in
`tokens.css`; department tokens `--dept-*` in `app.css`). Components bind with the
`[data-status]`/`.status-pill.<key>` / `[data-dept]`/`.dept-badge[data-dept]` /
`.cat-icon--<icon>` conventions — never by writing hex into a view.

### 3.1 Status pills — dot + text, 26px, radius-pill, 11px/600 uppercase

| key | bg | text | dot |
|---|---|---|---|
| `submitted` | `--status-submitted-bg` #dbeafe | `--status-submitted-text` #1e3a8a | `#3b82f6` |
| `under_review` / `verified` / `assigned` | `--status-review-bg` #e8eef8 | `--status-review-text` #17305a | `#17305a` |
| `in_progress` | `--status-progress-bg` #fef3c7 | `--status-progress-text` #92400e | `#f59e0b` |
| `resolved` | `--status-resolved-bg` #dcfce7 | `--status-resolved-text` #166534 | `#22c55e` |
| `rejected` | `--status-rejected-bg` #fee2e2 | `--status-rejected-text` #991b1b | `#ef4444` |
| `closed` | `--status-closed-bg` #e2e8f0 | `--status-closed-text` #334155 | `#64748b` |

**Why:** three visual families — informational (submitted), in-flight-but-stable
(review), action-needed (in progress = amber), good outcomes (resolved), negative
outcomes (rejected), cooling-off (closed). Adding a new status = 3 new tokens
(`--status-x-bg/text/dot`) + one `.status-pill.x` rule + one step mapping in the
progress helper. Never reuse `resolved`'s green for anything new.

### 3.2 Department badges — 11px/700 uppercase, radius 6px, padding 2px 6px
`.dept-badge[data-dept="ENG"]` etc., using the existing `--dept-<CODE>`/tints
(portal already uses them) — see `app.css` §"Department color identifiers".

ENG `--dept-ENG` #B45309 / tint rgba(180,83,9,.16) · HLTH #0F766E · GSO #475569 ·
MPDO #1D4ED8 · WST #15803D · SWDO #7C3AED · BPLO #0E7490 · OM #A16207 ·
fallback = slate. **Why:** the whole portal already speaks these codes; a new
department = one `--dept-<CODE>` pair + one `[data-dept]` line.

### 3.3 Category icon circles — 36px, 18px line icon (1.8 stroke)
| key | icon | bg token | fg token |
|---|---|---|---|
| `road_damage` | traffic cone | `--cat-road-bg` #fef3c7 | `--cat-road-fg` #b45309 |
| `noise_complaint` | volume | `--cat-noise-bg` #ede9fe | `--cat-noise-fg` #6d28d9 |
| `flooding` | droplet | `--cat-flood-bg` #e0f2fe | `--cat-flood-fg` #0369a1 |
| `garbage` / `sanitation` (waste) | trash | `--cat-waste-bg` #dcfce7 | `--cat-waste-fg` #15803d |
| `streetlight` | lightbulb | `--cat-streetlight-bg` #fef9c3 | `--cat-streetlight-fg` #a16207 |
| anything else | file | `--cat-other-bg` #e2e8f0 | `--cat-other-fg` #475569 |

Map by **category key**, never by translated label — a label change must not break
the icon. In Blade use `@include('pages.transparency.partials.category-icon', ['category' => ...])` (or its `category-icon` equivalent once extracted).
**Why:** colour is doing the filing here; the icon is redundant with the label,
which is why it may not carry meaning alone.

### 3.4 Heat ramp (maps) — `--heat-1 → --heat-4`
`#e0f2e9 → #fde68a → #fb923c → #dc2626` — used as the legend bar gradient and
passed to `L.heatLayer()` **from `getComputedStyle` tokens** (see the script). Areas
with **under 3 reports are neutral** (never highlighted). **Why:** a single report
is not a finding, and a false hot-spot is a false accusation in a public document.

### 3.5 Adding something new
New status ⇒ tokens + pill class + progress-step rule; new department ⇒
`--dept-<CODE>` + `[data-dept]`; new category ⇒ `--cat-*` pair + one icon case.
If the palette already says it, **use the existing tint** — do not invent a
green-yellow-purple of your own.

---

## 4. Layout & spacing

- **Container:** `max-width: 1040px; margin: 0 auto; padding: 0 24px;` (16px mobile) — one width for header, sections, footer.
- **Page background:** `--bg` (#f4f6fa). Never white-page, never cream.
- **Section rhythm:** sections butt with 24px (mobile 16px); register section uses
  `margin-top: 32px` for its heading.
- **Banner:** full-bleed, `padding: 48px 0 64px`,
  `linear-gradient(135deg, #0b1a33 0%, #17305a 62%, #1e3f73 100%)` +
  `repeating-linear-gradient(135deg, rgba(255,255,255,.035) 0 1px, transparent 1px 14px)`.
  Content: h1 white 34/700 −0.02em, **40×3px `--gold-500` horizontal bar** (radius 2px)
  below it, privacy sentence `rgba(255,255,255,.82)`, optional `<details wire:ignore>`
  ("How this data is shown", gold-300 summary → white popover card with 3 checks),
  "Updated M j, Y" line `rgba(255,255,255,.7)` with a clock icon.
  **LOCKED:** no gold side borders, no vertical accent lines, no serif, and the
  banner hero contains no buttons — the navbar owns navigation and the CTA.
- **Stat strip overlapping banner:** `margin-top: -32px`, 4 columns (2 on mobile,
  12px gap), white cards radius-lg `--shadow-card`, padding 16/20, icon circle 40px
  tinted navy-50/amber/green/sky, value 28/700 tnum, label 13 `--muted`.
  **Why 32px:** it visually ties the strip to the banner, and it was the one
  overlap that read as layers instead of a bug (see §6).
- **Stat strip is the ONLY place counts appear** besides the results summary.
  **Why:** two numbers for the same fact is one number the user has to reconcile.
- **Card:** `--surface`, `1px var(--line-soft)`, radius-lg, `--shadow-card`,
  16–20px padding. Map card clips to its radius.
- **List hover:** `--navy-50` background; focus-visible: `--ring-inset`; mobile
  card hover: `translateY(-1px)` + `--shadow-lift` (desktop-only, see
  reduced-motion).
- **Page shells:** three standard shells exist:

### Shell A — banner + stat strip + content (list pages)
```
┌ banner (full-bleed) ─────────────────────────────────────────┐
│ h1                                          (white)          │
│ ▬ 40×3 gold bar                                             │
│ one-line subtitle + optional "How this data is shown" ▾     │
│ Updated M j, Y                                              │
└──────────────────────────────┬───────────────────────────────┘
        ┌ stat ┐ ┌ stat ┐     │  (cards overlap by 32px)
┌ content, 1040px ──────────────────────────────────────────┐
│ [2/3 map card] [1/3 side card]   ← public pages only      │
│ h2 section …………………………… results-summary (aria-live)        │
│ [filter bar card]                                         │
│ [list card — the shared report-row component]             │
└──────────────────────────────────────────────────────────┘
```

### Shell B — list page, resident (My Complaints)
```
banner: h1 + ONE line subtitle + one CTA ("File a complaint" only if the navbar
does not show it at this viewport — never two)
stat strip: Total · Open · Resolved · Avg. resolution time   (server-side)
filter bar (search + chips + dept/category/sort, URL-persisted)
list card: same <ul>/<li> rows as Transparency; the resident adds two pills
(needs-response, visibility) — never more rows of facts
pagination: "Showing 1–10 of N" + numbered pages or "Show more"
```

### Shell C — detail page (single complaint)
```
back link (navy text, 44px target)
h1 card: ticket id mono navy + status pill (+ optional Verified pill) — the title
of the report, never the description
grid: [progress stepper card] [main column: evidence / timeline / description]
      [side: department / category / date / alias]
status-change timeline: ol of dated events, newest first
evidence grid: ≤ 3 visible, rest behind "show all"
```

---

## 5. Component catalog

Each entry: purpose → anatomy → variants/states → class/Blade name → notes.
Locations: shared rules live in `app.css`/`tokens.css`; Transparency-specific
component CSS lives in the Livewire component (all values are tokens) until a
second page consumes them, at which point they are extracted.

### 5.1 Banner / page header — `.tr-banner`
Purpose: anchor the page; state what it is and what it is not.
Anatomy: `<header class="tr-banner"><div class="tr-container">h1, .tr-goldbar,
.tr-privacy, optional details.tr-how, .tr-updated`.
States: disclosure open/closed (chevron rotates 180°); no interactive states on text.
Mobile: h1 28px; banner stays ~200px; nothing is hidden, the disclosure popover
clamps to `calc(100vw - 48px)`.
Why the Details disclosure: it is the privacy promise unfolded — available on demand,
zero noise by default. **LOCKED:** banner has no buttons.

### 5.2 Stat card / strip — `.tr-stats` / `.tr-stat`
Anatomy: icon circle `.tr-stat__icon--navy|amber|green|sky` + value
`.tr-stat__value` + label `.tr-stat__label`.
States: values only; on empty data the money stats show `0`, the rate shows `—`.
Mobile: 2-column grid, 12px gap, value 24px.
Single source: computed server-side from the same payload as the list.
Why the overlap: it reads as one composed surface, not floating cards.

### 5.3 Card — `.tr-card`
Base card. States: default, hover (list rows only, desktop `@media (hover:hover)`).
Use radius-lg + shadow-card; never thinner than `line-soft` hairlines inside.

### 5.4 Button — `.btn-nav-cta` (gold) · `.tr-button` (secondary) · nav CTA
- **Primary:** gold-500 gradient + navy-900 bold uppercase text; navbar/registered CTA
  for "File a complaint"; one per page. hover lifts; focus-visible ring.
  *Why: gold is the accent, and an accent used twice is decoration.*
- **Secondary:** white surface, `1px line`, navy text, 44px min height.
- **Danger:** white surface, danger-tinted text/border (#fee2e2 family tokens) — reserved for destructive confirms.
- **Ghost / text:** navy-700 text + underline-offset (used for Clear filters),
  *not* a filled pill — it is an escape hatch, not a commitment.
Both CTA-like buttons: 40px tall on desktop, 44px on mobile (LOCKED).

### 5.5 Search input — `.tr-search` + `#complaint-search`
Anatomy: 18px search icon left (`--faint`), 15px placeholder `--faint`, 44px height,
1px `--line`, radius-sm; focus = gold border + `--ring`.
Why: the icon inside the field saves a label column and is the expected pattern.

### 5.6 Select — `.tr-select`
Anatomy: `appearance: none` + custom chevron svg right, 44px, 1px `--line`,
radius-sm. Focus identical. **LOCKED:** never the default black border.

### 5.7 Status chip — `.status-chip` (`.selected`)
36px pill; `1px line`; selected = `--navy-700` bg + white text; hover `--navy-50` +
`--navy-700` text. All chips share `role="group"`.

### 5.8 Clear-filters text button — `#clear-filters` / `.tr-clear` / `.tr-button` (empty-state)
Navy-700 text, underline-offset 3px; appears only with an active filter
(`hidden` toggling), right-aligned; 44px target.

### 5.9 Filter bar — `.tr-filters`
Row 1: search (flex 1.6) · department · category · sort (each flex 1; custom chevrons).
Row 2: status chips (scrolling row, hidden scrollbar, 36px tall) · optional
barangay chip · Clear filters right.
Mobile: search full → department full → category+sort 50/50 halves → chips one
horizontally scrollable row. **LOCKED:** inputs are white + 1px `--line`, never a
heavy black border; chips are 36px on mobile too (they scroll — they do not wrap).
The bar is a `.tr-card` — one container, no inner shadows.

### 5.10 Status pill — `.status-pill.<status_key>`
26px, dot + text, 11px/600 uppercase. See §3.1.

### 5.11 Department badge — `.dept-badge[data-dept]`
See §3.2. Always rendered from the department's `code`, never the display name.

### 5.12 Category icon circle — `.cat-icon.cat-icon--<road|noise|flood|waste|streetlight|other>`
36px circle, 18px icon, 1.8 stroke. See §3.3.

### 5.13 Verified pill — `.report-row__verified`
Small `--status-review-tint` pill with an 11px check; rendered only when the
complaint was actually verified (`verification_method !== null`). Never "Verification
not recorded" on rows — absence is noise.

### 5.14 Report row / card — `.report-row` (THE shared pattern)
**One markup, two layouts. LOCKED.** A `<ul id="complaint-table-body">` of `<li
data-report-row>`, one `<a class="report-row">` per row, whole row clickable.

Desktop: grid `minmax(0,2.6fr) minmax(0,1.4fr) 150px 110px 16px`, 76px min height,
padding 16/20, `.report-list__head` above it (navy-50, uppercase 12/600,
aria-hidden). Hover `--navy-50`. focus-visible: inset ring.

Mobile: `<ul>` display:grid 12px gap; `<li>` white card radius-lg shadow-card;
`<a>` flex-wrap with this exact order:

```
line 1  [cat-icon 36] [ticket mono 13 navy/tabular] ........ [status pill] (margin-left:auto)
line 2  title, flex-basis:100%, 17px/600, clamp 2 lines
line 3  [dept badge] [📍 Barangay VI] [Nov 8, 2026]   (flex-wrap, gap 8px 12px, 13px muted)
line 4  "Step N of 5 · <Status>" 12px muted  ← above full-width 5-segment bar (4px)
```
Mechanism: `.report-row__complaint/.report-row__text/.report-row__meta/.report-row__dept
{ display: contents }` so the pieces join the flex line; `order:` assigns 1..9.

**LOCKED:** no chevron, no "View details", no bullets, no reporter/alias/
"Community member"/"Verification not recorded"/"Public visibility only" on rows.

### 5.15 5-segment progress bar — `.progress` + `.progress--mini` / `.progress--full`
Segments `flex: 1; height: 4px; border-radius: 2px; gap: 3px`. complete = `--navy-700`,
current = `--gold-500`, rest = `--line`. `aria-label "Step N of 5: <status>"`.
Step map: submitted→1 · under_review/verified/assigned→2 · in_progress→3 ·
resolved→4 · closed→5 · rejected→∅ (bar renders neutral, label only).
Why 5: submitted → under review → in progress → resolved → closed — a resident can
recite the lifecycle.

### 5.16 Map card — `.tr-map` (see §6 for the z-index contract)
Header: h2 left, legend right (`Low [96×8 gradient] High`), 16px padding, bottom
border `--line-soft`. `.tr-map { position:relative; isolation:isolate; z-index:0;
height:360px }` (280px ≤768px). `#transparency-map` fills it.
`#map-status` (z 1100): solid `--map-loading-bg` inset overlay with shimmer
skeleton; script hides it on build; shows the message state instead of children
(`setStatus` rebuilds an `.tr-map-status__message`). `.tr-map__hint` shows only
≤768px ("Use two fingers to move the map", hides on first two-finger touch).
Mobile map: `dragging: false` on touch, two-finger pan enabled.
Legend aria-label: "Concentration of reports from low to high".
Why tile choice: stock tiles stayed **but the tile pane is muted**
(`saturate(.45) brightness(1.08)`), because a POI's red cross pulls the eye away
from the heat, and there is no keyless, calm raster style (CARTO basemaps moved
behind a commercial key — do not wire `API KEY REQUIRED` into production).

### 5.17 Top-areas card — `.tr-areas`
`h2 "Top areas"`, `<ol>` of ≤5 rows, each a button with: rank (faint, tabular),
name, right-aligned count (13/600 muted), 6px bar (navy-700 fill / navy-50 track,
width = share of the largest area). On ≤1024px only the first three rows show until
`data-expanded="true"` is set by the "Show all" toggle (44px target). Empty: no
barangay with ≥3 reports ⇒ info icon + "Not enough reports yet…".

### 5.18 Empty / no-results / error states — `.empty-state`
- Empty (no data at all): 64px navy-50 circle + message + ONE primary CTA.
- No-results (filters active): same shell, `[data-no-results]`, always "Clear filters".
- Error: same shell, alert icon, message + Retry (`data-retry`, reloads).
Livewire note: empty row itself must remain a valid list item (`<li>` inside the `<ul>`).

### 5.19 Stepper (detail pages) — resident precedent
Five numbered steps (Submitted → Verified → Assigned → In progress → Resolved):
navy-700 done, gold-500 current, `--line` upcoming; an `aria-current="step"` on the
current; labels under dots; on mobile the text is "Step N of 5: <label>" + segments only.

### 5.20 Update timeline — `.timeline`
Dated `<ol>`, newest first; dot + vertical line (navy-100), card per event:
status pill + date + body. Never show `is_internal` entries.

### 5.21 Evidence grid — `.evidence-grid`
3-up desktop, 2-up mobile; each item a `figure` with caption; oversize/non-photo
items keep aspect ratio via `object-fit: cover`; viewing happens full-frame with a
title attribute — never open a raw image URL with no chrome.

### 5.22 Toast & modal/drawer
- Toast: `--surface`, success-tinted border, 8px radius, pinned above content;
  **always above the drawer**: `z-index: var(--z-overlay) = 1100`.
- Modal/drawer: backdrop `rgba(7,18,38,.48)` (`--z-drawer-backdrop: 1000`) + panel
  (`--z-drawer: 1010`), panel 320px max on mobile, `overflow:auto`, `inert` + `aria-hidden`
  toggling with open state; `Escape` and scrim click close; focus returns to trigger.
- The mobile nav drawer is the reference implementation — same z-contract, same
  language switch placement.

### 5.23 Site footer — `.site-footer`
Purpose: one standing footer on every shell, carrying the legal documents and the
municipality's identity. Never duplicated (single-footer contract, §7).
Anatomy: `.site-footer` (navy-950 fill, 2px gold-500 top accent) →
`.site-footer__inner` (1040px grid) with four areas:
`brand` (LGU logo + municipality name + admin-editable `footer_text`) ·
`legal` (Terms · Privacy · Transparency Register, withheld from admins) ·
`meta` (admin-editable `contact_text`) ·
`bottom` (seal left, copyright right, hairline-separated).
States: default only; links hover/focus to gold-300.
Mobile (≤768px): single column, brand → legal → meta → bottom; links 44px targets.
Why: legal notices belong where they are always present but never in the way;
admin-editable text reuses SystemSetting so the municipality can update copy
without a deploy; one footer per page so the links never appear twice.

---

### 5.23 Complaint card (public list) — `.complaint-card` / `.complaint-list`
Purpose: one public complaint as a self-contained, tappable card on every
breakpoint. Replaces the old table + mobile-card duplication with a single
`<ul class="complaint-list">` of `<li class="complaint-card">`.

Anatomy (mobile, < 768px, ~135px tall):
- Row 1: 36px category icon circle + column (title Inter 600 15px, clamped
  to 2 lines; meta line: ticket in mono 12px muted · barangay 13px muted).
- 1px `--line-soft` divider (12px above/below).
- Row 2 (flex, nowrap, gap 8px): status pill (24px) + 16px verified check
  (same line) · department CODE badge only · `margin-left: auto` short date
  · 14px chevron. Department badge hidden before anything wraps at 360px.

Anatomy (desktop, >= 768px, ~72-84px tall): CSS grid
`40px | minmax(0,1fr) | 176px | 150px | 64px | 16px` — icon | title + meta |
department (code badge + name) | status (pill + verified check) | short date |
chevron. No header row.

States: default (white, 1px `--line-soft`, radius 14px, soft shadow);
hover (border `--line`, stronger shadow); focus-visible (gold ring via
`--ring`). Whole card is clickable through a stretched link on the title
(`a::after { inset: 0 }`) — exactly one focusable link per card. NO colored
left borders or side stripes.

Tokens: `--surface`, `--line-soft`, `--line`, `--text`, `--muted`, `--faint`,
`--navy-700`, `--gold-500`, `--radius-sm`, `--radius-pill`, `--shadow-card`,
`--ring`. Category icon circles and status pills reuse §3.2/§3.3/§5.10.

Data rules (presentation only): barangay has a leading "Barangay " stripped
before re-prefixing (no doubling); unresolved barangay shows "Daet" with no
prefix; long barangay names truncate with ellipsis (full name in `title`);
title empty or > 70 chars shows the category label. Ticket number is mono
12px in `--muted` (never gold). No progress bar in the list.

Why: a table with stretched pills and a progress bar made rows ~120px tall
and forced horizontal scroll on phones; cards give a uniform 12px gap and
predictable heights that fit the scroll budget.

## 6. The z-index contract — **LOCKED**

| Layer | z-index |
|---|---|
| In-map Leaflet panes (tiles … controls … attribution) | 200 … 1000 … 1200 |
| Any `.tr-map` wrapper | `isolation: isolate; z-index: 0` |
| Navbar (`#navbar`) | **900** |
| Mobile drawer backdrop (`.mobile-menu-backdrop`, was `.mobile-nav-overlay`) | **1000** |
| Mobile drawer (`.mobile-menu`, was `#mobile-nav-drawer`) | **1010** |
| Mobile drawer close `X` (`.mobile-menu-close`) | inside the panel, not a new layer |
| Toast / modal / full overlays | **1100** |
| Landing page sticky mobile CTA (`.lp-sticky-cta`) | **800** |
| In-map overlays (`#map-status`, hint, attribution) | **inside** the map only |

**Why:** Leaflet assigns tile panes z 200 … markers 600 … controls 800–1000. Without
`isolation:isolate`, those numbers compete with the navbar and drawer on the page —
and the map wins. The first version of this bug shipped twice: the map covered the
open mobile menu. A map overlay (loading skeleton, legend, hint) that escapes
`.tr-map` repeats it — every overlay that belongs to the map lives **inside** the map.

**Locking scroll while a drawer is open** is required too: `html.mobile-drawer-open`
→ `overflow: hidden`. The portal drawers also toggle `mobile-menu-open` on
`<html>`/`<body>`; both classes must set `overflow: hidden` and restore on close.

**Drawer anatomy — LOCKED (2026-11-08, after the "drawer won't tap" incident):**

1. **The panel and the backdrop are direct children of `<body>`, never inside
   `<nav>`/`<header>`.** The navbar has `backdrop-blur`, which makes it a stacking
   context — and a containing block for `position: fixed` descendants — so a panel
   inside it can never out-paint later page sections no matter how high its
   `z-index` is. (We shipped exactly that: the fixed
   `body.mobile-menu-open::before` scrim @ z 950 painted above the whole navbar
   context @ 900 and swallowed every tap on the panel. `elementFromPoint` at a
   drawer item returned `body`, i.e. the pseudo-element's originating element.)
2. **Backdrop:** `position: fixed; inset: 0` @ z 1000, `rgba(7,18,38,.6)`,
   `pointer-events: none; visibility: hidden` when closed, `.is-open` → auto/visible.
   Tapping it closes the drawer.
3. **Panel:** `position: fixed; top: 0; right: 0; bottom: 0; width: min(85vw, 320px)`
   @ z 1010, `overflow-y: auto`. It deliberately covers the navbar, so the panel
   carries its own close `X` in `.mobile-menu-header`; it is aligned over the
   navbar hamburger's footprint, so even a tap aimed at the hamburger reaches it.
   Item labels right-align (`justify-content: flex-end; text-align: right`) —
   the panel is anchored to the right edge and left-aligned labels looked stranded.
4. **Closed state is inert, always:** `pointer-events: none; visibility: hidden`;
   open state restores `auto`/`visible`. A drawer that is merely transparent or
   `transform: translateX(100%)` but still `pointer-events: auto` eats taps
   (`opacity: 0` is not a hit-test exemption).
5. **Out-of-panel toggles get `inert` while the drawer is open** (they sit under
   the opaque panel — keeping them focusable creates an invisible tab stop); the
   in-panel close button stays reachable. All toggles sharing `aria-controls`
   keep `aria-expanded`/`aria-label` in sync.
6. **JS contract:** `public/js/shell.js` finds surfaces via `aria-controls`
   (`document.getElementById`), never via `navbar.querySelector` — the drawer no
   longer lives in the navbar. Close paths: Esc, backdrop tap, the in-panel `X`,
   and any `.mobile-menu a` tap; plus `html/body.mobile-menu-open` scroll lock.

**Prove it, don't trust it.** Before claiming a layering fix, run it in a real
browser (Playwright) at 393px: open the surface, then
`document.elementFromPoint(x, y)` at the center of **every** item and assert the
hit is the item or a descendant — plus a click-through of every item, plus
backdrop/Esc/scroll-lock/closed-state-interception assertions, at 393px and
360px, in `en` and `fil`, with screenshots. A green elementFromPoint sweep is
the regression test this bug never had.

Note: `#navbar` also got `z-[900]` as a markup-level belt to the CSS suspenders.

---

## 7. Livewire + JS contracts — **LOCKED**

These are the things that broke before. 

1. **A polled component replaces/morphs its DOM every ~20s.** Listeners bound per
   render **stack** against nodes that morph in place; therefore listeners are
   **delegated from the document, bound once**, and the per-poll work is only:
   re-apply reader state + rebuild the map only if stale.
   (`morphed` hook via `window.Livewire.hook` — Livewire 4 has no `livewire:updated` DOM event.)
3. **Typed search text, selected chip, and caret survive a poll** — the script holds
   a `filters` object and restores values/selection after every render.
4. **A map must not rebuild on an unchanged poll** — rebuild only when
   `JSON.stringify(payload)` changed or Leaflet's panes are gone from the container
   (`mapHasBeenDetached()`).
5. **Never duplicate a list into desktop and mobile markup** when a script reorders
   rows — one `<ul id="complaint-table-body">`, one `<li data-report-row>` each,
   `<a class="report-row">` as the only link.
6. **Data attributes are the JS contract.** Register script needs exactly:
   `#transparency-map` · `#transparency-map-data` (attrs `data-points`,`data-center`) ·
   `#map-status` · `#complaint-search` · `#complaint-department` · `#complaint-category` ·
   `#complaint-sort` (`reported_desc|reported_asc|status_asc`) · `#clear-filters` ·
   `#clear-filters-list` · `#results-summary` · `#complaint-table-body` ·
   `.status-chip` + `data-value` (`''` = All) · `[data-no-results]` · `[data-error]` ·
   `[data-barangay-filter]` · `#barangay-chip` · on each row: `data-report-row`,
   `data-report-id`, `data-search`, `data-department`, `data-category`, `data-status`,
   **`data-iso-date`** (not `data-isodate` — that silent mismatch broke sorting),
   `data-barangay`.
7. **No `<script>` inside Livewire components.** Payloads ride as data attributes;
   the page's script is in the page view's `@push('scripts')`.
8. **Client-side filtering threshold:** fine up to ≈200 visible rows. Beyond that,
   filters must move server-side (query string + pagination), not the browser.
9. **empty rows and morph:** `[hidden]` is the visibility mechanism (never
   `style.display`); `aria-live="polite"` owns the results count.

---

## 8. i18n rules

- **Every string is a lang key, in `en` and `fil`.** New groups get a `v2`-style
  namespace per page (`transparency.v2.*`), never raw copy in Blade.
- **No raw keys on screen** — in either language. If you cannot translate it, it is
  not ready to ship.
- Statuses and categories are stored as **language-neutral keys** and are
  translated only on display (`statuses.complaint_status.*`,
  `DepartmentRouter` category values).
- **Filipinos:** strings run ~30% longer — design rows for the longest translation,
  not the shortest; the layouts above already do (flexible chips row, no fixed
  widths on labels).
- **One language per locale in navbar/footer** — never mix "DASHBOARD" beside
  "Aking Mga Reklamo".
- Dates: one formatter, `"M j, Y"` via `->locale(app()->getLocale())->translatedFormat('M j, Y')`.
  Ticket numbers, counts and IDs get `tnum`.
- Tile/Leaflet strings and dates on the map only stay as-is where they are legally
  required (attribution) — content copy still ships via keys.

---

## 9. Accessibility checklist

- [ ] Exactly **one `<h1>`** per page; sections use `h2` with `aria-labelledby`.
- [ ] Lists are semantic `<ul>/<ol>`; cards inside `<li>`; icon-only buttons carry
      `aria-label` (and `aria-hidden` icons carry none).
- [ ] Results summary and map status use `aria-live="polite"` with `role="status"`.
- [ ] Current step uses `aria-current="step"`; stepper bars/progress bars carry
      `role="img"` + `aria-label "Step N of 5: <status>"`.
- [ ] Focus-visible ring everywhere (`--ring` / `--ring-inset`), never outline:none
      without a replacement.
- [ ] All text meets AA; `--faint` only for decoration.
- [ ] Touch targets ≥ 44px on mobile (CTAs, drawer links, chips row, back links).
- [ ] `prefers-reduced-motion` honoured (shimmer, card lift, chip chevrons disabled).
- [ ] Map is covered by the requirement "text alternatives for maps": the map has
      `role`/`h1`-adjacent summary via `h2` + the "Top areas" adjacent list; the
      legend has an aria-label; the tile-layer attribution remains legible.
- [ ] No colour-only meaning: every pill carries text; every bar has a text label.

---

## 10. Page recipes

| Page | Shell | Banner | Stat strip | Filter bar | List / card | Empty state |
|---|---|---|---|---|---|---|
| Transparency (public list) | A | yes — h1 + privacy sentence + "How this data is shown" + updated line | Public reports · In progress · Resolved · Resolution rate | yes (search, dept, category, sort, status chips, barangay chip) | `.report-row` grid, desktop table head + mobile cards | empty = "No public complaints yet" (no CTA), no-results = filters box |
| My Complaints (resident) | A/B | yes — h1 + ONE line subtitle + **one** CTA ("File a complaint", suppressed when the navbar shows it at this viewport) | Total (all) · Open (submitted+under review+in progress) · Resolved · Avg. resolution time | yes, URL-persisted `?status=&q=&sort=`; chips carry counts; dept select folded in only when meaningful | same `.report-row` + the resident extras (needs-response pill, visibility pill, Updated x ago) | empty = circle + "No complaints yet" + ONE "File a complaint" |
| Staff Complaint Queue / Admin All complaints (work lists) | A | yes — plain title + ONE line of scope ("Your office's queue"/"Filter the municipal queue"); no CTA | 4 existing metrics (do not invent) | yes — extended with review + priority (admin: + department, flagged, dates) — the same bar visual language, not a fork | same row component + staff columns (Review, Priority, Age); row click opens detail | empty per q-status = "queue clear"; no-results = same clear-filters box |
| Public complaint detail (`/transparency/complaints/{id}`) | C | no banner — back link only | no | no | stepper + timeline + evidence grid + alias/privacy card | "Exact location withheld" is the privacy state — keep it |
| Resident complaint detail (`/complaints/{id}`) | C | no banner — back link + breadcrumb dots, no cream/serif | no | no | stepper + actions for the resident (respond to info requests, toggle visibility) | same as above |
| File complaint / create (wizard) | form | no banner | no | wizard steps, one action per step | review-card before submit | "Ready to submit" row shows barangay, never coordinates (LOCKED) |
| Settings | form panel | no banner — h1 + tab nav | no | no | panel cards | Plug: profile/security/notifications/preferences/privacy tabs |
| Admin dashboard | A (dark variant) | no banner — workspace chrome | 4 KPI tiles from real metrics | no | dark tables + dept chips | treat separately: admin shell |
| Staff dashboard | A (dark variant) | no banner — workspace chrome | 4 KPI tiles | no | dark tables | treat separately |

**Hero/choices repeated from before failure:** no cream, no Cormorant, no ~50px
mystery gap under the navbar; every list page shares the banner gradient with the
navbar so chrome and content agree on the navy.

**Navbar rules (shared, not per page):** see `EN/FIL` labels per locale, single
locale per page, `.navbar-logo { flex-shrink: 0 }`, `#navbar` `z-index 900`.

---

## 11. Definition of Done (any UI change)

1. [ ] Screenshotped at **393px and 1310px**, in **both `en` and `fil`**, with data
       and zero data; attached to the PR/change.
2. [ ] Exactly one `<h1>`, exactly one footer; no horizontal scroll;
       `scrollWidth ≤ innerWidth` at 393px.
3. [ ] No raw lang keys rendered; `en`+`fil` complete for every string you added.
4. [ ] No hard-coded hex in the changed files; no serif; no gold-on-white; no
       vertical gold lines; no black input borders.
5. [ ] Every list is semantic, one markup per list; sorting/filtering still strips
       nothing and moves nothing extra.
6. [ ] Filters persist in the URL where they're supposed to (`?q=&status=&sort=`) and
       survive refresh; focus/caret survive the poll.
7. [ ] Map wrapper has `isolation:isolate; z-index:0`; drawer opens **above** it
       (elementFromPoint check); attribution visible.
8. [ ] AA contrast verified on new text; 44px touch targets on mobile; visible
       focus ring; `prefers-reduced-motion` respected.
9. [ ] The Definition of Done is printed in the PR description as a checklist.

### Common mistakes (from this project)
- Map covers the mobile menu → missing `isolation: isolate; z-index: 0`.
- Duplicate mobile/desktop markup → sort moves the wrong rows.
- `data-isodate` vs `data-iso-date` → sort silently broken.
- Claiming CARTO Positron without noticing it requires an API key — in our
  screenshot every tile read "API KEY REQUIRED".
- Tests that grep PHP source without rendering the page — runtime failures
  ("window is not defined", detached nodes) shipped.

---

## 12. Changelog

Every UI task appends here: **(date · page · what changed).**

- 2026-11-08 · Transparency · ground-up redesign: banner + stat strip + map/top-areas
  + filter bar + report-row pattern; tokens created; single-footer contract
  verified; sort/`data-iso-date` bug fixed; barangay resolver unified across
  map/rows/top-areas; privacy prune of the row (no reporter, no verification text);
  tests updated to the new contract.
- 2026-11-08 · Whole portal · `/design-system` living reference page created;
  navbar/drawer/z-contract unified (navbar 900, drawer 1010); `AGENTS.md` +
  PR template + hex/serif/isolation guard tests.
- 2026-11-08 · My Complaints (resident) · redesigned to Shell B; URL-persisted
  `?status=&q=&sort=`; resident pills (needs-response, visibility); 10/page
  pagination.
- 2026-11-08 · Staff queue / Admin all-complaints · redesigned to Shell A;
  report-row component with review/priority/age columns; admin keeps CSV export
  and bulk reassign.
- 2026-11-08 · Resident complaint detail · cream/Cormorant removed; guide tokens,
  stepper, timeline, evidence grid, privacy alias handling.
- 2026-11-08 · All public complaints (/transparency/complaints) · redesigned as
  compact cards (5/page), collapsible filter panel, status chips with counts,
  data-display fixes (barangay de-dup, title fallback).
- 2026-11-08 · Site footer · redesigned to tokens: navy-950 fill, gold-500 top
  accent, Inter type, brand block + legal nav + admin-editable footer/contact text
  + seal/copyright; mobile stacks; hex/DM Sans removed from allowlist.
- 2026-11-08 · Guest mobile menu · dedicated guest-only drawer partial
  (`layouts/partials/guest-drawer.blade.php`) with guest-scoped `.guest-drawer*`
  classes: navy-900 panel, 64px brand header, 52px icon-led rows, segmented
  EN|FIL control, pinned 52px Log in / Get started buttons (no wrap). Shared
  `.mobile-menu`/`.mobile-nav-link` classes restored so authenticated menus are
  untouched. Root cause of giant icons: unsized SVGs without width/height + no
  stroke color; Language label unstyled due to default color on navy.
- 2026-11-08 · Guest + app navbar (mobile) · drawer de-trapped after "panel
  opens but every tap lands on the scrim": panel + backdrop moved out of
  `<nav>` (its `backdrop-blur` made a stacking context; the fixed
  `body.mobile-menu-open::before` scrim @ z 950 painted above it and ate the
  taps) into `<body>`; drawer now `position: fixed; top: 0; bottom: 0;
  width: min(85vw, 320px)` @ z 1010 with an in-panel close `X` (aligned over
  the hamburger's footprint); backdrop @ z 1000 `rgba(7,18,38,.6)` closes on
  tap; closed state inert (`pointer-events:none` + `visibility:hidden`);
  scroll lock on `html/body.mobile-menu-open` kept; Esc / backdrop / X /
  link-tap all close; drawer items right-aligned, `min-height: 44px`, full
  tap width; out-of-panel toggles `inert` while open; one `aria-controls`
  family keeps `aria-expanded` in sync; z-contract anatomy + verification
  recipe locked in §6. Verified in Playwright at 393x852 + 360x740, `en` +
  `fil` (elementFromPoint per item, full click-through, screenshots).
- 2026-11-08 · Guest landing page (`/`) · redesigned for the 5-second
  resident test: H1 states the task, one filled gold CTA ("File a complaint")
  reused verbatim in hero/sticky bar/final band; navy gradient hero with an
  HTML/CSS phone mockup (Sample chip, COMP-2026-00000 tracker card, step-4
  progress bar) shown ≥1024px and re-rendered below id="process" <1024px;
  live proof strip reuses TransparencyFeed payload (filed/resolved/in
  progress/active offices, avg only at ≥5 resolved) with friendly empty
  state at 0; 6 category tiles → /register; 5-step "What happens after you
  file" (same `dashboard.steps.*` names and pill colors); status breakdown
  bar + 3 latest public rows (bar only ≥5 reports); 3 reassurance cards +
  native FAQ (4 items — emergency number withheld until the LGU provides
  one); sticky mobile CTA ≤767px @ z 800 (IO-driven hide at hero/final CTA,
  inert under the drawer); Inter self-hosted (`public/fonts/inter`); all
  strings in a new `landing` lang group (en/fil, Filipino pending LGU
  review); shared public-ui partial extracted from the Transparency register
  (`.tr-container`, `.tr-stats`, `.cat-icon`, `.status-pill`, `.progress`,
  `.ticket-id`) — the register consumes it too, no forked rows. Verified in
  Playwright at 393/360/768/1024/1310, en+fil, plus synthetic 0/4/17-report
  data states and a click-through of every drawer item.
