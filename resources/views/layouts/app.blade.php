{{--
    ╔══════════════════════════════════════════════════════════════╗
    ║  layouts/app.blade.php - UNIFIED LAYOUT                     ║
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
    ║  No need to pass $mainLayout anymore - this layout IS the    ║
    ║  one layout. The @auth/@guest directives handle the rest.    ║
    ╚══════════════════════════════════════════════════════════════╝
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="w-full max-w-full overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/lgulogo.webp') }}">

    {{-- Page title: views set this via @section('title', '...') --}}
    <title>@yield('title', 'Daet Listens - LGU Daet')</title>

    {{-- Fonts: loaded here once for ALL pages --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Inter:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Must come before @vite: the module is deferred, this is not. --}}
    @include('partials.leaflet-bootstrap')

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
    - Authenticated users land on cream (#F5F0E8) - this is the "inside the app" feel.
    - Guest users on most pages see navy (#142D4F) - the dark institutional landing.
    - Public-facing pages such as transparency use navy too even when auth'd,
      because those pages have their own dark hero sections that override body bg.
    - Pass $bodyClass from the controller to override, e.g.:
        return view('pages.transparency', ['bodyClass' => 'bg-navy']);
--}}
<body
    @auth
        data-role="{{ auth()->user()->role }}"
        class="w-full max-w-full overflow-x-clip bg-white is-auth role-{{ auth()->user()->role }} text-size-{{ auth()->user()->text_size ?? 'normal' }} {{ $bodyClass ?? '' }} @yield('body-class')"
    @else
        data-role="guest"
        class="w-full max-w-full overflow-x-clip bg-white role-guest {{ $bodyClass ?? '' }} @yield('body-class')"
    @endauth
>

    {{--
        NAVBAR SWITCH:
        - Logged-in users → full app navbar with dropdown, "File a Complaint" CTA
        - Guests → simple public navbar with Login + Get Started
    --}}
    @auth
        @include('layouts.navbar')
        @include('layouts.partials.mobile-drawer')
    @else
        @include('layouts.guest-navbar')
    @endauth

    <main class="w-full max-w-full overflow-x-clip">
        @yield('content')
    </main>

    {{--
        The site footer, which is where the Terms and Conditions and the Privacy
        Policy live.

        A page that renders the footer itself - because the footer has to sit
        inside its own full-height wrapper - declares `@section('site-footer')`
        so this does not add a second one. The section must contain something:
        `@hasSection` compiles to a non-empty content check, so an empty section
        is treated as absent and the footer would render twice.
    --}}
    @hasSection('site-footer')
        @yield('site-footer')
    @else
        @include('layouts.partials.site-footer')
    @endif

    @livewireScripts

    {{-- Allow individual pages to inject scripts at the bottom --}}
    @stack('scripts')
</body>
</html>
