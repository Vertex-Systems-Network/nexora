<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeploymentBootstrapArchitectureTest extends TestCase
{
    #[Test]
    public function deployment_bootstrap_stays_on_the_canonical_url_and_exposes_only_controlled_tasks(): void
    {
        $bootstrapPath = public_path('nexora-bootstrap.php');
        $indexPath = public_path('index.php');

        self::assertFileExists($bootstrapPath);
        self::assertFileExists($indexPath);

        $bootstrap = (string) file_get_contents($bootstrapPath);
        $index = (string) file_get_contents($indexPath);

        self::assertStringContainsString("define('NEXORA_BOOTSTRAP_INTERNAL', true)", $index);
        self::assertStringContainsString("require __DIR__.'/nexora-bootstrap.php'", $index);
        self::assertStringNotContainsString("Location: /nexora-bootstrap.php", $index);

        self::assertStringContainsString("defined('NEXORA_BOOTSTRAP_INTERNAL')", $bootstrap);
        self::assertStringContainsString('install --no-interaction --prefer-dist --optimize-autoloader', $bootstrap);
        self::assertStringContainsString('run build', $bootstrap);
        self::assertStringContainsString('nxInstallPrivateComposer', $bootstrap);
        self::assertStringContainsString('nxInstallPrivateNode', $bootstrap);
        self::assertStringContainsString('nexora-process-environment.php', $bootstrap);
        self::assertStringContainsString('nxValidateToolCommand', $bootstrap);
        self::assertStringContainsString('COMPOSER_HOME', (string) file_get_contents(base_path('bootstrap/nexora-process-environment.php')));
        self::assertStringContainsString('https://composer.github.io/installer.sig', $bootstrap);
        self::assertStringContainsString('https://nodejs.org/download/release/latest-v24.x/', $bootstrap);
        self::assertStringContainsString('application/x-ndjson', $bootstrap);
        self::assertStringContainsString('nxStreamFixedCommand', $bootstrap);
        self::assertStringContainsString('nxStreamDeploymentTask', $bootstrap);
        self::assertStringContainsString('data-deployment-form', $bootstrap);
        self::assertStringContainsString('getReader()', $bootstrap);
        self::assertStringContainsString('deployment-cancel', $bootstrap);
        self::assertStringContainsString('cancel_stream', $bootstrap);
        self::assertStringContainsString('deployment_status', $bootstrap);
        self::assertStringContainsString('nxDeploymentCancellationRequested', $bootstrap);
        self::assertStringContainsString('active_run_id', $bootstrap);
        self::assertStringContainsString("'type' => 'heartbeat'", $bootstrap);
        self::assertStringContainsString('connection_aborted()', $bootstrap);
        self::assertDoesNotMatchRegularExpression('/proc_open\s*\(\s*\$_(?:POST|GET|REQUEST)/', $bootstrap);
        self::assertStringNotContainsString('name="command"', $bootstrap);
        self::assertStringNotContainsString('proc_open($command, $descriptors, $pipes, $cwd, null', $bootstrap);
    }
}
