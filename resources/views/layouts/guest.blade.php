<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="w-full max-w-full overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="{{ asset('images/lgulogo.webp') }}">
    <title>@yield('title', 'Daet Listens')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Inter:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Must come before @vite: the module is deferred, this is not. --}}
    @include('partials.leaflet-bootstrap')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="/js/shell.js?v={{ filemtime(public_path('js/shell.js')) }}" defer></script>
    <style>
        /* The guest shell is always a dark navy surface. Painting html as well
           as body means a decorative element that reaches past the viewport can
           never expose the browser's white canvas behind it. */
        html,
        body {
            background-color: #142D4F;
        }
    </style>
    @livewireStyles
    @stack('styles')
</head>
<body class="w-full max-w-full bg-navy overflow-x-clip">
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