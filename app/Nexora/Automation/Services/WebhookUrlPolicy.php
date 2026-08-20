<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use App\Nexora\Foundation\Network\NetworkDestinationPolicy;

final readonly class WebhookUrlPolicy
{
    public function __construct(private NetworkDestinationPolicy $policy) {}
    public function assertAllowed(string $url,bool $resolveDns=false): void { $this->policy->external($url,$resolveDns); }
}
