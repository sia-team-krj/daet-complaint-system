@extends($mainLayout)

@section('title', 'Public Transparency — Daet Listens')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .transparency-root {
            --tp-navy: #0b1f3a;
            --tp-navy-mid: #12294d;
            --tp-gold: #c9a84c;
            --tp-gold-light: #e2c06a;
            --tp-cream: #f5f0e8;
            --tp-cream-dark: #ede7d9;
            --tp-ink: #172b49;
            --tp-muted: #64748b;
            --tp-green: #2f9e68;
            --tp-blue: #4c83c3;
            --tp-amber: #a66c17;
            min-height: 100vh;
            overflow: hidden;
            background: var(--tp-cream);
            color: var(--tp-ink);
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        }

        .transparency-root *,
        .transparency-root *::before,
        .transparency-root *::after {
            box-sizing: border-box;
        }

        .transparency-root h1,
        .transparency-root h2,
        .transparency-root p {
            margin-top: 0;
        }

        .transparency-container {
            width: min(1280px, calc(100% - 80px));
            margin: 0 auto;
        }

        /* The fixed navbar owns the top stacking layer. The map is isolated
           below it so Leaflet panes and controls cannot rise over the bar. */
        .transparency-header {
            position: relative;
            overflow: hidden;
            padding: 132px 40px 54px;
            background: var(--tp-navy);
            color: #fff;
        }

        .transparency-header::before {
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 42px,
                rgba(201, 168, 76, 0.035) 42px,
                rgba(201, 168, 76, 0.035) 43px
            );
            content: "";
            pointer-events: none;
        }

        .transparency-header::after {
            position: absolute;
            top: -260px;
            right: -140px;
            width: 620px;
            height: 620px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.1), transparent 68%);
            content: "";
            pointer-events: none;
        }

        .transparency-header__inner {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 48px;
        }

        .transparency-header__copy {
            max-width: 760px;
        }

        .transparency-kicker {
            display: block;
            margin-bottom: 16px;
            color: var(--tp-gold-light);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .transparency-section .transparency-kicker {
            color: #9a741e;
        }

        .transparency-header h1 {
            max-width: 720px;
            margin-bottom: 18px;
            color: #fff;
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(46px, 6vw, 78px);
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 0.96;
        }

        .transparency-header h1 em {
            color: var(--tp-gold-light);
            font-style: italic;
        }

        .transparency-header__lede {
            max-width: 650px;
            margin-bottom: 22px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
            font-weight: 300;
            line-height: 1.75;
        }

        .transparency-header__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 18px;
            color: rgba(255, 255, 255, 0.54);
            font-size: 11px;
        }

        .transparency-header__meta span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .transparency-header__meta svg {
            color: var(--tp-gold);
        }

        .transparency-header__actions {
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            gap: 9px;
            min-width: 170px;
        }

        .transparency-button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 18px;
            border: 1px solid transparent;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            line-height: 1.2;
            text-decoration: none;
            text-transform: uppercase;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .transparency-button:hover {
            transform: translateY(-2px);
        }

        .transparency-button--gold {
            background: linear-gradient(135deg, var(--tp-gold), var(--tp-gold-light));
            box-shadow: 0 8px 24px rgba(201, 168, 76, 0.2);
            color: var(--tp-navy);
        }

        .transparency-button--quiet {
            border-color: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
        }

        .transparency-button--quiet:hover {
            border-color: rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        /* ── Shared sections ───────────────────────────────────────────── */
        .transparency-section {
            scroll-margin-top: 84px;
            padding: 72px 40px 84px;
        }

        .transparency-section--map {
            background: var(--tp-cream-dark);
        }

        .transparency-section--table {
            background: #fff;
        }

        .transparency-section__head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 28px;
            margin-bottom: 28px;
        }

        .transparency-section__head h2 {
            max-width: 680px;
            margin-bottom: 10px;
            color: var(--tp-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(34px, 4vw, 52px);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 0.98;
        }

        .transparency-section__head p {
            max-width: 670px;
            margin-bottom: 0;
            color: var(--tp-muted);
            font-size: 13px;
            font-weight: 300;
            line-height: 1.7;
        }

        .transparency-count {
            flex: 0 0 auto;
            color: #9a741e;
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 38px;
            font-weight: 700;
            line-height: 1;
        }

        /* ── Heatmap ───────────────────────────────────────────────────── */
        .transparency-map-card {
            overflow: hidden;
            border: 1px solid rgba(201, 168, 76, 0.28);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 34px rgba(11, 31, 58, 0.06);
        }

        .transparency-map-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 17px 20px;
            border-bottom: 1px solid rgba(11, 31, 58, 0.08);
        }

        .transparency-map-card__head h3 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: var(--tp-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 20px;
            font-weight: 700;
        }

        .transparency-map-card__head h3 svg {
            color: #9a741e;
        }

        .transparency-map-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--tp-muted);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .transparency-map-legend__scale {
            width: 76px;
            height: 7px;
            border-radius: 99px;
            background: linear-gradient(90deg, #6abf8a, #e2c06a, #d16b5c);
        }

        .transparency-map-frame {
            position: relative;
            z-index: 0;
            isolation: isolate;
            height: 490px;
            background: #dce8e7;
        }

        #transparency-map {
            position: relative;
            z-index: 0;
            isolation: isolate;
            width: 100%;
            height: 100%;
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        }

        /* Leaflet assigns very high internal z-index values. The isolated
           parent keeps them below the fixed navbar; these rules also keep
           controls below the navbar when the page is scrolled. */
        .transparency-root .leaflet-control-container,
        .transparency-root .leaflet-top,
        .transparency-root .leaflet-bottom {
            z-index: 40;
        }

        .transparency-map-status {
            position: absolute;
            z-index: 45;
            top: 14px;
            left: 14px;
            display: inline-flex;
            max-width: calc(100% - 28px);
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border: 1px solid rgba(255, 255, 255, 0.65);
            border-radius: 5px;
            background: rgba(11, 31, 58, 0.84);
            box-shadow: 0 5px 16px rgba(11, 31, 58, 0.16);
            color: #fff;
            font-size: 11px;
            line-height: 1.4;
            pointer-events: none;
        }

        .transparency-map-status svg {
            flex: 0 0 auto;
            color: var(--tp-gold-light);
        }

        .transparency-map-card__foot {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 14px 20px 16px;
            border-top: 1px solid rgba(11, 31, 58, 0.08);
            color: var(--tp-muted);
            font-size: 11px;
            line-height: 1.55;
        }

        .transparency-map-card__foot svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: #9a741e;
        }

        /* ── Complaint table ───────────────────────────────────────────── */
        .transparency-table-card {
            overflow: hidden;
            border: 1px solid rgba(201, 168, 76, 0.25);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 34px rgba(11, 31, 58, 0.05);
        }

        .transparency-table-toolbar {
            display: grid;
            grid-template-columns: minmax(240px, 1.6fr) repeat(3, minmax(145px, 0.8fr)) auto;
            gap: 10px;
            align-items: end;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(11, 31, 58, 0.08);
            background: var(--tp-cream);
        }

        .transparency-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .transparency-field label {
            color: var(--tp-muted);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .transparency-field input,
        .transparency-field select {
            width: 100%;
            min-height: 40px;
            padding: 9px 11px;
            border: 1px solid rgba(11, 31, 58, 0.16);
            border-radius: 5px;
            outline: none;
            background: #fff;
            color: var(--tp-ink);
            font: inherit;
            font-size: 12px;
        }

        .transparency-field input:focus,
        .transparency-field select:focus {
            border-color: var(--tp-gold);
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.12);
        }

        .transparency-reset {
            min-height: 40px;
            padding: 9px 13px;
            border: 1px solid rgba(201, 168, 76, 0.35);
            border-radius: 5px;
            background: transparent;
            color: #8b6719;
            cursor: pointer;
            font: inherit;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .transparency-reset:hover {
            border-color: var(--tp-gold);
            background: rgba(201, 168, 76, 0.1);
        }

        .transparency-table-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 20px;
            color: var(--tp-muted);
            font-size: 10px;
        }

        .transparency-table-meta strong {
            color: var(--tp-navy);
        }

        .transparency-table-scroll {
            overflow-x: auto;
        }

        .transparency-table {
            width: 100%;
            min-width: 920px;
            border-collapse: collapse;
            text-align: left;
        }

        .transparency-table th {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(11, 31, 58, 0.1);
            background: #fbfaf7;
            color: var(--tp-muted);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .transparency-table td {
            padding: 15px 14px;
            border-bottom: 1px solid rgba(11, 31, 58, 0.07);
            color: var(--tp-ink);
            font-size: 11px;
            line-height: 1.45;
            vertical-align: middle;
        }

        .transparency-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .transparency-table tbody tr:hover {
            background: #fffdf8;
        }

        .transparency-ticket {
            color: #8b6719;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .transparency-complaint-title {
            display: block;
            max-width: 270px;
            color: var(--tp-navy);
            font-weight: 700;
        }

        .transparency-reporter {
            display: block;
            margin-top: 4px;
            color: var(--tp-muted);
            font-size: 10px;
        }

        .transparency-table-muted {
            color: var(--tp-muted);
        }

        .transparency-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 8px;
            border-radius: 3px;
            background: rgba(76, 131, 195, 0.1);
            color: #376da8;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.2;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .transparency-status--under_review,
        .transparency-status--rejected {
            background: rgba(166, 108, 23, 0.11);
            color: #8b5c12;
        }

        .transparency-status--in_progress {
            background: rgba(139, 104, 176, 0.11);
            color: #6e4c91;
        }

        .transparency-status--resolved {
            background: rgba(47, 158, 104, 0.11);
            color: #277a53;
        }

        .transparency-status--closed {
            background: rgba(113, 128, 150, 0.11);
            color: #5b687c;
        }

        .transparency-empty-row td {
            padding: 42px 20px !important;
            color: var(--tp-muted) !important;
            text-align: center;
        }

        .transparency-empty-row svg {
            display: block;
            margin: 0 auto 10px;
            color: #9a741e;
        }

        .transparency-empty-row strong {
            display: block;
            margin-bottom: 5px;
            color: var(--tp-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 24px;
        }

        .transparency-table-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 18px 0 0;
            color: var(--tp-muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .transparency-table-note svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: #9a741e;
        }

        .transparency-table-note a {
            color: #8b6719;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .transparency-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 20px 40px;
            border-top: 1px solid rgba(201, 168, 76, 0.12);
            background: #060f1e;
            color: rgba(255, 255, 255, 0.34);
            font-size: 10px;
        }

        .transparency-footer strong {
            color: rgba(201, 168, 76, 0.68);
        }

        @media (max-width: 900px) {
            .transparency-header__inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .transparency-header__actions {
                width: 100%;
                min-width: 0;
                flex-direction: row;
                flex-wrap: wrap;
            }

            .transparency-table-toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transparency-table-toolbar .transparency-field:first-child {
                grid-column: 1 / -1;
            }

            .transparency-reset {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .transparency-container {
                width: calc(100% - 40px);
            }

            .transparency-header,
            .transparency-section {
                padding-right: 20px;
                padding-left: 20px;
            }

            .transparency-header {
                padding-top: 112px;
                padding-bottom: 42px;
            }

            .transparency-header h1 {
                font-size: 48px;
            }

            .transparency-header__actions,
            .transparency-button {
                width: 100%;
            }

            .transparency-header__actions {
                flex-direction: column;
            }

            .transparency-section {
                padding-top: 54px;
                padding-bottom: 60px;
            }

            .transparency-section__head {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .transparency-map-card__head {
                align-items: flex-start;
                flex-direction: column;
            }

            .transparency-map-frame {
                height: 380px;
            }

            .transparency-table-toolbar {
                grid-template-columns: 1fr;
                padding: 15px;
            }

            .transparency-table-toolbar .transparency-field:first-child {
                grid-column: auto;
            }

            .transparency-table-meta {
                align-items: flex-start;
                flex-direction: column;
                padding: 11px 15px;
            }

            .transparency-footer {
                align-items: flex-start;
                flex-direction: column;
                padding: 18px 20px;
            }
        }

        @media (max-width: 420px) {
            .transparency-container {
                width: calc(100% - 24px);
            }

            .transparency-header,
            .transparency-section {
                padding-right: 14px;
                padding-left: 14px;
            }

            .transparency-header h1 {
                font-size: 38px;
            }

            .transparency-map-frame {
                height: 300px;
            }

            .transparency-table-card,
            .transparency-map-card {
                border-radius: 6px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="transparency-root">
        <header class="transparency-header">
            <div class="transparency-container transparency-header__inner">
                <div class="transparency-header__copy">
                    <span class="transparency-kicker">Public transparency</span>
                    <h1>Community reports, <em>clearly mapped.</em></h1>
                    <p class="transparency-header__lede">
                        See where public concerns are concentrated and search every complaint residents have chosen to share.
                        Exact addresses and private reports are never shown here.
                    </p>
                    <div class="transparency-header__meta">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V4.5"></path><path d="M4 19.5h16"></path><path d="m7 15 3-4 3 2 4-6"></path></svg>
                            {{ $reports->count() }} public {{ Str::plural('report', $reports->count()) }}
                        </span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                            Updated {{ $lastUpdated }}
                        </span>
                    </div>
                </div>
                <div class="transparency-header__actions">
                    @auth
                        <a href="{{ route('complaints.index') }}" class="transparency-button transparency-button--gold">My complaints</a>
                        <a href="{{ route('complaints.create') }}" class="transparency-button transparency-button--quiet">File a complaint</a>
                    @else
                        <a href="{{ route('login') }}" class="transparency-button transparency-button--gold">Log in</a>
                        <a href="{{ route('register') }}" class="transparency-button transparency-button--quiet">Create account</a>
                    @endauth
                </div>
            </div>
        </header>

        <section class="transparency-section transparency-section--map" id="heatmap" aria-labelledby="heatmap-heading">
            <div class="transparency-container">
                <div class="transparency-section__head">
                    <div>
                        <span class="transparency-kicker">Location signal</span>
                        <h2 id="heatmap-heading">Where public reports are concentrated.</h2>
                        <p>The heatmap uses approximate areas, not exact addresses or individual location pins.</p>
                    </div>
                </div>

                <div class="transparency-map-card">
                    <div class="transparency-map-card__head">
                        <h3>
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                            Public report heatmap
                        </h3>
                        <div class="transparency-map-legend" aria-label="Heatmap intensity legend">
                            <span>Low</span>
                            <span class="transparency-map-legend__scale" aria-hidden="true"></span>
                            <span>High</span>
                        </div>
                    </div>
                    <div class="transparency-map-frame">
                        <div id="transparency-map" role="region" aria-label="Approximate heatmap of public complaint areas in Daet"></div>
                        <div class="transparency-map-status" id="transparency-map-status" aria-live="polite">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                            <span>Loading approximate public areas…</span>
                        </div>
                    </div>
                    <div class="transparency-map-card__foot">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                        <span>Coordinates are clustered to a coarse area before display. Private reports and likely spam are excluded.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="transparency-section transparency-section--table" id="complaints" aria-labelledby="complaints-heading">
            <div class="transparency-container">
                <div class="transparency-section__head">
                    <div>
                        <span class="transparency-kicker">Complaint register</span>
                        <h2 id="complaints-heading">All public complaints.</h2>
                        <p>Search by ticket, title, category, department, or broad location. Use the filters to narrow the register.</p>
                    </div>
                    <span class="transparency-count" aria-label="{{ $reports->count() }} public complaints">{{ $reports->count() }}</span>
                </div>

                <div class="transparency-table-card">
                    <div class="transparency-table-toolbar">
                        <div class="transparency-field">
                            <label for="complaint-search">Search public complaints</label>
                            <input id="complaint-search" type="search" placeholder="Ticket, title, department…" autocomplete="off">
                        </div>
                        <div class="transparency-field">
                            <label for="complaint-department">Department</label>
                            <select id="complaint-department">
                                <option value="">All departments</option>
                                @foreach($departmentOptions as $department)
                                    <option value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="transparency-field">
                            <label for="complaint-category">Category</label>
                            <select id="complaint-category">
                                <option value="">All categories</option>
                                @foreach($categoryOptions as $category)
                                    <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="transparency-field">
                            <label for="complaint-status">Status</label>
                            <select id="complaint-status">
                                <option value="">All statuses</option>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status['key'] }}">{{ $status['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button id="complaint-reset" class="transparency-reset" type="button">Reset</button>
                    </div>

                    <div class="transparency-table-meta">
                        <span id="complaint-results">Showing {{ $reports->count() }} of {{ $reports->count() }} public complaints</span>
                        <span>Public visibility only</span>
                    </div>

                    <div class="transparency-table-scroll">
                        <table class="transparency-table">
                            <thead>
                                <tr>
                                    <th scope="col">Ticket</th>
                                    <th scope="col">Complaint</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Reported</th>
                                </tr>
                            </thead>
                            <tbody id="complaint-table-body">
                                @forelse($reports as $report)
                                    <tr
                                        data-report-row
                                        data-search="{{ strtolower(implode(' ', [$report['ticket_id'], $report['title'], $report['category_label'], $report['department'], $report['location'], $report['reporter']])) }}"
                                        data-department="{{ $report['department'] }}"
                                        data-category="{{ $report['category'] }}"
                                        data-status="{{ $report['status_key'] }}"
                                    >
                                        <td><span class="transparency-ticket">{{ $report['ticket_id'] }}</span></td>
                                        <td>
                                            <span class="transparency-complaint-title">{{ $report['title'] }}</span>
                                            <span class="transparency-reporter">{{ $report['reporter'] }}</span>
                                        </td>
                                        <td class="transparency-table-muted">{{ $report['category_label'] }}</td>
                                        <td class="transparency-table-muted">{{ $report['department'] }}</td>
                                        <td class="transparency-table-muted">{{ $report['location'] }}</td>
                                        <td><span class="transparency-status transparency-status--{{ $report['status_key'] }}">{{ $report['status_label'] }}</span></td>
                                        <td class="transparency-table-muted"><time datetime="{{ $report['iso_date'] }}">{{ $report['date'] }}</time></td>
                                    </tr>
                                @empty
                                    <tr id="complaint-empty-row" class="transparency-empty-row">
                                        <td colspan="7">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V4.5"></path><path d="M4 19.5h16"></path><path d="m7 15 3-4 3 2 4-6"></path></svg>
                                            <strong>No public complaints to show.</strong>
                                            Public reports will appear here when residents choose to share them.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr id="complaint-no-results" class="transparency-empty-row" hidden>
                                    <td colspan="7">
                                        <strong>No matching complaints.</strong>
                                        Try a different search term or reset the filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="transparency-table-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    <span>
                        Private complaints are not listed publicly. @auth <a href="{{ route('complaints.index') }}">Open My Complaints</a> to see the reports you filed and follow their progress. @else <a href="{{ route('login') }}">Log in</a> to view your own reports. @endauth
                    </span>
                </p>
            </div>
        </section>

        <footer class="transparency-footer">
            <span>© 2026 <strong>LGU Daet</strong> · Public Service Management System</span>
            <span>Approximate public locations · Privacy-safe reporting</span>
        </footer>
    </div>

    <script id="transparency-map-data" type="application/json">{!! json_encode([
        'center' => $mapCenter,
        'points' => $mapPoints,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
        (() => {
            const dataElement = document.getElementById('transparency-map-data');
            const mapElement = document.getElementById('transparency-map');
            const statusElement = document.getElementById('transparency-map-status');

            if (!dataElement || !mapElement || !statusElement) return;

            const setStatus = (message) => {
                const text = statusElement.querySelector('span');
                if (text) text.textContent = message;
            };

            let payload;
            try {
                payload = JSON.parse(dataElement.textContent || '{}');
            } catch (error) {
                setStatus('Approximate public areas could not be loaded. Use the complaint register below.');
                return;
            }

            const points = Array.isArray(payload.points) ? payload.points : [];
            const center = Array.isArray(payload.center) ? payload.center : [14.1122, 122.9553];

            if (typeof window.L === 'undefined') {
                setStatus('Interactive map unavailable. Use the complaint register below.');
                return;
            }

            const map = window.L.map(mapElement, {
                scrollWheelZoom: false,
                zoomControl: true,
            }).setView(center, 13);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(map);

            const heatLayerAvailable = typeof window.L.heatLayer === 'function';

            if (points.length > 0 && heatLayerAvailable) {
                window.L.heatLayer(
                    points.map((point) => [
                        Number(point.lat),
                        Number(point.lng),
                        Number(point.intensity || 0.6),
                    ]),
                    {
                        radius: 38,
                        blur: 26,
                        minOpacity: 0.38,
                        maxZoom: 16,
                        gradient: {
                            0.2: '#6abf8a',
                            0.45: '#e2c06a',
                            0.7: '#d98a4a',
                            1: '#c65b5b',
                        },
                    },
                ).addTo(map);
            }

            if (points.length > 0) {
                const bounds = window.L.latLngBounds(
                    points.map((point) => [Number(point.lat), Number(point.lng)]),
                );

                if (bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.15), { maxZoom: 15 });
                }
            }

            if (points.length === 0) {
                setStatus('No approximate public areas are available yet.');
            } else if (!heatLayerAvailable) {
                setStatus('Heat layer unavailable. Use the complaint register below.');
            } else {
                const reportTotal = points.reduce((total, point) => total + (Number(point.count) || 0), 0);
                setStatus(`${reportTotal} public ${reportTotal === 1 ? 'report' : 'reports'} shown as approximate areas`);
            }
        })();

        (() => {
            const search = document.getElementById('complaint-search');
            const department = document.getElementById('complaint-department');
            const category = document.getElementById('complaint-category');
            const status = document.getElementById('complaint-status');
            const reset = document.getElementById('complaint-reset');
            const results = document.getElementById('complaint-results');
            const noResults = document.getElementById('complaint-no-results');
            const rows = Array.from(document.querySelectorAll('[data-report-row]'));

            if (!search || !department || !category || !status || !reset || !results || !noResults || rows.length === 0) {
                return;
            }

            const normalize = (value) => (value || '').trim().toLocaleLowerCase();
            const total = rows.length;

            const applyFilters = () => {
                const term = normalize(search.value);
                const selectedDepartment = normalize(department.value);
                const selectedCategory = normalize(category.value);
                const selectedStatus = normalize(status.value);
                let visible = 0;

                rows.forEach((row) => {
                    const matchesSearch = !term || normalize(row.dataset.search).includes(term);
                    const matchesDepartment = !selectedDepartment || normalize(row.dataset.department) === selectedDepartment;
                    const matchesCategory = !selectedCategory || normalize(row.dataset.category) === selectedCategory;
                    const matchesStatus = !selectedStatus || normalize(row.dataset.status) === selectedStatus;
                    const matches = matchesSearch && matchesDepartment && matchesCategory && matchesStatus;

                    row.hidden = !matches;
                    if (matches) visible += 1;
                });

                results.textContent = `Showing ${visible} of ${total} public complaints`;
                noResults.hidden = visible !== 0;
            };

            search.addEventListener('input', applyFilters);
            [department, category, status].forEach((control) => {
                control.addEventListener('change', applyFilters);
            });

            reset.addEventListener('click', () => {
                search.value = '';
                department.value = '';
                category.value = '';
                status.value = '';
                applyFilters();
                search.focus();
            });
        })();
    </script>
@endpush
