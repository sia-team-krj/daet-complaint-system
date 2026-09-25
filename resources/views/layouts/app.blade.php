{{--
    ╔══════════════════════════════════════════════════════════════╗
    ║  layouts/app.blade.php — UNIFIED LAYOUT                     ║
    ║  Handles BOTH guest and authenticated states.                ║
    ║                                                              ║
    ║  HOW IT WORKS:                                               ║
    ║  - If the user is logged in  → shows navbar.blade.php        ║
    ║    Body bg: #F5F0E8 (cream)                                  ║
    ║  - If the user is a guest    → shows guest-navbar.blade.php  ║
    ║    Body bg: depends on page (passed via $bodyBg or default)  ║
    ║                                                              ║
    ║  USAGE IN CONTROLLERS:                                       ║
    ║  return view('pages.home', [                                 ║
    ║      'totalComplaints'    => ...,                            ║
    ║      'resolvedComplaints' => ...,                            ║
    ║  ]);                                                         ║
    ║                                                              ║
    ║  No need to pass $mainLayout anymore — this layout IS the    ║
    ║  one layout. The @auth/@guest directives handle the rest.    ║
    ╚══════════════════════════════════════════════════════════════╝
--}}
<!DOCTYPE html>
<html lang="en" class="w-full max-w-full overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/lgulogo.png') }}">

    {{-- Page title: views set this via @section('title', '...') --}}
    <title>@yield('title', 'Daet Listens — LGU Daet')</title>

    {{-- Fonts: loaded here once for ALL pages --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="/js/shell.js?v={{ filemtime(public_path('js/shell.js')) }}" defer></script>
    @livewireStyles

    {{-- Global accessibility fixes --}}
    <style>
      /* Ensure text is selectable everywhere */
      body { user-select: auto; }
      .user-select-none { user-select: none; }

      /* Focus visible for keyboard navigation */
      :focus-visible {
        outline: 2px solid #C9A84C;
        outline-offset: 2px;
        border-radius: 2px;
      }

      /* Reduced motion support */
      @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
          animation-duration: 0.01ms !important;
          animation-iteration-count: 1 !important;
          transition-duration: 0.01ms !important;
        }
      }
    </style>

    {{-- Allow individual pages to inject <head> content (meta tags, extra styles, etc.) --}}
    @stack('head')
    @stack('styles')
</head>

{{--
    Body background logic:
    - Authenticated users land on cream (#F5F0E8) — this is the "inside the app" feel.
    - Guest users on most pages see navy (#0B1F3A) — the dark institutional landing.
    - Some pages (transparency, rewards when public) use navy too even when auth'd,
      because those pages have their own dark hero sections that override body bg.
    - Pass $bodyClass from the controller to override, e.g.:
        return view('pages.transparency', ['bodyClass' => 'bg-navy']);
--}}
<body class="w-full max-w-full overflow-x-hidden {{ auth()->check() ? 'bg-[#F5F0E8]' : 'bg-[#0B1F3A]' }} {{ $bodyClass ?? '' }}">

    {{--
        NAVBAR SWITCH:
        - Logged-in users → full app navbar with dropdown, "File a Complaint" CTA
        - Guests → simple public navbar with Login + Get Started
    --}}
    @auth
        @include('layouts.navbar')
    @else
        @include('layouts.guest-navbar')
    @endauth

    <main class="w-full max-w-full overflow-x-hidden">
        @yield('content')
    </main>

    @livewireScripts

    {{-- Allow individual pages to inject scripts at the bottom --}}
    @stack('scripts')
</body>
</html>
