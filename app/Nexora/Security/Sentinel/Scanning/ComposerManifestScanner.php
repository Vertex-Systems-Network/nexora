<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;

final class ComposerManifestScanner
{
    /** @return list<SecurityFinding> */
    public function scan(?string $contents): array
    {
        if ($contents === null) {
            return [];
        }

        try {
            $composer = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return [new SecurityFinding(
                'NEX-CMP-0001', FindingSeverity::High, 'dependencies', 'composer.json is invalid JSON',
                $exception->getMessage(), 'composer.json', hardBlock: true,
            )];
        }

        if (! is_array($composer)) {
            return [new SecurityFinding('NEX-CMP-0002', FindingSeverity::High, 'dependencies', 'composer.json must be an object', 'Sentinel could not interpret the package dependency manifest.', 'composer.json', hardBlock: true)];
        }

        $findings = [];
        $scripts = $composer['scripts'] ?? [];
        if (is_array($scripts)) {
            $dangerousHooks = [
                'pre-install-cmd', 'post-install-cmd', 'pre-update-cmd', 'post-update-cmd',
                'pre-autoload-dump', 'post-autoload-dump', 'post-root-package-install', 'post-create-project-cmd',
            ];
            foreach ($dangerousHooks as $hook) {
                if (array_key_exists($hook, $scripts)) {
                    $findings[] = new SecurityFinding(
                        'NEX-CMP-0010', FindingSeverity::High, 'supply-chain', 'Composer lifecycle script detected',
                        "Package defines [{$hook}]. Nexora extensions must not execute arbitrary Composer lifecycle scripts during installation or activation.",
                        'composer.json', hardBlock: true, metadata: ['hook' => $hook],
                    );
                }
            }
        }

        if (isset($composer['repositories']) && is_array($composer['repositories'])) {
            $findings[] = new SecurityFinding(
                'NEX-CMP-0011', FindingSeverity::Medium, 'supply-chain', 'Custom Composer repositories declared',
                'Custom dependency repositories can alter package resolution and must be reviewed by the Nexora build/publishing pipeline.',
                'composer.json',
            );
        }

        if (($composer['config']['allow-plugins'] ?? []) !== []) {
            $findings[] = new SecurityFinding(
                'NEX-CMP-0012', FindingSeverity::High, 'supply-chain', 'Composer plugins requested',
                'Composer plugins execute code during dependency operations. Standard Nexora packages may not introduce Composer plugins at runtime.',
                'composer.json', hardBlock: true,
            );
        }

        return $findings;
    }
}
