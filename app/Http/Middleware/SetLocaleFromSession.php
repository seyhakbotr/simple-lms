<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next)
    {
        $locale = null;

        if ($request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        if (! $locale) {
            $locale = $request->cookie('locale');
        }

        if (in_array($locale, ['en', 'km'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
