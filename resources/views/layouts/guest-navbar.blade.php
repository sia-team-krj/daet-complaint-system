<nav id="navbar" data-site-navbar class="fixed left-0 top-0 w-full max-w-full min-w-0 z-[900] transition-all duration-300 bg-[#102847] backdrop-blur-md border-b border-[#C9A84C]/30">
  <div class="navbar-inner w-full max-w-full min-w-0">
    <div class="navbar-flex w-full max-w-full min-w-0">
      <a href="{{ url('/') }}" class="navbar-logo group" aria-label="Daet Listens home">
        <div class="logo-icon">
          <img src="/images/lgulogo.webp" alt="Daet LGU" class="logo-image" width="28" height="28">
        </div>
        <div class="logo-text">
          <span class="logo-name">DAET LISTENS</span>
          <span class="logo-sub">LGU Daet · Camarines Norte</span>
        </div>
      </a>

      <ul class="nav-links {{ Request::is('transparency') ? 'active-ui' : '' }}">
        <li><a href="{{ url('/') }}" wire:navigate class="nav-link">{{ __('nav.home') }}</a></li>
        <li><a href="/#process" class="nav-link">{{ __('nav.process') }}</a></li>
        <li><a href="/#features" class="nav-link">{{ __('nav.features') }}</a></li>
        <li><a href="{{ url('/transparency') }}" wire:navigate class="nav-link">{{ __('nav.transparency') }}</a></li>
        {{-- No Terms or Privacy here on purpose. They live in the site footer.
             The bar is the wayfinding surface; two more items crowded the links
             residents use and pushed the account controls into the compact menu
             sooner. They are still reachable from the footer on every page. --}}
      </ul>

      <ul class="nav-actions">
        <li class="nav-lang-item">
          @include('layouts.partials.language-switcher', ['variant' => 'compact'])
        </li>
        <li><a href="{{ route('login') }}" wire:navigate class="nav-link">{{ __('nav.login') }}</a></li>
        <li><a href="{{ route('register') }}" wire:navigate class="btn-nav-cta">{{ __('nav.get_started') }}</a></li>
      </ul>

      <div class="nav-mobile-right">
        <a href="{{ route('register') }}" wire:navigate class="btn-nav-cta nav-cta-sm hidden sm:flex">{{ __('nav.get_started') }}</a>
        <button id="mobile-menu-btn" data-mobile-menu-toggle aria-controls="mobile-menu" aria-label="{{ __('nav.open_navigation') }}" aria-expanded="false" class="hamburger-btn" type="button">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </div>
</nav>

{{-- The backdrop and drawer stay OUTSIDE <nav>: the navbar has backdrop-blur,
     which makes it a stacking context, so a panel inside it could never
     out-paint later page sections. The drawer is fixed at the root level
     (backdrop 1000, panel 1010) and the hamburger stays the open toggle. --}}
@include('layouts.partials.guest-drawer')
