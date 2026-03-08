<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer("*", function ($view) {
            $layout = "layouts.guest"; // Default

            if (auth()->check()) {
                // If you have an admin role check:
                $layout = auth()->user()->is_admin
                    ? "layouts.admin"
                    : "layouts.app";
            }

            $view->with("mainLayout", $layout);
            $view->with("siteName", "Daet Listens");
        });
    }
}
