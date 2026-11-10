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

**Inter is self-hosted** (`public/fonts/inter/*.woff2`, `@font-face` in
`app.css`, weights 400–800). **LOCKED: no third-party font request.** A page
must never pull a font from an outside host — that hands the visitor's IP
address and User-Agent to a third party, on a municipal portal, on every page
view. The retired faces (Cormorant Garamond, DM Sans, IBM Plex Mono) are gone
from the layouts and from every page: `--font-mono` is the system mono stack,
and no page declares a serif.

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
- **Resident dashboard content:** `.dashboard-content` (`32px 0 48px`,
  `24px 0 32px` ≤768px) separates the welcome hero from the columns (top) and
  the last card/pagination from the navy footer (bottom). Containers butt with
  24px (columns gap, rail gap, section-heading `margin-bottom: 24px`,
  success/alert `margin-bottom: 24px`, track form `margin-bottom: 24px`,
  view-all `margin-top: 24px`, pagination `margin-top: 24px`); report rows
  keep 12px inside their list.
- **Banner:** full-bleed behind the fixed navbar, `padding: calc(var(--navbar-height) + 16px) 0 48px`,
  `linear-gradient(135deg, #0b1a33 0%, #17305a 62%, #1e3f73 100%)` +
  `repeating-linear-gradient(135deg, rgba(255,255,255,.035) 0 1px, transparent 1px 14px)`.
  The navbar offset lives *inside* the banner, never as a `padding-top` on the
  page: the visible gap below the navbar is 16px and the banner's navy butts
  against the navbar's navy. **Why:** a light `--bg` strip between two navies
  read as a "~50px mystery gap" (the repeated failure in §10) and pushed the
  h1 a full navbar-height away from the nav. 16px is on the spacing scale.
  Text stack is left-aligned as one block: h1 (34/700 −0.02em, left), privacy
  `margin: 12px 0 0` (15px, left, max 640px), CTA `margin-top: 12px` (left),
  updated `margin-top: 12px` (13px, left, flex 6px gap). **Why:** 12px below
  the h1 reads as one left-aligned unit and sits optically centered under
  the fixed navbar.
  Staff/admin workspaces override `.tr-banner { padding-top: 48px }` because
  their shells already offset the navbar — they must not add it twice.
  Content: h1 white 34/700 −0.02em, privacy sentence `rgba(255,255,255,.82)`,
  optional `<details wire:ignore>`
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
stat strip: **none** (2026-11-09: removed) — the hero runs into the filter bar; keep the page flat on a phone
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

### Shell D — standalone error page (401 · 403 · 404 · 419 · 429 · 500 · 503)
```
brand header: navy-900, 64px, logo + DAET LISTENS + "LGU Daet · Camarines Norte"
              (no fixed navbar, no drawer, no session/DB lookups)
centered card (640px max): 64px navy-50 icon circle → "ERROR <code>" kicker →
                           h1 → one-line message → ONE gold primary + one secondary
minimal footer: navy-950, 2px gold top accent, copyright + portal line
```
**Why a separate shell:** the normal navbar, notification bell and shared site
footer all query the database (unread notifications, `SystemSetting` copy), and
the error page is exactly the surface that must survive a database or asset
failure. It therefore renders none of them — but it still uses the portal's
tokens, type, `.tr-container`, `.tr-card`, `.tr-button` and `.empty-state`
vocabulary so it never reads as a browser default. The page has no banner, no
stat strip, no filter bar and no list; the card is the whole message. Secondary
action is "Go back" (history) on 401/403/404/419 and "Try again" (reload) on
429/500/503. **LOCKED:** one `<h1>`, one primary action, no raw exception text,
no stack trace, no database-backed chrome.

---

## 5. Component catalog

Each entry: purpose → anatomy → variants/states → class/Blade name → notes.
Locations: shared rules live in `app.css`/`tokens.css`; Transparency-specific
component CSS lives in the Livewire component (all values are tokens) until a
second page consumes them, at which point they are extracted.

### 5.1 Banner / page header — `.tr-banner`
Purpose: anchor the page; state what it is and what it is not.
Anatomy: `<header class="tr-banner"><div class="tr-container">h1,
.tr-privacy, optional details.tr-how, .tr-updated`.
Full-bleed behind the fixed navbar (`padding-top: calc(var(--navbar-height) + 16px)`,
`.transparency-page { padding-top: 0 }`) — the navy runs uninterrupted from the
navbar into the banner, with 16px of breathing room below the navbar. The text
stack is left-aligned as one block (h1 left, privacy `margin: 12px 0 0`
left, CTA `margin-top: 12px` left, updated left).
Workspace shells (staff/admin) keep `padding-top: 48px` since their shell
already offsets the navbar.
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

### 5.24 Resident dashboard content — `.dashboard-content`
Purpose: separate the welcome hero, the report/rail containers, and the navy
footer with visible breathing room on every breakpoint.
Anatomy: `.dashboard-content` (`32px 0 48px`; `24px 0 32px` ≤768px) wrapping
`.dashboard-columns` (reports + rail, `gap: 24px`) with
`.dashboard-section-heading { margin-bottom: 24px }`, track form
`margin-bottom: 24px`, view-all `margin-top: 24px`, pagination
`margin-top: 24px`, success/alert `margin-bottom: 24px`, rail `gap: 24px`,
report list `gap: 12px` (rows only — not containers).
Why: the 32/48 band is the only separation between the last card and the
footer's gold accent; off-scale 16/20 separators made headings, banners and
cards read as one block.

### 5.25 Citizen mobile redesign (≤640px) — dashboard + My Complaints
Purpose: ≤640px overview + list that never changes desktop (`@media
(max-width:640px)` only; new markup `display:none` ≥641px, desktop
`before/after` pixel-diff zero at 1310×900).
Anatomy (mobile): hero `padding-top: calc(var(--navbar-height) + 16px)` (56px
on mobile, var — never hardcoded), greeting 24/600 + one 14px line, CTA
full-width 48 flat gold 12px; summary tiles 3-up ~72px (num 24/700, label 12
muted, 12px radius) linking to `/complaints?status=`; recent 3 only
(`nth-child(n+4) hide`), dashboard search/chips hidden (complaints has
equivalents); card rows: ticket mono 12 muted | status 12 nowrap,
title 16/600 clamp 2, `Category · Department` 13 muted 1-line ellipsis,
progress 5×4px + `Step N of 5 · Status` 12 muted left + date `M j` right,
no chevron/min-height, padding 16, gap 12, radius 12, `:active` bg +
`:focus-visible` ring, status/progress never truncated; stats 3 tiles +
muted avg line (hidden when null); search 48 visible, dept/category/sort
behind Filters button + badge (div panel, not `<details>`, always open ≥641px,
button hidden); chips horizontal scroll-snap + fade, hidden scrollbar, active
scrolled in, 36 visible / 44 tap via `::before`; top bar bell/hamburger
40×40 radius 10 border same, icons 20, 44 touch via `::before`.
Why: the mobile list repeated desktop work (full list + search + chips) and
cramped meta/chevron/dead space; the compact overview + shared card keeps one
job per screen on a phone without forking desktop markup.

### 5.26 Submission receipt (success page) — `.cs-*`
Purpose: the light receipt that ends the citizen filing flow
(`/complaints/{id}/success`). Replaces the one-off dark serif page: if a
confirmation does not look like the portal, the portal does not look finished.
Server-redirected here after `complaints.store` succeeds.

Anatomy (one centered column, max 640px inside the shared `.tr-container`,
page padding `calc(var(--navbar-height) + 32px) 0 48px`):

1. **Hero card** (`.cs-hero`): green check circle (48px, `--status-resolved-*`
   colours — meaning, not decoration), one h1 "Your complaint has been
   submitted", one-line lede carrying the routed office and the live
   `nav.my_complaints` label. Below: **ticket strip** (`.cs-ticket`,
   `--surface-soft` field) — uppercase label, mono 20px value (`tnum`,
   `--navy-900`), a "save this number" whisper — then **fact rows**
   (`.cs-facts`, the wizard-review pattern: muted key left, 14px value right,
   hairlines) — Status (shared `.status-pill.<key>` from the live status),
   Filed (`M j, Y` localized), Routed to (`.dept-badge[data-dept]` + name;
   `transparency.v2.department_pending` when unassigned), Photos
   (`trans_choice`, only when > 0) — and the **progress bar**: the shared
   `.progress` 5 segments (§5.15 base from `partials.public-ui`, widened by
   `.cs-page .progress { width: 100% }`), `role="img"` +
   `my_complaints.progress_aria`, visible "Step N of 5 · Status" from the
   **live** status so a revisit never lies (`closed` completes the bar,
   rejected renders label only).
2. **What-happens-next card** (`.cs-next`): the same three steps the wizard
   teaches (`complaint_create.wizard.next_1..3`, one copy, two pages) with
   24px navy-50 number circles; a hairline note states which location source
   was used (photo GPS / address·map / none) instead of a wall of prose.
3. **Auto-redirect strip** (`.cs-redirect`, `--surface-soft`): clock icon +
   `role="timer"` countdown "Redirecting to My Complaints in 6 s" (template
   from `complaint_success.redirect.countdown` via `data-redirect-template`,
   based on `my_complaints` menu label) + ghost text cancel "Stay on this
   page" (§5.8 style; 44px target). JS: data attributes are the contract
   (`#cs-redirect`, `data-redirect-url/-seconds/-template`); ticks
   **pause while the tab is hidden** and the redirect never fires unseen;
   cancel sets `[hidden]` (never `style.display`). No libraries, page
   script in `@push('scripts')`.
4. **Actions** (`.cs-actions`): ONE gold primary "View your report"
   (gold-500/navy-900, radius-sm — the wizard CTA language) + shared white
   `.tr-button` "Go to My Complaints". Stacked full-width 48px at ≤768px,
   inline 44px on desktop (§5.4 LOCKED numbers).

States: default only; hover on CTAs (gold-300 fill / navy-50 fill), focus
rings `--ring`. Mobile ≤768px: 16px page gutters, h1 22px, strip+buttons
stacked, cards 20px padding — delivered in one media block. The receipt is
deliberately narrow (640px reading measure) while remaining inside the
standard container gutters.
Tokens: `--bg`, `--surface`, `--surface-soft`, `--line`, `--line-soft`,
`--text`, `--muted`, `--navy-50/700/900`, `--gold-300/500`,
`--status-resolved-bg/-text`, `--radius-sm/-lg`, `--shadow-card`, `--ring`,
`--tracking-display`, `--font-mono`, `--navbar-height`.
Why: after a submit the person needs one glance — did it work, what is my
number, what happens now — then hand-off either to tracking (primary) or
away with minimal friction (auto-redirect with a visible clock and a stay
control, never a blind hop).

### 5.27 Error page — `.error-page` (Shell D)
Purpose: keep the portal usable, legible and branded when a request cannot be
completed — the one page a resident reaches when something is already wrong.

Anatomy (`resources/views/errors/layout.blade.php`):
1. **Brand header** (`.error-header`): navy-900 fill, 2px gold-500 bottom accent,
   64px row with the LGU seal + `DAET LISTENS` + `LGU Daet · Camarines Norte`
   (the whole lockup links home). On ≤640px it becomes 56px and drops the
   right-side "Official complaint portal" tag; the brand stays.
2. **Main** (`.error-main`): `48px 0` desktop, `32px 0` mobile, inside the shared
   `.tr-container` (1040px, 24px gutters / 16px ≤768px).
3. **Card** (`.tr-card.error-card`): 640px max, centered, `48px 32px` padding
   (`32px 24px` ≤640px), `--surface` + `--line-soft` + radius-lg +
   `--shadow-card`. Contents in order:
   - `.error-icon` — 64px navy-50 circle, 28px 1.8-stroke line icon keyed by
     status (search 404 · lock 401/403 · clock 419/429 · server 503 ·
     alert 500/unknown);
   - `.error-kicker` — 12px/600 uppercase `--muted`, `Error <code>`;
   - `.error-title` — the single `<h1>`, 34px/700 `--tracking-display`
     (28px ≤640px);
   - `.error-message` — 15px `--muted`, max 46ch, one or two sentences;
   - `.error-actions` — ONE gold primary `.error-primary` (gold-500 → gold-300
     gradient, navy-900 13/700 uppercase, radius-sm, 44px mobile / 40px desktop,
     hover lift + `--shadow-lift`, focus `--ring`) plus ONE secondary
     `.tr-button` — "Go back" (browser history; hidden until history exists) or
     "Try again" (current URL) depending on the code.
4. **Minimal footer** (`.error-footer`): navy-950 fill, 2px gold-500 top accent,
   64px row with `© <year> Municipality of Daet` and
   `footer.portal`; stacks at ≤640px.

Variants: 401 (sign in), 403 (permission), 404 (missing), 419 (session expired),
429 (throttled), 500 (server), 503 (maintenance) + generic `4xx`/`5xx` for any
other status. Copy lives in `lang/{en,fil}/errors.php`; no raw exception text
is ever printed. States: default + hover/focus on the two actions; the back
button is only revealed by JS when `history.length > 1` so it is never a dead
control. `prefers-reduced-motion` removes the lift.
Tokens: `--bg`, `--surface`, `--line-soft`, `--text`, `--muted`, `--navy-50`,
`--navy-700`, `--navy-900`, `--navy-950`, `--gold-300`, `--gold-500`,
`--radius-sm`, `--radius-lg`, `--shadow-card`, `--shadow-lift`, `--ring`,
`--tracking-display`, `--font-sans`.
Deliberately **not** included: the app navbar, notification bell, mobile drawer,
Livewire, or the shared site footer — each depends on the database/session and
would turn a database outage into a second failure while rendering the error
page. The card reuses the shared `.tr-container`, `.tr-card`, `.tr-button` and
`.empty-state` vocabulary, so it still reads as Daet Listens.

### 5.28 Filter popover (mobile, ≤640px) — `.mc-filters-panel` as an anchored overlay
Purpose: the resident filter panel must not displace the list. It floats below the
Filters button as a button-anchored popover — never in the content flow.
Anatomy: `.mc-filters-panel` (2-col grid, 16px padding, `--surface`, 1px `--line-soft`
hairline, `--radius-sm`, `--shadow-lift`) anchored to the relative
`.tr-filters__row1` at `top: calc(100% + 8px); right: 0; width: min(100%, 340px);
z-index: var(--z-popover)`. It overlays the chips row and the list — opening and
closing never shifts layout.
Behaviour: the "Filters" toggle opens/closes the popover; a tap/click outside the
popover (or its trigger) closes it; `Escape` closes it and returns focus to the
trigger. Changing a select **never** auto-submits — q, department, category and
sort are drafts. "Apply" (gold, `type="submit"`) commits once (GET round-trip,
URL persists); "Clear filters" drops to the bare `/complaints`. Closing the
popover (toggle / outside / Esc) discards unsaved drafts and reverts the selects
to the values in the URL, so the badge and aria-hidden state always testify to
the applied filters. The Apply/Clear row (`.mc-filters-actions`) is visible at
all breakpoints; ≤1024px it wraps to a right-aligned full-width second row under
the selects, ≥1025px it sits inline after "Newest first". Desktop (≥641px) keeps
the inline always-open panel with the toggle hidden.
States: closed (`[hidden]` + `aria-hidden="true"` + `aria-expanded="false"`), open
(`[hidden]` removed, `aria-hidden="false"` + `aria-expanded="true"`). No transitions
are used, so `prefers-reduced-motion` needs nothing extra.
Tokens: `--surface`, `--line-soft`, `--radius-sm`, `--shadow-lift`, `--z-popover`,
plus the existing control tokens. **No new colours, radii or shadows.**
Why: expanding in-flow pushed the whole list down under the user's thumb — a layout
jump, a taller page and a worse thumb target. A button-anchored popover gives one
overlay surface, exactly three close paths (toggle / outside / Esc), and zero reflow.

### 5.29 Needs-information panel (resident) — tickets waiting on the resident
Purpose: when the office needs more from the resident, this is the one screen where
that lands. It is the resident-facing counterpart of the staff "Ask for evidence"
flow; `needs_information` without this panel is a dead end and never happens.
Anatomy (top of the ticket body, right after the back link): a `--status-progress-bg`
card (1.5px amber hairline, `--radius-sm`, `--shadow-card`) with: a 44px navy-900
icon tile, `.info-request__kicker` (10px/800 uppercase "Waiting on you",
`--status-progress-text`) + `.info-request__title` (20px/700) — heading states the
state ("needs more information" / "we received your reply"), a 13.5px lede
(`--navy-900`), then the ask as a white `.info-request__question` card
(`Asked by <name> · time` line in amber-caps, then the question text in `--navy-900`)
— when the ask was flagged without a question (legacy), a generic fallback line is
shown — then either the answered box (`.info-request__answer`, white) or the reply
form: `Your reply` textarea (min 92px, 10px uppercase-muted label) + optional
photos input (jpeg/png/webp, ≤5, 5 MB each) + gold `Send to the department` button;

States: waiting (kicker/heading open, reply form) and answered (heading + reply text
box, no form). The section renders whenever there is an open/answered information
request **or** the ticket is flagged `needs_information`; legacy orphans still render
and the resident's reply materialises the request server-side (service invariant:
`needs_information` is never set without an accompanying question — the plain review
decision cannot carry it).
Tokens: `--status-progress-bg/-text/-dot`; `--surface`, `--line-soft`, `--navy-900`,
`--radius-sm`, `--shadow-card`, `--gold-500`, existing control tokens. **No new
colours.**
Why: two different things wearing the same costume made the resident page feel broken
(a banner with nothing to act on). One dedicated panel, one ask, one reply path is
the entire "needs information" story on the resident side.

### 5.30 Resolved close-confirmation panel (resident) — `.close-report`
Purpose: when the department has marked a report `Resolved`, the resident can
confirm the outcome and close it. If they do not act, the report auto-closes
7 days after `resolved_at` (scheduled `complaints:auto-close-resolved`).
Anatomy: `--status-resolved-bg` card (resolved-dot hairline, `--radius-sm`,
`--shadow-card`) with 44px navy-900 icon tile, resolved kicker, 20px/700 title,
lede explaining the 7-day auto-close, and one gold primary button
(`--gold-500`/`--navy-900`, 44px min-height, `--ring` focus) posting to
`complaints.close`. Visible only to the complaint owner while status is `Resolved`.
States: actionable (form shown) only; once closed the normal Closed/stepper state
renders. Tokens: `--status-resolved-*`, `--navy-900`, `--gold-300/500`,
`--radius-sm`, `--shadow-card`, `--ring`. No new colours.
Why: resolution and closure are different decisions — the office finishes the
work, the resident confirms it is done for them.

### 5.31 Navbar identity indicator (staff/admin) — `.nav-identity`
Purpose: make the current account context unmistakable in the fixed top bar
without opening any menu. Staff/department accounts and administrators see who
they are operating as on desktop and mobile; citizens see no extra chrome.
Anatomy: `.nav-identity` (inline, on the navy navbar) contains either an admin
role badge (`.nav-identity__badge--admin`, `--gold-300` text on a gold-500 tint,
6px radius, 10px/700 uppercase) or a department context: the canonical
`.dept-badge[data-dept="CODE"]` (§3.2/§5.11, department colour tokens) plus
`.nav-identity__label` (12px/500, light on navy, truncated). Staff with no
assigned department render a neutral staff badge (`.nav-identity__badge--staff`)
plus the "Department pending" label.
Placements (targeted): desktop `nav-actions` (before the account dropdown),
compact/tablet `nav-mobile-right` where visible, and a direct `.nav-identity-bar`
child of `.navbar-flex` for the `body.is-auth` ≤640px shared-drawer mode (so
identity remains visible when `nav-mobile-right` is hidden). The same
department/role treatment appears in the shared mobile drawer header and legacy
mobile menu header; dropdown headers repeat it as `.dropdown-identity`.
States: static (no interactive state). Labels collapse at tight widths
(`.nav-identity--compact` / breakpoint rules) so the badge/code remains legible.
Tokens: existing `--dept-*`/`--dept-*-tint` + `[data-dept]`, `--gold-300`,
`--gold-500` (tints via `color-mix`), `--color-white`, `--radius-sm`-scale
shape (6px badge), `--font-sans`. **No new colours.** JS contracts untouched
(no existing `id`/`data-*`/`aria-controls` changed).
Why: a person handling complaints for a department, or administering the whole
system, must never have to guess which account they are in — the navbar states
it once, always, in the same place the account lives.

### 5.32 Busy / loading action state — `.is-loading` / `aria-busy="true"`
Purpose: give immediate, honest feedback when an action with real pending work
is triggered (Apply, Submit, Login, Save, Send, state changes) and prevent
double activation. **Applies to all sectors** (auth, citizen complaints,
settings/profile, staff, admin, invitations/public forms). Do not use it for
instant client-only toggles (opening a popover, local wizard step changes) or as
a page-navigation progress bar.

Anatomy: the pressed control keeps its label and gains an inline spinner
(`::after`, 1em, `currentColor`, circular). The spinner inherits the control's
text color, so it works on every variant — gold primary, navy/secondary, white,
danger, ghost — with **no new colours**. Opacity reduces slightly while busy;
pointer interaction is blocked.

States:
- **Idle:** normal control.
- **Busy:** `.is-loading` + `aria-busy="true"` + disabled/`aria-disabled="true"`
  + `data-action-busy="true"` (JS marker for restore). Visual spinner via
  `::after`; `pointer-events: none`.
- **Settled (success/navigation):** the request completes, navigates, or the
  region updates; busy clears naturally.
- **Settled (validation/error, same page):** a full render clears state; fetch
  handlers must call `window.daetActionLoading.setBusy(el, false)` and surface
  the error via the existing status/toast pattern.
- **bfcache restore:** busy/disabled state is cleared on `pageshow` when
  `event.persisted` (Back/Forward cache).

Motion & accessibility (LOCKED for this pattern): the spin animation runs only
when motion is allowed; under `prefers-reduced-motion: reduce` the animation is
removed and the indicator becomes a static dotted ring (still perceivable, no
motion). Controls keep their visible focus treatment when applicable; do not
trap focus. The existing label remains available; `aria-busy="true"` announces
the pending state. Native `<button>`/submit inputs use `disabled`; non-native
action controls use `aria-disabled` + handler guards.

Implementation contract (for future use):
- **Standard forms:** add `data-loading-submit` to the `<form>` (applied across
  the app). The global handler in `resources/js/app.js` (`initializeSubmitGuards`
  / `window.daetActionLoading`) also auto-applies to non-GET forms. Legacy
  `data-disable-on-submit` remains supported (invitation single-use flows).
  Opt out with `data-loading="off"` or `data-loading-managed` (e.g. forms whose
  JS fully owns submission/validation or fetch-only autosave like
  `data-preferences-autosave`).
- **Fetch / custom actions:** call `window.daetActionLoading.setBusy(button, true)`
  before the request and `setBusy(button, false)` on settle/error; pair with an
  `aria-busy`/status region where a whole list updates (notifications pattern).
- **Livewire:** use `wire:loading`/`wire:target` to toggle `.is-loading` and
  `aria-busy` on the targeted control, e.g.
  `wire:loading.class="is-loading" wire:loading.attr="aria-busy=true" wire:target="save"`.
- **Shared CSS:** `resources/css/app.css` (`.is-loading` / `[data-action-busy]`
  + `@keyframes daet-action-spin`, reduced-motion override). Spinner uses
  `currentColor`; **no new colour tokens, no hard-coded hex**.
- **Do not** replace button labels with hardcoded English "Loading/Sending"
  strings in JS. Keep the localized label visible; use the spinner + `aria-busy`.
  If a design ever needs visible busy text, add `en` + `fil` lang keys first.

Demo: `/design-system` → "Busy / loading actions" (all major variants, reduced-
motion note).

### 5.33 Audit browser — `.admin-audit-browser`

Purpose: let an administrator browse the audit trail by entity — one office, one
person, or everyone — instead of rebuilding a dropdown query each time. It is
the primary navigator on `/admin/audit-trail`; search + Action stay secondary
filters below it. The trail itself is unchanged. Admin-only surface.

**The browser is bounded.** The log grows with the municipality (every resident
filing is a row), so the roster never renders everyone: each group shows a
short preview and opens on demand. A component that renders 20,000 people is a
component that stops working.

Anatomy — three bands in one card:
- **Scope band** (`.admin-audit-browser__scope`, always visible): a type icon,
  `Showing`, the selection name, and the count (`<n> entries`, or `<filtered> of
  <total> entries` when a filter is active). `Clear selection` returns to
  Everyone. The band is `--navy-50` so the eye lands on "what am I looking at".
- **Search band** (`.admin-audit-browser__bar`): `Find a person…` + a `Search`
  button. It searches **people**, not the trail — two different questions, two
  different inputs. The form works without JavaScript; the type-ahead only
  replaces the page load.
- **Panes** (`.admin-audit-browser__panes`, ≥641px: two columns):
  - *Scope rail* (`.admin-audit-browser__contexts`, a `<nav>`): a `Scope |
    Entries` head — **every number says what it counts, and counts itself**
    ("1 entry", "3 entries") — then `Everyone`, a `Residents` section with the
    `All residents` scope, a `Departments` section with one row per office
    (`.dept-badge` + name + count), and an `Automated` section with the records
    that have no actor. Office names truncate with an ellipsis + `title`
    tooltip. A standing line under the rail explains that office totals include
    the resident filings routed to that office, so an office total never
    silently disagrees with the sum of the people under it.
  - *Roster* (`.admin-audit-browser__roster`): a `People | Entries` column head,
    then groups (`.admin-audit-browser__group`) — offices, then `Administration`
    (office-less admins), then `Residents`. A person row is avatar/initials +
    name + role + count; the role appears only where it varies (inside an
    office, staff and admins share a list). Soft-deleted accounts are marked
    `Former account`.

Variants / states:
- **Everyone** (default): no query string; the roster shows every group.
- **Office** (`?department_id=`): the roster narrows to that office.
- **Person** (`?actor_id=`): the roster stays whole and the person row is
  selected.
- **Residents** (`?residents=1`): everything a resident did — the filings they
  made. The trail filters on the actor's role and the roster narrows to the
  residents group.
- **Automated** (`?system=1`): the records with no actor.
- **Legacy combined** (`?department_id=&actor_id=`, the old dropdown pair): both
  filters still apply, the label names the person and their office, and both
  rows are marked selected. New links never generate this form.
- **Group expanded** (`?people_group=&people_page=`): one group opens in place,
  **25 people to a page** — never a dump — with Previous/Next and `Collapse`.
- **People search** (`?people_q=`): the roster is replaced by the matches
  (server-side, top 20, people with activity only) with the office shown
  beside each name.
- **Search / Action**: preserved across entity links; clearing an entity does
  not clear them; the filter's `Clear` keeps the entity.
- **Empty**: "No entries for this selection" + `Show everyone`; with active
  filters: "No audit entries match" + `Clear filters`; "Nobody matches “x”"
  for a people search with no hits.

Selected state: `--navy-50` background, `--navy-900` text, 700 weight, and a
3px **navy** inset bar (`--navy-700`) — never a gold side bar (§4 lock).
`aria-current="true"` accompanies `.is-selected`.

Responsive: two panes at ≥641px; at ≤640px the panes collapse into a
`<details>` toggle labelled "Browse departments and people" (mirroring the
Filters disclosure) and the person rows stack the role under the name. The
scope band and the search band always stay visible. The `<details>` is forced
open on desktop (same `display: contents` + JS contract as
`.admin-audit-filters-collapsible`) and opens by default when an entity is
selected or a search is running.

Accessibility: the rail is a `<nav>` with its own label; rows are real links so
middle-click/open-in-new-tab work; selected rows expose `aria-current`; the
count column carries a screen-reader unit; touch targets are ≥44px; focus uses
`--ring` / `--ring-inset`.

Tokens: `--surface`, `--bg`, `--line`, `--line-soft`, `--navy-50`,
`--navy-100`, `--navy-700`, `--navy-800`, `--navy-900`, `--gold-500`,
`--text`, `--muted`, `--faint`, `--radius-sm`, `--radius-pill`, `--radius-lg`,
`--shadow-card`, `--ring`, `--ring-inset`, `--font-sans`, `--status-*` (via
`.dept-badge`). No new tokens.

Why: an audit trail is investigated by entity ("what did this office do?", "what
did this person do?"), not by rebuilding a dropdown query each time. The URL
mirrors the selection so the view is bookmarkable, shareable and refresh-safe.
The URL only filters the view; `role:admin` middleware still authorizes every
request and the controller re-validates every ID. Counts are cached for 60
seconds (one aggregate instead of one per page view) and the roster is sliced,
so the page costs the same whether the log holds 300 entries or 3,000,000.

Demo: `/design-system` → "Audit browser" (scope band, search band, rail,
roster with a bounded group and an expanded group).

### 5.34 Soft navigation — regions swapped without a reload

Purpose: a GET-filtered page (a directory, a queue, an audit trail) should not
redraw the whole browser when the admin changes a scope, turns a page or types
in a search. The page is drawn **once**; the regions that changed are swapped in
place. Full write-up: `docs/soft-navigation.md`. Skill:
`.opencode/skills/soft-navigation/` (the operational recipe, for applying this
to another page).

Anatomy — four parts, identical on every page:
- **Partials** (`resources/views/<area>/partials/`): one Blade file per region.
  The page includes them and the endpoint renders them, so **one markup per
  list** however the page is drawn.
- **A JSON endpoint** next to the page route, in the **same middleware group**:
  it renders the partials for the state in the query string and returns them as
  an object of HTML strings.
- **Swap targets**: the regions carry a `data-*` attribute — the contract
  between the view and the script.
- **A client controller**: fetch → swap `innerHTML` → `history.pushState`.

Rules (LOCKED):
- **The URL is the truth.** A soft navigation writes the URL; it never owns it.
  Refresh, Back, Forward, a bookmark and a shared link behave as before.
- **The server renders everything.** The browser never builds markup.
- **Graceful degradation.** With JavaScript off, links and forms are real
  links and real forms. A failed request falls back to a real navigation —
  never stale fragments.
- **Never redraw what the user is typing in.** Inputs live outside the swapped
  regions, so focus and the caret survive.
- **The page and the endpoint share the controller's state helpers**, so a
  fragment can never disagree with the page.

Pitfalls that break it (each one is a real trap in this codebase):
- `form.action` is shadowed by a control named `action` → use
  `form.getAttribute('action')`.
- Laravel's paginator links inherit the **current request** URL → a fragment
  rendered by the endpoint would link back to the endpoint; pin it with
  `->withPath(route('x.index', [], false))`.
- `route()` returns an absolute URL → compare `.pathname`, not the whole URL.
- A prevented submit leaves the shared loader spinning → the loader clears
  itself on the next tick, or mark the form `data-loading-managed`.
- A stale `page` after a new filter → delete `page` when the filter changes.

Demo: `/admin/audit-trail` — every scope change, page turn, filter and search
swaps in place; the URL still carries everything.

### 5.35 Admin phone card — `.admin-phone-card` / `.admin-phone-cell`

Purpose: every admin list has to work on a phone, and a row that loses its
column headers stops being readable — "WST", "Pending", "1d" say nothing on
their own. This is the shared shape an admin row takes below 640px: one card,
one label column, one type scale. Sized here once, so every admin page reads
the same.

Anatomy:
- **`.admin-phone-card`** — the row's content: a 28px checkbox column and the
  content column, aligned to the **top** so the checkbox sits beside the title
  instead of floating in a gutter.
- **`.admin-phone-cell`** — a value cell. It carries a `data-label`
  ("Department"), rendered as an 84px column: 10px/700 uppercase, `--muted`,
  `+0.08em`. The value takes the rest of the row.
- **`.admin-phone-values`** — the values inside a cell (pills, badges). They
  share one line and wrap. **On a desktop the wrapper is `display: contents`**,
  so adopting the pattern cannot change the page's desktop layout.

Adopting it — three steps:
1. the row's content gets `.admin-phone-card`;
2. every value cell gets `.admin-phone-cell` plus a `data-label`;
3. the values inside a cell are wrapped in `.admin-phone-values`.

**The label must be the same word the desktop column header uses.** The two are
one fact shown twice; the header is the desktop half, the `data-label` is the
phone half.

**Before any of it: the page's column must be allowed to shrink.**
`.admin-page` is a grid, and an implicit `auto` track is sized by the content's
max-content — so a table that is deliberately wider than a phone stretched the
page, and the body (`overflow-x: clip`) cut the right-hand third off every
table. `.admin-page { grid-template-columns: minmax(0, 1fr) }` lets the track
shrink to the container and leaves the table to scroll inside its own wrapper.
This is a page-level fix: it repairs every admin list at once.

Why: the phone is where the municipal queue is actually worked. The label is
the whole difference between a scannable card and a pile of anonymous values —
and a shared class is the only way "scannable" means the same thing on every
admin page.

Demo: `/admin/complaints` (the queue rows) and `/admin/audit-trail` (the trail
cards) on a 393px viewport.

### 5.36 Proof capture (staff) — `.proof-capture`

Purpose: let a staff member attach the photograph that proves the work was done,
and state the office's proof rule **before** they choose a status. The server
already refuses a move to `In Progress` or `Resolved` without a photo
(`StaffController@update`); this component moves that refusal earlier, so a staff
member learns what the office owes instead of discovering it from a validation
error after pressing send.

Staff-only. The resident filing wizard has its own picker and is a different
component: it collects evidence, this one collects *proof of work* and carries a
rule. They share the portal's tokens and visual language (same 48px icon circle,
same 44px buttons, same dashed zone) but not their markup, so either can change
without touching the other.

**Both staff and administrators use it.** The staff complaint page and the admin
complaint page both carry the component, because the rule is the same for both:
an administrator settles a ticket no more loosely than the office does.

Anatomy:

- **Requirement note** (`.proof-capture__note`) — an icon, a title and one line of
  body, above the picker. Neutral (`--surface-soft` / `--line-soft` / `--navy-700`)
  while the chosen status needs no photo; the amber action-needed family
  (`--status-progress-bg` / `--status-progress-text` / `--status-progress-dot`)
  once it does, and the body then names the status. The state is driven by the
  status select, so the note and the dropdown can never disagree about what is
  owed.
- **Zone** (`.proof-capture__zone`) — a dashed `--line` border on `--surface`; the
  gold `--gold-500` border on hover/drag; `--surface-soft` while dragging. The two
  file inputs are visually hidden but focusable, and the two visible buttons
  (`Photo library` / `Use camera`) are the real controls. The zone is also
  clickable — a convenience, never the only path.
- **Icon** (`.proof-capture__icon`) — a 48px `--navy-50` circle with a 24px
  `--navy-700` line icon, matching the wizard's picker.
- **Actions** (`.proof-capture__action`) — 44px min height, `--line` border,
  `--radius-sm`, `--navy-700` text, `--navy-50` on hover. `--ring` on focus.
- **Counter** (`.proof-capture__counter`) — `N / 5` with the number in
  `--navy-700`/700 and the label in `--muted`. `aria-live="polite"`.
- **Previews** (`.proof-capture__previews`) — a grid of `minmax(92px, 1fr)`
  squares; each is a `figure`-shaped tile (`--navy-50` underlay, `--line-soft`
  border, `--radius-sm`, `object-fit: cover`) with a 32px circular remove button
  in the top-right corner.
- **Error** (`.proof-capture__error`) — `--error-text`, 14px, `role="alert"`.

States: the note's **optional** and **required** states; the zone's default,
hover and dragging states; and the error line, which appears only for a
wrong-type/oversized file or a sixth selection. The server's own message renders
below the zone (`@error`), so a refusal is never silent.

Behaviour: the picker owns the selection and writes it back into the gallery
input through the DataTransfer API — a file input's own list cannot be edited, so
per-photo removal is only possible that way. The camera input is emptied only
after the write-back succeeds, so a browser that refuses DataTransfer still
submits the camera photos (the server reads both inputs and merges them).
**Without JavaScript the form is still a working form:** the browser puts the
files in the inputs, the server reads them, and what is lost is the preview, the
remove button and the live rule.

Tokens: `--surface`, `--surface-soft`, `--line`, `--line-soft`, `--text`,
`--muted`, `--navy-50`, `--navy-700`, `--navy-950`, `--gold-500`,
`--status-progress-bg` / `--status-progress-text` / `--status-progress-dot`,
`--error-text`, `--radius-sm`, `--radius`, `--ring`. **No new colours.**

Markup contract (the JS reads the markup, not a function call):
`[data-proof-capture]` · `[data-proof-gallery]` · `[data-proof-camera]` ·
`[data-proof-previews]` · `[data-proof-count]` · `[data-proof-error]` ·
`[data-proof-trigger]` · `[data-proof-note]` · `[data-proof-status]`.
Controller: `resources/js/proof-capture.js`; styles:
`resources/css/pages/staff-complaint.css`; markup:
`resources/views/components/proof-capture.blade.php`.

Why: an office that cannot photograph its own work cannot prove it did. The rule
was already on the server — what was missing was any way to satisfy it, because
the staff form had lost its upload surface entirely. Stating the rule beside the
picker (not only in the error) is what makes it a rule the staff member can
follow rather than a wall they hit.

Demo: `/design-system` → "Proof capture (staff)" (live; change the status to see
the note switch).

### 5.37 Proof in the timeline — `.proof-timeline`

Purpose: show the proof an update carried, and **where each photograph was
taken**. A photograph of a repair only means something if the place it was
taken at is on the record — otherwise it is a picture of anything, taken
anywhere, at any time.

The staff complaint timeline and the admin audit timeline both carry it. The
resident timeline does not show coordinates: a resident is told the work was
done and shown the photograph, and the exact position of a municipal work site
is not theirs to need.

Anatomy:

- `.proof-timeline` — a grid of `minmax(110px, 1fr)` tiles, 12px gap.
- `.proof-timeline__item` — a `figure`: the photograph, and its location.
- `.proof-timeline__item a` — a square frame (`--line-soft` border,
  `--radius-sm`, `--navy-50` underlay) with the photograph `object-fit: cover`.
- `.proof-timeline__location` — the coordinates, 10px `--muted`, with a 11px
  `--navy-700` pin icon. The coordinates are mono and may wrap over two lines.
- `.proof-timeline__location--none` — the same line in `--faint`, reading "No
  location recorded". **A photograph with no location says so** rather than
  being silently treated as proof of anything.

The admin timeline shows the same coordinates on its evidence card
(`.admin-evidence-card__location`, mono, `--faint`).

Tokens: `--surface`, `--line-soft`, `--navy-50`, `--navy-700`, `--muted`,
`--faint`, `--radius-sm`, `--font-mono`. **No new colours.**

Why: the office is being asked to prove its work. A photograph proves a place
only if the place is written down next to it; without that, "the crew was at
the site" is a claim, and the file has nothing to check it against. Saying
"No location recorded" is as important as saying where — a reader who cannot
tell the difference is being asked to take it on trust.

### 5.38 Deadline chip — `.sla-chip`

Purpose: say how long the office has left on a complaint, in one place, on
every surface that lists work. A report becomes the office's responsibility
the moment it is verified; from then the office owes the resident two things —
to start the work and to finish it. This chip is where that obligation is
visible.

Anatomy: two parts — **the action** ("Start work" / "Finish work") and **the
time** ("2 days left" / "3 days overdue"), separated by a middot. An 11px/600
pill, `--radius-pill`, 2px 8px padding, `white-space: nowrap` so the pair never
wraps mid-phrase.

States, and the family each belongs to:

| State | Class | Background / text | Means |
|---|---|---|---|
| On track | `.sla-chip--on-track` | `--status-review-bg` / `--status-review-text` | Comfortably inside the deadline |
| Due soon | `.sla-chip--due-soon` | `--status-progress-bg` / `--status-progress-text` | A day or less left |
| Overdue | `.sla-chip--overdue` | `--status-rejected-bg` / `--status-rejected-text` | The deadline has passed |
| Met | `.sla-chip--met` | `--status-resolved-bg` / `--status-resolved-text` | The work was done in time |
| Not applicable | `.sla-chip--none` | `--surface-soft` / `--muted` | Nothing is owed yet — not verified |

The colour is the status family the meaning belongs to, not decoration: a chip
that is late must not be dressed the same as one that is comfortable.

Where it appears: the **staff queue row** (beside the status and priority
pills), the **staff complaint banner** (beside the priority badge), and the
**admin dashboard department card** (as a count of what that office has let
slip, in the negative family).

Tokens: `--status-review-bg` / `--status-review-text`,
`--status-progress-bg` / `--status-progress-text`,
`--status-rejected-bg` / `--status-rejected-text`,
`--status-resolved-bg` / `--status-resolved-text`, `--surface-soft`,
`--muted`, `--radius-pill`. **No new colours.**

Why: a ticket nobody started must not look exactly like a ticket being worked
on today. Without the clock, "verified three weeks ago, still Under Review" is
one of forty rows in a queue and nobody is looking at it. With it, the row
itself says the office is late.

### 5.39 Live ticket notice — `.ticket-live-note`

Purpose: a ticket is worked by more than the person looking at it. Somebody in
the same office can move the case, settle it or leave a note while this page is
open — and a staff member who finds out only when the server refuses their
update has already wasted the work. The ticket page polls a fingerprint and,
when it differs, says who changed what. **It never replaces the page's form**:
a live update must not take the words out of somebody's mouth.

Anatomy:
- **`[data-live-ticket]`** — the root. Carries `data-endpoint` (the state
  endpoint), `data-fingerprint` (what is on screen) and `data-interval`
  (milliseconds, 20000 by default).
- **`.ticket-live-note`** — the notice, `hidden` until the fingerprint moves.
  It is **outside the form**. Amber action-needed family: a case that moved is
  something to read before pressing send.
- **`.ticket-live-note__text`** — the server's sentence, naming the actor and
  both statuses ("Maria Dela Cruz moved this complaint from Under Review to In
  Progress") or the internal note ("…left an internal note").
- **`.ticket-live-note__reload`** — an ordinary link to the same ticket, so it
  works without JavaScript and never traps the reader.
- **`.ticket-live-note__dismiss`** — hides the notice for that one change. A
  newer change is a new fact and may speak again.

Behaviour:
- An unchanged ticket costs one small request and touches no DOM.
- A hidden tab is not polled; it catches up on `visibilitychange`.
- The status pill in the banner (`[data-ticket-status-pill]`,
  `[data-ticket-status-label]`) is the shared §5.10 `.status-pill` recipe and
  is updated in place with the status the server returned. Inputs, the form,
  the timeline and the map are never rewritten.
- Without JavaScript the ticket is still a ticket; the reader reloads when
  they choose to.
- The endpoint is a machine endpoint: a person who opens it in the address bar
  is redirected to the ticket, not shown JSON.

Tokens: `--status-progress-bg`, `--status-progress-text`, `--radius-sm`,
`--radius-pill`. **No new colours.**

Why: a queue can be live and the ticket still stale. The person doing the work
needs the same truth as the person watching the list — but their hands are on
the form, so the update must inform them without interrupting them.

### 5.40 Staff action panels — `.staff-action-panel` / `.staff-field`

Purpose: the ticket is where a department acts, but three of the actions it
owns have no other surface — asking the resident for better evidence, handing
verification to another verifier, and moderating the public copy. They are not
navigation and not a list; they are short forms that belong beside the thing
they act on. The panel is the card those actions share, so a staff member sees
one grammar for "here is a thing the office can do" instead of three bespoke
boxes.

Anatomy:
- **`.staff-action-panel`** — an ordinary `.workspace-card` carrying a
  section list. The header is the page's existing icon + `h2`.
- **`.staff-action-panel__section`** — one action. A hairline rule separates
  two actions; the first has none. A section that only reports state (waiting
  on the resident) is still a section, because the question and the answer
  belong to the same action.
- **`.staff-action-panel__section-title`** — the action's verb, 13px/700.
- **`.staff-action-panel__hint`** — the sentence under it: what the action
  does, what it will cost, what the resident will see.
- **`.staff-action-panel__state`** — the current state of a conversation
  (waiting, answered). A waiting state uses the action-needed family so it
  cannot be mistaken for a completed one.
- **`.staff-action-panel__quote`** — the resident's question or reply, set
  apart with a left rule and `white-space: pre-line`; the resident's line
  breaks are part of what they said.
- **`.staff-field`** — a labelled form field: label (11px/700 uppercase
  `--faint`), control, and optional `.staff-field__hint` /
  `.staff-field__error`. Inputs are 44px-high or taller and take the shared
  gold focus ring.
- **`.staff-action-panel__submit`** — the one primary action in a section,
  navy on light, 44px minimum.

Variants:
- **Verification follow-up** (verifier only): ask form, waiting state,
  answered state, or the round cap. Delegation sits in a second section only
  when an eligible verifier exists.
- **Internal note** — a `.staff-field` inside the official response form, not
  a panel of its own. It is the internal half of the same update; splitting
  it into a second form would let the two halves drift apart.
- **`.moderation-panel--light`** — the shared moderation component
  (`resources/views/components/moderation-panel.blade.php`) re-toned for a
  light page. The markup is one component used by staff and admin; only the
  surface tone changes. The office can **withhold or redact**, never publish:
  the resident's visibility choice is stated, not offered as a control.

Behaviour:
- Ask and delegate are shown only when the viewer can verify and the report
  is still being settled (`Submitted` / `Under Review`). The page never
  advertises a control the service would refuse.
- The delegation select excludes the viewer and the assigned worker — the
  separation-of-duties rule — so the refused options are not offered.
- The ask form is not shown while a request is open or answered; the state
  box replaces it. Round 2 is the cap, and the page says so.
- Moderation is available to every staff member in the department, not only
  verifiers, because it is a content decision rather than a verification one.
- The review form itself (review decision / confirmed priority / verification
  method) is verifier-only, like the follow-up: a non-verifier sees the
  read-only state line (`staff.verification.read_only_body`), never a Save
  button the service would refuse.
- The internal note is written to the immutable log with `is_internal = true`
  and never rendered on the resident page, the public timeline or the
  transparency register.

Tokens: `--navy-900`, `--navy-800`, `--gold-500`, `--surface`,
`--surface-soft`, `--line`, `--line-soft`, `--text`, `--muted`, `--faint`,
`--error-text`, `--status-progress-bg` / `--status-progress-text`,
`--radius-sm`, `--radius-lg`, `--shadow-card`. **No new colours.**

Why: a staff member should not have to guess where the office's powers live,
and the resident's consent should not look like one of them. One card grammar
for the actions, one shared moderation markup, and no control for a decision
that is not the office's to make.

### 5.41 Password visibility toggle — `.field-toggle`

Purpose: let a person read back what they typed into a password field before
submitting — the single most common cause of "wrong password" on the sign-in
and registration screens, and of typos in the two confirm fields. The auth
(dark) password fields carried no affordance at all; the settings page had
only an undocumented text "Show"/"Hide" button, which read as another action
rather than a field affordance.

Anatomy: `<button type="button" class="field-toggle"
data-password-visibility-toggle="<input id>">` placed inside `.field-wrap`,
to the right of the password `.field-input` and before `.field-underline`.
Two feather glyphs (`eye` and `eye-off`) stack in the same grid cell and
cross-fade with a small pop rotation on toggle; `aria-pressed` on the button
is the only state CSS needs. Rendered via
`resources/views/auth/partials/password-toggle.blade.php`
(`@include('auth.partials.password-toggle', ['target' => '…'])`) — never
copy-pasted inline.

Visibility: **the button does not exist for the user until there is
something to reveal.** It renders invisible (`opacity: 0; visibility: hidden;
transform: … scale(0.5)`, `tabindex="-1"`); `app.js` adds `.is-visible` from
the input's `input` event. Emptying the field re-masks it and re-hides the
button. No eye floating over an empty box, ever.

Variants / states:
- **Hidden** (empty field): not tabbable, not clickable.
- **Reveal-ready** (field has text, `aria-pressed="false"`): `eye` glyph,
  accessible name is `auth_ui.common.show_password` (en + fil). Spring scale-in
  when it first appears.
- **Revealed** (`aria-pressed="true"`): input is `type="text"`; the
  accessible name flips to `auth_ui.common.hide_password`; the glyphs
  rotate/scale across. Focus returns to the input after every toggle.
- Hover: gold ink (`rgba(226,192,106,1)`) on a faint gold wash
  (`rgba(201,168,76,0.10)`); focus-visible: 3px `--ring`-family gold ring;
  resting ink `rgba(201,168,76,0.85)`. 44px × 44px target, `z-index: 2`
  above the input, `touch-action: manipulation`,
  `-webkit-tap-highlight-color: transparent`. Under
  `prefers-reduced-motion: reduce` all transitions drop.
- The wrap's input gets `padding-right: 52px` from
  `.field-wrap:has(.field-toggle) .field-input` — nothing else in the wrap
  moves, and non-password fields keep their padding.
- `.field-underline` is `pointer-events: none` (it was a decorative 2px strip
  at the field's bottom edge; it must never eat a tap meant for the toggle).

Tokens / colours: none new. The gold rgba values are the auth surface's own
palette (the same family the auth pages declare page-locally); the button
inherits nothing from the light portal and introduces no new hex. The
settings-page text toggle (`.settings-password-toggle`, light surface,
"Show"/"Hide") remains as-is — different surface, different idiom; do not
merge them.

Behaviour contract: bound in `resources/js/app.js` by
`initializePasswordToggles()` (`[data-password-visibility-toggle]`,
initialized-guard, re-bound on `livewire:navigated` / `pageshow`). Switching
`type` never resubmits the form and never triggers validation. Emptying the
input re-masks and re-hides the button automatically. The `.is-visible`
class is the only visibility state; `aria-pressed` is the only icon state.

Why: the eye is the one icon every password-field user worldwide already
knows; a labelled toggle inside the field costs no horizontal space, and the
mirror pair of names (show/hide) keeps screen-reader users in the same state
as sighted users.

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
| Anchored filter popover (≤640px, e.g. My Complaints) | **500** (`--z-popover`) |
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
browser at 393px: open the surface, then
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
| My Complaints (resident) | A/B | yes — h1 + ONE line subtitle + **one** CTA ("File a complaint", suppressed when the navbar shows it at this viewport) | none (2026-11-09: strip removed) | yes, URL-persisted `?status=&q=&sort=`; chips carry counts; dept select folded in only when meaningful | same `.report-row` + the resident extras (needs-response pill, visibility pill, Updated x ago) | empty = circle + "No complaints yet" + ONE "File a complaint" |
| Staff Complaint Queue / Admin All complaints (work lists) | A | yes — plain title + ONE line of scope ("Your office's queue"/"Filter the municipal queue"); no CTA | 4 existing metrics (do not invent) | yes — extended with review + priority (admin: + department, flagged, dates) — the same bar visual language, not a fork | same row component + staff columns (Review, Priority, Age); row click opens detail | empty per q-status = "queue clear"; no-results = same clear-filters box |
| Public complaint detail (`/transparency/complaints/{id}`) | C | no banner — back link only | no | no | stepper + timeline + evidence grid + alias/privacy card | "Exact location withheld" is the privacy state — keep it |
| Resident complaint detail (`/complaints/{id}`) | C | no banner — back link + breadcrumb dots, no cream/serif | no | no | stepper + actions for the resident (respond to info requests, toggle visibility) | same as above |
| Submission success receipt (`/complaints/{id}/success`) | receipt (§5.26) | no banner — light page, centered 640px | no | no | hero card (check, h1, mono ticket strip, fact rows, live 5-segment bar) + what-next card | auto-redirect strip with clock + "Stay on this page" cancel; actions: view report + my complaints |
| File complaint / create (wizard) | form | no banner | no | wizard steps, one action per step | review-card before submit | "Ready to submit" row shows barangay, never coordinates (LOCKED) |
| Settings | form panel | no banner — h1 + tab nav | no | no | panel cards | Plug: profile/security/notifications/preferences/privacy tabs |
| Error page (401/403/404/419/429/500/503 + generic 4xx/5xx) | D | no banner — standalone brand header | no | no | one 640px error card: 64px icon, code kicker, h1, message, ONE gold primary + one secondary | the card is the whole page; no list and no data chrome |
| Admin dashboard | A (dark variant) | no banner — workspace chrome | 4 KPI tiles from real metrics | no | dark tables + dept chips | treat separately: admin shell |
| Staff dashboard | A (dark variant) | no banner — workspace chrome | 4 KPI tiles | no | dark tables | treat separately |

**Hero/choices repeated from before failure:** no cream, no Cormorant, no ~50px
mystery gap under the navbar; every list page shares the banner gradient with the
navbar so chrome and content agree on the navy.

**Navbar rules (shared, not per page):** see `EN/FIL` labels per locale, single
locale per page, `.navbar-logo { flex-shrink: 0 }`, `#navbar` `z-index 900`.

---

## 11. Definition of Done (any UI change)

1. [ ] Verification evidence recorded in the changelog entry — what was rendered/clicked
       in a real browser and what passed (see `docs/ui-verification.md`). Screenshots
       at 393px and 1310px, in both `en` and `fil` with and without data are
       **optional** and attached when taken; otherwise the entry states the waiver
       inline (`NOT captured — waived by owner, <date>`).
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

- 2026-11-10 · Register · **inline errors while you type — except email,
  forever.** The create-account form only complained after submit, and one
  complaint rendered a raw key (`auth.email_unique`, missing in `en`+`fil` —
  added, alongside `transparency.register.table.department_pending` and
  `staff.pagination_label`, the same raw-key class found by scanning). Each
  field now validates live once touched (blur) and clears live while typing:
  first/last name regex, PH mobile regex, the barangay allow-list (read from
  the rendered suggestions), password strength, confirmation match, terms.
  The **email field has no live validator on purpose** — inline or
  background uniqueness checks turn the page into an account-enumeration
  oracle; email errors appear only after submit, from the server. Inline ink
  for `.field-error` is rgba(252,165,165,·) (the `.alert-error` red, kept as
  rgba so the allowlist count for `#fca5a5` doesn't move). The static
  "Type a barangay…" hint line is gone (now `.sr-only`, still the combobox's
  live region). New tokens: none.

- 2026-11-10 · Auth · **password fields now show what you typed — the eye
  toggle.** Sign in, create account, reset and set-new-password all rendered
  password inputs with no way to read them back; mistyped passwords were the
  cost. A shared eye toggle (`auth.partials.password-toggle`, §5.41) now sits
  at the right edge of every auth password field — and stays hidden until
  there is something to reveal. Once the field has text it springs in; it
  flips the input between `type="password"`/`type="text"`, cross-fades two
  stacked feather glyphs (`eye`/`eye-off`) via `aria-pressed`, and flips the
  accessible name with it (`auth_ui.common.show_password` /
  `hide_password`, en+fil). Emptying the field re-masks and re-hides it.
  44px target, `z-index: 2` above the input, `.field-underline` is now
  `pointer-events: none` so it can never eat a tap meant for the toggle.
  Behaviour lives in `app.js` (`initializePasswordToggles()`); the field
  keeps its gold left icon and reserves 52px on the right via
  `.field-wrap:has(.field-toggle) .field-input`. The settings page keeps its
  light "Show"/"Hide" text button (different surface). New tokens: none.

- 2026-11-10 · Staff ticket · **the review form is verifier-only.** The
  "Review and verification form" on the staff ticket page was rendered for
  every department account, verified or not — the server always refused a
  non-verifier's save, but the page advertised a control it would bounce.
  It now renders only for accounts with verification rights; non-verifiers
  get a read-only state line (`staff.verification.read_only_body`, en+fil).
  The official response form and the moderation panel remain open to every
  department staff member (worker flow and content decision per §5.40).
  Files: `resources/views/staff/complaints/show.blade.php`,
  `lang/{en,fil}/staff.php`, `tests/Feature/StaffActionPanelsTest.php`
  (two new tests: page-level absence, server-side refusal pinned).
  New tokens: none.

- 2026-11-10 · Staff queue · **the filters wait for Apply.** The status /
  review / priority selects no longer submit on `change`; the queue only
  re-queries when the Apply button (or Enter in search) is used. Files:
  `resources/views/staff/complaints/index.blade.php`. New tokens: none.

- 2026-11-10 · Staff queue · **the stat strip is gone.** The staff complaint
  queue (`/staff/complaints`) no longer renders the `.tr-stats` strip
  (pending verification / in progress / resolved this month / avg time open);
  the banner runs straight into the filter bar, matching Shell B's flat
  pattern. The controller no longer computes or passes `stats` for this page
  (the dashboard computes its own; the `staff.stats.*` keys remain in use
  there). Owner-requested removal — the strip duplicated the dashboard's stat
  cards and cost four aggregates per queue load. Files:
  `resources/views/staff/complaints/index.blade.php`,
  `app/Http/Controllers/StaffController.php`. New tokens: none.

- 2026-11-10 · Staff surfaces + admin complaint detail · **resident identity
  masked everywhere except admin, and the reveal is policy-backed.** The public
  register and the staff detail's resident card were already masked, but the
  staff timeline, the dashboard activity fallback and the live ticket state
  (`staff.complaints.state`, JSON/messages) could expose a resident actor's real
  name — and staff queries loaded the full resident record (email/contact) onto
  staff surfaces. A resident is now reported by the same staff-safe alias on
  every staff screen and in the live ticket payload: `User::staff_actor_name`
  resolves citizens to `staff_safe_name` and keeps staff/admin actors real so
  the office still knows who acted. Staff controllers eager-load only the
  columns staff need (`user:id,display_alias,barangay` and restricted actor
  fields). Admins continue to see real identity, and the admin complaint detail
  now enforces it through `ComplaintPolicy::viewRealIdentity`
  (`Gate::authorize`) as defense-in-depth. Files: `app/Models/User.php`,
  `app/Http/Controllers/{StaffController,AdminController}.php`,
  `resources/views/staff/complaints/show.blade.php`,
  `resources/views/staff/dashboard/partials/activity.blade.php`,
  `tests/Feature/StaffIdentityMaskingTest.php` (new). New tokens: none.

- 2026-11-10 · Staff queue, staff complaint detail, admin dashboard · **the
  clock on the work a department owes.** A report became the office's
  responsibility the moment it was verified, and nothing in the system noticed
  when that responsibility went unpaid. "Verified three weeks ago, still Under
  Review" was one of forty rows in a queue and nobody was looking at it. Now
  every verified complaint carries a **deadline**: work must start (three days
  by default, one for a critical report) and work must finish (fourteen days
  by default). The days are per priority and admin-editable. The clock starts
  the first time a report is verified — `reviewed_at` moves on every review
  decision and cannot say when the office took ownership, so a new
  `verified_at` records it once — and it never restarts on a later re-review,
  because the resident has already served that wait. The **staff queue row**
  and the **staff complaint banner** now carry a deadline chip (§5.38): the
  action owed and the time left, in the status family the meaning belongs to,
  so a late row cannot be mistaken for a comfortable one. The queue gained an
  **Overdue filter**. The **admin dashboard** counts what each office has let
  slip, on its department card. And a daily sweep
  (`complaints:flag-overdue-work`) tells the office and the administrators —
  once each, so a badge nobody reads is not the safety net, but a badge that
  cries wolf every morning is not a safety net either. Files:
  `app/Enums/ComplaintSlaState.php` (new), `app/Services/ComplaintSlaService.php`
  (new), `app/Console/Commands/FlagOverdueWork.php` (new),
  `app/Services/NotificationService.php`, `app/Models/Notification.php`,
  `app/Services/ComplaintVerificationService.php`, `app/Models/Complaint.php`,
  `app/Http/Controllers/{StaffController,AdminController}.php`,
  `resources/views/staff/complaints/index.blade.php`,
  `resources/views/staff/complaints/show.blade.php`,
  `resources/views/admin/dashboard/index.blade.php`,
  `resources/css/pages/staff-complaint.css`,
  `resources/css/pages/admin-dashboard.css`, `lang/{en,fil}/staff.php`,
  `lang/{en,fil}/statuses.php`, `bootstrap/app.php`,
  `database/migrations/2026_11_10_000004_add_verified_at_to_complaints_table.php`
  (new), `tests/Feature/ComplaintSlaTest.php` (new). New tokens: none.

- 2026-11-10 · Staff and admin complaint detail · **the proof carries its
  place.** A photograph of the work was being stored as a bare path with
  nothing about it but the file — so "the crew was at the reported site" was a
  claim the file could not be checked against, and the **staff page did not
  even show the photographs it had been given**. Every photograph now has a
  row of its own (`complaint_evidences`): the place it was taken, read from
  the camera's own EXIF record; when it was taken; who attached it; and which
  part of the work it proves (`work_start` · `completion`). The **staff
  timeline** now shows the proof it holds, each photograph captioned with its
  coordinates — §5.37 — and says **"No location recorded"** rather than
  quietly implying a photograph is proof of somewhere. The **admin timeline**
  carries the same coordinates on its evidence card. The resident timeline is
  unchanged: a resident is shown the photograph and told the work was done;
  the exact position of a municipal work site is not theirs to need. Files:
  `app/Models/ComplaintEvidence.php` (new),
  `database/migrations/2026_11_10_000003_create_complaint_evidences_table.php`
  (new, with a backfill so no existing photograph is invisible),
  `app/Services/ComplaintWorkService.php`, `app/Services/PhotoLocationService.php`,
  `app/Models/ComplaintLog.php`, `app/Http/Controllers/{StaffController,AdminController}.php`,
  `resources/views/staff/complaints/show.blade.php`,
  `resources/views/admin/complaints/show.blade.php`,
  `resources/css/pages/staff-complaint.css`,
  `resources/css/pages/admin-workspace.css`, `lang/{en,fil}/staff.php`,
  `tests/Feature/ComplaintEvidenceTest.php` (new),
  `tests/Unit/PhotoLocationServiceTest.php`, `tests/TestCase.php`. New tokens:
  none.

- 2026-11-10 · Staff **and** admin complaint detail · **the proof rule is now
  category-aware, and it applies to everyone who can act on a ticket.** The rule
  was "a photo, always" — every complaint, every office, and only the staff
  path. That was wrong twice over. A noise complaint is an enforcement action
  with no site work to photograph, and demanding a photograph of it is theatre;
  and an administrator could settle a repair with a sentence the department
  could not. What changed: a complaint now carries a **work mode** —
  `labor_required` (a repair: proven by a photograph of it), `report_only` (an
  enforcement or desk action: proven by a written record) or `referral` — taken
  from its category (`config/complaint_work_policy.php`), **frozen onto the
  complaint the first time anyone acts on it**, so a later policy edit cannot
  move the goalposts on a ticket already in flight. The staff and admin paths
  both call one service (`ComplaintWorkService`), so they cannot drift. The
  **staff page** states the kind of work in its banner and asks for a photograph
  only when the chosen status is one that needs it; the **admin page** gained
  the same proof capture, the same rule, and the kind of work in its detail
  list — and an administrator can no longer resolve labor work with no
  photograph. The office may override the mode on a single complaint (a
  streetlight that turns out to need a crew), and the override is recorded in
  the audit trail. Files: `app/Enums/WorkExecutionMode.php`,
  `config/complaint_work_policy.php`, `app/Services/ComplaintWorkService.php`,
  `app/Http/Controllers/{StaffController,AdminController}.php`,
  `resources/views/staff/complaints/show.blade.php`,
  `resources/views/admin/complaints/show.blade.php`,
  `lang/{en,fil}/statuses.php`, `lang/{en,fil}/staff.php`,
  `database/migrations/2026_11_10_000002_add_work_mode_to_complaints_table.php`,
  `tests/Feature/ComplaintWorkProofTest.php`. New tokens: none.

- 2026-11-10 · Staff complaint detail (`/staff/complaints/{id}`) · **proof capture,
  rebuilt**: the "Add official response" form could not attach a documentation photo
  at all — the Nov 9 page rewrite dropped the upload surface, while the server kept
  refusing any move to `In Progress` or `Resolved` without one. The office was stuck
  at *Under Review* with no way forward. What shipped is a new component, §5.36
  "Proof capture (staff)": the photo-proof picker with the office's rule stated
  *above* it, so a staff member learns what the office owes before choosing a
  status rather than from a validation error after pressing send. The note is
  driven by the status select and turns to the amber action-needed family the
  moment a status that needs a photo is chosen, naming that status. Two sources,
  one picker; the camera input is emptied only after the selection is written back
  into the gallery input, so a browser that refuses DataTransfer still submits the
  photos. **Without JavaScript the form still works** — the browser puts the files
  in the inputs and the server reads them; what is lost is the preview, the
  per-photo remove button and the live rule. Two fixes came with it: the status
  dropdown now offers only the transitions the server accepts (it offered every
  status except the current one, so its default option was a move the server would
  refuse), and the dropdown's default is now the status the proof note describes.
  Staff-only — no resident view, route or payload was touched. Files:
  `resources/views/components/proof-capture.blade.php` (new),
  `resources/css/pages/staff-complaint.css` (new),
  `resources/js/proof-capture.js` (new),
  `resources/views/staff/complaints/show.blade.php`,
  `resources/views/pages/design-system.blade.php` (live demo),
  `lang/{en,fil}/staff.php`, `resources/css/app.css` (one import),
  `resources/js/app.js` (one import), `tests/Feature/StaffDocumentationProofTest.php`.
  New tokens: none.

- 2026-11-10 · Public transparency feed (`/transparency/complaints`) · **soft
  navigation**: the status chips (with their per-status totals), the active
  filter chips and the results (count, list, pager) are three Blade partials;
  `transparency.complaints.browser` renders them for the URL's state and the
  page swaps them in, so a visitor filtering, chipping or paging never loses
  the page, the scroll position or the text they typed. Same rules as §5.34:
  the URL is the truth, the server renders everything, the page and the
  fragments share one controller method, and without JavaScript every link and
  form is still an ordinary navigation. **A broken script was fixed with it:**
  the page declared `const btn` and `const panel` twice, so the whole IIFE
  threw `Identifier 'btn' has already been declared` — the filter disclosure,
  the self-applying selects and the search box had never worked. Files:
  `TransparencyController::complaints` + `complaintsBrowser` and the shared
  `transparencyComplaints()` method, `routes/web.php`,
  `resources/views/pages/transparency/partials/complaints-{chips,active,results}.blade.php`,
  `resources/views/pages/transparency/complaints.blade.php`. New tokens: none.

- 2026-11-10 · Every page · **no third-party request**. The layouts loaded
  Cormorant Garamond, Inter and IBM Plex Mono from Google Fonts, so every page
  view sent the visitor's IP address and User-Agent to a third party — on a
  municipal portal, and while the privacy policy promises there is no
  tracking. Inter was **already self-hosted** (`public/fonts/inter`), the mono
  is the system stack, and the serif was retired by this guide in the first
  place. The Google Fonts link is gone from `layouts/app` and `layouts/guest`,
  and the seven pages that still asked for the retired faces (login, register,
  change-password, forgot-password, reset-password, the profile page, the
  complaint tracker) now use `--font-sans` / `--font-mono`. Measured: **zero
  third-party requests** on the home page, the transparency feed, the auth
  pages, the tracker and the legal pages. The privacy policy now also names the
  language cookie and states that fonts, scripts and styles are served by the
  portal itself. New tokens: none.

- 2026-11-10 · Admin pages · **the phone card, shared**: a new component, §5.35
  "Admin phone card" — `.admin-phone-card` / `.admin-phone-cell` /
  `.admin-phone-values` in `admin-workspace.css`. Every admin row can adopt it:
  the row's content gets the card class, each value cell gets the cell class and
  a `data-label` (the same word its desktop column header uses), and the values
  inside a cell are wrapped so they share one line. The label column is 84px,
  10px/700 uppercase `--muted`; the values wrap. On a desktop the wrapper is
  `display: contents`, so adopting it cannot change the desktop layout. Two
  page-level fixes came with it: `.admin-page` is now
  `grid-template-columns: minmax(0, 1fr)` (an implicit `auto` track was sized by
  a wide table, so the body clipped the right-hand third of every admin table on
  a phone), and the review-status pills (`pending`, `needs_information`,
  `duplicate`, `escalated`) got the variants they were missing — they had been
  rendering as bare bold text with no pill, on every screen. Applied to the
  complaints queue first. New tokens: none.

- 2026-11-10 · Admin complaints queue (`/admin/complaints`) · **soft navigation**:
  the filter chips, the queue and its pager are now three Blade partials;
  `admin.complaints.browser` renders them for the URL's state and the page
  swaps them in, so changing a filter, applying the search, removing a chip or
  turning a page never redraws the browser. The bulk bar's JavaScript now
  listens by delegation, so the new checkboxes are picked up without a re-bind
  and the bar resets when the rows are replaced. Same rules as §5.34: the URL is
  the truth, the server renders everything, and without JavaScript every link
  and form is still an ordinary navigation. Files:
  `AdminController::complaintsBrowser` + the shared `complaintsQueue()`,
  `complaintFilterLabels()` and `complaintQueueStats()` helpers, `routes/web.php`,
  `resources/views/admin/complaints/partials/{summary,list,pagination}.blade.php`,
  the client controller in `resources/views/admin/complaints/index.blade.php`.
  New tokens: none.

- 2026-11-10 · Admin audit trail (`/admin/audit-trail`) · **soft navigation**:
  the scope band, the rail, the roster and the trail are now four Blade
  partials; `admin.audit.browser` renders them for a URL's state and the page
  swaps them in, so changing a scope, turning a page, filtering or searching
  never redraws the browser. The URL stays the truth (`pushState`), the state
  helpers are shared with the page, and without JavaScript every link and form
  still works as an ordinary navigation. Why: an index that reloads on every
  click throws away the admin's place and costs seconds on a municipal
  connection. Files: `AdminController::auditBrowser` + the shared
  `auditScope()`/`auditLogs()`/`auditSelection()` helpers, `routes/web.php`,
  `resources/views/admin/audit/partials/{scope,contexts,roster,trail}.blade.php`,
  the client controller in `resources/views/admin/audit/index.blade.php`.
  New tokens: none. Also documented as §5.34 "Soft navigation" and captured in
  `docs/soft-navigation.md` + the `soft-navigation` skill, so the pattern can
  be applied to another page.

- 2026-11-10 · Admin audit trail (`/admin/audit-trail`) · **drill-down browser**
  replaces the Department/Actor dropdowns. Why: the old dependent selectors hid the
  roster, had no counts, and made inspecting one office/person a dropdown round-trip.
  What: new `.admin-audit-browser` card in three bands — scope band (what the trail
  is showing, with `Clear selection`), search band (`Find a person…`, server-side
  type-ahead over people), and two panes (scope rail: Everyone / Offices / Automated;
  roster: people grouped by office, Administration, Residents). Entity selection via
  `?department_id=` / `?actor_id=` / `?system=1`; search + Action stay as secondary
  filters and survive entity switches; the left rail carries a `Scope | Entries` head
  and its own `Residents` scope (`?residents=1`, the entries residents made) beside
  Everyone, the offices and the automated records; department counts include resident
  filings (hint); mobile collapses the panes behind "Browse departments and people"
  and keeps the scope band and search visible; empty states distinguish "no match" from
  "nothing recorded for this selection". **The roster is bounded**: 8 rows per group
  with "See all N" (25 per page, `?people_group=&people_page=`) so the page does not
  grow with the number of residents in the log — 3,000 residents measured 2.87 MB /
  347 ms before, 179 KB / 62 ms after. Files: `app/Services/AuditDirectory.php`
  (cached snapshot: counts + roster ids, sliced on demand), `AdminController::auditIndex`
  + `auditPeople` (JSON fragment for the type-ahead), `resources/views/admin/audit/`
  (index + partials: one row markup shared by the roster, the expanded group and the
  search results), `resources/css/pages/admin-workspace.css`, `lang/{en,fil}/audit.php`,
  `resources/views/pages/design-system.blade.php`. Reuses existing tokens; no new
  tokens; no gold side bars (selected state uses a navy inset bar).
  Verify: Playwright (headed Chromium, seeded data) at 1310×900 and 393×852, `en`
  and `fil`: Everyone / Engineering Office / Juan dela Cruz / Automated records /
  search + office / legacy combined office+actor / expanded group / people search —
  scope counts, selection highlight, preserved `search`, translated action labels and
  table headers, the "Showing 8 of N · See all N" affordance, empty state and mobile
  disclosure all render; temporary screenshots in `/tmp/opencode/audit-ui/`.
  `php scripts/design-system-guard.php` — no new violations in touched files.
  `php artisan test --filter=ActivityLogTest` — green (see the PR for the exact list).
  Screenshots NOT captured in the repo (waived by owner, 2026-11-09).

- 2026-11-09 · Staff department dashboard (`staff.dashboard`) — footer anchoring + mobile bottom
  nav / complaint-card layout fix (follow-up to the 2026-11-09 pass 3):
  - **Why:** reported `/staff/dashboard` UI breakage — footer not anchored to end of page (desktop
    rendered mid-screen, splitting the complaint list) and, on mobile, bottom nav floating into the
    content area with search/chips/cards overlapping the Recent Activity card.
  - **What:** page shell normal flow restored (body flex column `min-height: 100vh`, main
    `flex: 1 0 auto` → footer after main, anchored to end); bottom-nav content clearance now
    relaxes at the nav breakpoint (1024px) instead of 768px; complaint-card ≤1100px grid tracks
    made shrinkable (`minmax(0, …)`).
  - **Verify:** design-system guard — no NEW violations from changed files; StaffWorkspace +
    PageOverflowTest green; screenshots NOT captured (waived by owner, 2026-11-09).
- 2026-11-09 · Department Account Dashboard (`staff.dashboard`, resident parity) · light-theme
  redesign reusing transparency/resident tokens and components: navy hero + diagonal texture +
  gold CTA, gold accent strip under `#navbar` for `role-staff`, compact `Department Account` +
  dept code in header, hero identity card, 2×2/4-across white stat cards (tappable → queue
  filters), priority complaint cards (left priority strip, mono ticket, status pill, priority
  chip, suggested/confirmed line, formatted days-open, 44px Review), GET priority
  search/filter chips, collapsible/sticky recent-activity timeline with merged consecutive
  entries and "No notes added" fallback, desktop 8/4 grid (no overlap) + white-card sidebar
  (gold-left active), fixed mobile bottom nav (Dashboard/Queue/Verify/Activity). i18n
  `staff.dashboard.*`/`staff.bottom_nav.*`/`nav.department_account` (en+fil). No routes/API/data
  logic changed.

- 2026-11-09 · All sectors · busy/loading action state (§5.32, "all sector" +
  future-use docs): shared `.is-loading`/`aria-busy` pattern with `currentColor`
  spinner (`resources/css/app.css`, `daet-action-spin`, reduced-motion static
  dotted ring, unified legacy `.admin-button.is-loading`) and global JS
  (`resources/js/app.js` — `window.daetActionLoading.setBusy`, submit handler
  for non-GET/`data-loading-submit`/legacy `data-disable-on-submit`, double-submit
  guard, bfcache restore). `data-loading-submit` applied across action forms in
  auth/complaints/settings/profile/staff/admin/invitations; fetch autosave
  (`data-preferences-autosave`) and `data-loading="off"`/`-managed` opted out.
  Complaint create uses shared busy state (no hardcoded busy text). No new
  colours/hex; `/design-system` swatch + §5.32 contract document Livewire/fetch
  usage for future work.

- 2026-11-09 · Global navbar + mobile drawer headers (staff/admin identity) · added
  §5.31 `.nav-identity` indicator so staff/department accounts and administrators
  are identifiable from the fixed top navbar on desktop and mobile without opening
  menus. Staff show the canonical `.dept-badge[data-dept]` (existing §3.2 tokens)
  + department name (or a neutral staff badge + "Department pending"); admin show
  an `Administrator` role badge using `--gold-300`/`--gold-500` tints on navy.
  Placements: desktop `nav-actions`, compact `nav-mobile-right`, and a direct
  `.nav-identity-bar` child of `.navbar-flex` for the `body.is-auth` ≤640px
  shared-drawer mode; mirrored in the shared mobile drawer header, legacy mobile
  menu header, and account dropdown headers. Added global `.dept-badge` baseline
  in `app.css` (token-driven, `[data-dept]` mapping). No new colours; no
  `id`/`data-*`/JS contracts changed; z-index contract unchanged. Citizens unchanged.
- 2026-11-13 · Resident ticket detail + closure lifecycle (B/UI) · resolved-owner close
  confirmation panel (§5.30, `complaints.close`, owner-only on `Resolved`, `Resolved→Closed`,
  `closed_at/closed_by=resident`) + 7-day auto-close
  (`complaints:auto-close-resolved`, daily 07:30, stamps `closed_by=system`, notifies resident,
  legacy `resolved_at` fallback) + `resolved_at/closed_at/closed_by` migration and staff/admin
  timestamping. Lang `complaint_show.close.*` (en+fil). No new tokens.
- 2026-11-09 · My Complaints `/complaints` · stat strip removed outright (all breakpoints):
  the desktop `.tr-stats` 4-card strip and the ≤640px 3-cell `.mc-mobile-summary` card
  are gone — hero now runs straight into the filter bar. `$avgDays` computed nowhere;
  dead `my_complaints.stat_*`/`avg_*` lang keys purged. Shell B and the §10 recipe
  updated; nothing else references the strip.
- 2026-11-09 · Resident ticket detail · removed the mobile "Latest update" inset
  entirely: the section, the scoped `.pcs-latest* / .pcs-no-updates` rules and the
  `.pcs-latest` flex-order, the `$pcsLatestLog` computation, and the now-dead
  `complaint_show.latest.*` keys (en+fil). The timeline (#pcs-updates) is the
  single place the newest staff response appears. `pcs-inset` stays (help card).
- 2026-11-09 · Resident ticket detail + admin all-complaints + status model (B) ·
  needs-information is now one coherent story everywhere: resident ticket renders a
  dedicated 5.29 panel (kicker, question, reply form or answered state; renders on
  `open|answered` requests **and** on legacy `needs_information` orphans) with the
  lede/kicker/fallback-question keys (en+fil); `recordResidentReply` auto-creates the
  request for legacy orphans (FK `requested_by` is now nullable, 2026-11-09
  migration); resident attention strip keys off `open` information requests, so the
  banner always lands on a reply form; admin complaint page gains the same "Ask the
  resident for more evidence" panel as staff (new `admin…request-information` route,
  same controller/service); bare review dropdowns on staff+admin no longer offer
  `needs_information` and the service rejects it via `reviewUpdates()` — needs
  information always carries a question. No new colours.
- 2026-11-09 · My Complaints `/complaints` · filter selects are drafts, never
  auto-apply (§5.28): removed `onchange="this.form.submit()"` from q → department →
  category → sort; "Apply" commits once on all breakpoints (visible ≥641px too,
  inline after sort ≥1025px, right-aligned second row ≤1025px); closing the
  popover (toggle / outside / Esc) reverts unsaved drafts to the applied URL.
- 2026-11-09 · My Complaints `/complaints` (resident, ≤640px) · filter panel is now an
  anchored popover below the Filters button (§5.28): `position:absolute` under
  `.tr-filters__row1`, `--z-popover` (500, new z-contract row), `--shadow-lift`, zero
  reflow of the list/chips; outside-tap and `Escape` close; toggle/aria sync.
  Desktop unchanged (inline panel). New `--z-popover` in `tokens.css`.
- 2026-11-09 · Error pages (401/403/404/419/429/500/503 + generic 4xx/5xx) ·
  new standalone Shell D (§4) and §5.27: brand header, one centered
  `.error-card` (64px icon, code kicker, single h1, one-line message, ONE gold
  primary + one secondary), minimal footer. Deliberately no app navbar, drawer,
  Livewire or database-backed site footer — the page must render when the DB or
  assets are the failure. Copy in `lang/{en,fil}/errors.php`; styles in
  `resources/css/pages/error.css` (tokens only). Back action is revealed only
  when browser history exists; 429/500/503 offer Reload. Added to `/design-system`.
- 2026-11-08 · Submission success (`/complaints/{id}/success`) · light receipt
  redesign (§5.26): green check hero, mono ticket strip, fact rows with shared
  status pill + dept badge, live 5-segment progress bar (§5.15 base widened),
  what-next card reusing the wizard's three steps, ONE gold CTA + shared
  `.tr-button`; auto-redirect clock strip cancels and pauses on hidden tabs.
  Removed the dark navy serif page (5 hexes + serif gone; allowlist rows
  dropped); CSS moved to `resources/css/pages/complaint-success.css`; new
  `complaint_success` lang group (en+fil).
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
- 2026-11-08 · My Complaints `/complaints` (citizen home, mobile pass 3, ≤640px) ·
  flat, token-only mobile redesign scoped to `body.page-complaints-index`
  (desktop ≥641px unchanged): 16px edge, no nested/wrapper cards; compact hero
  (24/700 title + 14px greeting, no "Updated", full-width 48px flat gold
  `--radius-sm` CTA with `+`); attention strip moved under hero on mobile
  (`min-height:48px`, wrapping, `role="status"`); one 3-column summary card
  (Total|Open|Resolved, 1px hairlines, 24/700+13px, 72px) overlapping hero 24px;
  "Your complaints" 20/600 + single muted count (bottom "Showing" SR-only on
  mobile); flat toolbar (search 48 + Filters button with icon/badge, in-place panel
  2-col/360px 1-col with gold Apply + Clear, GET/field names/submit unchanged) and
  16px-aligned scrolling status chips with fade (36px/44px tap, active gold inset
  accent); page-scoped report-card order (ticket mono 13 + status → 16/600 title →
  category·department 13 muted → step/progress+short date, needs-response full
  line, 8px radius, no stray dot). No new tokens/hex. Lang `list_heading_mobile`
  + `count`. Footer kept shared (requested). Screenshots
  `public/debug-ui/pass3-*-complaints-*.png`.
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
- 2026-11-08 · Transparency + My Complaints + resident dashboard · banner/welcome
  now butt against the navbar (global `body` offset cancelled for hero pages
  via `body:has(.transparency-page), body:has(.citizen-dashboard)`), navbar
  offset folded into the navy (`calc(var(--navbar-height) + 16px) 0 48px`),
  visible gap 16px navy instead of a ~50px light "mystery gap" (§10) + 48px.
  Text stack left-aligned as one block (h1 left, gold bar `8px 0 12px` left,
  privacy left, CTA `12px` left, updated left); dashboard welcome
  `align-items: flex-start` (CTA top matches heading top), title −0.02em left.
  Staff/admin keep 48px via `.tr-banner { padding-top: 48px }` (shell offsets).
  No new tokens; 8/12/16 are on the spacing scale.
- 2026-11-08 · Resident dashboard content · `.dashboard-content` now separates
  hero/columns/footer with `32px 0 48px` (`24px 0 32px` ≤768px); containers
  butt 24px (columns/rail gaps, heading/banners/track `margin-bottom: 24px`,
  view-all/pagination `margin-top: 24px`), report rows keep 12px. Corrects
  off-scale 16/20 separators; bottom band keeps last card off the footer gold
  accent. New §5.24 spec.
- 2026-11-08 · Citizen mobile dashboard + My Complaints (≤640px) · hero
  `calc(var(--navbar-height) + 16px)` (56px mobile), dashboard 24/600 +
  14px line + 48px full-width CTA; 3-tile summaries (link to
  `/complaints?status=` where filter exists) + muted avg line (hidden when
  null/negative); dashboard recent 3 via `nth-child`, search/chips hidden on
  mobile (complaints equivalents exist); shared 4-row cards (ticket 12 muted
  | status 12 nowrap, title 16/600 clamp 2, `Category · Department` 13 1-line,
  5×4px + step 12 left + `M j` right, no chevron/min-height, 16/12/12,
  `:active` + ring); filters search 48 visible, dept/cat/sort behind Filters
  + badge (div panel, always open ≥641px); chips scroll-snap + fade, hidden
  scrollbar, active scrolled in, 36/44 via `::before`; bell/hamburger 40×40
  radius 10 border same, icons 20, 44 touch via `::before`. Averages exclude
  invalid `end<created` rows (seed backdates) + clamp ≥0. New §5.25 spec;
  desktop unchanged (mobile markup `display:none` ≥641px).
- 2026-11-08 · All banners (Transparency, My Complaints, staff/admin queues,
  public complaint list + ticket detail, file-complaint) · removed the 40×3px
  `--gold-500` horizontal after-title bar (`.tr-goldbar` / `.pt-goldbar` /
  `.fc-goldbar` spans + CSS) from every banner hero. Privacy/lede now sit
  `12px` below the h1 (`margin: 12px 0 0`) so the stack stays one
  left-aligned unit on the spacing scale; ticket title's existing `12px`
  bottom margin already covered the status row. Design-system §4/§5.1/Shell A
  updated; no new tokens.
- 2026-11-08 · Citizen dashboard merged into My Complaints · citizen `/dashboard`
  302 → `complaints.index` (query preserved, `dashboard` route name kept;
  staff/admin unchanged), duplicate Dashboard nav item removed from desktop +
  drawer, My Complaints active on index/show/create, logo → role home through
  `User::homeRouteName()`, greeting as hero title (24px ≤640px), and a 48px
  amber `needs_information` strip (only when count > 0, `role="status"`,
  opens the newest matching report). Reuses `--status-progress-bg/text` and
  `--radius-sm`; no new tokens. Screenshots
  `docs/design-log/2026-11-08-citizen-merge-*.png`.
- 2026-11-08 · File complaint wizard (`/complaints/create`, ≤767px wizard-on, light) · mobile flat app-like: compact back+progress row, one-line chip, no banner, hidden cards leave no shell (`[hidden]` + backstop), footer hidden on this page only, bottom=`var(--fc-bar-h)+safe+16`, draft `hidden`-only ≥2 fields; group 2-col 104px, category 64px rows + group Change, describe 56px/autogrow + counters + priority toggle, photos primary 56px + 3-col thumbs + tips toggle, where landmark collapse, pin locate-above-map `clamp(240px,45dvh,360px)`, visibility gold cards + Why toggle, review 7 rows + Edit (`goToStep`) + What-next + 48px terms; 180ms motion, 44px targets, 16px padding; desktop 1310 diff zero. Screenshots `public/debug-wizard/` 390×844 + 360×800 (8 steps/invalid/review-5) + 1310 before/after; visibility lock + breakpoint unchanged (await decision). No new tokens.
- 2026-11-08 · Guest landing page (`/`) · spacing tightened, navbar-to-hero gap
  fixed: `.lp-root` joins the `body:has(...)` navbar-offset cancel list in
  `app.css` (the double 64px body + hero offset was the ~79px measured gap, the
  §10 repeated failure) and `.lp-hero` now follows §4
  (`calc(var(--navbar-height) + 16px) 0 24px`, inner top padding 56/48 → 0,
  desktop `min-height: 440px` dropped, phone 430 → 400px) — visible gap 15px
  measured at 1310 and 393, en + fil. Sections 48/64 → 32/48, buttons 52/56 →
  48px, tiles/rows/steps/FAQ/final each one step down the spacing scale
  (16/24/32/48 only); targets stay ≥44px. No new tokens. Screenshots
  `docs/design-log/2026-11-08-landing-spacing-{en,fil}-{desktop-1310x900,mobile-393x852}.png`.

## 2026-11-09 — Department Account Dashboard pass 3 (staff workspace)
- **Why:** pass 2 left dark/heavy staff surfaces and conflicting CSS (invisible sidebar
  text/double box, unreadable header pill, cramped stats, duplicate mobile priority chip,
  noisy activity, mobile header wrap). Presentation-only correction to match the spec.
- **What:** brighter department identity via `--dept-*` tokens (page `#F4F7FC`, hero
  `#1E3A8A→#2563EB→#3B82F6`, review blue `#1D4ED8`, priority/status/stat tints, card
  border/shadow) in `tokens.css`; single white sidebar card (248px sticky, Assigned Dept
  gradient, Workspace/Verification with count, bottom EN/FIL + Settings, active
  `#EAF1FF`/blue/3px gold bar); 3-zone dashboard layout at ≥1280px (sidebar/main/320px
  sticky activity), 1024–1279px activity below main; hero contained (no dark overlap
  block) with CTA right on desktop, vertical stat cards; navbar pill white-on-navy with
  gold border (desktop `Department · GSO`, mobile icon+`GSO`), no wordmark truncation at
  360px, 3px staff gold strip; mobile chips rendered once auto-width, relative filed time
  with "ago"/exact tooltip, activity strips "Administrative review:"/dedupes `Note:`,
  bottom-nav safe-area padding. No routes/queries/permissions/data logic changed.
- **Verify:** `php artisan test --filter=StaffWorkspace` (6 passed). Headless Playwright
  screenshots in `/tmp/opencode/staff-pass3/` (1440/1100/1024 full, 393 top/bottom/drawer,
  360 header/full). Guard: no new violations in files touched by this pass.
