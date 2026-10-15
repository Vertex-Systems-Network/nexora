<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

require_once __DIR__.'/../../scripts/lib/bootstrap-origin-contracts.php';

final class BootstrapOriginPortabilityTest extends TestCase
{
    #[Test]
    public function origin_and_cookie_policy_is_host_independent_and_secure(): void
    {
        $base = "APP_ENV=local\nAPP_URL=https://site.example.org\nSESSION_SECURE_COOKIE=true\nNEXORA_ALLOW_INSECURE_HTTP=false\n";
        $cases = [
            'configured HTTPS deployment' => [$base, true],
            'leftover Laragon-style domain' => [str_replace('site.example.org', 'nexora.test', $base), false],
            'template example.com domain' => [str_replace('site.example.org', 'example.com', $base), false],
            'HTTPS with insecure cookie' => [str_replace('SESSION_SECURE_COOKIE=true', 'SESSION_SECURE_COOKIE=false', $base), false],
            'local loopback HTTP with explicit nonsecure cookie' => [str_replace(['https://site.example.org', 'SESSION_SECURE_COOKIE=true'], ['http://127.0.0.1:8000', 'SESSION_SECURE_COOKIE=false'], $base), true],
            'localhost HTTP with explicit nonsecure cookie' => [str_replace(['https://site.example.org', 'SESSION_SECURE_COOKIE=true'], ['http://localhost:8974', 'SESSION_SECURE_COOKIE=false'], $base), true],
            'loopback HTTP with Secure cookie' => [str_replace('https://site.example.org', 'http://127.0.0.1:8000', $base), false],
            'unapproved non-loopback HTTP' => [str_replace(['https://site.example.org', 'SESSION_SECURE_COOKIE=true'], ['http://otherhost.example.org', 'SESSION_SECURE_COOKIE=false'], $base), false],
            'explicit nonproduction HTTP' => [str_replace(['https://site.example.org', 'SESSION_SECURE_COOKIE=true', 'NEXORA_ALLOW_INSECURE_HTTP=false'], ['http://otherhost.example.org', 'SESSION_SECURE_COOKIE=false', 'NEXORA_ALLOW_INSECURE_HTTP=true'], $base), true],
            'production HTTP despite opt-in' => [str_replace(['APP_ENV=local', 'https://site.example.org', 'SESSION_SECURE_COOKIE=true', 'NEXORA_ALLOW_INSECURE_HTTP=false'], ['APP_ENV=production', 'http://otherhost.example.org', 'SESSION_SECURE_COOKIE=false', 'NEXORA_ALLOW_INSECURE_HTTP=true'], $base), false],
            'URL containing embedded credentials' => [str_replace('https://site.example.org', 'https://someone:secret@site.example.org', $base), false],
            'URL containing path' => [str_replace('https://site.example.org', 'https://site.example.org/public', $base), false],
            'URL containing query' => [str_replace('https://site.example.org', 'https://site.example.org?token=x', $base), false],
            'relative APP_URL' => [str_replace('https://site.example.org', '/admin', $base), false],
            'missing SESSION_SECURE_COOKIE' => [str_replace("SESSION_SECURE_COOKIE=true\n", '', $base), false],
        ];
        foreach ($cases as $case => [$config, $expected]) {
            self::assertSame($expected, \nexoraBootstrapOriginErrors($config) === [], $case);
        }
    }

    #[Test]
    public function every_portable_bootstrap_runs_the_origin_check_before_dependency_install(): void
    {
        $commands = [
            'scripts/bootstrap-installer.sh' => 'php scripts/bootstrap-origin-doctor.php',
            'scripts/bootstrap-installer.ps1' => 'php scripts/bootstrap-origin-doctor.php',
            'scripts/bootstrap-installer.bat' => 'php scripts\\bootstrap-origin-doctor.php',
        ];
        foreach ($commands as $script => $call) {
            $content = (string) file_get_contents(base_path($script));
            $index = strpos($content, $call);
            self::assertNotFalse($index, $script.' must invoke the bootstrap origin doctor');
            self::assertLessThan(strpos($content, 'composer install'), $index, $script.' must validate before dependency installation');
        }
    }
}
