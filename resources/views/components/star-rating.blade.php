@props([
    'name',
    'value' => null,
    'label' => null,
    'id' => null,
    'hint' => null,
])

{{--
    A star rating.

    Five real radio buttons under five stars: the stars are the picture, the
    radios are the control, so it works with a keyboard and a screen reader
    and needs no JavaScript at all. The radios are laid out ascending and the
    row is reversed, which is what makes the CSS able to fill the stars from
    the left as the rating rises.

    Docs: docs/design-system.md §5.46.
--}}
@php
    $fieldId = $id ?: ('stars-'.preg_replace('/[^a-z0-9]+/i', '-', $name));
@endphp
<fieldset class="star-rating" @if($id) id="{{ $id }}" @endif>
    @if($label)
        <legend class="star-rating__legend">{{ $label }}</legend>
    @endif

    <div class="star-rating__stars">
        @for($i = 1; $i <= 5; $i++)
            <input type="radio" id="{{ $fieldId }}-{{ $i }}" name="{{ $name }}" value="{{ $i }}"
                   @checked((int) $value === $i)>
            <label for="{{ $fieldId }}-{{ $i }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <span class="sr-only">{{ __('feedback.stars.'.$i, ['count' => $i]) }}</span>
            </label>
        @endfor
    </div>

    @if($hint)
        <p class="star-rating__hint">{{ $hint }}</p>
    @endif
</fieldset>
