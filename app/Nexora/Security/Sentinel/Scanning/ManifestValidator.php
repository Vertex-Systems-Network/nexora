<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;

final class ManifestValidator
{
    /** @return array{manifest:array<string,mixed>,findings:list<SecurityFinding>} */
    public function validate(?string $contents): array
    {
        if ($contents === null) {
            return ['manifest' => [], 'findings' => [new SecurityFinding(
                'NEX-MAN-0001', FindingSeverity::Critical, 'manifest', 'Package manifest is missing',
                'Every installable Nexora package must contain a root-level nexora.json manifest. Nothing may activate without a valid manifest.',
                'nexora.json', hardBlock: true,
            )]];
        }

        try {
            $manifest = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return ['manifest' => [], 'findings' => [new SecurityFinding(
                'NEX-MAN-0002', FindingSeverity::Critical, 'manifest', 'Package manifest is invalid JSON',
                $exception->getMessage(), 'nexora.json', hardBlock: true,
            )]];
        }

        if (! is_array($manifest)) {
            return ['manifest' => [], 'findings' => [new SecurityFinding(
                'NEX-MAN-0003', FindingSeverity::Critical, 'manifest', 'Package manifest must be an object',
                'nexora.json decoded to a non-object value.', 'nexora.json', hardBlock: true,
            )]];
        }

        $findings = [];
        foreach (['schema', 'id', 'name', 'type', 'version'] as $field) {
            if (! isset($manifest[$field]) || ! is_string($manifest[$field]) || trim($manifest[$field]) === '') {
                $findings[] = new SecurityFinding(
                    'NEX-MAN-0010', FindingSeverity::High, 'manifest', "Required manifest field [{$field}] is missing",
                    "The package must declare a non-empty string [{$field}].", 'nexora.json', hardBlock: true, metadata: ['field' => $field],
                );
            }
        }

        $id = (string) ($manifest['id'] ?? '');
        if ($id !== '' && preg_match('/^[a-z0-9](?:[a-z0-9._-]{1,126})[a-z0-9]$/', $id) !== 1) {
            $findings[] = new SecurityFinding('NEX-MAN-0011', FindingSeverity::High, 'manifest', 'Package identifier is invalid', 'Use a stable lowercase identifier containing letters, numbers, dots, underscores or hyphens.', 'nexora.json', hardBlock: true);
        }

        $version = (string) ($manifest['version'] ?? '');
        if ($version !== '' && preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version) !== 1) {
            $findings[] = new SecurityFinding('NEX-MAN-0012', FindingSeverity::High, 'manifest', 'Package version is not semantic', 'Use a semantic version such as 1.2.3 or 1.2.3-beta.1.', 'nexora.json', hardBlock: true);
        }

        $type = (string) ($manifest['type'] ?? '');
        $allowedTypes = (array) config('sentinel.package_types', []);
        if ($type !== '' && ! in_array($type, $allowedTypes, true)) {
            $findings[] = new SecurityFinding('NEX-MAN-0013', FindingSeverity::High, 'manifest', 'Unknown Nexora package type', "Package type [{$type}] is not supported by this platform.", 'nexora.json', hardBlock: true);
        }

        $capabilities = $manifest['capabilities'] ?? [];
        if (! is_array($capabilities) || array_filter($capabilities, static fn (mixed $value): bool => ! is_string($value)) !== []) {
            $findings[] = new SecurityFinding('NEX-MAN-0014', FindingSeverity::Medium, 'manifest', 'Capabilities declaration is malformed', 'Capabilities must be a JSON array of capability slug strings.', 'nexora.json');
        }

        return ['manifest' => $manifest, 'findings' => $findings];
    }
}
