<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Settings;

use App\Models\EnterpriseSetting;
use App\Models\Setting;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Foundation\Contracts\SettingsContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class DatabaseSettingsRepository implements SettingsContract
{
    private const REQUEST_SNAPSHOT_KEY = 'nexora.settings.snapshot';

    public function get(string $key, mixed $default = null): mixed
    {
        $snapshot = $this->snapshot();

        if (array_key_exists($key, $snapshot['tenant'])) {
            return $snapshot['tenant'][$key];
        }

        return array_key_exists($key, $snapshot['global'])
            ? $snapshot['global'][$key]
            : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $tenantId = $this->contextTenantId();
        if ($tenantId !== null && Schema::hasTable('nx_enterprise_settings')) {
            EnterpriseSetting::query()->updateOrCreate(
                ['organization_id' => $tenantId, 'key' => $key],
                ['id' => (string) Str::uuid(), 'value' => $value, 'type' => $this->type($value)],
            );
            $this->invalidateRequestSnapshot();
            return;
        }

        [$type, $encoded] = $this->encode($value);
        $group = str_contains($key, '.') ? explode('.', $key, 2)[0] : 'general';
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $encoded, 'type' => $type],
        );
        $this->invalidateRequestSnapshot();
    }

    /** @return array{context_tenant_id:?string,tenant:array<string,mixed>,global:array<string,mixed>} */
    private function snapshot(): array
    {
        $contextTenantId = $this->contextTenantId();
        $request = $this->currentRequest();
        $cached = $request?->attributes->get(self::REQUEST_SNAPSHOT_KEY);

        if (
            is_array($cached)
            && array_key_exists('context_tenant_id', $cached)
            && ($cached['context_tenant_id'] ?? null) === $contextTenantId
            && is_array($cached['tenant'] ?? null)
            && is_array($cached['global'] ?? null)
        ) {
            return $cached;
        }

        $global = [];
        foreach (Setting::query()->get(['key', 'value', 'type']) as $setting) {
            $global[(string) $setting->key] = $this->decode($setting->value, $setting->type);
        }

        $tenant = [];
        if ($contextTenantId !== null && Schema::hasTable('nx_enterprise_settings')) {
            foreach (
                EnterpriseSetting::query()
                    ->where('organization_id', $contextTenantId)
                    ->get(['key', 'value']) as $setting
            ) {
                // Preserve EnterpriseSetting's existing JSON value cast semantics.
                $tenant[(string) $setting->key] = $setting->value;
            }
        }

        $snapshot = [
            'context_tenant_id' => $contextTenantId,
            'tenant' => $tenant,
            'global' => $global,
        ];

        $request?->attributes->set(self::REQUEST_SNAPSHOT_KEY, $snapshot);

        return $snapshot;
    }

    private function contextTenantId(): ?string
    {
        if (! app()->bound(TenantContext::class)) {
            return null;
        }

        return app(TenantContext::class)->id();
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }

    private function invalidateRequestSnapshot(): void
    {
        $this->currentRequest()?->attributes->remove(self::REQUEST_SNAPSHOT_KEY);
    }

    private function type(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value), is_object($value) => 'json',
            $value === null => 'null',
            default => 'string',
        };
    }

    private function encode(mixed $value): array
    {
        return match (true) {
            is_bool($value) => ['boolean', $value ? '1' : '0'],
            is_int($value) => ['integer', (string) $value],
            is_float($value) => ['float', (string) $value],
            is_array($value), is_object($value) => ['json', json_encode($value, JSON_THROW_ON_ERROR)],
            $value === null => ['null', null],
            default => ['string', (string) $value],
        };
    }

    private function decode(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => $value === null ? null : json_decode($value, true, flags: JSON_THROW_ON_ERROR),
            'null' => null,
            default => $value,
        };
    }
}
