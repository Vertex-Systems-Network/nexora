<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

final class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $supported = array_keys((array) config('localization.supported', ['en' => []]));
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($supported)],
        ]);

        $locale = (string) $validated['locale'];
        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        if ($request->user() !== null && $request->user()->locale !== $locale) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        $cookieName = (string) config('localization.cookie', 'nexora_locale');

        return back(303)->withCookie(cookie(
            $cookieName,
            $locale,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'Lax',
        ));
    }
}
