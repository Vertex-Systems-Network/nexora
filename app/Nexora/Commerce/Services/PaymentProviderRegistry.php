<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use InvalidArgumentException;

final class PaymentProviderRegistry
{
    /** @var array<string,PaymentProviderContract> */
    private array $providers = [];

    public function register(PaymentProviderContract $provider): void
    {
        $key = trim($provider->key());
        if ($key === '' || preg_match('/^[a-z0-9][a-z0-9._-]+$/', $key) !== 1) {
            throw new InvalidArgumentException('Payment providers require a stable lowercase key.');
        }
        if (isset($this->providers[$key])) {
            throw new InvalidArgumentException('Payment provider already registered: '.$key);
        }
        $this->providers[$key] = $provider;
    }

    /** @return array<string,PaymentProviderContract> */
    public function all(): array { return $this->providers; }
    public function has(string $key): bool { return isset($this->providers[$key]); }
    public function get(string $key): ?PaymentProviderContract { return $this->providers[$key] ?? null; }
}
