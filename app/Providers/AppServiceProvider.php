<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

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
        // Push SetLocale middleware to API group so backend uses frontend locale
        $router = $this->app['router'] ?? null;
        if ($router instanceof Router) {
            $router->pushMiddlewareToGroup('api', \App\Http\Middleware\SetLocale::class);
        }
    }
}
