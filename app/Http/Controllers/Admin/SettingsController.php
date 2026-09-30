<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Security\Audit\AuditManager;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    public function edit(SettingsContract $settings): Response
    {
        $supportedLocales = (array) config('localization.supported', ['en' => ['name' => 'English', 'native' => 'English']]);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'appName' => $settings->get('app.name', config('app.name', 'Nexora')),
                'logoUrl' => $settings->get('app.logo_url', ''),
                'defaultTimezone' => $settings->get('app.default_timezone', 'UTC'),
                'defaultLocale' => $settings->get('app.default_locale', (string) config('localization.default', 'en')),
                'theme' => $settings->get('appearance.theme', 'system'),
                'primary' => $settings->get('appearance.primary', '#7C3AED'),
                'density' => $settings->get('appearance.density', 'comfortable'),
                'radius' => $settings->get('appearance.radius', 'medium'),
            ],
            'timezoneOptions' => array_map(static fn (string $timezone): array => [
                'value' => $timezone,
                'label' => str_replace('_', ' ', $timezone),
            ], DateTimeZone::listIdentifiers()),
            'localeOptions' => array_map(static fn (array $locale, string $code): array => [
                'value' => $code,
                'label' => (string) ($locale['native'] ?? $locale['name'] ?? $code),
                'description' => (string) ($locale['name'] ?? $code),
            ], $supportedLocales, array_keys($supportedLocales)),
        ]);
    }

    public function update(Request $request, SettingsContract $settings, AuditManager $audit): RedirectResponse
    {
        $supportedLocales = array_keys((array) config('localization.supported', ['en' => []]));

        $data = $request->validate([
            'appName' => ['required', 'string', 'max:80'],
            'logoUrl' => ['nullable', 'string', 'max:2048', 'regex:~^(?:/|https?://)~i'],
            'defaultTimezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'defaultLocale' => ['required', Rule::in($supportedLocales)],
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'density' => ['required', Rule::in(['comfortable', 'compact'])],
            'radius' => ['required', Rule::in(['small', 'medium', 'large'])],
        ]);

        $settings->set('app.name', $data['appName']);
        $settings->set('app.logo_url', trim((string) ($data['logoUrl'] ?? '')));
        $settings->set('app.default_timezone', $data['defaultTimezone']);
        $settings->set('app.default_locale', $data['defaultLocale']);
        $settings->set('appearance.theme', $data['theme']);
        $settings->set('appearance.primary', strtoupper($data['primary']));
        $settings->set('appearance.density', $data['density']);
        $settings->set('appearance.radius', $data['radius']);

        $audit->record('settings.updated', metadata: ['keys' => array_keys($data)]);

        return back()->with('success', 'Settings saved.');
    }
}
