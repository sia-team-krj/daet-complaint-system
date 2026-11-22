{{--
    Office marking of a report as misleading or spam — the office's own
    rejection of the report.

    Shown to office staff while the report is open (the form), and again once
    it has been marked (the read-only record): a mark must remain visible on
    the ticket after the report has been rejected. The shared
    `.moderation-panel--light` grammar is the same one the hold panel uses on
    this page; no new colours, tokens or CSS.
--}}
@php
    $marked = $complaint->isMarked();
    $descriptionRequired = ($user->role ?? null) === 'staff';
@endphp

@if ($canMark || $marked)
    <div class="moderation-panel moderation-panel--light staff-detail-card staff-detail-card--marking marking-panel">
        <div class="marking-panel__head">
            <span class="marking-panel__icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
            <div class="marking-panel__head-copy">
                <p class="marking-panel__eyebrow">{{ __('staff.marking.eyebrow') }}</p>
                <h3 class="moderation-panel__title marking-panel__title">{{ __('staff.marking.title') }}</h3>
                <p class="moderation-panel__lede marking-panel__lede">{{ __('staff.marking.lede') }}</p>
            </div>
        </div>

        @if ($marked)
            <div class="marking-panel__record" role="status">
                <div class="marking-panel__record-row">
                    <span class="marking-panel__record-label">{{ __('staff.marking.marked_label') }}</span>
                    <span class="marking-panel__record-value">{{ $complaint->markingEnum()?->label() }}</span>
                </div>
                <div class="marking-panel__record-row">
                    <span class="marking-panel__record-label">{{ __('staff.marking.by', ['default' => 'Marked by']) }}</span>
                    <span class="marking-panel__record-value">{{ $complaint->markedBy?->full_name ?? '—' }}</span>
                </div>
                <div class="marking-panel__record-row">
                    <span class="marking-panel__record-label">{{ __('staff.marking.date', ['default' => 'Date']) }}</span>
                    <span class="marking-panel__record-value">{{ $complaint->marked_at?->translatedFormat('M j, Y') ?? '—' }}</span>
                </div>
                <div class="marking-panel__record-row">
                    <span class="marking-panel__record-label">{{ __('staff.marking.status', ['default' => 'Review status']) }}</span>
                    <span class="marking-panel__record-value">
                        @if($complaint->isMarkingPendingReview())
                            {{ __('staff.marking.pending_review') }}
                        @elseif($complaint->isMarkingConfirmed())
                            {{ __('staff.marking.confirmed') }}
                        @elseif($complaint->isMarkingDismissed())
                            {{ __('staff.marking.dismissed') }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>

            @if (trim((string) ($complaint->mark_description ?? '')) !== '')
                <div class="marking-panel__note">
                    <p class="marking-panel__note-label">{{ __('staff.marking.description_label') }}</p>
                    <p class="marking-panel__note-body">{{ $complaint->mark_description }}</p>
                </div>
            @endif

            @if (trim((string) ($complaint->mark_comment ?? '')) !== '')
                <div class="marking-panel__note">
                    <p class="marking-panel__note-label">{{ __('staff.marking.comment_label') }}</p>
                    <p class="marking-panel__note-body">{{ $complaint->mark_comment }}</p>
                </div>
            @endif
        @elseif ($canMark)
            <form method="POST" action="{{ route('staff.complaints.mark-misleading-spam', $complaint) }}" class="moderation-panel__form marking-panel__form" data-loading-submit>
                @csrf

                <div class="moderation-field marking-panel__field">
                    <label class="moderation-field__label" for="marking-kind">{{ __('staff.marking.kind_label') }}</label>
                    <select id="marking-kind" name="marking" class="tr-select marking-panel__select" required>
                        <option value="">{{ __('staff.marking.kind_choose') }}</option>
                        @foreach (\App\Enums\ComplaintMarking::cases() as $option)
                            <option value="{{ $option->value }}" @selected(old('marking') === $option->value)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('marking')<p class="marking-panel__error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="moderation-field marking-panel__field">
                    <label class="moderation-field__label" for="marking-description">
                        {{ __('staff.marking.description_label') }}
                        @if ($descriptionRequired)<span class="marking-panel__required" aria-hidden="true">*</span>@endif
                    </label>
                    <p class="moderation-panel__hint marking-panel__hint">{{ __('staff.marking.description_hint') }}</p>
                    <textarea id="marking-description" name="description" rows="4" maxlength="2000" placeholder="{{ __('staff.marking.description_hint') }}" @if ($descriptionRequired) required @endif>{{ old('description') }}</textarea>
                    @error('description')<p class="marking-panel__error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="moderation-field marking-panel__field">
                    <label class="moderation-field__label" for="marking-comment">
                        {{ __('staff.marking.comment_label') }}
                        @if ($descriptionRequired)<span class="marking-panel__required" aria-hidden="true">*</span>@endif
                    </label>
                    <p class="moderation-panel__hint marking-panel__hint">{{ __('staff.marking.comment_hint') }}</p>
                    <textarea id="marking-comment" name="comment" rows="3" maxlength="2000" @if ($descriptionRequired) required @endif>{{ old('comment') }}</textarea>
                    @error('comment')<p class="marking-panel__error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="marking-panel__actions">
                    <p class="marking-panel__actions-note">{{ __('staff.marking.review_note') }}</p>
                    <button type="submit" class="moderation-panel__submit marking-panel__submit">
                        {{ __('staff.marking.submit') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
@endif
