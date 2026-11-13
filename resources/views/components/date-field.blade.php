@props([
    'name',
    'label',
    'value' => null,
    'hint' => null,
    'optional' => true,
    'id' => null,
])

{{--
    A date the office can actually fill in.

    The native date input alone is two problems in one: it renders as bare
    "dd/mm/yyyy" text until it is filled, and the format it shows follows the
    browser's locale rather than ours — so the same office sees 12/15/2026 on
    one device and 15/12/2026 on another. So the visible field is a text input
    masked to dd/mm/yyyy, and the calendar is a native date input kept
    alongside it: the button opens the OS calendar (the wheel picker on a
    phone), and the real value travels in the native input for the server.

    One markup for every date the portal asks for. The visible input is a
    plain text input on purpose: it then inherits whatever the surface it
    sits in gives a text field, dark panel or light.
    Docs: docs/design-system.md §5.42.
--}}
@php
    $fieldId = $id ?: ('date-field-'.$name);
    $iso = is_string($value) && $value !== '' ? $value : null;
    $display = $iso ? \Illuminate\Support\Carbon::parse($iso)->format('d/m/Y') : '';
@endphp

<div class="moderation-field date-field" data-date-field>
    <label for="{{ $fieldId }}">
        {{ $label }}
        @if($optional)
            <span class="label-chip label-chip--internal">{{ __('common.optional') }}</span>
        @endif
    </label>

    <div class="date-field__control">
        <input
            type="text"
            id="{{ $fieldId }}"
            class="date-field__text"
            inputmode="numeric"
            autocomplete="off"
            placeholder="dd/mm/yyyy"
            maxlength="10"
            value="{{ $display }}"
            data-date-field-display
            aria-describedby="{{ $fieldId }}-hint"
        >

        {{-- The value the server receives. Kept out of sight but still
             rendered: showPicker() refuses on an element that is not. --}}
        <input type="date" name="{{ $name }}" value="{{ $iso }}" class="date-field__native" tabindex="-1" aria-hidden="true" data-date-field-native>

        <button type="button" class="date-field__button" data-date-field-open aria-label="{{ __('common.choose_date') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        </button>

        <button type="button" class="date-field__clear" data-date-field-clear aria-label="{{ __('common.clear_date') }}" @if($display === '') hidden @endif>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    @if($hint)
        <p class="moderation-panel__hint" id="{{ $fieldId }}-hint" data-date-field-hint>{{ $hint }}</p>
    @endif

    <p class="date-field__error" data-date-field-error hidden>{{ __('common.invalid_date') }}</p>
</div>
