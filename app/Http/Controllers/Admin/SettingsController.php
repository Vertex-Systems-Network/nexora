<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    public function edit(SettingsContract $settings): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'appName' => $settings->get('app.name', config('app.name', 'Nexora')),
                'theme' => $settings->get('appearance.theme', 'system'),
                'primary' => $settings->get('appearance.primary', '#7C3AED'),
                'density' => $settings->get('appearance.density', 'comfortable'),
                'radius' => $settings->get('appearance.radius', 'medium'),
            ],
        ]);
    }

    public function update(Request $request, SettingsContract $settings, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'appName' => ['required', 'string', 'max:80'],
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'density' => ['required', Rule::in(['comfortable', 'compact'])],
            'radius' => ['required', Rule::in(['small', 'medium', 'large'])],
        ]);

        $settings->set('app.name', $data['appName']);
        $settings->set('appearance.theme', $data['theme']);
        $settings->set('appearance.primary', strtoupper($data['primary']));
        $settings->set('appearance.density', $data['density']);
        $settings->set('appearance.radius', $data['radius']);

        $audit->record('settings.updated', metadata: ['keys' => array_keys($data)]);

        return back()->with('success', 'Settings saved.');
    }
}
