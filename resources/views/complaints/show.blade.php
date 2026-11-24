@extends($mainLayout)

@section('title', __('complaint_show.page.title', ['ticket' => $complaint->ticket_id]))
@section('body-class', 'page-complaint-show')

@section('content')
    @php
        $statusEnum = $complaint->statusEnum;
        $statusKey = str($complaint->status)->slug('_');
        $isOwner = $complaint->visibilityIsOwnedBy(auth()->user());
        $verificationMethod = $complaint->verificationMethodEnum();

        $publicLogs = $complaint->logs
            ->where('is_internal', false)
            ->sortByDesc('created_at')
            ->values();
        $visibleLogs = $publicLogs->take(5)->values();
        $earlierLogs = $publicLogs->slice(5)->values();

        $openRequest = $complaint->informationRequests
            ->filter(fn ($request) => in_array($request->status, [
                \App\Enums\InformationRequestStatus::Open,
                \App\Enums\InformationRequestStatus::Answered,
            ], true))
            ->sortByDesc('id')
            ->first();
        $needsInfo = (string) $complaint->review_status === 'needs_information';
        $reviewerNote = trim((string) $complaint->review_notes);
        $needsInfoQuestion = $openRequest?->question
            ?? ($reviewerNote !== '' ? $reviewerNote : __('complaint_show.action.fallback_question'));
        $showNeedsInfo = $isOwner && ($openRequest !== null || $needsInfo);

        $office = $complaint->department?->name ?? __('complaint_show.details.unassigned');
        $policy = $complaint->intakePolicy();
        $photosAllowed = ($policy['photos_required'] ?? false) || ($policy['photos_allowed'] ?? false);
        $photoCount = (int) $complaint->evidence_image_count;
        $showPhotos = $photosAllowed && $photoCount > 0;
        $showLocation = $complaint->intakeLocationApplicable()
            || $complaint->latitude !== null
            || $complaint->longitude !== null
            || filled($complaint->barangay)
            || filled($complaint->address_text);

        $categoryLabels = \App\Services\DepartmentRouter::categoryLabels();
        $categoryLabel = $categoryLabels[$complaint->category]
            ?? \Illuminate\Support\Str::headline($complaint->category);

        $progressSteps = \App\Support\ComplaintProgress::steps($complaint, $publicLogs);
        $whatMeans = \App\Support\ComplaintProgress::whatMeans($complaint);

        $contactText = \App\Models\SystemSetting::getValue('contact_text');
    @endphp

    <div class="pt">
        <div class="pt-back-row">
            <div class="pt-container">
                <a href="{{ route('complaints.index') }}" class="pt-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                    {{ __('complaint_show.page.back') }}
                </a>
            </div>
        </div>

        <header class="pt-banner">
            <div class="pt-container">
                <div class="pt-banner__ticket-row">
                    <span class="pt-ticket-pill">{{ $complaint->ticket_id }}</span>
                    <button type="button" class="pt-copy-btn" data-ticket="{{ $complaint->ticket_id }}" aria-label="{{ __('complaint_show.page.copy') }}" title="{{ __('complaint_show.page.copy') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                    <span class="sr-only" id="copy-live" aria-live="polite"></span>
                </div>

                <h1 class="pt-banner__title">{{ $complaint->title }}</h1>

                <div class="pt-banner__status-row">
                    <span class="pt-pill pt-pill--{{ str($complaint->status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $statusEnum->label() }}</span>
                    @if($complaint->isOnHold())
                        <span class="pt-chip-neutral">{{ $complaint->holdReasonEnum()?->label() ?? __('complaint_show.hold.on_hold') }}</span>
                    @endif
                    @if($verificationMethod)
                        <span class="pt-chip-neutral">{{ __('complaint_show.page.verified_by', ['method' => $verificationMethod->label()]) }}</span>
                    @endif
                    <span class="pt-chip-neutral">{{ __('complaint_show.details.date_filed') }}: {{ $complaint->created_at->locale(app()->getLocale())->translatedFormat('M j, Y') }}</span>
                </div>
            </div>
        </header>

        @if(session('status') || session('error'))
            <div class="pt-container">
                @if(session('status'))
                    <x-toast :message="session('status')" variant="success" />
                @endif
                @if(session('error'))
                    <x-toast :message="session('error')" variant="error" />
                @endif
            </div>
        @endif

        <div class="pt-container pt-stack rd-stack">
            @if($showNeedsInfo)
                <section class="info-request rd-order-action" aria-labelledby="info-request-heading">
                    <div class="info-request__head">
                        <span class="info-request__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </span>
                        <div class="info-request__head-copy">
                            <p class="info-request__kicker">
                                {{ $openRequest?->isAnswered() ? __('complaint_show.action.kicker_answered') : __('complaint_show.action.kicker') }}
                            </p>
                            <h2 id="info-request-heading" class="info-request__title">
                                {{ $openRequest?->isAnswered() ? __('complaint_show.action.title_answered') : __('complaint_show.action.title_open') }}
                            </h2>
                        </div>
                    </div>

                    <p class="info-request__lede">
                        {{ $openRequest?->isAnswered() ? __('complaint_show.action.thanks') : __('complaint_show.action.lede') }}
                    </p>

                    <blockquote class="info-request__question">
                        <p class="info-request__asked-by">
                            @if($openRequest?->requester?->full_name)
                                {{ __('complaint_show.action.asked_by', ['name' => $openRequest->requester->full_name, 'time' => $openRequest->created_at->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}
                            @elseif($openRequest)
                                {{ __('complaint_show.action.asked_by_department', ['time' => $openRequest->created_at->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}
                            @else
                                {{ __('complaint_show.action.from_office') }}
                            @endif
                        </p>
                        <p>{{ $needsInfoQuestion }}</p>
                    </blockquote>

                    @if($openRequest?->isAnswered())
                        <div class="info-request__answer">
                            <p class="info-request__answer-label">{{ __('complaint_show.action.reply_label') }}</p>
                            <p class="info-request__answer-text">{{ $openRequest->response_text }}</p>
                            @php $replyPhotos = (array) ($openRequest->response_image_paths ?? []); @endphp
                            @if($replyPhotos !== [])
                                <div class="info-request__answer-photos">
                                    @foreach($replyPhotos as $replyPhotoIndex => $replyPhotoPath)
                                        <a href="{{ route('complaints.information.evidence', [$complaint, $openRequest, $replyPhotoIndex]) }}" target="_blank" rel="noopener" class="info-request__answer-photo">
                                            <img src="{{ route('complaints.information.evidence', [$complaint, $openRequest, $replyPhotoIndex]) }}" alt="{{ __('complaint_show.action.reply_photo_alt', ['index' => $replyPhotoIndex + 1]) }}" loading="lazy">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <form data-loading-submit method="POST" action="{{ route('complaints.information.store', $complaint) }}" enctype="multipart/form-data" class="info-request__form" data-disable-on-submit>
                            @csrf
                            <div class="info-request__field">
                                <label for="response" class="info-request__label">{{ __('complaint_show.action.reply_label') }}</label>
                                <textarea name="response" id="response" rows="4" required minlength="10" maxlength="2000" placeholder="{{ __('complaint_show.action.reply_placeholder') }}">{{ old('response') }}</textarea>
                                @error('response')<p class="info-request__error">{{ $message }}</p>@enderror
                            </div>

                            <div class="info-request__field info-request__photos-field"
                                 data-photo-picker
                                 data-max="5"
                                 data-max-bytes="5242880"
                                 data-msg-too-many="{{ __('complaint_show.action.photos_too_many', ['max' => 5]) }}"
                                 data-msg-invalid="{{ __('complaint_show.action.photos_invalid') }}"
                                 data-msg-remove="{{ __('complaint_show.action.photos_remove', ['name' => '__name__']) }}"
                                 data-msg-preview="{{ __('complaint_show.action.photos_preview_alt', ['name' => '__name__']) }}"
                                 data-msg-count="{{ __('complaint_show.action.photos_count', ['count' => '__count__', 'max' => 5]) }}">
                                <label for="images" class="info-request__label">{{ __('complaint_show.action.photos_label') }} <small>{{ __('complaint_show.action.photos_optional') }}</small></label>

                                <div class="info-request__photos">
                                    <input type="file" name="images[]" id="images" class="info-request__file-input" accept="image/jpeg,image/png,image/webp" multiple data-photo-input>
                                    <input type="file" class="info-request__file-input" accept="image/jpeg,image/png,image/webp" capture="environment" data-photo-camera>

                                    <div class="info-request__photo-actions">
                                        <button type="button" class="info-request__photo-add tr-button" data-photo-trigger>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
                                            {{ __('complaint_show.action.photos_choose') }}
                                        </button>
                                        <button type="button" class="info-request__photo-add tr-button" data-photo-camera-trigger>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                            {{ __('complaint_show.action.photos_take') }}
                                        </button>
                                        <span class="info-request__photo-count" data-photo-count aria-live="polite">{{ __('complaint_show.action.photos_count', ['count' => 0, 'max' => 5]) }}</span>
                                    </div>

                                    <div class="info-request__photo-grid" data-photo-previews aria-live="polite"></div>
                                    <p class="info-request__error" data-photo-error hidden></p>
                                </div>

                                @error('images')<p class="info-request__error">{{ $message }}</p>@enderror
                                @error('images.*')<p class="info-request__error">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit" class="info-request__submit">{{ __('complaint_show.action.submit') }}</button>
                        </form>
                    @endif
                </section>
            @endif

            <section class="pt-card rd-order-progress" aria-labelledby="pcs-progress-heading">
                <h2 id="pcs-progress-heading">{{ __('complaint_show.progress.title') }}</h2>

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

                @include('partials.complaint-steps', ['steps' => $progressSteps])

                @if($whatMeans !== '')
                    <div class="pt-whatmeans">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <p>{{ $whatMeans }}</p>
                    </div>
                @endif
            </section>

            <div class="pt-grid">
                <div class="pt-col pt-col--main">
                    <section class="pt-card rd-order-report" aria-labelledby="pcs-report-heading">
                        <h2 id="pcs-report-heading">{{ __('complaint_show.report.title') }}</h2>
                        <p class="pt-copy">{{ $complaint->description }}</p>
                    </section>

                    @if($showPhotos)
                        <section class="pt-card rd-order-evidence" aria-labelledby="pcs-evidence-heading">
                            <h2 id="pcs-evidence-heading">
                                {{ __('complaint_show.report.photos_label') }}
                                <span class="pt-count">{{ trans_choice('complaint_show.report.photo_count', $photoCount) }}</span>
                            </h2>
                            <div class="pt-evidence" data-pcs-gallery>
                                @foreach($complaint->evidence_images as $imageIndex => $imagePath)
                                    @php $imageUrl = route('complaints.evidence', [$complaint, $imageIndex]); @endphp
                                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener" data-pcs-photo data-pcs-index="{{ $imageIndex }}" aria-label="{{ __('complaint_show.report.photo_alt', ['index' => $imageIndex + 1]) }}">
                                        <img src="{{ $imageUrl }}" alt="{{ __('complaint_show.report.photo_alt', ['index' => $imageIndex + 1]) }}" loading="lazy" width="420" height="420">
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if($isOwner && ($complaint->canReceiveFeedback() || $complaint->feedback))
                        @php $complaintFeedback = $complaint->feedback; @endphp
                        <section class="pt-card rd-order-feedback" aria-labelledby="complaint-feedback-heading">
                            <h2 id="complaint-feedback-heading">{{ __('complaint_show.feedback.title') }}</h2>

                            @if(! $complaintFeedback)
                                <p class="pt-copy">{{ __('complaint_show.feedback.lede') }}</p>
                                <form data-loading-submit method="POST" action="{{ route('complaints.feedback.store', $complaint) }}" class="complaint-feedback-form">
                                    @csrf
                                    <x-star-rating
                                        name="rating"
                                        :value="old('rating')"
                                        :label="__('complaint_show.feedback.rating_label')"
                                    />
                                    @error('rating')
                                        <p class="cat-field__error" role="alert">{{ $message }}</p>
                                    @enderror

                                    <label class="complaint-feedback-field" for="complaint-feedback-comment">
                                        <span class="complaint-feedback-field__label">
                                            {{ __('complaint_show.feedback.comment_label') }}
                                            <em>{{ __('complaint_show.feedback.optional') }}</em>
                                        </span>
                                        <textarea
                                            id="complaint-feedback-comment"
                                            name="comment"
                                            class="feedback-textarea"
                                            rows="4"
                                            maxlength="2000"
                                            placeholder="{{ __('complaint_show.feedback.comment_placeholder') }}"
                                        >{{ old('comment') }}</textarea>
                                    </label>
                                    @error('comment')
                                        <p class="cat-field__error" role="alert">{{ $message }}</p>
                                    @enderror

                                    <p class="feedback-card__note">{{ __('complaint_show.feedback.moderation_note') }}</p>

                                    <div class="complaint-feedback-actions">
                                        <button type="submit" class="tr-button tr-button--primary">{{ __('complaint_show.feedback.submit') }}</button>
                                    </div>
                                </form>
                            @else
                                <div class="complaint-feedback-result">
                                    <x-star-rating
                                        name="complaint-feedback-rating-display"
                                        :value="$complaintFeedback->rating"
                                        :locked="true"
                                        :label="__('complaint_show.feedback.your_rating')"
                                    />

                                    @if($complaintFeedback->hasComment())
                                        <div class="complaint-feedback-comment">
                                            <p>{{ $complaintFeedback->comment }}</p>
                                        </div>

                                        @if($complaintFeedback->isPending())
                                            <p class="feedback-card__note" role="status">{{ __('complaint_show.feedback.pending') }}</p>
                                        @elseif($complaintFeedback->isWithheld())
                                            <p class="feedback-card__note" role="status">{{ __('complaint_show.feedback.withheld') }}</p>
                                        @else
                                            <p class="feedback-card__note" role="status">{{ __('complaint_show.feedback.published') }}</p>
                                        @endif
                                    @else
                                        <p class="feedback-card__note" role="status">{{ __('complaint_show.feedback.published_rating_only') }}</p>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endif

                    @if($isOwner && $statusEnum === \App\Enums\ComplaintStatus::Resolved)
                        <section class="close-report rd-order-close" aria-labelledby="close-report-heading">
                            <div class="close-report__head">
                                <span class="close-report__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </span>
                                <div>
                                    <p class="close-report__kicker">{{ __('complaint_show.close.kicker') }}</p>
                                    <h2 class="close-report__title" id="close-report-heading">{{ __('complaint_show.close.title') }}</h2>
                                </div>
                            </div>
                            <p class="close-report__lede">{{ __('complaint_show.close.lede') }}</p>
                            <div class="close-report__actions">
                                <form data-loading-submit method="POST" action="{{ route('complaints.close', $complaint) }}" class="close-report__form">
                                    @csrf
                                    <button type="submit" class="close-report__submit">{{ __('complaint_show.close.submit') }}</button>
                                </form>
                                <form method="POST" action="{{ route('complaints.reopen.store', $complaint) }}" class="close-report__reopen">
                                    @csrf
                                    <label class="close-report__reopen-label" for="close-report-reason">{{ __('complaint_show.reopen.reason_label') }}</label>
                                    <textarea id="close-report-reason" name="reason" class="close-report__reopen-input" rows="3" minlength="10" maxlength="2000" required placeholder="{{ __('complaint_show.reopen.reason_placeholder') }}"></textarea>
                                    @error('reason')<p class="close-report__reopen-error" role="alert">{{ $message }}</p>@enderror
                                    <button type="submit" class="close-report__reopen-submit">{{ __('complaint_show.reopen.submit') }}</button>
                                </form>
                            </div>
                        </section>
                    @endif

                    <section class="pt-card rd-order-updates" aria-labelledby="pcs-updates-heading">
                        <h2 id="pcs-updates-heading">{{ __('complaint_show.updates.title') }}</h2>

                        @if($publicLogs->isEmpty())
                            <div class="pt-empty">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <strong>{{ __('complaint_show.updates.empty_title') }}</strong>
                                <span>{{ __('complaint_show.updates.empty_body') }}</span>
                            </div>
                        @else
                            <ul class="pt-updates">
                                @foreach($visibleLogs as $log)
                                    @php $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status); @endphp
                                    <li class="pt-update{{ $loop->first ? ' pt-update--newest' : '' }}">
                                        <span class="pt-update__marker" aria-hidden="true"></span>
                                        <div>
                                            <div class="pt-update__meta">
                                                @if($log->new_status)
                                                    <span class="pt-pill pt-pill--{{ str((string) $log->new_status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $updateStatus?->label() ?? $log->new_status }}</span>
                                                @endif
                                                <time datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->locale(app()->getLocale())->translatedFormat('M j, Y g:i A') }}</time>
                                            </div>
                                            @if($log->comment)
                                                <p class="pt-update__text">{{ $log->comment }}</p>
                                            @endif
                                            @if($log->documentation_images)
                                                <div class="pt-evidence" aria-label="{{ __('complaint_show.updates.documentation') }}">
                                                    @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                        <a href="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('complaint_show.updates.open_documentation', ['index' => $documentationIndex + 1]) }}">
                                                            <img src="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('complaint_show.updates.documentation_photo', ['index' => $documentationIndex + 1]) }}" loading="lazy" width="420" height="420">
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
                                        {{ __('complaint_show.updates.earlier', ['count' => $earlierLogs->count()]) }}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </summary>
                                    <ul class="pt-updates">
                                        @foreach($earlierLogs as $log)
                                            @php $updateStatus = \App\Enums\ComplaintStatus::tryFrom((string) $log->new_status); @endphp
                                            <li class="pt-update">
                                                <span class="pt-update__marker" aria-hidden="true"></span>
                                                <div>
                                                    <div class="pt-update__meta">
                                                        @if($log->new_status)
                                                            <span class="pt-pill pt-pill--{{ str((string) $log->new_status)->slug() }}"><span class="pt-pill__dot" aria-hidden="true"></span>{{ $updateStatus?->label() ?? $log->new_status }}</span>
                                                        @endif
                                                        <time datetime="{{ $log->created_at?->toAtomString() }}">{{ $log->created_at?->locale(app()->getLocale())->translatedFormat('M j, Y g:i A') }}</time>
                                                    </div>
                                                    @if($log->comment)
                                                        <p class="pt-update__text">{{ $log->comment }}</p>
                                                    @endif
                                                    @if($log->documentation_images)
                                                        <div class="pt-evidence" aria-label="{{ __('complaint_show.updates.documentation') }}">
                                                            @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                                <a href="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" aria-label="{{ __('complaint_show.updates.open_documentation', ['index' => $documentationIndex + 1]) }}">
                                                                    <img src="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('complaint_show.updates.documentation_photo', ['index' => $documentationIndex + 1]) }}" loading="lazy" width="420" height="420">
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

                    @if($showLocation)
                        <section class="pt-card rd-order-location" aria-labelledby="pcs-location-heading">
                            <h2 id="pcs-location-heading">{{ __('complaint_show.location.title') }}</h2>

                            @if($complaint->barangay || $complaint->address_text)
                                <p class="pt-location-address">
                                    @if($complaint->barangay)
                                        <strong>{{ $complaint->barangay }}</strong>
                                    @endif
                                    @if($complaint->barangay && $complaint->address_text)<br>@endif
                                    @if($complaint->address_text){{ $complaint->address_text }}@endif
                                </p>
                            @endif

                            @if($complaint->latitude !== null && $complaint->longitude !== null)
                                {{-- The wrapper's stacking context lives in
                                     resources/css/pages/complaint-show.css:
                                     .pt .map-container { isolation: isolate; z-index: 0; } --}}
                                <div id="complaint-map" class="map-container"></div>
                                <p class="pt-coords">{{ number_format($complaint->latitude, 6) }}, {{ number_format($complaint->longitude, 6) }}</p>
                                <a class="pt-map-link" href="https://www.openstreetmap.org/?mlat={{ $complaint->latitude }}&amp;mlon={{ $complaint->longitude }}#map=17/{{ $complaint->latitude }}/{{ $complaint->longitude }}" target="_blank" rel="noopener">{{ __('complaint_show.location.open_in_maps') }}</a>
                            @elseif(! $complaint->barangay && ! $complaint->address_text)
                                <div class="pt-empty">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <strong>{{ __('complaint_show.location.no_location') }}</strong>
                                </div>
                            @endif
                        </section>
                    @endif
                </div>

                <aside class="pt-col pt-col--side">
                    <section class="pt-card rd-order-details" aria-labelledby="pcs-details-heading">
                        <h2 id="pcs-details-heading">{{ __('complaint_show.details.title') }}</h2>
                        <dl class="pt-dl">
                            <div>
                                <dt>{{ __('complaint_show.details.category') }}</dt>
                                <dd>
                                    @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                    {{ $categoryLabel }}
                                </dd>
                            </div>
                            <div class="pt-dl--full">
                                <dt>{{ __('complaint_show.details.department') }}</dt>
                                <dd>
                                    @if($complaint->department)
                                        <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                                        {{ $complaint->department->name }}
                                    @else
                                        {{ __('complaint_show.details.unassigned') }}
                                    @endif
                                </dd>
                            </div>
                            <div class="pt-dl--full">
                                <dt>{{ __('complaint_show.details.assigned_personnel') }}</dt>
                                <dd>{{ $complaint->assignedStaff?->full_name ?? __('complaint_show.details.unassigned') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('complaint_show.details.review_status') }}</dt>
                                <dd>{{ $complaint->reviewStatusEnum->label() }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('complaint_show.visibility.title_legacy') }}</dt>
                                <dd>
                                    @if($complaint->isPrivacyGated())
                                        {{ $complaint->publicationStatus()?->label() ?? __('complaint_show.visibility.gated_state') }}
                                    @else
                                        {{ $complaint->is_public ? __('complaint_show.details.published') : __('my_complaints.visibility_private') }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>{{ __('complaint_show.details.suggested_priority') }}</dt>
                                <dd>{{ $complaint->suggestedPriorityEnum->label() }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('complaint_show.details.confirmed_priority') }}</dt>
                                <dd>{{ $complaint->confirmedPriorityEnum?->label() ?? __('complaint_show.details.awaiting') }}</dd>
                            </div>
                            <div class="pt-dl--full">
                                <dt>{{ __('complaint_show.details.date_filed') }}</dt>
                                <dd>
                                    {{ $complaint->created_at->locale(app()->getLocale())->translatedFormat('F j, Y') }}
                                    <span class="pt-dl--sub">{{ $complaint->created_at->diffForHumans() }}</span>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    @if($isOwner)
                        <section class="visibility-panel rd-order-visibility" aria-labelledby="pcs-visibility-heading">
                            <h2 class="visibility-panel__title" id="pcs-visibility-heading">{{ __('complaint_show.visibility.title') }}</h2>
                            <p class="visibility-panel__lede">{{ __('complaint_show.visibility.lede') }}</p>

                            @if($complaint->isPrivacyGated())
                                <p class="visibility-panel__state">
                                    <strong>{{ $complaint->publicationStatus()?->label() ?? __('complaint_show.visibility.gated_state') }}</strong>
                                    {{ __('complaint_show.visibility.gated_body') }}
                                </p>
                            @else
                                <form data-loading-submit method="POST" action="{{ route('complaints.visibility.update', $complaint) }}" class="visibility-panel__actions">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="visibility" value="{{ $complaint->is_public ? 'private' : 'public' }}">
                                    <button type="submit" class="visibility-panel__button">
                                        {{ $complaint->is_public ? __('complaint_show.visibility.privatise') : __('complaint_show.visibility.publish') }}
                                    </button>
                                    <span class="visibility-panel__state">
                                        {{ $complaint->is_public ? __('complaint_show.visibility.public_state') : __('complaint_show.visibility.private_state') }}
                                    </span>
                                </form>
                            @endif

                            @if($complaint->isHiddenFromPublic() || $complaint->isRedacted() || $complaint->isModerationFlagged())
                                <div class="visibility-moderation" role="status">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                    <div>
                                        @if($complaint->isHiddenFromPublic() || $complaint->isRedacted())
                                            <strong>{{ __('complaint_show.visibility.moderation_title') }}</strong>
                                            <p>
                                                {{ __('complaint_show.visibility.moderation_body') }}
                                                @if($complaint->moderation_reason)
                                                    {{ __('complaint_show.visibility.moderation_reason', ['reason' => $complaint->moderation_reason]) }}
                                                @else
                                                    {{ __('complaint_show.visibility.moderation_generic') }}
                                                @endif
                                                {{ __('complaint_show.visibility.moderation_reassurance') }}
                                            </p>
                                        @else
                                            <strong>{{ __('complaint_show.visibility.moderation_flag_title') }}</strong>
                                            <p>{{ __('complaint_show.visibility.moderation_flag_body') }}</p>
                                            {{ __('complaint_show.visibility.moderation_reassurance') }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </section>
                    @endif

                    <section class="pt-card rd-order-help" aria-labelledby="pcs-help-heading">
                        <h2 id="pcs-help-heading">{{ __('complaint_show.help.title') }}</h2>

                        @if(filled($contactText))
                            <p class="pt-help__body">{{ $contactText }}</p>
                        @else
                            <p class="pt-help__body">{{ __('complaint_show.help.intro') }}</p>
                            <div class="pt-help__contacts" role="list">
                                <div class="pt-help__contact" role="listitem">
                                    <span class="pt-help__contact-label">{{ __('complaint_show.help.mobile_label') }}</span>
                                    <span class="pt-help__contact-value">
                                        <a class="pt-help__contact-link" href="tel:{{ __('complaint_show.help.mobile_href') }}">{{ __('complaint_show.help.mobile_value') }}</a>
                                        <span class="pt-help__sample">({{ __('complaint_show.help.sample_tag') }})</span>
                                    </span>
                                </div>
                                <div class="pt-help__contact" role="listitem">
                                    <span class="pt-help__contact-label">{{ __('complaint_show.help.email_label') }}</span>
                                    <span class="pt-help__contact-value">
                                        <a class="pt-help__contact-link" href="mailto:{{ __('complaint_show.help.email_value') }}">{{ __('complaint_show.help.email_value') }}</a>
                                        <span class="pt-help__sample">({{ __('complaint_show.help.sample_tag') }})</span>
                                    </span>
                                </div>
                            </div>
                        @endif

                        <a class="pt-help__link" href="{{ route('transparency') }}">{{ __('complaint_show.help.view_transparency') }}</a>
                    </section>
                </aside>
            </div>

            <p class="pt-privacy rd-order-privacy" role="note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <span>{{ __('complaint_show.privacy.public_note') }}</span>
            </p>
        </div>

        <dialog id="pcs-viewer" class="pcs-viewer" aria-label="{{ __('complaint_show.report.viewer_label') }}">
            <div class="pcs-viewer__bar">
                <button type="button" class="pcs-viewer__close" data-pcs-viewer-close aria-label="{{ __('complaint_show.report.viewer_close') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <figure class="pcs-viewer__figure">
                <img class="pcs-viewer__img" alt="" data-pcs-viewer-img>
            </figure>
        </dialog>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Copy ticket: clipboard.writeText only, with a polite status.
        (function () {
            const btn = document.querySelector('.pt-copy-btn');
            const live = document.getElementById('copy-live');
            if (!btn) return;

            const original = btn.getAttribute('aria-label');
            const copied = @json(__('complaint_show.page.copied'));
            let resetTimer;

            btn.addEventListener('click', async () => {
                const text = btn.dataset.ticket || '';
                try {
                    await navigator.clipboard.writeText(text);
                } catch (error) {
                    // Clipboard can be unavailable in an insecure context.
                }
                btn.setAttribute('aria-label', copied);
                btn.setAttribute('title', copied);
                if (live) live.textContent = copied;
                window.clearTimeout(resetTimer);
                resetTimer = window.setTimeout(() => {
                    btn.setAttribute('aria-label', original);
                    btn.setAttribute('title', original);
                    if (live) live.textContent = '';
                }, 2000);
            });
        })();

        // Photos open in a full-screen viewer on phones; desktop keeps target=_blank.
        (function () {
            const gallery = document.querySelector('[data-pcs-gallery]');
            const viewer = document.getElementById('pcs-viewer');
            const viewerImg = viewer ? viewer.querySelector('[data-pcs-viewer-img]') : null;
            const viewerClose = viewer ? viewer.querySelector('[data-pcs-viewer-close]') : null;
            if (!gallery || !viewer || !viewerImg) return;

            let lastTrigger = null;

            gallery.addEventListener('click', (event) => {
                const link = event.target.closest('[data-pcs-photo]');
                if (!link || !window.matchMedia('(max-width: 767px)').matches) return;
                event.preventDefault();
                lastTrigger = link;
                viewerImg.src = link.getAttribute('href');
                const image = link.querySelector('img');
                viewerImg.alt = image ? (image.getAttribute('alt') || '') : '';
                if (!viewer.open) viewer.showModal();
                if (viewerClose) viewerClose.focus();
            });

            const closeViewer = () => {
                if (viewer.open) viewer.close();
                if (lastTrigger) lastTrigger.focus();
            };

            if (viewerClose) viewerClose.addEventListener('click', closeViewer);
            viewer.addEventListener('click', (event) => {
                if (event.target === viewer) closeViewer();
            });
            viewer.addEventListener('close', () => viewerImg.removeAttribute('src'));
        })();

        /*
         * Keep the map honest about its own size.
         *
         * Leaflet measures the container once, when the map is built, and caches
         * that measurement. The viewport on a phone has not settled by then:
         * the URL bar collapses, scrollbars come and go, a late webfont reflows
         * the card. invalidateSize() re-reads the container on the next frame,
         * after a short delay, and on every resize/rotation.
         */
        const keepMapSized = (target) => {
            if (!target) return;
            const refresh = () => target.invalidateSize({ animate: false });
            window.requestAnimationFrame(refresh);
            window.setTimeout(refresh, 300);
            window.addEventListener('resize', refresh);
            window.addEventListener('orientationchange', () => window.setTimeout(refresh, 150));
        };

        (function () {
            const mapEl = document.getElementById('complaint-map');
            if (!mapEl) return;

            // Leaflet is bundled and deferred, so wait for the bundle's handshake
            // instead of reaching for window.L before it exists.
            window.whenLeafletReady(() => {
                @if($complaint->latitude !== null && $complaint->longitude !== null)
                    const lat = {{ $complaint->latitude }};
                    const lng = {{ $complaint->longitude }};
                    const map = L.map('complaint-map').setView([lat, lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map);

                    keepMapSized(map);
                    if ('IntersectionObserver' in window) {
                        new IntersectionObserver((entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    try { map.invalidateSize({ animate: false }); } catch (error) {}
                                }
                            });
                        }, { root: null, threshold: 0.1 }).observe(mapEl);
                    }
                @endif
            }, {
                // The coordinates are printed underneath the map regardless, so the
                // work can still be done. Saying so is what stops a blank box being
                // read as "my location was not saved".
                onUnavailable: () => {
                    if (mapEl) mapEl.innerHTML = @json('<div style="padding:20px;text-align:center;color:var(--muted);font-size:12px">' . __('complaint_show.location.map_unavailable') . '</div>');
                },
            });
        })();

        // Photo pickers: previews and removable selected photos. There can be
        // more than one on the page (info request + follow-up). Re-init safe for soft navigation.
        (function () {
            const initPhotoPickers = (root) => {
                (root || document).querySelectorAll('[data-photo-picker]').forEach((picker) => {
                    if (picker.dataset.photoPickerReady === '1') return;
                    picker.dataset.photoPickerReady = '1';
                    const input = picker.querySelector('[data-photo-input]');
                    const trigger = picker.querySelector('[data-photo-trigger]');
                    const previews = picker.querySelector('[data-photo-previews]');
                    const count = picker.querySelector('[data-photo-count]');
                    const errorBox = picker.querySelector('[data-photo-error]');
                    if (!input || !previews) return;

                    const max = Number(picker.dataset.max || 5);
                    const maxBytes = Number(picker.dataset.maxBytes || 5242880);
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    const allowedName = /\.(jpe?g|png|webp)$/i;
                    const tpl = {
                        count: picker.dataset.msgCount || '__count__ / __max__ photos',
                        remove: picker.dataset.msgRemove || 'Remove __name__',
                        preview: picker.dataset.msgPreview || 'Selected photo: __name__',
                        tooMany: picker.dataset.msgTooMany || 'You can attach up to __max__ photos.',
                        invalid: picker.dataset.msgInvalid || 'Use JPG, PNG, or WEBP photos no larger than 5 MB each.',
                    };

                    let selected = [];
                    let previewUrls = [];

                    const fill = (template, values) => {
                        let out = String(template || '');
                        Object.entries(values || {}).forEach(([key, value]) => {
                            out = out.split(`:${key}`).join(String(value)).split(`__${key}__`).join(String(value));
                        });
                        return out;
                    };

                    const showError = (message) => {
                        if (!errorBox) return;
                        errorBox.textContent = message || '';
                        errorBox.hidden = !message;
                    };

                    const isAllowed = (file) => file.size > 0 && file.size <= maxBytes
                        && (allowedTypes.includes(file.type) || allowedName.test(file.name));

                    const syncInput = () => {
                        if (typeof DataTransfer === 'undefined') return false;
                        try {
                            const transfer = new DataTransfer();
                            selected.forEach(({ file }) => transfer.items.add(file));
                            input.files = transfer.files;
                            return true;
                        } catch (error) {
                            return false;
                        }
                    };

                    const render = () => {
                        previewUrls.forEach((url) => URL.revokeObjectURL(url));
                        previewUrls = [];
                        previews.replaceChildren();

                        selected.forEach(({ id, file }) => {
                            const item = document.createElement('div');
                            item.className = 'info-request__photo-item';

                            const image = document.createElement('img');
                            const url = URL.createObjectURL(file);
                            previewUrls.push(url);
                            image.src = url;
                            image.alt = fill(tpl.preview, { name: file.name });
                            item.appendChild(image);

                            const remove = document.createElement('button');
                            remove.type = 'button';
                            remove.className = 'info-request__photo-remove';
                            remove.setAttribute('aria-label', fill(tpl.remove, { name: file.name }));
                            remove.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
                            remove.addEventListener('click', () => {
                                selected = selected.filter((photo) => photo.id !== id);
                                syncInput();
                                showError('');
                                render();
                            });
                            item.appendChild(remove);

                            previews.appendChild(item);
                        });

                        if (count) {
                            count.textContent = fill(tpl.count, { count: selected.length, max });
                        }
                    };

                    const addFiles = (files) => {
                        const incoming = Array.from(files || []);
                        if (!incoming.length) return;

                        if (incoming.some((file) => !isAllowed(file))) {
                            showError(tpl.invalid);
                            return;
                        }

                        const seen = new Set(selected.map(({ file }) => `${file.name}-${file.size}-${file.lastModified}`));
                        const unique = incoming.filter((file) => {
                            const key = `${file.name}-${file.size}-${file.lastModified}`;
                            if (seen.has(key)) return false;
                            seen.add(key);
                            return true;
                        });

                        const slots = max - selected.length;
                        if (unique.length > slots) {
                            showError(fill(tpl.tooMany, { max }));
                        } else {
                            showError('');
                        }

                        unique.slice(0, Math.max(slots, 0)).forEach((file, index) => {
                            selected.push({ id: `${Date.now()}-${index}-${file.name}-${file.size}`, file });
                        });

                        syncInput();
                        render();
                    };

                    const cameraInput = picker.querySelector('[data-photo-camera]');
                    const cameraTrigger = picker.querySelector('[data-photo-camera-trigger]');

                    picker.classList.add('is-enhanced');
                    picker.querySelector('[data-photo-field]')?.classList.add('is-enhanced');

                    if (trigger) {
                        trigger.addEventListener('click', () => input.click());
                    }
                    if (cameraTrigger && cameraInput) {
                        cameraTrigger.addEventListener('click', () => cameraInput.click());
                    }

                    input.addEventListener('change', () => {
                        addFiles(input.files);
                    });
                    if (cameraInput) {
                        cameraInput.addEventListener('change', () => {
                            addFiles(cameraInput.files);
                            cameraInput.value = '';
                        });
                    }

                    render();
                });
            };

            initPhotoPickers(document);
            document.addEventListener('DOMContentLoaded', () => initPhotoPickers(document));
            document.addEventListener('livewire:navigated', () => initPhotoPickers(document));
            document.addEventListener('turbo:load', () => initPhotoPickers(document));
            if ('MutationObserver' in window) {
                new MutationObserver((mutations) => {
                    for (const m of mutations) {
                        for (const node of m.addedNodes || []) {
                            if (node.nodeType !== 1) continue;
                            if (node.matches?.('[data-photo-picker]') || node.querySelector?.('[data-photo-picker]')) {
                                initPhotoPickers(document);
                                return;
                            }
                        }
                    }
                }).observe(document.body, { childList: true, subtree: true });
            }
        })();
    });
</script>
@endpush
