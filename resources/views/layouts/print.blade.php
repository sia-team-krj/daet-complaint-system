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
    <div class="print-toolbar" aria-hidden="false">
        <div class="print-toolbar__inner">
            <p class="print-toolbar__hint">{{ __('analytics.print.save_hint') }}</p>
            <div class="print-toolbar__actions">
                <button type="button" class="print-toolbar__btn print-toolbar__btn--primary" onclick="window.print()">
                    {{ __('analytics.actions.print') }}
                </button>
                <button type="button" class="print-toolbar__btn" onclick="window.close()">
                    {{ __('analytics.print.close') }}
                </button>
            </div>
        </div>
    </div>
    <main class="print-document">
        @yield('content')
    </main>
    <script>
        window.addEventListener('load', function () {
            if (!window.matchMedia('print').matches) {
                window.setTimeout(function () { window.print(); }, 250);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
