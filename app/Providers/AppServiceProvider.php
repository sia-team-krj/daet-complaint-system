<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
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
