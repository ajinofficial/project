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
        $validated = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($validated['email']);
        $user = User::query()->where('email', $email)->first();

        // Do not disclose whether an address belongs to an account.
        if (! $user) {
            return back()->with('status', 'If an account uses that email address, a verification code has been sent.');
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
            Mail::to($user->email)->send(new PasswordResetOtpMail($otp));
        } catch (Throwable $exception) {
            Cache::forget($this->cacheKey($email));
            $request->session()->forget('password_reset_email');
            Log::error('Password reset OTP email could not be sent.', [
                'email' => $email,
                'exception' => $exception,
            ]);

            return back()->withErrors(['email' => 'We could not send the verification code. Please try again later.']);
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
        $validated = $request->validate(['otp' => ['required', 'digits:6']]);
        $email = $request->session()->get('password_reset_email');
        $otpData = $email ? Cache::get($this->cacheKey($email)) : null;

        if (! $otpData || ($otpData['attempts'] ?? 0) >= self::MAX_OTP_ATTEMPTS) {
            return redirect()->route('password.request')->withErrors(['email' => 'This verification code has expired. Please request a new one.']);
        }

        if (! Hash::check($validated['otp'], $otpData['hash'])) {
            $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
            Cache::put(
                $this->cacheKey($email),
                $otpData,
                now()->addSeconds(max(1, $otpData['expires_at'] - now()->timestamp))
            );

            return back()->withErrors(['otp' => 'That verification code is not correct.']);
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = $request->session()->get('password_reset_email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $request->session()->forget(['password_reset_email', 'password_reset_verified']);

            return redirect()->route('password.request')->withErrors(['email' => 'Please start the password reset process again.']);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $request->session()->forget(['password_reset_email', 'password_reset_verified']);

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.');
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
