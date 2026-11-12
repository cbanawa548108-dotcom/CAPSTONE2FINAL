<?php

namespace App\Providers;

use App\Models\OwnerSetting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // On Railway (production), always build links and asset URLs with https://
        // so the browser doesn't block CSS/JS as insecure content.
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // The signed-in user's saved appearance (theme, dark mode, font size) for every page
        View::composer('components.app-layout', function ($view) {
            $view->with('appearance', auth()->check() ? OwnerSetting::forUser(auth()->id())->appearance() : null);
        });

        // The signed-in user's language / region / notification preferences
        View::composer(['partials.navbar', 'pages.pos', 'pages.dashboard'], function ($view) {
            if (auth()->check()) {
                $view->with('prefs', OwnerSetting::forUser(auth()->id()));
            }
        });
    }
}
