<?php

declare(strict_types=1);

namespace App\Nexora\Ai\Services;

use App\Nexora\Ai\Contracts\AiTextProviderContract;
use InvalidArgumentException;

final class AiProviderRegistry
{
    /** @var array<string,AiTextProviderContract> */
    private array $providers = [];

    public function register(AiTextProviderContract $provider): void
    {
        $key = trim($provider->key());
        if ($key === '' || preg_match('/^[a-z0-9][a-z0-9._-]+$/', $key) !== 1) {
            throw new InvalidArgumentException('AI providers require a stable lowercase key.');
        }
        if (isset($this->providers[$key])) {
            throw new InvalidArgumentException('AI provider already registered: '.$key);
        }
        $this->providers[$key] = $provider;
        ksort($this->providers);
    }

    /** @return array<string,AiTextProviderContract> */
    public function all(): array { return $this->providers; }
    public function has(string $key): bool { return isset($this->providers[$key]); }
    public function get(string $key): ?AiTextProviderContract { return $this->providers[$key] ?? null; }
}
