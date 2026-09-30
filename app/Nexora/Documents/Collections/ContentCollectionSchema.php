<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Collections;

use Illuminate\Validation\ValidationException;

final readonly class ContentCollectionSchema
{
    private const TYPES = ['text', 'long-text', 'number', 'boolean', 'date', 'url'];
    private const MAX_FIELDS = 50;

    /** @param array<int,mixed> $fields @return list<array{key:string,label:string,type:string,required:bool}> */
    public function normalize(array $fields): array
    {
        if (count($fields) > self::MAX_FIELDS) {
            throw ValidationException::withMessages(['schema' => 'Content collections support up to 50 custom fields.']);
        }

        $normalized = [];
        $seen = [];
        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                throw ValidationException::withMessages(['schema' => 'Each collection field must be a structured field definition.']);
            }

            $key = strtolower(trim((string) ($field['key'] ?? '')));
            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);

            if (preg_match('/^[a-z][a-z0-9_]{0,62}$/', $key) !== 1) {
                throw ValidationException::withMessages(["schema.{$index}.key" => 'Field keys must start with a letter and use lowercase letters, numbers or underscores.']);
            }
            if ($label === '' || mb_strlen($label) > 100) {
                throw ValidationException::withMessages(["schema.{$index}.label" => 'Field labels are required and may not exceed 100 characters.']);
            }
            if (! in_array($type, self::TYPES, true)) {
                throw ValidationException::withMessages(["schema.{$index}.type" => 'Unsupported content collection field type.']);
            }
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(["schema.{$index}.key" => "Collection field key [{$key}] is duplicated."]);
            }
            $seen[$key] = true;
            $normalized[] = ['key' => $key, 'label' => mb_substr($label, 0, 100), 'type' => $type, 'required' => $required];
        }

        return $normalized;
    }

    /** @param array<string,mixed> $values @param array<int,mixed> $schema @return array<string,mixed> */
    public function normalizeEntry(array $values, array $schema): array
    {
        $fields = $this->normalize($schema);
        $allowed = array_column($fields, null, 'key');
        $unknown = array_diff(array_keys($values), array_keys($allowed));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['data' => 'Unknown collection fields: '.implode(', ', $unknown).'.']);
        }

        $normalized = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $values[$key] ?? null;
            $empty = $value === null || $value === '';
            if ($field['required'] && $empty) {
                throw ValidationException::withMessages(["data.{$key}" => "{$field['label']} is required."]);
            }
            if ($empty) {
                $normalized[$key] = null;
                continue;
            }

            $normalized[$key] = match ($field['type']) {
                'number' => $this->number($value, $key),
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
                'date' => $this->date($value, $key),
                'url' => $this->url($value, $key),
                'long-text' => mb_substr((string) $value, 0, 20_000),
                default => mb_substr((string) $value, 0, 2_000),
            };
        }

        return $normalized;
    }

    private function number(mixed $value, string $key): int|float
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages(["data.{$key}" => 'This collection field must be numeric.']);
        }
        return (float) $value == (int) $value ? (int) $value : (float) $value;
    }

    private function date(mixed $value, string $key): string
    {
        $candidate = (string) $value;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $candidate);
        if (! $date || $date->format('Y-m-d') !== $candidate) {
            throw ValidationException::withMessages(["data.{$key}" => 'This collection field must use YYYY-MM-DD date format.']);
        }
        return $candidate;
    }

    private function url(mixed $value, string $key): string
    {
        $candidate = trim((string) $value);
        if (filter_var($candidate, FILTER_VALIDATE_URL) === false || ! in_array(strtolower((string) parse_url($candidate, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw ValidationException::withMessages(["data.{$key}" => 'This collection field must be a valid HTTP or HTTPS URL.']);
        }
        return mb_substr($candidate, 0, 2_000);
    }
}
