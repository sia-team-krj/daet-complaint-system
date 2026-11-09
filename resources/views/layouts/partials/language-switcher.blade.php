@php
    $locales = config('localization.supported', []);
    $current = app()->getLocale();
    $variant = $variant ?? 'compact';
@endphp

<form data-loading-submit
    method="POST"
    action="{{ route('locale.update') }}"
    class="lang-switch lang-switch--{{ $variant }}"
    role="group"
    aria-label="{{ __('nav.language') }}"
    @auth data-instant @endauth
>
    @csrf

    <span class="lang-switch__globe" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M3 12h18"></path>
            <path d="M12 3a14.5 14.5 0 0 1 0 18"></path>
            <path d="M12 3a14.5 14.5 0 0 0 0 18"></path>
        </svg>
    </span>

    @foreach($locales as $code => $label)
        <button
            type="submit"
            name="locale"
            value="{{ $code }}"
            class="lang-switch__option {{ $current === $code ? 'is-active' : '' }}"
            lang="{{ $code }}"
            title="{{ $label }}"
            aria-label="{{ $label }}"
            aria-pressed="{{ $current === $code ? 'true' : 'false' }}"
            data-locale-choice="{{ $code }}"
        >
            <span class="lang-switch__label">{{ $label }}</span>
            <span class="lang-switch__code">{{ $code === 'fil' ? 'FIL' : strtoupper($code) }}</span>
        </button>
    @endforeach
</form>

@once
<script>
(function () {
  // Guest choice lives in localStorage (per spec) and mirrors the cookie.
  try {
    var stored = localStorage.getItem('daet_locale');
    if (stored && stored !== '{{ $current }}' && !document.querySelector('[data-user-dropdown]')) {
      // Logged-out only: if the stored choice differs from the server
      // locale, apply it via the switcher form without a manual click.
      var form = document.querySelector('.lang-switch form, form.lang-switch');
      var btn = form && form.querySelector('[data-locale-choice="' + stored + '"]');
      if (form && btn && !btn.classList.contains('is-active')) {
        btn.click();
      }
    }
  } catch (e) {}

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-locale-choice]');
    if (!btn) return;
    try { localStorage.setItem('daet_locale', btn.getAttribute('data-locale-choice')); } catch (e) {}
    // Immediate, no-reload switch for logged-in users via fetch; guests
    // fall back to the normal form POST (full load picks up the cookie).
    var form = btn.closest('form.lang-switch');
    if (form && form.hasAttribute('data-instant')) {
      event.preventDefault();
      // NOTE: new FormData(form) does NOT include the clicked submit
      // button's name=value, so locale must be set explicitly or the
      // server validation fails and nothing changes.
      var data = new FormData(form);
      data.set('locale', btn.getAttribute('data-locale-choice'));
      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value },
        body: data
      }).then(function (response) {
        if (!response.ok) throw new Error('locale switch failed');
        document.documentElement.lang = btn.getAttribute('data-locale-choice');
        window.location.reload();
      }).catch(function () { form.submit(); });
    }
  });
})();
</script>
@endonce

@once
<style>
    .lang-switch {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 3px;
        border: 1px solid rgba(201, 168, 76, 0.28);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.04);
        flex-shrink: 0;
    }

    .lang-switch__globe {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        color: rgba(255, 255, 255, 0.55);
        flex: 0 0 auto;
    }

    .lang-switch__option {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 4px 8px;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: rgba(255, 255, 255, 0.72);
        font-family: var(--font-sans, ui-sans-serif, system-ui, sans-serif);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        line-height: 1;
        cursor: pointer;
        transition: background 0.18s ease, color 0.18s ease;
    }

    .lang-switch__option:hover,
    .lang-switch__option:focus-visible {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.08);
    }

    .lang-switch__option.is-active {
        background: var(--color-gold, #c9a84c);
        color: var(--color-navy, #142d4f);
    }

    .lang-switch__option.is-active:hover,
    .lang-switch__option.is-active:focus-visible {
        background: var(--color-gold-light, #e2c06a);
        color: var(--color-navy, #142d4f);
    }

    .lang-switch__label {
        display: none;
    }

    .lang-switch__code {
        display: inline;
    }

    .lang-switch--mobile,
    .lang-switch--footer {
        width: 100%;
        justify-content: flex-start;
        padding: 5px;
        border-radius: 12px;
    }

    .lang-switch--mobile .lang-switch__label,
    .lang-switch--footer .lang-switch__label {
        display: inline;
    }

    .lang-switch--mobile .lang-switch__code,
    .lang-switch--footer .lang-switch__code {
        display: none;
    }

    .lang-switch--mobile .lang-switch__option,
    .lang-switch--footer .lang-switch__option {
        flex: 1;
        min-height: 44px;
        padding: 8px 10px;
        font-size: 12px;
        letter-spacing: 0.02em;
    }

    .lang-switch--footer {
        background: transparent;
        border-color: rgba(255, 255, 255, 0.16);
    }

    .lang-switch--footer .lang-switch__option {
        color: rgba(255, 255, 255, 0.62);
    }

    .lang-switch--footer .lang-switch__option:hover,
    .lang-switch--footer .lang-switch__option:focus-visible {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.08);
    }

    .lang-switch--footer .lang-switch__option.is-active {
        background: var(--color-gold, #c9a84c);
        color: var(--color-navy, #142d4f);
    }

    .mobile-nav-drawer__language {
        padding: 4px 16px 12px;
    }
</style>
@endonce
