<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V57InstallRuntimeHandoffArchitectureTest extends TestCase
{
    #[Test]
    public function source_bound_install_commit_and_runtime_handoff_remain_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-install-runtime-handoff-contracts.php');

        $result = \nexoraAnalyzeInstallRuntimeHandoffContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(1, $result['metrics']['source_tree_bound_to_deployment']);
        self::assertSame(4, $result['metrics']['strict_certification_planes_decoupled_from_request_admission']);
        self::assertSame(1, $result['metrics']['one_time_post_install_identity_finalization']);
        self::assertSame(0, $result['metrics']['target_denominator_change']);
    }
}
