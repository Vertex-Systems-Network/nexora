<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class SecretPatternScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $findings = [];
        $rules = [
            ['NEX-SEC-0001', FindingSeverity::Critical, '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/', 'Private key embedded in package source', 'Private cryptographic keys must never be distributed inside a Nexora package.', true],
            ['NEX-SEC-0002', FindingSeverity::Critical, '/\bAKIA[0-9A-Z]{16}\b/', 'AWS access key identifier detected', 'A credential-like AWS access key was embedded in package source.', true],
            ['NEX-SEC-0003', FindingSeverity::Critical, '/\bsk_live_[A-Za-z0-9]{16,}\b/', 'Live Stripe secret-like token detected', 'Live payment credentials must never ship inside package source.', true],
            ['NEX-SEC-0004', FindingSeverity::High, '/\bgh[opusr]_[A-Za-z0-9]{30,}\b/', 'GitHub token-like credential detected', 'A GitHub credential-like token was embedded in package source.', true],
            ['NEX-SEC-0010', FindingSeverity::Medium, '/\b(?:api[_-]?key|client[_-]?secret|password)\b\s*[:=]\s*[\'\"][^\'\"\r\n]{12,}[\'\"]/i', 'Hard-coded secret-like value detected', 'A credential-like value appears hard-coded. Move secrets to Nexora Vault and declare the required capability.', false],
        ];

        foreach ($rules as [$ruleId, $severity, $pattern, $title, $message, $hard]) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding($ruleId, $severity, 'secrets', $title, $message, $path, $line, $line, Excerpt::around($content, $line), $hard);
            }
        }

        return $findings;
    }
}
