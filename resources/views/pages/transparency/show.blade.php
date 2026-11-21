@extends($mainLayout)

@section('title', $complaint->ticket_id.' - '.__('transparency.show.title'))
@section('body-class', 'page-transparency-show')


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
                <div class="pt-banner__status-row">
                    <span class="pt-pill pt-pill--{{ str($complaint->status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                    @if($verificationMethod)
                        <span class="pt-chip-neutral">{{ __('transparency.show.latest_action') }}: {{ $verificationMethod->label() }}</span>
                    @endif
                    @if($lastUpdated)
                        <span class="pt-chip-neutral">{{ __('transparency.show.labels.last_updated') }}: {{ $lastUpdated->translatedFormat('M j, Y') }}</span>
                    @endif
                </div>
            </div>
        </header>

        <div class="pt-container pt-stack">

            {{-- Progress --}}
            <section class="pt-card pt-order-progress" aria-labelledby="pt-progress">
                <h2 id="pt-progress">{{ __('transparency.show.progress') }}</h2>
                @include('partials.complaint-steps', ['steps' => $progressSteps])
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
                                            @if($log->publicDisplayComment())
                                                <p class="pt-update__text">{{ $log->publicDisplayComment() }}</p>
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
                                                    @if($log->publicDisplayComment())
                                                        <p class="pt-update__text">{{ $log->publicDisplayComment() }}</p>
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

                    @if($complaint->feedback && $complaint->feedback->isPublished())
                        <section class="pt-card pt-order-feedback" aria-labelledby="pt-feedback">
                            <h2 id="pt-feedback">{{ __('transparency.show.feedback.title') }}</h2>
                            <p class="pt-copy">{{ __('transparency.show.feedback.lede') }}</p>

                            <div class="complaint-feedback-result">
                                <x-star-rating
                                    name="public-complaint-feedback-{{ $complaint->id }}"
                                    :value="$complaint->feedback->rating"
                                    :locked="true"
                                    :label="__('transparency.show.feedback.rating_label')"
                                />

                                @if($complaint->feedback->hasComment())
                                    <div class="complaint-feedback-comment">
                                        <p>{{ $complaint->feedback->comment }}</p>
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif
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
                                <dt>{{ __('transparency.show.labels.assigned_personnel') }}</dt>
                                <dd>{{ $complaint->assigned_staff_id ? __('transparency.show.assigned_personnel_private') : __('transparency.show.assigned_personnel_unassigned') }}</dd>
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
