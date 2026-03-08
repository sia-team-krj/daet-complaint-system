<nav id="navbar" class="fixed left-0 top-0 w-full z-50 transition-all duration-300 bg-[#0B1F3A]/96 backdrop-blur-md border-b border-[#C9A84C]/15">
  <div class="navbar-inner">
    <div class="navbar-flex">

      {{-- Logo --}}
      <a href="{{ url('/') }}" class="navbar-logo group">
        <div class="logo-icon group-hover:scale-105 transition-transform duration-200">
          <img src="/images/lgulogo.png" alt="Daet LGU" class="w-7 h-7 object-contain">
        </div>
        <div class="logo-text">
          <span class="logo-name">Daet Listens</span>
          <span class="logo-sub">LGU Daet · Camarines Norte</span>
        </div>
      </a>

      {{-- Desktop Center Links --}}
      <ul class="nav-links">
        <li><a href="{{ url('/') }}" class="nav-link">Home</a></li>
        <li><a href="{{ url('/') }}#process" class="nav-link">Process</a></li>
        <li><a href="{{ url('/') }}#transparency" class="nav-link">Transparency</a></li>
        <li><a href="{{ url('/') }}#features" class="nav-link">Features</a></li>
        <li><a href="{{ url('/') }}#rewards" class="nav-link">Rewards</a></li>
      </ul>

      {{-- Desktop Right --}}
      <ul class="nav-actions">
        @auth
          {{-- File a Complaint CTA --}}
          <li>
            <a href="{{ route('complaints.create') }}" class="btn-nav-cta">File a Complaint</a>
          </li>

          @if(auth()->user()->is_admin)
            <li><a href="{{ route('admin.dashboard') }}" class="nav-link">Admin</a></li>
          @endif

          {{-- User dropdown --}}
          <li class="nav-dropdown-wrap">
            <button class="nav-user-btn" id="user-menu-btn" aria-expanded="false">
              <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
              </div>
              <span class="user-name">{{ auth()->user()->first_name }}</span>
              <svg class="dropdown-chevron" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <div class="nav-dropdown" id="user-dropdown">
              <div class="dropdown-header">
                <span class="dropdown-name">{{ auth()->user()->full_name }}</span>
                <span class="dropdown-email">{{ auth()->user()->email }}</span>
              </div>
              <div class="dropdown-divider"></div>
              <a href="{{ route('profile') }}" class="dropdown-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                My Account
              </a>
              <a href="#" class="dropdown-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                My Complaints
              </a>
              <div class="dropdown-divider"></div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-logout">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  Sign Out
                </button>
              </form>
            </div>
          </li>
        @else
          <li><a href="{{ route('login') }}" class="nav-link">Login</a></li>
          <li><a href="{{ route('register') }}" class="btn-nav-cta">Get Started</a></li>
        @endauth
      </ul>

      {{-- Mobile Right --}}
      <div class="nav-mobile-right">
        @auth
          <a href="{{ route('complaints.create') }}" class="btn-nav-cta nav-cta-sm">File</a>
        @else
          <a href="{{ route('register') }}" class="btn-nav-cta nav-cta-sm">Get Started</a>
        @endauth
        <button id="mobile-menu-btn" aria-label="Toggle menu" class="hamburger-btn">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line" style="width:14px"></span>
        </button>
      </div>

    </div>
  </div>

  {{-- Mobile Dropdown --}}
  <div id="mobile-menu" class="mobile-menu hidden">
    <div class="mobile-menu-inner">
      <a href="{{ url('/') }}" class="mobile-nav-link">Home</a>
      <a href="{{ url('/') }}#process" class="mobile-nav-link">Process</a>
      <a href="{{ url('/') }}#transparency" class="mobile-nav-link">Transparency</a>
      <a href="{{ url('/') }}#features" class="mobile-nav-link">Features</a>
      <a href="{{ url('/') }}#rewards" class="mobile-nav-link">Rewards</a>
      <div class="mobile-divider"></div>
      @auth
        <div class="mobile-user-info">
          <div class="mobile-user-avatar">
            {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
          </div>
          <div>
            <div class="mobile-user-name">{{ auth()->user()->full_name }}</div>
            <div class="mobile-user-email">{{ auth()->user()->email }}</div>
          </div>
        </div>
        <div class="mobile-divider"></div>
        <a href="{{ route('profile') }}" class="mobile-nav-link">My Account</a>
        <a href="#" class="mobile-nav-link">My Complaints</a>
        @if(auth()->user()->is_admin)
          <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">Admin Dashboard</a>
        @endif
        <div class="mobile-divider"></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="mobile-nav-link mobile-logout">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
          </button>
        </form>
      @else
        <a href="{{ route('login') }}" class="mobile-nav-link">Login</a>
        <a href="{{ route('register') }}" class="mobile-nav-link" style="color:#C9A84C;">Get Started</a>
      @endauth
    </div>
  </div>
</nav>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

  /* ── Navbar shell ── */
  #navbar .navbar-inner {
    width: 100%; max-width: 1536px;
    margin: 0 auto;
    padding: 0 clamp(20px, 3vw, 48px);
  }
  #navbar .navbar-flex {
    display: flex; align-items: center;
    justify-content: space-between;
    height: 64px; gap: 24px;
  }

  /* ── Logo ── */
  .navbar-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; flex-shrink: 0; }
  .logo-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: linear-gradient(135deg, #C9A84C, #E2C06A);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 3px 10px rgba(201,168,76,0.3); flex-shrink: 0;
  }
  .logo-text { display: flex; flex-direction: column; line-height: 1.2; }
  .logo-name { font-family: 'Cormorant Garamond', serif; color: #fff; font-size: 15px; font-weight: 700; letter-spacing: 0.01em; white-space: nowrap; }
  .logo-sub { color: #C9A84C; font-family: 'DM Sans', sans-serif; font-size: 9px; letter-spacing: 0.14em; text-transform: uppercase; font-weight: 500; white-space: nowrap; }

  /* ── Center nav links ── */
  .nav-links { display: flex; align-items: center; gap: clamp(20px, 2.5vw, 36px); list-style: none; margin: 0; padding: 0; flex: 1; justify-content: center; }
  @media (max-width: 768px) { .nav-links { display: none; } }

  /* ── Right actions ── */
  .nav-actions { display: flex; align-items: center; gap: clamp(14px, 1.5vw, 20px); list-style: none; margin: 0; padding: 0; flex-shrink: 0; }
  @media (max-width: 768px) { .nav-actions { display: none; } }

  /* ── Shared nav-link ── */
  .nav-link {
    font-family: 'DM Sans', sans-serif; font-size: clamp(10px, 0.75vw, 12px);
    font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase;
    color: rgba(255,255,255,0.55); text-decoration: none;
    position: relative; transition: color 0.2s; white-space: nowrap;
  }
  .nav-link::after { content: ''; position: absolute; bottom: -3px; left: 0; width: 0; height: 1px; background: #C9A84C; transition: width 0.28s ease; }
  .nav-link:hover { color: rgba(255,255,255,0.92); }
  .nav-link:hover::after { width: 100%; }

  /* ── CTA button ── */
  .btn-nav-cta {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #C9A84C, #E2C06A);
    color: #0B1F3A; font-family: 'DM Sans', sans-serif;
    font-size: clamp(10px, 0.75vw, 12px); font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase;
    text-decoration: none; padding: 8px clamp(14px, 1.2vw, 22px);
    border-radius: 6px; white-space: nowrap;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 3px 12px rgba(201,168,76,0.25);
  }
  .btn-nav-cta:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,168,76,0.4); }
  .nav-cta-sm { font-size: 11px; padding: 7px 14px; }

  /* ── User dropdown ── */
  .nav-dropdown-wrap { position: relative; }

  .nav-user-btn {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(201,168,76,0.2);
    border-radius: 8px; padding: 5px 10px 5px 6px;
    cursor: pointer; transition: background 0.2s, border-color 0.2s;
    color: rgba(255,255,255,0.75);
  }
  .nav-user-btn:hover { background: rgba(201,168,76,0.08); border-color: rgba(201,168,76,0.4); }

  .user-avatar {
    width: 26px; height: 26px; border-radius: 50%;
    background: linear-gradient(135deg, #C9A84C, #E2C06A);
    color: #0B1F3A; font-family: 'DM Sans', sans-serif;
    font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .user-name { font-family: 'DM Sans', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 0.04em; white-space: nowrap; }
  .dropdown-chevron { color: rgba(201,168,76,0.6); transition: transform 0.2s; flex-shrink: 0; }
  .nav-user-btn[aria-expanded="true"] .dropdown-chevron { transform: rotate(180deg); }

  .nav-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0;
    width: 220px;
    background: #0e2040; border: 1px solid rgba(201,168,76,0.18);
    border-radius: 10px; padding: 6px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.5);
    opacity: 0; visibility: hidden; transform: translateY(-6px);
    transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
    z-index: 200;
  }
  .nav-dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }

  .dropdown-header { padding: 8px 10px 10px; }
  .dropdown-name { display: block; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; color: #fff; }
  .dropdown-email { display: block; font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 2px; word-break: break-all; }

  .dropdown-divider { height: 1px; background: rgba(201,168,76,0.12); margin: 4px 0; }

  .dropdown-item {
    display: flex; align-items: center; gap: 9px;
    width: 100%; padding: 9px 10px; border-radius: 6px;
    font-family: 'DM Sans', sans-serif; font-size: 11.5px; font-weight: 500;
    color: rgba(255,255,255,0.6); text-decoration: none;
    background: none; border: none; cursor: pointer; text-align: left;
    transition: background 0.15s, color 0.15s; letter-spacing: 0.02em;
  }
  .dropdown-item:hover { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.9); }
  .dropdown-item svg { flex-shrink: 0; opacity: 0.6; }

  .dropdown-logout { color: rgba(239,68,68,0.7); }
  .dropdown-logout:hover { background: rgba(239,68,68,0.08); color: #f87171; }
  .dropdown-logout svg { opacity: 0.7; }

  /* ── Mobile right cluster ── */
  .nav-mobile-right { display: none; align-items: center; gap: 10px; }
  @media (max-width: 768px) { .nav-mobile-right { display: flex; } }

  /* ── Hamburger ── */
  .hamburger-btn {
    width: 36px; height: 36px; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 5px;
    border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);
    background: transparent; cursor: pointer; transition: border-color 0.2s;
  }
  .hamburger-btn:hover { border-color: rgba(201,168,76,0.4); }
  .hamburger-line { display: block; height: 1.5px; width: 20px; background: rgba(255,255,255,0.7); border-radius: 2px; transition: background 0.2s; }
  .hamburger-btn:hover .hamburger-line { background: #C9A84C; }

  /* ── Mobile menu ── */
  .mobile-menu { background: #0B1F3A; border-top: 1px solid rgba(201,168,76,0.1); }
  .mobile-menu-inner {
    padding: 12px clamp(16px, 4vw, 32px) 16px;
    display: flex; flex-direction: column; gap: 2px;
    max-width: 1536px; margin: 0 auto;
  }
  .mobile-divider { height: 1px; background: rgba(201,168,76,0.12); margin: 8px 0; }

  .mobile-user-info { display: flex; align-items: center; gap: 12px; padding: 10px 10px; }
  .mobile-user-avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #C9A84C, #E2C06A);
    color: #0B1F3A; font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
  }
  .mobile-user-name { font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; color: #fff; }
  .mobile-user-email { font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 1px; }

  .mobile-nav-link {
    font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 500;
    letter-spacing: 0.07em; text-transform: uppercase;
    color: rgba(255,255,255,0.6); text-decoration: none;
    padding: 10px 10px; border-radius: 6px; display: block;
    transition: background 0.18s, color 0.18s;
    background: none; border: none; cursor: pointer; width: 100%; text-align: left;
  }
  .mobile-nav-link:hover { background: rgba(201,168,76,0.08); color: #E2C06A; }

  .mobile-logout { color: rgba(239,68,68,0.65); display: flex; align-items: center; gap: 8px; }
  .mobile-logout:hover { background: rgba(239,68,68,0.08); color: #f87171; }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Mobile menu toggle
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileBtn && mobileMenu) {
      mobileBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
      mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.add('hidden')));
    }

    // User dropdown toggle
    const userBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');
    if (userBtn && userDropdown) {
      userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = userDropdown.classList.contains('open');
        userDropdown.classList.toggle('open');
        userBtn.setAttribute('aria-expanded', !isOpen);
      });
      // Close on outside click
      document.addEventListener('click', () => {
        userDropdown.classList.remove('open');
        userBtn.setAttribute('aria-expanded', 'false');
      });
    }
  });
</script>
