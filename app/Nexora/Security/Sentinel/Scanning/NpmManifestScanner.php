<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;

final class NpmManifestScanner
{
    /** @return list<SecurityFinding> */
    public function scan(?string $contents): array
    {
        if ($contents === null) {
            return [];
        }

        try {
            $package = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return [new SecurityFinding('NEX-NPM-0001', FindingSeverity::High, 'supply-chain', 'package.json is invalid JSON', $exception->getMessage(), 'package.json', hardBlock: true)];
        }

        if (! is_array($package)) {
            return [new SecurityFinding('NEX-NPM-0002', FindingSeverity::High, 'supply-chain', 'package.json must be an object', 'Sentinel could not interpret the frontend dependency manifest.', 'package.json', hardBlock: true)];
        }

        $findings = [];
        $scripts = $package['scripts'] ?? [];
        if (is_array($scripts)) {
            foreach (['preinstall', 'install', 'postinstall', 'prepublish', 'prepare'] as $hook) {
                if (array_key_exists($hook, $scripts)) {
                    $findings[] = new SecurityFinding(
                        'NEX-NPM-0010', FindingSeverity::High, 'supply-chain', 'npm lifecycle script detected',
                        "package.json defines [{$hook}]. Runtime package activation must never execute npm lifecycle hooks.",
                        'package.json', hardBlock: true, metadata: ['hook' => $hook],
                    );
                }
            }
        }

        return $findings;
    }
}
