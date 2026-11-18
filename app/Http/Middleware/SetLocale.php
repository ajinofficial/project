<?php

namespace App\Http\Middleware;

use App\Support\LocalePreference;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->preferred_locale
            ?: $request->session()->get('locale')
            ?: $request->cookie(config('localization.cookie'));

        if (! LocalePreference::supported($locale)) {
            $locale = config('app.locale', 'en');
        }

        app()->setLocale($locale);
        \Illuminate\Support\Carbon::setLocale($locale);
        $request->session()->put('locale', $locale);

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());

        return $response;
    }
}
