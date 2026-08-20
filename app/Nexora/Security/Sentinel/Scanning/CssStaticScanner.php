<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class CssStaticScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $findings = [];
        $rules = [
            ['NEX-CSS-0001', FindingSeverity::High, '/@import\s+(?:url\()?\s*[\'\"]?https?:\/\//i', 'Remote CSS import detected', 'Remote styles can change after review and create an external request outside Nexora asset policy.', false],
            ['NEX-CSS-0002', FindingSeverity::Medium, '/url\(\s*[\'\"]?https?:\/\//i', 'Remote CSS resource detected', 'External fonts/images/resources can track visitors and should be declared through platform asset/network policy.', false],
            ['NEX-CSS-0003', FindingSeverity::Critical, '/\bexpression\s*\(/i', 'Executable CSS expression detected', 'Legacy CSS expression syntax is executable and is not permitted in Nexora package assets.', true],
            ['NEX-CSS-0004', FindingSeverity::High, '/-moz-binding\s*:/i', 'Legacy executable binding detected', 'Executable browser-binding CSS is prohibited in package assets.', true],
        ];

        foreach ($rules as [$ruleId, $severity, $pattern, $title, $message, $hard]) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding($ruleId, $severity, 'css', $title, $message, $path, $line, $line, Excerpt::around($content, $line), $hard);
            }
        }

        return $findings;
    }
}
