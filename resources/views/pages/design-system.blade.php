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

        /* Password visibility toggle demo — the auth (dark) surface (§5.41) */
        .ds-password-demo { max-width: 360px; background: #142D4F; border-radius: 12px; padding: 20px; }
        .ds-password-demo__label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.55); margin-bottom: 8px; font-family: var(--font-sans); }
        .ds-password-demo__icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(201,168,76,.85); pointer-events: none; display: inline-flex; }
        .ds-password-demo__input { background: #1F3D60; border: 1px solid rgba(255,255,255,.22); border-radius: 4px; padding: 12px 14px 12px 42px; font-family: var(--font-sans); font-size: 13px; color: #ffffff; caret-color: #ffffff; outline: none; transition: border-color .2s, box-shadow .2s; }
        .ds-password-demo__input:focus { border-color: #D4A947; box-shadow: 0 0 0 3px rgba(212,169,71,.2); }

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
        ['ENG', '#B45309', 'rgba(180,83,9,.16)'], ['HRMO', '#1D4ED8', 'rgba(29,78,216,.16)'],
        ['MENRO', '#15803D', 'rgba(21,128,61,.16)'], ['BPLO', '#0E7490', 'rgba(14,116,144,.16)'],
        ['OM', '#A16207', 'rgba(161,98,7,.16)'],
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

    {{-- ═══ TOAST (§5.48) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-toast">
        <h2 class="ds-h2" id="ds-toast">Toast — §5.48</h2>
        <p class="ds-note">A status that says “done”, then gets out of the way. Without JavaScript it is the inline notice below; with JavaScript it floats under the navbar, fades in, and dismisses itself after about 4 seconds. Hovering or focusing it pauses the clock, the close button and <kbd>Esc</kbd> dismiss it, and errors stay until dismissed. Markup: <code>x-toast</code>; behaviour: <code>resources/js/toast.js</code>.</p>
        <div style="display:grid; gap:12px; max-width:520px;">
            <x-toast :message="__('feedback.sent_rating')" variant="success" preview />
            <x-toast :message="__('feedback.errors.suggestion_limit')" variant="error" preview />
        </div>
        <p class="ds-note" style="margin-top:12px">On <code>/feedback</code> the success toast is the thank-you. It lives at <code>--z-overlay</code> (1100), so it sits above the navbar and the drawer; on a phone it spans the width with 16px gutters.</p>
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
                            <option>All departments</option><option>Engineering Office</option><option>Mayor's Office</option>
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


    {{-- ═══ PASSWORD VISIBILITY TOGGLE (§5.41) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-password-toggle">
        <h2 class="ds-h2" id="ds-password-toggle">Password visibility toggle — §5.41</h2>
        <p class="ds-note">Auth (dark) password fields only. A 44px button at the right edge of the field, invisible until the input has text — then it springs in. It flips the input between <code>type="password"</code> and <code>type="text"</code>; two stacked feather glyphs (<code>eye</code>/<code>eye-off</code>) cross-fade off <code>aria-pressed</code>, and the accessible name flips with them (<code>auth_ui.common.show_password</code> / <code>auth_ui.common.hide_password</code>, en+fil). Markup comes from <code>auth.partials.password-toggle</code>; behaviour from <code>initializePasswordToggles()</code> in <code>app.js</code>. The input keeps its left icon; <code>.field-wrap:has(.field-toggle) .field-input</code> reserves the right gutter so text never runs under the button. The settings page keeps its light “Show”/“Hide” text button — different surface, not merged. Try it (type into the field):</p>
        <div class="ds-password-demo">
            <label class="ds-password-demo__label" for="ds-password-demo">Password</label>
            <div class="field-wrap" style="position:relative;">
                <span class="ds-password-demo__icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                <input id="ds-password-demo" type="password" class="field-input ds-password-demo__input" value="" placeholder="Type to reveal the eye…" autocomplete="off" spellcheck="false" style="width:100%; box-sizing:border-box;">
                @include('auth.partials.password-toggle', ['target' => 'ds-password-demo'])
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
                    <span class="dept-badge" data-dept="HRMO">HRMO</span>
                    <span class="dept-badge" data-dept="MENRO">MENRO</span>
                    <span class="dept-badge" data-dept="BPLO">BPLO</span>
                    <span class="dept-badge" data-dept="OM">OM</span>
                    <span class="dept-badge" data-dept="UNKNOWN-XX">UNKNOWN</span>
                </div>

                <h3 class="ds-h3">Account identity (navbar, §5.31)</h3>
                <p class="ds-note">Staff context shown in the fixed top bar (desktop + mobile). On navy chrome; uses dept tokens for staff. No new colours.</p>
                <div style="display:flex; flex-wrap:wrap; gap:12px 16px; align-items:center; padding:12px 16px; background:var(--navy-800); border:1px solid color-mix(in srgb, var(--gold-500) 30%, transparent); border-radius:var(--radius-sm);">
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
                                <span class="dept-badge" data-dept="MENRO">MENRO</span>
                                <span class="admin-audit-browser__context-name">Municipal Environment and Natural Resource Office</span>
                                <span class="admin-audit-browser__context-count">14</span>
                            </a>
                            <a href="#ds-audit-browser" class="admin-audit-browser__context">
                                <span class="dept-badge" data-dept="HRMO">HRMO</span>
                                <span class="admin-audit-browser__context-name">Human Resource Management Office</span>
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
            <blockquote style="margin:0 0 16px; padding:16px 18px; background:var(--surface); border:1px solid var(--line-soft); border-radius:var(--radius-sm);"><p style="margin:0 0 6px; font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--status-progress-text);">Asked by MENRO · Nov 9, 2026 10:15 AM</p><p style="margin:0; font-size:14px; line-height:1.65; color:var(--navy-900);">The streetlight photo is too dark to read the pole number. Please send a closer shot of the pole.</p></blockquote>
            <p style="margin:0; font-size:12px; color:var(--status-progress-text); font-weight:700;">→ Your reply textarea + optional photos + gold “Send to the department”</p>
        </div>
    </section>

    {{-- ═══ PROOF CAPTURE (staff) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-proof">
        <h2 class="ds-h2" id="ds-proof">Proof capture (staff) — §5.36</h2>
        <p class="ds-note">
            The staff photo-proof picker, live. It states the office's rule <em>before</em>
            the staff member chooses a status: pick a status that needs a photo and the note
            turns to the amber action-needed family and names it. The server refuses the
            update without the photo either way — this only moves the refusal earlier.
            Change the status below to see it switch.
        </p>
        <div style="max-width: 520px;">
            <div class="tr-select" style="max-width: 320px; margin-bottom: 16px;">
                <select id="ds-proof-status" aria-label="Status update">
                    <option value="In Progress" selected>In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <x-proof-capture status-select="ds-proof-status" initial-status="In Progress" />
            <p class="ds-note" style="margin-top: 12px;">
                Markup contract: <code>[data-proof-capture]</code> · <code>[data-proof-gallery]</code> ·
                <code>[data-proof-camera]</code> · <code>[data-proof-previews]</code> ·
                <code>[data-proof-count]</code> · <code>[data-proof-error]</code> ·
                <code>[data-proof-trigger]</code> · <code>[data-proof-note]</code>.
                Controller: <code>resources/js/proof-capture.js</code>. Staff-only — it binds to
                nothing on a resident page.
            </p>
        </div>
    </section>

    {{-- ═══ LIVE TICKET NOTICE ═══ --}}
    <section class="ds-section" aria-labelledby="ds-live-ticket">
        <h2 class="ds-h2" id="ds-live-ticket">Live ticket notice — §5.39</h2>
        <p class="ds-note">
            Static swatch: the notice a staff ticket raises when another hand moves the case
            while the page is open. Amber action-needed, outside the form, with a reload that
            works without JavaScript and a dismiss for this one change. The status pill updates
            in place; the form and everything already typed are never rewritten.
        </p>
        <div style="max-width: 720px;">
            <div class="ticket-live-note" style="margin-bottom: 0;">
                <span class="ticket-live-note__dot" aria-hidden="true"></span>
                <p class="ticket-live-note__text">Maria Dela Cruz moved this complaint from Under Review to In Progress.</p>
                <div class="ticket-live-note__actions">
                    <a class="ticket-live-note__reload" href="#ds-live-ticket">Reload to see the latest</a>
                    <button type="button" class="ticket-live-note__dismiss">Dismiss</button>
                </div>
            </div>
            <p class="ds-note" style="margin-top: 12px;">
                Markup contract: <code>[data-live-ticket]</code> ·
                <code>[data-live-ticket-notice]</code> · <code>[data-live-ticket-message]</code> ·
                <code>[data-live-ticket-refresh]</code> · <code>[data-live-ticket-dismiss]</code> ·
                <code>[data-ticket-status-pill]</code> · <code>[data-ticket-status-label]</code>.
                Controller: <code>resources/js/live-ticket.js</code>. Staff- and admin-only — it
                binds to nothing on a resident page.
            </p>
        </div>
    </section>

    {{-- ═══ STAFF ACTION PANELS ═══ --}}
    <section class="ds-section" aria-labelledby="ds-staff-actions">
        <h2 class="ds-h2" id="ds-staff-actions">Staff action panels — §5.40</h2>
        <p class="ds-note">
            Static swatch: the verification follow-up card a verifier sees on a ticket that is
            still being settled. The ask form is replaced by a waiting or answered state while a
            request is open; the delegation section appears only when another eligible verifier
            exists. Fields are the shared <code>.staff-field</code> recipe.
        </p>

        <div style="max-width: 620px; padding: 20px; background: var(--surface); border: 1px solid var(--line-soft); border-radius: var(--radius-lg); box-shadow: var(--shadow-card);">
            <h3 class="staff-action-panel__section-title">Ask the resident for more evidence</h3>
            <p class="staff-action-panel__hint">Say exactly what is missing. The resident sees the question and can reply with text and photos. This uses round 1 of 2.</p>

            <div class="staff-field">
                <label for="ds-staff-question">What is missing?</label>
                <textarea id="ds-staff-question" rows="3">The streetlight photo is too dark to read the pole number. Please send a closer shot of the pole.</textarea>
                <p class="staff-field__hint">At least 15 characters. A vague ask is refused by the server.</p>
            </div>

            <button type="button" class="staff-action-panel__submit">Ask the resident</button>
        </div>

        <div class="staff-action-panel__state staff-action-panel__state--waiting" style="max-width: 620px; margin-top: 16px;">
            <strong>Waiting on the resident</strong>
            <p>They have been asked for more evidence. Their reply will appear here, and the ticket stays with them until it does.</p>
        </div>

        <div class="moderation-panel moderation-panel--light" style="max-width: 620px; margin-top: 16px;">
            <h3 class="moderation-panel__title">Public presentation</h3>
            <p class="moderation-panel__lede">You can withhold this report or clean up sensitive parts of it. You cannot publish or unpublish it.</p>
            <p class="moderation-panel__consent">
                <strong>The resident published this.</strong>
                It appears on the transparency page under their display alias. Moderating below can withhold it, but nothing here can change that choice.
            </p>
        </div>

        <p class="ds-note" style="margin-top: 12px;">
            Markup contract: <code>.staff-action-panel__section</code> ·
            <code>.staff-action-panel__state</code> ·
            <code>.staff-action-panel__quote</code> ·
            <code>.staff-field</code> ·
            <code>.moderation-panel--light</code>.
            Moderation markup lives once in
            <code>resources/views/components/moderation-panel.blade.php</code> and is shared by
            staff and admin. The internal note is a <code>.staff-field</code> inside the official
            response form, never a separate form.
        </p>
    </section>

    {{-- ═══ STAFF LOCATION CARD (§5.42) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-location-card">
        <h2 class="ds-h2" id="ds-location-card">Staff location card &amp; directions — §5.42</h2>
        <p class="ds-note">
            Static swatch: the location card on a staff ticket. The map shows the place; the
            footer carries the one action that hands the pin to Google Maps as the destination,
            starting from the device's current location. The exact coordinate pair is never
            printed — it lives only in the map's data attributes and the directions href.
        </p>

        <div class="tr-card location-card" style="max-width: 620px; overflow: hidden; padding: 0; border: 1px solid var(--line-soft); border-radius: var(--radius-lg); background: var(--surface); box-shadow: var(--shadow-card);">
            <div style="display: flex; align-items: center; gap: 10px; padding: 16px 20px; border-bottom: 1px solid var(--line-soft);">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex-shrink: 0; border-radius: 6px; background: var(--navy-50); color: var(--navy-700);" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--navy-900);">Complaint location</h3>
            </div>
            <div role="region" aria-label="Map showing the complaint's pinned location" style="display: flex; align-items: center; justify-content: center; gap: 8px; height: 280px; background: var(--navy-50); color: var(--muted); font-size: 13px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Leaflet map (280px) — static demo
            </div>
            <div class="location-card__footer">
                <span class="location-card__meta">Barangay: <strong>Barangay I (Hilahod)</strong></span>
                <a class="tr-button location-card__directions" href="https://www.google.com/maps/dir/?api=1&amp;destination=14.112800,122.955500" target="_blank" rel="noopener noreferrer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                    <span>Get directions</span>
                </a>
            </div>
        </div>

        <div class="tr-card location-card" style="max-width: 620px; margin-top: 16px; overflow: hidden; padding: 0; border: 1px solid var(--line-soft); border-radius: var(--radius-lg); background: var(--surface); box-shadow: var(--shadow-card);">
            <div class="location-card__empty">
                <span class="location-card__empty-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <strong>No exact pin on this report</strong>
                <p>The resident did not drop a pin and this category does not require one. There is no destination to hand to Google Maps.</p>
            </div>
            <div class="location-card__footer">
                <span class="location-card__meta">Barangay: <strong>Barangay IV</strong></span>
            </div>
        </div>

        <p class="ds-note" style="margin-top: 12px;">
            Markup contract: <code>.location-card</code> · <code>.map-container</code>
            (<code>data-latitude</code> · <code>data-longitude</code> ·
            <code>data-map-unavailable</code>) · <code>.location-card__footer</code> ·
            <code>.location-card__meta</code> · <code>.location-card__directions</code> ·
            <code>.location-card__empty</code>. States: pin present → map + directions; no
            pin → the card still renders with the empty state and no action; map library
            unavailable → the map says so and directions stays usable; no barangay → the
            footer shows only the action.
        </p>
    </section>

    {{-- ═══ DATE FIELD (§5.44) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-date-field">
        <h2 class="ds-h2" id="ds-date-field">Date field — §5.44</h2>
        <p class="ds-note">
            The field an office fills in when it says when it expects to carry on with a
            complaint. The visible field is a text input masked to dd/mm/yyyy — never the
            browser's locale, which would show 12/15/2026 to one office and 15/12/2026 to
            another — and the calendar beside it opens the OS picker, which on a phone is the
            wheel picker the LGU already knows. A date that does not exist is refused rather
            than rolled over.
        </p>

        <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; max-width: 760px;">
            <div class="moderation-panel moderation-panel--light" style="flex: 1 1 320px; max-width: 420px;">
                <div class="moderation-panel__header">
                    <h3 class="moderation-panel__title">Pause the work</h3>
                    <p class="moderation-panel__lede">Some complaints cannot be finished today for reasons that are not the office's doing.</p>
                </div>

                <x-date-field
                    name="ds-date-field-demo"
                    id="ds-date-field-demo"
                    label="Expected to carry on by"
                    value="2026-12-15"
                    hint="The resident sees this date, so they know when to expect an update. Leave it empty if the office cannot say yet."
                />

                <button type="button" class="moderation-panel__submit">Put the work on hold</button>
            </div>

            <div class="moderation-panel moderation-panel--light" style="flex: 1 1 260px; max-width: 320px;">
                <div class="moderation-panel__header">
                    <h3 class="moderation-panel__title">Empty, and not yet a date</h3>
                    <p class="moderation-panel__lede">The field before anything is typed, and what a date that does not exist looks like.</p>
                </div>

                <x-date-field
                    name="ds-date-field-empty"
                    id="ds-date-field-empty"
                    label="Expected to carry on by"
                    hint="Leave it empty if the office cannot say yet."
                />

                <div class="moderation-field date-field date-field--invalid" style="margin-top: 14px;">
                    <label for="ds-date-field-invalid">Expected to carry on by</label>
                    <div class="date-field__control">
                        <input type="text" id="ds-date-field-invalid" class="date-field__text" inputmode="numeric" autocomplete="off" placeholder="dd/mm/yyyy" maxlength="10" value="31/02/2026" aria-invalid="true" aria-describedby="ds-date-field-invalid-hint">
                        <input type="date" name="ds-date-field-invalid-native" value="" class="date-field__native" tabindex="-1" aria-hidden="true">
                        <button type="button" class="date-field__button" aria-label="Choose a date">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </button>
                        <button type="button" class="date-field__clear" aria-label="Clear the date">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                    <p class="moderation-panel__hint" id="ds-date-field-invalid-hint">The resident sees this date, so they know when to expect an update.</p>
                    <p class="date-field__error">That is not a date. Use dd/mm/yyyy.</p>
                </div>
            </div>
        </div>

        <p class="ds-note" style="margin-top: 12px;">
            Markup contract: <code>.date-field</code> · <code>.date-field__control</code> ·
            <code>.date-field__text</code> · <code>.date-field__native</code> ·
            <code>.date-field__button</code> · <code>.date-field__clear</code> ·
            <code>.date-field__optional</code> · <code>.date-field__error</code>, with
            <code>data-date-field</code> on the wrapper, <code>data-date-field-display</code> on the
            visible input, <code>data-date-field-native</code> on the native one (this is the
            field the form posts), <code>data-date-field-open</code> and
            <code>data-date-field-clear</code> on the buttons. The visible input is a plain
            <code>input[type="text"]</code> on purpose, so it inherits whatever the surface
            gives a text field. Tokens: <code>--error-bg</code>, <code>--error-text</code>,
            <code>--status-rejected-dot</code> — no new colours.
        </p>
    </section>

    {{-- ═══ REASON CHIPS & LABEL CHIPS (§5.45) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-reason-chips">
        <h2 class="ds-h2" id="ds-reason-chips">Reason chips &amp; label chips — §5.45</h2>
        <p class="ds-note">
            The controls the hold panel is built from. Seven short reasons do not belong in a
            dropdown on a phone: the chips put every one of them on screen, so choosing is one tap
            and the office can see that "another reason" exists. The label chip says where a piece
            of text goes, which two same-looking textareas otherwise do not.
        </p>

        <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start;">
            <div class="moderation-panel moderation-panel--light" style="flex: 1 1 320px; max-width: 420px;">
                <div class="moderation-panel__header">
                    <h3 class="moderation-panel__title">Pause the work</h3>
                    <p class="moderation-panel__lede">Some complaints cannot be finished today for reasons that are not the office's doing.</p>
                </div>

                <div class="moderation-field">
                    <span class="moderation-field__label">Why is the work on hold?</span>
                    <fieldset class="reason-chips">
                        <legend class="sr-only">Why is the work on hold?</legend>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="funds" data-reason-chip><span class="reason-chip__label">Waiting on funds</span></label>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="materials" data-reason-chip checked><span class="reason-chip__label">Waiting on materials</span></label>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="procurement" data-reason-chip><span class="reason-chip__label">Waiting on procurement</span></label>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="budget" data-reason-chip><span class="reason-chip__label">Waiting on a budget decision</span></label>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="office" data-reason-chip><span class="reason-chip__label">Waiting on another office</span></label>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="weather" data-reason-chip><span class="reason-chip__label">Waiting on the weather</span></label>
                        <label class="reason-chip"><input type="radio" name="ds-reason" value="other" data-reason-chip><span class="reason-chip__label">Another reason</span></label>
                    </fieldset>
                    <p class="moderation-panel__hint">The reason is recorded for the administrator. The resident reads your explanation below.</p>
                </div>

                <div class="moderation-field">
                    <label for="ds-label-chip-note">
                        What the resident will read
                        <span class="label-chip label-chip--public">Resident sees this</span>
                    </label>
                    <textarea id="ds-label-chip-note" rows="3" placeholder="The asphalt for this stretch of road is not in stock; the schedule is for next quarter."></textarea>
                    <p class="moderation-panel__hint">The resident reads this, and so does the public register. Keep out of it anything that identifies a person.</p>
                </div>

                <div class="moderation-field">
                    <label for="ds-label-chip-internal">
                        Internal note
                        <span class="label-chip label-chip--internal">Office only</span>
                    </label>
                    <textarea id="ds-label-chip-internal" rows="2" placeholder="Only the office sees this."></textarea>
                </div>
            </div>

            <div style="flex: 1 1 220px; max-width: 280px;">
                <h3 class="ds-h3">Label chips</h3>
                <p class="ds-note" style="margin-bottom: 10px;">On a label, where the eye already is.</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <span class="label-chip label-chip--public">Resident sees this</span>
                    <span class="label-chip label-chip--internal">Office only</span>
                    <span class="label-chip label-chip--internal">Optional</span>
                </div>
            </div>
        </div>

        <p class="ds-note" style="margin-top: 12px;">
            Markup contract: <code>.reason-chips</code> (a fieldset of radios, each in a
            <code>.reason-chip</code> label) · <code>.reason-chip__label</code> ·
            <code>.label-chip</code> · <code>.label-chip--public</code> ·
            <code>.label-chip--internal</code> · <code>.moderation-field__label</code> for a
            label that points at nothing. The radios stay in the form and in the tab order; only
            their box is hidden, and the chip carries the focus ring.
        </p>
    </section>

    {{-- ═══ DEPARTMENT ANALYTICS (§5.43) ═══ --}}
    <section class="ds-section" aria-labelledby="ds-analytics">
        <h2 class="ds-h2" id="ds-analytics">Department analytics — §5.43</h2>
        <p class="ds-note">
            Static swatch: the shared vocabulary behind the staff department report and the
            administrator comparison. The period bar, stat strip, delta, trend, donut, bars,
            tables, activity feed and fairness note are the same components on every surface.
            Direction in a delta is context — the arrow never turns red or green. The
            comparison is alphabetical and never ranked; the fairness note sits above the
            table, not in a tooltip.
        </p>

        <div class="an-page" data-an-preview style="max-width: 940px; padding: 0;">
            <section class="an-period" aria-labelledby="ds-an-period-label">
                <div class="an-period__top">
                    <div>
                        <h3 class="an-period__title" id="ds-an-period-label">{{ __('analytics.period.label') }}</h3>
                        <p class="an-period__range">
                            Nov 1, 2026 to Nov 30, 2026
                            <span class="an-period__previous">Compared with Oct 1, 2026 to Oct 31, 2026</span>
                        </p>
                    </div>
                    <nav class="an-period__nav" aria-label="{{ __('analytics.period.label') }}">
                        <span class="an-period__chip is-active" aria-current="true">{{ __('analytics.period.month') }}</span>
                        <span class="an-period__chip">{{ __('analytics.period.7d') }}</span>
                        <span class="an-period__chip">{{ __('analytics.period.30d') }}</span>
                        <span class="an-period__chip">{{ __('analytics.period.90d') }}</span>
                        <span class="an-period__chip">{{ __('analytics.period.ytd') }}</span>
                    </nav>
                </div>
                <div class="an-period__custom">
                    <label class="an-period__field">
                        <span>{{ __('analytics.period.from') }}</span>
                        <input type="date" value="2026-11-01">
                    </label>
                    <label class="an-period__field">
                        <span>{{ __('analytics.period.to') }}</span>
                        <input type="date" value="2026-11-30">
                    </label>
                    <button type="button" class="an-button an-button--secondary an-button--compact">{{ __('analytics.period.apply') }}</button>
                </div>
            </section>

            <div class="an-stats">
                <article class="an-stat">
                    <span class="an-stat__label">{{ __('analytics.stats.filed') }}</span>
                    <strong class="an-stat__value">128</strong>
                    <span class="an-stat__meta">{{ __('analytics.stats.filed_meta') }}</span>
                    <span class="an-delta an-delta--up">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                        <span>+12%</span>
                        <span class="an-delta__label">{{ __('analytics.delta.label') }}</span>
                    </span>
                </article>
                <article class="an-stat">
                    <span class="an-stat__label">{{ __('analytics.stats.resolved') }}</span>
                    <strong class="an-stat__value">94</strong>
                    <span class="an-stat__meta">{{ __('analytics.stats.resolved_meta') }}</span>
                    <span class="an-delta an-delta--down">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                        <span>−4%</span>
                        <span class="an-delta__label">{{ __('analytics.delta.label') }}</span>
                    </span>
                </article>
                <article class="an-stat">
                    <span class="an-stat__label">{{ __('analytics.stats.open') }}</span>
                    <strong class="an-stat__value">21</strong>
                    <span class="an-stat__meta">{{ __('analytics.stats.open_meta', ['assigned' => '17', 'unassigned' => '4']) }}</span>
                </article>
                <article class="an-stat an-stat--attention">
                    <span class="an-stat__label">{{ __('analytics.stats.overdue') }}</span>
                    <strong class="an-stat__value">3</strong>
                    <span class="an-stat__meta">{{ __('analytics.stats.overdue_meta') }}</span>
                </article>
                <article class="an-stat">
                    <span class="an-stat__label">{{ __('analytics.stats.avg_days') }}</span>
                    <strong class="an-stat__value">6.4 {{ __('analytics.days_short') }}</strong>
                    <span class="an-stat__meta">{{ __('analytics.stats.avg_meta') }}</span>
                    <span class="an-delta an-delta--down">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                        <span>−1.2 {{ __('analytics.days_short') }}</span>
                        <span class="an-delta__label">{{ __('analytics.delta.label') }}</span>
                    </span>
                </article>
                <article class="an-stat an-stat--calm">
                    <span class="an-stat__label">{{ __('analytics.stats.on_time') }}</span>
                    <strong class="an-stat__value">82%</strong>
                    <span class="an-stat__meta">{{ __('analytics.stats.on_time_meta', ['on_time' => '74', 'base' => '90']) }}</span>
                </article>
            </div>

            <div class="an-grid an-grid--trend">
                <section class="an-card an-card--wide" aria-labelledby="ds-an-trend">
                    <div class="an-card__head">
                        <div>
                            <h3 class="an-card__title" id="ds-an-trend">{{ __('analytics.sections.trend') }}</h3>
                            <p class="an-card__sub">{{ __('analytics.sections.trend_sub', ['granularity' => __('analytics.granularity.weekly')]) }}</p>
                        </div>
                    </div>
                    @include('analytics.partials.trend-chart', ['trend' => [
                        'granularity' => 'weekly',
                        'max' => 32,
                        'rows' => [
                            ['label' => 'Nov 1', 'filed' => 18, 'resolved' => 12],
                            ['label' => 'Nov 8', 'filed' => 24, 'resolved' => 19],
                            ['label' => 'Nov 15', 'filed' => 31, 'resolved' => 22],
                            ['label' => 'Nov 22', 'filed' => 27, 'resolved' => 25],
                            ['label' => 'Nov 29', 'filed' => 16, 'resolved' => 21],
                        ],
                    ]])
                </section>

                <section class="an-card" aria-labelledby="ds-an-status">
                    <div class="an-card__head">
                        <div>
                            <h3 class="an-card__title" id="ds-an-status">{{ __('analytics.sections.status_mix') }}</h3>
                            <p class="an-card__sub">{{ __('analytics.sections.status_mix_sub') }}</p>
                        </div>
                    </div>
                    @include('analytics.partials.status-mix', ['mix' => [
                        'total' => 128,
                        'segments' => [
                            ['key' => 'submitted', 'label' => __('statuses.complaint_status.submitted'), 'count' => 8, 'percent' => 6.3],
                            ['key' => 'under_review', 'label' => __('statuses.complaint_status.under_review'), 'count' => 14, 'percent' => 10.9],
                            ['key' => 'in_progress', 'label' => __('statuses.complaint_status.in_progress'), 'count' => 22, 'percent' => 17.2],
                            ['key' => 'resolved', 'label' => __('statuses.complaint_status.resolved'), 'count' => 58, 'percent' => 45.3],
                            ['key' => 'rejected', 'label' => __('statuses.complaint_status.rejected'), 'count' => 6, 'percent' => 4.7],
                            ['key' => 'closed', 'label' => __('statuses.complaint_status.closed'), 'count' => 20, 'percent' => 15.6],
                        ],
                    ]])
                </section>
            </div>

            <div class="an-grid an-grid--two">
                <section class="an-card" aria-labelledby="ds-an-categories">
                    <div class="an-card__head">
                        <div>
                            <h3 class="an-card__title" id="ds-an-categories">{{ __('analytics.sections.categories') }}</h3>
                            <p class="an-card__sub">{{ __('analytics.sections.categories_sub') }}</p>
                        </div>
                    </div>
                    @include('analytics.partials.bars', [
                        'emptyMessage' => __('analytics.sections.no_reports'),
                        'items' => [
                            ['key' => 'road_damage', 'label' => \App\Services\DepartmentRouter::categoryLabels()['road_damage'] ?? 'Road damage', 'count' => 34, 'percent' => 26.6],
                            ['key' => 'garbage', 'label' => \App\Services\DepartmentRouter::categoryLabels()['garbage'] ?? 'Garbage collection', 'count' => 28, 'percent' => 21.9],
                            ['key' => 'streetlight', 'label' => \App\Services\DepartmentRouter::categoryLabels()['streetlight'] ?? 'Streetlight', 'count' => 17, 'percent' => 13.3],
                        ],
                    ])
                </section>
            </div>

            @include('analytics.partials.fairness')

            <p class="an-table-note">{{ __('analytics.sections.staff_breakdown_sub') }}</p>
        </div>

        <p class="ds-note" style="margin-top: 12px;">
            Markup contract: <code>.an-page</code> (with <code>[data-analytics]</code> when live)
            · <code>.an-period</code> · <code>.an-stats</code> / <code>.an-stat</code> ·
            <code>.an-grid</code> · <code>.an-card</code> · <code>.an-chart</code> ·
            <code>.an-donut</code> · <code>.an-bars</code> · <code>.an-mini-grid</code> ·
            <code>.an-table--workload</code> / <code>.an-table--comparison</code> ·
            <code>.an-feed</code> · <code>.an-fairness</code> ·
            <code>.an-inspect</code>. The live chart, donut and bars expose values on hover,
            tap and keyboard focus; the chart uses focusable hit bands and the donut and bars
            use real buttons. The period bar and content are
            swapped by <code>resources/js/analytics.js</code> from
            <code>[data-analytics-period]</code> / <code>[data-analytics-content]</code>; the
            swatch above uses <code>[data-an-preview]</code> to exercise the same interaction
            handlers without a live endpoint. Without JavaScript the chips are ordinary links
            and the custom range is a normal GET — the report still works, it just reloads.
            Live surfaces: <code>/admin/analytics</code> and
            <code>/admin/analytics/departments/{department}</code>. The staff-facing
            <code>/staff/analytics</code> report is parked.
        </p>
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
