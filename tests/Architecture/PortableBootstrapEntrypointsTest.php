<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PortableBootstrapEntrypointsTest extends TestCase
{
    #[Test]
    public function powershell_bootstrap_preserves_existing_key_and_executes_each_install_step_once(): void
    {
        $script = (string) file_get_contents(base_path('scripts/bootstrap-installer.ps1'));

        self::assertSame(1, preg_match('/(?m)^if \(\$envContent -notmatch \'[^\r\n]+\'\) \{$/', $script), 'Password/APP_KEY detection must be a complete PowerShell conditional.');
        self::assertSame(1, substr_count($script, "php artisan key:generate\n"), 'Only generate missing application keys, once.');
        self::assertSame(1, substr_count($script, 'npm ci --no-audit --no-fund'));
        self::assertSame(1, substr_count($script, 'npm run build'));
        self::assertSame(1, substr_count($script, 'php artisan optimize:clear'));
        self::assertStringNotContainsString('key:generate --force', $script);
        self::assertStringNotContainsString('http://nexora.test', $script);
        self::assertStringContainsString('configured APP_URL', $script);
    }

    #[Test]
    public function shell_and_cmd_bootstrap_keep_existing_key_and_do_not_require_local_server_vendor(): void
    {
        $shell = (string) file_get_contents(base_path('scripts/bootstrap-installer.sh'));
        $cmd = (string) file_get_contents(base_path('scripts/bootstrap-installer.bat'));

        self::assertStringContainsString("if ! grep -Eq '^APP_KEY=.{8,}' .env", $shell);
        self::assertStringContainsString('findstr /B /R "APP_KEY=........" .env', $cmd);
        foreach ([$shell, $cmd] as $script) {
            self::assertStringNotContainsString('key:generate --force', $script);
            self::assertStringNotContainsString('http://nexora.test', $script);
            self::assertStringNotContainsString('laragon', strtolower($script));
        }
    }
}
