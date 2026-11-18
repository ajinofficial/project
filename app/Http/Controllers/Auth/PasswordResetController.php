<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    private const OTP_TTL_SECONDS = 600;

    private const MAX_OTP_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', new \App\Rules\SafeEmail],
        ], [
            'email.required' => __('auth.email_address_is_required_240bd7'),
            'email.email' => __('auth.enter_a_valid_email_address_2e09ed'),
        ]);
        $email = strtolower($validated['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('validation.we_could_not_find_an_account_with_this_email_7536fa'),
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put($this->cacheKey($email), [
            'hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS)->timestamp,
        ], now()->addSeconds(self::OTP_TTL_SECONDS));

        $request->session()->put('password_reset_email', $email);
        $request->session()->forget('password_reset_verified');

        try {
            $businessName = $user->tenant?->business_name ?: $user->company_name ?: config('app.name');
            Mail::to($user->email)->locale($user->preferredLocale())
                ->send(new PasswordResetOtpMail($otp, $businessName));
        } catch (Throwable $exception) {
            Cache::forget($this->cacheKey($email));
            $request->session()->forget('password_reset_email');
            Log::error('Password reset OTP email could not be sent.', [
                'email' => $email,
                'exception' => $exception,
            ]);

            return back()->withErrors(['email' => __('auth.we_could_not_send_the_verification_code_please_try_again_later_f5d61a')]);
        }

        return redirect()->route('password.otp.form')
            ->with('status', 'We sent a six-digit verification code to '.$this->maskedEmail($email).'.');
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-otp', ['email' => $this->maskedEmail($request->session()->get('password_reset_email'))]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => __('auth.verification_code_is_required_93daff'),
            'otp.digits' => __('auth.enter_a_valid_six_digit_verification_code_17f165'),
        ]);
        $email = $request->session()->get('password_reset_email');
        $otpData = $email ? Cache::get($this->cacheKey($email)) : null;

        if (! $otpData || ($otpData['attempts'] ?? 0) >= self::MAX_OTP_ATTEMPTS) {
            return redirect()->route('password.request')->withErrors(['email' => __('auth.this_verification_code_has_expired_please_request_a_new_one_c9f20e')]);
        }

        if (! Hash::check($validated['otp'], $otpData['hash'])) {
            $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
            Cache::put(
                $this->cacheKey($email),
                $otpData,
                now()->addSeconds(max(1, $otpData['expires_at'] - now()->timestamp))
            );

            return back()->withErrors(['otp' => __('auth.that_verification_code_is_not_correct_cd26b5')]);
        }

        Cache::forget($this->cacheKey($email));
        $request->session()->put('password_reset_verified', true);

        return redirect()->route('password.reset.form');
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('password_reset_verified') || ! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request): RedirectResponse
    {
        if (! $request->session()->get('password_reset_verified')) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ], [
            'password.required' => __('auth.new_password_is_required_d2eb9a'),
            'password.min' => __('auth.new_password_must_be_at_least_8_characters_f36bc7'),
            'password.letters' => __('auth.new_password_must_include_at_least_one_letter_60b5d2'),
            'password.numbers' => __('auth.new_password_must_include_at_least_one_number_78e5b7'),
            'password.confirmed' => __('auth.the_password_confirmation_does_not_match_d54123'),
        ]);

        $email = $request->session()->get('password_reset_email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $request->session()->forget(['password_reset_email', 'password_reset_verified']);

            return redirect()->route('password.request')->withErrors(['email' => __('auth.please_start_the_password_reset_process_again_267163')]);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $request->session()->forget(['password_reset_email', 'password_reset_verified']);

        return redirect()->route('login')->with('status', __('auth.your_password_has_been_reset_you_can_now_log_in_5b4fa2'));
    }

    private function cacheKey(string $email): string
    {
        return 'password-reset-otp:'.hash('sha256', $email);
    }

    private function maskedEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);

        return substr($name, 0, 1).str_repeat('•', max(1, strlen($name) - 1)).'@'.$domain;
    }
}
