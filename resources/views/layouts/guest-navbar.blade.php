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
      <ul class="nav-links {{ Request::is('transparency') ? 'active-ui' : '' }}">
        <li><a href="{{ url('/') }}" wire:navigate class="nav-link">Home</a></li>
        <li><a href="/#process" class="nav-link">Process</a></li>
        <li><a href="/#features" class="nav-link">Features</a></li>
        <li><a href="{{ url('/transparency') }}" wire:navigate class="nav-link">Transparency</a></li>
        <li><a href="{{ url('/rewards') }}" wire:navigate class="nav-link">Rewards</a></li>
      </ul>

      {{-- Desktop Right --}}
      <ul class="nav-actions">
        <li><a href="{{ route('login') }}" wire:navigate class="nav-link">Login</a></li>
        <li><a href="{{ route('register') }}" wire:navigate class="btn-nav-cta">Get Started</a></li>
      </ul>

      {{-- Mobile Right --}}
      <div class="nav-mobile-right">
        @auth
          <a href="{{ route('complaints.create') }}" wire:navigate class="btn-nav-cta nav-cta-sm">File</a>
        @else
          <a href="{{ route('register') }}" wire:navigate class="btn-nav-cta nav-cta-sm">Get Started</a>
        @endauth
        <button id="mobile-menu-btn" aria-label="Toggle menu" class="hamburger-btn" type="button">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line" style="width:14px"></span>
        </button>
      </div>

    </div>
  </div>

  {{-- Mobile Dropdown --}}
  <div id="mobile-menu" class="mobile-menu" style="display:none;">
    <div class="mobile-menu-inner">
      <a href="{{ url('/') }}" wire:navigate class="mobile-nav-link">Home</a>
      <a href="{{ url('/') }}#process" class="mobile-nav-link">Process</a>
      <a href="{{ url('/transparency') }}" wire:navigate class="mobile-nav-link">Transparency</a>
      <a href="{{ url('/') }}#features" class="mobile-nav-link">Features</a>
      <a href="{{ url('/rewards') }}" wire:navigate class="mobile-nav-link">Rewards</a>
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
        @if(auth()->user()->role === 'admin')
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
        <a href="{{ route('login') }}" wire:navigate class="mobile-nav-link">Login</a>
        <a href="{{ route('register') }}" wire:navigate class="mobile-nav-link" style="color:#C9A84C;">Get Started</a>
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

  /* ── Mobile right cluster ── */
  .nav-mobile-right {
    display: none;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }
  @media (max-width: 768px) {
    .nav-mobile-right { display: flex; }
  }

  /* ── Hamburger ── */
  .hamburger-btn {
    width: 36px;
    height: 36px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    background: transparent;
    cursor: pointer;
    transition: border-color 0.2s;
    padding: 0;
    flex-shrink: 0;
  }
  .hamburger-btn:hover { border-color: rgba(201,168,76,0.4); }
  .hamburger-line {
    display: block;
    height: 1.5px;
    width: 20px;
    background: rgba(255,255,255,0.7);
    border-radius: 2px;
    transition: background 0.2s;
  }
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
  function initGuestNavbar() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');

    if (!btn || !menu) return;

    // Clone to strip any previously attached listeners (safe for Livewire re-renders)
    const freshBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(freshBtn, btn);

    freshBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const isHidden = menu.style.display === 'none' || menu.style.display === '';
      menu.style.display = isHidden ? 'block' : 'none';
    });

    // Close menu on outside click
    document.addEventListener('click', function handleOutside(e) {
      if (!menu.contains(e.target) && e.target !== freshBtn && !freshBtn.contains(e.target)) {
        menu.style.display = 'none';
      }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href*="#"]').forEach(link => {
      link.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href.startsWith('/#') || href.startsWith('#')) {
          const targetId = href.split('#')[1];
          const target = document.getElementById(targetId);
          if (target) {
            e.preventDefault();
            menu.style.display = 'none';
            target.scrollIntoView({ behavior: 'smooth' });
            history.pushState(null, null, href);
          }
        }
      });
    });
  }

  // Run on initial page load
  document.addEventListener('DOMContentLoaded', initGuestNavbar);

  // Re-run after every Livewire navigation (wire:navigate)
  document.addEventListener('livewire:navigated', initGuestNavbar);
</script>