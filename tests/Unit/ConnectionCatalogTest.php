<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Data\ConnectionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ConnectionCatalogTest extends TestCase
{
    #[Test]
    public function it_exposes_document_cache_and_aws_auxiliary_connections_without_making_them_primary_sql_drivers(): void
    {
        $catalog = app(ConnectionCatalog::class)->all();

        foreach (['mongodb', 'mongodb_atlas', 'redis', 'aws_documentdb', 'aws_elasticache_redis', 'aws_dynamodb'] as $key) {
            self::assertArrayHasKey($key, $catalog);
            self::assertArrayHasKey('available', $catalog[$key]);
            self::assertArrayHasKey('requirement', $catalog[$key]);
        }
    }
}
