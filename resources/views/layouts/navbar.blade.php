<nav id="navbar" data-site-navbar class="fixed left-0 top-0 w-full max-w-full min-w-0 z-50 transition-all duration-300 bg-[#0B1F3A] backdrop-blur-md border-b border-[#C9A84C]/30">
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
        <div class="logo-icon group-hover:scale-105 transition-transform duration-200">
          <img src="/images/lgulogo.png" alt="Daet LGU" class="logo-image" width="28" height="28">
        </div>
        <div class="logo-text">
          <span class="logo-name">Daet Listens</span>
          <span class="logo-sub">LGU Daet · Camarines Norte</span>
        </div>
      </a>

      <ul class="nav-links">
        @if(auth()->user()->role === 'citizen')
          <li><a href="{{ route('dashboard') }}" wire:navigate class="nav-link">Dashboard</a></li>
        @endif
        <li><a href="{{ url('/transparency') }}" wire:navigate class="nav-link">Transparency</a></li>
        <li><a href="{{ url('/rewards') }}" wire:navigate class="nav-link">Rewards</a></li>
      </ul>

      <ul class="nav-actions">
        @if(auth()->user()->role === 'admin')
          <li><a href="{{ route('admin.dashboard') }}" class="nav-link">Admin Dashboard</a></li>
        @elseif(auth()->user()->role === 'staff')
          <li><a href="{{ route('staff.dashboard') }}" class="nav-link">Staff Dashboard</a></li>
        @endif

        <li><a href="{{ route('complaints.create') }}" class="btn-nav-cta">File a Complaint</a></li>

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
            <a href="{{ route('profile') }}" class="dropdown-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              My Account
            </a>
            <a href="{{ route('complaints.index') }}" class="dropdown-item" wire:navigate>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              My Complaints
            </a>
            <div class="dropdown-divider"></div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item dropdown-logout">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Sign Out
              </button>
            </form>
          </div>
        </li>
      </ul>

      <div class="nav-mobile-right">
        <a href="{{ route('complaints.create') }}" class="btn-nav-cta nav-cta-sm hidden sm:flex">File</a>
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
      <div class="mobile-user-info">
        <div class="mobile-user-avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
        <div class="mobile-user-copy">
          <div class="mobile-user-name">{{ auth()->user()->full_name }}</div>
          <div class="mobile-user-email">{{ auth()->user()->email }}</div>
        </div>
      </div>
      <div class="mobile-divider"></div>

      @if(auth()->user()->role === 'citizen')
        <a href="{{ route('dashboard') }}" wire:navigate class="mobile-nav-link">Dashboard</a>
      @endif
      <a href="{{ url('/transparency') }}" wire:navigate class="mobile-nav-link">Transparency</a>
      <a href="{{ url('/rewards') }}" wire:navigate class="mobile-nav-link">Rewards</a>
      <div class="mobile-divider"></div>

      <a href="{{ route('profile') }}" class="mobile-nav-link">My Account</a>
      <a href="{{ route('complaints.index') }}" wire:navigate class="mobile-nav-link">My Complaints</a>
      @if(auth()->user()->role === 'admin')
        <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">Admin Dashboard</a>
      @elseif(auth()->user()->role === 'staff')
        <a href="{{ route('staff.dashboard') }}" class="mobile-nav-link">Staff Dashboard</a>
      @endif
      <div class="mobile-divider"></div>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="mobile-nav-link mobile-logout">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Sign Out
        </button>
      </form>
    </div>
  </div>
</nav>
