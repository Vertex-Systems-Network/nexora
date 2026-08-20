<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class MigrationPolicyScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $normalized = str_replace('\\', '/', strtolower($path));
        if (! str_contains($normalized, '/migrations/') && ! str_starts_with($normalized, 'migrations/') && ! str_starts_with($normalized, 'database/migrations/')) {
            return [];
        }

        $findings = [];

        if (preg_match_all('/Schema\s*::\s*(create|table|drop|dropIfExists|rename)\s*\(\s*[\'\"]([^\'\"]+)[\'\"]/i', $content, $matches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($matches[0] as $index => $match) {
                $operation = strtolower((string) $matches[1][$index][0]);
                $table = strtolower((string) $matches[2][$index][0]);
                if ($table !== 'users' && ! str_starts_with($table, 'nx_')) {
                    continue;
                }
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding(
                    'NEX-DB-0001', FindingSeverity::Critical, 'database', 'Package migration touches a Nexora core table',
                    "Migration operation [{$operation}] targets protected core table [{$table}]. Third-party packages may not mutate Nexora-owned schemas.",
                    $path, $line, $line, Excerpt::around($content, $line), true, ['operation' => $operation, 'table' => $table],
                );
            }
        }

        $sqlRules = [
            ['NEX-DB-0010', '/\bDROP\s+DATABASE\b/i', 'DROP DATABASE statement detected'],
            ['NEX-DB-0011', '/\b(?:CREATE|DROP)\s+TRIGGER\b/i', 'Database trigger manipulation detected'],
            ['NEX-DB-0012', '/\b(?:CREATE|DROP)\s+(?:PROCEDURE|FUNCTION)\b/i', 'Stored routine manipulation detected'],
            ['NEX-DB-0013', '/\bGRANT\s+.+\s+ON\b/i', 'Database privilege grant detected'],
            ['NEX-DB-0014', '/\bTRUNCATE\s+(?:TABLE\s+)?(?:users|nx_[A-Za-z0-9_]+)/i', 'Core table truncation detected'],
            ['NEX-DB-0015', '/\bDROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?(?:users|nx_[A-Za-z0-9_]+)/i', 'Core table drop detected'],
        ];
        foreach ($sqlRules as [$ruleId, $pattern, $title]) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding($ruleId, FindingSeverity::Critical, 'database', $title, 'Third-party package migrations may not perform privileged or destructive database operations against the platform.', $path, $line, $line, Excerpt::around($content, $line), true);
            }
        }

        return $findings;
    }
}
