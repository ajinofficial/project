<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<body style="margin:0;padding:32px;background:#f5f7f0;font-family:Arial,sans-serif;color:#17201a;">
    <div style="max-width:520px;margin:0 auto;padding:32px;border-radius:12px;background:#ffffff;">
        <h1 style="margin:0 0 16px;font-size:24px;">{{ __('auth.reset_your_password_bf8804') }}</h1>
        <p style="line-height:1.6;">{{ __('auth.use_this_verification_code_to_reset_your_zyloah_password_it_expires_in_2080ff') }}</p>
        <p style="margin:24px 0;padding:16px;border-radius:8px;background:#eaf5ed;color:#0f5132;font-size:30px;font-weight:700;letter-spacing:8px;text-align:center;">{{ $otp }}</p>
        <p style="line-height:1.6;color:#667066;">{{ __('common.if_you_did_not_request_this_code_you_can_safely_ignore_this_email_d6ac09') }}</p>
    </div>
</body>
</html>
