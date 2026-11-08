@extends($mainLayout)

@section('title', $complaint->ticket_id.' - '.__('transparency.show.title'))

@push('styles')
    <style>
        .pt {
            /* The navbar is fixed; offset like every other shell. */
            padding-top: var(--navbar-height, 64px);
            background: var(--bg);
            color: var(--text);
            font-family: var(--font-sans);
            font-size: 15px;
            line-height: 1.5;
        }
        .pt *, .pt *::before, .pt *::after { box-sizing: border-box; }
        .pt h1, .pt h2, .pt p, .pt dl, .pt dd, .pt ol, .pt ul { margin: 0; padding: 0; }
        .pt ol, .pt ul { list-style: none; }

        .pt-container { width: 100%; max-width: 1040px; margin: 0 auto; padding: 0 24px; }
        @media (max-width: 767px) { .pt-container { padding: 0 16px; } }

        /* ── Back link: light background, navy text, 44px target ── */
        .pt-back-row { background: var(--bg); padding: 12px 0; }
        @media (max-width: 767px) { .pt-back-row { padding: 8px 0; } }
        .pt-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            color: #17305a;
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            text-decoration: none;
        }
        .pt-back:hover { text-decoration: underline; }
        .pt-back:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(212, 169, 71, .35); border-radius: 8px; }
        .pt-back svg { width: 16px; height: 16px; flex: none; }

        /* ── Banner: full-bleed gradient, zero radius ── */
        .pt-banner {
            background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 62%, var(--navy-600) 100%),
                repeating-linear-gradient(135deg, rgba(255, 255, 255, .035) 0 1px, transparent 1px 14px);
            background-blend-mode: normal;
            color: #fff;
            padding: 24px 0 48px;
            border-radius: 0;
        }
        @media (max-width: 767px) { .pt-banner { padding: 20px 0 48px; } }
        .pt-banner__ticket-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 12px; }
        .pt-ticket-pill {
            display: inline-flex;
            align-items: center;
            height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .02em;
            color: #fff;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .28);
            white-space: nowrap;
        }
        .pt-copy-btn {
            width: 44px; height: 44px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: 50%;
            background: transparent; color: #fff; cursor: pointer;
        }
        .pt-copy-btn:hover { background: rgba(255, 255, 255, .12); }
        .pt-copy-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(212, 169, 71, .35); }
        .pt-copy-btn svg { width: 20px; height: 20px; }
        .pt-banner__title {
            margin: 0 0 12px;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            color: #fff;
            letter-spacing: -0.01em;
            overflow-wrap: anywhere;
        }
        @media (max-width: 767px) { .pt-banner__title { font-size: 24px; line-height: 1.25; } }
        .pt-goldbar { display: block; width: 40px; height: 3px; border-radius: 2px; background: var(--gold-500); margin: 0 0 12px; }
        .pt-banner__status-row { display: flex; flex-wrap: wrap; gap: 8px 12px; align-items: center; }
        .pt-chip-neutral {
            display: inline-flex;
            align-items: center;
            height: 28px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .35);
            background: transparent;
            color: #fff;
            font-size: 12px;
            line-height: 16px;
            font-weight: 600;
            letter-spacing: 0;
            text-transform: none;
            white-space: nowrap;
        }

        /* ── Pills (same recipe as .status-pill, scoped) ── */
        .pt-pill { display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 10px; border-radius: var(--radius-pill); font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap; }
        .pt-pill__dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .pt-pill--submitted    { background: var(--status-submitted-bg); color: var(--status-submitted-text); }
        .pt-pill--submitted .pt-pill__dot { background: var(--status-submitted-dot); }
        .pt-pill--under-review { background: var(--status-review-bg); color: var(--status-review-text); }
        .pt-pill--under-review .pt-pill__dot, .pt-pill--verified .pt-pill__dot, .pt-pill--assigned .pt-pill__dot { background: var(--status-review-dot); }
        .pt-pill--verified, .pt-pill--assigned { background: var(--status-review-bg); color: var(--status-review-text); }
        .pt-pill--in-progress  { background: var(--status-progress-bg); color: var(--status-progress-text); }
        .pt-pill--in-progress .pt-pill__dot { background: var(--status-progress-dot); }
        .pt-pill--resolved     { background: var(--status-resolved-bg); color: var(--status-resolved-text); }
        .pt-pill--resolved .pt-pill__dot { background: var(--status-resolved-dot); }
        .pt-pill--rejected     { background: var(--status-rejected-bg); color: var(--status-rejected-text); }
        .pt-pill--rejected .pt-pill__dot { background: var(--status-rejected-dot); }
        .pt-pill--closed       { background: var(--status-closed-bg); color: var(--status-closed-text); }
        .pt-pill--closed .pt-pill__dot { background: var(--status-closed-dot); }

        /* ── Cards + grid ── */
        .pt-stack { display: flex; flex-direction: column; gap: 16px; padding: 0 0 48px; position: relative; z-index: 1; }
        @media (min-width: 768px) { .pt-stack { gap: 24px; } }
        .pt-card {
            background: var(--surface);
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 20px;
        }
        @media (min-width: 768px) { .pt-card { padding: 24px; } }
        .pt-card h2 {
            font-size: 17px;
            line-height: 24px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 16px;
        }

        .pt-grid { display: contents; }
        .pt-col { display: contents; }
        .pt-order-progress { order: 1; margin-top: -24px; }
        .pt-order-details  { order: 2; }
        .pt-order-report   { order: 3; }
        .pt-order-evidence { order: 4; }
        .pt-order-updates  { order: 5; }
        .pt-order-privacy  { order: 6; }

        @media (min-width: 1024px) {
            .pt-stack { display: grid; grid-template-columns: minmax(0, 1fr) 320px; column-gap: 24px; row-gap: 24px; align-items: start; }
            .pt-order-progress { order: unset; grid-column: 1 / -1; }
            .pt-order-details  { order: unset; }
            .pt-order-report   { order: unset; }
            .pt-order-evidence { order: unset; }
            .pt-order-updates  { order: unset; }
            .pt-order-privacy  { order: unset; }
            .pt-col { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
            .pt-col--side { position: sticky; top: calc(var(--navbar-h, 64px) + 16px); }
        }

        /* ── Progress stepper (desktop: horizontal / mobile: vertical list) ── */
        .pt-steps { list-style: none; margin: 0; padding: 0; display: flex; }
        .pt-step {
            position: relative;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 8px;
            min-width: 0;
            padding: 0 4px;
        }
        .pt-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 16px;
            left: calc(50% + 20px);
            width: calc(100% - 40px);
            height: 2px;
            background: var(--line);
        }
        .pt-step--complete:not(:last-child)::after { background: var(--navy-700); }
        .pt-step__marker {
            position: relative;
            z-index: 1;
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 2px solid #94a3b8;
            font-size: 13px;
            line-height: 1;
            font-weight: 600;
            color: #475569;
            font-variant-numeric: tabular-nums;
            flex: none;
        }
        .pt-step__marker svg { width: 15px; height: 15px; }
        .pt-step--complete .pt-step__marker { background: var(--navy-700); border-color: var(--navy-700); color: #fff; }
        .pt-step--current .pt-step__marker { background: var(--gold-500); border-color: var(--gold-500); color: var(--navy-900); box-shadow: 0 0 0 4px rgba(212, 169, 71, .28); }
        .pt-step__current-dot { display: block; width: 10px; height: 10px; border-radius: 50%; background: var(--navy-900); }
        .pt-step--rejected .pt-step__marker { background: var(--status-rejected-dot); border-color: var(--status-rejected-dot); color: #fff; }
        .pt-step__text { display: flex; flex-direction: column; gap: 2px; }
        .pt-step__label { font-size: 15px; line-height: 20px; font-weight: 600; color: var(--text); }
        .pt-step__caption { font-size: 13px; line-height: 18px; font-weight: 400; color: var(--muted); font-variant-numeric: tabular-nums; }

        @media (max-width: 767px) {
            .pt-steps { flex-direction: column; }
            .pt-step {
                display: grid;
                grid-template-columns: 32px minmax(0, 1fr);
                column-gap: 16px;
                align-items: center;
                justify-items: start;
                height: 56px;
                padding: 0;
                text-align: left;
            }
            .pt-step:not(:last-child)::after {
                top: calc(50% + 16px);
                left: 15px;
                bottom: auto;
                width: 2px;
                height: calc(100% - 32px);
            }
            .pt-step__text { justify-self: start; }
        }

        /* ── What this means ── */
        .pt-whatmeans {
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 8px;
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 12px;
            background: var(--navy-50);
            color: var(--text);
            font-size: 14px;
            line-height: 1.5;
        }
        .pt-whatmeans svg { width: 18px; height: 18px; color: var(--navy-700); align-self: start; margin-top: 1px; }
        .pt-whatmeans p { margin: 0; }

        /* ── Report ── */
        .pt-copy { color: var(--text); font-size: 15px; line-height: 1.6; overflow-wrap: anywhere; }

        /* ── Evidence ── */
        .pt-count { color: var(--muted); font-size: 13px; font-weight: 500; margin-left: 6px; }
        .pt-evidence { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        @media (max-width: 640px) { .pt-evidence { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .pt-evidence a { display: block; aspect-ratio: 1; overflow: hidden; border: 1px solid var(--line); border-radius: 8px; background: var(--map-loading-bg); }
        .pt-evidence img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* ── Updates ── */
        .pt-updates { display: flex; flex-direction: column; gap: 20px; margin: 0; padding: 0; list-style: none; }
        .pt-update { position: relative; display: grid; grid-template-columns: 12px minmax(0, 1fr); column-gap: 12px; padding-bottom: 0; }
        .pt-update:not(:last-child)::before { content: ""; position: absolute; left: 5px; top: 24px; bottom: -2px; width: 2px; background: var(--line); }
        .pt-update__marker {
            align-self: start;
            width: 12px; height: 12px;
            margin-top: 6px;
            border-radius: 50%;
            border: 2px solid #64748b;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        .pt-update--newest .pt-update__marker { border-color: var(--navy-700); background: var(--navy-700); }
        .pt-update__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 8px; }
        .pt-update__meta time { color: var(--muted); font-size: 13px; line-height: 1; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .pt-update__text { color: var(--text); font-size: 15px; line-height: 1.5; overflow-wrap: anywhere; }
        .pt-update .pt-evidence { margin-top: 12px; max-width: 420px; }
        .pt-updates-more { margin-top: 20px; border-top: 1px solid var(--line-soft); padding-top: 16px; }
        .pt-updates-more summary {
            display: inline-flex; align-items: center; gap: 8px;
            min-height: 44px; font-size: 14px; font-weight: 600; color: var(--navy-700);
            cursor: pointer; list-style: none; border-radius: 8px; padding: 0 4px;
        }
        .pt-updates-more summary::-webkit-details-marker { display: none; }
        .pt-updates-more summary:hover { text-decoration: underline; }
        .pt-updates-more summary:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(212, 169, 71, .35); }
        .pt-updates-more summary svg { width: 16px; height: 16px; transition: transform .18s ease; }
        .pt-updates-more[open] summary svg { transform: rotate(45deg); }
        .pt-updates-more .pt-updates { margin-top: 16px; }

        .pt-empty { display: grid; justify-items: center; gap: 4px; padding: 24px 8px; text-align: center; color: var(--muted); font-size: 14px; }
        .pt-empty svg { width: 28px; height: 28px; color: var(--faint); margin-bottom: 4px; }
        .pt-empty strong { color: var(--text); font-size: 15px; font-weight: 600; }

        /* ── Details (mobile 2-col, desktop single column) ── */
        .pt-dl { display: grid; grid-template-columns: 1fr; }
        .pt-dl > div { padding: 12px 0; border-bottom: 1px solid #eef2f7; }
        .pt-dl > div:first-child { padding-top: 0; }
        .pt-dl > div:last-child, .pt-dl > div.pt-dl--full:last-child { padding-bottom: 0; border-bottom: 0; }
        .pt-dl dt { font-size: 12px; line-height: 16px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin: 0 0 4px; }
        .pt-dl dd { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--text); overflow-wrap: anywhere; }
        .pt-dl .dept-badge { flex: 0 0 auto; }
        .pt-dl .cat-icon { width: 28px; height: 28px; }
        .pt-dl .cat-icon svg { width: 15px; height: 15px; }
        @media (max-width: 767px) {
            .pt-dl { grid-template-columns: 1fr 1fr; column-gap: 16px; }
            .pt-dl > div.pt-dl--area, .pt-dl > div.pt-dl--full { grid-column: 1 / -1; }
        }

        .dept-badge {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 2px 8px; border-radius: 6px;
            background: var(--dept-tint, var(--dept-fallback-tint));
            color: var(--dept-accent, var(--dept-fallback));
            font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            line-height: 1.4; white-space: nowrap;
        }

        /* ── Privacy note (inline, no card) ── */
        .pt-privacy {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 8px 0 32px;
            color: var(--muted);
            font-size: 13px;
            line-height: 20px;
            max-width: 640px;
        }
        @media (min-width: 768px) { .pt-privacy { margin-top: 0; } }
        .pt-privacy svg { width: 16px; height: 16px; flex: none; color: var(--muted); margin-top: 2px; }
        .pt-privacy p { margin: 0; }

        @media (max-width: 640px) { .pt-steps { } }
    </style>
@endpush

@section('content')
    @php
        $verificationMethod = $complaint->verificationMethodEnum();
        $publicPhotoCount = count($complaint->publicImagePaths());
        $statusKey = str($complaint->status)->slug('_');
        $visibleLogs = $logs->take(5)->values();
        $earlierLogs = $logs->slice(5)->values();
        $lastUpdated = $logs->max('created_at');
        $previous = url()->previous();
        $previousPath = parse_url($previous, PHP_URL_PATH) ?? '';
        $cameFromList = str_starts_with($previousPath, '/transparency/complaints') && $previousPath !== '/transparency/complaints/' . $complaint->id;
        $backUrl = $cameFromList ? $previous : route('transparency.complaints');
        $backLabel = $cameFromList
            ? __('transparency.show.back_to_list')
            : __('transparency.show.back');
    @endphp

    <div class="pt">
        <div class="pt-back-row">
            <div class="pt-container">
                <a href="{{ $backUrl }}" class="pt-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                    {{ $backLabel }}
                </a>
            </div>
        </div>

        <header class="pt-banner">
            <div class="pt-container">
                <div class="pt-banner__ticket-row">
                    <span class="pt-ticket-pill">{{ $complaint->ticket_id }}</span>
                    <button type="button" class="pt-copy-btn" data-ticket="{{ $complaint->ticket_id }}" aria-label="{{ __('transparency.show.copy_ticket') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                    <span class="sr-only" id="copy-live" aria-live="polite"></span>
                </div>
                <h1 class="pt-banner__title">{{ $complaint->publicTitle() }}</h1>
                <span class="pt-goldbar" aria-hidden="true"></span>
                <div class="pt-banner__status-row">
                    <span class="pt-pill pt-pill--{{ str($complaint->status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                    @if($verificationMethod)
                        <span class="pt-chip-neutral">{{ __('transparency.show.latest_action') }}: {{ $verificationMethod->label() }}</span>
                    @endif
                </div>
            </div>
        </header>

        <div class="pt-container pt-stack">

            {{-- Progress --}}
            <section class="pt-card pt-order-progress" aria-labelledby="pt-progress">
                <h2 id="pt-progress">{{ __('transparency.show.progress') }}</h2>
                @php
                    $stepCount = count($progressSteps);
                @endphp
                <ol class="pt-steps">
                    @foreach($progressSteps as $index => $step)
                        <li class="pt-step pt-step--{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
                            <span class="pt-step__marker" aria-hidden="true">
                                @if($step['state'] === 'complete')
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
                                @elseif($step['state'] === 'rejected')
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="m7 7 10 10"/><path d="m17 7-10 10"/></svg>
                                @elseif($step['state'] === 'current')
                                    <span class="pt-step__current-dot"></span>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <span class="pt-step__text">
                                <span class="pt-step__label">{{ $step['label'] }}</span>
                                <span class="pt-step__caption">
                                    @if($step['state'] === 'complete')
                                        {{ $step['date'] ?? __('transparency.show.done') }}
                                    @elseif($step['state'] === 'current')
                                        {{ __('transparency.show.current_stage') }}
                                    @elseif($step['state'] === 'rejected')
                                        {{ __('transparency.show.rejected_stage') }}
                                    @else
                                        {{ __('transparency.show.upcoming') }}
                                    @endif
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                @php
                    $office = $complaint->department?->name ?? __('transparency.show.department_pending');
                    $whatMeans = match ($statusKey) {
                        'submitted' => __('transparency.show.what_submitted'),
                        'under_review' => __('transparency.show.what_under_review'),
                        'in_progress' => __('transparency.show.what_in_progress', ['office' => $office]),
                        'resolved' => __('transparency.show.what_resolved'),
                        'closed' => __('transparency.show.what_closed'),
                        'rejected' => __('transparency.show.what_rejected'),
                        default => '',
                    };
                @endphp
                @if($whatMeans !== '')
                    <div class="pt-whatmeans">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <p>{{ $whatMeans }}</p>
                    </div>
                @endif
            </section>

            <div class="pt-grid">
                <div class="pt-col pt-col--main">
                    {{-- Report --}}
                    <section class="pt-card pt-order-report" aria-labelledby="pt-report">
                        <h2 id="pt-report">{{ __('transparency.show.report') }}</h2>
                        <p class="pt-copy">{{ $description }}</p>
                    </section>

                    {{-- Evidence: only when there is some --}}
                    @if($publicPhotoCount > 0)
                        <section class="pt-card pt-order-evidence" aria-labelledby="pt-evidence">
                            <h2 id="pt-evidence">
                                {{ __('transparency.show.evidence') }}
                                <span class="pt-count">{{ $publicPhotoCount }} {{ $publicPhotoCount === 1 ? __('transparency.show.photo') : __('transparency.show.photos') }}</span>
                            </h2>
                            <div class="pt-evidence">
                                @foreach($complaint->publicImagePaths() as $imageIndex => $imagePath)
                                    <a href="{{ route('transparency.complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('transparency.show.open_evidence_photo', ['index' => $imageIndex + 1]) }}">
                                        <img src="{{ route('transparency.complaints.evidence', [$complaint, $imageIndex]) }}" alt="{{ __('transparency.show.evidence_photo', ['index' => $imageIndex + 1]) }}" loading="lazy" width="420" height="420">
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Public updates: newest first, 5 visible, rest behind a disclosure --}}
                    <section class="pt-card pt-order-updates" aria-labelledby="pt-updates">
                        <h2 id="pt-updates">{{ __('transparency.show.updates') }}</h2>
                        @if($logs->isEmpty())
                            <div class="pt-empty">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <strong>{{ __('transparency.show.no_updates') }}</strong>
                                <span>{{ __('transparency.show.next_update') }}</span>
                            </div>
                        @else
                            <ul class="pt-updates">
                                @foreach($visibleLogs as $log)
                                    @php $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status); @endphp
                                    <li class="pt-update{{ $loop->first ? ' pt-update--newest' : '' }}">
                                        <span class="pt-update__marker" aria-hidden="true"></span>
                                        <div>
                                            <div class="pt-update__meta">
                                                <span class="pt-pill pt-pill--{{ str((string) $log->new_status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $updateStatus?->label() ?? $log->new_status }}</span>
                                                <time datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->translatedFormat('M j, Y g:i A') }}</time>
                                            </div>
                                            @if($log->comment)
                                                <p class="pt-update__text">{{ $log->comment }}</p>
                                            @endif
                                            @if($log->documentation_images)
                                                <div class="pt-evidence" aria-label="{{ __('transparency.show.documentation') }}">
                                                    @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                        <a href="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('transparency.show.open_documentation', ['index' => $documentationIndex + 1]) }}">
                                                            <img src="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('transparency.show.documentation_photo', ['index' => $documentationIndex + 1]) }}" loading="lazy" width="420" height="420">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if($earlierLogs->isNotEmpty())
                                <details class="pt-updates-more">
                                    <summary>
                                        {{ __('transparency.show.show_earlier_updates', ['count' => $earlierLogs->count()]) }}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </summary>
                                    <ul class="pt-updates">
                                        @foreach($earlierLogs as $log)
                                            @php $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status); @endphp
                                            <li class="pt-update">
                                                <span class="pt-update__marker" aria-hidden="true"></span>
                                                <div>
                                                    <div class="pt-update__meta">
                                                        <span class="pt-pill pt-pill--{{ str((string) $log->new_status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $updateStatus?->label() ?? $log->new_status }}</span>
                                                        <time datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->translatedFormat('M j, Y g:i A') }}</time>
                                                    </div>
                                                    @if($log->comment)
                                                        <p class="pt-update__text">{{ $log->comment }}</p>
                                                    @endif
                                                    @if($log->documentation_images)
                                                        <div class="pt-evidence" aria-label="{{ __('transparency.show.documentation') }}">
                                                            @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                                <a href="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('transparency.show.open_documentation', ['index' => $documentationIndex + 1]) }}">
                                                                    <img src="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('transparency.show.documentation_photo', ['index' => $documentationIndex + 1]) }}" loading="lazy" width="420" height="420">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        @endif
                    </section>
                </div>

                <aside class="pt-col pt-col--side">
                    {{-- Ticket details --}}
                    <section class="pt-card pt-order-details" aria-labelledby="pt-details">
                        <h2 id="pt-details">{{ __('transparency.show.details') }}</h2>
                        <dl class="pt-dl">
                            <div>
                                <dt>{{ __('transparency.show.labels.department') }}</dt>
                                <dd><span class="dept-badge" data-dept="{{ $complaint->department?->code }}">{{ $complaint->department?->code }}</span>{{ $complaint->department?->name ?? __('transparency.show.department_pending') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('transparency.show.labels.category') }}</dt>
                                <dd>
                                    @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                    {{ $categoryLabel }}
                                </dd>
                            </div>
                            <div class="pt-dl--area">
                                <dt>{{ __('transparency.show.labels.broad_area') }}</dt>
                                <dd>{{ $location }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('transparency.show.labels.reported') }}</dt>
                                <dd>{{ $complaint->created_at?->translatedFormat('M j, Y') }}</dd>
                            </div>
                            @if($lastUpdated)
                                <div>
                                    <dt>{{ __('transparency.show.labels.last_updated') }}</dt>
                                    <dd>{{ $lastUpdated->translatedFormat('M j, Y g:i A') }}</dd>
                                </div>
                            @endif
                            @if($reporter !== '' && $reporter !== 'Community member')
                                <div>
                                    <dt>{{ __('transparency.show.labels.public_alias') }}</dt>
                                    <dd>{{ $reporter }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>
                </aside>
            </div>

            {{-- Privacy note: inline, not a card --}}
            <p class="pt-privacy pt-order-privacy" role="note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <span>{{ __('transparency.show.privacy.copy') }}</span>
            </p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const btn = document.querySelector('.pt-copy-btn');
    const live = document.getElementById('copy-live');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        const text = btn.dataset.ticket || '';
        try { await navigator.clipboard.writeText(text); } catch (e) { /* clipboard may be unavailable (insecure context) */ }
        const original = btn.getAttribute('aria-label');
        btn.setAttribute('aria-label', @json(__('transparency.show.copied')));
        if (live) live.textContent = @json(__('transparency.show.copied'));
        setTimeout(() => {
            btn.setAttribute('aria-label', original);
            if (live) live.textContent = '';
        }, 2000);
    });
})();
</script>
@endpush
