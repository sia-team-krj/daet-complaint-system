{{--
    The /track result state: a progress-only public record.

    Rendered by the page AND by the fragment endpoint behind it
    (complaints.track.browser), so the two can never disagree — see
    docs/soft-navigation.md. The root element is the page's own element, so a
    soft navigation replaces it whole.

    Anatomy: docs/design-system.md §5.53.
--}}
<div class="pt track-page">
    @php
        $statusEnum = $complaint->statusEnum;
        $statusKey = str($complaint->status)->slug('_');
    @endphp

    {{-- ── Escape hatch before the record, as on the public detail ───────── --}}
    <div class="pt-back-row">
        <div class="pt-container track-container">
            <a href="{{ route('home') }}" class="pt-back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                {{ __('complaint_track.result.back_home') }}
            </a>
        </div>
    </div>

    {{-- ── Result: progress only ─────────────────────────────────────────── --}}
    <header class="pt-banner track-banner track-banner--result">
        <div class="pt-container track-container">
            <div class="track-header">
                <p class="track-ref-label">{{ __('complaint_track.result.reference_label') }}</p>
                <div class="track-ref-row">
                    <h1 class="track-ref-number" data-track-heading tabindex="-1">{{ $complaint->ticket_id }}</h1>
                    <span class="pt-pill pt-pill--{{ $statusKey }} track-status">
                        <span class="pt-pill__dot" aria-hidden="true"></span>{{ $statusEnum->label() }}
                    </span>
                </div>
                @if($isPublicRecord && !empty($publicTitle))
                    <p class="track-public-title">{{ $publicTitle }}</p>
                @endif
            </div>

            <dl class="track-meta" aria-label="{{ __('complaint_track.result.reference_label') }}">
                <div class="track-meta__item track-meta__item--department">
                    <dt>{{ __('complaint_track.result.department_label') }}</dt>
                    <dd>{{ $office }}</dd>
                </div>
                <div class="track-meta__item">
                    <dt>{{ __('complaint_track.result.filed_label') }}</dt>
                    <dd>
                        <time datetime="{{ $complaint->created_at?->toAtomString() }}">{{ $complaint->created_at?->locale(app()->getLocale())->translatedFormat('M j, Y') ?? __('complaint_track.result.not_available') }}</time>
                    </dd>
                </div>
                <div class="track-meta__item">
                    <dt>{{ __('complaint_track.result.updated_label') }}</dt>
                    <dd>
                        @if($lastUpdated)
                            <time datetime="{{ $lastUpdated->toAtomString() }}">{{ $lastUpdated->locale(app()->getLocale())->translatedFormat('M j, Y') }}</time>
                        @else
                            {{ __('complaint_track.result.not_available') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </header>

    <div class="pt-container track-container">
        <div class="track-result-stack">
            <section class="pt-card track-card--progress" aria-labelledby="track-progress">
                <h2 id="track-progress">{{ __('transparency.show.progress') }}</h2>

                @if($complaint->isOnHold())
                    <div class="hold-notice" role="status">
                        <span class="hold-notice__chip">{{ $complaint->holdReasonEnum()?->label() ?? __('complaint_show.hold.on_hold') }}</span>
                        <p class="hold-notice__body">
                            {{ $complaint->holdNote() ?? __('complaint_show.hold.no_note') }}
                            @if($complaint->hold_expected_resume_at)
                                <span class="hold-notice__meta">{{ __('complaint_show.hold.expected_on', ['date' => $complaint->hold_expected_resume_at->locale(app()->getLocale())->translatedFormat('M j, Y')]) }}</span>
                            @endif
                        </p>
                    </div>
                @endif

                @if($hasOpenInformationRequest)
                    <div class="track-note track-note--waiting" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <div>
                            <strong>{{ __('complaint_track.result.waiting_title') }}</strong>
                            <p>{{ __('complaint_track.result.waiting_body') }}</p>
                        </div>
                    </div>
                @endif

                @include('partials.complaint-steps', ['steps' => $progressSteps])

                @if($whatMeans !== '')
                    <div class="pt-whatmeans">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <p>{{ $whatMeans }}</p>
                    </div>
                @endif

                @if($statusEnum === \App\Enums\ComplaintStatus::Rejected)
                    <div class="track-note track-note--rejected" role="note">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                        <div>
                            <strong>{{ __('complaint_track.result.rejected_title') }}</strong>
                            <p>{{ __('complaint_track.result.rejected_body') }}</p>
                        </div>
                    </div>
                @endif
            </section>

            @if($isPublicRecord)
                <section class="pt-card" aria-labelledby="track-report">
                    <h2 id="track-report">{{ __('complaint_track.result.report') }}</h2>
                    <p class="track-public-copy">{{ $publicDescription }}</p>
                </section>

                @if($publicPhotoCount > 0)
                    <section class="pt-card" aria-labelledby="track-evidence">
                        <h2 id="track-evidence">
                            {{ __('complaint_track.result.evidence') }}
                            <span class="pt-count">{{ $publicPhotoCount }} {{ $publicPhotoCount === 1 ? __('complaint_track.result.photo') : __('complaint_track.result.photos') }}</span>
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
            @endif

            <section class="pt-card track-card--updates" aria-labelledby="track-updates">
                <h2 id="track-updates">{{ __('transparency.show.updates') }}</h2>
                @include('complaints.partials.track-updates', [
                    'updates' => $updates,
                    'complaint' => $complaint,
                    'showDocumentation' => $isPublicRecord ?? false,
                ])
            </section>

            @if($isPublicRecord)
                <section class="pt-card" aria-labelledby="track-details">
                    <h2 id="track-details">{{ __('complaint_track.result.details') }}</h2>
                    <dl class="pt-dl">
                        <div>
                            <dt>{{ __('complaint_track.result.category') }}</dt>
                            <dd>
                                @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                {{ $categoryLabel }}
                            </dd>
                        </div>
                        <div class="pt-dl--full">
                            <dt>{{ __('complaint_track.result.area') }}</dt>
                            <dd>{{ $location }}</dd>
                        </div>
                    </dl>
                </section>

                @if($complaint->feedback && $complaint->feedback->isPublished())
                    <section class="pt-card" aria-labelledby="track-feedback">
                        <h2 id="track-feedback">{{ __('transparency.show.feedback.title') }}</h2>
                        <p class="track-public-copy track-feedback-lede">{{ __('transparency.show.feedback.lede') }}</p>

                        <div class="complaint-feedback-result">
                            <x-star-rating
                                name="public-track-feedback-{{ $complaint->id }}"
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
            @endif

            <section class="pt-card track-privacy-card" aria-labelledby="track-privacy">
                <div class="track-privacy-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-3.58 8-10V5l-8-3-8 3v7c0 6.42 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <div class="track-privacy-card__body">
                    <h2 id="track-privacy">{{ $isPublicRecord ? __('complaint_track.result.privacy_title_public') : __('complaint_track.result.privacy_title') }}</h2>
                    <p class="track-actions__copy">{{ $isPublicRecord ? __('complaint_track.result.privacy_body_public') : __('complaint_track.result.privacy_body') }}</p>
                </div>
            </section>

            <div class="track-actions-bar">
                <a href="{{ route('complaints.track') }}" class="track-btn track-btn--primary">{{ __('complaint_track.result.track_another') }}</a>
                @if($isPublicRecord)
                    <a href="{{ route('transparency.complaints.show', $complaint) }}" class="track-btn track-btn--secondary">{{ __('complaint_track.result.view_public_record') }}</a>
                @endif
                @guest
                    <a href="{{ route('login') }}" class="track-btn track-btn--secondary">{{ __('complaint_track.result.sign_in') }}</a>
                @endguest
            </div>
        </div>
    </div>
</div>
