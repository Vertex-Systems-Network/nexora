<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Nexora\Extensions\Data\ExtensionManifest;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use RuntimeException;

final readonly class ExtensionManifestValidator
{
    public function __construct(private VersionConstraintMatcher $versions) {}

    public function validate(array $manifest): ExtensionManifest
    {
        foreach (['id','name','type','version'] as $key) {
            if (! is_string($manifest[$key] ?? null) || trim((string) $manifest[$key]) === '') throw new RuntimeException("Extension manifest field [{$key}] is required.");
        }
        if (! in_array($manifest['type'], ['extension','app','integration','studio-pack'], true)) throw new RuntimeException('This package type belongs to a different Nexora lifecycle manager.');
        $requires = is_array($manifest['requires'] ?? null) ? $manifest['requires'] : [];
        $nexora = (string) ($requires['nexora'] ?? '*');
        if (! $this->versions->matches((string) config('nexora.version'), $nexora)) throw new RuntimeException("Package requires Nexora {$nexora}; this installation is ".config('nexora.version').'.');
        $runtime = is_array($manifest['runtime'] ?? null) ? $manifest['runtime'] : [];
        $runtimeMode = (string) ($runtime['mode'] ?? 'declarative');
        if (! in_array($runtimeMode, ['declarative','trusted-php'], true)) throw new RuntimeException('Unsupported extension runtime mode.');
        $capabilities = array_values(array_unique(array_filter((array) ($manifest['capabilities'] ?? []), 'is_string')));
        $dependencies = [];
        foreach ((array) ($manifest['dependencies'] ?? []) as $identifier => $constraint) {
            if (! is_string($identifier) || ! is_string($constraint) || $identifier === '') throw new RuntimeException('Extension dependencies must map package identifiers to version constraints.');
            $dependencies[$identifier] = $constraint;
        }
        $migrations = is_array($manifest['migrations'] ?? null) ? $manifest['migrations'] : [];
        $migrationPolicy = (string) ($migrations['policy'] ?? 'none');
        if (! in_array($migrationPolicy, ['none','forward-only'], true)) throw new RuntimeException('Extension migrations must use none or forward-only policy.');
        return new ExtensionManifest(
            identifier:(string) $manifest['id'], name:(string) $manifest['name'], type:(string) $manifest['type'], version:(string) $manifest['version'],
            description:(string) ($manifest['description'] ?? ''), nexoraConstraint:$nexora, runtimeMode:$runtimeMode, capabilities:$capabilities,
            dependencies:$dependencies, migrationPolicy:$migrationPolicy, schemaCompatibleRollback:(bool) ($migrations['schema_compatible_rollback'] ?? false), raw:$manifest,
        );
    }
}
