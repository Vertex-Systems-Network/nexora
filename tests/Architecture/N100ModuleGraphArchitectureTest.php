<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100ModuleGraphArchitectureTest extends TestCase
{
    public function test_release_candidate_has_a_static_module_graph_gate_and_correct_enterprise_identity_dependency(): void
    {
        $root = dirname(__DIR__, 2);
        $enterprise = (string) file_get_contents($root.'/app/Nexora/Modules/Core/EnterpriseModule.php');
        $runner = (string) file_get_contents($root.'/scripts/certify-release.php');
        $preflight = (string) file_get_contents($root.'/scripts/certification-preflight.php');
        $graph = (string) file_get_contents($root.'/scripts/lib/module-graph.php');

        self::assertStringContainsString("new ModuleDependency('nexora.identity-access','^0.5')", $enterprise);
        self::assertStringNotContainsString("new ModuleDependency('nexora.identity','^0.3')", $enterprise);
        self::assertFileExists($root.'/scripts/module-graph-verify.php');
        self::assertStringContainsString("'module-graph'", $runner);
        self::assertStringContainsString("modules.graph", $preflight);
        foreach (['requires missing module', 'depends on itself', 'Circular Nexora module dependency', 'Duplicate Nexora module identifier'] as $marker) {
            self::assertStringContainsString($marker, $graph);
        }
    }
}
