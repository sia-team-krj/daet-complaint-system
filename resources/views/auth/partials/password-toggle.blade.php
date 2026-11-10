{{-- Password visibility toggle (docs/design-system.md §5.41).
     Rendered inside .field-wrap, on the right edge of a password input.
     Stays hidden until the input has text; then flips the input between
     type="password" and type="text" so the resident can read back what they
     typed. Two feather glyphs (eye / eye-off) cross-fade on toggle; the
     accessible name flips with it (auth_ui.common.show_password /
     auth_ui.common.hide_password via the data-label-* attributes). --}}
@php
    $target = $target ?? 'password';
@endphp

<button type="button"
        class="field-toggle"
        data-password-visibility-toggle="{{ $target }}"
        data-label-show="{{ __('auth_ui.common.show_password') }}"
        data-label-hide="{{ __('auth_ui.common.hide_password') }}"
        aria-controls="{{ $target }}"
        aria-pressed="false"
        aria-label="{{ __('auth_ui.common.show_password') }}"
        tabindex="-1">
  <svg class="field-toggle__icon field-toggle__icon--show" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
  <svg class="field-toggle__icon field-toggle__icon--hide" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
</button>
