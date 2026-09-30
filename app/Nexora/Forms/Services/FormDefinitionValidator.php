<?php

declare(strict_types=1);

namespace App\Nexora\Forms\Services;

use Illuminate\Validation\ValidationException;

final class FormDefinitionValidator
{
    /** @var list<string> */
    private const TYPES = ['text', 'email', 'textarea', 'number', 'select', 'checkbox', 'date'];

    /** @param array<int,mixed> $fields
     *  @return list<array<string,mixed>> */
    public function normalize(array $fields): array
    {
        if ($fields === [] || count($fields) > 50) {
            throw ValidationException::withMessages([
                'fields' => 'A form must contain between 1 and 50 fields.',
            ]);
        }

        $normalized = [];
        $keys = [];
        foreach (array_values($fields) as $index => $field) {
            if (! is_array($field)) {
                throw $this->fieldError($index, 'Field definition must be an object.');
            }

            $key = trim((string) ($field['key'] ?? ''));
            if (! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key)) {
                throw $this->fieldError($index, 'Field key must start with a letter and contain only lowercase letters, numbers and underscores.');
            }
            if (isset($keys[$key])) {
                throw $this->fieldError($index, 'Field keys must be unique within a form.');
            }
            $keys[$key] = true;

            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '' || mb_strlen($label) > 180) {
                throw $this->fieldError($index, 'Field label is required and may not exceed 180 characters.');
            }

            $type = (string) ($field['type'] ?? 'text');
            if (! in_array($type, self::TYPES, true)) {
                throw $this->fieldError($index, 'Unsupported field type.');
            }

            $maxLength = (int) ($field['max_length'] ?? ($type === 'textarea' ? 10000 : 255));
            $maxLength = max(1, min($maxLength, 20000));
            $options = $type === 'select' ? $this->normalizeOptions((array) ($field['options'] ?? []), $index) : [];

            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'placeholder' => mb_substr(trim((string) ($field['placeholder'] ?? '')), 0, 255),
                'help' => mb_substr(trim((string) ($field['help'] ?? '')), 0, 500),
                'max_length' => $maxLength,
                'options' => $options,
            ];
        }

        return $normalized;
    }

    /** @param array<int,mixed> $options
     *  @return list<array{value:string,label:string}> */
    private function normalizeOptions(array $options, int $fieldIndex): array
    {
        if ($options === [] || count($options) > 50) {
            throw $this->fieldError($fieldIndex, 'Select fields must define between 1 and 50 options.');
        }

        $normalized = [];
        $values = [];
        foreach (array_values($options) as $option) {
            if (! is_array($option)) {
                throw $this->fieldError($fieldIndex, 'Select options must be objects.');
            }
            $value = mb_substr(trim((string) ($option['value'] ?? '')), 0, 120);
            $label = mb_substr(trim((string) ($option['label'] ?? '')), 0, 180);
            if ($value === '' || $label === '' || isset($values[$value])) {
                throw $this->fieldError($fieldIndex, 'Select option values and labels are required and values must be unique.');
            }
            $values[$value] = true;
            $normalized[] = ['value' => $value, 'label' => $label];
        }

        return $normalized;
    }

    private function fieldError(int $index, string $message): ValidationException
    {
        return ValidationException::withMessages([
            'fields.'.($index + 1) => $message,
        ]);
    }
}
