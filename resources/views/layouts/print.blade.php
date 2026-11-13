<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Daet Listens')</title>
    <link rel="icon" href="{{ asset('images/lgulogo.webp') }}">
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="print-layout @yield('body-class')">
    <main class="print-document">
        @yield('content')
    </main>
</body>
</html>
