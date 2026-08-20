<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Nexora\Crm\Contracts\CrmActivityProviderContract;
use InvalidArgumentException;

final class CrmActivityProviderRegistry
{
    /** @var array<string,CrmActivityProviderContract> */
    private array $providers=[];

    public function register(CrmActivityProviderContract $provider): void
    {
        $key=trim($provider->key());
        if ($key==='' || ! preg_match('/^[a-z0-9][a-z0-9._-]+$/',$key)) throw new InvalidArgumentException('CRM activity providers require a stable lowercase key.');
        if (isset($this->providers[$key])) throw new InvalidArgumentException('CRM activity provider already registered: '.$key);
        $this->providers[$key]=$provider;
    }

    /** @return array<string,CrmActivityProviderContract> */
    public function all(): array { return $this->providers; }
    public function get(string $key): ?CrmActivityProviderContract { return $this->providers[$key]??null; }
}
