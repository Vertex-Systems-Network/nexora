<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function __construct(
        private readonly InstallationState $installation,
        private readonly SettingsContract $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('localization.supported', ['en' => []]);
        $fallback = (string) config('localization.fallback', config('app.fallback_locale', 'en'));
        $cookieName = (string) config('localization.cookie', 'nexora_locale');

        // Resolving the auth user before installation can invoke the Eloquent
        // user provider and therefore the not-yet-configured application DB.
        $userLocale = $this->installation->isInstalled() ? $request->user()?->locale : null;

        $configuredDefault = $this->installation->isInstalled()
            ? $this->settings->get('app.default_locale', config('localization.default', config('app.locale', 'en')))
            : config('localization.default', config('app.locale', 'en'));

        $candidate = $userLocale
            ?: $request->session()->get('locale')
            ?: $request->cookie($cookieName)
            ?: $configuredDefault;

        $locale = is_string($candidate) && isset($supported[$candidate]) ? $candidate : $fallback;
        if (! isset($supported[$locale])) {
            $locale = array_key_first($supported) ?: 'en';
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);
        $request->attributes->set('nexora_locale', $locale);
        $request->attributes->set('nexora_direction', (string) ($supported[$locale]['dir'] ?? 'ltr'));

        return $next($request);
    }
}
