<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class JavaScriptStaticScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $findings = [];
        $rules = [
            ['NEX-JS-0001', FindingSeverity::Critical, '/\beval\s*\(/', 'Dynamic JavaScript execution', 'eval() executes runtime-generated JavaScript and is blocked in standard Nexora packages.', true],
            ['NEX-JS-0002', FindingSeverity::Critical, '/\bnew\s+Function\s*\(/', 'Dynamic Function constructor', 'new Function() creates executable code from strings and is blocked in standard Nexora packages.', true],
            ['NEX-JS-0003', FindingSeverity::Critical, '/\bchild_process\s*\.\s*(?:exec|execSync|spawn|spawnSync|fork)\s*\(/', 'Node process execution primitive', 'child_process execution can escape the Nexora application boundary.', true],
            ['NEX-JS-0004', FindingSeverity::Critical, '/(?:require\s*\(\s*[\'\"]child_process[\'\"]\s*\)|from\s+[\'\"]child_process[\'\"])/', 'Node child_process import', 'child_process enables arbitrary process spawning and is not allowed in restricted packages.', true],
            ['NEX-JS-0010', FindingSeverity::High, '/\bprocess\.env\b/', 'Direct process environment access', 'Direct environment access may expose secrets. Packages should use declared Nexora secret contracts.', false],
            ['NEX-JS-0011', FindingSeverity::High, '/\bdocument\.cookie\b/', 'Direct cookie access', 'Direct cookie reads may expose session or application data and require security review.', false],
            ['NEX-JS-0012', FindingSeverity::Medium, '/\blocalStorage\b|\bsessionStorage\b/', 'Browser storage access', 'Browser storage access should be reviewed for token or sensitive-data handling.', false],
            ['NEX-JS-0020', FindingSeverity::High, '/\bfetch\s*\(/', 'Direct outbound browser request', 'Direct fetch() calls bypass the Nexora network/integration broker and destination policy.', false],
            ['NEX-JS-0021', FindingSeverity::High, '/\bnew\s+WebSocket\s*\(/', 'Direct WebSocket connection', 'Direct WebSocket connections bypass platform network destination policy.', false],
            ['NEX-JS-0022', FindingSeverity::High, '/\bXMLHttpRequest\b/', 'Direct XMLHttpRequest usage', 'Direct XHR calls bypass the Nexora network/integration broker.', false],
            ['NEX-JS-0030', FindingSeverity::High, '/\.innerHTML\s*=/', 'Raw innerHTML assignment', 'Direct innerHTML assignment can create DOM XSS when content is not strictly trusted and sanitized.', false],
            ['NEX-JS-0031', FindingSeverity::High, '/\bdocument\.write\s*\(/', 'document.write execution surface', 'document.write can inject executable markup into the page and is prohibited in restricted UI packages.', true],
            ['NEX-JS-0032', FindingSeverity::High, '/\bcreateElement\s*\(\s*[\'\"]script[\'\"]\s*\)/', 'Dynamic script element creation', 'Creating script elements dynamically can load or execute unreviewed code.', false],
        ];

        foreach ($rules as [$ruleId, $severity, $pattern, $title, $message, $hard]) {
            $result = preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
            if ($result === false || $result === 0) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $offset = (int) $match[1];
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                $findings[] = new SecurityFinding(
                    $ruleId,
                    $severity,
                    'javascript',
                    $title,
                    $message,
                    $path,
                    $line,
                    $line,
                    Excerpt::around($content, $line),
                    $hard,
                );
            }
        }

        if (preg_match_all('/[A-Za-z0-9+\/]{1200,}={0,2}/', $content, $encoded, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($encoded[0] as $match) {
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding(
                    'NEX-JS-0040', FindingSeverity::High, 'obfuscation', 'Large encoded JavaScript payload detected',
                    'A large base64-like string may conceal executable code or embedded data and requires review.',
                    $path, $line, $line, Excerpt::around($content, $line), false,
                );
            }
        }

        return $this->deduplicate($findings);
    }

    /** @param list<SecurityFinding> $findings @return list<SecurityFinding> */
    private function deduplicate(array $findings): array
    {
        $seen = [];
        $result = [];
        foreach ($findings as $finding) {
            $key = $finding->ruleId.'|'.($finding->filePath ?? '').'|'.($finding->lineStart ?? 0);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $finding;
        }

        return $result;
    }
}
