@php
    /**
     * The save row's quiet line.
     *
     * It says what happened to the form this card owns: "Saved" after a
     * successful save, "You have unsaved changes" once the office has typed
     * something. It stays where the eye already is — with the fields it is
     * talking about — instead of appearing as a banner that pushes the form
     * down.
     *
     * @var string $section
     */
    $message = (session('status_for') ?? null) === $section ? (string) session('status') : '';
@endphp
<p class="settings-save-row__state @if($message) is-saved @endif" data-save-state role="status" aria-live="polite" data-dirty-message="{{ __('settings.profile.unsaved') }}">
    @if($message)
        <span class="settings-save-row__tick" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
        {{ $message }}
    @endif
</p>
