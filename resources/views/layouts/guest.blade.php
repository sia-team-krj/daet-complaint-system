<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="w-full max-w-full overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="{{ asset('images/lgulogo.webp') }}">
    <title>@yield('title', 'Daet Listens')</title>
    {{-- Fonts are self-hosted (public/fonts/inter) — no third-party request,
         so a visitor's IP address never leaves the Municipality's server. --}}
    {{-- Must come before @vite: the module is deferred, this is not. --}}
    @include('partials.leaflet-bootstrap')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="/js/shell.js?v={{ filemtime(public_path('js/shell.js')) }}" defer></script>
    <style>
        /* The guest shell is always a dark navy surface. Painting html as well
           as body means a decorative element that reaches past the viewport can
           never expose the browser's white canvas behind it. */
        html {
            background-color: #142D4F;
        }
        @if(request()->routeIs('transparency.complaints'))
        body {
            background-color: var(--surface);
        }
        @else
        body {
            background-color: #142D4F;
        }
        @endif
    </style>
    @livewireStyles
    @stack('head')
    @stack('styles')
</head>
<body class="w-full max-w-full {{ request()->routeIs('transparency.complaints') ? '' : 'bg-navy' }} overflow-x-clip">
    @include('layouts.guest-navbar')
    <main class="w-full max-w-full overflow-x-clip">
        @yield('content')
    </main>

    {{-- See layouts/app.blade.php: the footer carries the legal documents, and a
         page that renders its own declares `@section('site-footer')`. --}}
    @hasSection('site-footer')
        @yield('site-footer')
    @else
        @include('layouts.partials.site-footer')
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>