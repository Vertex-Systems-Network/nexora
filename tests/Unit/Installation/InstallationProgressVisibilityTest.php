<?php

declare(strict_types=1);

namespace Tests\Unit\Installation;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationProgressVisibilityTest extends TestCase
{
    #[Test]
    public function failed_installation_progress_exposes_the_sanitized_blocker(): void
    {
        require_once base_path('scripts/lib/n1-installation-progress.php');

        $root = storage_path('framework/testing-installation-progress-root');
        $directory = $root.'/storage/app/nexora/installation-control';
        @mkdir($directory, 0775, true);
        $statePath = $directory.'/abcdefabcdefabcdefabcdef.json';

        file_put_contents($statePath, json_encode([
            'run_id' => 'abcdefabcdefabcdefabcdef',
            'active' => false,
            'status' => 'failed',
            'stage' => 'lock',
            'failure_stage' => 'lock',
            'failure_message' => 'Fresh-install dependency trust is not ready: composer.lock is unavailable.',
            'platform_version' => '1.0.0-rc.69',
            'installer_protocol' => 'v5.4',
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        try {
            $progress = \nexoraBuildInstallationProgress($root);

            self::assertSame(98, $progress['percent']);
            self::assertSame('failed', $progress['status']);
            self::assertSame('lock', $progress['stage']);
            self::assertStringContainsString('composer.lock', (string) $progress['blocker']);
            self::assertStringContainsString('Blocker:', \nexoraRenderInstallationProgress($progress));
        } finally {
            @unlink($statePath);
            @rmdir($directory);
            @rmdir(dirname($directory));
            @rmdir(dirname(dirname($directory)));
            @rmdir(dirname(dirname(dirname($directory))));
            @rmdir($root);
        }
    }
}
