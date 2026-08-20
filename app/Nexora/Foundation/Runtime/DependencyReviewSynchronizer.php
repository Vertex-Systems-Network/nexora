<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use App\Nexora\Installation\InstallationState;
use RuntimeException;

final class DependencyReviewSynchronizer
{
    public function __construct(
        private readonly InstallationState $installation,
        private readonly ReviewedDependencyState $dependencies,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly AtomicFileWriter $files,
    ) {}

    /** @return array<string,mixed> */
    public function sync(string $operator): array
    {
        $operator = trim($operator);
        $this->assertOperator($operator);

        $installed = $this->installation->metadata();
        if (! is_array($installed)) {
            throw new RuntimeException('Nexora must be installed before dependency review provenance can be synchronized.');
        }

        $state = $this->dependencies->inspect();
        if (($state['status'] ?? 'fail') !== 'pass') {
            $errors = implode('; ', (array) ($state['errors'] ?? []));
            throw new RuntimeException('Current dependency locks are not formally reviewed: '.$errors);
        }

        $installedFingerprint = strtolower(trim((string) ($installed['runtime_dependency_fingerprint'] ?? '')));
        $currentFingerprint = strtolower(trim((string) ($state['fingerprint'] ?? '')));
        if ($installedFingerprint === '' || $currentFingerprint === '' || ! hash_equals($installedFingerprint, $currentFingerprint)) {
            throw new RuntimeException(
                'Reviewed dependency fingerprint does not match the installed dependency identity. '
                .'Use the dependency reconciliation workflow if the lockfiles changed.',
            );
        }

        $drift = $this->deployment->installedDriftAssessment();
        if (($drift['generation_changed'] ?? false) === true) {
            throw new RuntimeException(
                'Dependency review provenance cannot be synchronized while deployment generation drift exists. '
                .'Use the dependency reconciliation or normal upgrade workflow first.',
            );
        }

        $review = is_array($state['review'] ?? null) ? $state['review'] : [];
        $reviewHash = $this->dependencies->reviewFileHash();
        if ($reviewHash === null) {
            throw new RuntimeException('Reviewed dependency attestation hash is unavailable.');
        }

        $now = now()->toIso8601String();
        $this->installation->updateMetadata([
            'dependency_trust_mode' => 'reviewed',
            'dependency_review_required' => false,
            'dependency_review_status' => 'reviewed',
            'reviewed_locks_sha256' => $reviewHash,
            'dependency_review_synced_at' => $now,
            'dependency_review_synced_by' => $operator,
            'dependency_reviewed_at' => $review['reviewed_at'] ?? null,
            'dependency_reviewer' => $review['reviewer'] ?? null,
        ]);

        $receipt = [
            'schema' => 1,
            'status' => 'synced',
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'runtime_dependency_fingerprint' => $currentFingerprint,
            'reviewed_locks_sha256' => $reviewHash,
            'reviewed_at' => $review['reviewed_at'] ?? null,
            'reviewer' => $review['reviewer'] ?? null,
            'operator' => $operator,
            'synced_at' => $now,
            'deployment_generation_changed' => false,
        ];
        $receipt['receipt_sha256'] = $this->fingerprint($receipt);
        $this->writeReceipt($receipt);

        return $receipt;
    }

    private function assertOperator(string $operator): void
    {
        if ($operator === '' || in_array(strtolower($operator), ['operator', 'operator-name', 'your name'], true)) {
            throw new RuntimeException('A real operator identity is required.');
        }
    }

    /** @param array<string,mixed> $receipt */
    private function writeReceipt(array $receipt): void
    {
        $path = storage_path('app/nexora/dependency-intake/dependency-review-sync.json');
        $payload = json_encode(
            $receipt,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        $this->files->write($path, $payload, 0755, 0600);
    }

    /** @param array<string,mixed> $payload */
    private function fingerprint(array $payload): string
    {
        ksort($payload, SORT_STRING);

        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
