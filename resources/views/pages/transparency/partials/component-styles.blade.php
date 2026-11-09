{{--
    Shared component styles for the Transparency family (banner, stats, map
    card, top areas, filter bar, report rows, states). Included by the
    Livewire register component and by the /design-system reference page,
    so the reference renders the real thing, not a copy.
--}}
<style>
        /* ════════════════════════════════════════════════════════
           TRANSPARENCY (v2) — "official but human"
           All colours/shadows/radii come from resources/css/tokens.css.
           ════════════════════════════════════════════════════════ */

        *, *::before, *::after { box-sizing: border-box; }

        .transparency-page {
            background: var(--bg);
            color: var(--text);
            font-family: var(--font-sans);
            font-size: 15px;
            line-height: 1.5;
            /* The navbar is fixed; the banner absorbs the offset so its navy
               butts against the navbar's navy (no light gap strip). Staff
               and admin shells already offset the navbar — they override
               .tr-banner to keep their workspace spacing. */
            padding-top: 0;
        }

        /* Cancel the global body navbar offset on hero/banner pages so the
           navy runs uninterrupted (also in app.css; duplicated here so it
           holds even when the compiled app.css is cached). */
        body:has(.transparency-page) { padding-top: 0; }

        /* .tr-container extracted to partials.public-ui */

        .transparency-page h1,
        .transparency-page h2 { font-family: var(--font-sans); }

        /* ── Banner ─────────────────────────────────────────── */
        .tr-banner {
            position: relative;
            background:
                repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.035) 0 1px, transparent 1px 14px),
                linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 62%, var(--navy-600) 100%);
            /* Full-bleed behind the fixed navbar: navbar height + 16px breathing
               room, so the visible gap below the navbar is 16px navy (was a
               64px light strip + 48px navy). 16 and 48 are on the scale. */
            padding: calc(var(--navbar-height, 64px) + 16px) 0 48px;
            overflow: visible;
        }

        .tr-h1 {
            margin: 0;
            color: var(--surface);
            font-size: 34px;
            font-weight: 700;
            letter-spacing: var(--tracking-display);
            line-height: 1.15;
            text-align: left;
        }

        @media (max-width: 768px) {
            .tr-h1 { font-size: 28px; }
        }

        .tr-privacy {
            margin: 12px 0 0;
            max-width: 640px;
            color: rgba(255, 255, 255, 0.82);
            font-size: 15px;
            line-height: 1.55;
            text-align: left;
        }

        /* "How this data is shown" disclosure */
        .tr-how { position: relative; display: inline-block; }

        .tr-how > summary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 44px;
            padding: 8px 0;
            color: var(--gold-300);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            list-style: none;
        }

        .tr-how > summary::-webkit-details-marker { display: none; }
        .tr-how > summary::marker { content: ''; }

        .tr-how > summary:hover { text-decoration: underline; text-underline-offset: 3px; }

        .tr-how > summary:focus-visible {
            outline: none;
            box-shadow: var(--ring);
            border-radius: var(--radius-sm);
        }

        .tr-how__chevron {
            width: 14px;
            height: 14px;
            transition: transform 160ms ease;
        }

        .tr-how[open] .tr-how__chevron { transform: rotate(180deg); }

        .tr-how__card {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            z-index: 30;
            width: 320px;
            max-width: calc(100vw - 48px);
            padding: 16px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lift);
        }

        .tr-how__card ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .tr-how__card li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .tr-how__card li svg {
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            margin-top: 1px;
            color: var(--navy-700);
        }

        .tr-updated {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 12px 0 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-variant-numeric: tabular-nums;
            text-align: left;
        }

        .tr-updated svg { width: 14px; height: 14px; }

        /* .tr-stats extracted to partials.public-ui */
        /* ── Map + Top areas ────────────────────────────────── */
        .tr-map-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 24px;
            margin-top: 24px;
        }

        @media (max-width: 1023px) {
            .tr-map-grid { grid-template-columns: 1fr; }
        }

        .tr-card {
            background: var(--surface);
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
        }

        .tr-map-card { overflow: hidden; }

        .tr-map-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid var(--line-soft);
        }

        .tr-h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.01em;
            line-height: 1.25;
            color: var(--text);
        }

        @media (max-width: 640px) {
            .tr-map-card__head { gap: 8px; }
            .tr-map-card__head .tr-h2 { font-size: 18px; }
            .tr-legend__bar { width: 72px; }
        }

        .tr-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .tr-legend__label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .tr-legend__bar {
            width: 96px;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--heat-1), var(--heat-2), var(--heat-3), var(--heat-4));
        }

        /* The map traps Leaflet's internal z-indexes (tile pane 200 …
           controls 1000) inside its own stacking context. */
        .tr-map {
            position: relative;
            isolation: isolate;
            z-index: 0;
            height: 360px;
        }

        @media (max-width: 768px) {
            .tr-map { height: 280px; }
        }

        .tr-map__canvas {
            width: 100%;
            height: 100%;
        }

        /* Behind the Leaflet panes (up to 1000 inside our isolated context)
           these would be invisible, so they outrank the tiles and controls.
           They stay inside .tr-map, so nothing escapes onto the page. */
        .tr-map-status {
            position: absolute;
            inset: 0;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 24px;
            background: var(--map-loading-bg);
            text-align: center;
        }

        .tr-map-status[hidden] { display: none; }

        .tr-map-status__skeleton {
            width: 180px;
            height: 18px;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--line-soft) 25%, var(--line) 50%, var(--line-soft) 75%);
            background-size: 200% 100%;
            animation: tr-shimmer 1.5s infinite;
        }

        @keyframes tr-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .tr-map-status__message {
            margin: 0;
            max-width: 320px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .tr-map__hint {
            position: absolute;
            left: 50%;
            bottom: 44px;
            transform: translateX(-50%);
            z-index: 1100;
            display: none;
            margin: 0;
            padding: 6px 12px;
            border-radius: var(--radius-pill);
            background: rgba(7, 18, 38, 0.78);
            color: rgba(255, 255, 255, 0.92);
            font-size: 12px;
            white-space: nowrap;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .tr-map__hint { display: block; }
        }

        /* ── Top areas ──────────────────────────────────────── */
        .tr-areas {
            display: flex;
            flex-direction: column;
            padding: 16px;
            align-self: start;
        }

        .tr-areas__none {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .tr-areas__privacy-note {
            margin: 16px 0 0;
            color: var(--faint);
            font-size: 13px;
            line-height: 1.5;
        }

        .tr-areas__list {
            margin: 16px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 4px;
        }

        .tr-areas__row {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr) auto;
            grid-template-areas:
                'rank name count'
                'bar  bar  bar';
            column-gap: 8px;
            row-gap: 6px;
            width: 100%;
            padding: 8px;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            background: transparent;
            font-family: var(--font-sans);
            text-align: left;
            cursor: pointer;
        }

        .tr-areas__row:hover { background: var(--navy-50); }

        .tr-areas__row:focus-visible {
            outline: none;
            box-shadow: var(--ring);
        }

        .tr-areas__row[aria-pressed="true"] {
            background: var(--navy-50);
            border-color: var(--navy-100);
        }

        .tr-areas__rank {
            grid-area: rank;
            font-size: 13px;
            font-weight: 700;
            color: var(--faint);
            font-variant-numeric: tabular-nums;
            line-height: 1.5;
        }

        .tr-areas__name {
            grid-area: name;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.5;
            min-width: 0;
        }

        .tr-areas__count {
            grid-area: count;
            justify-self: end;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            line-height: 1.5;
        }

        .tr-areas__bar {
            grid-area: bar;
            height: 6px;
            border-radius: 3px;
            background: var(--navy-50);
            overflow: hidden;
        }

        .tr-areas__fill {
            display: block;
            height: 100%;
            border-radius: 3px;
            background: var(--navy-700);
        }

        .tr-areas__toggle {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 44px;
            margin-top: 8px;
            padding: 8px 12px;
            border: 0;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--navy-700);
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .tr-areas__toggle:hover { background: var(--navy-50); text-decoration: underline; text-underline-offset: 2px; }
        .tr-areas__toggle:focus-visible { outline: none; box-shadow: var(--ring); }
        .tr-areas__toggle svg { width: 14px; height: 14px; transition: transform 160ms ease; }
        .tr-areas__toggle[aria-expanded="true"] svg { transform: rotate(180deg); }

        .tr-areas__empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 32px 16px;
            text-align: center;
        }

        .tr-areas__empty-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--navy-50);
            color: var(--navy-700);
        }

        .tr-areas__empty-icon svg { width: 22px; height: 22px; }

        .tr-areas__empty p {
            margin: 0;
            max-width: 220px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 1023px) {
            .tr-areas__toggle { display: inline-flex; }
            .tr-areas__item--extra { display: none; }
            .tr-areas[data-expanded="true"] .tr-areas__item--extra { display: list-item; }
        }

        /* ── Register header ────────────────────────────────── */
        .tr-register-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 32px;
        }

        .tr-results {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            font-variant-numeric: tabular-nums;
        }

        .tr-cap-notice {
            margin: 8px 0 0;
            color: var(--faint);
            font-size: 12px;
            line-height: 1.5;
        }

        .tr-view-all {
            display: flex;
            justify-content: center;
            margin-top: 24px;
            padding-bottom: 48px;
        }

        .tr-view-all__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: auto;
            max-width: 100%;
            min-height: 52px;
            padding: 14px 24px;
            border-radius: 14px;
            background: #d4a947;
            color: #0b1a33;
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 700;
            line-height: 1.3;
            text-align: center;
            text-decoration: none;
            white-space: normal;
            box-shadow: 0 4px 24px rgba(201, 168, 76, 0.32);
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        @media (min-width: 768px) {
            .tr-view-all__btn {
                padding: 0 32px;
                white-space: nowrap;
            }
        }

        .tr-view-all__btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 28px rgba(201, 168, 76, 0.4);
        }

        .tr-view-all__btn:focus-visible {
            outline: none;
            box-shadow: var(--ring);
        }

        /* ── Filter card ────────────────────────────────────── */
        .tr-filters {
            margin-top: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .tr-filters__row1 {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .tr-search {
            position: relative;
            flex: 1.6 1 0;
            min-width: 0;
        }

        .tr-search__icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--faint);
            pointer-events: none;
        }

        .tr-search input {
            width: 100%;
            height: 44px;
            padding: 0 12px 0 42px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text);
            font-family: var(--font-sans);
            font-size: 15px;
            outline: none;
            appearance: none;
        }

        .tr-search input::placeholder { color: var(--faint); }

        .tr-search input:focus-visible {
            border-color: var(--gold-500);
            box-shadow: var(--ring);
        }

        .tr-selects {
            display: flex;
            gap: 12px;
            flex: 3 1 0;
        }

        .tr-select {
            position: relative;
            flex: 1 1 0;
            min-width: 0;
        }

        .tr-select select {
            width: 100%;
            height: 44px;
            padding: 0 36px 0 12px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text);
            font-family: var(--font-sans);
            font-size: 14px;
            outline: none;
            appearance: none;
            cursor: pointer;
        }

        .tr-select select:focus-visible {
            border-color: var(--gold-500);
            box-shadow: var(--ring);
        }

        .tr-select__chevron {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--muted);
            pointer-events: none;
        }

        .tr-filters__row2 {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .tr-chips {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            flex: 1 1 auto;
            min-width: 0;
        }

        .status-chip {
            min-height: 36px;
            padding: 0 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-pill);
            background: var(--surface);
            color: var(--muted);
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
        }

        .status-chip:hover { background: var(--navy-50); color: var(--navy-700); }

        .status-chip:focus-visible { outline: none; box-shadow: var(--ring); }

        .status-chip.selected {
            background: var(--navy-700);
            border-color: var(--navy-700);
            color: var(--surface);
        }

        .tr-barangay-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 36px;
            padding: 0 6px 0 12px;
            border: 1px solid var(--navy-100);
            border-radius: var(--radius-pill);
            background: var(--navy-50);
            color: var(--navy-700);
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .tr-barangay-chip:hover { background: var(--navy-100); }
        .tr-barangay-chip:focus-visible { outline: none; box-shadow: var(--ring); }
        .tr-barangay-chip svg { width: 14px; height: 14px; }

        .tr-clear {
            margin-left: auto;
            min-height: 44px;
            padding: 0 4px;
            border: 0;
            background: transparent;
            color: var(--navy-700);
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
            white-space: nowrap;
            cursor: pointer;
        }

        .tr-clear:hover { color: var(--navy-900); }
        .tr-clear:focus-visible { outline: none; box-shadow: var(--ring); border-radius: var(--radius-sm); }

        @media (max-width: 1023px) {
            .tr-filters__row1 { flex-direction: column; align-items: stretch; gap: 12px; }
            .tr-search { flex: none; width: 100%; }
            .tr-selects { flex: none; width: 100%; }
            .tr-selects .tr-select:first-child { flex: none; width: 100%; }
            .tr-selects { flex-wrap: wrap; }
            .tr-selects .tr-select--category,
            .tr-selects .tr-select--sort { flex: 1 1 calc(50% - 6px); }
        }

        @media (max-width: 768px) {
            .tr-selects .tr-select--department { flex: none; width: 100%; }

            .tr-filters__row2 { flex-wrap: nowrap; gap: 8px; }
            .tr-chips {
                flex-wrap: nowrap;
                overflow-x: auto;
                scrollbar-width: none;
                -ms-overflow-style: none;
                padding-bottom: 2px;
            }
            .tr-chips::-webkit-scrollbar { display: none; }
            .tr-clear { margin-left: 0; }
        }

        /* ── List card ──────────────────────────────────────── */
        .tr-list-card {
            margin-top: 24px;
            margin-bottom: 48px;
            overflow: hidden;
        }

        ul, ol { margin: 0; padding: 0; list-style: none; }

        .report-list { display: block; }

        .report-list > li[data-report-row] {
            border-bottom: 1px solid var(--line-soft);
        }

        .report-list > li[data-report-row]:last-of-type { border-bottom: 0; }

        .report-list__head {
            display: grid;
            grid-template-columns: minmax(0, 2.6fr) minmax(0, 1.4fr) 150px 110px 16px;
            gap: 16px;
            padding: 10px 20px;
            background: var(--navy-50);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .report-list__head > :last-child { justify-self: end; }

        .report-row {
            display: grid;
            grid-template-columns: minmax(0, 2.6fr) minmax(0, 1.4fr) 150px 110px 16px;
            align-items: center;
            gap: 16px;
            min-height: 76px;
            padding: 16px 20px;
            text-decoration: none;
            color: inherit;
        }

        .report-row:hover { background: var(--navy-50); }

        .report-row:focus-visible {
            outline: none;
            box-shadow: var(--ring-inset);
        }

        /* Complaint cell */
        .report-row__complaint {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .report-row__text { min-width: 0; }

        .report-row__title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: -0.005em;
            color: var(--text);
        }

        .report-row__meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            font-size: 13px;
            color: var(--muted);
            min-width: 0;
        }

        .report-row__meta-pin {
            width: 12px;
            height: 12px;
            flex: 0 0 auto;
            color: var(--faint);
        }

        /* .ticket-id + .cat-icon extracted to partials.public-ui */

        /* Department badge (colours reused from the admin dashboard's
           [data-dept] tokens so the portal matches itself). */
        .dept-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 6px;
            border-radius: 6px;
            background: var(--dept-tint, var(--dept-fallback-tint));
            color: var(--dept-accent, var(--dept-fallback));
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.3;
            white-space: nowrap;
        }

        .report-row__dept {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .report-row__dept-name {
            font-size: 13px;
            color: var(--muted);
            min-width: 0;
            overflow-wrap: anywhere;
        }

        /* Status cell: pill + 5-segment progress bar */
        .report-row__status {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-start;
            min-width: 0;
        }

        /* .status-pill + .progress extracted to partials.public-ui */

        .report-row__reported {
            font-size: 13px;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .report-row__chevron {
            justify-self: end;
            width: 16px;
            height: 16px;
            color: var(--faint);
        }

        /* Verified pill (only rendered when the complaint is verified) */
        .report-row__verified {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 20px;
            padding: 0 8px;
            border-radius: var(--radius-pill);
            background: var(--status-review-bg);
            color: var(--status-review-text);
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .report-row__verified svg { width: 11px; height: 11px; }

        /* Desktop: full-width mobile progress block hidden */
        .report-row__progressline { display: none; }

        /* ── Mobile: one flex-wrap reading of the same markup ─────── */
        @media (max-width: 767px) {
            .report-list {
                display: grid;
                gap: 12px;
                padding: 12px;
            }

            .report-list__head { display: none; }

            .report-list > li[data-report-row] {
                border: 1px solid var(--line-soft);
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-card);
                background: var(--surface);
                overflow: hidden;
                transition: transform 160ms ease, box-shadow 160ms ease;
            }

            /* The <a> is one wrapping flex line; nested cell wrappers
               dissolve with display: contents so their children join it. */
            .report-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px 12px;
                min-height: 0;
                padding: 16px;
            }

            .report-row__complaint,
            .report-row__text,
            .report-row__meta,
            .report-row__dept { display: contents; }

            .cat-icon { order: 1; }
            .ticket-id { order: 2; align-self: center; }
            .report-row__status {
                order: 3;
                flex-direction: row;
                align-items: center;
                gap: 8px;
                margin-left: auto;
            }
            .progress--mini { display: none !important; }

            /* Line 2: the title, full width */
            .report-row__title {
                order: 4;
                flex: 1 1 100%;
                font-size: 17px;
                line-height: 1.35;
            }

            /* Line 3: badge, pin + barangay, date — wraps, no extra lines */
            .report-row__dept .dept-badge { order: 5; }
            .report-row__dept-name { display: none; }
            .report-row__meta-pin { order: 6; display: inline-block; }
            .report-row__brgy { order: 7; font-size: 13px; color: var(--muted); }
            .report-row__reported {
                order: 8;
                font-size: 13px;
                color: var(--muted);
                white-space: nowrap;
            }

            /* Line separator between ticket and the pin dot */
            .report-row__meta-dot { display: none; }

            /* Line 4: full-width progress block, step text above the bar */
            .report-row__progressline {
                order: 9;
                flex: 1 1 100%;
                display: grid;
                gap: 6px;
            }

            .report-row__progressline .progress__text {
                font-size: 12px;
                color: var(--muted);
            }

            .report-row__progressline .progress {
                display: flex;
                width: 100%;
            }

            /* No chevron, no bullets, no footers on mobile */
            .report-row__chevron { display: none; }
        }

        @media (hover: hover) and (min-width: 768px) {
            .report-list > li[data-report-row]:hover .report-row { background: var(--navy-50); }
        }

        @media (hover: hover) and (max-width: 767px) {
            .report-list > li[data-report-row]:hover {
                transform: translateY(-1px);
                box-shadow: var(--shadow-lift);
            }

            .report-list > li[data-report-row] {
                transition: transform 160ms ease, box-shadow 160ms ease;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .report-list > li[data-report-row] { transition: none !important; }
            .tr-how__chevron, .tr-areas__toggle svg { transition: none; }
        }

        /* ── Empty / no-results / error states ──────────────── */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 48px 20px;
            text-align: center;
        }

        .empty-state__icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--navy-50);
            color: var(--navy-700);
        }

        .empty-state__icon svg { width: 28px; height: 28px; }

        .empty-state strong {
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
        }

        .empty-state p {
            margin: 0;
            max-width: 400px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .tr-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 20px;
            margin-top: 4px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--navy-700);
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .tr-button:hover { background: var(--navy-50); }
        .tr-button:focus-visible { outline: none; box-shadow: var(--ring); }

        /* Mute the stock tile layer so the heat ramp carries the signal.
           Only the tile pane is filtered; the heat overlay and the
           attribution keep their colours and contrast. */
        .tr-map .leaflet-tile-pane { filter: saturate(0.45) brightness(1.08) contrast(0.92); }

        /* Leaflet attribution stays readable */
        /* Leaflet attribution stays readable — and visible, sitting above
           the no-areas/loading overlay when one is shown. Still inside
           .tr-map's stacking context. */
        .leaflet-control-attribution {
            font-size: 10px !important;
            background: rgba(255, 255, 255, 0.78) !important;
            z-index: 1200 !important;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
