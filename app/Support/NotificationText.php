<?php

namespace App\Support;

/** Read historical English activity snapshots without rewriting audit records. */
final class NotificationText
{
    public static function historicalData(string $type, string $message): ?array
    {
        $key = 'notifications.events.'.$type;
        $translator = app('translator');
        if (! $translator->hasForLocale($key, 'en')) {
            return null;
        }
        $template = $translator->get($key, [], 'en');
        $parts = preg_split('/(:[a-z_]+)/', $template, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pattern = implode('', array_map(fn ($part) => preg_match('/^:[a-z_]+$/D', $part)
            ? '(?P<'.substr($part, 1).'>.+?)' : preg_quote($part, '~'), $parts));
        if (! preg_match('~^'.$pattern.'$~usD', $message, $matches)) {
            return null;
        }
        return ['key' => $key, 'parameters' => array_filter($matches, fn ($name) => is_string($name), ARRAY_FILTER_USE_KEY)];
    }
}
