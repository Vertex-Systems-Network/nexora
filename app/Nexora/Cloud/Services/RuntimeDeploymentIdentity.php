<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Installation\InstallationState;
use RuntimeException;
use Throwable;

final class RuntimeDeploymentIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    private ?array $memoizedIdentity = null;

    public function __construct(
        private readonly InstallationState $installation,
    ) {}

    /** @return array<string, mixed> */
    public function current(): array
    {
        if ($this->memoizedIdentity !== null) {
            return $this->memoizedIdentity;
        }

        $this->loadGenerationHelpers();

        $version = trim((string) config('nexora.version', 'unknown'));
        $release = $this->readJson(base_path('nexora-release.json'));

        if ($this->isReleaseManifestForVersion($release, $version)) {
            return $this->memoizedIdentity = $this->fromProductionManifest($release, $version);
        }

        $admission = $this->readJson(
            base_path('storage/app/nexora/update-trust/admission.json'),
        );

        if ($this->isAdmittedUpdateForVersion($admission, $version)) {
            return $this->memoizedIdentity = $this->fromAdmittedUpdate($admission, $version);
        }

        $installed = $this->installation->metadata();

        if ($this->isInstalledVersion($installed, $version)) {
            return $this->memoizedIdentity = $this->fromInstalledMetadata($installed, $version);
        }

        $sourceTree = $this->currentSourceTreeSha256();
        $materials = nexoraDeploymentMaterialsFromRoot(base_path(), $sourceTree);

        return $this->memoizedIdentity = $this->normalize(
            mode: 'source-fallback',
            materials: $materials,
            generation: nexoraDeploymentGeneration($materials),
        );
    }

    /**
     * Calculate the identity produced by the files that are on disk right now,
     * while keeping the installed source-tree provenance fixed. This is used by
     * the explicit dependency-reconciliation workflow; it never mutates state.
     *
     * @return array<string, mixed>
     */
    public function candidateForInstalledSource(): array
    {
        $this->loadGenerationHelpers();

        $installed = $this->installation->metadata();
        $sourceTree = is_array($installed)
            ? $this->sha($installed['release_source_tree_sha256'] ?? null)
            : null;

        $materials = nexoraDeploymentMaterialsFromRoot(base_path(), $sourceTree);

        return $this->normalize(
            mode: 'installed-candidate',
            materials: $materials,
            generation: nexoraDeploymentGeneration($materials),
        );
    }

    /** @return array<string, mixed> */
    public function installedDriftAssessment(): array
    {
        $installed = $this->installation->metadata();

        if (! is_array($installed)) {
            return [
                'status' => 'not-installed',
                'dependency_only' => false,
                'errors' => ['Nexora is not installed.'],
            ];
        }

        $candidate = $this->candidateForInstalledSource();
        $installedGeneration = $this->sha($installed['deployment_generation'] ?? null);
        $errors = [];

        $sourceMatches = $this->sourceTreeMatchesInstalled($installed, $errors);
        $frontendMatches = $this->frontendManifestMatchesInstalled($installed, $errors);
        $sessionMatches = $this->sessionSchemaMatchesInstalled($installed, $errors);
        $versionMatches = hash_equals(
            trim((string) ($installed['version'] ?? '')),
            trim((string) config('nexora.version', '')),
        );

        if (! $versionMatches) {
            $errors[] = 'Installed Nexora version differs from the running source version.';
        }

        $generationChanged = $installedGeneration === null
            || ! hash_equals($installedGeneration, (string) $candidate['generation']);

        $dependencyOnly = $versionMatches
            && $sourceMatches
            && $frontendMatches
            && $sessionMatches
            && $generationChanged;

        return [
            'status' => $errors === [] ? 'pass' : 'fail',
            'dependency_only' => $dependencyOnly,
            'generation_changed' => $generationChanged,
            'installed_generation' => $installedGeneration,
            'candidate_generation' => $candidate['generation'],
            'source_matches' => $sourceMatches,
            'frontend_manifest_matches' => $frontendMatches,
            'session_schema_matches' => $sessionMatches,
            'version_matches' => $versionMatches,
            'candidate' => $candidate,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{
     *     ok: bool,
     *     errors: list<string>,
     *     current: array<string, mixed>,
     *     source_tree_sha256: ?string,
     *     source_fallback_identity_refreshed: bool,
     *     initial_source_tree_sha256: ?string
     * }
     */
    public function deepVerify(): array
    {
        $errors = [];
        $current = $this->current();
        $sourceTree = null;
        $initialSourceTree = $this->sha($current['source_tree_sha256'] ?? null);
        $sourceFallbackIdentityRefreshed = false;

        require_once base_path('scripts/lib/source-attestation.php');

        try {
            $attestation = nexoraComputeSourceAttestation(base_path());
            $sourceTree = $this->sha($attestation['tree_sha256'] ?? null);
        } catch (Throwable $exception) {
            $errors[] = 'source attestation failed: '.$exception->getMessage();
        }

        $expectedSource = $this->sha($current['source_tree_sha256'] ?? null);
        if (
            $expectedSource !== null
            && $sourceTree !== null
            && ! hash_equals($expectedSource, $sourceTree)
        ) {
            // The installer and CLI must resolve the same source-fallback identity.
            // A long-lived web application container can legitimately hold a
            // source-fallback identity that was resolved before the final source
            // tree became stable. Refresh it once and require the refreshed
            // identity plus a second attestation to converge. Persisted release,
            // admitted-update and installed identities are never relaxed here.
            if (($current['mode'] ?? null) === 'source-fallback') {
                $this->forgetMemoizedIdentity();
                $refreshed = $this->current();
                $refreshedExpected = $this->sha($refreshed['source_tree_sha256'] ?? null);
                $secondSourceTree = null;

                try {
                    $secondAttestation = nexoraComputeSourceAttestation(base_path());
                    $secondSourceTree = $this->sha($secondAttestation['tree_sha256'] ?? null);
                } catch (Throwable $exception) {
                    $errors[] = 'source re-attestation failed: '.$exception->getMessage();
                }

                if (
                    $refreshedExpected !== null
                    && $secondSourceTree !== null
                    && hash_equals($refreshedExpected, $secondSourceTree)
                    && hash_equals($sourceTree, $secondSourceTree)
                ) {
                    $current = $refreshed;
                    $expectedSource = $refreshedExpected;
                    $sourceTree = $secondSourceTree;
                    $sourceFallbackIdentityRefreshed = true;
                } else {
                    $errors[] = 'deployed source tree does not match deployment identity'
                        .' [mode=source-fallback, expected='.($refreshedExpected ?? 'missing')
                        .', actual='.($secondSourceTree ?? $sourceTree ?? 'missing').']';
                }
            } else {
                $errors[] = 'deployed source tree does not match deployment identity'
                    .' [mode='.(string) ($current['mode'] ?? 'unknown')
                    .', expected='.$expectedSource.', actual='.$sourceTree.']';
            }
        }

        foreach ($this->materialPaths() as $key => $path) {
            $expectedHash = $current[$key] ?? null;

            if (! is_string($expectedHash) || $expectedHash === '') {
                continue;
            }

            $actualHash = $this->hashFile($path);
            if ($actualHash === null || ! hash_equals($expectedHash, $actualHash)) {
                $errors[] = "deployment material hash mismatch [{$key}]";
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'current' => $current,
            'source_tree_sha256' => $sourceTree,
            'initial_source_tree_sha256' => $initialSourceTree,
            'source_fallback_identity_refreshed' => $sourceFallbackIdentityRefreshed,
        ];
    }

    public function generation(): string
    {
        return (string) $this->current()['generation'];
    }

    public function assetVersion(): string
    {
        $identity = $this->current();

        return (string) ($identity['frontend_manifest_sha256'] ?? $identity['generation']);
    }

    public function cacheNamespace(): string
    {
        return 'g'.substr($this->generation(), 0, 16);
    }

    public function forgetMemoizedIdentity(): void
    {
        $this->memoizedIdentity = null;
    }

    /** @param array<string, mixed> $release */
    private function fromProductionManifest(array $release, string $version): array
    {
        $runtime = is_array($release['runtime_deployment'] ?? null)
            ? $release['runtime_deployment']
            : [];

        $materials = is_array($runtime['materials'] ?? null)
            ? $runtime['materials']
            : $this->releaseMaterials($release, $runtime, $version);

        $computed = nexoraDeploymentGeneration($materials);
        $declared = $this->sha($runtime['generation'] ?? null);

        if ($declared !== null && ! hash_equals($declared, $computed)) {
            throw new RuntimeException(
                'Production release deployment generation does not match its declared runtime materials.',
            );
        }

        return $this->normalize(
            mode: 'production-manifest',
            materials: $materials,
            generation: $computed,
            declaredGeneration: $declared,
        );
    }

    /** @param array<string, mixed> $admission */
    private function fromAdmittedUpdate(array $admission, string $version): array
    {
        $materials = $this->localMaterials(
            version: $version,
            sourceTree: $this->sha($admission['target_source_tree_sha256'] ?? null),
            frontendManifest: $this->sha($admission['target_frontend_manifest_sha256'] ?? null),
        );

        $computed = nexoraDeploymentGeneration($materials);
        $declared = $this->sha($admission['target_deployment_generation'] ?? null);

        if ($declared === null || ! hash_equals($declared, $computed)) {
            throw new RuntimeException(
                'Admitted update deployment generation does not match the local target materials/session schema.',
            );
        }

        return $this->normalize(
            mode: 'admitted-update',
            materials: $materials,
            generation: $computed,
            declaredGeneration: $declared,
        );
    }

    /** @param array<string, mixed> $installed */
    private function fromInstalledMetadata(array $installed, string $version): array
    {
        $materials = $this->localMaterials(
            version: $version,
            sourceTree: $this->sha($installed['release_source_tree_sha256'] ?? null),
            frontendManifest: $this->sha($installed['frontend_manifest_sha256'] ?? null),
        );

        $computed = nexoraDeploymentGeneration($materials);
        $declared = $this->sha($installed['deployment_generation'] ?? null);

        // Installed dependency locks are allowed to drift only into a quarantined
        // incompatible state. RuntimeVersionGuard will keep this node out of
        // traffic until the reviewed dependency-reconciliation command commits
        // the new generation. Do not throw here: operators need diagnostics.
        return $this->normalize(
            mode: 'installed-metadata',
            materials: $materials,
            generation: $computed,
            declaredGeneration: $declared,
        );
    }

    /** @return array<string, mixed> */
    private function localMaterials(
        string $version,
        ?string $sourceTree,
        ?string $frontendManifest,
    ): array {
        $materials = nexoraDeploymentMaterialsFromRoot(base_path(), $sourceTree);
        $materials['platform_version'] = $version;
        $materials['frontend_manifest_sha256'] = $frontendManifest
            ?? $this->hashFile(base_path('public/build/manifest.json'));

        return $materials;
    }

    /** @param array<string, mixed> $release @param array<string, mixed> $runtime @return array<string, mixed> */
    private function releaseMaterials(array $release, array $runtime, string $version): array
    {
        return [
            'platform_version' => $version,
            'source_tree_sha256' => $release['certification']['source_tree_sha256'] ?? null,
            'frontend_manifest_sha256' => $release['artifacts']['frontend_manifest_sha256'] ?? null,
            'composer_lock_sha256' => $release['artifacts']['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $release['artifacts']['package_lock_sha256'] ?? null,
            'runtime_policy_sha256' => $release['runtime_safety']['policy_sha256'] ?? null,
            'upgrade_policy_sha256' => $release['upgrade']['policy_sha256'] ?? null,
            'activation_policy_sha256' => $release['runtime_activation_contract']['policy_sha256'] ?? null,
            'engine_policy_sha256' => $release['runtime_engine_contract']['policy_sha256'] ?? null,
            'database_policy_sha256' => $release['database_data_plane_contract']['policy_sha256'] ?? null,
            'storage_policy_sha256' => $release['storage_data_plane_contract']['policy_sha256'] ?? null,
            'network_policy_sha256' => $release['service_data_plane_contract']['policy_sha256'] ?? null,
            'host_policy_sha256' => $release['host_clock_contract']['policy_sha256'] ?? null,
            'resource_policy_sha256' => $release['resource_envelope_contract']['policy_sha256'] ?? null,
            'policy_plane_sha256' => $release['runtime_policy_plane_contract']['policy_sha256'] ?? null,
            'process_policy_sha256' => $release['runtime_process_plane_contract']['policy_sha256'] ?? null,
            'framework_policy_sha256' => $release['framework_dependency_contract']['policy_sha256'] ?? null,
            'session_schema' => (int) ($runtime['session_schema']
                ?? config('nexora-runtime.deployment.session_schema', 1)),
        ];
    }

    /**
     * @param array<string, mixed> $materials
     * @return array<string, mixed>
     */
    private function normalize(
        string $mode,
        array $materials,
        string $generation,
        ?string $declaredGeneration = null,
    ): array {
        return [
            'mode' => $mode,
            'platform_version' => (string) ($materials['platform_version']
                ?? config('nexora.version', 'unknown')),
            'generation' => $generation,
            'declared_generation' => $declaredGeneration,
            'generation_matches_declared' => $declaredGeneration === null
                || hash_equals($declaredGeneration, $generation),
            'source_tree_sha256' => $this->sha($materials['source_tree_sha256'] ?? null),
            'frontend_manifest_sha256' => $this->sha($materials['frontend_manifest_sha256'] ?? null),
            'composer_lock_sha256' => $this->sha($materials['composer_lock_sha256'] ?? null),
            'package_lock_sha256' => $this->sha($materials['package_lock_sha256'] ?? null),
            'runtime_policy_sha256' => $this->sha($materials['runtime_policy_sha256'] ?? null),
            'upgrade_policy_sha256' => $this->sha($materials['upgrade_policy_sha256'] ?? null),
            'activation_policy_sha256' => $this->sha($materials['activation_policy_sha256'] ?? null),
            'engine_policy_sha256' => $this->sha($materials['engine_policy_sha256'] ?? null),
            'database_policy_sha256' => $this->sha($materials['database_policy_sha256'] ?? null),
            'storage_policy_sha256' => $this->sha($materials['storage_policy_sha256'] ?? null),
            'network_policy_sha256' => $this->sha($materials['network_policy_sha256'] ?? null),
            'host_policy_sha256' => $this->sha($materials['host_policy_sha256'] ?? null),
            'resource_policy_sha256' => $this->sha($materials['resource_policy_sha256'] ?? null),
            'policy_plane_sha256' => $this->sha($materials['policy_plane_sha256'] ?? null),
            'process_policy_sha256' => $this->sha($materials['process_policy_sha256'] ?? null),
            'framework_policy_sha256' => $this->sha($materials['framework_policy_sha256'] ?? null),
            'session_schema' => max(1, (int) ($materials['session_schema']
                ?? config('nexora-runtime.deployment.session_schema', 1))),
        ];
    }

    /** @param array<string, mixed>|null $release */
    private function isReleaseManifestForVersion(?array $release, string $version): bool
    {
        return is_array($release) && ($release['version'] ?? null) === $version;
    }

    /** @param array<string, mixed>|null $admission */
    private function isAdmittedUpdateForVersion(?array $admission, string $version): bool
    {
        return is_array($admission)
            && ($admission['target_version'] ?? null) === $version
            && $this->sha($admission['target_deployment_generation'] ?? null) !== null;
    }

    /** @param array<string, mixed>|null $installed */
    private function isInstalledVersion(?array $installed, string $version): bool
    {
        return is_array($installed) && ($installed['version'] ?? null) === $version;
    }

    /** @param array<string, mixed> $installed @param list<string> $errors */
    private function sourceTreeMatchesInstalled(array $installed, array &$errors): bool
    {
        $expected = $this->sha($installed['release_source_tree_sha256'] ?? null);

        if ($expected === null) {
            $errors[] = 'Installed source-tree provenance is missing.';
            return false;
        }

        require_once base_path('scripts/lib/source-attestation.php');

        try {
            $attestation = nexoraComputeSourceAttestation(base_path());
            $actual = $this->sha($attestation['tree_sha256'] ?? null);
        } catch (Throwable $exception) {
            $errors[] = 'Current source tree could not be attested: '.$exception->getMessage();
            return false;
        }

        if ($actual === null || ! hash_equals($expected, $actual)) {
            $errors[] = 'Source tree changed; this is not a dependency-only transition.';
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $installed @param list<string> $errors */
    private function frontendManifestMatchesInstalled(array $installed, array &$errors): bool
    {
        $expected = $this->sha($installed['frontend_manifest_sha256'] ?? null);

        if ($expected === null) {
            return true;
        }

        $actual = $this->hashFile(base_path('public/build/manifest.json'));
        if ($actual === null || ! hash_equals($expected, $actual)) {
            $errors[] = 'Frontend manifest changed; this is not a dependency-only transition.';
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $installed @param list<string> $errors */
    private function sessionSchemaMatchesInstalled(array $installed, array &$errors): bool
    {
        $installedSchema = max(1, (int) ($installed['session_schema'] ?? 1));
        $currentSchema = max(1, (int) config('nexora-runtime.deployment.session_schema', 1));

        if ($installedSchema !== $currentSchema) {
            $errors[] = 'Session schema changed; use the normal Nexora upgrade workflow.';
            return false;
        }

        return true;
    }

    /** @return array<string, string> */
    private function materialPaths(): array
    {
        return [
            'frontend_manifest_sha256' => base_path('public/build/manifest.json'),
            'composer_lock_sha256' => base_path('composer.lock'),
            'package_lock_sha256' => base_path('package-lock.json'),
            'runtime_policy_sha256' => base_path('config/nexora-runtime.php'),
            'upgrade_policy_sha256' => base_path('config/nexora-upgrade.php'),
            'activation_policy_sha256' => base_path('config/nexora-activation.php'),
            'engine_policy_sha256' => base_path('config/nexora-engine.php'),
            'database_policy_sha256' => base_path('config/nexora-database-runtime.php'),
            'storage_policy_sha256' => base_path('config/nexora-storage-runtime.php'),
            'network_policy_sha256' => base_path('config/nexora-network-runtime.php'),
            'host_policy_sha256' => base_path('config/nexora-host-runtime.php'),
            'resource_policy_sha256' => base_path('config/nexora-resource-runtime.php'),
            'policy_plane_sha256' => base_path('config/nexora-policy-runtime.php'),
            'process_policy_sha256' => base_path('config/nexora-process-runtime.php'),
            'framework_policy_sha256' => base_path('config/nexora-framework.php'),
        ];
    }

    /** @return array<string, mixed>|null */
    private function readJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                256,
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function sha(mixed $value): ?string
    {
        $hash = strtolower(trim((string) $value));

        return preg_match('/^[a-f0-9]{64}$/', $hash) === 1 ? $hash : null;
    }

    private function currentSourceTreeSha256(): string
    {
        require_once base_path('scripts/lib/source-attestation.php');

        $attestation = nexoraComputeSourceAttestation(base_path());
        $digest = strtolower(trim((string) ($attestation['tree_sha256'] ?? '')));

        if (preg_match('/^[a-f0-9]{64}$/', $digest) !== 1) {
            throw new RuntimeException('Unable to calculate a valid Nexora source-tree SHA-256 for deployment identity.');
        }

        return $digest;
    }

    private function hashFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private function loadGenerationHelpers(): void
    {
        require_once base_path('scripts/lib/deployment-generation.php');
    }
}
