<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/*
 * Where to send someone whose form submission failed CSRF.
 *
 * `back()` is not good enough on its own. By the time this runs, the session
 * that would have held the previous page has already been replaced, so
 * url()->previous() falls through to "/" and the person who was registering
 * gets dumped on the home page with a message about a form they can no longer
 * see. The Referer header is checked first — that survives the lost session —
 * and is only trusted when it points back at this host, so it cannot be turned
 * into an open redirect.
 *
 * A closure rather than a named function on purpose: this file is required
 * once per application instance, and the test suite builds many of them, so a
 * top-level function here would be redeclared on the second include.
 */
$csrfFailureTarget = function (Request $request): string {
    $referer = $request->headers->get('referer');

    if ($referer !== null && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
        return $referer;
    }

    // No usable referer, so name the page explicitly. These are the forms a
    // resident is most likely to be submitting when their session lapses.
    $pages = [
        'login' => 'login',
        'register' => 'register',
        'forgot-password' => 'password.request',
        'reset-password/*' => 'password.reset',
        'email/verify' => 'email.verify',
        'email/resend' => 'email.verify',
    ];

    foreach ($pages as $pattern => $routeName) {
        if ($request->is($pattern) && Route::has($routeName)) {
            return route($routeName);
        }
    }

    return url()->previous();
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tailscale serve terminates TLS and forwards to the app over plain
        // HTTP, so the app never sees an https request and never sees the
        // X-Forwarded-Proto that says otherwise. Without this,
        // Request::isSecure() is always false behind the proxy, and the
        // scheme-correctness the AppServiceProvider relies on is lost.
        //
        // '*' trusts any proxy, which is the practical choice on a private
        // network. Tighten it to the proxy's address if this is ever exposed
        // to the internet, since a trusted proxy may assert a client IP.
        $middleware->trustProxies(
            at: env("TRUSTED_PROXIES", "*"),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            "role" => \App\Http\Middleware\RoleMiddleware::class,
            "admin" => \App\Http\Middleware\AdminMiddleware::class,
            "force.password.change" => \App\Http\Middleware\ForcePasswordChange::class,
            "email.verified" => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        // Resolve the resident's language after the session and cookies are
        // available, but before any controller or Blade view runs.
        // ForcePasswordChange is NOT global: it needs the authenticated user,
        // so it runs as route middleware on the authenticated groups, after
        // `auth` in the pipeline.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($csrfFailureTarget) {
        /*
         * A CSRF failure must land the user back on the form they were filling
         * in, with their input intact and an explanation — not on Laravel's
         * bare 419 page.
         *
         * Note the exception type this is keyed on. It cannot be
         * TokenMismatchException: Handler::render() calls prepareException()
         * *before* renderViaCallbacks(), and prepareException() rewrites
         * TokenMismatchException into HttpException(419). A callback typed on
         * TokenMismatchException therefore never matches anything, which is why
         * this handler sat in the file doing nothing. HttpExceptionInterface
         * matches the rewritten exception, and the original is still reachable
         * through getPrevious().
         */
        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request,
        ) use ($csrfFailureTarget) {
            if (
                $e->getStatusCode() !== 419
                || ! $e->getPrevious() instanceof TokenMismatchException
            ) {
                // Not ours — returning null lets Laravel render it normally.
                return null;
            }

            /*
             * Log what actually happened. A CSRF failure has several very
             * different causes that look identical to the user — no session
             * cookie sent, a stale token, a scheme mismatch across a proxy — and
             * guessing between them from the outside wastes far more time than
             * recording the facts at the moment of failure.
             *
             * Deliberately records no token value and no cookie value, only
             * whether each was present, so the log stays safe to keep.
             */
            logger()->warning('csrf.token_mismatch', [
                'path' => $request->path(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'secure' => $request->isSecure(),
                'root' => $request->root(),
                'referer' => $request->headers->get('referer'),
                'has_session_cookie' => $request->hasCookie(config('session.cookie')),
                'session_id_present' => $request->hasSession() && filled($request->session()->getId()),
                'token_in_input' => $request->filled('_token') || $request->hasHeader('X-CSRF-TOKEN'),
                'forwarded_proto' => $request->headers->get('x-forwarded-proto'),
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->to($csrfFailureTarget($request))
                ->withInput(
                    $request->except("password", "password_confirmation"),
                )
                // Routed through the error bag rather than a plain `error`
                // flash. The auth views (login, register, forgot, reset) all
                // render $errors in their own .auth-alert styling but none of
                // them render session('error'), so a flash key would have been
                // set and silently discarded — a failed attempt would look
                // like the form simply did nothing.
                ->withErrors([
                    "session" => "Your session expired. Please try again.",
                ]);
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        // An unanswered request is the failure mode that quietly rots an LGU
        // workflow, so it gets swept daily rather than left to be noticed.
        // This only helps if `php artisan schedule:work` actually runs.
        $schedule->command('complaints:flag-stale-requests')
            ->dailyAt('07:15')
            ->withoutOverlapping();

        // Resolved reports the resident never confirmed close automatically
        // after 7 days, so cases do not stay open forever.
        $schedule->command('complaints:auto-close-resolved')
            ->dailyAt('07:30')
            ->withoutOverlapping();

        // Verified work that has slipped past its deadline. The queue shows
        // it, but a badge that nobody refreshes is not a safety net.
        $schedule->command('complaints:flag-overdue-work')
            ->dailyAt('07:45')
            ->withoutOverlapping();
    })
    ->create();
