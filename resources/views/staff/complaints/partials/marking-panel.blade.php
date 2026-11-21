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
    <div class="moderation-panel moderation-panel--light staff-detail-card staff-detail-card--marking">
        <div class="moderation-panel__header">
            <h3 class="moderation-panel__title">{{ __('staff.marking.title') }}</h3>
            <p class="moderation-panel__lede">{{ __('staff.marking.lede') }}</p>
        </div>

        @if ($marked)
            <p class="moderation-panel__consent">
                <strong>{{ __('staff.marking.marked_label') }}: {{ $complaint->markingEnum()?->label() }}</strong>
                · {{ __('staff.marking.marked_by', ['name' => $complaint->markedBy?->full_name ?? '—']) }}
                · {{ $complaint->marked_at?->translatedFormat('M j, Y') }}
                @if($complaint->isMarkingPendingReview())
                    · {{ __('staff.marking.pending_review') }}
                @elseif($complaint->isMarkingConfirmed())
                    · {{ __('staff.marking.confirmed') }}
                @elseif($complaint->isMarkingDismissed())
                    · {{ __('staff.marking.dismissed') }}
                @endif
            </p>

            @if (trim((string) ($complaint->mark_description ?? '')) !== '')
                <p class="moderation-panel__consent">
                    <strong>{{ __('staff.marking.description_label') }}:</strong>
                    {{ $complaint->mark_description }}
                </p>
            @endif

            @if (trim((string) ($complaint->mark_comment ?? '')) !== '')
                <p class="moderation-panel__consent">
                    <strong>{{ __('staff.marking.comment_label') }}:</strong>
                    {{ $complaint->mark_comment }}
                </p>
            @endif
        @elseif ($canMark)
            <form method="POST" action="{{ route('staff.complaints.mark-misleading-spam', $complaint) }}" class="moderation-panel__form" data-loading-submit>
                @csrf

                <div class="moderation-field">
                    <label class="moderation-field__label" for="marking-kind">{{ __('staff.marking.kind_label') }}</label>
                    <select id="marking-kind" name="marking" class="tr-select" required>
                        <option value="">{{ __('staff.marking.kind_choose') }}</option>
                        @foreach (\App\Enums\ComplaintMarking::cases() as $option)
                            <option value="{{ $option->value }}" @selected(old('marking') === $option->value)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('marking')<div class="date-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="moderation-field">
                    <label for="marking-description">
                        {{ __('staff.marking.description_label') }}
                        @if ($descriptionRequired)<span aria-hidden="true">*</span>@endif
                    </label>
                    <textarea id="marking-description" name="description" rows="4" maxlength="2000" placeholder="{{ __('staff.marking.description_hint') }}" @if ($descriptionRequired) required @endif>{{ old('description') }}</textarea>
                    <p class="moderation-panel__hint">{{ __('staff.marking.description_hint') }}</p>
                    @error('description')<div class="date-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="moderation-field">
                    <label for="marking-comment">
                        {{ __('staff.marking.comment_label') }}
                        @if ($descriptionRequired)<span aria-hidden="true">*</span>@endif
                    </label>
                    <textarea id="marking-comment" name="comment" rows="3" maxlength="2000" @if ($descriptionRequired) required @endif>{{ old('comment') }}</textarea>
                    <p class="moderation-panel__hint">{{ __('staff.marking.comment_hint') }}</p>
                    @error('comment')<div class="date-field__error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="moderation-panel__submit moderation-panel__submit--block">
                    {{ __('staff.marking.submit') }}
                </button>
            </form>
        @endif
    </div>
@endif
