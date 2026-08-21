<?php

declare(strict_types=1);

namespace Tests\Unit\Data;

use App\Nexora\Data\ConnectionTester;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

final class ConnectionTesterPortabilityTest extends TestCase
{
    #[Test]
    public function dynamodb_rejects_partial_static_credentials_before_sdk_resolution(): void
    {
        $result = (new ConnectionTester())->testPayload([
            'driver' => 'aws_dynamodb',
            'region' => 'us-east-1',
            'access_key' => 'AKIAEXAMPLE',
            'secret_key' => '',
        ]);

        self::assertFalse($result['ok']);
        self::assertSame('AWS access key and secret key must be provided together.', $result['message']);
    }

    #[Test]
    public function redis_rediss_endpoint_maps_to_tls_transport_for_both_clients(): void
    {
        $method = new ReflectionMethod(ConnectionTester::class, 'redisEndpoint');
        $result = $method->invoke(new ConnectionTester(), 'rediss://cache.example.test:6380', 6379);

        self::assertSame(['tls', 'cache.example.test', 6380], $result);
    }

    #[Test]
    public function redis_plain_endpoint_maps_to_tcp_transport(): void
    {
        $method = new ReflectionMethod(ConnectionTester::class, 'redisEndpoint');
        $result = $method->invoke(new ConnectionTester(), 'cache.example.test:6379', 6379);

        self::assertSame(['tcp', 'cache.example.test', 6379], $result);
    }

    #[Test]
    public function redis_endpoint_rejects_non_redis_url_schemes(): void
    {
        $method = new ReflectionMethod(ConnectionTester::class, 'redisEndpoint');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis endpoint scheme must be redis://, rediss://, tcp:// or tls://.');

        $method->invoke(new ConnectionTester(), 'https://cache.example.test:6379', 6379);
    }
}
