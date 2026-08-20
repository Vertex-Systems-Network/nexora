<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SeoSettingsController extends Controller
{
    public function edit(SettingsContract $settings): Response
    {
        return Inertia::render('Admin/Seo/Settings', ['settings' => [
            'site_name' => $settings->get('seo.site_name', $settings->get('app.name', 'Nexora')),
            'organization_name' => $settings->get('seo.organization_name', ''),
            'organization_url' => $settings->get('seo.organization_url', config('app.url', '')),
            'organization_logo' => $settings->get('seo.organization_logo', ''),
        ]]);
    }

    public function update(Request $request, SettingsContract $settings, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'organization_name' => ['nullable', 'string', 'max:180'],
            'organization_url' => ['nullable', 'url:http,https', 'max:2048'],
            'organization_logo' => ['nullable', 'url:http,https', 'max:2048'],
        ]);
        foreach ($data as $key => $value) $settings->set('seo.'.$key, $value ?: '');
        $audit->record('seo.settings.updated', metadata: ['keys' => array_keys($data)]);
        return back()->with('success', 'SEO site identity settings saved.');
    }
}
