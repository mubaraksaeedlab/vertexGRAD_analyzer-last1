<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', config('app.locale'));

        if (! in_array($locale, config('app.supported_locales', ['en', 'ar']))) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}