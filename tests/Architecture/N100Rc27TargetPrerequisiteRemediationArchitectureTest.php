<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc27TargetPrerequisiteRemediationArchitectureTest extends TestCase
{
    #[Test]
    public function rc27_target_remediation_is_explicit_reversible_and_release_clean(): void
    {
        $root = base_path();
        require_once $root.'/scripts/lib/target-remediation-contracts.php';
        $result = \nexoraAnalyzeTargetRemediationContracts($root);
        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(3, $result['metrics']['wrappers']);
        self::assertSame(0, $result['metrics']['automatic_downloads']);
        self::assertSame(0, $result['metrics']['automatic_lock_acceptance']);
        self::assertGreaterThanOrEqual(2, $result['metrics']['php_ini_checksum_guards']);
    }
}
