@extends($mainLayout)

@section('title', $complaint->ticket_id.' - Public Transparency - Daet Listens')

@push('styles')
    <style>
        .public-ticket-root {
            --pt-navy: #0b1f3a;
            --pt-navy-mid: #1B3A61;
            --pt-gold: #c9a84c;
            --pt-gold-light: #e2c06a;
            --pt-cream: #f5f0e8;
            --pt-cream-dark: #ede7d9;
            --pt-ink: #172b49;
            --pt-muted: #64748b;
            --pt-green: #2f9e68;
            min-height: 100vh;
            overflow: hidden;
            background: var(--pt-cream);
            color: var(--pt-ink);
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        }

        .public-ticket-root *,
        .public-ticket-root *::before,
        .public-ticket-root *::after {
            box-sizing: border-box;
        }

        .public-ticket-root h1,
        .public-ticket-root h2,
        .public-ticket-root h3,
        .public-ticket-root p {
            margin-top: 0;
        }

        .public-ticket-container {
            width: min(1180px, calc(100% - 80px));
            margin: 0 auto;
        }

        .public-ticket-header {
            position: relative;
            overflow: hidden;
            padding: 112px 40px 46px;
            background: var(--pt-navy);
            color: #fff;
        }

        .public-ticket-header::before {
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

        .public-ticket-header::after {
            position: absolute;
            top: -260px;
            right: -120px;
            width: 560px;
            height: 560px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.1), transparent 68%);
            content: "";
            pointer-events: none;
        }

        .public-ticket-header__inner {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 36px;
        }

        .public-ticket-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.68);
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .public-ticket-back:hover {
            color: var(--pt-gold-light);
        }

        .public-ticket-back svg {
            width: 15px;
            height: 15px;
        }

        .public-ticket-heading {
            max-width: 820px;
        }

        .public-ticket-id-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
            margin-bottom: 13px;
        }

        .public-ticket-id {
            color: var(--pt-gold-light);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .public-ticket-status,
        .public-update-status {
            display: inline-flex;
            align-items: center;
            min-height: 23px;
            padding: 4px 8px;
            border: 1px solid transparent;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.2;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* How the department settled the report, stated rather than implied.
           "Report reviewed" and "Site attended" are different claims. */
        .public-verification {
            display: inline-flex;
            align-items: center;
            min-height: 23px;
            padding: 4px 8px;
            border: 1px solid rgba(201, 168, 76, 0.32);
            border-radius: 4px;
            background: rgba(201, 168, 76, 0.1);
            color: #e2c06a;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.2;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .public-verification--site_visit {
            border-color: rgba(63, 203, 111, 0.34);
            background: rgba(63, 203, 111, 0.12);
            color: #86efac;
        }

        .public-verification--duplicate_match {
            border-color: rgba(167, 139, 250, 0.32);
            background: rgba(167, 139, 250, 0.12);
            color: #c4b5fd;
        }

        .public-ticket-status--submitted {
            color: #bfdbfe;
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .public-ticket-status--under-review,
        .public-ticket-status--rejected {
            color: #fcd34d;
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.3);
        }

        .public-ticket-status--in-progress {
            color: #c4b5fd;
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .public-ticket-status--resolved {
            color: #86efac;
            background: rgba(63, 203, 111, 0.15);
            border-color: rgba(63, 203, 111, 0.3);
        }

        .public-ticket-status--closed {
            color: #d1d5db;
            background: rgba(156, 163, 175, 0.15);
            border-color: rgba(156, 163, 175, 0.28);
        }

        .public-ticket-heading h1 {
            max-width: 820px;
            margin-bottom: 12px;
            color: #fff;
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(42px, 5vw, 68px);
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 0.98;
        }

        .public-ticket-heading__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 18px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 11px;
        }

        .public-ticket-heading__meta span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .public-ticket-heading__meta svg {
            flex: 0 0 auto;
            color: var(--pt-gold);
        }

        .public-ticket-header__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 5px;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-decoration: none;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .public-ticket-header__action:hover {
            border-color: var(--pt-gold-light);
            background: rgba(255, 255, 255, 0.08);
            color: var(--pt-gold-light);
        }

        .public-ticket-main {
            display: grid;
            gap: 22px;
            padding: 40px 0 72px;
        }

        .public-ticket-panel {
            overflow: hidden;
            border: 1px solid rgba(201, 168, 76, 0.25);
            border-radius: 7px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(11, 31, 58, 0.05);
        }

        .public-ticket-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(11, 31, 58, 0.08);
        }

        .public-ticket-panel__header h2 {
            margin: 0;
            color: var(--pt-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 25px;
            font-weight: 700;
            line-height: 1;
        }

        .public-ticket-panel__header p {
            margin: 6px 0 0;
            color: var(--pt-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .public-ticket-panel__body {
            padding: 20px;
        }

        .public-progress-panel {
            padding: 20px;
        }

        .public-progress-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .public-progress-heading h2 {
            margin: 0;
            color: var(--pt-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 28px;
            line-height: 1;
        }

        .public-progress-heading p {
            margin: 6px 0 0;
            color: var(--pt-muted);
            font-size: 11px;
        }

        .public-progress-current {
            color: var(--pt-green);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .public-progress-track {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0;
        }

        .public-progress-step {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 9px;
            min-width: 0;
            text-align: center;
        }

        .public-progress-step:not(:last-child)::after {
            position: absolute;
            top: 14px;
            left: calc(50% + 17px);
            width: calc(100% - 34px);
            height: 1px;
            background: #d9e0e7;
            content: "";
        }

        .public-progress-step--complete:not(:last-child)::after {
            background: rgba(47, 158, 104, 0.55);
        }

        .public-progress-marker {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 29px;
            height: 29px;
            border: 1px solid #cbd5df;
            border-radius: 50%;
            background: #fff;
            color: #94a3b8;
        }

        .public-progress-marker svg {
            width: 14px;
            height: 14px;
        }

        .public-progress-step--complete .public-progress-marker {
            border-color: var(--pt-green);
            background: var(--pt-green);
            color: #fff;
        }

        .public-progress-step--current .public-progress-marker {
            border-color: var(--pt-gold);
            background: var(--pt-gold);
            color: var(--pt-navy);
            box-shadow: 0 0 0 4px rgba(201, 168, 76, 0.14);
        }

        .public-progress-step--rejected .public-progress-marker {
            border-color: #c47745;
            background: #c47745;
            color: #fff;
        }

        .public-progress-step strong {
            color: var(--pt-navy);
            font-size: 10px;
            font-weight: 700;
            line-height: 1.25;
        }

        .public-progress-step small {
            color: var(--pt-muted);
            font-size: 9px;
            line-height: 1.3;
        }

        .public-ticket-content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.75fr);
            align-items: start;
            gap: 22px;
        }

        .public-ticket-main-column,
        .public-ticket-side-column {
            display: grid;
            gap: 22px;
            min-width: 0;
        }

        .public-report-copy {
            margin: 0;
            color: var(--pt-ink);
            font-size: 13px;
            line-height: 1.75;
            white-space: normal;
        }

        .public-ticket-meta-list {
            display: grid;
            gap: 0;
            margin: 0;
        }

        .public-ticket-meta-list > div {
            display: grid;
            grid-template-columns: 108px minmax(0, 1fr);
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid rgba(11, 31, 58, 0.08);
        }

        .public-ticket-meta-list > div:first-child {
            padding-top: 0;
        }

        .public-ticket-meta-list > div:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .public-ticket-meta-list dt {
            color: var(--pt-muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .public-ticket-meta-list dd {
            min-width: 0;
            margin: 0;
            color: var(--pt-ink);
            font-size: 12px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .public-evidence-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
        }

        .public-evidence-link {
            display: block;
            overflow: hidden;
            aspect-ratio: 1;
            border: 1px solid rgba(201, 168, 76, 0.28);
            border-radius: 5px;
            background: var(--pt-cream-dark);
        }

        .public-evidence-link img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.2s ease;
        }

        .public-evidence-link:hover img {
            transform: scale(1.03);
        }

        .public-empty-state {
            display: grid;
            justify-items: center;
            gap: 8px;
            padding: 28px 16px;
            color: var(--pt-muted);
            text-align: center;
        }

        .public-empty-state svg {
            width: 25px;
            height: 25px;
            color: var(--pt-gold);
        }

        .public-empty-state strong {
            color: var(--pt-navy);
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: 21px;
        }

        .public-empty-state p {
            margin: 0;
            font-size: 11px;
            line-height: 1.5;
        }

        .public-update-list {
            display: grid;
            gap: 0;
        }

        .public-update {
            position: relative;
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 12px;
            padding: 0 0 22px;
        }

        .public-update:not(:last-child)::before {
            position: absolute;
            top: 18px;
            bottom: 0;
            left: 8px;
            width: 1px;
            background: #d9e0e7;
            content: "";
        }

        .public-update-marker {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 17px;
            height: 17px;
            border: 3px solid #fff;
            border-radius: 50%;
            background: var(--pt-gold);
            box-shadow: 0 0 0 1px rgba(201, 168, 76, 0.4);
        }

        .public-update-copy {
            min-width: 0;
        }

        .public-update-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 7px;
        }

        .public-update-status {
            min-height: 21px;
            padding: 3px 7px;
            color: #6e4c91;
            background: rgba(139, 104, 176, 0.11);
            border-color: rgba(139, 104, 176, 0.2);
        }

        .public-update-time {
            color: var(--pt-muted);
            font-size: 10px;
        }

        .public-update-copy p {
            margin: 0;
            color: var(--pt-ink);
            font-size: 12px;
            line-height: 1.6;
        }

        .public-update-evidence {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 7px;
            margin-top: 10px;
        }

        .public-privacy-note {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr);
            gap: 10px;
            padding: 16px;
            border: 1px solid rgba(201, 168, 76, 0.25);
            border-radius: 6px;
            background: var(--pt-cream);
        }

        .public-privacy-note svg {
            width: 22px;
            height: 22px;
            color: #9a741e;
        }

        .public-privacy-note strong {
            display: block;
            margin-bottom: 5px;
            color: var(--pt-navy);
            font-size: 11px;
        }

        .public-privacy-note p {
            margin: 0;
            color: var(--pt-muted);
            font-size: 10px;
            line-height: 1.55;
        }

        .public-ticket-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 40px;
            border-top: 1px solid rgba(201, 168, 76, 0.14);
            background: #060f1e;
            color: rgba(255, 255, 255, 0.38);
            font-size: 10px;
        }

        .public-ticket-footer strong {
            color: rgba(201, 168, 76, 0.72);
        }

        @media (max-width: 900px) {
            .public-ticket-header__inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .public-ticket-header__action {
                width: 100%;
            }

            .public-ticket-content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .public-ticket-container {
                width: calc(100% - 32px);
            }

            .public-ticket-header {
                padding: 104px 16px 34px;
            }

            .public-ticket-heading h1 {
                font-size: 43px;
            }

            .public-ticket-main {
                gap: 16px;
                padding: 24px 0 48px;
            }

            .public-ticket-panel__header,
            .public-progress-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
                padding: 16px;
            }

            .public-ticket-panel__body,
            .public-progress-panel {
                padding: 16px;
            }

            .public-progress-track {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .public-progress-step {
                grid-template-columns: 29px minmax(0, 1fr);
                justify-items: start;
                align-items: center;
                gap: 12px;
                min-height: 53px;
                text-align: left;
            }

            .public-progress-step:not(:last-child)::after {
                top: 29px;
                bottom: 0;
                left: 14px;
                width: 1px;
                height: auto;
            }

            .public-progress-step strong,
            .public-progress-step small {
                grid-column: 2;
            }

            .public-progress-marker {
                grid-column: 1;
                grid-row: 1 / span 2;
            }

            .public-evidence-grid,
            .public-update-evidence {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .public-ticket-meta-list > div {
                grid-template-columns: 92px minmax(0, 1fr);
                gap: 8px;
            }

            .public-ticket-footer {
                align-items: flex-start;
                flex-direction: column;
                padding: 16px;
            }
        }

        @media (max-width: 400px) {
            .public-ticket-container {
                width: calc(100% - 24px);
            }

            .public-ticket-heading h1 {
                font-size: 37px;
            }

            .public-evidence-grid,
            .public-update-evidence {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="public-ticket-root">
        <header class="public-ticket-header">
            <div class="public-ticket-container public-ticket-header__inner">
                <div class="public-ticket-heading">
                    <a href="{{ route('transparency') }}" class="public-ticket-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
                        Back to public transparency
                    </a>
                    <div class="public-ticket-id-row">
                        <span class="public-ticket-id">{{ $complaint->ticket_id }}</span>
                        <span class="public-ticket-status public-ticket-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                        @php $verificationMethod = $complaint->verificationMethodEnum(); @endphp
                        @if($verificationMethod)
                            <span class="public-verification public-verification--{{ str($verificationMethod->value)->slug() }}">
                                {{ $verificationMethod->label() }}
                            </span>
                        @endif
                    </div>
                    <h1>{{ $complaint->publicTitle() }}</h1>
                    <div class="public-ticket-heading__meta">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V4.5"></path><path d="M4 19.5h16"></path><path d="m7 15 3-4 3 2 4-6"></path></svg>
                            {{ $categoryLabel }}
                        </span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5l6-3 6 3v16"></path><path d="M9 9h1"></path><path d="M14 9h1"></path></svg>
                            {{ $complaint->department?->name ?? 'Department pending' }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('transparency') }}" class="public-ticket-header__action">Browse public complaints</a>
            </div>
        </header>

        <main class="public-ticket-container public-ticket-main">
            <section class="public-ticket-panel public-progress-panel" aria-labelledby="public-progress-heading">
                <div class="public-progress-heading">
                    <div>
                        <h2 id="public-progress-heading">Resolution progress</h2>
                        <p>Public dates for each stage of this complaint.</p>
                    </div>
                    <span class="public-progress-current">Current: {{ $complaint->statusEnum->label() }}</span>
                </div>
                <ol class="public-progress-track">
                    @foreach($progressSteps as $step)
                        <li class="public-progress-step public-progress-step--{{ $step['state'] }}">
                            <span class="public-progress-marker" aria-hidden="true">
                                @if($step['state'] === 'complete')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"></path></svg>
                                @elseif($step['state'] === 'rejected')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m7 7 10 10"></path><path d="m17 7-10 10"></path></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l2.5 1.5"></path></svg>
                                @endif
                            </span>
                            <strong>{{ $step['label'] }}</strong>
                            <small>{{ $step['date'] ?? 'Not started' }}</small>
                        </li>
                    @endforeach
                </ol>
            </section>

            <div class="public-ticket-content-grid">
                <div class="public-ticket-main-column">
                    <section class="public-ticket-panel" aria-labelledby="public-report-heading">
                        <div class="public-ticket-panel__header">
                            <div>
                                <h2 id="public-report-heading">Complaint report</h2>
                                <p>The public description shared by the resident, with the exact location field withheld.</p>
                            </div>
                        </div>
                        <div class="public-ticket-panel__body">
                            <p class="public-report-copy">{{ $description }}</p>
                        </div>
                    </section>

                    <section class="public-ticket-panel" aria-labelledby="public-evidence-heading">
                        <div class="public-ticket-panel__header">
                            <div>
                                <h2 id="public-evidence-heading">Resident evidence</h2>
                                <p>Photos submitted with the original report.</p>
                            </div>
                            <span class="public-ticket-id">{{ count($complaint->publicImagePaths()) }} {{ Str::plural('photo', count($complaint->publicImagePaths())) }}</span>
                        </div>
                        <div class="public-ticket-panel__body">
                            @if($complaint->publicImagePaths())
                                <div class="public-evidence-grid">
                                    @foreach($complaint->publicImagePaths() as $imageIndex => $imagePath)
                                        <a href="{{ route('transparency.complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" class="public-evidence-link" aria-label="Open resident evidence photo {{ $imageIndex + 1 }}">
                                            <img src="{{ route('transparency.complaints.evidence', [$complaint, $imageIndex]) }}" alt="Resident evidence photo {{ $imageIndex + 1 }}" loading="lazy">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="public-empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8.5" cy="9" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                                    <strong>No resident photos</strong>
                                    <p>This public report does not have attached evidence.</p>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="public-ticket-panel" aria-labelledby="public-updates-heading">
                        <div class="public-ticket-panel__header">
                            <div>
                                <h2 id="public-updates-heading">Public updates</h2>
                                <p>Status changes and documentation shared by the department.</p>
                            </div>
                        </div>
                        <div class="public-ticket-panel__body">
                            @if($logs->isEmpty())
                                <div class="public-empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                    <strong>No public updates yet</strong>
                                    <p>The next department update will appear here.</p>
                                </div>
                            @else
                                <div class="public-update-list">
                                    @foreach($logs as $log)
                                        @php
                                            $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status);
                                        @endphp
                                        <article class="public-update">
                                            <span class="public-update-marker" aria-hidden="true"></span>
                                            <div class="public-update-copy">
                                                <div class="public-update-meta">
                                                    <span class="public-update-status">{{ $updateStatus?->label() ?? $log->new_status }}</span>
                                                    <time class="public-update-time" datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->format('M j, Y g:i A') }}</time>
                                                </div>
                                                @if($log->comment)
                                                    <p>{{ $log->comment }}</p>
                                                @endif
                                                @if($log->documentation_images)
                                                    <div class="public-update-evidence" aria-label="Documentation for this update">
                                                        @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                            <a href="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" class="public-evidence-link" aria-label="Open documentation photo {{ $documentationIndex + 1 }}">
                                                                <img src="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="Documentation photo {{ $documentationIndex + 1 }}" loading="lazy">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <aside class="public-ticket-side-column">
                    <section class="public-ticket-panel" aria-labelledby="public-details-heading">
                        <div class="public-ticket-panel__header">
                            <div>
                                <h2 id="public-details-heading">Ticket details</h2>
                                <p>Public information for this report.</p>
                            </div>
                        </div>
                        <div class="public-ticket-panel__body">
                            <dl class="public-ticket-meta-list">
                                <div><dt>Ticket</dt><dd>{{ $complaint->ticket_id }}</dd></div>
                                <div><dt>Category</dt><dd>{{ $categoryLabel }}</dd></div>
                                <div><dt>Department</dt><dd>{{ $complaint->department?->name ?? 'Department pending' }}</dd></div>
                                <div><dt>Broad area</dt><dd>{{ $location }}</dd></div>
                                <div><dt>Reported</dt><dd>{{ $complaint->created_at?->format('M j, Y') }}</dd></div>
                                <div><dt>Public alias</dt><dd>{{ $reporter }}</dd></div>
                            </dl>
                        </div>
                    </section>

                    <section class="public-privacy-note" aria-label="Privacy information">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                        <div>
                            <strong>Privacy protected</strong>
                            <p>This page shows only a complaint shared publicly. Exact addresses, coordinates, names, and contact details are not displayed.</p>
                        </div>
                    </section>

                    <a href="{{ route('transparency') }}" class="public-ticket-header__action" style="background: transparent; color: var(--pt-navy); border-color: rgba(11, 31, 58, 0.2);">Return to complaint register</a>
                </aside>
            </div>
        </main>

        <footer class="public-ticket-footer">
            <span>© 2026 <strong>LGU Daet</strong> · Public Service Management System</span>
            <span>Public complaint record · Privacy-safe reporting</span>
        </footer>
    </div>
@endsection
