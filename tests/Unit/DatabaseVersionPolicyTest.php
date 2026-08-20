<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\Database\DatabaseVersionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseVersionPolicyTest extends TestCase
{
    public static function supported(): array
    {
        return [
            ['mysql','5.7.44-log'],['mariadb','10.11.6-MariaDB-0ubuntu0'],['pgsql','16.4'],['sqlite','3.45.3'],['sqlsrv','16.0.4125.3'],
        ];
    }

    #[Test, DataProvider('supported')]
    public function supported_versions_pass(string $driver,string $reported): void
    {
        (new DatabaseVersionPolicy())->assertSupported($driver,$reported);
        self::assertTrue(true);
    }

    public static function unsupported(): array
    {
        return [['mysql','5.6.51'],['mariadb','10.2.44-MariaDB'],['pgsql','9.6.24'],['sqlite','3.25.3'],['sqlsrv','13.0.6435.1']];
    }

    #[Test, DataProvider('unsupported')]
    public function unsupported_versions_fail(string $driver,string $reported): void
    {
        $this->expectException(RuntimeException::class);
        (new DatabaseVersionPolicy())->assertSupported($driver,$reported);
    }
}
