@extends($mainLayout)

@section('body-class', 'admin-layout')

@section('title', 'Design System — Daet Listens')

{{--
    The living reference for docs/design-system.md.

    Not public. The route in web.php aborts 403 unless this is the local
    environment or the viewer is an admin. It renders the real component
    stylesheet, so a token that stops working takes the whole page down
    with it — which is the point.
--}}

@push('styles')
    @include('partials.public-ui')
    @include('pages.transparency.partials.component-styles')
    <style>
        .ds-root { min-height: 100vh; background: var(--bg); color: var(--text); font-family: var(--font-sans); padding-bottom: 48px; }
        .ds-container { width: 100%; max-width: 1040px; margin: 0 auto; padding: 32px 24px 0; }
        @media (max-width: 768px) { .ds-container { padding: 24px 16px 0; } }

        .ds-h1 { margin: 0 0 4px; font-size: 34px; font-weight: 700; letter-spacing: var(--tracking-display); line-height: 1.15; }
        .ds-kicker { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 4px 10px; border-radius: var(--radius-pill); background: var(--navy-50); color: var(--navy-700); font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
        .ds-sub { margin: 0 0 32px; color: var(--muted); font-size: 14px; line-height: 1.6; max-width: 640px; }
        .ds-sub code { font-family: var(--font-mono); font-size: 12px; background: var(--navy-50); border-radius: 4px; padding: 1px 6px; color: var(--navy-700); }

        .ds-section { margin-bottom: 48px; }
        .ds-h2 { margin: 0 0 4px; font-size: 22px; font-weight: 700; letter-spacing: -0.01em; }
        .ds-h3 { margin: 16px 0 12px; font-size: 15px; font-weight: 600; color: var(--muted); }
        .ds-note { margin: 0 0 16px; color: var(--muted); font-size: 13px; }
        .ds-note code { font-family: var(--font-mono); font-size: 11px; background: var(--navy-50); border-radius: 4px; padding: 1px 5px; }

        /* Swatches */
        .ds-swatches { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        .ds-swatch { border: 1px solid var(--line-soft); border-radius: var(--radius); overflow: hidden; background: var(--surface); }
        .ds-swatch__chip { height: 64px; display: flex; align-items: flex-end; padding: 8px; gap: 6px; }
        .ds-swatch__chip--light { background: var(--surface); border-bottom: 1px solid var(--line-soft); }
        .ds-swatch__chip > span { font-family: var(--font-mono); font-size: 10px; padding: 1px 6px; border-radius: 4px; background: rgba(11, 26, 51, 0.55); color: #fff; }
        .ds-swatch--light .ds-swatch__chip > span { background: var(--line-soft); color: var(--navy-800); }
        .ds-swatch__body { padding: 8px 10px; }
        .ds-swatch__name { display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--navy-800); }
        .ds-swatch__hex { display: block; font-family: var(--font-mono); font-size: 10px; color: var(--faint); margin-top: 2px; }
        .ds-swatch__use { display: block; font-size: 10px; color: var(--muted); margin-top: 4px; line-height: 1.4; }

        .ds-ramp { display: flex; height: 40px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--line-soft); margin-bottom: 8px; }
        .ds-ramp > div { flex: 1; display: flex; align-items: center; justify-content: center; font-family: var(--font-mono); font-size: 10px; color: rgba(11, 26, 51, 0.75); }

        /* Frames and rows */
        .ds-frame { border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; background: var(--bg); }
        .ds-frame--mobile { max-width: 393px; }
        .ds-frame iframe { display: block; width: 100%; height: 560px; border: 0; background: var(--bg); }
        .ds-row-example { font-family: var(--font-mono); font-size: 11px; color: var(--faint); margin-bottom: 8px; text-transform: uppercase; letter-spacing: .05em; }

        .ds-grid-2 { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
        @media (max-width: 900px) { .ds-grid-2 { grid-template-columns: 1fr; } }

        .ds-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ds-table th, .ds-table td { text-align: left; padding: 8px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
        .ds-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); background: var(--navy-50); }
        .ds-table code { font-family: var(--font-mono); font-size: 11px; background: var(--navy-50); padding: 1px 5px; border-radius: 4px; color: var(--navy-700); }

        .ds-dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; font-size: 13px; }
        .ds-dl dt { font-weight: 600; color: var(--navy-800); }
        .ds-dl dd { margin: 0; color: var(--muted); }

        .ds-kbd-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .ds-wireframe { font-family: var(--font-mono); font-size: 11px; line-height: 1.45; background: var(--navy-900); color: #dbe4f3; padding: 20px; border-radius: var(--radius-lg); overflow-x: auto; white-space: pre; }
    </style>
@endpush

@php
    $navyAndGold = [
        ['--navy-950', '#071226', 'deepest backdrop'],
        ['--navy-900', '#0b1a33', 'banner start · headings on navy'],
        ['--navy-800', '#102545', 'sidebar/rail fills'],
        ['--navy-700', '#17305a', 'selected chip · bar fill · banner mid'],
        ['--navy-600', '#1e3f73', 'banner end (highlight)'],
        ['--navy-100', '#dbe4f3', 'hairlines on navy'],
        ['--navy-50', '#eef3fb', 'card headers · icon tint · row hover'],
        ['--gold-300', '#e7c873', 'gold ON navy only (links, disclosure)'],
        ['--gold-500', '#d4a947', 'accent: current segment · CTAs'],
        ['--gold-700', '#8a6a12', 'gold text on light surfaces'],
    ];
    $neutrals = [
        ['--bg', '#f4f6fa', 'page background'],
        ['--surface', '#ffffff', 'cards · inputs'],
        ['--line', '#d9e0ec', 'control borders'],
        ['--line-soft', '#e8edf5', 'hairline dividers'],
        ['--text', '#0f172a', 'body ink'],
        ['--muted', '#475569', 'labels · secondary text'],
        ['--faint', '#64748b', 'icons · de-emphasis'],
        ['--map-loading-bg', '#eef1f6', 'map overlay (opaque)'],
    ];
    $statusSwatches = [
        ['submitted', '#dbeafe', '#1e3a8a', '#3b82f6'],
        ['under_review / verified / assigned', '#e8eef8', '#17305a', '#17305a'],
        ['in_progress', '#fef3c7', '#92400e', '#f59e0b'],
        ['resolved', '#dcfce7', '#166534', '#22c55e'],
        ['rejected', '#fee2e2', '#991b1b', '#ef4444'],
        ['closed', '#e2e8f0', '#334155', '#64748b'],
    ];
    $categorySwatches = [
        ['road_damage → cone', '#fef3c7', '#b45309'],
        ['noise_complaint → volume', '#ede9fe', '#6d28d9'],
        ['flooding → droplet', '#e0f2fe', '#0369a1'],
        ['garbage / sanitation → trash', '#dcfce7', '#15803d'],
        ['streetlight → lightbulb', '#fef9c3', '#a16207'],
        ['anything else → file', '#e2e8f0', '#475569'],
    ];
    $heatStops = [
        ['--heat-1', '#e0f2e9', 'low'],
        ['--heat-2', '#fde68a', ''],
        ['--heat-3', '#fb923c', ''],
        ['--heat-4', '#dc2626', 'high'],
    ];
    $deptSwatches = [
        ['ENG', '#B45309', 'rgba(180,83,9,.16)'], ['HLTH', '#0F766E', 'rgba(15,118,110,.16)'],
        ['GSO', '#475569', 'rgba(71,85,105,.16)'], ['MPDO', '#1D4ED8', 'rgba(29,78,216,.16)'],
        ['WST', '#15803D', 'rgba(21,128,61,.16)'], ['SWDO', '#7C3AED', 'rgba(124,58,237,.16)'],
        ['BPLO', '#0E7490', 'rgba(14,116,144,.16)'], ['OM', '#A16207', 'rgba(161,98,7,.16)'],
        ['fallback', '#475569', 'rgba(71,85,105,.16)'],
    ];
@endphp

@if(request('embed') === 'list')
    {{-- iframe target: only the report-row examples, at the iframe's width.
         The layout renders the navbar/drawer too, so those are hidden here. --}}
    <style>
        #navbar, .mobile-nav-drawer, .mobile-nav-overlay { display: none !important; }
        main { padding-top: 0 !important; }
    </style>
    <div class="ds-embed" style="margin: 0;">
    <div class="ds-frame" style="border: 0; border-radius: 0;">
        @include('pages.design-system-rows')
    </div>
    </div>
@else
<div class="ds-root">
<div class="ds-container">

    <header>
        <span class="ds-kicker">Internal reference · not public</span>
        <h1 class="ds-h1">Daet Listens design system</h1>
        <p class="ds-sub">The living companion to <code>docs/design-system.md</code>. Every swatch and every component on this page comes from the tokens in <code>resources/css/tokens.css</code> and the shared component partial — if you change a token, change this page, not the swatches. See the <a href="{{ route('transparency') }}">transparency page</a> for the reference implementation.</p>
    </header>

    {{-- ═══ TOKENS: brand + neutrals ═══ --}}
    <section class="ds-section" aria-labelledby="ds-colors">
        <h2 class="ds-h2" id="ds-colors">Colour tokens</h2>
        <p class="ds-note">Everything below is a CSS variable. Do not paste hex values into components.</p>

        <h3 class="ds-h3">Navy authority + warm gold</h3>
        <div class="ds-swatches">
            @foreach($navyAndGold as [$name, $hex, $use])
                <div class="ds-swatch {{ $name === '--navy-50' || $name === '--gold-300' ? 'ds-swatch--light' : '' }}">
                    <div class="ds-swatch__chip" style="background: {{ $hex }};"><span>{{ $hex }}</span></div>
                    <div class="ds-swatch__body"><span class="ds-swatch__name">{{ $name }}</span><span class="ds-swatch__hex">{{ $hex }}</span><span class="ds-swatch__use">{{ $use }}</span></div>
                </div>
            @endforeach
        </div>

        <h3 class="ds-h3">Neutrals</h3>
        <div class="ds-swatches">
            @foreach($neutrals as [$name, $hex, $use])
                <div class="ds-swatch ds-swatch--light">
                    <div class="ds-swatch__chip" style="background: {{ $hex }};"><span>{{ $hex }}</span></div>
                    <div class="ds-swatch__body"><span class="ds-swatch__name">{{ $name }}</span><span class="ds-swatch__hex">{{ $hex }}</span><span class="ds-swatch__use">{{ $use }}</span></div>
                </div>
            @endforeach
        </div>

        <h3 class="ds-h3">Status (dot + text pills, 26px, radius-pill)</h3>
        <div class="ds-swatches">
            @foreach($statusSwatches as [$key, $bg, $text, $dot])
                <div class="ds-swatch ds-swatch--light">
                    <div class="ds-swatch__chip" style="background: {{ $bg }}; border-bottom: 1px solid var(--line-soft);"><span style="background: {{ $text }}; color: {{ $bg }};">{{ $text }}</span></div>
                    <div class="ds-swatch__body"><span class="ds-swatch__name">{{ $key }}</span><span class="ds-swatch__hex">dot {{ $dot }}</span><span class="ds-swatch__use">bg {{ $bg }} · text {{ $text }}</span></div>
                </div>
            @endforeach
        </div>

        <h3 class="ds-h3">Category icon circles (36px)</h3>
        <div class="ds-swatches">
            @foreach($categorySwatches as [$key, $bg, $fg])
                <div class="ds-swatch ds-swatch--light">
                    <div class="ds-swatch__chip" style="background: {{ $bg }}; border-bottom: 1px solid var(--line-soft);"><span style="background: {{ $fg }}; color: {{ $bg }};">{{ $fg }}</span></div>
                    <div class="ds-swatch__body"><span class="ds-swatch__name">{{ $key }}</span><span class="ds-swatch__hex">bg {{ $bg }} · fg {{ $fg }}</span><span class="ds-swatch__use">icon uses the file's own line SVG</span></div>
                </div>
            @endforeach
        </div>

        <h3 class="ds-h3">Department badges (11/700, radius 6)</h3>
        <div class="ds-swatches">
            @foreach($deptSwatches as [$code, $fg, $tint])
                <div class="ds-swatch ds-swatch--light">
                    <div class="ds-swatch__chip" style="background: {{ $tint }}; border-bottom: 1px solid var(--line-soft);"><span style="background: {{ $fg }}; color: #fff;">{{ $code }}</span></div>
                    <div class="ds-swatch__body"><span class="ds-swatch__name">{{ $code }}</span><span class="ds-swatch__hex">{{ $fg }}</span><span class="ds-swatch__use">tint {{ $tint }}</span></div>
                </div>
            @endforeach
        </div>

        <h3 class="ds-h3">Heat ramp (map legend + <code>L.heatLayer</code> gradient)</h3>
        <div class="ds-ramp" role="img" aria-label="Concentration of reports, from low to high">
            @foreach($heatStops as [$name, $hex, $edge])
                <div style="background: {{ $hex }}">{{ $name }}</div>
            @endforeach
        </div>
        <p class="ds-note"><code>--heat-1</code> low · neutral below 3 reports · <code>--heat-4</code> high. Areas with under 3 reports never render a colour.</p>

        <h3 class="ds-h3">Radius, shadow, ring</h3>
        <table class="ds-table">
            <thead><tr><th>Token</th><th>Value</th><th>Use</th></tr></thead>
            <tbody>
                <tr><td><code>--radius-sm</code></td><td>8px</td><td>buttons, chips, inputs, selects</td></tr>
                <tr><td><code>--radius</code></td><td>12px</td><td>disclosure popover, small cards</td></tr>
                <tr><td><code>--radius-lg</code></td><td>16px</td><td>all cards</td></tr>
                <tr><td><code>--radius-pill</code></td><td>999px</td><td>pills, chips, rank moves</td></tr>
                <tr><td><code>--shadow-card</code></td><td>two soft navy-tinted</td><td>default card elevation</td></tr>
                <tr><td><code>--shadow-lift</code></td><td>deeper two-layer</td><td>hover lift, popovers</td></tr>
                <tr><td><code>--ring</code></td><td>3px rgba(212,169,71,.35)</td><td>focus ring, standard</td></tr>
                <tr><td><code>--ring-inset</code></td><td>inset 3px same</td><td>focus on full-width rows</td></tr>
            </tbody>
        </table>
    </section>

    {{-- ═══ TYPOGRAPHY ═══ --}}
    <section class="ds-section" aria-labelledby="ds-type">
        <h2 class="ds-h2" id="ds-type">Typography</h2>
        <p class="ds-note">Inter everywhere. No serif, ever. Tabular numerals for IDs, counts and dates.</p>
        <dl class="ds-dl">
            <dt>h1 · 34px / 700 / −0.02em</dt><dd><span style="font-size:34px;font-weight:700;letter-spacing:-0.02em;line-height:1.15">Public transparency</span></dd>
            <dt>h2 · 20px / 700 / −0.01em</dt><dd><span style="font-size:20px;font-weight:700;letter-spacing:-0.01em">Where reports are concentrated</span></dd>
            <dt>Body · 15px / 400</dt><dd>Body text reads this size. Ticket IDs use tabular figures.</dd>
            <dt>Small · 13px</dt><dd><span style="font-size:13px">Muted support text — counts, dates, meta rows.</span></dd>
            <dt>Label · 12px / 600 / uppercase</dt><dd><span style="font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)">Department</span></dd>
            <dt>Mono IDs</dt><dd><span style="font-family:var(--font-mono);font-size:13px;font-weight:600;color:var(--navy-700);font-variant-numeric:tabular-nums">COMP-2026-00022</span></dd>
        </dl>
    </section>

    {{-- ═══ BANNER EXAMPLE ═══ --}}
    <section class="ds-section" aria-labelledby="ds-banner">
        <h2 class="ds-h2" id="ds-banner">Banner / page header</h2>
        <p class="ds-note">Component: <code>.tr-banner</code> — full-bleed, navy gradient + hairline pattern layer, privacy sentence, disclosure, updated line. No buttons.</p>
        @include('pages.transparency.partials.banner-example')
    </section>

    {{-- ═══ STAT STRIP ═══ --}}
    <section class="ds-section" aria-labelledby="ds-stats">
        <h2 class="ds-h2" id="ds-stats">Stat strip</h2>
        <p class="ds-note"><code>.tr-stats</code> — margin-top −32px, 4 cards (2×2 on mobile). Values computed server-side from the same payload as the list.</p>
        @include('partials.public-ui')
        @include('pages.transparency.partials.component-styles')
        <div style="background: var(--navy-900); border-radius: var(--radius-lg); padding: 32px 24px 64px; margin-bottom: 16px;">
            <p style="margin: 0; color: rgba(255,255,255,.82); font-size: 13px;">(banner tail — stats overlap this surface)</p>
        </div>
        <div class="tr-stats" style="grid-template-columns: repeat(4, 1fr); margin-top: -32px;">
            <div class="tr-stat"><span class="tr-stat__icon tr-stat__icon--navy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span><span class="tr-stat__value">4</span><span class="tr-stat__label">Public reports</span></span></div>
            <div class="tr-stat"><span class="tr-stat__icon tr-stat__icon--amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg></span><span><span class="tr-stat__value">1</span><span class="tr-stat__label">In progress</span></span></div>
            <div class="tr-stat"><span class="tr-stat__icon tr-stat__icon--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span><span><span class="tr-stat__value">1</span><span class="tr-stat__label">Resolved</span></span></div>
            <div class="tr-stat"><span class="tr-stat__icon tr-stat__icon--sky"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></span><span><span class="tr-stat__value">25%</span><span class="tr-stat__label">Resolution rate</span></span></div>
        </div>
    </section>

    {{-- ═══ BUTTONS ═══ --}}
    <section class="ds-section" aria-labelledby="ds-buttons">
        <h2 class="ds-h2" id="ds-buttons">Buttons</h2>
        <p class="ds-note">Primary = gold gradient + navy text (one per screen). Secondary = white + <code>--line</code> + navy text. Danger = destructive tint. Ghost = navy text + underline. 40px / 44px touch targets.</p>
        <div class="ds-kbd-row">
            <a class="btn-nav-cta" href="#ds-buttons" style="text-transform:none; letter-spacing:0; font-size:14px; border-radius: var(--radius-sm); padding: 10px 18px;">File a complaint</a>
            <button class="tr-button" type="button">Secondary</button>
            <button class="tr-button" type="button" style="color:#991b1b; border-color:#fecaca;">Danger</button>
            <button class="tr-clear" type="button">Ghost · clear filters</button>
        </div>
        <p class="ds-note" style="margin-top:12px"><code>.btn-nav-cta</code> is the literal navbar component (gradient, bold navy, shadow) — mapping for the portal, not for every button on every page.</p>
    </section>

    {{-- ═══ BUSY / LOADING ACTIONS ═══ --}}
    <section class="ds-section" aria-labelledby="ds-loading">
        <h2 class="ds-h2" id="ds-loading">Busy / loading actions (§5.32)</h2>
        <p class="ds-note">Applied on press for actions with pending work (Apply, Submit, Login, Save, Send, state changes) across all sectors. The inline spinner uses <code>currentColor</code> (no new colours) and keeps the label visible. JS sets <code>.is-loading</code> + <code>aria-busy="true"</code> and prevents double activation; it clears on settle, validation re-render, or bfcache restore. Under <code>prefers-reduced-motion: reduce</code> the spin becomes a static dotted ring.</p>
        <div class="ds-kbd-row" style="align-items:center;">
            <button class="btn-nav-cta is-loading" type="button" aria-busy="true" disabled style="text-transform:none; letter-spacing:0; font-size:14px; border-radius: var(--radius-sm); padding: 10px 18px;">Submitting</button>
            <button class="tr-button is-loading" type="button" aria-busy="true" disabled>Applying</button>
            <button class="admin-button admin-button--primary is-loading" type="button" aria-busy="true" disabled>Saving</button>
            <button class="tr-button is-loading" type="button" aria-busy="true" disabled style="color:#991b1b; border-color:#fecaca;">Deleting</button>
            <button class="tr-clear is-loading" type="button" aria-busy="true" disabled>Sending</button>
        </div>
        <p class="ds-note" style="margin-top:12px">Forms: <code>data-loading-submit</code> (global auto-applies to non-GET forms). Fetch: <code>window.daetActionLoading.setBusy(el, true|false)</code>. Livewire: <code>wire:loading.class="is-loading" wire:loading.attr="aria-busy=true" wire:target="…"</code>. Opt out with <code>data-loading="off"</code>/<code>data-loading-managed</code>.</p>
    </section>

    {{-- ═══ FORM CONTROLS ═══ --}}
    <section class="ds-section" aria-labelledby="ds-forms">
        <h2 class="ds-h2" id="ds-forms">Search, select, chips</h2>
        <div class="ds-grid-2">
            <div>
                <label class="tr-search" for="ds-search">
                    <span class="sr-only">Search</span>
                    <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input id="ds-search" type="search" placeholder="Ticket, title, department…" value="pothole">
                </label>
                <div style="height:16px"></div>
                <div class="tr-selects" style="max-width: 520px;">
                    <div class="tr-select">
                        <select id="ds-department" aria-label="Department">
                            <option>All departments</option><option>Engineering Office</option><option>Office of the Mayor</option>
                        </select>
                        <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="tr-select">
                        <select id="ds-sort" aria-label="Sort">
                            <option>Newest first</option><option>Oldest first</option><option>Status</option>
                        </select>
                        <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
                <div style="height:16px"></div>
                <div class="tr-chips">
                    <button class="status-chip selected" type="button">All (4)</button>
                    <button class="status-chip" type="button">Submitted</button>
                    <button class="status-chip" type="button">In Progress</button>
                    <button class="status-chip" type="button">Resolved</button>
                </div>
                <div style="height:24px"></div>
                <div style="position:relative; max-width:340px;">
                    <div style="display:flex; gap:8px; align-items:center; position:relative;">
                        <input type="search" placeholder="Search ticket or title" readonly style="height:48px; flex:1 1 0; min-width:0; border:1px solid var(--line); border-radius:var(--radius-sm); padding:0 12px; font-size:15px; background:var(--surface); color:var(--muted);" aria-label="Search demo">
                        <button type="button" aria-expanded="true" aria-controls="ds-popover-demo" style="display:inline-flex; align-items:center; gap:8px; flex:0 0 auto; height:48px; padding:0 12px; border:1px solid var(--line); border-radius:var(--radius-sm); background:var(--surface); color:var(--text); font-family:var(--font-sans); font-size:14px; font-weight:600; cursor:pointer;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>Filters<span style="display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 6px; border-radius:var(--radius-pill); background:var(--navy-700); color:var(--surface); font-size:12px; font-weight:700;">2</span></button>
                    </div>
                    <div id="ds-popover-demo" style="position:absolute; top:calc(100% + 8px); right:0; width:min(100%, 340px); z-index:var(--z-popover); display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; padding:16px; background:var(--surface); border:1px solid var(--line-soft); border-radius:var(--radius-sm); box-shadow:var(--shadow-lift);">
                        <div class="tr-select" style="grid-column:1/-1;"><select aria-label="Department"><option>All departments</option></select><svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg></div>
                        <div class="tr-select"><select aria-label="Category"><option>All categories</option></select><svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg></div>
                        <div class="tr-select"><select aria-label="Sort"><option>Newest first</option></select><svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg></div>
                        <button type="button" style="grid-column:span 1; min-height:48px; border:0; border-radius:var(--radius-sm); background:var(--gold-500); color:var(--navy-900); font-family:var(--font-sans); font-weight:700; font-size:16px;">Apply</button>
                        <button type="button" style="grid-column:span 1; min-height:48px; border:1px solid var(--line); border-radius:var(--radius-sm); background:var(--surface); color:var(--text); font-family:var(--font-sans); font-weight:600;">Clear filters</button>
                    </div>
                </div>
            </div>
            <div>
                <div class="tr-filters" style="padding:16px;">
                    <p style="margin:0 0 12px; font-size:11px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--muted);">Filter bar card</p>
                    <p style="margin:0; font-size:13px; color:var(--muted); line-height:1.6;">Wireframe: search (flex 1.6) + three selects (flex 1) on row 1; status chips scrollable row + barangay chip + Clear filters on row 2. Mobile: search → department (full) → category/sort (50/50) → chips scroll.</p>
                    <button class="tr-barangay-chip" type="button" style="margin-top: 12px;"><span class="tr-barangay-chip__label">Barangay VI (Centro)</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
            </div>
        </div>
    </section>


    {{-- ═══ STATUS PILLS / DEPT BADGES / CATEGORY ICONS ═══ --}}
    <section class="ds-section" aria-labelledby="ds-meaning">
        <h2 class="ds-h2" id="ds-meaning">Colours for meaning</h2>
        <div class="ds-grid-2">
            <div>
                <h3 class="ds-h3">Status pills</h3>
                <div class="ds-kbd-row">
                    <span class="status-pill submitted"><span class="status-pill__dot"></span>Submitted</span>
                    <span class="status-pill under_review"><span class="status-pill__dot"></span>Under review</span>
                    <span class="status-pill in_progress"><span class="status-pill__dot"></span>In progress</span>
                    <span class="status-pill resolved"><span class="status-pill__dot"></span>Resolved</span>
                    <span class="status-pill rejected"><span class="status-pill__dot"></span>Rejected</span>
                    <span class="status-pill closed"><span class="status-pill__dot"></span>Closed</span>
                </div>
                <p class="ds-note" style="margin-top:8px">verified / assigned reuse the <code>under_review</code> family. Adding a status = 3 tokens + one <code>.status-pill.key</code> rule.</p>

                <h3 class="ds-h3">Verified pill (only when actually verified)</h3>
                <span class="report-row__verified"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" width="11" height="11" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>Verified</span>

                <h3 class="ds-h3">Department badges</h3>
                <div class="ds-kbd-row">
                    <span class="dept-badge" data-dept="ENG">ENG</span>
                    <span class="dept-badge" data-dept="HLTH">HLTH</span>
                    <span class="dept-badge" data-dept="GSO">GSO</span>
                    <span class="dept-badge" data-dept="MPDO">MPDO</span>
                    <span class="dept-badge" data-dept="WST">WST</span>
                    <span class="dept-badge" data-dept="SWDO">SWDO</span>
                    <span class="dept-badge" data-dept="BPLO">BPLO</span>
                    <span class="dept-badge" data-dept="OM">OM</span>
                    <span class="dept-badge" data-dept="UNKNOWN-XX">UNKNOWN</span>
                </div>

                <h3 class="ds-h3">Account identity (navbar, §5.31)</h3>
                <p class="ds-note">Staff/admin context shown in the fixed top bar (desktop + mobile). On navy chrome; uses dept tokens for staff and navy/gold tokens for admin. No new colours.</p>
                <div style="display:flex; flex-wrap:wrap; gap:12px 16px; align-items:center; padding:12px 16px; background:var(--navy-800); border:1px solid color-mix(in srgb, var(--gold-500) 30%, transparent); border-radius:var(--radius-sm);">
                    <span class="nav-identity nav-identity--on-dark" aria-label="Administrator">
                        <span class="nav-identity__badge nav-identity__badge--admin">Administrator</span>
                    </span>
                    <span class="nav-identity nav-identity--on-dark" aria-label="Engineering Office · Staff">
                        <span class="dept-badge" data-dept="ENG">ENG</span>
                        <span class="nav-identity__label">Engineering Office</span>
                    </span>
                    <span class="nav-identity nav-identity--on-dark" aria-label="Department pending · Staff">
                        <span class="nav-identity__badge nav-identity__badge--staff">Staff</span>
                        <span class="nav-identity__label">Department pending</span>
                    </span>
                </div>
            </div>
            <div>
                <h3 class="ds-h3">Category icon circles</h3>
                <div class="ds-kbd-row" style="gap: 8px 16px;">
                    @include('pages.transparency.partials.category-icon', ['category' => 'road_damage'])
                    @include('pages.transparency.partials.category-icon', ['category' => 'noise_complaint'])
                    @include('pages.transparency.partials.category-icon', ['category' => 'flooding'])
                    @include('pages.transparency.partials.category-icon', ['category' => 'garbage'])
                    @include('pages.transparency.partials.category-icon', ['category' => 'streetlight'])
                    @include('pages.transparency.partials.category-icon', ['category' => 'anything'])
                </div>
                <p class="ds-note" style="margin-top:8px">Mapped by category <em>key</em>, never by translated label.</p>
            </div>
        </div>
    </section>

    {{-- ═══ REPORT ROWS ═══ --}}
    <section class="ds-section" aria-labelledby="ds-rows">
        <h2 class="ds-h2" id="ds-rows">Report row — one markup, two layouts</h2>
        <p class="ds-note">Desktop: grid <code>minmax(0,2.6fr) minmax(0,1.4fr) 150px 110px 16px</code>. Mobile: flex-wrap cards via <code>display: contents</code>; order = icon(1) ticket(2) status(3) title(4) badge(5) pin(6) barangay(7) date(8) progressline(9).</p>

        <h3 class="ds-h3">Desktop rendering</h3>
        <div class="tr-card tr-list-card" style="margin-bottom: 24px;">
            <div class="report-list__head" aria-hidden="true"><span>Complaint</span><span>Department</span><span>Status</span><span>Reported</span><span></span></div>
            @include('pages.design-system-rows')
        </div>

        <h3 class="ds-h3">Mobile rendering (iframe at 393px)</h3>
        <div class="ds-frame ds-frame--mobile">
            <iframe src="{{ route('design-system', ['embed' => 'list']) }}" title="Report rows at 393 pixels wide" loading="lazy" width="393" height="560"></iframe>
        </div>
    </section>

    {{-- ═══ ADMIN: AUDIT BROWSER ═══ --}}
    <section class="ds-section" aria-labelledby="ds-audit-browser">
        <h2 class="ds-h2" id="ds-audit-browser">Audit browser (§5.33)</h2>
        <p class="ds-note">
            The drill-down browser on <code>/admin/audit-trail</code>. Three bands in one card:
            the <strong>scope</strong> (what the trail below is showing), the <strong>search</strong>
            (the way to one person without scrolling), and the two <strong>panes</strong> — scopes on
            the left, people on the right. The roster is bounded: every group shows 8 rows and
            offers “See all N” (25 per page), so the page never grows with the number of residents
            in the log. Counts are totals and ignore the search and Action filters. Selected rows
            use <code>--navy-50</code> plus a 3px navy inset bar — never gold. On mobile the panes
            collapse behind a “Browse departments and people” disclosure; the scope band stays
            visible. URL: <code>?department_id=</code>, <code>?actor_id=</code>, <code>?system=1</code>,
            <code>?people_q=</code>, <code>?people_group=&amp;people_page=</code>.
        </p>
        <div class="ds-frame" style="background: var(--bg); padding: 20px;">
            <section class="admin-panel admin-audit-browser" style="margin-bottom: 0;" aria-label="Audit browser demo">
                <div class="admin-audit-browser__scope">
                    <span class="admin-audit-browser__scope-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M6 21V5l6-3 6 3v16"/><path d="M9 9h1"/><path d="M14 9h1"/><path d="M9 13h1"/><path d="M14 13h1"/><path d="M9 17h1"/><path d="M14 17h1"/></svg>
                    </span>
                    <p class="admin-audit-browser__scope-text">
                        <span class="admin-audit-browser__scope-label">Showing</span>
                        <strong class="admin-audit-browser__scope-name">Engineering Office</strong>
                        <span class="admin-audit-browser__scope-count">· 1,204 entries</span>
                    </p>
                    <a href="#ds-audit-browser" class="admin-audit-browser__scope-clear">Clear selection</a>
                </div>

                <div class="admin-audit-browser__bar">
                    <form class="admin-audit-browser__search" method="GET" action="#ds-audit-browser" onsubmit="return false;">
                        <label class="admin-audit-browser__search-field">
                            <span class="sr-only">Find a person…</span>
                            <svg class="admin-audit-browser__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="search" name="people_q" placeholder="Find a person…" autocomplete="off">
                        </label>
                        <button type="submit" class="admin-button admin-button--secondary admin-audit-browser__search-submit">Search</button>
                    </form>
                </div>

                <details class="admin-audit-browser__disclosure" data-audit-directory-mobile open>
                    <summary class="admin-audit-browser__disclosure-toggle">
                        <span class="admin-audit-browser__disclosure-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M6 21V5l6-3 6 3v16"/><path d="M9 9h1"/><path d="M14 9h1"/><path d="M9 13h1"/><path d="M14 13h1"/><path d="M9 17h1"/><path d="M14 17h1"/></svg>
                        </span>
                        <span class="admin-audit-browser__disclosure-label">Browse departments and people</span>
                        <span class="admin-audit-browser__disclosure-chevron" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </summary>

                    <div class="admin-audit-browser__panes">
                        <nav class="admin-audit-browser__contexts" aria-label="Scope demo">
                            <p class="admin-audit-browser__rail-head">
                                <span class="admin-audit-browser__rail-head-label">Scope</span>
                                <span class="admin-audit-browser__rail-head-label">People</span>
                            </p>

                            <a href="#ds-audit-browser" class="admin-audit-browser__context">
                                <span class="admin-audit-browser__context-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </span>
                                <span class="admin-audit-browser__context-name">Everyone</span>
                                <span class="admin-audit-browser__context-count">3,012</span>
                            </a>

                            <p class="admin-audit-browser__label">Offices</p>
                            <a href="#ds-audit-browser" class="admin-audit-browser__context is-selected" aria-current="true">
                                <span class="dept-badge" data-dept="ENG">ENG</span>
                                <span class="admin-audit-browser__context-name">Engineering Office</span>
                                <span class="admin-audit-browser__context-count">18</span>
                            </a>
                            <a href="#ds-audit-browser" class="admin-audit-browser__context">
                                <span class="dept-badge" data-dept="GSO">GSO</span>
                                <span class="admin-audit-browser__context-name">General Services Office</span>
                                <span class="admin-audit-browser__context-count">14</span>
                            </a>
                            <a href="#ds-audit-browser" class="admin-audit-browser__context">
                                <span class="dept-badge" data-dept="WST">WST</span>
                                <span class="admin-audit-browser__context-name">Waste Management Office</span>
                                <span class="admin-audit-browser__context-count">6</span>
                            </a>

                            <p class="admin-audit-browser__label">Automated</p>
                            <a href="#ds-audit-browser" class="admin-audit-browser__context">
                                <span class="admin-audit-browser__context-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                </span>
                                <span class="admin-audit-browser__context-name">Automated records</span>
                                <span class="admin-audit-browser__context-count">57 records</span>
                            </a>
                        </nav>

                        <div class="admin-audit-browser__roster">
                            <div class="admin-audit-browser__roster-head">
                                <span class="admin-audit-browser__roster-head-label">People</span>
                                <span class="admin-audit-browser__roster-head-label">Entries</span>
                            </div>

                            <section class="admin-audit-browser__group">
                                <p class="admin-audit-browser__group-head">
                                    <span class="admin-audit-browser__group-name">Engineering Office</span>
                                    <span class="admin-audit-browser__group-meta">18 people</span>
                                </p>
                                <div class="admin-audit-browser__group-list">
                                    <a href="#ds-audit-browser" class="admin-audit-browser__person">
                                        <span class="admin-audit-browser__avatar" aria-hidden="true">JD</span>
                                        <span class="admin-audit-browser__person-name">Juan dela Cruz</span>
                                        <span class="admin-audit-browser__person-role">Staff</span>
                                        <span class="admin-audit-browser__person-count">86</span>
                                    </a>
                                    <a href="#ds-audit-browser" class="admin-audit-browser__person is-selected" aria-current="true">
                                        <span class="admin-audit-browser__avatar" aria-hidden="true">AL</span>
                                        <span class="admin-audit-browser__person-name">Alize Lowe</span>
                                        <span class="admin-audit-browser__person-role">Staff</span>
                                        <span class="admin-audit-browser__person-count">95</span>
                                    </a>
                                    <a href="#ds-audit-browser" class="admin-audit-browser__person">
                                        <span class="admin-audit-browser__avatar" aria-hidden="true">KM</span>
                                        <span class="admin-audit-browser__person-name">Kristel Mendez</span>
                                        <span class="admin-audit-browser__person-role">Administrator</span>
                                        <span class="admin-audit-browser__person-count">17</span>
                                    </a>
                                </div>
                                <div class="admin-audit-browser__group-foot">
                                    <span class="admin-audit-browser__group-shown">Showing 3 of 18</span>
                                    <a href="#ds-audit-browser" class="admin-audit-browser__group-more">See all 3,010</a>
                                </div>
                            </section>

                            <section class="admin-audit-browser__group">
                                <p class="admin-audit-browser__group-head">
                                    <span class="admin-audit-browser__group-name">Residents</span>
                                    <span class="admin-audit-browser__group-meta">3,010 people</span>
                                </p>
                                <div class="admin-audit-browser__group-list">
                                    <a href="#ds-audit-browser" class="admin-audit-browser__person">
                                        <span class="admin-audit-browser__avatar" aria-hidden="true">AD</span>
                                        <span class="admin-audit-browser__person-name">Ana Dela Cruz</span>
                                        <span class="admin-audit-browser__person-count">6</span>
                                    </a>
                                    <a href="#ds-audit-browser" class="admin-audit-browser__person">
                                        <span class="admin-audit-browser__avatar" aria-hidden="true">PP</span>
                                        <span class="admin-audit-browser__person-name">Pedro Penduko</span>
                                        <span class="admin-audit-browser__person-count">8</span>
                                    </a>
                                </div>
                                <div class="admin-audit-browser__group-foot">
                                    <span class="admin-audit-browser__pager">
                                        <span class="admin-audit-browser__pager-link is-disabled" aria-disabled="true">Previous</span>
                                        <span class="admin-audit-browser__pager-state">Page 2 of 61</span>
                                        <a href="#ds-audit-browser" class="admin-audit-browser__pager-link">Next</a>
                                    </span>
                                    <a href="#ds-audit-browser" class="admin-audit-browser__group-more">Collapse</a>
                                </div>
                            </section>
                        </div>
                    </div>
                </details>
            </section>
        </div>
    </section>

    {{-- ═══ STATES ═══ --}}
    <section class="ds-section" aria-labelledby="ds-states">
        <h2 class="ds-h2" id="ds-states">Empty · no-results · error</h2>
        <div class="ds-grid-2">
            <div class="tr-card" style="padding: 32px 20px;">
                <div class="empty-state">
                    <span class="empty-state__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 6 8.38 8.38 0 0 1-3.8-.9L3 21l1.6-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 6-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></span>
                    <strong>No public complaints yet.</strong>
                    <p>Public reports appear here when residents choose to share them.</p>
                </div>
            </div>
            <div class="tr-card" style="padding: 32px 20px;">
                <div class="empty-state">
                    <span class="empty-state__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg></span>
                    <strong>No complaints match your filters.</strong>
                    <p>Try different filters or clear your selection.</p>
                    <button class="tr-button" type="button">Clear filters</button>
                </div>
            </div>
        </div>
        <div class="tr-card" style="padding: 32px 20px; margin-top: 16px;">
            <div class="empty-state">
                <span class="empty-state__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                <strong>Something went wrong</strong>
                <p>The public complaint register could not be loaded. It may be a connection problem.</p>
                <button class="tr-button" type="button" data-retry>Retry</button>
            </div>
        </div>
    </section>

    {{-- ═══ ERROR PAGES ═══ --}}
    <section class="ds-section" aria-labelledby="ds-errors">
        <h2 class="ds-h2" id="ds-errors">Error pages — standalone shell</h2>
        <p class="ds-note">One centered card, one <code>&lt;h1&gt;</code>, one gold primary action. Codes: 401 · 403 · 404 · 419 · 429 · 500 · 503 plus generic 4xx/5xx. The shell deliberately renders no app navbar or shared footer — both query the database, and this is the page that must still work when the database or the app is the thing that failed.</p>

        <div class="ds-frame" style="padding: 32px 16px;">
            <div class="tr-card error-card" style="margin: 0 auto;">
                <span class="error-icon" aria-hidden="true">
                    @include('errors.partials.icon', ['icon' => 'search'])
                </span>
                <p class="error-kicker">Error 404</p>
                <p class="error-title">Page not found</p>
                <p class="error-message">The page you are looking for does not exist, or it may have been moved or removed.</p>
                <div class="error-actions">
                    <a href="{{ url('/') }}" class="error-primary">Back to home</a>
                    <a href="{{ url('/') }}" class="tr-button">Go back</a>
                </div>
            </div>
        </div>
        <p class="ds-note" style="margin-top: 12px;">The secondary action is “Go back” (browser history) on 401/403/404/419 and “Try again” (reload) on 429/500/503. The error code, copy and locale come from <code>lang/{en,fil}/errors.php</code>.</p>
    </section>

    {{-- ═══ Z-INDEX CONTRACT ═══ --}}
    <section class="ds-section" aria-labelledby="ds-z">
        <h2 class="ds-h2" id="ds-z">The z-index contract</h2>
        <p class="ds-note">Why: Leaflet's panes reach z-index 1000 inside a map. Without <code>isolation:isolate</code> those numbers compete with the navbar and the map once covered the open mobile menu.</p>
        <table class="ds-table">
            <thead><tr><th>Layer</th><th>z-index</th><th>Rule</th></tr></thead>
            <tbody>
                <tr><td>any <code>.tr-map</code> wrapper</td><td><code>isolation: isolate; z-index: 0</code></td><td>Locks all Leaflet panes inside the map</td></tr>
                <tr><td><code>.mc-filters-panel</code> popover (≤640px)</td><td><code>500</code></td><td>Anchored under the Filters button (§5.28) — above content, below chrome</td></tr>
                <tr><td><code>.lp-sticky-cta</code></td><td><code>800</code></td><td>Landing sticky CTA, yields to the navbar</td></tr>
                <tr><td><code>#navbar</code></td><td><code>900</code></td><td>Below drawer, above page and maps</td></tr>
                <tr><td><code>#mobile-nav-drawer</code> backdrop</td><td><code>1000</code></td><td>Backdrop for the drawer</td></tr>
                <tr><td><code>#mobile-nav-drawer</code></td><td><code>1010</code></td><td>The drawer itself</td></tr>
                <tr><td>toast / modal</td><td><code>1100</code></td><td>Above everything, including drawers</td></tr>
                <tr><td>map overlays (status/hint)</td><td><code>inside the map</code></td><td>Never escape the isolated context</td></tr>
            </tbody>
        </table>
    </section>

    <section class="ds-section" aria-labelledby="ds-needspanel">
        <h2 class="ds-h2" id="ds-needspanel">Needs-information panel (resident) — §5.29</h2>
        <p class="ds-note">Static swatch: the one screen a ticket waiting on the resident renders. Amber progress-tint card; white question/answer body; gold submit. Shell C, right after the back link.</p>
        <div style="max-width: 640px; padding: 20px; background: var(--status-progress-bg); border: 1.5px solid rgba(180,83,9,0.4); border-radius: var(--radius-sm); box-shadow: var(--shadow-card);">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="flex:0 0 auto; width:44px; height:44px; border-radius:var(--radius-sm); background:var(--navy-900); color:var(--surface); display:inline-flex; align-items:center; justify-content:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <span style="min-width:0;"><span style="display:block; font-size:10px; font-weight:800; letter-spacing:0.14em; text-transform:uppercase; color:var(--status-progress-text);">WAITING ON YOU</span><strong style="display:block; font-size:20px; font-weight:700; color:var(--navy-900); margin-top:2px;">The department needs more information</strong></span>
            </div>
            <p style="margin:6px 0 14px; font-size:13.5px; line-height:1.55; color:var(--navy-900);">The office reviewing this complaint needs more from you before it can proceed. Answer the question below — photos help confirm the spot.</p>
            <blockquote style="margin:0 0 16px; padding:16px 18px; background:var(--surface); border:1px solid var(--line-soft); border-radius:var(--radius-sm);"><p style="margin:0 0 6px; font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--status-progress-text);">Asked by GSO · Nov 9, 2026 10:15 AM</p><p style="margin:0; font-size:14px; line-height:1.65; color:var(--navy-900);">The streetlight photo is too dark to read the pole number. Please send a closer shot of the pole.</p></blockquote>
            <p style="margin:0; font-size:12px; color:var(--status-progress-text); font-weight:700;">→ Your reply textarea + optional photos + gold “Send to the department”</p>
        </div>
    </section>

    {{-- ═══ Wireframes ═══ --}}
    <section class="ds-section" aria-labelledby="ds-wire">
        <h2 class="ds-h2" id="ds-wire">Shells</h2>
        <p class="ds-note">Three standard shells — see docs/design-system.md §4 for exact paddings and the banner grammar.</p>
        <div class="ds-wireframe">Shell A — banner + stat strip + content (list pages)
┌ banner ─────────────────────────────────────────────┐
│ h1                                                  │
│ privacy sentence · How this data is shown ▾          │
│ Updated Nov 8, 2026                                  │
└──────────────────────────────┬───────────────────────┘
      ┌ stat ┐ ┌ stat ┐ ┌ stat ┐ ┌ stat ┐   ← overlap −32px
      └──────┘ └──────┘ └──────┘ └──────┘
      ┌ [2/3 map card]  ┌ [1/3 side] ┐
      │ h2      Low→High legend      │
      └──────────────────────────────┘
      Public complaints ……… Showing N of N
      ┌ filter bar ───────────────────────┐
      └───────────────────────────────────┘
      ┌ list card ────────────────────────┐
      └───────────────────────────────────┘</div>

        <div class="ds-wireframe" style="margin-top:16px">Shell B — resident list (My complaints)
┌ banner: h1 + one line + ONE CTA (suppressed when the navbar shows it) ┐
│ stat strip: Total · Open · Resolved · Avg. resolution time           │
└──────────────────────────────────────────────────────────────────────┘
filter bar: search (icon) · dept/category/sort + status chips with counts
list card: .report-row rows + resident pills (needs-response, visibility)
pagination: Showing 1–10 of N</div>

        <div class="ds-wireframe" style="margin-top:16px">Shell C — detail page
back link                                      ← navy text, 44px
h1 card: ticket id mono navy + status pill (+ verified pill)
h1 = the report's title — never the description
stepper: 5 segments, current = gold
grid: [evidence] [timeline]                   ← single column on mobile
side: department / category / alias / privacy</div>
    </section>

    <footer style="border-top: 1px solid var(--line-soft); padding-top: 16px; display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <p style="margin: 0; font-size: 13px; color: var(--muted);">Update <code>docs/design-system.md</code> §12 with every change to this page.</p>
        <a href="{{ route('transparency') }}" style="font-size: 13px; color: var(--navy-700); font-weight: 600;">View the reference implementation →</a>
    </footer>
</div>
</div>
@endif
