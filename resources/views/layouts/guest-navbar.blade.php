<nav id="navbar" data-site-navbar class="fixed left-0 top-0 w-full max-w-full min-w-0 z-50 transition-all duration-300 bg-[#0B1F3A] backdrop-blur-md border-b border-[#C9A84C]/30">
  <div class="navbar-inner w-full max-w-full min-w-0">
    <div class="navbar-flex w-full max-w-full min-w-0">
      <a href="{{ url('/') }}" class="navbar-logo group" aria-label="Daet Listens home">
        <div class="logo-icon group-hover:scale-105 transition-transform duration-200">
          <img src="/images/lgulogo.png" alt="Daet LGU" class="logo-image" width="28" height="28">
        </div>
        <div class="logo-text">
          <span class="logo-name">Daet Listens</span>
          <span class="logo-sub">LGU Daet · Camarines Norte</span>
        </div>
      </a>

      <ul class="nav-links {{ Request::is('transparency') ? 'active-ui' : '' }}">
        <li><a href="{{ url('/') }}" wire:navigate class="nav-link">Home</a></li>
        <li><a href="/#process" class="nav-link">Process</a></li>
        <li><a href="/#features" class="nav-link">Features</a></li>
        <li><a href="{{ url('/transparency') }}" wire:navigate class="nav-link">Transparency</a></li>
        <li><a href="{{ url('/rewards') }}" wire:navigate class="nav-link">Rewards</a></li>
      </ul>

      <ul class="nav-actions">
        <li><a href="{{ route('login') }}" wire:navigate class="nav-link">Login</a></li>
        <li><a href="{{ route('register') }}" wire:navigate class="btn-nav-cta">Get Started</a></li>
      </ul>

      <div class="nav-mobile-right">
        <a href="{{ route('register') }}" wire:navigate class="btn-nav-cta nav-cta-sm hidden sm:flex">Get Started</a>
        <button id="mobile-menu-btn" data-mobile-menu-toggle aria-controls="mobile-menu" aria-label="Open navigation menu" aria-expanded="false" class="hamburger-btn" type="button">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </div>

  <div id="mobile-menu" data-mobile-menu aria-hidden="true" class="mobile-menu hidden">
    <div class="mobile-menu-inner">
      <a href="{{ url('/') }}" wire:navigate class="mobile-nav-link">Home</a>
      <a href="{{ url('/') }}#process" class="mobile-nav-link">Process</a>
      <a href="{{ url('/transparency') }}" wire:navigate class="mobile-nav-link">Transparency</a>
      <a href="{{ url('/') }}#features" class="mobile-nav-link">Features</a>
      <a href="{{ url('/rewards') }}" wire:navigate class="mobile-nav-link">Rewards</a>
      <div class="mobile-divider"></div>
      <a href="{{ route('login') }}" wire:navigate class="mobile-nav-link">Login</a>
      <a href="{{ route('register') }}" wire:navigate class="mobile-nav-link mobile-nav-link--accent">Get Started</a>
    </div>
  </div>
</nav>
