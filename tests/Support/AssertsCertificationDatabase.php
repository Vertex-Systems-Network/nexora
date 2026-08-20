<?php

declare(strict_types=1);

namespace Tests\Support;

trait AssertsCertificationDatabase
{
    protected function assertCertificationDatabaseBinding(): void
    {
        $expectedConnection=(string)(getenv('NEXORA_CERT_EXPECT_DB_CONNECTION') ?: '');
        $expectedDatabase=(string)(getenv('NEXORA_CERT_EXPECT_DB_DATABASE') ?: '');
        if($expectedConnection==='' || $expectedDatabase==='') return;

        $actualConnection=(string)config('database.default');
        $actualDatabase=(string)config('database.connections.'.$actualConnection.'.database');
        self::assertSame($expectedConnection,$actualConnection,'PHPUnit/Laravel test process escaped the certification DB connection selected by scripts/certify-release.php.');

        $normalize=static fn(string $value):string=>str_replace('\\','/',$value);
        if($expectedConnection==='sqlite'){
            self::assertSame($normalize($expectedDatabase),$normalize($actualDatabase),'PHPUnit/Laravel test process escaped the isolated certification SQLite database.');
        }else{
            self::assertSame($expectedDatabase,$actualDatabase,'PHPUnit/Laravel test process escaped the isolated certification database name.');
        }
    }
}
