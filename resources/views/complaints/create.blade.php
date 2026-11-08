@extends($mainLayout)

@section('title', 'File a Complaint - Daet Listens')

@push('styles')
    <style>
        .filing-page {
            --filing-navy: #0b1f3a;
            --filing-navy-mid: #1B3A61;
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

        /* ─── Visibility choice ───
           A fieldset of radios rather than a switch: the two outcomes carry equal
           weight, so both have to be readable before one is chosen. */
        .visibility-choice {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            border: 0;
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        }

        .visibility-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            background: #fcfbf8;
            border: 1px solid rgba(11, 31, 58, 0.16);
            border-radius: 5px;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease;
        }

        .visibility-option:hover {
            border-color: var(--filing-gold);
            background: var(--filing-gold-pale);
        }

        .visibility-option input {
            margin: 2px 0 0;
            accent-color: var(--filing-gold);
            flex-shrink: 0;
        }

        .visibility-option:has(input:checked) {
            background: var(--filing-gold-pale);
            border-color: var(--filing-gold);
        }

        .visibility-option:has(input:focus-visible) {
            outline: 2px solid var(--filing-gold);
            outline-offset: 2px;
        }

        .visibility-option__body {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .visibility-option__title {
            color: var(--filing-navy);
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
        }

        .visibility-option__note {
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.55;
        }

        .visibility-footnote {
            margin: 14px 0 0;
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.55;
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

        .filing-locate {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 9px 14px;
            color: var(--filing-navy);
            background: #fcfbf8;
            border: 1px solid rgba(11, 31, 58, 0.16);
            border-radius: 5px;
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .filing-locate:hover:not(:disabled) {
            border-color: var(--filing-gold);
            background: var(--filing-gold-pale);
        }

        .filing-locate:disabled {
            color: var(--filing-muted);
            cursor: progress;
            opacity: 0.7;
        }

        .filing-locate svg {
            flex: 0 0 auto;
            color: #8b6719;
        }

        .filing-locate-status {
            margin: 7px 0 0;
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .filing-locate-status[hidden] {
            display: none;
        }

        .location-choice {
            margin-top: 15px;
            padding: 16px;
            background: #fdf6e4;
            border: 1px solid rgba(201, 168, 76, 0.5);
            border-radius: 6px;
        }

        .location-choice__title {
            margin: 0 0 4px;
            color: var(--filing-navy);
            font-size: 13px;
            font-weight: 700;
        }

        .location-choice__note {
            margin: 0 0 13px;
            color: var(--filing-muted);
            font-size: 11px;
            line-height: 1.55;
        }

        .location-choice__options {
            display: grid;
            gap: 9px;
            margin: 0;
            padding: 0;
            border: 0;
        }

        .location-choice__option {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 12px 14px;
            background: #fcfbf8;
            border: 1px solid rgba(11, 31, 58, 0.14);
            border-radius: 5px;
            cursor: pointer;
        }

        .location-choice__option:hover {
            border-color: var(--filing-gold);
        }

        .location-choice__option:has(input:checked) {
            background: var(--filing-gold-pale);
            border-color: var(--filing-gold);
        }

        .location-choice__option input {
            margin: 2px 0 0;
            accent-color: var(--filing-gold);
            flex: 0 0 auto;
        }

        .location-choice__label {
            display: block;
            color: var(--filing-navy);
            font-size: 12.5px;
            font-weight: 600;
        }

        .location-choice__coords {
            display: block;
            margin-top: 2px;
            color: var(--filing-muted);
            font-size: 11px;
            font-variant-numeric: tabular-nums;
        }

        .location-choice__error {
            margin: 11px 0 0;
            color: #b91c1c;
            font-size: 11px;
            line-height: 1.5;
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

        /* ═══════════════════════════════════════════════════════════════
           MOBILE WIZARD
           Everything wizard-related is scoped to `.filing-page.wizard-on`,
           a class the script adds ONLY while the viewport is ≤640px.
           Desktop never gets it, and if the script fails the plain form
           still shows.
           ═══════════════════════════════════════════════════════════════ */

        .filing-page [hidden] {
            display: none !important;
        }

        @keyframes wizardFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: none; }
        }

        /* Step headings: screen-reader only on desktop, visible in the wizard */
        .wizard-step-title {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }

        /* Hidden by default (desktop) */
        .wizard-progress-header,
        .wizard-action-bar,
        .wizard-draft-banner {
            display: none;
        }

        @media (max-width: 640px) {
            /* Compact hero */
            .filing-header {
                padding: 76px 16px 16px;
            }

            .filing-header h1 {
                margin-bottom: 0;
                font-size: 28px;
                line-height: 1.15;
            }

            .filing-header__lede,
            .filing-header__aside {
                display: none;
            }

            .filing-back {
                margin-bottom: 10px;
            }

            /* Page chrome */
            .filing-stepper {
                display: none;
            }

            .filing-main {
                padding: 16px 16px calc(104px + env(safe-area-inset-bottom));
            }

            .filing-layout {
                display: block;
            }

            .filing-aside {
                display: none !important;
            }

            .page-complaint-create footer {
                display: none !important;
            }

            /* Progress header */
            .filing-page.wizard-on .wizard-progress-header {
                display: block;
                margin-bottom: 16px;
                padding: 16px;
                border: 1px solid var(--filing-border);
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 4px 16px rgba(11, 31, 58, 0.06);
            }

            .wizard-progress-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
                color: var(--filing-muted);
                font-size: 13px;
                font-weight: 600;
            }

            .wizard-progress-title {
                color: var(--filing-navy);
                font-size: 15px;
                font-weight: 700;
            }

            .wizard-progress-track {
                width: 100%;
                height: 6px;
                overflow: hidden;
                border-radius: 3px;
                background: var(--filing-cream-dark);
            }

            .wizard-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, var(--filing-gold), var(--filing-gold-light));
                transition: width 0.25s ease;
            }

            /* Draft banner */
            .filing-page.wizard-on .wizard-draft-banner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 16px;
                padding: 12px 16px;
                border: 1px solid rgba(201, 168, 76, 0.4);
                border-radius: 8px;
                background: #fdf6e4;
                color: var(--filing-navy);
                font-size: 12px;
            }

            .wizard-draft-actions {
                display: flex;
                gap: 6px;
            }

            .wizard-draft-banner button {
                padding: 8px 12px;
                border: 0;
                border-radius: 6px;
                background: var(--filing-gold);
                color: var(--filing-navy);
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
            }

            .wizard-draft-banner .wizard-draft-reset {
                background: transparent;
                border: 1px solid rgba(11, 31, 58, 0.25);
            }

            /* Wrapper cards vanish; only the active step shows */
            .filing-page.wizard-on .filing-form {
                gap: 0;
            }

            .filing-page.wizard-on #filing-details,
            .filing-page.wizard-on #filing-location,
            .filing-page.wizard-on #filing-details > .filing-card__body,
            .filing-page.wizard-on #filing-location > .filing-card__body {
                display: contents;
            }

            .filing-page.wizard-on #filing-details > .filing-card__header,
            .filing-page.wizard-on #filing-location > .filing-card__header {
                display: none;
            }

            .filing-page.wizard-on [data-wizard-step] {
                display: none;
            }

            .filing-page.wizard-on [data-wizard-step].wizard-step-active {
                display: block;
                animation: wizardFadeIn 0.2s ease both;
            }

            /* Steps that are plain <div>s get their own card look */
            .filing-page.wizard-on div[data-wizard-step].wizard-step-active {
                padding: 18px;
                border: 1px solid var(--filing-border);
                border-radius: 12px;
                background: var(--filing-white);
                box-shadow: 0 4px 16px rgba(11, 31, 58, 0.06);
            }

            .filing-page.wizard-on section.filing-card[data-wizard-step].wizard-step-active {
                border-radius: 12px;
            }

            .filing-page.wizard-on section.filing-card[data-wizard-step] .filing-card__body {
                padding: 18px;
            }

            .filing-page.wizard-on .wizard-step-title {
                position: static;
                width: auto;
                height: auto;
                margin: 0 0 14px;
                overflow: visible;
                clip: auto;
                white-space: normal;
                color: var(--filing-navy);
                font-family: "Cormorant Garamond", Georgia, serif;
                font-size: 24px;
                font-weight: 700;
                line-height: 1.1;
            }

            /* The action bar's last button submits, so hide the in-page one */
            .filing-page.wizard-on .filing-submit {
                display: none;
            }

            /* Action bar */
            .filing-page.wizard-on .wizard-action-bar {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 16px calc(14px + env(safe-area-inset-bottom));
                border-top: 1px solid var(--filing-border);
                background: #fff;
                box-shadow: 0 -4px 20px rgba(11, 31, 58, 0.1);
            }

            .wizard-btn-back,
            .wizard-btn-next {
                display: inline-flex;
                flex: 1;
                min-height: 48px;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                font: inherit;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                transition: opacity 0.2s ease;
            }

            .wizard-btn-back {
                border: 1px solid var(--filing-border);
                background: #fcfbf8;
                color: var(--filing-navy);
            }

            .wizard-btn-next {
                border: 0;
                background: linear-gradient(135deg, var(--filing-gold), var(--filing-gold-light));
                color: var(--filing-navy);
                box-shadow: 0 6px 16px rgba(201, 168, 76, 0.3);
            }

            .wizard-btn-next:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                box-shadow: none;
            }

            .wizard-step-hint {
                position: absolute;
                top: -44px;
                right: 16px;
                left: 16px;
                padding: 8px 12px;
                border: 1px solid rgba(220, 38, 38, 0.3);
                border-radius: 6px;
                background: #fff;
                color: #b91c1c;
                font-size: 12px;
                text-align: center;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .filing-page.wizard-on [data-wizard-step].wizard-step-active {
                animation: none;
            }

            .wizard-progress-fill {
                transition: none;
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

            <div class="wizard-progress-header" aria-hidden="true">
                <div class="wizard-progress-meta">
                    <span id="wizard-step-counter">Step 1 of 7</span>
                    <span class="wizard-progress-title" id="wizard-step-label">Category</span>
                </div>
                <div class="wizard-progress-track">
                    <div class="wizard-progress-fill" id="wizard-progress-fill" style="width: 14.28%;"></div>
                </div>
            </div>

            <div class="wizard-draft-banner" id="wizard-draft-banner" hidden>
                <span>Resume your previous draft?</span>
                <div class="wizard-draft-actions">
                    <button type="button" id="wizard-draft-resume">Resume</button>
                    <button type="button" id="wizard-draft-reset" class="wizard-draft-reset">Start over</button>
                </div>
            </div>

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

                    <section class="filing-card" id="filing-category" data-wizard-step="1" role="group" aria-labelledby="step1-heading">
                        <div class="filing-card__header">
                            <span class="filing-card__icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 22V12h6v10"></path></svg>
                            </span>
                            <div>
                                <h2 id="step1-heading">What are you reporting?</h2>
                                <p>Choose the closest category so we can route it correctly.</p>
                            </div>
                        </div>
                        <div class="filing-card__body">
                            <div class="filing-field">
                                <label for="category">Complaint category <span>*</span></label>
                                <p class="filing-field__hint">Pick the main issue. You can add more context in the description.</p>
                                <select id="category" name="category" class="filing-select" required>
                                    <option value="">- Select a category -</option>
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

                    {{--
                        Public visibility is the resident's decision, and private
                        is the default. Real radio inputs rather than a switch,
                        because the choice is between two equally weighted
                        outcomes and both need to be readable before one is
                        picked.

                        Nothing in the staff or admin interface can change this
                        afterwards.
                    --}}
                    <section class="filing-card" id="filing-visibility" data-wizard-step="6" role="group" aria-labelledby="step6-heading">
                        <div class="filing-card__header">
                            <span class="filing-card__icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path><path d="M4 4l16 16"></path></svg>
                            </span>
                            <div>
                                <h2 id="step6-heading">Who can see this</h2>
                                <p>You decide this. No member of staff can change it later.</p>
                            </div>
                        </div>

                        <div class="filing-card__body">
                            <fieldset class="visibility-choice" @disabled(old('visibility', 'private') === 'public')>
                                <legend class="sr-only">Who can see this complaint</legend>

                                <label class="visibility-option">
                                    <input type="radio" name="visibility" value="private" @checked(old('visibility', 'private') === 'private')>
                                    <span class="visibility-option__body">
                                        <span class="visibility-option__title">Just me</span>
                                        <span class="visibility-option__note">Only you and the staff handling your report can see it. It will not appear on the public page.</span>
                                    </span>
                                </label>

                                <label class="visibility-option">
                                    <input type="radio" name="visibility" value="public" @checked(old('visibility') === 'public')>
                                    <span class="visibility-option__body">
                                        <span class="visibility-option__title">Publish on the transparency page</span>
                                        <span class="visibility-option__note">Anyone can read it there under your display alias, never your real name. You can make it private again at any time.</span>
                                    </span>
                                </label>
                            </fieldset>

                            @error('visibility') <div class="filing-error">{{ $message }}</div> @enderror

                            <p class="visibility-footnote">
                                Reports that turn out to be spam or unrelated to municipal services are held back automatically. Staff may also
                                remove sensitive details from the public page, but they cannot change this choice - and you will always see your
                                own report exactly as you filed it.
                            </p>
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
                            <div data-wizard-step="2" role="group" aria-labelledby="step2-heading">
                                <h3 id="step2-heading" class="wizard-step-title">Give your report a short title</h3>
                                <div class="filing-field">
                                    <label for="title">Short title <span>*</span></label>
                                    <p class="filing-field__hint">Summarize the problem in one sentence.</p>
                                    <input id="title" name="title" class="filing-input" type="text" maxlength="255" placeholder="e.g. Fallen tree blocking the road" value="{{ old('title') }}" required>
                                    @error('title') <div class="filing-error">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div data-wizard-step="3" role="group" aria-labelledby="step3-heading">
                                <h3 id="step3-heading" class="wizard-step-title">What happened?</h3>
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
                            </div>

                            <div data-wizard-step="4" role="group" aria-labelledby="step4-heading">
                                <h3 id="step4-heading" class="wizard-step-title">Add photo evidence</h3>
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
                            <div data-wizard-step="5" role="group" aria-labelledby="step5-heading">
                                <h3 id="step5-heading" class="wizard-step-title">Where is the issue?</h3>
                                <div class="filing-field">
                                    <label for="barangay">Which barangay <span>(required)</span></label>
                                    <p class="filing-field__hint">Prefilled from your account. Change it if the problem is somewhere else.</p>
                                    <input
                                        id="barangay"
                                        name="barangay"
                                        class="filing-input"
                                        type="search"
                                        list="complaint-barangay-options"
                                        autocomplete="off"
                                        value="{{ old('barangay', auth()->user()?->barangay ?? '') }}"
                                        required
                                    >
                                    {{--
                                        A native list alongside the field, so the form
                                        still completes without JavaScript and a phone
                                        gets its own picker.
                                    --}}
                                    <datalist id="complaint-barangay-options">
                                        @foreach (\App\Models\User::BARANGAYS as $barangayOption)
                                            <option value="{{ $barangayOption }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('barangay') <div class="filing-error">{{ $message }}</div> @enderror
                                </div>

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

                                <button type="button" class="filing-locate" id="locate-me" data-locate-button>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M12 2v3"></path><path d="M12 19v3"></path><path d="M2 12h3"></path><path d="M19 12h3"></path></svg>
                                    Use my current location
                                </button>
                                <p class="filing-locate-status" id="locate-status" role="status" aria-live="polite" hidden></p>

                                {{--
                                    Only present when a photo's own GPS and the pin the
                                    resident placed disagree by more than the rounding
                                    threshold.
                                --}}
                                @if ($locationConflict)
                                    <div class="location-choice">
                                        <h3 class="location-choice__title">Where is the problem?</h3>
                                        <p class="location-choice__note">One of your photos was taken in a different area from the point you marked on the map. Pick the place the problem is actually at.</p>

                                        <fieldset class="location-choice__options">
                                            <legend class="sr-only">Which location is the problem at?</legend>

                                            <label class="location-choice__option">
                                                <input type="radio" name="location_choice" value="map"
                                                       @checked(old('location_choice', \App\Http\Controllers\ComplaintController::LOCATION_CHOICE_MAP) === 'map')>
                                                <span>
                                                    <span class="location-choice__label">The point I marked on the map</span>
                                                    <span class="location-choice__coords">
                                                        {{ $locationConflict['map']['barangay'] ?? 'Location not recognised' }}
                                                    </span>
                                                </span>
                                            </label>

                                            <label class="location-choice__option">
                                                <input type="radio" name="location_choice" value="photo"
                                                       @checked(old('location_choice', \App\Http\Controllers\ComplaintController::LOCATION_CHOICE_PHOTO) === 'photo')>
                                                <span>
                                                    <span class="location-choice__label">Where the photo was taken</span>
                                                    <span class="location-choice__coords">
                                                        {{ $locationConflict['photo']['barangay'] ?? 'Location not recognised' }}
                                                    </span>
                                                </span>
                                            </label>
                                        </fieldset>

                                        @error('location_choice')
                                            <p class="location-choice__error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                                @error('latitude') <div class="filing-error">{{ $message }}</div> @enderror
                                @error('longitude') <div class="filing-error">{{ $message }}</div> @enderror
                            </div>

                            <div data-wizard-step="7" role="group" aria-labelledby="step7-heading">
                                <h3 id="step7-heading" class="wizard-step-title">Review &amp; submit</h3>
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
                        </div>
                    </section>

                    <div class="wizard-action-bar">
                        <div class="wizard-step-hint" id="wizard-step-hint" role="status" aria-live="polite" hidden></div>
                        <button type="button" class="wizard-btn-back" id="wizard-btn-back">Back</button>
                        <button type="button" class="wizard-btn-next" id="wizard-btn-next">Next</button>
                    </div>
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
    <script>
        (() => {
            const initializeFilingPage = () => {
                const form = document.getElementById('complaint-form');
                if (!form || form.dataset.filingInitialized === 'true') return;
                form.dataset.filingInitialized = 'true';

                const page = document.querySelector('.filing-page');
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

                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                const mapElement = document.getElementById('complaint-map');
                const barangayInput = document.getElementById('barangay');

                // Wizard shell elements
                const stepCounter = document.getElementById('wizard-step-counter');
                const stepLabel = document.getElementById('wizard-step-label');
                const progressFill = document.getElementById('wizard-progress-fill');
                const draftBanner = document.getElementById('wizard-draft-banner');
                const draftResumeBtn = document.getElementById('wizard-draft-resume');
                const btnBack = document.getElementById('wizard-btn-back');
                const btnNext = document.getElementById('wizard-btn-next');
                const stepHint = document.getElementById('wizard-step-hint');

                // The wizard only exists on phones. Desktop keeps the normal form.
                // jsdom has no matchMedia (the script-runtime audit runs this page
                // there); a desktop-shaped stub is the honest fallback.
                const mobileQuery = typeof window.matchMedia === 'function'
                    ? window.matchMedia('(max-width: 640px)')
                    : { matches: false };
                const isWizardMode = () => mobileQuery.matches;

                // Scope hook so CSS can hide the site footer on this page only.
                document.body.classList.add('page-complaint-create');
                document.addEventListener('livewire:navigating', () => {
                    document.body.classList.remove('page-complaint-create');
                }, { once: true });

                const currentUserId = '{{ auth()->id() ?? 'guest' }}';
                const draftKey = `wizard:v1:${currentUserId}`;
                const totalSteps = 7;
                let currentStep = 1;
                let touchedBack = {};
                let touchedNext = {};

                const stepNames = {
                    1: 'Category',
                    2: 'Title',
                    3: 'What happened',
                    4: 'Photos',
                    5: 'Location',
                    6: 'Visibility',
                    7: 'Review & submit'
                };

                const stepConfigs = {
                    1: {
                        validate: () => {
                            const val = categorySelect?.value;
                            return { valid: Boolean(val), hint: 'Please select a complaint category.' };
                        }
                    },
                    2: {
                        validate: () => {
                            const val = document.getElementById('title')?.value?.trim();
                            return { valid: Boolean(val), hint: 'Enter a short title for the problem.' };
                        }
                    },
                    3: {
                        validate: () => {
                            const val = document.getElementById('description')?.value?.trim();
                            const len = val?.length || 0;
                            return { valid: len >= 20, hint: `Enter at least 20 characters (${Math.max(0, 20 - len)} more needed).` };
                        }
                    },
                    4: {
                        validate: () => {
                            const count = selectedPhotos.length;
                            return { valid: count >= 1, hint: 'Add at least one clear photo.' };
                        }
                    },
                    5: {
                        validate: () => {
                            const barangay = barangayInput?.value?.trim();
                            return { valid: Boolean(barangay), hint: 'Please enter or select a barangay.' };
                        }
                    },
                    6: {
                        validate: () => ({ valid: true, hint: '' })
                    },
                    7: {
                        validate: () => {
                            const terms = document.getElementById('terms')?.checked;
                            return { valid: Boolean(terms), hint: 'You must confirm policy and accuracy.' };
                        }
                    }
                };

                const saveDraft = () => {
                    try {
                        const data = {
                            category: categorySelect?.value || '',
                            title: document.getElementById('title')?.value || '',
                            description: document.getElementById('description')?.value || '',
                            barangay: barangayInput?.value || '',
                            address_text: addressInput?.value || '',
                            latitude: latitudeInput?.value || '',
                            longitude: longitudeInput?.value || '',
                            visibility: document.querySelector('input[name="visibility"]:checked')?.value || 'private',
                            location_choice: document.querySelector('input[name="location_choice"]:checked')?.value || '',
                            terms: document.getElementById('terms')?.checked || false
                        };
                        sessionStorage.setItem(draftKey, JSON.stringify(data));
                    } catch (e) {}
                };

                const loadDraft = () => {
                    try {
                        const raw = sessionStorage.getItem(draftKey);
                        if (!raw) return null;
                        return JSON.parse(raw);
                    } catch (e) {
                        return null;
                    }
                };

                const applyDraft = (draft) => {
                    if (!draft) return;
                    if (categorySelect && draft.category) {
                        categorySelect.value = draft.category;
                        categorySelect.dispatchEvent(new Event('change'));
                    }
                    const titleEl = document.getElementById('title');
                    if (titleEl && draft.title) titleEl.value = draft.title;
                    const descEl = document.getElementById('description');
                    if (descEl && draft.description) descEl.value = draft.description;
                    if (barangayInput && draft.barangay) barangayInput.value = draft.barangay;
                    if (addressInput && draft.address_text) addressInput.value = draft.address_text;
                    if (latitudeInput && draft.latitude) latitudeInput.value = draft.latitude;
                    if (longitudeInput && draft.longitude) longitudeInput.value = draft.longitude;
                    if (draft.visibility) {
                        const vis = document.querySelector(`input[name="visibility"][value="${draft.visibility}"]`);
                        if (vis) vis.checked = true;
                    }
                    if (draft.location_choice) {
                        const loc = document.querySelector(`input[name="location_choice"][value="${draft.location_choice}"]`);
                        if (loc) loc.checked = true;
                    }
                    const termsEl = document.getElementById('terms');
                    if (termsEl && draft.terms) termsEl.checked = true;
                    updateSummary();
                    updateWizardUI();
                };

                const getErrorStep = () => {
                    @if($errors->has('category'))
                        return 1;
                    @endif
                    @if($errors->has('title'))
                        return 2;
                    @endif
                    @if($errors->has('description'))
                        return 3;
                    @endif
                    @if($errors->has('images') || $errors->has('camera_photo') || $errors->has('image'))
                        return 4;
                    @endif
                    @if($errors->has('barangay') || $errors->has('address_text') || $errors->has('latitude') || $errors->has('longitude') || $errors->has('location_choice'))
                        return 5;
                    @endif
                    @if($errors->has('visibility'))
                        return 6;
                    @endif
                    @if($errors->has('terms'))
                        return 7;
                    @endif
                    return 0;
                };

                const updateWizardUI = () => {
                    const wizardOn = isWizardMode();
                    page?.classList.toggle('wizard-on', wizardOn);

                    // Hidden required fields would silently block submit on mobile.
                    // The server validates everything again, so skip native checks there.
                    form.noValidate = wizardOn;

                    if (!wizardOn) {
                        // Desktop: show everything, exactly like the original form.
                        document.querySelectorAll('[data-wizard-step]').forEach((el) => {
                            el.classList.remove('wizard-step-active');
                            el.removeAttribute('hidden');
                        });
                        return;
                    }

                    document.querySelectorAll('[data-wizard-step]').forEach((el) => {
                        const stepNum = Number.parseInt(el.getAttribute('data-wizard-step'), 10);
                        if (stepNum === currentStep) {
                            el.classList.add('wizard-step-active');
                            el.removeAttribute('hidden');
                        } else {
                            el.classList.remove('wizard-step-active');
                            el.setAttribute('hidden', 'true');
                        }
                    });

                    if (stepCounter) stepCounter.textContent = `Step ${currentStep} of ${totalSteps}`;
                    if (stepLabel) stepLabel.textContent = stepNames[currentStep] || '';
                    if (progressFill) progressFill.style.width = `${(currentStep / totalSteps) * 100}%`;

                    if (btnBack) {
                        btnBack.style.display = currentStep === 1 ? 'none' : 'inline-flex';
                    }

                    if (btnNext) {
                        if (currentStep === totalSteps) {
                            btnNext.textContent = 'Submit for department review';
                            btnNext.type = 'submit';
                        } else {
                            btnNext.textContent = 'Next';
                            btnNext.type = 'button';
                        }
                    }

                    const config = stepConfigs[currentStep];
                    if (config) {
                        const res = config.validate();
                        if (btnNext) {
                            btnNext.disabled = currentStep < totalSteps ? !res.valid : false;
                        }
                        if (stepHint) {
                            if (!res.valid && res.hint && touchedNext[currentStep]) {
                                stepHint.textContent = res.hint;
                                stepHint.hidden = false;
                            } else {
                                stepHint.hidden = true;
                            }
                        }
                    }

                    if (currentStep === 5 && window.invalidateComplaintMap) {
                        window.invalidateComplaintMap();
                    }
                };

                const goToStep = (step, updateHash = true) => {
                    if (!isWizardMode()) return;
                    if (step < 1 || step > totalSteps) return;
                    currentStep = step;
                    updateWizardUI();
                    if (updateHash) {
                        window.location.hash = `#step-${currentStep}`;
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                };

                let categoryToDepartment = {};

                try {
                    categoryToDepartment = JSON.parse(categoryDataElement?.textContent || '{}');
                } catch (error) {
                    categoryToDepartment = {};
                }

                const updateSummary = () => {
                    const categoryLabel = categorySelect?.selectedOptions?.[0]?.textContent?.trim();
                    const department = categoryToDepartment[categorySelect?.value || ''];
                    reviewCategory.textContent = categoryLabel && categoryLabel !== '- Select a category -' ? categoryLabel : 'Not selected';
                    reviewDepartment.textContent = department || 'Not routed yet';

                    const description = addressInput?.value?.trim();
                    const barangay = barangayInput?.value?.trim();

                    reviewLocation.textContent = [barangay, description]
                        .filter(Boolean)
                        .join(' · ') || 'No location added';
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
                barangayInput?.addEventListener('input', updateSummary);

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
                    updateWizardUI();
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

                const updateCoordinates = (latitude, longitude) => {
                    if (latitudeInput) latitudeInput.value = latitude.toFixed(8);
                    if (longitudeInput) longitudeInput.value = longitude.toFixed(8);
                    updateSummary();
                };

                const showMapUnavailable = () => {
                    if (!mapElement) return;
                    mapElement.innerHTML = '<div style="padding:20px;text-align:center;color:#64748b;font-size:12px">Map unavailable. You can still submit using the address or landmark field.</div>';
                };

                const initializeMap = () => {
                    if (!mapElement) return;

                    if (map) {
                        map.remove();
                        map = null;
                        marker = null;
                    }

                    const keepMapSized = (target) => {
                        if (!target) return;

                        const refresh = () => target.invalidateSize({ animate: false });

                        window.requestAnimationFrame(refresh);
                        window.setTimeout(refresh, 300);
                        window.addEventListener('resize', refresh);
                        window.addEventListener('orientationchange', () => {
                            window.setTimeout(refresh, 150);
                        });
                    };
                    map = window.L.map(mapElement).setView([14.1153, 122.9553], 14);
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 18,
                    }).addTo(map);

                    window.invalidateComplaintMap = () => {
                        if (map) map.invalidateSize({ animate: false });
                    };

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

                    keepMapSized(map);

                    const locateButton = document.getElementById('locate-me');
                    const locateStatus = document.getElementById('locate-status');

                    const sayLocating = (message) => {
                        if (!locateStatus) return;
                        locateStatus.textContent = message;
                        locateStatus.hidden = false;
                    };

                    const locateFailures = {
                        1: 'Location permission was declined. You can still place the pin on the map.',
                        2: 'Your location is unavailable right now. Please place the pin on the map.',
                        3: 'Finding your location took too long. Please place the pin on the map.',
                    };

                    if (locateButton) {
                        locateButton.addEventListener('click', () => {
                            if (!navigator.geolocation) {
                                sayLocating('This browser cannot share your location. Please place the pin on the map.');
                                return;
                            }

                            locateButton.disabled = true;
                            sayLocating('Finding your location...');

                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    locateButton.disabled = false;

                                    const { latitude, longitude, accuracy } = position.coords;
                                    placeMarker(latitude, longitude, true);

                                    const within = Number.isFinite(accuracy)
                                        ? ` It is accurate to about ${Math.round(accuracy)} m.`
                                        : '';

                                    sayLocating(
                                        `Pin moved to where you are.${within} `
                                        + 'Drag the pin if the problem is a little way from you.',
                                    );
                                },
                                (error) => {
                                    locateButton.disabled = false;

                                    if (error.code === 1 && !window.isSecureContext) {
                                        sayLocating(
                                            'Your browser only shares a location over a secure (https) connection. '
                                            + 'Please place the pin on the map.',
                                        );
                                        return;
                                    }

                                    sayLocating(
                                        locateFailures[error.code]
                                        ?? 'Your location could not be read. Please place the pin on the map.',
                                    );
                                },
                                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
                            );
                        });
                    }
                };

                window.whenLeafletReady(initializeMap, { onUnavailable: showMapUnavailable });

                // Initial step: server errors win, then the URL hash (mobile only)
                const serverErrStep = getErrorStep();
                const initialHashMatch = window.location.hash.match(/^#step-(\d+)$/);
                const hashStep = initialHashMatch ? Number.parseInt(initialHashMatch[1], 10) : null;

                if (serverErrStep > 0) {
                    currentStep = serverErrStep;
                } else if (isWizardMode() && hashStep && hashStep >= 1 && hashStep <= totalSteps) {
                    currentStep = hashStep;
                } else {
                    currentStep = 1;
                }

                // Draft banner: only when the draft has real content
                const existingDraft = loadDraft();
                @if(!$errors->any())
                if (existingDraft && draftBanner) {
                    const meaningful = ['category', 'title', 'description', 'address_text']
                        .filter((key) => existingDraft[key]).length;
                    if (meaningful >= 2) {
                        draftBanner.hidden = false;
                    }
                }
                @endif

                draftResumeBtn?.addEventListener('click', () => {
                    applyDraft(existingDraft);
                    if (draftBanner) draftBanner.hidden = true;
                });

                const draftResetBtn = document.getElementById('wizard-draft-reset');
                draftResetBtn?.addEventListener('click', () => {
                    try {
                        sessionStorage.removeItem(draftKey);
                    } catch (e) {}
                    if (draftBanner) draftBanner.hidden = true;
                });

                // Auto-save on inputs
                form.addEventListener('input', () => {
                    saveDraft();
                    updateWizardUI();
                });
                form.addEventListener('change', () => {
                    saveDraft();
                    updateWizardUI();
                });

                btnNext?.addEventListener('click', (e) => {
                    touchedNext[currentStep] = true;
                    const config = stepConfigs[currentStep];
                    if (config) {
                        const res = config.validate();
                        if (!res.valid) {
                            e.preventDefault();
                            if (stepHint) {
                                stepHint.textContent = res.hint;
                                stepHint.hidden = false;
                            }
                            return;
                        }
                    }
                    if (currentStep < totalSteps) {
                        goToStep(currentStep + 1);
                    }
                });

                btnBack?.addEventListener('click', () => {
                    touchedBack[currentStep] = true;
                    if (currentStep > 1) {
                        goToStep(currentStep - 1);
                    }
                });

                window.addEventListener('hashchange', () => {
                    if (!isWizardMode()) return;
                    const match = window.location.hash.match(/^#step-(\d+)$/);
                    if (match) {
                        const s = Number.parseInt(match[1], 10);
                        if (s >= 1 && s <= totalSteps && s !== currentStep) {
                            currentStep = s;
                            updateWizardUI();
                        }
                    }
                });

                // Auto-advance for tile-like steps (category and visibility), phone only
                categorySelect?.addEventListener('change', () => {
                    if (isWizardMode() && currentStep === 1 && !touchedBack[1] && categorySelect.value) {
                        window.setTimeout(() => {
                            if (currentStep === 1) goToStep(2);
                        }, 250);
                    }
                });

                document.querySelectorAll('input[name="visibility"]').forEach((radio) => {
                    radio.addEventListener('change', () => {
                        if (isWizardMode() && currentStep === 6 && !touchedBack[6] && radio.checked) {
                            window.setTimeout(() => {
                                if (currentStep === 6) goToStep(7);
                            }, 250);
                        }
                    });
                });

                // Rotate / resize between phone and desktop without losing anything
                if (mobileQuery.addEventListener) {
                    mobileQuery.addEventListener('change', updateWizardUI);
                } else if (mobileQuery.addListener) {
                    mobileQuery.addListener(updateWizardUI);
                }

                updateWizardUI();

                form.addEventListener('submit', (event) => {
                    if (selectedPhotos.length === 0) {
                        event.preventDefault();
                        showUploadError('Add at least one clear photo before submitting.');
                        goToStep(4);
                        return;
                    }

                    try {
                        sessionStorage.removeItem(draftKey);
                    } catch (e) {}

                    if (btnNext) btnNext.disabled = true;

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
