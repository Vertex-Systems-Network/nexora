<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Nexora\Enterprise\Contracts\EnterpriseIdentityProviderContract;
use LogicException;

final class SsoProviderRegistry
{
    /** @var array<string,EnterpriseIdentityProviderContract> */ private array $providers=[];
    public function register(EnterpriseIdentityProviderContract $provider): void
    {
        $key=$provider->key();
        if (! preg_match('/^[a-z0-9][a-z0-9._-]{2,159}$/',$key)) throw new LogicException('Invalid enterprise identity provider key.');
        if (isset($this->providers[$key])) throw new LogicException('Duplicate enterprise identity provider: '.$key);
        if (! in_array($provider->protocol(),['oidc','saml'],true)) throw new LogicException('Unsupported enterprise identity protocol.');
        $this->providers[$key]=$provider;
    }
    public function get(string $key): ?EnterpriseIdentityProviderContract { return $this->providers[$key]??null; }
    /** @return list<array{key:string,protocol:string}> */ public function all(): array { return array_values(array_map(fn($p)=>['key'=>$p->key(),'protocol'=>$p->protocol()],$this->providers)); }
}
