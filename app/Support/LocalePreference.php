<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

final class LocalePreference
{
    public static function supported(mixed $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, config('localization.supported', []));
    }

    public static function remember(Request $request, string $locale): void
    {
        $request->session()->put('locale', $locale);
        Cookie::queue(cookie(
            config('localization.cookie'), $locale, config('localization.cookie_minutes'),
            config('session.path', '/'), config('session.domain'),
            $request->isSecure() || config('session.secure'), true, false, 'lax'
        ));
    }

    public static function afterLogin(Request $request): void
    {
        $locale = $request->session()->get('locale_explicit')
            ? $request->session()->get('locale')
            : ($request->user()->preferred_locale ?: app()->getLocale());

        if (! self::supported($locale)) {
            $locale = config('app.locale', 'en');
        }

        if ((! $request->user()->preferred_locale || $request->session()->get('locale_explicit')) && $request->user()->preferred_locale !== $locale) {
            $request->user()->forceFill(['preferred_locale' => $locale])->save();
        }

        app()->setLocale($locale);
        self::remember($request, $locale);
        $request->session()->forget('locale_explicit');
    }
}
