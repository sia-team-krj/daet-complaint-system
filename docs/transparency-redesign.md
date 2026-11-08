# Transparency Page Redesign — Design Change Log

**Date:** 2026-11-08  
**Scope:** Public Transparency page (`/transparency`) redesign — desktop ~1310px and mobile ~393px  
**Status:** Implemented (design layer only; backend data shape unchanged)

---

## 1. Goal

Redesign the public transparency register page to be consistent with the rest of the Daet Listens portal:
- Single `Inter` font family (no serif headings)
- Light page background `#f6f8fb` (no cream, no cream bands)
- White cards with `1px #cdd5e1` borders, `12px` radius
- No vertical gold lines, no gold text on white
- Compact page header (no tall navy hero)
- Shared Blade component library (`x-button`, `x-card`, `x-pill`, `x-chip`, `x-input`, `x-report-card`)
- Barangay-level map aggregation with minimum-3-reports threshold
- Filter bar matching My Complaints pattern
- Desktop sticky-header table + mobile ReportCard-style list
- Full i18n (en/fil) with layout-safe longer Filipino text

---

## 2. Files Changed

### 2.1 New Files (shared component library)

| File | Purpose |
|------|---------|
| `resources/views/components/button.blade.php` | Primary/secondary/ghost/danger button with size variants |
| `resources/views/components/card.blade.php` | White card with border, radius, padding variants |
| `resources/views/components/pill.blade.php` | Status pill with color variants (submitted/verified/assigned/in_progress/resolved/rejected/closed/default) |
| `resources/views/components/chip.blade.php` | Filter chip (selected/unselected states) |
| `resources/views/components/input.blade.php` | Labelled input with placeholder, required mark, error/helper text |
| `resources/views/components/report-card.blade.php` | Mobile card for transparency register rows |

### 2.2 Modified Files (transparency page)

| File | Change |
|------|--------|
| `resources/views/pages/transparency/index.blade.php` | Rewritten as thin Livewire shell wrapper — all visible markup moved into the component |
| `resources/views/livewire/transparency-register.blade.php` | Complete rewrite: compact header (no buttons, no hero), map section (360px desktop / 280px mobile), filter bar (chips + dropdowns + sort), desktop sticky table (6 cols, location as second line under title), mobile ReportCard list, empty/no-results states, map data attribute |
| `resources/views/pages/transparency/partials/register-script.blade.php` | Rewritten: status chips replace status select, clear-filters button (conditional), sort select, debounced client-side filtering, URL-ready filter state, map no longer renders floating "shown" badge |
| `app/Services/TransparencyFeed.php` | `mapPoints()` now returns `intensity: 0` and `low_volume: true` for barangays with fewer than 3 reports (prevents identifying single complaints) |

### 2.3 Modified Files (translations)

| File | Change |
|------|--------|
| `lang/en/transparency.php` | New keys: header.title, header.privacy, header.privacy_link, map.kicker, map.heading, map.low_volume, map.barangay_chip, map.screen_reader_summary, register.filter_label, register.sort_label, register.sort_\*, register.clear_filters, register.apply_filters, register.results_paginated, register.barangay, register.no_results, register.empty, status.unknown |
| `lang/fil/transparency.php` | Same key set translated into Filipino (simpler, Daet-localized; technical terms like "Loading…" kept in English) |

### 2.4 Modified Files (navigation — "File a complaint" placement)

| File | Change |
|------|--------|
| `resources/views/layouts/partials/mobile-drawer.blade.php` | Moved "File a complaint" to TOP of citizen mobile drawer menu (primary gold button), followed by Dashboard, Transparency, My Complaints |
| `resources/views/layouts/navbar.blade.php` | Added "File a complaint" as primary link at top of citizen mobile menu (authenticated shell) |

### 2.5 Modified Files (existing localization — already committed)

| File | Change |
|------|--------|
| `resources/views/auth/register.blade.php` | Required asterisk `*` on first_name, last_name, email, password, password_confirmation, terms |
| `lang/en/auth.php`, `lang/fil/auth.php` | Auth flash/error messages |
| `lang/en/auth_ui.php`, `lang/fil/auth_ui.php` | Auth UI labels, placeholders, validation messages |
| `lang/en/nav.php`, `lang/fil/nav.php` | Nav/drawer labels |
| `resources/views/admin/partials/sidebar.blade.php` | Admin sidebar translated via `nav.*` keys |
| `resources/views/staff/partials/sidebar.blade.php` | Staff sidebar translated via `nav.*` keys |
| `resources/views/layouts/partials/site-footer.blade.php` | Language switcher removed from footer |

---

## 3. Design Decisions

### 3.1 Theme Tokens

| Token | Value | Usage |
|-------|-------|-------|
| `--bg` | `#f6f8fb` | Page background (replaces cream `#f5f0e8`) |
| `--card` | `#ffffff` | Card background (white) |
| `--border` | `#cdd5e1` | Card/table border (1px) |
| `--ink` | `#172b49` | Body text |
| `--muted` | `#64748b` | Muted/secondary text |
| `--navy` | `#0b1f3a` | Primary text, ticket IDs, status text |
| `--gold` | `#8a6a12` | Accent, kicker text, focus rings (NOT gold text on white) |
| `--radius` | `12px` | Card/border radius |

### 3.2 Font Stack

- **All text:** `Inter`, `ui-sans-serif`, `system-ui`, `sans-serif`
- **Monospace:** `ui-monospace`, `SFMono-Regular`, `Menlo`, `monospace` (ticket IDs)
- **Removed:** `Cormorant Garamond` (serif), `DM Sans` (transparency-specific override)

### 3.3 Status Pill Colors

| Status | Variant | Background | Text |
|--------|---------|------------|------|
| Submitted | `submitted` | `#dbeafe` | `#1d4ed8` (blue) |
| Verified/Assigned | `verified`/`assigned` | `#dcfce7` / `#eff6ff` | `#15803d` / `#1e3a8a` (navy) |
| In Progress | `in_progress` | `#fef3c7` | `#92400e` (amber — NOT purple) |
| Resolved | `resolved` | `#dcfce7` | `#15803d` (green) |
| Rejected | `rejected` | `#fee2e2` | `#991b1b` (red) |
| Closed | `closed` | `#f1f5f9` | `#475569` (slate) |

### 3.4 Layout Rules

| Element | Desktop | Mobile |
|---------|---------|--------|
| Container | max-width 1040px, 24px padding | max-width 100%, 16px padding |
| Header height | ~120px | ~140px |
| Map height | 360px | 280px |
| Section gap | 32px | 32px |
| Heading-to-content gap | 12–16px | 12–16px |
| Bottom padding above footer | 32px | 32px |
| Tablet breakpoint | 768px | — |
| Map zoom controls | 44px | 44px |

### 3.5 Filter Bar (matches My Complaints)

- Desktop: single row — search input + status chip group + department dropdown + category dropdown + sort dropdown + clear-filters text button (only when active)
- Mobile: search input full-width + "Filters" button (44px, count badge when active) → bottom sheet with Department, Category, Status, Sort + Apply/Clear
- Active filters show as removable chips under search row
- Filtering applies instantly (debounced 300ms for search)
- URL query params: `?q=&status=&department=&category=&barangay=&sort=`

### 3.6 Map Rules

- Barangay-level heat areas only — NO individual pins
- Minimum aggregation: areas with fewer than 3 reports get `intensity: 0` (neutral "Low volume" shade), not highlighted
- Map and table barangays agree (same `TransparencyFeed::mapPoints()` data)
- Clicking an area filters the register to that barangay, shows removable chip "Barangay VI ×"
- Map attribution (OpenStreetMap) stays visible
- Scroll-zoom disabled on mobile; two-finger pan or hint text

### 3.7 Register List

**Desktop table (>=768px):**
- 6 columns: Ticket, Complaint (title only), Category, Department, Status, Reported
- Location moved to muted second line under title ("Barangay VI")
- Sticky header, row hover, whole row is link, chevron at end
- Sortable by Reported date and Status
- Ticket ID: monospace in navy (not gold)

**Mobile cards (<768px):**
- Row 1: ticket ID (monospace) left, status pill right
- Row 2: title, Inter 17/600, max 2 lines
- Row 3: muted meta flex-wrap: Category, Department, Barangay, "Nov 8, 2026"
- Chevron on right, whole card is link
- Cards separated by 12px, padding 16px
- No footer row inside cards (no "Public visibility only", no "View details")

### 3.8 States

| State | Desktop | Mobile |
|-------|---------|--------|
| Empty register | Icon + "No public complaints yet" + one sentence | Same |
| No results (filters) | "No complaints match your filters" + "Clear filters" button | Same |
| Loading | Skeletons for map and rows | Same |
| Error | Retry button | Same |

### 3.9 Accessibility

- One h1 per page, h2 per section
- Table with proper `th scope="col"` and caption (desktop) or `ul/li` (mobile)
- Map has `aria-label` and text alternative
- Visible focus rings (2px gold)
- 44px touch targets
- WCAG AA contrast
- Screen reader summary: "Most reports are in :barangay"
- No names, contact details, or exact coordinates in page, HTML, or API response

---

## 4. What Was Removed (Redundancy)

| Element | Where it was | Action |
|---------|--------------|--------|
| "My complaints" button | Transparency hero | Deleted |
| "File a complaint" button | Transparency hero | Deleted (moved to nav/header) |
| "Log in" / "Create account" buttons | Transparency hero (guests) | Deleted |
| Hero paragraph ("See where public concerns...") | Transparency header | Replaced with one privacy sentence |
| Map subtitle ("The heatmap uses approximate areas...") | Map section | Replaced with popover link |
| Map footer text ("Coordinates are clustered...") | Map card foot | Deleted |
| "Public visibility only" | Table header + every mobile card footer | Deleted (replaced by single privacy note) |
| Table note under register | Below table | Deleted |
| Eyebrow labels ("PUBLIC TRANSPARENCY", "LOCATION SIGNAL", "COMPLAINT REGISTER") | All sections | Deleted |
| Section intro paragraphs | Map + register sections | Deleted |
| "Public report heatmap" title | Inside map card | Deleted (h2 above card only) |
| "Community member" under each title | Table rows + mobile cards | Deleted |
| "Verification not recorded" tag | Table rows | Deleted (only show "Verified" badge when verified) |
| "View details" link | Table rows + mobile cards | Deleted (whole row/card is the link) |
| ", Daet" from locations | Table + cards | Deleted |
| "Showing 3 of 3" line | Table meta | Replaced with single header count |
| Floating "3 public reports shown" badge | Map overlay | Deleted |
| Reset button (permanent) | Filter toolbar | Replaced with conditional "Clear filters" text button |

---

## 5. What Remains / Next Steps

| Item | Status |
|------|--------|
| Bottom sheet for mobile filters | Not implemented — filter bar currently renders inline on mobile |
| Popover for "How this data is shown" | Not implemented — link exists but no popover logic |
| URL query param sync (`?q=&status=...`) | Not implemented — filters are client-side only |
| Server-side pagination (10 per page) | Not implemented — unbounded query (pre-existing behavior) |
| Loading skeletons | Not implemented — empty states only |
| Error state with retry | Not implemented |
| Before/after screenshots | Not captured |
| Test with 0, 3, 12, 40 reports | Not run |
| Test in English and Filipino as guest/resident/staff/admin | Not run |

---

## 6. Component Library Status

The shared component library was created because none existed (`resources/views/components/` was empty):

| Component | Path | Props |
|-----------|------|-------|
| `x-button` | `components/button.blade.php` | type, variant, size, class, disabled |
| `x-card` | `components/card.blade.php` | class, padding |
| `x-pill` | `components/pill.blade.php` | variant, class |
| `x-chip` | `components/chip.blade.php` | value, label, selected, class |
| `x-input` | `components/input.blade.php` | type, name, id, label, placeholder, value, required, error, helper |
| `x-report-card` | `components/report-card.blade.php` | report, locale |

These components use the design tokens defined above and are ready for use across the portal.

---

## 7. Consistency Notes

- All new strings use i18n keys (`__('transparency.*')`) with both `en` and `fil` translations
- Layout accommodates longer Filipino text (flex-wrap, no fixed widths on text)
- Department names remain untranslated (proper names)
- "Daet" and "LGU Daet" remain untranslated in all locales
- Gold is used only as accent/kicker/focus — never as text on white background
- No serif fonts anywhere on the transparency page
- No cream background or bands anywhere on the transparency page
- Header, content, and footer edges align at 360px, 393px, 768px, 1024px, 1310px (single max-width 1040px container)

---

## 8. Related Documentation

- `docs/localization.md` — Full localization implementation log (languages, keys, architecture, tests)
- `resources/views/components/*.blade.php` — Shared component source (self-documenting with @props blocks)
