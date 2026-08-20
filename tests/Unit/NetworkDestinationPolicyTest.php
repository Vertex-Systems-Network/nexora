<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Network\NetworkDestinationPolicy;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class NetworkDestinationPolicyTest extends TestCase
{
    #[Test]
    public function external_policy_rejects_private_reserved_and_embedded_credentials(): void
    {
        $policy=app(NetworkDestinationPolicy::class);config()->set('nexora-network-runtime.external.block_private_reserved',true);
        $this->expectException(RuntimeException::class);$policy->external('https://127.0.0.1/internal',true);
    }

    #[Test]
    public function external_policy_rejects_embedded_credentials(): void
    {
        $this->expectException(RuntimeException::class);app(NetworkDestinationPolicy::class)->external('https://user:pass@example.com/hook',false);
    }

    #[Test]
    public function same_origin_policy_rejects_origin_escape_without_network_io(): void
    {
        $this->expectException(RuntimeException::class);app(NetworkDestinationPolicy::class)->sameOrigin('https://other.example/path','https://site.example');
    }
}
