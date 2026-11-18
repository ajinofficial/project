<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $eventTitle }}</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7f0;color:#17201a;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:32px;border:1px solid #dfe6db;border-radius:12px;background:#ffffff;">
        <p style="margin:0 0 8px;color:#146c43;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">{{ __('common.important_activity_1ae691') }}</p>
        <h1 style="margin:0 0 16px;font-size:24px;">{{ $eventTitle }}</h1>
        <p style="margin:0;line-height:1.6;color:#4b554d;">{{ $eventMessage }}</p>
        <p style="margin:24px 0 0;padding-top:16px;border-top:1px solid #dfe6db;color:#7a847c;font-size:12px;">
            {{ __('notifications.this_is_an_automatic_security_and_activity_notification_from_zyloah_92dad2') }}
        </p>
    </div>
</body>
</html>
