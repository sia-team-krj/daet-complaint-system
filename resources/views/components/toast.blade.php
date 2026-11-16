@props([
    'message',
    'variant' => 'success',
    'duration' => 4000,
    'preview' => false,
])

{{--
    A status toast.

    It renders as an ordinary inline notice first: without JavaScript the
    message is still on the page, in the flow, with no close button that would
    do nothing. `toast.js` upgrades it in the browser — fixed under the navbar,
    fades in, auto-dismisses, pauses on hover/focus, closes on the button or
    Escape — and removes it from the DOM when it is done.

    Error toasts never auto-dismiss: a refusal is a fact the reader should not
    have to catch before it disappears.

    Docs: docs/design-system.md §5.48.
--}}
@php
    $isError = $variant === 'error';
    $autoDismiss = $isError ? 0 : (int) $duration;
@endphp

<div
    @class([
        'toast',
        'toast--'.$variant,
        'toast--preview' => $preview,
    ])
    @unless($preview)
        data-toast
        data-toast-duration="{{ $autoDismiss }}"
        role="{{ $isError ? 'alert' : 'status' }}"
    @endunless
>
    <span class="toast__icon" aria-hidden="true">
        @if($isError)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 10"/></svg>
        @endif
    </span>

    <p class="toast__message">{{ $message }}</p>

    @unless($preview)
        <button type="button" class="toast__close" data-toast-close aria-label="{{ __('common.dismiss') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    @endunless
</div>
