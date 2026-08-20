<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use App\Nexora\Crm\Contracts\CrmActivityProviderContract;
use App\Nexora\Crm\Services\CrmActivityProviderRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CrmActivityProviderRegistryTest extends TestCase
{
    public function test_registry_rejects_duplicate_provider_keys(): void
    {
        $registry = new CrmActivityProviderRegistry();
        $provider = new class implements CrmActivityProviderContract {
            public function key(): string { return 'mail.example'; }
            public function label(): string { return 'Example Mail'; }
            public function capabilities(): array { return ['email.read']; }
            public function health(): array { return ['ok' => true, 'message' => 'Ready']; }
        };

        $registry->register($provider);
        self::assertSame($provider, $registry->get('mail.example'));

        $this->expectException(InvalidArgumentException::class);
        $registry->register($provider);
    }

    public function test_registry_rejects_unstable_provider_keys(): void
    {
        $registry = new CrmActivityProviderRegistry();
        $provider = new class implements CrmActivityProviderContract {
            public function key(): string { return 'Gmail Provider'; }
            public function label(): string { return 'Bad Provider'; }
            public function capabilities(): array { return []; }
            public function health(): array { return ['ok' => false, 'message' => 'Invalid']; }
        };

        $this->expectException(InvalidArgumentException::class);
        $registry->register($provider);
    }
}
