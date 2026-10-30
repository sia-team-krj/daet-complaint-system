@extends($mainLayout)

@section('title', 'Public Transparency - Daet Listens')

@push('styles')
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

        .transparency-mobile-list {
            display: none;
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
            min-width: 0;
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
            max-width: 100%;
            overflow: hidden;
            color: #8b6719;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-decoration: none;
            text-overflow: ellipsis;
            white-space: nowrap;
            word-break: normal;
        }

        .transparency-ticket:hover,
        .transparency-complaint-title:hover,
        .transparency-row-link:hover {
            color: #6e4c91;
        }

        .transparency-complaint-title {
            display: block;
            max-width: 270px;
            overflow-wrap: break-word;
            color: var(--tp-navy);
            font-weight: 700;
            text-decoration: none;
            word-break: normal;
        }

        .transparency-cell-department,
        .transparency-cell-category,
        .transparency-cell-location {
            max-width: 100%;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .transparency-row-link {
            display: inline-block;
            margin-top: 6px;
            color: #8b6719;
            font-size: 10px;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
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

        /* How the department settled the report. Published deliberately: a
           single vague "verified" would claim more than a desk check can. */
        .transparency-verification {
            display: inline-block;
            margin-top: 5px;
            padding: 2px 7px;
            border: 1px solid rgba(31, 41, 55, 0.14);
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.6);
            color: #5b687c;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .transparency-verification--site_visit {
            border-color: rgba(39, 122, 83, 0.3);
            background: rgba(47, 158, 104, 0.1);
            color: #277a53;
        }

        .transparency-verification--duplicate_match {
            border-color: rgba(110, 76, 145, 0.28);
            background: rgba(139, 104, 176, 0.1);
            color: #6e4c91;
        }

        .transparency-verification--unrecorded {
            border-color: rgba(146, 138, 122, 0.3);
            color: #7c7466;
            font-style: italic;
            text-transform: none;
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

        /* The footer comes from layouts/partials/site-footer.blade.php now. */

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

            .transparency-desktop-table {
                display: none;
            }

            .transparency-mobile-list {
                display: grid;
                gap: 10px;
            }

            .transparency-mobile-card {
                min-width: 0;
                padding: 13px;
                border: 1px solid rgba(201, 168, 76, 0.25);
                border-radius: 6px;
                background: #fff;
            }

            .transparency-mobile-card[hidden] {
                display: none;
            }

            .transparency-mobile-card__top {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 10px;
            }

            .transparency-mobile-ticket {
                min-width: 0;
                overflow: hidden;
                color: #8b6719;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-decoration: none;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .transparency-mobile-card__title {
                display: block;
                margin-top: 10px;
                color: var(--tp-navy);
                font-family: "Cormorant Garamond", Georgia, serif;
                font-size: 21px;
                font-weight: 700;
                line-height: 1.05;
                overflow-wrap: break-word;
                text-decoration: none;
                word-break: normal;
            }

            .transparency-mobile-card__reporter {
                display: block;
                margin-top: 4px;
                color: var(--tp-muted);
                font-size: 10px;
            }

            .transparency-mobile-card__meta {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px 12px;
                margin-top: 13px;
            }

            .transparency-mobile-card__meta > div {
                min-width: 0;
            }

            .transparency-mobile-card__meta dt {
                margin-bottom: 3px;
                color: var(--tp-muted);
                font-size: 8px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .transparency-mobile-card__meta dd {
                min-width: 0;
                margin: 0;
                color: var(--tp-ink);
                font-size: 10px;
                line-height: 1.4;
                overflow-wrap: anywhere;
                word-break: normal;
            }

            .transparency-mobile-card__footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-top: 13px;
                padding-top: 10px;
                border-top: 1px solid rgba(11, 31, 58, 0.08);
            }

            .transparency-mobile-card__footer .transparency-row-link {
                margin-top: 0;
            }

            .transparency-mobile-empty {
                padding: 28px 16px;
                color: var(--tp-muted);
                text-align: center;
            }

            .transparency-mobile-empty strong {
                display: block;
                margin-bottom: 5px;
                color: var(--tp-navy);
                font-family: "Cormorant Garamond", Georgia, serif;
                font-size: 22px;
            }

            .transparency-mobile-empty p {
                margin: 0;
                font-size: 11px;
                line-height: 1.5;
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
    {{-- The register is a component so it can re-read the database on a timer.
         See app/Livewire/TransparencyRegister.php for why it used to need a
         manual reload, and TransparencyFeed for the query behind it. --}}
    <livewire:transparency-register />

    {{-- The register states its own privacy behaviour, and the footer carries the
         documents that explain it. Outside the component: it is static, and a
         timer has no reason to re-render it. --}}
    @include('layouts.partials.site-footer')
@endsection

{{-- This page renders the shared footer itself; see layouts/app.blade.php. --}}
@section('site-footer')
    {{-- Ownership marker only. The footer is rendered above, inside this page's
         own layout wrapper. This section exists purely to tell the shell layout
         not to add a second one, and it must contain something: `@hasSection`
         compiles to a non-empty content check, so an empty section is ignored. --}}
    <span hidden aria-hidden="true"></span>
@endsection

@push('scripts')
@include('pages.transparency.partials.register-script')
@endpush
