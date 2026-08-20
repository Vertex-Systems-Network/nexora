<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use App\Nexora\Themes\Contracts\ThemeRendererContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ThemePreviewController extends Controller
{
    public function __construct(private ThemeManagerContract $themes, private ThemeRendererContract $renderer, private SettingsContract $settings)
    {
    }

    public function __invoke(Request $request, string $token): Response
    {
        $userId = (int) $request->user()->id;
        $version = $this->themes->resolvePreviewToken($token, $userId);
        abort_if($version === null, 404);
        $siteName = (string) $this->settings->get('app.name', 'Nexora');
        $html = $this->renderer->render('home', [
            'site_name' => $siteName,
            'page_title' => 'Theme Preview · '.$version->theme?->name,
            'tagline' => 'Private theme preview. This does not change the active public theme.',
            'nx_head' => '<title>Theme Preview · '.e((string) $version->theme?->name).'</title><meta name="robots" content="noindex,nofollow">',
            'nx_schema' => '',
            'nx_content' => '<section class="nx-preview-notice"><strong>Private preview</strong><p>You are previewing '.e((string) $version->theme?->name).' '.e($version->version).'. Public visitors still see the active theme.</p></section>',
        ], $version);
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8')->header('Cache-Control', 'private, no-store');
    }
}
