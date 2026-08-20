<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\MigrationPolicyScanner;
use PHPUnit\Framework\TestCase;

final class MigrationPolicyScannerTest extends TestCase
{
    public function test_core_table_mutation_is_hard_blocked(): void
    {
        $source = <<<'PHP'
<?php
Schema::table('users', function ($table) {
    $table->string('backdoor')->nullable();
});
DB::statement('DROP TABLE nx_settings');
PHP;

        $findings = (new MigrationPolicyScanner())->scan('database/migrations/2026_08_15_000001_attack.php', $source);
        $rules = array_map(static fn ($finding): string => $finding->ruleId, $findings);

        self::assertContains('NEX-DB-0001', $rules);
        self::assertContains('NEX-DB-0015', $rules);
        self::assertTrue((bool) array_filter($findings, static fn ($finding): bool => $finding->hardBlock));
    }

    public function test_package_owned_table_is_not_rejected_by_core_table_policy(): void
    {
        $source = <<<'PHP'
<?php
Schema::create('ext_acme_reviews', function ($table) {
    $table->id();
});
PHP;

        self::assertSame([], (new MigrationPolicyScanner())->scan('database/migrations/2026_08_15_000001_create_reviews.php', $source));
    }

    public function test_non_migration_php_is_ignored(): void
    {
        $source = "<?php Schema::drop('users');\n";
        self::assertSame([], (new MigrationPolicyScanner())->scan('src/Example.php', $source));
    }
}
