<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request and set application locale from header.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('X-App-Locale') ?: $request->header('Accept-Language');

        if ($locale) {
            // Normalize: take first two letters (en, fr, ar)
            $locale = strtolower(substr($locale, 0, 2));
            // Whitelist supported locales
            $supported = ['en', 'fr', 'ar'];
            if (in_array($locale, $supported, true)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
