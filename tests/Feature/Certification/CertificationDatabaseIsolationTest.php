<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use Tests\Support\AssertsCertificationDatabase;
use Tests\TestCase;

final class CertificationDatabaseIsolationTest extends TestCase
{
    use AssertsCertificationDatabase;

    public function test_phpunit_respects_the_certification_database_selected_by_the_runner(): void
    {
        $this->assertCertificationDatabaseBinding();
        self::assertTrue(true);
    }
}
