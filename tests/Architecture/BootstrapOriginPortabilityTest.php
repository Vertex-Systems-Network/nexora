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
    public function explicit_container_environment_overrides_are_checked_without_exposing_secret_values(): void
    {
        $sample = "APP_ENV=local\nAPP_URL=https://nexora.test\nSESSION_SECURE_COOKIE=true\nNEXORA_ALLOW_INSECURE_HTTP=false\n";
        self::assertSame([], \nexoraBootstrapOriginErrors($sample, [
            'APP_URL' => 'http://127.0.0.1:8974',
            'SESSION_SECURE_COOKIE' => 'false',
        ]));
        self::assertNotSame([], \nexoraBootstrapOriginErrors($sample, [
            'APP_URL' => 'https://production.example.org',
            'SESSION_SECURE_COOKIE' => 'false',
        ]));
        self::assertNotSame([], \nexoraBootstrapOriginErrors($sample, [
            'APP_URL' => 'https://production.example.org',
            'APP_ENV' => 'production',
            'NEXORA_ALLOW_INSECURE_HTTP' => 'true',
        ]));
    }

    #[Test]
    public function protected_fallback_active_marker_selects_actual_environment_and_fails_closed(): void
    {
        $root = sys_get_temp_dir().'/nexora-preflight-'.bin2hex(random_bytes(8));
        $protected = $root.'/storage/app/nexora/environment';
        self::assertTrue(mkdir($protected, 0700, true));
        try {
            file_put_contents($root.'/.env', "APP_URL=https://nexora.test\nSESSION_SECURE_COOKIE=true\n");
            file_put_contents($protected.'/.env', "APP_ENV=production\nAPP_URL=https://host.example.org\nSESSION_SECURE_COOKIE=true\n");
            self::assertSame($root.'/.env', \nexoraBootstrapEffectiveEnvironmentFile($root));
            file_put_contents($protected.'/active', 'fallback');
            self::assertSame($protected.'/.env', \nexoraBootstrapEffectiveEnvironmentFile($root));
            $effective = (string) file_get_contents(\nexoraBootstrapEffectiveEnvironmentFile($root));
            self::assertSame([], \nexoraBootstrapOriginErrors($effective));

            file_put_contents($protected.'/active', 'root');
            self::assertSame($root.'/.env', \nexoraBootstrapEffectiveEnvironmentFile($root));
            file_put_contents($protected.'/active', 'other');
            try {
                \nexoraBootstrapEffectiveEnvironmentFile($root);
                self::fail('Invalid environment activation must fail closed');
            } catch (\RuntimeException $e) {
                self::assertSame('The protected environment active marker is invalid.', $e->getMessage());
            }
            file_put_contents($protected.'/active', 'fallback');
            unlink($protected.'/.env');
            try {
                \nexoraBootstrapEffectiveEnvironmentFile($root);
                self::fail('Missing active fallback must not use a stale root environment');
            } catch (\RuntimeException $e) {
                self::assertSame('The active protected fallback environment is missing or unreadable.', $e->getMessage());
            }
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function ipv6_loopback_and_valid_hostname_rules_are_checked(): void
    {
        $base = "APP_ENV=local\nAPP_URL=http://127.0.0.1:8000\nSESSION_SECURE_COOKIE=false\n";
        $cases = [
            'IPv6 loopback with port' => ['http://[::1]:8000', true],
            'IPv6 loopback without port' => ['http://[::1]', true],
            'invalid zero port' => ['http://127.0.0.1:0', false],
            'missing port number' => ['http://127.0.0.1:', false],
            'invalid hostname with empty label' => ['http://bad..example.org', false],
            'invalid hostname leading hyphen' => ['http://-bad.example.org', false],
            'valid HTTPS IPv6 deployment' => ['https://[2001:db8::42]:8443', false],
        ];
        foreach ($cases as $description => [$url, $accepted]) {
            $candidate = str_replace('http://127.0.0.1:8000', $url, $base);
            self::assertSame($accepted, \nexoraBootstrapOriginErrors($candidate) === [], $description);
        }
        self::assertSame([], \nexoraBootstrapOriginErrors("APP_ENV=production\nAPP_URL=https://[2001:db8::42]:8443\nSESSION_SECURE_COOKIE=true\n"));
    }

    #[Test]
    public function bootstrap_rejects_unspecified_listen_addresses_and_unclassified_insecure_http(): void
    {
        $sample = "APP_ENV=local\nAPP_URL=https://site.example.org\nSESSION_SECURE_COOKIE=true\nNEXORA_ALLOW_INSECURE_HTTP=false\n";
        $cases = [
            'wildcard IPv4 is a bind address, not an origin' => ['https://0.0.0.0', 'local', 'true', false],
            'IPv6 wildcard is a bind address, not an origin' => ['https://[::]', 'local', 'true', false],
            'HTTPS browser loopback remains supported' => ['https://127.0.0.1', 'local', 'true', true],
            'nonloopback HTTP without environment classification' => ['http://preview.example.org', '', 'false', false],
            'nonloopback HTTP with explicit local classification' => ['http://preview.example.org', 'local', 'false', true],
            'nonloopback HTTP with explicit staging classification' => ['http://preview.example.org', 'staging', 'false', true],
            'nonloopback HTTP in production always rejected' => ['http://preview.example.org', 'production', 'false', false],
        ];
        foreach ($cases as $label => [$url, $appEnv, $secureCookie, $expected]) {
            $config = str_replace(
                ['APP_ENV=local', 'APP_URL=https://site.example.org', 'SESSION_SECURE_COOKIE=true', 'NEXORA_ALLOW_INSECURE_HTTP=false'],
                ['APP_ENV='.$appEnv, 'APP_URL='.$url, 'SESSION_SECURE_COOKIE='.$secureCookie, 'NEXORA_ALLOW_INSECURE_HTTP=true'],
                $sample,
            );
            self::assertSame($expected, \nexoraBootstrapOriginErrors($config) === [], $label);
        }
    }

    #[Test]
    public function cookie_domain_must_match_the_actual_browser_origin(): void
    {
        $base = "APP_ENV=production\nAPP_URL=https://admin.example.org\nSESSION_SECURE_COOKIE=true\nSESSION_SAME_SITE=lax\n";
        $cases = [
            'host-only cookie default' => ['', true],
            'exact cookie domain' => ["SESSION_DOMAIN=admin.example.org\n", true],
            'parent cookie domain' => ["SESSION_DOMAIN=.example.org\n", true],
            'unrelated cookie domain' => ["SESSION_DOMAIN=other.example.org\n", false],
            'domain in different registrable site' => ["SESSION_DOMAIN=example.net\n", false],
            'single-label public suffix' => ["SESSION_DOMAIN=org\n", false],
            'malformed empty label' => ["SESSION_DOMAIN=example..org\n", false],
            'domain is a URL' => ["SESSION_DOMAIN=https://example.org\n", false],
        ];
        foreach ($cases as $name => [$suffix, $expected]) {
            self::assertSame($expected, \nexoraBootstrapOriginErrors($base.$suffix) === [], $name);
        }
        self::assertNotSame([], \nexoraBootstrapOriginErrors(
            "APP_ENV=local\nAPP_URL=http://127.0.0.1:8000\nSESSION_SECURE_COOKIE=false\nSESSION_DOMAIN=example.org\n",
        ));
        self::assertNotSame([], \nexoraBootstrapOriginErrors(
            "APP_ENV=local\nAPP_URL=http://localhost:8000\nSESSION_SECURE_COOKIE=false\nSESSION_DOMAIN=localhost\n",
        ));
    }

    #[Test]
    public function samesite_and_partitioned_cookie_settings_must_be_usable_by_the_browser(): void
    {
        $https = "APP_ENV=production\nAPP_URL=https://admin.example.org\nSESSION_SECURE_COOKIE=true\n";
        $http = "APP_ENV=local\nAPP_URL=http://127.0.0.1:8000\nSESSION_SECURE_COOKIE=false\n";
        $cases = [
            'default SameSite policy' => [$https, true],
            'SameSite strict over HTTPS' => [$https."SESSION_SAME_SITE=strict\n", true],
            'SameSite none over HTTPS' => [$https."SESSION_SAME_SITE=none\n", true],
            'unexpected SameSite setting' => [$https."SESSION_SAME_SITE=invalid\n", false],
            'SameSite none over insecure HTTP' => [$http."SESSION_SAME_SITE=none\n", false],
            'partitioned cookie over HTTPS' => [$https."SESSION_PARTITIONED_COOKIE=true\n", true],
            'partitioned cookie over insecure HTTP' => [$http."SESSION_PARTITIONED_COOKIE=true\n", false],
            'invalid partitioned cookie setting' => [$https."SESSION_PARTITIONED_COOKIE=maybe\n", false],
        ];
        foreach ($cases as $name => [$config, $expected]) {
            self::assertSame($expected, \nexoraBootstrapOriginErrors($config) === [], $name);
        }
    }

    #[Test]
    public function process_supplied_cookie_domain_and_policy_are_validated_as_effective_values(): void
    {
        $base = "APP_ENV=production\nAPP_URL=https://admin.example.org\nSESSION_SECURE_COOKIE=true\nSESSION_DOMAIN=example.org\nSESSION_SAME_SITE=lax\nSESSION_PARTITIONED_COOKIE=false\n";
        self::assertNotSame([], \nexoraBootstrapOriginErrors($base, ['SESSION_DOMAIN' => 'wrong.example.net']));
        self::assertNotSame([], \nexoraBootstrapOriginErrors($base, ['SESSION_SAME_SITE' => 'not-valid']));
        self::assertNotSame([], \nexoraBootstrapOriginErrors($base, ['SESSION_PARTITIONED_COOKIE' => 'nonsense']));
        self::assertSame([], \nexoraBootstrapOriginErrors($base, ['SESSION_DOMAIN' => '.example.org']));
    }

    #[Test]
    public function non_loopback_insecure_http_requires_an_explicit_known_development_mode(): void
    {
        $base = "APP_URL=http://preview.example.org\nSESSION_SECURE_COOKIE=false\nNEXORA_ALLOW_INSECURE_HTTP=true\n";
        foreach (['prod', 'Production', 'release', 'unknown', ''] as $mode) {
            self::assertNotSame([], \nexoraBootstrapOriginErrors("APP_ENV={$mode}\n".$base), "mode ".$mode);
        }
        foreach (['local', 'development', 'testing', 'staging', 'preview'] as $mode) {
            self::assertSame([], \nexoraBootstrapOriginErrors("APP_ENV={$mode}\n".$base), "mode ".$mode);
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
