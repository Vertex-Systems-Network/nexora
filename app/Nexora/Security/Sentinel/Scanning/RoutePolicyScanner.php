<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Support\Excerpt;

final class RoutePolicyScanner
{
    /** @return list<SecurityFinding> */
    public function scan(string $path, string $content): array
    {
        $normalized = str_replace('\\', '/', strtolower($path));
        if (! str_contains($normalized, '/routes/') && ! str_starts_with($normalized, 'routes/')) {
            return [];
        }

        $findings = [];
        if (preg_match_all('/Route\s*::\s*(?:get|post|put|patch|delete|options|any|match)\s*\(\s*[\'\"]([^\'\"]+)[\'\"]/i', $content, $matches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($matches[0] as $index => $match) {
                $uri = '/'.ltrim((string) $matches[1][$index][0], '/');
                if (! $this->isReserved($uri)) {
                    continue;
                }
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding(
                    'NEX-RTE-0001', FindingSeverity::Critical, 'routing', 'Package attempts to shadow a protected Nexora route',
                    "Route [{$uri}] overlaps a protected authentication/admin namespace. Packages must register routes through approved namespaced extension points.",
                    $path, $line, $line, Excerpt::around($content, $line), true, ['uri' => $uri],
                );
            }
        }

        if (preg_match_all('/->\s*name\s*\(\s*[\'\"](login|logout|register|verification\.[^\'\"]+|admin\.(?:users|roles|settings|system|security)[^\'\"]*)[\'\"]/i', $content, $names, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($names[0] as $index => $match) {
                $routeName = (string) $names[1][$index][0];
                $line = substr_count(substr($content, 0, (int) $match[1]), "\n") + 1;
                $findings[] = new SecurityFinding('NEX-RTE-0002', FindingSeverity::Critical, 'routing', 'Protected route name collision detected', "Package declares protected route name [{$routeName}].", $path, $line, $line, Excerpt::around($content, $line), true, ['route_name' => $routeName]);
            }
        }

        if (preg_match('/Route\s*::\s*prefix\s*\(\s*[\'\"]admin[\'\"]\s*\)/i', $content, $prefix, PREG_OFFSET_CAPTURE) === 1) {
            $line = substr_count(substr($content, 0, (int) $prefix[0][1]), "\n") + 1;
            $findings[] = new SecurityFinding('NEX-RTE-0003', FindingSeverity::High, 'routing', 'Raw admin route prefix detected', 'Package admin routes must be registered through Nexora Admin extension routing so authorization and namespacing are enforced.', $path, $line, $line, Excerpt::around($content, $line), false);
        }

        return $findings;
    }

    private function isReserved(string $uri): bool
    {
        $exact = ['/login', '/logout', '/register', '/forgot-password', '/reset-password', '/verify-email', '/admin'];
        if (in_array(rtrim($uri, '/'), $exact, true)) {
            return true;
        }

        foreach (['/admin/users', '/admin/roles', '/admin/settings', '/admin/system', '/admin/security'] as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
