<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\SecurityScan;
use App\Models\Theme;
use App\Models\ThemeActivation;
use App\Models\ThemeVersion;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Sentinel\Support\QuarantineManager;
use App\Nexora\Security\Sentinel\Support\ScanRecorder;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use App\Nexora\Themes\Services\ThemePackageInstaller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ThemeController extends Controller
{
    public function __construct(
        private ThemeManagerContract $themes,
        private ThemePackageInstaller $installer,
        private QuarantineManager $quarantine,
        private ScanRecorder $scans,
        private AuditManager $audit,
    ) {
    }

    public function index(Request $request): Response
    {
        $items = Theme::query()->with(['currentVersion', 'versions' => fn ($query) => $query->latest('installed_at')])->orderByDesc('status')->orderBy('name')->get()->map(function (Theme $theme): array {
            $version = $theme->currentVersion ?? $theme->versions->first();
            $manifest = (array) ($version?->manifest ?? []);
            $screenshot = $this->screenshotUrl($version, $manifest);
            return [
                'id' => $theme->id,
                'identifier' => $theme->identifier,
                'name' => $theme->name,
                'description' => $theme->description,
                'status' => $theme->status,
                'isBuiltin' => (bool) $theme->is_builtin,
                'activeVersionId' => $theme->current_version_id,
                'version' => $version?->version,
                'engine' => $version?->engine,
                'screenshot' => $screenshot,
                'versions' => $theme->versions->map(static fn (ThemeVersion $item): array => [
                    'id' => $item->id,
                    'version' => $item->version,
                    'sha256' => $item->sha256,
                    'installedAt' => $item->installed_at?->toIso8601String(),
                ])->values()->all(),
                'tokens' => $version ? $this->tokenPayload($version) : [],
            ];
        })->values();

        $lastActivation = ThemeActivation::query()->latest('id')->first();
        return Inertia::render('Admin/Appearance/Themes', [
            'themes' => $items,
            'canRollback' => $lastActivation?->previous_theme_version_id !== null,
            'permissions' => [
                'install' => $request->user()?->hasPermission('themes.install') ?? false,
                'activate' => $request->user()?->hasPermission('themes.activate') ?? false,
                'manage' => $request->user()?->hasPermission('themes.manage') ?? false,
                'preview' => $request->user()?->hasPermission('themes.preview') ?? false,
            ],
        ]);
    }

    public function install(Request $request): RedirectResponse
    {
        if ($request->filled('scan_id')) {
            $validated = $request->validate([
                'scan_id' => ['required', 'uuid', 'exists:nx_security_scans,id'],
            ]);
            $scan = SecurityScan::query()->with('quarantinePackage')->findOrFail((string) $validated['scan_id']);

            return $this->promoteApprovedScan($scan, $request->user()?->id, true);
        }

        $maxKb = (int) config('sentinel.upload.max_kilobytes', 51_200);
        $validated = $request->validate(['package' => ['required', 'file', "max:{$maxKb}"]]);
        $file = $validated['package'];
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages(['package' => 'Theme packages must be ZIP archives.']);
        }

        $package = $this->quarantine->store($file, $request->user()?->id);
        $scan = $this->scans->scan($package, $request->user()?->id);
        $this->audit->record('theme.package.scanned', $scan, ['decision' => $scan->decision, 'risk_score' => $scan->risk_score]);
        if ($scan->decision !== 'allow') {
            return redirect()->route('admin.security.sentinel.show', $scan)->with('warning', 'Theme installation was blocked until Sentinel returns an ALLOW decision.');
        }

        return $this->promoteApprovedScan($scan->loadMissing('quarantinePackage'), $request->user()?->id, false);
    }

    public function activate(Request $request, ThemeVersion $version): RedirectResponse
    {
        $version->loadMissing('theme');
        $this->themes->activate($version, $request->user()?->id);
        $this->audit->record('theme.activated', $version, ['theme' => $version->theme?->identifier, 'version' => $version->version]);
        return back()->with('success', "Activated {$version->theme?->name} {$version->version}.");
    }

    public function rollback(Request $request): RedirectResponse
    {
        $version = $this->themes->rollback($request->user()?->id);
        if ($version === null) return back()->with('warning', 'No previous theme activation is available to roll back to.');
        $this->audit->record('theme.rolled_back', $version, ['theme' => $version->theme?->identifier, 'version' => $version->version]);
        return back()->with('success', "Rolled back to {$version->theme?->name} {$version->version}.");
    }

    public function preview(Request $request, ThemeVersion $version): JsonResponse
    {
        $token = $this->themes->createPreviewToken($version, (int) $request->user()->id);
        return response()->json(['url' => url('/theme-preview/'.$token), 'expires_in_minutes' => 20]);
    }

    public function updateTokens(Request $request, Theme $theme): RedirectResponse
    {
        $theme->loadMissing('currentVersion');
        $version = $theme->currentVersion ?? $theme->versions()->latest('installed_at')->first();
        if ($version === null) return back()->with('error', 'Theme has no installed version.');
        $values = $request->input('tokens', []);
        if (! is_array($values)) throw ValidationException::withMessages(['tokens' => 'Theme tokens must be submitted as an object.']);
        try {
            $this->themes->updateTokens($theme, $version, $values, $request->user()?->id);
            $this->audit->record('theme.tokens.updated', $theme, ['theme' => $theme->identifier, 'version' => $version->version, 'keys' => array_keys($values)]);
            return back()->with('success', 'Theme design tokens updated.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    private function promoteApprovedScan(SecurityScan $scan, ?int $userId, bool $preScanned): RedirectResponse
    {
        $scan->loadMissing('quarantinePackage');
        if (($scan->manifest['type'] ?? null) !== 'theme') {
            return back()->with('error', 'Only a Sentinel-scanned theme package can be promoted through the Theme Engine.');
        }
        if ($scan->quarantinePackage === null) {
            return back()->with('error', 'The quarantined theme package is no longer available.');
        }

        try {
            $version = $this->installer->install($scan->quarantinePackage, $scan, $userId);
            $this->audit->record('theme.installed', $version, [
                'theme_id' => $version->theme_id,
                'version' => $version->version,
                'scan_id' => $scan->id,
                'pre_scanned' => $preScanned,
            ]);
            return redirect()->route('admin.themes.index')->with(
                'success',
                $preScanned
                    ? 'Sentinel-approved theme installed safely. Preview it before activation.'
                    : 'Theme passed Sentinel and was installed safely. Preview it before activation.',
            );
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', 'Theme could not be installed: '.$exception->getMessage());
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function tokenPayload(ThemeVersion $version): array
    {
        $values = $this->themes->tokens($version);
        $definitions = (array) (($version->manifest ?? [])['design_tokens'] ?? []);
        $result = [];
        foreach ($definitions as $key => $definition) {
            if (! is_array($definition)) continue;
            $result[] = [
                'key' => (string) $key,
                'label' => (string) ($definition['label'] ?? str_replace(['.', '_', '-'], ' ', ucfirst((string) $key))),
                'type' => (string) ($definition['type'] ?? 'text'),
                'value' => $values[$key] ?? ($definition['default'] ?? null),
                'default' => $definition['default'] ?? null,
                'options' => array_values(array_map('strval', (array) ($definition['options'] ?? []))),
            ];
        }
        return $result;
    }

    /** @param array<string,mixed> $manifest */
    private function screenshotUrl(?ThemeVersion $version, array $manifest): ?string
    {
        if ($version === null || $version->asset_base_path === null) return null;
        $path = $manifest['screenshot'] ?? null;
        if (! is_string($path) || $path === '') return null;
        $asset = preg_replace('#^assets/#', '', str_replace('\\', '/', $path)) ?: basename($path);
        return rtrim($version->asset_base_path, '/').'/'.$asset;
    }
}
