@extends('staff.layouts.app')

@section('title', __('staff.publication.review_title') . ' - ' . __('staff.title'))
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
@php
    $status = $complaint->publicationStatus();
    $approvalBlockers = \App\Services\ComplaintPrivacyService::approvalBlockers($complaint);
    $redactionReady = $complaint->isRedacted();
    $isPending = $status === \App\Enums\PublicationStatus::Pending;
@endphp
<div class="transparency-page publication-review">
    <div class="tr-container" style="max-width: none; padding: 0 24px;">

        {{-- Shell C: back link, h1, one line. No banner. --}}
        <div class="publication-review__head">
            <a href="{{ route('staff.publication-requests.index') }}" class="publication-review__back">
                <span aria-hidden="true">&larr;</span> {{ __('staff.publication.review_back') }}
            </a>
            <h1 class="tr-h1">{{ __('staff.publication.review_title') }}</h1>
            <p class="tr-privacy">{{ __('staff.publication.review_lede') }}</p>
        </div>

        {{-- Context strip: what this report is, in one line. --}}
        <div class="tr-card publication-review__context">
            <div class="report-row__head">
                <span class="report-row__ticket">{{ $complaint->ticket_id }}</span>
                @if($complaint->department)
                    <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                @endif
            </div>
            <p class="publication-review__context-facts">
                {{ $complaint->category_label ?? $complaint->category }}
                <span aria-hidden="true">&middot;</span> {{ $complaint->department?->name ?? __('nav.departments') }}
                <span aria-hidden="true">&middot;</span> {{ $complaint->created_at->locale(app()->getLocale())->translatedFormat('M j, Y') }}
            </p>
        </div>

        {{-- The decider's first question: who this is about. Always visible;
             it is never published. --}}
        <div class="publication-privacy" role="note" aria-label="{{ __('staff.publication.named_party') }}">
            <span class="publication-privacy__icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.5 0-10-4.5-10-10a10.07 10.07 0 0 1 2.06-6.06"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c5.5 0 10 4.5 10 10a9.12 9.12 0 0 1-.24 1.98"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
            </span>
            <div>
                <p class="publication-privacy__kicker">{{ __('staff.publication.privacy_kicker') }}</p>
                <p class="publication-privacy__name">
                    {{ $complaint->namedParty() }}@if ($complaint->namedPartyPosition()) — {{ $complaint->namedPartyPosition() }}@endif
                </p>
                <p class="publication-privacy__hint">{{ __('staff.publication.named_party_hint') }}</p>
            </div>
        </div>

        {{-- The work area: the original report and the redaction editor on the
             left; what residents will read on the right. --}}
        <div class="publication-review__grid">
            <div class="publication-review__main">

                {{-- What the office says happened, before any redaction. --}}
                <section class="tr-card" aria-labelledby="publication-original-title">
                    <h2 class="tr-h2" id="publication-original-title">{{ __('staff.publication.redact_original') }}</h2>
                    <p class="publication-review__title">{{ $complaint->title }}</p>
                    <p class="publication-review__body">{{ $complaint->description }}</p>
                </section>

                @if ($isPending)
                    {{-- Two stages, always in this order: draft a safe copy, then save the public one. --}}
                    <section class="tr-card moderation-panel--light" aria-labelledby="publication-redact-title">
                        <h2 class="tr-h2" id="publication-redact-title">{{ __('staff.publication.redact_title') }}</h2>
                        <p class="tr-results" style="margin-top: 8px; margin-bottom: 16px;">{{ __('staff.publication.redact_lede') }}</p>

                        {{-- Stage 1: tick the identifying details; the system drafts a safe copy. --}}
                        <div class="publication-stage">
                            <p class="publication-stage__label">
                                <span class="publication-stage__number" aria-hidden="true">1</span>
                                <span class="publication-stage__title">{{ __('staff.publication.redact_stage_1') }}</span>
                            </p>
                            <p class="publication-stage__hint">{{ __('staff.publication.redact_stage_1_hint') }}</p>
                            <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.redact-draft', $complaint) }}">
                                @csrf

                                <div class="moderation-field">
                                    @if ($complaint->namedParty())
                                        <label class="moderation-check" for="publication-strip-party">
                                            <input type="hidden" name="strip_party" value="0">
                                            <input type="checkbox" id="publication-strip-party" name="strip_party" value="1">
                                            <span class="moderation-check__text">{{ __('staff.publication.redact_party') }} — <strong>{{ $complaint->namedParty() }}</strong></span>
                                        </label>
                                    @endif

                                    @if ($complaint->namedPartyPosition())
                                        <label class="moderation-check" for="publication-strip-position">
                                            <input type="hidden" name="strip_position" value="0">
                                            <input type="checkbox" id="publication-strip-position" name="strip_position" value="1">
                                            <span class="moderation-check__text">{{ __('staff.publication.redact_position') }} — <strong>{{ $complaint->namedPartyPosition() }}</strong></span>
                                        </label>
                                    @endif
                                </div>

                                <button type="submit" class="tr-button">{{ __('staff.publication.redact_draft') }}</button>
                            </form>
                        </div>

                        {{-- Stage 2: the public copy itself. Saving writes only; publishing decides. --}}
                        <div class="publication-stage">
                            <p class="publication-stage__label">
                                <span class="publication-stage__number" aria-hidden="true">2</span>
                                <span class="publication-stage__title">{{ __('staff.publication.redact_stage_2') }}</span>
                            </p>
                            <p class="publication-stage__hint">{{ __('staff.publication.redact_stage_2_hint') }}</p>
                            <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.redaction', $complaint) }}">
                                @csrf

                                <div class="moderation-field">
                                    <label for="publication-redacted-title">{{ __('staff.moderation.redacted_title') }}</label>
                                    <input type="text" id="publication-redacted-title" name="redacted_title" maxlength="255" required
                                           value="{{ old('redacted_title', $complaint->redacted_title) }}"
                                           placeholder="{{ $complaint->title }}">
                                    @error('redacted_title')<div class="filing-error">{{ $message }}</div>@enderror
                                </div>

                                <div class="moderation-field">
                                    <label for="publication-redacted-description">{{ __('staff.moderation.redacted_description') }}</label>
                                    <textarea id="publication-redacted-description" name="redacted_description" rows="4" maxlength="5000" required
                                              placeholder="{{ $complaint->description }}">{{ old('redacted_description', $complaint->redacted_description) }}</textarea>
                                    @error('redacted_description')<div class="filing-error">{{ $message }}</div>@enderror
                                </div>

                                <div class="moderation-field">
                                    <label for="publication-redaction-note">{{ __('staff.publication.redact_note') }}</label>
                                    <textarea id="publication-redaction-note" name="redaction_note" rows="2" maxlength="1000"
                                              placeholder="{{ __('staff.publication.redact_note_placeholder') }}">{{ old('redaction_note') }}</textarea>
                                </div>

                                <p class="moderation-panel__hint">
                                    {{ $redactionReady ? __('staff.publication.redact_ready') : __('staff.publication.redact_empty') }}
                                </p>

                                <button type="submit" class="publication-review__save">{{ __('staff.publication.redact_save') }}</button>
                            </form>
                        </div>
                    </section>
                @endif
            </div>

            {{-- The public copy, exactly as a resident would read it. --}}
            <aside class="publication-review__aside">
                <section class="tr-card" aria-labelledby="publication-preview-title">
                    <p class="publication-preview__status">
                        <strong>{{ $status?->label() ?? __('staff.publication.not_requested') }}</strong>
                    </p>
                    <h2 class="tr-h2" id="publication-preview-title" style="margin-top: 12px;">{{ __('staff.publication.review_copy_title') }}</h2>
                    <p class="tr-results" style="margin-top: 4px;">{{ __('staff.publication.review_copy_hint') }}</p>

                    <div class="publication-preview__card">
                        <p class="publication-preview__meta">
                            {{ $complaint->category_label ?? $complaint->category }}
                            <span aria-hidden="true">&middot;</span> {{ $complaint->department?->name ?? __('nav.departments') }}
                        </p>
                        <h3 class="publication-preview__title">{{ $complaint->publicTitle() }}</h3>
                        <p class="publication-preview__body">{{ $complaint->publicDescription() }}</p>
                        @unless ($redactionReady)
                            <p class="publication-preview__warn" role="status">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                {{ __('staff.publication.redact_empty') }}
                            </p>
                        @endunless
                    </div>
                </section>
            </aside>
        </div>

        {{-- Decision bar: sticky, one primary action, works without JavaScript. --}}
        @if ($isPending)
            <div class="publication-decide" role="region" aria-label="{{ __('staff.publication.decide_label') }}">
                <div class="publication-decide__status">
                    <div class="publication-decide__statuscopy">
                        <p class="publication-decide__status-line">{{ $status?->label() }}</p>
                        <p class="publication-decide__hint" id="publication-decide-hint">
                            @if ($approvalBlockers !== [])
                                {{ __('staff.publication.approve_blocked') }}
                            @else
                                {{ __('staff.publication.decide_ready') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="publication-decide__actions">
                    <details class="publication-decide__decline">
                        <summary>{{ __('staff.publication.decline') }}</summary>
                        <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.decline', $complaint) }}" class="publication-decide__decline-body">
                            @csrf
                            <label for="publication-note-decline">{{ __('staff.publication.decline_note') }}</label>
                            <textarea id="publication-note-decline" name="publication_note" rows="3" maxlength="1000" required
                                      placeholder="{{ __('staff.publication.decline_note_placeholder') }}">{{ old('publication_note') }}</textarea>
                            <button type="submit" class="publication-decide__decline-confirm">{{ __('staff.publication.decide_decline_confirm') }}</button>
                        </form>
                    </details>
                    <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.approve', $complaint) }}">
                        @csrf
                        <button type="submit" class="publication-decide__approve" @disabled($approvalBlockers !== []) aria-describedby="publication-decide-hint">{{ __('staff.publication.approve') }}</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
