{{--
    Guest-only mobile drawer (right side). Rendered only on guest pages.
    Self-contained styles so it never leaks into authenticated menus.
    JS contract kept: #mobile-menu, data-mobile-menu, data-mobile-menu-backdrop.
--}}
@guest
<div class="guest-drawer-backdrop" data-mobile-menu-backdrop aria-hidden="true"></div>
<aside id="mobile-menu" data-mobile-menu class="guest-drawer" aria-hidden="true" inert role="dialog" aria-modal="true" aria-label="{{ __('nav.navigation') }}">
  <header class="guest-drawer__header">
    <div class="guest-drawer__brand">
      <img src="/images/lgulogo.webp" alt="" class="guest-drawer__logo" width="36" height="36" aria-hidden="true">
      <div class="guest-drawer__brand-text">
        <span class="guest-drawer__brand-name">DAET LISTENS</span>
        <span class="guest-drawer__brand-sub">{{ __('footer.municipality') }}</span>
      </div>
    </div>
    <button type="button" class="guest-drawer__close" data-mobile-menu-toggle aria-controls="mobile-menu" aria-label="{{ __('nav.close_navigation') }}" aria-expanded="true">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
  </header>

  <nav class="guest-drawer__nav" aria-label="{{ __('nav.main_navigation') }}">
    <a href="{{ url('/') }}" wire:navigate class="guest-drawer__row {{ request()->is('/') ? 'is-active' : '' }}">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"></path><path d="M5 10v10h14V10"></path></svg>
      <span>{{ __('nav.home') }}</span>
    </a>
    <a href="{{ url('/') }}#process" class="guest-drawer__row">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
      <span>{{ __('nav.process') }}</span>
    </a>
    <a href="{{ url('/') }}#features" class="guest-drawer__row">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>
      <span>{{ __('nav.features') }}</span>
    </a>
    <a href="{{ url('/transparency') }}" wire:navigate class="guest-drawer__row {{ request()->is('transparency*') ? 'is-active' : '' }}">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"></path><path d="m7 14 3-3 3 3 5-6"></path></svg>
      <span>{{ __('nav.transparency') }}</span>
    </a>
  </nav>

  <div class="guest-drawer__divider" aria-hidden="true"></div>

  <div class="guest-drawer__lang">
    <div class="guest-drawer__lang-label">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M2 12h20"></path><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
      <span>{{ __('nav.language') }}</span>
    </div>
    @include('layouts.partials.language-switcher', ['variant' => 'mobile'])
  </div>

  <div class="guest-drawer__divider" aria-hidden="true"></div>

  <footer class="guest-drawer__actions">
    <a href="{{ route('login') }}" wire:navigate class="guest-drawer__btn guest-drawer__btn--secondary">
      <span>{{ __('nav.login') }}</span>
    </a>
    <a href="{{ route('register') }}" wire:navigate class="guest-drawer__btn guest-drawer__btn--primary">
      <span>{{ __('nav.get_started') }}</span>
    </a>
  </footer>
</aside>

<style>
  .guest-drawer-backdrop {
      position: fixed;
      inset: 0;
      z-index: var(--z-drawer-backdrop, 1000);
      background: rgba(7, 18, 38, 0.6);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity 180ms ease, visibility 0s linear 320ms;
  }
  .guest-drawer-backdrop.is-open {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      transition-delay: 0s;
  }

  .guest-drawer {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      height: 100dvh;
      width: min(86vw, 340px);
      max-width: 100vw;
      z-index: var(--z-drawer, 1010);
      background: var(--navy-900);
      border-left: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: -12px 0 32px rgba(7, 18, 38, 0.45);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transform: translateX(100%);
      transform-origin: right center;
      transition: transform 220ms ease-out, opacity 180ms ease, visibility 0s linear 320ms;
      will-change: transform;
      -webkit-overflow-scrolling: touch;
      font-family: var(--font-sans);
  }
  .guest-drawer.is-open {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      transform: translateX(0);
      transition-delay: 0s;
  }

  .guest-drawer__header {
      flex: 0 0 auto;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 0 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }
  .guest-drawer__brand {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
  }
  .guest-drawer__logo {
      width: 36px;
      height: 36px;
      object-fit: contain;
      flex-shrink: 0;
      border-radius: var(--radius-sm);
  }
  .guest-drawer__brand-text {
      display: flex;
      flex-direction: column;
      gap: 1px;
      min-width: 0;
  }
  .guest-drawer__brand-name {
      font-family: var(--font-sans);
      font-size: 15px;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: var(--surface);
      white-space: nowrap;
  }
  .guest-drawer__brand-sub {
      font-family: var(--font-sans);
      font-size: 11px;
      color: rgba(255, 255, 255, 0.62);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
  }
  .guest-drawer__close {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
      padding: 0;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      background: var(--navy-800);
      color: var(--surface);
      cursor: pointer;
      transition: background 0.18s, color 0.18s;
  }
  .guest-drawer__close:hover,
  .guest-drawer__close:focus-visible {
      background: rgba(255, 255, 255, 0.06);
      color: var(--gold-300);
  }
  .guest-drawer__close:focus-visible {
      outline: 2px solid var(--gold-500);
      outline-offset: 2px;
  }

  .guest-drawer__nav {
      flex: 1 1 auto;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 4px;
      padding: 12px;
  }
  .guest-drawer__row {
      display: flex;
      align-items: center;
      gap: 14px;
      min-height: 52px;
      padding: 0 16px;
      margin: 0 12px;
      border-radius: 12px;
      text-decoration: none;
      color: var(--navy-100);
      transition: background 0.18s, color 0.18s;
  }
  .guest-drawer__row svg {
      width: 20px;
      height: 20px;
      flex: none;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.75;
      stroke-linecap: round;
      stroke-linejoin: round;
      color: rgba(159, 179, 209, 1);
  }
  .guest-drawer__row span {
      font-family: var(--font-sans);
      font-size: 15px;
      font-weight: 600;
      letter-spacing: 0.02em;
      color: var(--navy-100);
      text-transform: none;
  }
  .guest-drawer__row:hover,
  .guest-drawer__row:focus-visible,
  .guest-drawer__row:active {
      background: rgba(255, 255, 255, 0.06);
  }
  .guest-drawer__row:hover svg,
  .guest-drawer__row:focus-visible svg,
  .guest-drawer__row:active svg,
  .guest-drawer__row:hover span,
  .guest-drawer__row:focus-visible span,
  .guest-drawer__row:active span {
      color: var(--gold-300);
  }
  .guest-drawer__row.is-active,
  .guest-drawer__row[aria-current="page"] {
      background: rgba(212, 169, 71, 0.14);
  }
  .guest-drawer__row.is-active svg,
  .guest-drawer__row[aria-current="page"] svg {
      color: var(--gold-300);
  }
  .guest-drawer__row.is-active span,
  .guest-drawer__row[aria-current="page"] span {
      color: var(--surface);
  }

  .guest-drawer__divider {
      height: 1px;
      background: rgba(255, 255, 255, 0.08);
      margin: 0 16px;
  }

  .guest-drawer__lang {
      flex: 0 0 auto;
      padding: 24px 16px 12px;
      display: flex;
      flex-direction: column;
      gap: 12px;
  }
  .guest-drawer__lang-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-family: var(--font-sans);
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: rgba(159, 179, 209, 1);
  }
  .guest-drawer__lang-label svg {
      width: 18px;
      height: 18px;
      flex: none;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.75;
      color: rgba(159, 179, 209, 1);
  }
  .guest-drawer__lang .language-switcher,
  .guest-drawer__lang .mobile-nav-language,
  .guest-drawer__lang .language-switcher__* {
      width: 100%;
  }

  .guest-drawer .lang-switch--mobile {
      background: var(--navy-800);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 14px;
      padding: 4px;
      min-height: 48px;
      height: 48px;
      width: 100%;
      gap: 4px;
  }
  .guest-drawer .lang-switch--mobile .lang-switch__option {
      border-radius: 10px;
      min-height: 40px;
      height: 40px;
      font-size: 14px;
      font-weight: 600;
      color: var(--navy-100);
      background: transparent;
  }
  .guest-drawer .lang-switch--mobile .lang-switch__option.is-active {
      background: var(--gold-500);
      color: var(--navy-900);
  }
  .guest-drawer .lang-switch--mobile .lang-switch__option:hover,
  .guest-drawer .lang-switch--mobile .lang-switch__option:focus-visible {
      color: var(--surface);
      background: rgba(255, 255, 255, 0.06);
  }
  .guest-drawer .lang-switch--mobile .lang-switch__option.is-active:hover,
  .guest-drawer .lang-switch--mobile .lang-switch__option.is-active:focus-visible {
      background: var(--gold-300);
      color: var(--navy-900);
  }
  /* Segmented EN | FIL control styling if the switcher renders buttons */
  .guest-drawer__lang button,
  .guest-drawer__lang a {
      min-height: 40px;
  }

  .guest-drawer__actions {
      flex: 0 0 auto;
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 12px 16px max(16px, env(safe-area-inset-bottom));
      border-top: 1px solid rgba(255, 255, 255, 0.08);
  }
  .guest-drawer__btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 52px;
      height: 52px;
      border-radius: 14px;
      font-family: var(--font-sans);
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      white-space: nowrap;
      transition: background 0.18s, color 0.18s, border-color 0.18s;
  }
  .guest-drawer__btn--secondary {
      background: transparent;
      border: 1.5px solid rgba(255, 255, 255, 0.28);
      color: var(--surface);
  }
  .guest-drawer__btn--secondary:hover,
  .guest-drawer__btn--secondary:focus-visible {
      background: rgba(255, 255, 255, 0.06);
  }
  .guest-drawer__btn--primary {
      background: var(--gold-500);
      border: 1.5px solid var(--gold-500);
      color: var(--navy-900);
      font-weight: 700;
      box-shadow: 0 6px 16px rgba(212, 169, 71, 0.25);
  }
  .guest-drawer__btn--primary:hover,
  .guest-drawer__btn--primary:focus-visible {
      background: rgba(194, 154, 60, 1);
      border-color: rgba(194, 154, 60, 1);
  }
  .guest-drawer__btn:focus-visible {
      outline: 2px solid var(--gold-500);
      outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
      .guest-drawer,
      .guest-drawer-backdrop {
          transition: none;
      }
  }
</style>

<script>
  (function () {
    var drawer = document.getElementById('mobile-menu');
    if (!drawer) return;
    drawer.addEventListener('click', function (e) {
      var row = e.target && e.target.closest ? e.target.closest('.guest-drawer__row') : null;
      if (!row) return;
      drawer.querySelectorAll('.guest-drawer__row').forEach(function (r) { r.classList.remove('is-active'); r.removeAttribute('aria-current'); });
      row.classList.add('is-active');
      row.setAttribute('aria-current', 'page');
    });
  })();
</script>
@endguest