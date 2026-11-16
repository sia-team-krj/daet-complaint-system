@props([
    'complaint',
    'canManage' => false,
])

{{--
    The work on a complaint is paused.

    Some complaints cannot be finished today for reasons that are not the
    office's doing — the materials are not in stock, the budget has not been
    released, the work belongs to another body. The office records why and
    says it out loud to the resident; the deadline it owes pauses for as
    long as it waits.

    `canManage` — the office handling the complaint may put it on hold and
    take it off again. One markup for the staff and admin ticket pages.

    A finished report (resolved, rejected, closed) cannot be paused: the work
    is done, so the panel renders nothing — not the form, not even the idle
    line. If the report was put on hold before it was finished, the panel
    shows only what it is waiting for and the way to take the hold off.
    Docs: docs/design-system.md §5.40, §5.45.
--}}
@php
    $reason = $complaint->holdReasonEnum();
    $heldDays = $complaint->heldDays();
    $reasons = \App\Enums\HoldReason::cases();
    $finished = $complaint->statusEnum->isClosed();
@endphp

@if ($complaint->isOnHold() || ! $finished)
<div {{ $attributes->merge(['class' => 'moderation-panel moderation-panel--light hold-panel']) }}>
    <div class="moderation-panel__header">
        <div class="hold-panel__title">
            <span class="hold-panel__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"></rect><rect x="14" y="4" width="4" height="16" rx="1"></rect></svg>
            </span>
            <h3 class="moderation-panel__title">{{ __('staff.hold.title') }}</h3>
        </div>
        <p class="moderation-panel__lede">{{ __('staff.hold.lede') }}</p>
    </div>

    @if ($complaint->isOnHold())
        <p class="moderation-panel__consent hold-panel__state">
            <span class="hold-panel__state-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"></rect><rect x="14" y="4" width="4" height="16" rx="1"></rect></svg>
            </span>
            <span>
                <strong>{{ $reason?->label() ?? __('staff.hold.on_hold') }}</strong>
                {{ trans_choice('staff.hold.held_for', $heldDays, ['count' => $heldDays]) }}
                @if ($complaint->hold_expected_resume_at)
                    {{ __('staff.hold.expected_on', ['date' => $complaint->hold_expected_resume_at->toFormattedDateString()]) }}
                @endif
            </span>
        </p>

        @if ($complaint->holdNote())
            <div class="moderation-field">
                <label>{{ __('staff.hold.told_the_resident') }}</label>
                <p class="moderation-panel__consent">{{ $complaint->holdNote() }}</p>
            </div>
        @endif

        @if ($complaint->hold_internal_note)
            <div class="moderation-field">
                <label>{{ __('staff.hold.internal_note') }}</label>
                <p class="moderation-panel__consent">{{ $complaint->hold_internal_note }}</p>
            </div>
        @endif

        @if ($canManage)
            <form data-loading-submit method="POST" action="{{ route('staff.complaints.resume', $complaint) }}">
                @csrf
                <div class="moderation-field">
                    <label for="hold-resume-note">{{ __('staff.hold.resume_note') }}</label>
                    <textarea id="hold-resume-note" name="hold_note" rows="3" maxlength="2000"
                              placeholder="{{ __('staff.hold.resume_note_placeholder') }}">{{ old('hold_note') }}</textarea>
                </div>
                <button type="submit" class="moderation-panel__submit moderation-panel__submit--block">
                    {{ __('staff.hold.resume') }}
                </button>
            </form>
        @endif
    @else
        <p class="moderation-panel__consent hold-panel__state">
            <span class="hold-panel__state-icon hold-panel__state-icon--idle" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline></svg>
            </span>
            <span>{{ __('staff.hold.not_on_hold') }}</span>
        </p>

        @if ($canManage)
            <form data-loading-submit method="POST" action="{{ route('staff.complaints.hold', $complaint) }}">
                @csrf

                {{-- The reasons are short and there are only seven, so they
                     are all on screen at once: one tap, and no hunting through
                     a dropdown on a phone. --}}
                <div class="moderation-field">
                    <span class="moderation-field__label">{{ __('staff.hold.reason') }}</span>
                    <fieldset class="reason-chips">
                        <legend class="sr-only">{{ __('staff.hold.reason') }}</legend>
                        @foreach ($reasons as $case)
                            <label class="reason-chip">
                                <input type="radio" name="hold_reason" value="{{ $case->value }}"
                                       @checked(old('hold_reason') === $case->value)
                                       data-reason-chip>
                                <span class="reason-chip__label">{{ $case->label() }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    <p class="moderation-panel__hint">{{ __('staff.hold.reason_hint') }}</p>
                    @error('hold_reason') <p class="date-field__error">{{ $message }}</p> @enderror
                </div>

                {{-- What the resident sees comes first; the office's own note
                     comes last, where it cannot be mistaken for it. --}}
                <div class="moderation-field">
                    <label for="hold-note">
                        {{ __('staff.hold.note') }}
                        <span class="label-chip label-chip--public">{{ __('staff.hold.chip_public') }}</span>
                    </label>
                    <textarea id="hold-note" name="hold_note" rows="4" maxlength="2000" required
                              placeholder="{{ __('staff.hold.note_placeholder') }}">{{ old('hold_note') }}</textarea>
                    <p class="moderation-panel__hint">{{ __('staff.hold.note_hint') }}</p>
                    @error('hold_note') <p class="date-field__error">{{ $message }}</p> @enderror
                </div>

                <x-date-field
                    name="hold_expected_resume_at"
                    id="hold-expected-resume-at"
                    :label="__('staff.hold.expected_resume')"
                    :value="old('hold_expected_resume_at')"
                    :hint="__('staff.hold.expected_resume_hint')"
                    :optional="true"
                />

                <div class="moderation-field">
                    <label for="hold-internal-note">
                        {{ __('staff.hold.internal_note') }}
                        <span class="label-chip label-chip--internal">{{ __('staff.hold.chip_internal') }}</span>
                    </label>
                    <textarea id="hold-internal-note" name="hold_internal_note" rows="3" maxlength="2000"
                              placeholder="{{ __('staff.hold.internal_note_placeholder') }}">{{ old('hold_internal_note') }}</textarea>
                </div>

                <button type="submit" class="moderation-panel__submit moderation-panel__submit--block">
                    {{ __('staff.hold.put_on_hold') }}
                </button>
            </form>
        @endif
    @endif
</div>
@endif
