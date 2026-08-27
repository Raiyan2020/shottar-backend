<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = session('dashboard_locale')
            ?? $request->cookie('dashboard_locale')
            ?? App::getLocale();

        if (in_array($locale, ['ar', 'en'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
