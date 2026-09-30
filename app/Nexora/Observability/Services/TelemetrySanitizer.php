<?php

declare(strict_types=1);

namespace App\Nexora\Observability\Services;

final class TelemetrySanitizer
{
    private const SENSITIVE_KEY = '/(?:password|passwd|secret|token|authorization|cookie|session|credential|api[_-]?key|private[_-]?key|client[_-]?secret)/i';

    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    public function metadata(array $metadata): array
    {
        return $this->sanitizeArray($metadata, 0);
    }

    public function text(?string $value, int $max = 500): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, max(1, min(2000, $max)));
    }

    /** @param array<mixed> $value @return array<string,mixed> */
    private function sanitizeArray(array $value, int $depth): array
    {
        if ($depth >= 4) {
            return ['truncated' => true];
        }

        $out = [];
        $seen = 0;
        foreach ($value as $key => $item) {
            if ($seen >= 50) {
                $out['truncated'] = true;
                break;
            }
            $seen++;

            $name = mb_substr((string) $key, 0, 120);
            if ($name === '') {
                continue;
            }
            if (preg_match(self::SENSITIVE_KEY, $name) === 1) {
                $out[$name] = '[REDACTED]';
                continue;
            }

            if (is_array($item)) {
                $out[$name] = $this->sanitizeArray($item, $depth + 1);
            } elseif (is_string($item)) {
                $out[$name] = mb_substr($item, 0, 500);
            } elseif (is_int($item) || is_float($item) || is_bool($item) || $item === null) {
                $out[$name] = $item;
            } else {
                $out[$name] = '[UNSUPPORTED]';
            }
        }

        return $out;
    }
}
