<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Nexora\Automation\Services\WebhookUrlPolicy;
use ReflectionMethod;
use Tests\TestCase;

final class WebhookUrlPolicyDefaultsTest extends TestCase
{
    public function test_webhook_policy_resolves_dns_by_default(): void
    {
        $parameters = (new ReflectionMethod(WebhookUrlPolicy::class, 'assertAllowed'))->getParameters();
        $resolveDns = $parameters[1] ?? null;

        $this->assertNotNull($resolveDns);
        $this->assertTrue($resolveDns->isDefaultValueAvailable());
        $this->assertTrue($resolveDns->getDefaultValue());
    }
}
