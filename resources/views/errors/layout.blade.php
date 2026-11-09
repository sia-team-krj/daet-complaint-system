{{--
    Standalone error shell — the page that must still work when something is
    wrong. It deliberately does not include the app navbar, the mobile drawer,
    Livewire, or the shared site footer: all of those depend on the database,
    the session, or the assets being healthy. An error page that fails while
    rendering the error is the one page nobody can use.

    Anatomy follows docs/design-system.md §5.27:
    brand header → one centered card (64px icon circle, error code, h1,
    message, ONE gold primary action + one secondary) → minimal footer.
    Strings: lang/{en,fil}/errors.php. Styles: resources/css/pages/error.css.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('images/lgulogo.webp') }}">

    @php
        $errorTitle = trim($__env->yieldContent('title')) ?: __('errors.500.title');
        $errorCode = trim($__env->yieldContent('code')) ?: '500';
        $errorMessage = trim($__env->yieldContent('message')) ?: __('errors.500.message');
        $actionUrl = trim($__env->yieldContent('action_url')) ?: url('/');
        $actionLabel = trim($__env->yieldContent('action_label')) ?: __('errors.action_home');
        $secondary = trim($__env->yieldContent('secondary')) ?: 'back';

        $icon = match ($errorCode) {
            '401', '403' => 'lock',
            '404' => 'search',
            '419', '429' => 'clock',
            '503' => 'server',
            default => 'alert',
        };

        /*
         * The asset manifest can be the very thing that is broken (a failed
         * deploy is a classic 500). Falling back to an unstyled page is better
         * than recursively throwing while rendering the error.
         */
        $errorStyles = null;

        try {
            $errorStyles = \Illuminate\Support\Facades\Vite::asset('resources/css/app.css');
        } catch (\Throwable $e) {
            // No manifest, no stylesheet. Keep the page readable anyway.
        }
    @endphp

    <title>{{ $errorTitle }} — {{ __('errors.brand') }}</title>

    @if ($errorStyles)
        <link rel="stylesheet" href="{{ $errorStyles }}">
    @endif

    @include('partials.public-ui')
    @include('pages.transparency.partials.component-styles')
</head>
<body class="error-page" data-error-page>
    <header class="error-header">
        <div class="tr-container error-header__inner">
            <a href="{{ url('/') }}" class="error-brand" aria-label="{{ __('errors.brand_home') }}">
                <img src="{{ asset('images/lgulogo.webp') }}" alt="" class="error-brand__logo" width="32" height="32">
                <span class="error-brand__text">
                    <span class="error-brand__name">{{ __('errors.brand') }}</span>
                    <span class="error-brand__sub">{{ __('errors.tagline') }}</span>
                </span>
            </a>
            <span class="error-header__tag">{{ __('errors.header_tag') }}</span>
        </div>
    </header>

    <main class="error-main">
        <div class="tr-container">
            <section class="tr-card error-card" aria-labelledby="error-title">
                <span class="error-icon" aria-hidden="true">
                    @include('errors.partials.icon', ['icon' => $icon])
                </span>

                <p class="error-kicker">{{ __('errors.kicker', ['code' => $errorCode]) }}</p>
                <h1 class="error-title" id="error-title">{{ $errorTitle }}</h1>
                <p class="error-message">{{ $errorMessage }}</p>

                <div class="error-actions">
                    <a href="{{ $actionUrl }}" class="error-primary">{{ $actionLabel }}</a>

                    @if ($secondary === 'retry')
                        <a href="{{ url()->current() }}" class="tr-button">{{ __('errors.action_retry') }}</a>
                    @else
                        {{-- Revealed only when the browser actually has a page
                             to go back to; a dead back button is worse than no
                             back button. --}}
                        <button type="button" class="tr-button" data-error-back hidden>{{ __('errors.action_back') }}</button>
                    @endif
                </div>
            </section>
        </div>
    </main>

    <footer class="error-footer">
        <div class="tr-container error-footer__inner">
            <p class="error-footer__text">&copy; {{ date('Y') }} {{ __('footer.municipality') }}</p>
            <p class="error-footer__text">{{ __('footer.portal') }}</p>
        </div>
    </footer>

    <script>
        (function () {
            var back = document.querySelector('[data-error-back]');

            if (!back) {
                return;
            }

            if (window.history.length > 1) {
                back.hidden = false;
            }

            back.addEventListener('click', function () {
                window.history.back();
            });
        })();
    </script>
</body>
</html>
