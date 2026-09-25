<!DOCTYPE html>
<html lang="en" class="w-full max-w-full overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="{{ asset('images/lgulogo.png') }}">
    <title>@yield('title', 'Daet Listens')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="/js/shell.js?v={{ filemtime(public_path('js/shell.js')) }}" defer></script>
    @livewireStyles
    @stack('styles')
</head>
<body class="w-full max-w-full bg-[#0B1F3A] overflow-x-hidden">
    @include('layouts.guest-navbar')
    <main class="w-full max-w-full overflow-x-hidden">
        @yield('content')
    </main>
    @livewireScripts
    @stack('scripts')
</body>
</html>