<nav id="navbar" data-site-navbar class="fixed left-0 top-0 w-full max-w-full min-w-0 z-50 transition-all duration-300 bg-[#102847] backdrop-blur-md border-b border-[#C9A84C]/30">
  <div class="navbar-inner w-full max-w-full min-w-0">
    <div class="navbar-flex w-full max-w-full min-w-0">
      @php
        $logoRoute = match (auth()->user()->role) {
          'admin' => route('admin.dashboard'),
          'staff' => route('staff.dashboard'),
          default => route('dashboard'),
        };
      @endphp
      <a href="{{ $logoRoute }}" class="navbar-logo group" aria-label="Daet Listens dashboard">
        <div class="logo-icon">
          <img src="/images/lgulogo.webp" alt="Daet LGU" class="logo-image" width="28" height="28">
        </div>
        <div class="logo-text">
          <span class="logo-name">DAET LISTENS</span>
          <span class="logo-sub">LGU Daet · Camarines Norte</span>
        </div>
      </a>

      <ul class="nav-links">
        @if(auth()->user()->role === 'citizen')
          <li><a href="{{ route('dashboard') }}" wire:navigate class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>{{ __('nav.dashboard') }}</a></li>
          <li><a href="{{ route('complaints.index') }}" wire:navigate class="nav-link {{ request()->routeIs('complaints.index') ? 'is-active' : '' }}" @if(request()->routeIs('complaints.index')) aria-current="page" @endif>{{ __('nav.my_complaints') }}</a></li>
        @endif
        @if(auth()->user()->role !== 'admin')
          <li><a href="{{ url('/transparency') }}" wire:navigate class="nav-link">{{ __('nav.transparency') }}</a></li>
        @endif
        {{-- No Terms or Privacy here on purpose - they live in the site footer,
             which renders on every page including the admin and staff shells, so
             a signed-in operator can still read them. The registration consent
             checkbox links them directly. --}}
      </ul>

      {{-- The bell is a direct child of the bar, NOT a member of .nav-actions.
           That list is hidden below 1241px in favour of the compact menu, so a
           bell inside it disappeared on tablet and phone. Placed here it stays
           visible at every width and still sits immediately left of whichever
           account control that width uses. --}}
      @include('layouts.partials.notification-bell')
      <button
        type="button"
        class="mobile-drawer-toggle"
        data-mobile-drawer-toggle
        aria-controls="mobile-nav-drawer"
        aria-expanded="false"
        aria-label="{{ __('nav.open_navigation') }}"
      >
        <span class="mobile-drawer-toggle__bars" aria-hidden="true">
          <span></span><span></span><span></span>
        </span>
      </button>

      <ul class="nav-actions">
        {{-- No dashboard link here: the logo already routes to the admin or
             staff workspace, so the action area stays limited to account actions. --}}

        {{-- Filing a complaint is a citizen action. LGU operators manage the
             queue from their workspace, so the CTA stays out of their bar. --}}
        @if(auth()->user()->role === 'citizen' && !request()->routeIs('dashboard'))
          <li><a href="{{ route('complaints.create') }}" class="btn-nav-cta">{{ __('nav.file_complaint') }}</a></li>
        @endif

        <li class="nav-dropdown-wrap">
          <button type="button" class="nav-user-btn" id="user-menu-btn" data-user-menu-toggle aria-controls="user-dropdown" aria-expanded="false">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
            <span class="user-name">{{ auth()->user()->first_name }}</span>
            <svg class="dropdown-chevron" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>

          <div class="nav-dropdown" id="user-dropdown" data-user-dropdown aria-hidden="true">
            <div class="dropdown-header">
              <span class="dropdown-name">{{ auth()->user()->full_name }}</span>
              <span class="dropdown-email">{{ auth()->user()->email }}</span>
            </div>
            <div class="dropdown-divider"></div>
            @if(auth()->user()->role === 'admin')
              <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                {{ __('nav.admin_dashboard') }}
              </a>
            @endif
            @if(auth()->user()->role !== 'admin')
            <a href="{{ route('complaints.index') }}" class="dropdown-item" wire:navigate>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              {{ __('nav.my_complaints') }}
            </a>
            @endif
            <div class="dropdown-divider"></div>
            <div class="dropdown-language">
              <span class="dropdown-language__label">{{ __('nav.language') }}</span>
              @include('layouts.partials.language-switcher', ['variant' => 'compact'])
            </div>
            <a href="{{ route('settings', ['tab' => 'preferences']) }}" class="dropdown-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              {{ __('nav.settings') }}
            </a>
            <div class="dropdown-divider"></div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item dropdown-logout">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                {{ __('nav.sign_out') }}
              </button>
            </form>
          </div>
        </li>
      </ul>

      <div class="nav-mobile-right">
        @if(auth()->user()->role === 'admin')
          {{-- Admins keep the left workspace drawer for sections; the top-right
               trigger is an account popover, matching the desktop hierarchy. --}}
          <div class="nav-mobile-account">
            <button
              type="button"
              id="mobile-user-menu-btn"
              class="nav-user-btn nav-user-btn--mobile"
              data-user-menu-toggle
              aria-controls="mobile-user-dropdown"
              aria-expanded="false"
              aria-label="{{ __('nav.open_account') }}"
            >
              <span class="user-avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</span>
              <span class="user-name">{{ auth()->user()->first_name }}</span>
              <svg class="dropdown-chevron" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>

            <div class="nav-dropdown nav-dropdown--mobile" id="mobile-user-dropdown" data-user-dropdown aria-hidden="true">
              <div class="dropdown-header">
                <span class="dropdown-name">{{ auth()->user()->full_name }}</span>
                <span class="dropdown-email">{{ auth()->user()->email }}</span>
              </div>
              <div class="dropdown-divider"></div>
              @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                  {{ __('nav.admin_dashboard') }}
                </a>
              @endif
              @if(auth()->user()->role !== 'admin')
              <a href="{{ route('complaints.index') }}" class="dropdown-item" wire:navigate>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                {{ __('nav.my_complaints') }}
              </a>
              @endif
              <div class="dropdown-divider"></div>
              <div class="dropdown-language">
                <span class="dropdown-language__label">{{ __('nav.language') }}</span>
                @include('layouts.partials.language-switcher', ['variant' => 'compact'])
              </div>
              <a href="{{ route('settings', ['tab' => 'preferences']) }}" class="dropdown-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                {{ __('nav.settings') }}
              </a>
              <div class="dropdown-divider"></div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-logout">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                  {{ __('nav.sign_out') }}
                </button>
              </form>
            </div>
          </div>
        @else
          @if(auth()->user()->role === 'citizen' && !request()->routeIs('dashboard'))
            <a href="{{ route('complaints.create') }}" class="btn-nav-cta nav-cta-sm hidden sm:flex">{{ __('nav.file') }}</a>
          @endif
          <button id="mobile-menu-btn" data-mobile-menu-toggle aria-controls="mobile-menu" aria-label="{{ __('nav.open_navigation') }}" aria-expanded="false" class="hamburger-btn" type="button">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
          </button>
        @endif
      </div>
    </div>
  </div>

  <div id="mobile-menu" data-mobile-menu aria-hidden="true" class="mobile-menu" inert>
    <div class="mobile-menu-inner">
      <div class="mobile-user-info">
        <div class="mobile-user-avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
        <div class="mobile-user-copy">
          <div class="mobile-user-name">{{ auth()->user()->full_name }}</div>
          <div class="mobile-user-email">{{ auth()->user()->email }}</div>
        </div>
      </div>
      <div class="mobile-divider"></div>

      @if(auth()->user()->role === 'citizen')
        <a href="{{ route('dashboard') }}" wire:navigate class="mobile-nav-link">{{ __('nav.dashboard') }}</a>
      @endif
      @if(auth()->user()->role !== 'admin')
        <a href="{{ url('/transparency') }}" wire:navigate class="mobile-nav-link">{{ __('nav.transparency') }}</a>
      @endif
      <div class="mobile-divider"></div>

      <div class="mobile-nav-language">
        <span class="mobile-nav-language__label">{{ __('nav.language') }}</span>
        @include('layouts.partials.language-switcher', ['variant' => 'mobile'])
      </div>
      <div class="mobile-divider"></div>

      <a href="{{ route('settings', ['tab' => 'preferences']) }}" class="mobile-nav-link">{{ __('nav.settings') }}</a>
      @if(auth()->user()->role !== 'admin')
        <a href="{{ route('complaints.index') }}" wire:navigate class="mobile-nav-link">{{ __('nav.my_complaints') }}</a>
      @endif
      @if(auth()->user()->role === 'admin')
        <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">{{ __('nav.admin_dashboard') }}</a>
      @elseif(auth()->user()->role === 'staff')
        <a href="{{ route('staff.dashboard') }}" class="mobile-nav-link">{{ __('nav.staff_dashboard') }}</a>
      @endif
      <div class="mobile-divider"></div>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="mobile-nav-link mobile-logout">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          {{ __('nav.sign_out') }}
        </button>
      </form>
    </div>
  </div>
</nav>
