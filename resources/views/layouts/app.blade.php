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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/lgulogo.png') }}">

    {{-- Page title: views set this via @section('title', '...') --}}
    <title>@yield('title', 'Daet Listens — LGU Daet')</title>

    {{-- Fonts: loaded here once for ALL pages --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Allow individual pages to inject <head> content (meta tags, extra styles, etc.) --}}
    @stack('head')
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
<body class="overflow-x-hidden {{ auth()->check() ? 'bg-[#F5F0E8]' : 'bg-[#0B1F3A]' }} {{ $bodyClass ?? '' }}">

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

    <main>
        @yield('content')
    </main>

    @livewireScripts

    {{-- Allow individual pages to inject scripts at the bottom --}}
    @stack('scripts')
</body>
</html>
