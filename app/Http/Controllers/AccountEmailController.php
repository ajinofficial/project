<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountEmailController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'email' => [
                'required',
                new \App\Rules\SafeEmail,
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
        ], [
            'email.unique' => 'This email address is already registered.',
        ]);

        DB::transaction(function () use ($request, $data) {
            $user = $request->user();

            $user->forceFill([
                'email' => $data['email'],
                'email_verified' => false,
                'email_verified_at' => null,
            ])->save();

            if ((int) $user->role === User::ROLE_OWNER) {
                $user->tenant()->update(['email' => $data['email']]);
            }
        });

        Cache::forget('email-verification-otp:'.$request->user()->id);

        return response()->json([
            'email' => $data['email'],
            'message' => 'Email updated. Send a new OTP to verify it.',
        ]);
    }
}
