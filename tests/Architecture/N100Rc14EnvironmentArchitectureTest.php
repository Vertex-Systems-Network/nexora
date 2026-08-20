<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc14EnvironmentArchitectureTest extends TestCase
{
    public function test_rc14_environment_and_config_cache_contract_is_fail_closed(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/environment-contracts.php';
        $result=\nexoraAnalyzeEnvironmentContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(0, $result['metrics']['runtime_env_calls']);
        self::assertGreaterThanOrEqual(40, $result['metrics']['production_template_keys']);

        $bootstrap=(string)file_get_contents($root.'/bootstrap/nexora-installer-bootstrap.php');
        $artisan=(string)file_get_contents($root.'/artisan');
        self::assertStringContainsString('Refusing to fall back to a different environment file',$bootstrap);
        self::assertStringContainsString('NEXORA_INSTALL_BOOTSTRAP_ERROR',$artisan);
    }
}
