<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class WebAssetScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $findings = [];
        $rules = [
            ['NEX-WEB-0001', FindingSeverity::Critical, '/<script\b/i', 'Executable script embedded in markup asset', 'Markup assets in standard Nexora packages may not contain inline script execution. Register JavaScript through the platform asset pipeline.', true],
            ['NEX-WEB-0002', FindingSeverity::Critical, '/\bon(?:load|error|click|mouseover|focus|animationstart)\s*=/i', 'Inline event-handler execution detected', 'Inline event handlers create an executable markup surface and are blocked in scanned SVG/HTML assets.', true],
            ['NEX-WEB-0003', FindingSeverity::Critical, '/javascript\s*:/i', 'javascript: URI detected', 'javascript: URLs can execute code when a user interacts with the asset.', true],
            ['NEX-WEB-0004', FindingSeverity::High, '/<iframe\b/i', 'Embedded iframe detected', 'Iframes can load external or privileged content and require explicit platform-managed embed policy.', false],
            ['NEX-WEB-0005', FindingSeverity::High, '/(?:href|src)\s*=\s*[\'\"]https?:\/\//i', 'External resource reference detected', 'External resources can exfiltrate data, fingerprint users or change after review. Use declared integration/network policy.', false],
        ];

        foreach ($rules as [$ruleId, $severity, $pattern, $title, $message, $hard]) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding($ruleId, $severity, 'web-asset', $title, $message, $path, $line, $line, Excerpt::around($content, $line), $hard);
            }
        }

        return $findings;
    }
}
