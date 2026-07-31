<?php

namespace App\Support;

class RedirectUrl
{
    public static function encode(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        $relativeUrl = $path.($query ? '?'.$query : '');

        return rtrim(strtr(base64_encode($relativeUrl), '+/', '-_'), '=');
    }

    public static function decode(?string $encodedUrl, string $fallback): string
    {
        if (! is_string($encodedUrl) || $encodedUrl === '') {
            return $fallback;
        }

        $padding = strlen($encodedUrl) % 4;
        $decodedUrl = base64_decode(
            strtr($encodedUrl, '-_', '+/').($padding ? str_repeat('=', 4 - $padding) : ''),
            true
        );

        if (! is_string($decodedUrl) || ! str_starts_with($decodedUrl, '/') || str_starts_with($decodedUrl, '//')) {
            return $fallback;
        }

        return $decodedUrl;
    }
}
