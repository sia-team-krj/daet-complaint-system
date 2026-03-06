<nav id="navbar" class="fixed left-0 top-0 w-full z-50 transition-all duration-300 bg-[#0B1F3A]/96 backdrop-blur-md border-b border-[#C9A84C]/15">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      {{-- Logo --}}
      <a href="{{ url('/') }}" class="flex items-center gap-3 group flex-shrink-0">
        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#C9A84C] to-[#E2C06A] flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform duration-200">
          <img src="/images/lgulogo.png" alt="Daet LGU" class="w-7 h-7 object-contain">
        </div>
        <div class="flex flex-col leading-tight">
          <span class="font-serif text-white text-sm font-semibold tracking-wide">Daet Listens</span>
          <span class="text-[#C9A84C] text-[9px] tracking-[0.14em] uppercase font-medium hidden sm:block">LGU Daet · Camarines Norte</span>
        </div>
      </a>

      {{-- Desktop Center Links --}}
      <ul class="hidden md:flex items-center gap-8">
        <li><a href="{{ url('/') }}" class="nav-link">Home</a></li>
        <li><a href="#process" class="nav-link">Process</a></li>
        <li><a href="#features" class="nav-link">Features</a></li>
        <li><a href="#rewards" class="nav-link">Rewards</a></li>
      </ul>

      {{-- Desktop Right --}}
      <ul class="hidden md:flex items-center gap-5">
        @auth
          <li><a href="{{ route('profile') }}" class="nav-link">Account</a></li>
          @if(auth()->user()->is_admin)
            <li><a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a></li>
          @endif
          <li><a href="{{ route('complaints.create') }}" class="btn-nav-cta">File a Complaint</a></li>
        @else
        {{-- Make A page for login and register--}}
        {{-- {{route('login')}} --}}
        {{-- {{route('register')}} --}}
          <li><a href="" class="nav-link">Login</a></li>
          <li><a href="" class="btn-nav-cta">Get Started</a></li>
        @endauth
      </ul>

      {{-- Mobile --}}
      <div class="flex items-center gap-3 md:hidden">
        @auth
          <a href="{{ route('complaints.create') }}" class="btn-nav-cta text-[11px] px-3 py-2">File</a>
        @else
          <a href="" class="btn-nav-cta text-[11px] px-3 py-2">Get Started</a>
        @endauth
        <button id="mobile-menu-btn" aria-label="Toggle menu"
          class="w-9 h-9 flex flex-col items-center justify-center gap-[5px] rounded-lg border border-white/10 hover:border-[#C9A84C]/40 transition-colors">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line" style="width:14px"></span>
        </button>
      </div>
    </div>
  </div>

  {{-- Mobile Dropdown --}}
  <div id="mobile-menu" class="hidden md:hidden bg-[#0B1F3A] border-t border-[#C9A84C]/10">
    <div class="px-4 py-4 flex flex-col gap-1">
      <a href="{{ url('/') }}" class="mobile-nav-link">Home</a>
      <a href="#process" class="mobile-nav-link">Process</a>
      <a href="#features" class="mobile-nav-link">Features</a>
      <a href="#rewards" class="mobile-nav-link">Rewards</a>
      <div class="h-px bg-[#C9A84C]/15 my-2"></div>
      @auth
        <a href="{{ route('profile') }}" class="mobile-nav-link">Account</a>
        @if(auth()->user()->is_admin)
          <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">Dashboard</a>
        @endif
      @else
      {{-- {{route('login')}} --}}
        <a href="" class="mobile-nav-link">Login</a>
      @endauth
    </div>
  </div>
</nav>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

  .nav-link {
    font-family: 'DM Sans', sans-serif;
    font-size: 11.5px; font-weight: 500;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: rgba(255,255,255,0.55); text-decoration: none;
    position: relative; transition: color 0.2s;
  }
  .nav-link::after {
    content: ''; position: absolute;
    bottom: -3px; left: 0;
    width: 0; height: 1px;
    background: #C9A84C;
    transition: width 0.28s ease;
  }
  .nav-link:hover { color: rgba(255,255,255,0.92); }
  .nav-link:hover::after { width: 100%; }

  .btn-nav-cta {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #C9A84C, #E2C06A);
    color: #0B1F3A;
    font-family: 'DM Sans', sans-serif;
    font-size: 11.5px; font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase;
    text-decoration: none; padding: 8px 20px;
    border-radius: 6px;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 3px 12px rgba(201,168,76,0.25);
  }
  .btn-nav-cta:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,168,76,0.4); }

  .hamburger-line {
    display: block; height: 1.5px; width: 20px;
    background: rgba(255,255,255,0.7); border-radius: 2px;
    transition: background 0.2s;
  }
  #mobile-menu-btn:hover .hamburger-line { background: #C9A84C; }

  .mobile-nav-link {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 500;
    letter-spacing: 0.07em; text-transform: uppercase;
    color: rgba(255,255,255,0.6); text-decoration: none;
    padding: 10px 8px; border-radius: 6px;
    display: block; transition: background 0.18s, color 0.18s;
  }
  .mobile-nav-link:hover { background: rgba(201,168,76,0.08); color: #E2C06A; }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (btn && menu) {
      btn.addEventListener('click', () => menu.classList.toggle('hidden'));
      menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => menu.classList.add('hidden')));
    }
  });
</script>
