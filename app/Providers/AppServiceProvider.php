<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Follow the scheme the request actually arrived on.
        //
        // This used to be driven by APP_URL: if APP_URL was https, every URL
        // was forced to https for every request. On any plain-HTTP access path
        // (LAN IP, localhost, php artisan serve) that meant a form rendered over
        // http posted to https:// — a different schemeful origin — and because
        // the session cookie is SameSite=lax the browser withheld it. No session
        // cookie on POST means the CSRF token cannot be validated, so every
        // login and registration died on 419 Page Expired.
        //
        // Deriving it from the request instead means a generated URL always
        // matches the origin the browser is on, which is what keeps the cookie
        // attached. Proxies are handled by trusting X-Forwarded-* in
        // bootstrap/app.php, so this stays correct behind Tailscale serve.
        if (request()->isSecure()) {
            URL::forceScheme('https');
        }

        View::composer("*", function ($view) {
            $layout = "layouts.guest"; // Default

            if (auth()->check()) {
                $layout = "layouts.app";
            }

            $view->with("mainLayout", $layout);
            $view->with("siteName", "Daet Listens");
        });
    }
}
