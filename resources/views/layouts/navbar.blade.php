<nav id="navbar" data-site-navbar class="fixed left-0 top-0 w-full max-w-full min-w-0 z-[900] transition-all duration-300 bg-[#102847] backdrop-blur-md border-b border-[#C9A84C]/30">
  <div class="navbar-inner w-full max-w-full min-w-0">
    <div class="navbar-flex w-full max-w-full min-w-0">
      @php
        $logoRoute = auth()->user()->homeRouteName();
        $logoUrl = route($logoRoute);
        $navRole = auth()->user()->role;
        $navDept = $navRole === 'staff' ? auth()->user()->department : null;
        $showNavIdentity = in_array($navRole, ['admin', 'staff'], true);
      @endphp
      <a href="{{ $logoUrl }}" class="navbar-logo group" aria-label="Daet Listens dashboard">
        <div class="logo-icon">
          <img src="/images/lgulogo.webp" alt="Daet LGU" class="logo-image" width="28" height="28">
        </div>
        <div class="logo-text">
          <span class="logo-name">DAET LISTENS</span>
          <span class="logo-sub">LGU Daet · Camarines Norte</span>
        </div>
        @if($navRole === 'staff')
          <span class="nav-dept-account" data-dept="{{ $navDept?->code }}" aria-label="{{ __('nav.department_account') }}{{ $navDept?->code ? ' · '.$navDept->code : '' }}">
            <svg class="nav-dept-account__icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M6 21V8l6-4 6 4v13"/><path d="M10 10h4"/></svg>
            <span class="nav-dept-account__text"><span class="nav-dept-account__label">{{ __('nav.department') }}</span>@if($navDept?->code)<span class="nav-dept-account__sep" aria-hidden="true"> · </span><span class="nav-dept-account__code">{{ $navDept->code }}</span>@endif</span>
          </span>
        @endif
      </a>

      <ul class="nav-links">
        @if(auth()->user()->role === 'citizen')
          <li><a href="{{ route('complaints.index') }}" wire:navigate class="nav-link {{ request()->routeIs('complaints.index', 'complaints.show', 'complaints.create') ? 'is-active' : '' }}" @if(request()->routeIs('complaints.index', 'complaints.show', 'complaints.create')) aria-current="page" @endif>{{ __('nav.my_complaints') }}</a></li>
          <li><a href="{{ url('/transparency') }}" wire:navigate class="nav-link">{{ __('nav.transparency') }}</a></li>
        @endif
        {{-- Transparency is available in the drawer/footer for staff accounts to
             keep the department header compact (avoids collision with the bell). --}}
      </ul>

      {{-- The bell is a direct child of the bar, NOT a member of .nav-actions.
           That list is hidden below 1241px in favour of the compact menu, so a
           bell inside it disappeared on tablet and phone. Placed here it stays
           visible at every width and still sits immediately left of whichever
           account control that width uses. --}}
      @include('layouts.partials.notification-bell')
      @if($navRole === 'admin')
        <span class="nav-identity-bar nav-identity nav-identity--on-dark" aria-label="{{ __('nav.administrator') }}">
          <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
        </span>
      @endif
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
        {{-- No dashboard link here: the logo already routes to the role home
             (My Complaints for citizens, workspace for staff/admin), so the
             action area stays limited to account actions. --}}

        {{-- Filing a complaint is a citizen action. LGU operators manage the
             queue from their workspace, so the CTA stays out of their bar. --}}
        @if(auth()->user()->role === 'citizen' && !request()->routeIs('complaints.index'))
          <li><a href="{{ route('complaints.create') }}" class="btn-nav-cta">{{ __('nav.file_complaint') }}</a></li>
        @endif

        @if($navRole === 'admin')
          <li class="nav-identity nav-identity--on-dark" aria-label="{{ __('nav.administrator') }}">
            <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
          </li>
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
              @if($showNavIdentity)
                <span class="dropdown-identity">
                  @if($navRole === 'admin')
                    <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
                  @elseif($navDept)
                    <span class="dept-badge" data-dept="{{ $navDept->code }}">{{ $navDept->code }}</span>
                  @else
                    <span class="nav-identity__badge nav-identity__badge--staff">{{ __('nav.staff') }}</span>
                  @endif
                </span>
              @endif
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
            <form data-loading-submit method="POST" action="{{ route('logout') }}">
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
        @if($showNavIdentity && $navRole === 'admin')
          <span class="nav-identity nav-identity--on-dark" aria-label="{{ __('nav.administrator') }}">
            <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
          </span>
        @endif
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
                @if($showNavIdentity)
                  <span class="dropdown-identity">
                    @if($navRole === 'admin')
                      <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
                    @elseif($navDept)
                      <span class="dept-badge" data-dept="{{ $navDept->code }}">{{ $navDept->code }}</span>
                      <span class="dropdown-identity__name">{{ $navDept->name }}</span>
                    @else
                      <span class="nav-identity__badge nav-identity__badge--staff">{{ __('nav.staff') }}</span>
                      <span class="dropdown-identity__name">{{ __('nav.department_pending') }}</span>
                    @endif
                  </span>
                @endif
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
              <form data-loading-submit method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-logout">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                  {{ __('nav.sign_out') }}
                </button>
              </form>
            </div>
          </div>
        @else
          @if(auth()->user()->role === 'citizen' && !request()->routeIs('complaints.index'))
            <a href="{{ route('complaints.create') }}" class="btn-nav-cta nav-cta-sm hidden sm:flex">{{ __('nav.file') }}</a>
          @endif
          @if($navRole === 'admin')
            <span class="nav-identity nav-identity--on-dark" aria-label="{{ __('nav.administrator') }}">
              <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
            </span>
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

</nav>

{{-- Backdrop and drawer live outside <nav>: its backdrop-blur makes it a
     stacking context, so a panel inside it could never out-paint later page
     sections. See layouts/guest-navbar.blade.php. --}}
<div class="mobile-menu-backdrop" data-mobile-menu-backdrop aria-hidden="true"></div>
<div id="mobile-menu" data-mobile-menu aria-hidden="true" class="mobile-menu" inert>
  <div class="mobile-menu-header">
    <button type="button" class="mobile-menu-close" data-mobile-menu-toggle aria-controls="mobile-menu" aria-label="{{ __('nav.close_navigation') }}" aria-expanded="true">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
  </div>
    <div class="mobile-menu-inner">
      <div class="mobile-user-info">
        <div class="mobile-user-avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
        <div class="mobile-user-copy">
          <div class="mobile-user-name">{{ auth()->user()->full_name }}</div>
          <div class="mobile-user-email">{{ auth()->user()->email }}</div>
          @if($showNavIdentity)
            <div class="mobile-user-identity">
              @if($navRole === 'admin')
                <span class="nav-identity__badge nav-identity__badge--admin">{{ __('nav.administrator') }}</span>
              @elseif($navDept)
                <span class="dept-badge" data-dept="{{ $navDept->code }}">{{ $navDept->code }}</span>
                <span class="mobile-user-identity__label">{{ $navDept->name }}</span>
              @else
                <span class="nav-identity__badge nav-identity__badge--staff">{{ __('nav.staff') }}</span>
                <span class="mobile-user-identity__label">{{ __('nav.department_pending') }}</span>
              @endif
            </div>
          @endif
        </div>
      </div>
      <div class="mobile-divider"></div>

      @if(auth()->user()->role === 'citizen' && !request()->routeIs('complaints.index'))
        <a href="{{ route('complaints.create') }}" wire:navigate class="mobile-nav-link mobile-nav-link--primary">{{ __('nav.file_complaint') }}</a>
      @endif
      @if(auth()->user()->role === 'citizen')
        <a href="{{ route('complaints.index') }}" wire:navigate class="mobile-nav-link{{ request()->routeIs('complaints.index', 'complaints.show', 'complaints.create') ? ' is-active' : '' }}" @if(request()->routeIs('complaints.index', 'complaints.show', 'complaints.create')) aria-current="page" @endif>{{ __('nav.my_complaints') }}</a>
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

      @if(auth()->user()->role === 'admin')
        <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">{{ __('nav.admin_dashboard') }}</a>
      @elseif(auth()->user()->role === 'staff')
        <a href="{{ route('staff.dashboard') }}" class="mobile-nav-link">{{ __('nav.staff_dashboard') }}</a>
      @endif
      <div class="mobile-divider"></div>

      <form data-loading-submit method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="mobile-nav-link mobile-logout">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          {{ __('nav.sign_out') }}
        </button>
      </form>
    </div>
</div>
