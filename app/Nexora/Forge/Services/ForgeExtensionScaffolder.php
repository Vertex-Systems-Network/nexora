<?php

declare(strict_types=1);

namespace App\Nexora\Forge\Services;

use App\Nexora\Extensions\Services\ExtensionManifestValidator;
use App\Nexora\Foundation\Filesystem\PortablePath;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

final readonly class ForgeExtensionScaffolder
{
    private const MARKER = '.nexora-forge.json';
    private const TYPES = ['extension', 'app', 'integration', 'studio-pack'];

    public function __construct(private ExtensionManifestValidator $manifests) {}

    /**
     * @return array{identifier:string,name:string,type:string,workspace:string,target:string,files:list<string>,exists:bool,forge_owned:bool}
     */
    public function plan(string $identifier, ?string $name = null, string $type = 'extension'): array
    {
        $identifier = strtolower(trim($identifier));
        if (preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)+$/', $identifier) !== 1) {
            throw new InvalidArgumentException('Use a namespaced identifier such as vendor.extension.');
        }
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Type must be extension, app, integration or studio-pack.');
        }

        $name = trim((string) ($name ?: ucwords(str_replace(['.', '-', '_'], ' ', $identifier))));
        if ($name === '' || mb_strlen($name) > 160) {
            throw new InvalidArgumentException('Extension name must be between 1 and 160 characters.');
        }

        $workspace = base_path('extensions');
        if (is_link($workspace)) {
            throw new RuntimeException('Forge workspace may not be a symbolic link.');
        }
        $target = PortablePath::join($workspace, $identifier);
        PortablePath::assertNoExistingSymlinkTraversal($workspace, $target);

        $files = array_keys($this->files($identifier, $name, $type));
        sort($files, SORT_STRING);

        return [
            'identifier' => $identifier,
            'name' => $name,
            'type' => $type,
            'workspace' => $workspace,
            'target' => $target,
            'files' => array_values($files),
            'exists' => is_dir($target),
            'forge_owned' => $this->isForgeOwned($target, $identifier),
        ];
    }

    /** @return array{target:string,files:list<string>,created:bool,refreshed:bool} */
    public function create(string $identifier, ?string $name = null, string $type = 'extension', bool $force = false): array
    {
        $plan = $this->plan($identifier, $name, $type);
        $target = $plan['target'];

        if ($plan['exists'] && ! $force) {
            throw new RuntimeException('Extension source directory already exists. Re-run with --force only for a Forge-owned scaffold.');
        }
        if ($plan['exists'] && $force && ! $plan['forge_owned']) {
            throw new RuntimeException('Forge refuses to overwrite an existing directory that is not owned by the same Forge scaffold.');
        }

        File::ensureDirectoryExists($plan['workspace'], 0755, true);
        if (is_link($plan['workspace'])) {
            throw new RuntimeException('Forge workspace became a symbolic link during scaffold creation.');
        }
        File::ensureDirectoryExists($target, 0755, true);
        PortablePath::assertNoExistingSymlinkTraversal($plan['workspace'], $target);

        foreach ($this->files($plan['identifier'], $plan['name'], $plan['type']) as $relative => $content) {
            $destination = PortablePath::join($target, $relative);
            PortablePath::assertNoExistingSymlinkTraversal($target, $destination);
            File::ensureDirectoryExists(dirname($destination), 0755, true);
            if (is_link($destination)) {
                throw new RuntimeException("Forge refuses to overwrite symbolic link [{$relative}].");
            }
            File::put($destination, $content, true);
        }

        return [
            'target' => $target,
            'files' => $plan['files'],
            'created' => ! $plan['exists'],
            'refreshed' => $plan['exists'],
        ];
    }

    /** @return array<string,string> */
    private function files(string $identifier, string $name, string $type): array
    {
        $manifest = [
            'schema' => 'https://nexora.dev/schemas/package-v1.json',
            'id' => $identifier,
            'name' => $name,
            'type' => $type,
            'version' => '0.1.0',
            'description' => '',
            'requires' => ['nexora' => '>=0.34 <2.0'],
            'runtime' => ['mode' => 'declarative'],
            'capabilities' => [],
            'dependencies' => (object) [],
            'migrations' => ['policy' => 'none', 'schema_compatible_rollback' => false],
        ];
        $this->manifests->validate($manifest);

        $composer = [
            'name' => str_replace('.', '/', $identifier),
            'type' => 'nexora-extension',
            'require' => ['php' => '^8.3'],
        ];
        $marker = [
            'schema' => 'nexora.forge.scaffold.v1',
            'identifier' => $identifier,
            'managed_files' => [
                self::MARKER,
                'README.md',
                'composer.json',
                'database/migrations/.gitkeep',
                'nexora.json',
                'tests/.gitkeep',
            ],
        ];

        return [
            self::MARKER => $this->json($marker),
            'README.md' => "# {$name}\n\nForge source package for Nexora.\n\n- Add only the capabilities your package actually needs.\n- Build/sign the ZIP outside the runtime installation directory.\n- Upload the package through Sentinel; Forge does not install, trust, enable or bypass package review.\n",
            'composer.json' => $this->json($composer),
            'database/migrations/.gitkeep' => '',
            'nexora.json' => $this->json($manifest),
            'tests/.gitkeep' => '',
        ];
    }

    private function isForgeOwned(string $target, string $identifier): bool
    {
        if (! is_dir($target) || is_link($target)) {
            return false;
        }
        $markerPath = $target.DIRECTORY_SEPARATOR.self::MARKER;
        if (! is_file($markerPath) || is_link($markerPath)) {
            return false;
        }
        $decoded = json_decode((string) file_get_contents($markerPath), true);

        return is_array($decoded)
            && ($decoded['schema'] ?? null) === 'nexora.forge.scaffold.v1'
            && ($decoded['identifier'] ?? null) === $identifier;
    }

    /** @param array<string,mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    }
}
