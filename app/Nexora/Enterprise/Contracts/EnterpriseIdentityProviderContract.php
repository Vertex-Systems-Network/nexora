<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Contracts;

use App\Models\EnterpriseSsoProvider;
use Illuminate\Http\Request;

interface EnterpriseIdentityProviderContract
{
    public function key(): string;
    public function protocol(): string;
    /** @return array<string,mixed> */
    public function health(EnterpriseSsoProvider $provider): array;
    public function redirectUrl(EnterpriseSsoProvider $provider, string $state): string;
    /** @return array{email:string,name?:string,external_id?:string,attributes?:array<string,mixed>} */
    public function resolveIdentity(EnterpriseSsoProvider $provider, Request $request): array;
}
