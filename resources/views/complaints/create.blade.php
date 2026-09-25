@extends($mainLayout)

@section('title', 'File a Complaint — Daet Listens')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .filing-page {
            --filing-navy: #0b1f3a;
            --filing-navy-mid: #12294d;
            --filing-gold: #c9a84c;
            --filing-gold-light: #e2c06a;
            --filing-gold-pale: rgba(201, 168, 76, 0.12);
            --filing-cream: #f5f0e8;
            --filing-cream-dark: #ede7d9;
            --filing-white: #fff;
            --filing-ink: #172b49;
            --filing-muted: #64748b;
            --filing-border: rgba(11, 31, 58, 0.1);
            min-height: 100vh;
            overflow: hidden;
            background: var(--filing-cream);
            color: var(--filing-ink);
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        }

        .filing-page *,
        .filing-page *::before,
        .filing-page *::after {
            box-sizing: border-box;
        }

        .filing-page h1,
        .filing-page h2,
        .filing-page h3,
        .filing-page p {
            margin-top: 0;
        }

        .filing-header {
            position: relative;
            overflow: hidden;
            padding: 116px 40px 46px;
            background: var(--filing-navy);
            color: #fff;
        }

        .filing-header::before {
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

        .filing-header::after {
            position: absolute;
            top: -250px;
            right: -120px;
            width: 580px;
            height: 580px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.11), transparent 68%);
            content: "";
            pointer-events: none;
        }

        .filing-header__inner,
        .filing-main {
            position: relative;
            z-index: 1;
            width: min(1180px, calc(100% - 80px));
            margin: 0 auto;
        }

        .filing-header__inner {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 42px;
        }

        .filing-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
            color: rgba(255, 255, 255, 0.58);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-decoration: none;
            text-transform: uppercase;
            transition: color 0.2s ease;
        }

        .filing-back:hover {
            color: var(--filing-gold-light);
        }

        .filing-header h1 {
            max-width: 720px;
            margin-bottom: 14px;
            color: #fff;
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(46px, 6vw, 74px);
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 0.96;
        }

        .filing-header h1 em {
            color: var(--filing-gold-light);
            font-style: italic;
        }

        .filing-header__lede {
            max-width: 620px;
            margin-bottom: 0;
            color: rgba(255, 255, 255, 0.68);
            font-size: 15px;
            font-weight: 300;
            line-height: 1.75;
        }

        .filing-header__aside {
            display: flex;
            max-width: 280px;
            align-items: flex-start;
            gap: 11px;
            padding: 15px 16px;
            border: 1px solid rgba(201, 168, 76, 0.32);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.045);
            color: rgba(255, 255, 255, 0.68);
            font-size: 11px;
            line-height: 1.6;
        }

        .filing-header__aside svg {
            flex: 0 0 auto;
            margin-top: 2px;
            color: var(--filing-gold-light);
        }

        .filing-header__aside strong {
            display: block;
            margin-bottom: 3px;
            color: #fff;
            font-size: 12px;
        }

        .filing-main {
            padding: 38px 0 80px;
        }

        .filing-stepper {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            margin-bottom: 28px;
            border: 1px solid var(--filing-border);
            border-radius: 8px;
            background: var(--filing-white);
            box-shadow: 0 10px 26px rgba(11, 31, 58, 0.05);
            overflow: hidden;
        }

        .filing-step {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 76px;
            padding: 14px 20px;
            border-right: 1px solid var(--filing-border);
        }

        .filing-step:last-child {
            border-right: 0;
        }

        .filing-step__number {
            display: inline-flex;
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(201, 168, 76, 0.35);
            border-radius: 50%;
            background: var(--filing-gold-pale);
            color: #8b6719;
            font-size: 10px;
            font-weight: 700;
        }

        .filing-step strong {
            display: block;
            margin-bottom: 3px;
            color: var(--filing-ink);
            font-size: 12px;
        }

        .filing-step small {
            display: block;
            color: var(--filing-muted);
            font-size: 10px;
            line-height: 1.4;
        }

        .filing-step--active {
            background: linear-gradient(90deg, rgba(201, 168, 76, 0.1), rgba(201, 168, 76, 0.02));
        }

        .filing-step--active::after {
            position: absolute;
            right: 20px;
            bottom: 0;
            left: 20px;
            height: 2px;
            background: var(--filing-gold);
            content: "";
        }

        .filing-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 290px;
            gap: 24px;
            align-items: start;
        }

        .filing-form {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 18px;
        }

        .filing-card {
            border: 1px solid var(--filing-border);
            border-radius: 8px;
            background: var(--filing-white);
            box-shadow: 0 10px 26px rgba(11, 31, 58, 0.045);
            overflow: hidden;
        }

        .filing-card__header {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 22px 26px 18px;
            border-bottom: 1px solid var(--filing-border);
        }

        .filing-card__icon {
            display: inline-flex;
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(201, 168, 76, 0.35);
            border-radius: 6px;
            background: var(--filing-gold-pale);
            color: #8b6719;
        }

        .filing-card__header h2 {
            margin-bottom: 4px;
            color: var(--filing-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 25px;
            font-weight: 700;
            line-height: 1;
        }

        .filing-card__header p {
            margin-bottom: 0;
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .filing-card__body {
            padding: 26px;
        }

        .filing-field {
            margin-bottom: 22px;
        }

        .filing-field:last-child {
            margin-bottom: 0;
        }

        .filing-field label {
            display: block;
            margin-bottom: 7px;
            color: var(--filing-navy);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .filing-field label span {
            color: #b45309;
        }

        .filing-field__hint {
            margin: -2px 0 9px;
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.55;
        }

        .filing-input,
        .filing-select,
        .filing-textarea {
            width: 100%;
            border: 1px solid rgba(11, 31, 58, 0.16);
            border-radius: 5px;
            outline: none;
            background: #fcfbf8;
            color: var(--filing-ink);
            font: inherit;
            font-size: 13px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .filing-input,
        .filing-select {
            min-height: 46px;
            padding: 11px 13px;
        }

        .filing-textarea {
            min-height: 150px;
            padding: 13px;
            line-height: 1.65;
            resize: vertical;
        }

        .filing-input::placeholder,
        .filing-textarea::placeholder {
            color: rgba(100, 116, 139, 0.62);
        }

        .filing-input:focus,
        .filing-select:focus,
        .filing-textarea:focus {
            border-color: var(--filing-gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.13);
        }

        .filing-select {
            appearance: none;
            padding-right: 38px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        .filing-error {
            margin-top: 6px;
            color: #b91c1c;
            font-size: 11px;
        }

        .filing-route {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            padding: 13px 14px;
            border: 1px solid rgba(201, 168, 76, 0.3);
            border-radius: 5px;
            background: rgba(201, 168, 76, 0.08);
            color: var(--filing-ink);
            font-size: 12px;
            line-height: 1.5;
        }

        .filing-route[hidden] {
            display: none;
        }

        .filing-route svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: #8b6719;
        }

        .filing-route strong {
            color: var(--filing-navy);
        }

        .filing-review-notice {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin: 0 0 24px;
            padding: 14px 16px;
            border: 1px solid rgba(201, 168, 76, 0.3);
            border-radius: 5px;
            background: rgba(201, 168, 76, 0.08);
            color: var(--filing-ink);
            font-size: 12px;
            line-height: 1.6;
        }

        .filing-review-notice svg {
            flex: 0 0 auto;
            margin-top: 2px;
            color: #8b6719;
        }

        .filing-review-notice strong {
            color: var(--filing-navy);
        }

        .filing-upload {
            position: relative;
            padding: 25px 18px;
            border: 1px dashed rgba(11, 31, 58, 0.25);
            border-radius: 6px;
            background: #fcfbf8;
            cursor: pointer;
            text-align: center;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .filing-upload:hover,
        .filing-upload.is-dragging {
            border-color: var(--filing-gold);
            background: rgba(201, 168, 76, 0.08);
        }

        .filing-upload__input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            clip-path: inset(50%);
            white-space: nowrap;
        }

        .filing-upload__icon {
            margin-bottom: 9px;
            color: #8b6719;
        }

        .filing-upload strong {
            display: block;
            margin-bottom: 4px;
            color: var(--filing-navy);
            font-size: 13px;
        }

        .filing-upload > span {
            color: var(--filing-muted);
            font-size: 11px;
        }

        .filing-upload__actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .filing-upload__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 13px;
            border: 1px solid rgba(201, 168, 76, 0.5);
            border-radius: 4px;
            color: #8b6719;
            background: #fff;
            font: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .filing-upload__action:hover,
        .filing-upload__action:focus-visible {
            color: var(--filing-navy);
            background: var(--filing-gold-pale);
            border-color: var(--filing-gold);
        }

        .filing-upload__action--camera {
            color: var(--filing-navy);
            background: var(--filing-gold-pale);
        }

        .filing-upload__counter {
            margin-top: 13px;
            color: var(--filing-muted);
            font-size: 10px;
        }

        .filing-upload__counter strong {
            display: inline;
            margin: 0;
            color: var(--filing-navy);
            font-size: 10px;
        }

        .filing-upload__previews {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 9px;
            margin-top: 15px;
        }

        .filing-upload__preview {
            position: relative;
            min-width: 0;
            aspect-ratio: 1;
            overflow: hidden;
            border: 1px solid var(--filing-border);
            border-radius: 4px;
            background: var(--filing-cream-dark);
        }

        .filing-upload__preview img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .filing-upload__remove {
            position: absolute;
            top: 5px;
            right: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            padding: 0;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            color: #fff;
            background: rgba(11, 31, 58, 0.78);
            font: inherit;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
        }

        .filing-upload__remove:hover,
        .filing-upload__remove:focus-visible {
            background: var(--filing-navy);
            border-color: var(--filing-gold-light);
        }

        .filing-upload__error {
            margin-top: 10px;
            color: #b91c1c;
            font-size: 11px;
            line-height: 1.45;
        }

        .filing-map {
            height: 330px;
            margin-top: 15px;
            overflow: hidden;
            border: 1px solid var(--filing-border);
            border-radius: 6px;
            background: #dce8e7;
        }

        .filing-map-note {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin: 8px 0 0;
            color: var(--filing-muted);
            font-size: 10px;
            line-height: 1.5;
        }

        .filing-map-note svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: #8b6719;
        }

        .filing-summary {
            margin: 25px 0 0;
            padding: 17px 18px;
            border: 1px solid rgba(201, 168, 76, 0.28);
            border-radius: 6px;
            background: #fffdf8;
        }

        .filing-summary h3 {
            margin-bottom: 13px;
            color: var(--filing-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 20px;
            line-height: 1;
        }

        .filing-summary__row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 9px 0;
            border-top: 1px solid rgba(11, 31, 58, 0.08);
            font-size: 11px;
        }

        .filing-summary__row span {
            color: var(--filing-muted);
        }

        .filing-summary__row strong {
            max-width: 65%;
            color: var(--filing-navy);
            font-size: 11px;
            text-align: right;
        }

        .filing-terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 22px;
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .filing-terms input {
            width: 16px;
            height: 16px;
            flex: 0 0 auto;
            margin-top: 1px;
            accent-color: var(--filing-gold);
        }

        .filing-terms a {
            color: #8b6719;
            font-weight: 700;
        }

        .filing-submit {
            display: flex;
            width: 100%;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 23px;
            padding: 13px 22px;
            border: 0;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--filing-gold), var(--filing-gold-light));
            box-shadow: 0 8px 22px rgba(201, 168, 76, 0.22);
            color: var(--filing-navy);
            cursor: pointer;
            font: inherit;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .filing-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(201, 168, 76, 0.3);
        }

        .filing-submit:disabled {
            cursor: wait;
            opacity: 0.65;
            transform: none;
        }

        .filing-aside {
            position: sticky;
            top: 84px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .filing-aside-card {
            padding: 21px;
            border: 1px solid rgba(201, 168, 76, 0.24);
            border-radius: 7px;
            background: rgba(255, 255, 255, 0.72);
        }

        .filing-aside-card h2 {
            margin-bottom: 14px;
            color: var(--filing-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 23px;
            line-height: 1;
        }

        .filing-aside-card p,
        .filing-aside-card li {
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.65;
        }

        .filing-aside-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .filing-aside-list li {
            display: grid;
            grid-template-columns: 22px 1fr;
            gap: 9px;
        }

        .filing-aside-list li svg {
            width: 22px;
            height: 22px;
            padding: 4px;
            border: 1px solid rgba(201, 168, 76, 0.3);
            border-radius: 50%;
            color: #8b6719;
        }

        .filing-next {
            display: flex;
            flex-direction: column;
            gap: 13px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .filing-next li {
            display: grid;
            grid-template-columns: 25px 1fr;
            gap: 9px;
        }

        .filing-next strong {
            display: block;
            margin-bottom: 2px;
            color: var(--filing-navy);
            font-size: 11px;
        }

        .filing-next__number {
            display: inline-flex;
            width: 24px;
            height: 24px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--filing-navy);
            color: var(--filing-gold-light);
            font-size: 10px;
            font-weight: 700;
        }

        .filing-error-banner {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 14px 17px;
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 6px;
            background: rgba(239, 68, 68, 0.06);
            color: #991b1b;
            font-size: 12px;
            line-height: 1.6;
        }

        .filing-error-banner svg {
            flex: 0 0 auto;
            margin-top: 2px;
        }

        .filing-error-banner ul {
            margin: 5px 0 0;
            padding-left: 17px;
        }

        /* Leaflet marker */
        .gold-pin-marker {
            position: relative;
            width: 30px;
            height: 42px;
        }

        .gold-pin-marker .pin-head {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 20px;
            height: 20px;
            transform: translateX(-50%);
            border: 3px solid #fff;
            border-radius: 50%;
            background: linear-gradient(135deg, #c9a84c, #e2c06a);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .gold-pin-marker .pin-head::after {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 6px;
            height: 6px;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background: #0b1f3a;
            content: "";
        }

        .gold-pin-marker .pin-pulse {
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 40px;
            height: 40px;
            transform: translateX(-50%);
            border-radius: 50%;
            background: rgba(201, 168, 76, 0.35);
            animation: filing-pin-pulse 1.5s ease-out infinite;
        }

        @keyframes filing-pin-pulse {
            0% { opacity: 1; transform: translateX(-50%) scale(0.5); }
            100% { opacity: 0; transform: translateX(-50%) scale(1.5); }
        }

        .leaflet-marker-icon.gold-pin-marker {
            border: 0 !important;
            background: transparent !important;
        }

        @media (max-width: 900px) {
            .filing-header,
            .filing-main {
                width: 100%;
            }

            .filing-header {
                padding-right: 24px;
                padding-left: 24px;
            }

            .filing-header__inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .filing-header__aside {
                max-width: 420px;
            }

            .filing-main {
                padding: 28px 24px 64px;
            }

            .filing-layout {
                grid-template-columns: 1fr;
            }

            .filing-aside {
                position: static;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .filing-header {
                padding-top: 104px;
                padding-bottom: 38px;
            }

            .filing-header h1 {
                font-size: 48px;
            }

            .filing-main {
                padding: 20px 16px 52px;
            }

            .filing-stepper {
                grid-template-columns: 1fr;
            }

            .filing-step {
                min-height: 62px;
                border-right: 0;
                border-bottom: 1px solid var(--filing-border);
            }

            .filing-step:last-child {
                border-bottom: 0;
            }

            .filing-step--active::after {
                right: 16px;
                bottom: 0;
                left: 16px;
            }

            .filing-card__header,
            .filing-card__body {
                padding-right: 18px;
                padding-left: 18px;
            }

            .filing-card__header h2 {
                font-size: 23px;
            }

            .filing-aside {
                display: flex;
            }

            .filing-map {
                height: 280px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .gold-pin-marker .pin-pulse {
                animation: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="filing-page">
        <header class="filing-header">
            <div class="filing-header__inner">
                <div>
                    <a href="{{ route('complaints.index') }}" class="filing-back">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
                        Back to my complaints
                    </a>
                    <h1>Tell us what needs <em>attention.</em></h1>
                    <p class="filing-header__lede">
                        Share the facts once. We will route your report to the right department, verify it, and keep you updated.
                    </p>
                </div>
                <div class="filing-header__aside">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7l8 4 8-4-8-4Z"></path><path d="M4 12h16"></path><path d="M4 17h16"></path><path d="m4 7 8 4 8-4"></path></svg>
                    <span><strong>Reviewed before action</strong>The department checks each report and confirms its priority.</span>
                </div>
            </div>
        </header>

        <main class="filing-main">
            <nav class="filing-stepper" aria-label="Complaint filing steps">
                <div class="filing-step filing-step--active">
                    <span class="filing-step__number">01</span>
                    <span><strong>Category</strong><small>Choose the concern</small></span>
                </div>
                <div class="filing-step">
                    <span class="filing-step__number">02</span>
                    <span><strong>Details</strong><small>Explain what happened</small></span>
                </div>
                <div class="filing-step">
                    <span class="filing-step__number">03</span>
                    <span><strong>Location & review</strong><small>Confirm and submit</small></span>
                </div>
            </nav>

            <div class="filing-layout">
                <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" id="complaint-form" class="filing-form">
                    @csrf

                    @if($errors->any())
                        <div class="filing-error-banner" role="alert">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                            <div>
                                <strong>Check the highlighted fields.</strong>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <section class="filing-card" id="filing-category">
                        <div class="filing-card__header">
                            <span class="filing-card__icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 22V12h6v10"></path></svg>
                            </span>
                            <div>
                                <h2>What are you reporting?</h2>
                                <p>Choose the closest category so we can route it correctly.</p>
                            </div>
                        </div>
                        <div class="filing-card__body">
                            <div class="filing-field">
                                <label for="category">Complaint category <span>*</span></label>
                                <p class="filing-field__hint">Pick the main issue. You can add more context in the description.</p>
                                <select id="category" name="category" class="filing-select" required>
                                    <option value="">— Select a category —</option>
                                    @foreach($categoryOptions as $department => $options)
                                        <optgroup label="{{ $department }}">
                                            @foreach($options as $option)
                                                <option value="{{ $option['value'] }}" @selected(old('category') === $option['value'])>{{ $option['label'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('category') <div class="filing-error">{{ $message }}</div> @enderror
                            </div>
                            <div id="department-hint" class="filing-route" role="status" aria-live="polite" hidden>
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                                <span>This report will be routed to <strong id="department-name">the responsible department</strong>.</span>
                            </div>
                        </div>
                    </section>

                    <section class="filing-card" id="filing-details">
                        <div class="filing-card__header">
                            <span class="filing-card__icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>
                            </span>
                            <div>
                                <h2>Give us the useful details.</h2>
                                <p>Clear facts help the department verify and act faster.</p>
                            </div>
                        </div>
                        <div class="filing-card__body">
                            <div class="filing-field">
                                <label for="title">Short title <span>*</span></label>
                                <p class="filing-field__hint">Summarize the problem in one sentence.</p>
                                <input id="title" name="title" class="filing-input" type="text" maxlength="255" placeholder="e.g. Fallen tree blocking the road" value="{{ old('title') }}" required>
                                @error('title') <div class="filing-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="filing-field">
                                <label for="description">What happened? <span>*</span></label>
                                <p class="filing-field__hint">Include when it started, who is affected, and any immediate risk. Minimum 20 characters.</p>
                                <textarea id="description" name="description" class="filing-textarea" minlength="20" placeholder="Describe what you observed and how it affects the community..." required>{{ old('description') }}</textarea>
                                @error('description') <div class="filing-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="filing-review-notice">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7l8 4 8-4-8-4Z"></path><path d="M4 12h16"></path><path d="M4 17h16"></path><path d="m4 7 8 4 8-4"></path></svg>
                                <span><strong>No urgency selection is needed.</strong> The system will suggest a priority from your category and description. The responsible department verifies the report and confirms the final priority.</span>
                            </div>
                            <div class="filing-field">
                                <label for="images-input">Photo evidence <span>*</span></label>
                                <p class="filing-field__hint">Required. Add up to five clear photos taken at the issue location. GPS from any photo is used when available; add a landmark or map point as a fallback. JPG, PNG, or WEBP, up to 5 MB each.</p>
                                <div class="filing-upload" id="upload-zone">
                                    <input class="filing-upload__input" type="file" name="images[]" id="images-input" accept="image/jpeg,image/png,image/webp" multiple>
                                    <input class="filing-upload__input" type="file" name="camera_photo" id="camera-input" accept="image/jpeg,image/png,image/webp" capture="environment">
                                    <div class="filing-upload__icon">
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                                    </div>
                                    <strong>Add up to five photos</strong>
                                    <span>Choose from your device or use your phone’s camera.</span>
                                    <div class="filing-upload__actions">
                                        <button type="button" class="filing-upload__action" data-upload-trigger="images-input">Choose photos</button>
                                        <button type="button" class="filing-upload__action filing-upload__action--camera" data-upload-trigger="camera-input">Use camera</button>
                                    </div>
                                    <div class="filing-upload__counter"><strong id="photo-count">0 / 5</strong> photos attached</div>
                                    <div class="filing-upload__previews" id="upload-previews" aria-live="polite"></div>
                                    <div class="filing-upload__error" id="upload-client-error" role="alert" hidden></div>
                                </div>
                                @error('images') <div class="filing-error">{{ $message }}</div> @enderror
                                @error('camera_photo') <div class="filing-error">{{ $message }}</div> @enderror
                                @error('image') <div class="filing-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="filing-card" id="filing-location">
                        <div class="filing-card__header">
                            <span class="filing-card__icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                            </span>
                            <div>
                                <h2>Where is the issue?</h2>
                                <p>Photo GPS is used when available. Add a landmark or map point as a fallback.</p>
                            </div>
                        </div>
                        <div class="filing-card__body">
                            <div class="filing-field">
                                <label for="address_text">Address or nearest landmark <span>(optional)</span></label>
                                <p class="filing-field__hint">Example: in front of Daet Public Market, Barangay VI.</p>
                                <input id="address_text" name="address_text" class="filing-input" type="text" maxlength="500" placeholder="Describe the location..." value="{{ old('address_text') }}">
                                @error('address_text') <div class="filing-error">{{ $message }}</div> @enderror
                            </div>
                            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                            <div id="complaint-map" class="filing-map" aria-label="Map for selecting the complaint location"></div>
                            <p class="filing-map-note">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                                Click the map to place a marker, or enter a nearby landmark above.
                            </p>
                            @error('latitude') <div class="filing-error">{{ $message }}</div> @enderror
                            @error('longitude') <div class="filing-error">{{ $message }}</div> @enderror

                            <div class="filing-summary" aria-live="polite">
                                <h3>Ready to submit?</h3>
                                <div class="filing-summary__row"><span>Category</span><strong id="review-category">Not selected</strong></div>
                                <div class="filing-summary__row"><span>Department</span><strong id="review-department">Not routed yet</strong></div>
                                <div class="filing-summary__row"><span>Location</span><strong id="review-location">No location added</strong></div>
                            </div>

                            <div class="filing-terms">
                                <input type="checkbox" name="terms" id="terms" value="1" @checked(old('terms')) required>
                                <label for="terms">I confirm that the information provided is accurate. I understand that the department will verify this report before deciding its priority and next action. <a href="#" target="_blank" rel="noopener">Read the complaint policy.</a></label>
                            </div>
                            @error('terms') <div class="filing-error">{{ $message }}</div> @enderror

                            <button type="submit" class="filing-submit" id="submit-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                                Submit for department review
                            </button>
                        </div>
                    </section>
                </form>

                <aside class="filing-aside" aria-label="Filing guidance">
                    <div class="filing-aside-card">
                        <h2>Before you submit</h2>
                        <ul class="filing-aside-list">
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><circle cx="8" cy="9" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                                <span>Take one or more clear photos at the issue location; keep location services enabled when possible.</span>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                                <span>Describe what you observed, not what you assume caused it.</span>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                                <span>Add a nearby landmark or map pin when possible.</span>
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18"></path><path d="M3 12h18"></path><circle cx="12" cy="12" r="9"></circle></svg>
                                <span>For immediate danger, contact local emergency services first.</span>
                            </li>
                        </ul>
                    </div>
                    <div class="filing-aside-card">
                        <h2>What happens next</h2>
                        <ol class="filing-next">
                            <li><span class="filing-next__number">1</span><span><strong>Department review</strong><span>Your report is checked for legitimacy and scope.</span></span></li>
                            <li><span class="filing-next__number">2</span><span><strong>Priority confirmed</strong><span>The assigned office confirms the suggested priority.</span></span></li>
                            <li><span class="filing-next__number">3</span><span><strong>Status updates</strong><span>You can follow progress from My Complaints.</span></span></li>
                        </ol>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script id="category-department-data" type="application/json">{!! json_encode(
        collect($categoryOptions)
            ->mapWithKeys(fn ($options, $department) => collect($options)->mapWithKeys(fn ($option) => [$option['value'] => $department]))
            ->all(),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
    ) !!}</script>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const initializeFilingPage = () => {
                const form = document.getElementById('complaint-form');
                if (!form || form.dataset.filingInitialized === 'true') return;
                form.dataset.filingInitialized = 'true';

                const imagesInput = document.getElementById('images-input');
                const cameraInput = document.getElementById('camera-input');
                const uploadZone = document.getElementById('upload-zone');
                const uploadPreviews = document.getElementById('upload-previews');
                const photoCount = document.getElementById('photo-count');
                const clientUploadError = document.getElementById('upload-client-error');
                const categorySelect = document.getElementById('category');
                const departmentHint = document.getElementById('department-hint');
                const departmentName = document.getElementById('department-name');
                const addressInput = document.getElementById('address_text');
                const reviewCategory = document.getElementById('review-category');
                const reviewDepartment = document.getElementById('review-department');
                const reviewLocation = document.getElementById('review-location');
                const submitButton = document.getElementById('submit-btn');
                const categoryDataElement = document.getElementById('category-department-data');
                let categoryToDepartment = {};

                try {
                    categoryToDepartment = JSON.parse(categoryDataElement?.textContent || '{}');
                } catch (error) {
                    categoryToDepartment = {};
                }

                const updateSummary = () => {
                    const categoryLabel = categorySelect?.selectedOptions?.[0]?.textContent?.trim();
                    const department = categoryToDepartment[categorySelect?.value || ''];
                    reviewCategory.textContent = categoryLabel && categoryLabel !== '— Select a category —' ? categoryLabel : 'Not selected';
                    reviewDepartment.textContent = department || 'Not routed yet';
                    reviewLocation.textContent = addressInput?.value?.trim() || 'No location added';
                };

                if (categorySelect) {
                    categorySelect.addEventListener('change', () => {
                        const department = categoryToDepartment[categorySelect.value];
                        if (department) {
                            departmentName.textContent = department;
                            departmentHint.hidden = false;
                        } else {
                            departmentHint.hidden = true;
                        }
                        updateSummary();
                    });
                    updateSummary();
                }

                addressInput?.addEventListener('input', updateSummary);

                const maxPhotos = 5;
                const maxPhotoBytes = 5 * 1024 * 1024;
                let selectedPhotos = [];
                let previewUrls = [];

                const showUploadError = (message) => {
                    if (!clientUploadError) return;
                    clientUploadError.textContent = message;
                    clientUploadError.hidden = !message;
                };

                const isAllowedPhoto = (file) => {
                    const allowedType = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
                    const allowedName = /\.(jpe?g|png|webp)$/i.test(file.name);
                    return file.size > 0 && file.size <= maxPhotoBytes && (allowedType || allowedName);
                };

                const syncGalleryInput = () => {
                    if (!imagesInput || typeof DataTransfer === 'undefined') return false;

                    try {
                        const transfer = new DataTransfer();
                        selectedPhotos.forEach(({ file }) => transfer.items.add(file));
                        imagesInput.files = transfer.files;
                        return true;
                    } catch (error) {
                        return false;
                    }
                };

                const renderPhotoPreviews = () => {
                    if (!uploadPreviews || !photoCount) return;

                    previewUrls.forEach((url) => URL.revokeObjectURL(url));
                    previewUrls = [];
                    uploadPreviews.replaceChildren();
                    photoCount.textContent = `${selectedPhotos.length} / ${maxPhotos}`;

                    selectedPhotos.forEach(({ id, file }) => {
                        const preview = document.createElement('div');
                        preview.className = 'filing-upload__preview';

                        const image = document.createElement('img');
                        const objectUrl = URL.createObjectURL(file);
                        previewUrls.push(objectUrl);
                        image.src = objectUrl;
                        image.alt = `Selected evidence photo ${file.name}`;
                        preview.appendChild(image);

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'filing-upload__remove';
                        remove.setAttribute('aria-label', `Remove ${file.name}`);
                        remove.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
                        remove.addEventListener('click', () => {
                            selectedPhotos = selectedPhotos.filter((photo) => photo.id !== id);
                            syncGalleryInput();
                            showUploadError('');
                            renderPhotoPreviews();
                        });
                        preview.appendChild(remove);
                        uploadPreviews.appendChild(preview);
                    });
                };

                const addPhotos = (files) => {
                    const incoming = Array.from(files || []);
                    const invalid = incoming.find((file) => !isAllowedPhoto(file));

                    if (invalid) {
                        showUploadError('Use JPG, PNG, or WEBP photos no larger than 5 MB each.');
                        return;
                    }

                    const existingKeys = new Set(selectedPhotos.map(({ file }) => `${file.name}-${file.size}-${file.lastModified}`));
                    const unique = incoming.filter((file) => {
                        const key = `${file.name}-${file.size}-${file.lastModified}`;
                        if (existingKeys.has(key)) return false;
                        existingKeys.add(key);
                        return true;
                    });
                    const availableSlots = maxPhotos - selectedPhotos.length;

                    if (unique.length > availableSlots) {
                        showUploadError(`You can attach a maximum of ${maxPhotos} photos.`);
                    } else {
                        showUploadError('');
                    }

                    unique.slice(0, Math.max(availableSlots, 0)).forEach((file, index) => {
                        selectedPhotos.push({
                            id: `${Date.now()}-${index}-${file.name}-${file.size}`,
                            file,
                        });
                    });

                    syncGalleryInput();
                    renderPhotoPreviews();
                };

                document.querySelectorAll('[data-upload-trigger]').forEach((trigger) => {
                    trigger.addEventListener('click', () => {
                        const input = document.getElementById(trigger.dataset.uploadTrigger);
                        input?.click();
                    });
                });

                imagesInput?.addEventListener('change', () => {
                    addPhotos(imagesInput.files);
                });

                cameraInput?.addEventListener('change', () => {
                    if (!cameraInput.files?.length) return;
                    addPhotos(cameraInput.files);
                    if (syncGalleryInput()) {
                        cameraInput.value = '';
                    }
                });

                if (uploadZone) {
                    uploadZone.addEventListener('click', (event) => {
                        if (event.target === imagesInput || event.target === cameraInput || event.target.closest('button')) return;
                        imagesInput?.click();
                    });

                    uploadZone.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        uploadZone.classList.add('is-dragging');
                    });
                    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('is-dragging'));
                    uploadZone.addEventListener('drop', (event) => {
                        event.preventDefault();
                        uploadZone.classList.remove('is-dragging');
                        addPhotos(event.dataTransfer?.files);
                    });
                }

                let map = null;
                let marker = null;
                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                const mapElement = document.getElementById('complaint-map');

                const updateCoordinates = (latitude, longitude) => {
                    if (latitudeInput) latitudeInput.value = latitude.toFixed(8);
                    if (longitudeInput) longitudeInput.value = longitude.toFixed(8);
                    if (addressInput && !addressInput.value) {
                        addressInput.value = `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
                    }
                    updateSummary();
                };

                const initializeMap = () => {
                    if (!mapElement) return;
                    if (typeof window.L === 'undefined') {
                        mapElement.innerHTML = '<div style="padding:20px;text-align:center;color:#64748b;font-size:12px">Map unavailable. You can still submit using the address or landmark field.</div>';
                        return;
                    }

                    if (map) {
                        map.remove();
                        map = null;
                        marker = null;
                    }

                    map = window.L.map(mapElement).setView([14.1153, 122.9553], 14);
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 18,
                    }).addTo(map);

                    const pinIcon = window.L.divIcon({
                        className: 'gold-pin-marker',
                        html: '<div class="pin-head"></div><div class="pin-pulse"></div>',
                        iconSize: [30, 42],
                        iconAnchor: [15, 42],
                    });

                    const placeMarker = (latitude, longitude, recenter = false) => {
                        if (marker) {
                            marker.setLatLng([latitude, longitude]);
                        } else {
                            marker = window.L.marker([latitude, longitude], { icon: pinIcon, draggable: true }).addTo(map);
                            marker.on('dragend', () => {
                                const position = marker.getLatLng();
                                updateCoordinates(position.lat, position.lng);
                            });
                        }
                        if (recenter) map.setView([latitude, longitude], 16);
                        updateCoordinates(latitude, longitude);
                    };

                    const savedLatitude = Number.parseFloat(latitudeInput?.value);
                    const savedLongitude = Number.parseFloat(longitudeInput?.value);
                    if (Number.isFinite(savedLatitude) && Number.isFinite(savedLongitude)) {
                        placeMarker(savedLatitude, savedLongitude, true);
                    }

                    map.on('click', (event) => placeMarker(event.latlng.lat, event.latlng.lng));
                };

                initializeMap();

                form.addEventListener('submit', (event) => {
                    if (selectedPhotos.length === 0) {
                        event.preventDefault();
                        showUploadError('Add at least one clear photo before submitting.');
                        document.querySelector('[data-upload-trigger="images-input"]')?.focus();
                        return;
                    }

                    if (!submitButton) return;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg> Sending for review...';
                });
            };

            const scheduleInitialize = () => window.setTimeout(initializeFilingPage, 80);
            document.addEventListener('livewire:navigated', scheduleInitialize);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', scheduleInitialize);
            } else {
                scheduleInitialize();
            }
        })();
    </script>
@endpush
