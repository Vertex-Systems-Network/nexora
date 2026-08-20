<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Services;

use App\Models\Theme;
use App\Models\ThemeActivation;
use App\Models\ThemePreviewToken;
use App\Models\ThemeSetting;
use App\Models\ThemeVersion;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ThemeManager implements ThemeManagerContract
{
    public function __construct(private SettingsContract $settings)
    {
    }

    public function active(): ?ThemeVersion
    {
        $theme = Theme::query()->with('currentVersion')->where('status', 'active')->first();
        $version = $theme?->currentVersion;
        if ($version !== null && $this->runtimeFilesExist($version)) {
            return $version;
        }

        $fallback = Theme::query()->with('currentVersion')->where('identifier', 'nexora.base')->first()?->currentVersion;
        return $fallback !== null && $this->runtimeFilesExist($fallback) ? $fallback : null;
    }

    public function activate(ThemeVersion $version, ?int $userId = null, string $action = 'activate', ?string $reason = null): void
    {
        $version->loadMissing('theme');
        if ($version->theme === null) throw new \RuntimeException('Theme version is detached from its theme.');
        $this->assertRuntimeIntegrity($version);

        DB::transaction(function () use ($version, $userId, $action, $reason): void {
            $previous = Theme::query()->with('currentVersion')->where('status', 'active')->lockForUpdate()->first();
            Theme::query()->where('status', 'active')->update(['status' => 'inactive']);

            $version->theme->forceFill([
                'status' => 'active',
                'current_version_id' => $version->id,
                'activated_at' => now(),
            ])->save();

            ThemeActivation::query()->create([
                'theme_id' => $version->theme->id,
                'theme_version_id' => $version->id,
                'previous_theme_id' => $previous?->id,
                'previous_theme_version_id' => $previous?->current_version_id,
                'action' => $action,
                'reason' => $reason,
                'user_id' => $userId,
            ]);

            $this->settings->set('appearance.active_theme', $version->theme->identifier);
            $this->settings->set('appearance.active_theme_version', $version->version);
        });
    }

    public function rollback(?int $userId = null): ?ThemeVersion
    {
        $active = Theme::query()->where('status', 'active')->first();
        if ($active === null) return null;

        $activation = ThemeActivation::query()
            ->where('theme_id', $active->id)
            ->whereNotNull('previous_theme_version_id')
            ->latest('id')
            ->first();
        if ($activation === null) return null;

        $version = ThemeVersion::query()->with('theme')->find($activation->previous_theme_version_id);
        if ($version === null) return null;

        $this->activate($version, $userId, 'rollback', 'Rolled back to the previous activation snapshot.');
        return $version;
    }

    public function createPreviewToken(ThemeVersion $version, int $userId, int $minutes = 20): string
    {
        $this->assertRuntimeIntegrity($version);
        ThemePreviewToken::query()->where('user_id', $userId)->where('expires_at', '<', now())->delete();
        $raw = bin2hex(random_bytes(32));
        ThemePreviewToken::query()->create([
            'id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $raw),
            'theme_version_id' => $version->id,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes(max(5, min(60, $minutes))),
        ]);
        return $raw;
    }

    public function resolvePreviewToken(string $token, int $userId): ?ThemeVersion
    {
        if ($token === '' || strlen($token) > 256) return null;
        $record = ThemePreviewToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->first();
        if ($record === null) return null;
        $record->forceFill(['last_used_at' => now()])->save();
        $version = ThemeVersion::query()->with('theme')->find($record->theme_version_id);
        if ($version) $this->assertRuntimeIntegrity($version);
        return $version;
    }

    public function tokens(ThemeVersion $version): array
    {
        $definitions = (array) (($version->manifest ?? [])['design_tokens'] ?? []);
        $overrides = ThemeSetting::query()->where('theme_id', $version->theme_id)->get()->mapWithKeys(static fn (ThemeSetting $setting): array => [$setting->key => $setting->value])->all();
        $result = [];
        foreach ($definitions as $key => $definition) {
            if (! is_array($definition)) continue;
            $value = array_key_exists($key, $overrides) ? $overrides[$key] : ($definition['default'] ?? null);
            if (is_array($value) && array_key_exists('value', $value)) $value = $value['value'];
            $result[(string) $key] = $value;
        }
        return $result;
    }

    public function updateTokens(Theme $theme, ThemeVersion $version, array $values, ?int $userId = null): void
    {
        if ($version->theme_id !== $theme->id) throw new \InvalidArgumentException('Theme version mismatch.');
        $definitions = (array) (($version->manifest ?? [])['design_tokens'] ?? []);
        foreach ($values as $key => $value) {
            if (! isset($definitions[$key]) || ! is_array($definitions[$key])) continue;
            $normalized = $this->normalizeToken((string) ($definitions[$key]['type'] ?? 'text'), $value, $definitions[$key]);
            ThemeSetting::query()->updateOrCreate(
                ['theme_id' => $theme->id, 'key' => $key],
                ['theme_version_id' => $version->id, 'value' => ['value' => $normalized], 'updated_by' => $userId],
            );
        }
    }

    private function runtimeFilesExist(ThemeVersion $version): bool
    {
        try {
            $this->assertRuntimeIntegrity($version);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function assertRuntimeIntegrity(ThemeVersion $version): void
    {
        $manifest = (array) $version->manifest;
        $templates = (array) ($manifest['templates'] ?? []);
        foreach (['home', 'document'] as $required) {
            $relative = $templates[$required] ?? null;
            if (! is_string($relative) || ! is_file(rtrim($version->install_path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
                throw new \RuntimeException("Theme runtime integrity failed: required [{$required}] template is missing.");
            }
        }
        $stylesheet = $manifest['stylesheet'] ?? null;
        if (is_string($stylesheet) && $stylesheet !== '' && ! is_file(rtrim($version->install_path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $stylesheet))) {
            throw new \RuntimeException('Theme runtime integrity failed: declared stylesheet is missing.');
        }
    }

    /** @param array<string,mixed> $definition */
    private function normalizeToken(string $type, mixed $value, array $definition): mixed
    {
        return match ($type) {
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtoupper($value) : throw new \InvalidArgumentException('Theme color tokens require a six-digit hex color.'),
            'number' => is_numeric($value) ? (float) $value : throw new \InvalidArgumentException('Theme number token must be numeric.'),
            'select' => in_array((string) $value, array_map('strval', (array) ($definition['options'] ?? [])), true) ? (string) $value : throw new \InvalidArgumentException('Theme select token value is not allowed.'),
            default => preg_match('/^[A-Za-z0-9 .,_-]{0,120}$/', trim((string) $value)) === 1 ? trim((string) $value) : throw new \InvalidArgumentException('Theme text token contains unsupported CSS characters.'),
        };
    }
}
