@extends($mainLayout)

@section('title', $complaint->ticket_id.' - '.__('transparency.show.title'))

@push('styles')
    <style>
        .pt {
            min-height: 100%;
            /* Design tokens only — see resources/css/tokens.css */
            background: var(--bg);
            color: var(--text);
            font-family: var(--font-sans);
            font-size: 15px;
            line-height: 1.5;
        }

        .pt *, .pt *::before, .pt *::after { box-sizing: border-box; }
        .pt h1, .pt h2, .pt p, .pt dl, .pt dd, .pt ol, .pt ul { margin: 0; padding: 0; }
        .pt ol, .pt ul { list-style: none; }

        .pt-container {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 24px 24px 48px;
        }

        /* Back link */
        .pt-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            color: var(--navy-700);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .pt-back:hover { text-decoration: underline; }
        .pt-back:focus-visible,
        .pt-evidence a:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(212, 169, 71, .45);
            border-radius: 8px;
        }
        .pt-back svg { width: 16px; height: 16px; }

        /* Cards */
        .pt-card {
            background: var(--surface);
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 24px;
        }
        .pt-card h2 {
            font-size: 18px;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 16px;
        }

        /* Header card */
        .pt-head { margin-top: 4px; }
        .pt-head__row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 12px;
            margin-bottom: 12px;
        }
        .pt-ticket {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 14px;
            font-weight: 700;
            color: var(--navy-700);
            letter-spacing: .02em;
        }
        .pt-head h1 {
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -.01em;
            overflow-wrap: anywhere;
        }
        .pt-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 20px;
            margin-top: 12px;
            color: var(--muted);
            font-size: 14px;
        }
        .pt-meta span { display: inline-flex; align-items: center; gap: 8px; }
        .pt-meta svg { width: 16px; height: 16px; color: var(--navy-700); flex: none; }

        /* Pills — same recipe as .status-pill, scoped */
        .pt-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 26px;
            padding: 0 10px;
            border-radius: var(--radius-pill);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .pt-pill__dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .pt-pill--submitted   { background: var(--status-submitted-bg); color: var(--status-submitted-text); }
        .pt-pill--submitted .pt-pill__dot { background: var(--status-submitted-dot); }
        .pt-pill--under-review{ background: var(--status-review-bg); color: var(--status-review-text); }
        .pt-pill--under-review .pt-pill__dot { background: var(--status-review-dot); }
        .pt-pill--in-progress { background: var(--status-progress-bg); color: var(--status-progress-text); }
        .pt-pill--in-progress .pt-pill__dot { background: var(--status-progress-dot); }
        .pt-pill--resolved    { background: var(--status-resolved-bg); color: var(--status-resolved-text); }
        .pt-pill--resolved .pt-pill__dot { background: var(--status-resolved-dot); }
        .pt-pill--rejected    { background: var(--status-rejected-bg); color: var(--status-rejected-text); }
        .pt-pill--rejected .pt-pill__dot { background: var(--status-rejected-dot); }
        .pt-pill--closed      { background: var(--status-closed-bg); color: var(--status-closed-text); }
        .pt-pill--closed .pt-pill__dot { background: var(--status-closed-dot); }
        .pt-pill--verify      { background: var(--status-review-bg); color: var(--status-review-text); }
        .pt-pill--site-visit  { background: var(--status-resolved-bg); color: var(--status-resolved-text); }
        .pt-pill--duplicate-match { background: var(--cat-noise-bg); color: var(--cat-noise-fg); }

        .pt-stack { display: grid; gap: 16px; margin-top: 16px; }

        /* Progress */
        .pt-steps {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
        .pt-step {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 8px;
            text-align: center;
            min-width: 0;
        }
        .pt-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 15px;
            left: calc(50% + 20px);
            width: calc(100% - 40px);
            height: 2px;
            background: var(--border);
        }
        .pt-step--complete:not(:last-child)::after { background: var(--navy-700); }
        .pt-dot {
            position: relative;
            z-index: 1;
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--surface);
            color: var(--faint);
        }
        .pt-dot svg { width: 15px; height: 15px; }
        .pt-step--complete .pt-dot { background: var(--navy-700); border-color: var(--navy-700); color: var(--surface); }
        .pt-step--current .pt-dot  { background: var(--gold-500); border-color: var(--gold-500); color: var(--navy-900); box-shadow: 0 0 0 4px rgba(212, 169, 71, .25); }
        .pt-step--rejected .pt-dot { background: var(--status-rejected-dot); border-color: var(--status-rejected-dot); color: var(--surface); }
        .pt-step strong { font-size: 13px; font-weight: 600; line-height: 1.3; }
        .pt-step small { font-size: 12px; color: var(--muted); line-height: 1.3; }

        /* Layout */
        .pt-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }
        .pt-col { display: grid; gap: 16px; min-width: 0; }

        .pt-copy { color: var(--text); line-height: 1.7; overflow-wrap: anywhere; }

        /* Evidence */
        .pt-count { color: var(--muted); font-size: 13px; font-weight: 500; margin-left: 6px; }
        .pt-evidence {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
        .pt-evidence a {
            display: block;
            aspect-ratio: 1;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--map-loading-bg);
        }
        .pt-evidence img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .2s; }
        .pt-evidence a:hover img { transform: scale(1.04); }

        .pt-empty {
            display: grid;
            justify-items: center;
            gap: 4px;
            padding: 24px 8px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }
        .pt-empty svg { width: 28px; height: 28px; color: var(--faint); margin-bottom: 4px; }
        .pt-empty strong { color: var(--text); font-size: 15px; font-weight: 600; }

        /* Updates */
        .pt-updates { display: grid; }
        .pt-update {
            position: relative;
            display: grid;
            grid-template-columns: 16px minmax(0, 1fr);
            gap: 12px;
            padding-bottom: 24px;
        }
        .pt-update:last-child { padding-bottom: 0; }
        .pt-update:not(:last-child)::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 18px;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        .pt-update__dot {
            width: 16px;
            height: 16px;
            margin-top: 3px;
            border-radius: 50%;
            background: var(--navy-700);
            border: 3px solid var(--surface);
            box-shadow: 0 0 0 1px var(--navy-700);
            position: relative;
            z-index: 1;
        }
        .pt-update__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 6px; }
        .pt-update time { color: var(--muted); font-size: 13px; }
        .pt-update p { color: var(--text); line-height: 1.6; overflow-wrap: anywhere; }
        .pt-update .pt-evidence { margin-top: 12px; max-width: 420px; }

        /* Details */
        .pt-dl > div { padding: 12px 0; border-bottom: 1px solid var(--line-soft); }
        .pt-dl > div:first-child { padding-top: 0; }
        .pt-dl > div:last-child { padding-bottom: 0; border-bottom: 0; }
        .pt-dl dt { font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 2px; }
        .pt-dl dd { font-size: 15px; font-weight: 500; overflow-wrap: anywhere; }

        /* Privacy */
        .pt-privacy { display: grid; grid-template-columns: 20px minmax(0, 1fr); gap: 12px; background: var(--surface); }
        .pt-privacy svg { width: 20px; height: 20px; color: var(--navy-700); margin-top: 2px; }
        .pt-privacy strong { display: block; font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .pt-privacy p { font-size: 14px; color: var(--muted); line-height: 1.55; }

        @media (max-width: 900px) {
            .pt-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .pt-container { padding: 16px 16px 32px; }
            .pt-card { padding: 20px; }
            .pt-head h1 { font-size: 26px; }

            /* Vertical stepper */
            .pt-steps { grid-template-columns: 1fr; }
            .pt-step {
                grid-template-columns: 32px minmax(0, 1fr);
                grid-template-rows: auto auto;
                justify-items: start;
                align-items: center;
                column-gap: 12px;
                row-gap: 0;
                text-align: left;
                min-height: 56px;
            }
            .pt-step:not(:last-child)::after {
                top: 32px;
                bottom: -24px;
                left: 15px;
                width: 2px;
                height: auto;
            }
            .pt-dot { grid-row: 1 / span 2; }
            .pt-step strong, .pt-step small { grid-column: 2; }
            .pt-evidence { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
@endpush

@section('content')
    @php
        $verificationMethod = $complaint->verificationMethodEnum();
        $publicPhotoCount = count($complaint->publicImagePaths());
    @endphp

    <div class="pt">
        <div class="pt-container">
            <a href="{{ route('transparency') }}" class="pt-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
                {{ __('transparency.show.back') }}
            </a>

            {{-- Header: ticket, status, title, category + department. Each fact appears once on this page. --}}
            <header class="pt-card pt-head">
                <div class="pt-head__row">
                    <span class="pt-ticket">{{ $complaint->ticket_id }}</span>
                    <span class="pt-pill pt-pill--{{ str($complaint->status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                    @if($verificationMethod)
                        <span class="pt-pill pt-pill--{{ str($verificationMethod->value)->slug() === 'site-visit' || str($verificationMethod->value)->slug() === 'duplicate-match' ? str($verificationMethod->value)->slug() : 'verify' }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $verificationMethod->label() }}</span>
                    @endif
                </div>
                <h1>{{ $complaint->publicTitle() }}</h1>
                <div class="pt-meta">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"></path><circle cx="7.5" cy="7.5" r="1"></circle></svg>
                        {{ $categoryLabel }}
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5l6-3 6 3v16"></path><path d="M9 9h1"></path><path d="M14 9h1"></path></svg>
                        {{ $complaint->department?->name ?? __('transparency.show.department_pending') }}
                    </span>
                </div>
            </header>

            <div class="pt-stack">
                {{-- Progress --}}
                <section class="pt-card" aria-labelledby="pt-progress">
                    <h2 id="pt-progress">{{ __('transparency.show.progress') }}</h2>
                    <ol class="pt-steps">
                        @foreach($progressSteps as $step)
                            <li class="pt-step pt-step--{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
                                <span class="pt-dot" aria-hidden="true">
                                    @if($step['state'] === 'complete')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"></path></svg>
                                    @elseif($step['state'] === 'rejected')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="m7 7 10 10"></path><path d="m17 7-10 10"></path></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle></svg>
                                    @endif
                                </span>
                                <strong>{{ $step['label'] }}</strong>
                                <small>{{ $step['date'] ?? __('transparency.show.not_started') }}</small>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <div class="pt-grid">
                    <div class="pt-col">
                        {{-- Report --}}
                        <section class="pt-card" aria-labelledby="pt-report">
                            <h2 id="pt-report">{{ __('transparency.show.report') }}</h2>
                            <p class="pt-copy">{{ $description }}</p>
                        </section>

                        {{-- Evidence: only shown when there is some, so there is no empty box to scroll past --}}
                        @if($publicPhotoCount > 0)
                            <section class="pt-card" aria-labelledby="pt-evidence">
                                <h2 id="pt-evidence">
                                    {{ __('transparency.show.evidence') }}
                                    <span class="pt-count">{{ $publicPhotoCount }} {{ $publicPhotoCount === 1 ? __('transparency.show.photo') : __('transparency.show.photos') }}</span>
                                </h2>
                                <div class="pt-evidence">
                                    @foreach($complaint->publicImagePaths() as $imageIndex => $imagePath)
                                        <a href="{{ route('transparency.complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('transparency.show.open_evidence_photo', ['index' => $imageIndex + 1]) }}">
                                            <img src="{{ route('transparency.complaints.evidence', [$complaint, $imageIndex]) }}" alt="{{ __('transparency.show.evidence_photo', ['index' => $imageIndex + 1]) }}" loading="lazy">
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        {{-- Updates --}}
                        <section class="pt-card" aria-labelledby="pt-updates">
                            <h2 id="pt-updates">{{ __('transparency.show.updates') }}</h2>
                            @if($logs->isEmpty())
                                <div class="pt-empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                    <strong>{{ __('transparency.show.no_updates') }}</strong>
                                    <span>{{ __('transparency.show.next_update') }}</span>
                                </div>
                            @else
                                <div class="pt-updates">
                                    @foreach($logs as $log)
                                        @php $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status); @endphp
                                        <article class="pt-update">
                                            <span class="pt-update__dot" aria-hidden="true"></span>
                                            <div>
                                                <div class="pt-update__meta">
                                                    <span class="pt-pill pt-pill--{{ str((string) $log->new_status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $updateStatus?->label() ?? $log->new_status }}</span>
                                                    <time datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->translatedFormat('M j, Y g:i A') }}</time>
                                                </div>
                                                @if($log->comment)
                                                    <p>{{ $log->comment }}</p>
                                                @endif
                                                @if($log->documentation_images)
                                                    <div class="pt-evidence" aria-label="{{ __('transparency.show.documentation') }}">
                                                        @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                            <a href="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('transparency.show.open_documentation', ['index' => $documentationIndex + 1]) }}">
                                                                <img src="{{ route('transparency.complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('transparency.show.documentation_photo', ['index' => $documentationIndex + 1]) }}" loading="lazy">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    </div>

                    <aside class="pt-col">
                        {{-- Details: only what the header doesn't already say --}}
                        <section class="pt-card" aria-labelledby="pt-details">
                            <h2 id="pt-details">{{ __('transparency.show.details') }}</h2>
                            <dl class="pt-dl">
                                <div><dt>{{ __('transparency.show.labels.broad_area') }}</dt><dd>{{ $location }}</dd></div>
                                <div><dt>{{ __('transparency.show.labels.reported') }}</dt><dd>{{ $complaint->created_at?->translatedFormat('M j, Y') }}</dd></div>
                                <div><dt>{{ __('transparency.show.labels.public_alias') }}</dt><dd>{{ $reporter }}</dd></div>
                            </dl>
                        </section>

                        <section class="pt-card pt-privacy" aria-label="{{ __('transparency.show.privacy.label') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                            <div>
                                <strong>{{ __('transparency.show.privacy.protected') }}</strong>
                                <p>{{ __('transparency.show.privacy.copy') }}</p>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </div>
@endsection
