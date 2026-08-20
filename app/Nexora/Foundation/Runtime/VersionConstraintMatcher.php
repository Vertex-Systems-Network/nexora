<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

final class VersionConstraintMatcher
{
    public function matches(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return true;
        }

        foreach (preg_split('/\s*\|\|\s*/', $constraint) ?: [] as $alternative) {
            if ($this->matchesAndSet($version, trim($alternative))) {
                return true;
            }
        }

        return false;
    }

    private function matchesAndSet(string $version, string $constraint): bool
    {
        $parts = preg_split('/\s*,\s*|\s+(?=[<>~=^])/', $constraint) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (! $this->matchesSingle($version, $part)) {
                return false;
            }
        }

        return true;
    }

    private function matchesSingle(string $version, string $constraint): bool
    {
        if (preg_match('/^(>=|<=|>|<|=)?\s*(\d+(?:\.\d+){0,2})$/', $constraint, $matches)) {
            return version_compare($version, $this->normalize($matches[2]), $matches[1] ?: '=');
        }

        if (preg_match('/^\^(\d+(?:\.\d+){0,2})$/', $constraint, $matches)) {
            $minimum = $this->normalize($matches[1]);
            [$major, $minor] = array_map('intval', array_slice(explode('.', $minimum), 0, 2));
            $upper = $major > 0 ? ($major + 1).'.0.0' : '0.'.($minor + 1).'.0';

            return version_compare($version, $minimum, '>=') && version_compare($version, $upper, '<');
        }

        if (preg_match('/^~(\d+(?:\.\d+){0,2})$/', $constraint, $matches)) {
            $minimum = $this->normalize($matches[1]);
            [$major, $minor] = array_map('intval', array_slice(explode('.', $minimum), 0, 2));
            $upper = $major.'.'.($minor + 1).'.0';

            return version_compare($version, $minimum, '>=') && version_compare($version, $upper, '<');
        }

        return false;
    }

    private function normalize(string $version): string
    {
        $parts = explode('.', $version);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        return implode('.', array_slice($parts, 0, 3));
    }
}
