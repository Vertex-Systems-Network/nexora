<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use Illuminate\Support\Arr;

final class ConditionEvaluator
{
    /** @param array<int,array<string,mixed>> $conditions */
    public function passes(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            if (! $this->passesOne($condition, $context)) return false;
        }
        return true;
    }

    /** @param array<string,mixed> $condition */
    public function passesOne(array $condition, array $context): bool
    {
        $field = trim((string) ($condition['field'] ?? ''));
        $operator = (string) ($condition['operator'] ?? 'equals');
        $expected = $condition['value'] ?? null;
        $actual = $field === '' ? null : Arr::get($context, $field);

        return match ($operator) {
            'equals' => $this->string($actual) === $this->string($expected),
            'not_equals' => $this->string($actual) !== $this->string($expected),
            'contains' => str_contains(mb_strtolower($this->string($actual)), mb_strtolower($this->string($expected))),
            'not_contains' => ! str_contains(mb_strtolower($this->string($actual)), mb_strtolower($this->string($expected))),
            'exists' => $actual !== null && $actual !== '',
            'not_exists' => $actual === null || $actual === '',
            'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            default => false,
        };
    }

    private function string(mixed $value): string
    {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_scalar($value) || $value === null) return trim((string) $value);
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }
}
