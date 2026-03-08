<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('images/lgulogo.png') }}">
    <title>@yield('title', 'Daet Listens')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0B1F3A] overflow-x-hidden">
    @include('layouts.guest-navbar')
    <main>
        @yield('content')
    </main>
</body>
</html>
