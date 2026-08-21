<?php

declare(strict_types=1);

namespace App\Nexora\Api\Services;

use Illuminate\Validation\ValidationException;

final class ApiAbilityRegistry
{
    public const DOCUMENTS_READ = 'documents.read';

    /** @return list<array{slug:string,label:string,description:string}> */
    public function all(): array
    {
        return [[
            'slug' => self::DOCUMENTS_READ,
            'label' => 'Read documents',
            'description' => 'Read document metadata and structured content from the active token organization.',
        ]];
    }

    /** @param array<int,mixed> $abilities @return list<string> */
    public function normalize(array $abilities): array
    {
        $allowed = array_column($this->all(), 'slug');
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $ability): string => is_string($ability) ? trim($ability) : '',
            $abilities,
        ))));
        sort($normalized);

        if ($normalized === []) {
            throw ValidationException::withMessages(['abilities' => 'Choose at least one API ability.']);
        }

        foreach ($normalized as $ability) {
            if (! in_array($ability, $allowed, true)) {
                throw ValidationException::withMessages(['abilities' => 'The requested API ability is not supported.']);
            }
        }

        return $normalized;
    }
}
