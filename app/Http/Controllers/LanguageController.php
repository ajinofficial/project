<?php

namespace App\Http\Controllers;

use App\Support\LocalePreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('localization.supported')))],
            'return_to' => ['nullable', 'string', 'max:4096'],
        ]);

        LocalePreference::remember($request, $data['locale']);
        $request->session()->put('locale_explicit', true);

        if ($request->user() && $request->user()->preferred_locale !== $data['locale']) {
            $request->user()->forceFill(['preferred_locale' => $data['locale']])->save();
        }

        app()->setLocale($data['locale']);

        // Accept only local, root-relative paths, never a host supplied by the client.
        $target = $data['return_to'] ?? '/';
        $decoded = rawurldecode($target);
        if (! str_starts_with($target, '/') || str_starts_with($decoded, '//')
            || preg_match('/[\\\\\x00-\x20\x7f]/', $decoded)
            || parse_url($target, PHP_URL_HOST) !== null) {
            $target = '/';
        }

        return redirect()->to(url($target), 303);
    }
}
