<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Tests\Support\AssertsCertificationDatabase;
use Tests\TestCase;

final class CertificationDatabaseBindingCompatibilityTest extends TestCase
{
    use AssertsCertificationDatabase;

    public function test_matrix_phpunit_process_uses_the_exact_driver_database_selected_by_the_matrix(): void
    {
        $this->assertCertificationDatabaseBinding();
        self::assertTrue(true);
    }
}
