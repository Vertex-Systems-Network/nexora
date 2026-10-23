<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ARCH-BOUNDARY-100: the dependency direction in ARCHITECTURE.md ("Layer direction") is
 * Http → Nexora domain → Contracts/Models, never the reverse.
 */
final class LayerDirectionArchitectureTest extends TestCase
{
    /** App\Http with one or two backslashes (escaped strings) and a word boundary (namespace aliases). */
    private const HTTP = '/(?<![A-Za-z0-9_])App\\\\{1,2}Http\\b/';

    /** A service namespace, also inside grouped use statements: App\Nexora\X\Services\… or App\Nexora\X\{Services\…}. */
    private const SERVICES = '/(?<![A-Za-z0-9_])App\\\\{1,2}Nexora\\\\{1,2}[A-Za-z]+\\\\{1,2}\\{?Services\\b/';

    /** Domain files allowed to name App\Http classes, with the reason. */
    private const DOMAIN_HTTP_REFERENCES = [
        'app/Nexora/Installation/SourceActivationIdentity.php' => 'source-integrity inventory of loaded installer classes; names the controller, does not call it',
    ];

    /** Models allowed to import a domain service, with the reason. */
    private const MODEL_SERVICE_IMPORTS = [
        'App\\Nexora\\Enterprise\\Services\\TenantContext' => 'tenant scoping applied by tenant-owned models',
    ];

    public function test_domain_code_does_not_depend_on_the_http_layer(): void
    {
        $violations = [];
        foreach ($this->phpFiles('app/Nexora') as $relative => $source) {
            if (isset(self::DOMAIN_HTTP_REFERENCES[$relative])) {
                continue;
            }
            if (preg_match(self::HTTP, $this->code($source)) === 1) {
                $violations[] = $relative;
            }
        }

        self::assertSame([], $violations, 'Domain code must not import or name App\\Http classes; move shared names into App\\Nexora (e.g. App\\Nexora\\Http\\RequestContext).');
    }

    public function test_contracts_do_not_depend_on_services_or_http(): void
    {
        $violations = [];
        foreach ($this->phpFiles('app/Nexora') as $relative => $source) {
            if (! str_contains($relative, '/Contracts/')) {
                continue;
            }
            $code = $this->code($source);
            if (preg_match(self::HTTP, $code) === 1 || preg_match(self::SERVICES, $code) === 1) {
                $violations[] = $relative;
            }
        }

        self::assertSame([], $violations, 'Contracts must not depend on service implementations or the HTTP layer.');
    }

    public function test_models_do_not_depend_on_http_and_only_on_allowlisted_services(): void
    {
        $violations = [];
        foreach ($this->phpFiles('app/Models') as $relative => $source) {
            if (preg_match(self::HTTP, $this->code($source)) === 1) {
                $violations[] = $relative.' imports App\\Http';
            }
            preg_match_all('/^use (App\\\\Nexora\\\\[A-Za-z]+\\\\Services\\\\[A-Za-z\\\\]+);/m', $source, $matches);
            foreach ($matches[1] as $service) {
                if (! isset(self::MODEL_SERVICE_IMPORTS[$service])) {
                    $violations[] = $relative.' imports '.$service;
                }
            }
        }

        self::assertSame([], $violations, 'Models must not depend on the HTTP layer or on domain services beyond the allowlist.');
    }

    public function test_allowlists_have_no_stale_entries(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (array_keys(self::DOMAIN_HTTP_REFERENCES) as $file) {
            self::assertFileExists($root.'/'.$file);
            self::assertMatchesRegularExpression('/\\\\App\\\\Http\\\\/', (string) file_get_contents($root.'/'.$file), "Stale allowlist entry {$file}.");
        }
        $models = implode("\n", $this->phpFiles('app/Models'));
        foreach (array_keys(self::MODEL_SERVICE_IMPORTS) as $class) {
            self::assertTrue(class_exists($class), "Stale allowlist entry {$class}.");
            self::assertStringContainsString('use '.$class.';', $models, "Allowlist entry {$class} is no longer imported by any model.");
        }
    }

    /** @return array<string,string> relative path => source */
    private function phpFiles(string $dir): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[substr($file->getPathname(), strlen($root) + 1)] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }

    /** Source without comments, so prose and docblocks naming a layer are not counted as dependencies. */
    private function code(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }
}
