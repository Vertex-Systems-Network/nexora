<?php

declare(strict_types=1);

namespace App\Nexora\Api\Services;

use App\Nexora\Api\Contracts\PublicApiContract;

final readonly class CorePublicApiContract implements PublicApiContract
{
    public function __construct(private ApiAbilityRegistry $abilities) {}

    public function version(): string
    {
        return 'v1';
    }

    public function abilities(): array
    {
        return $this->abilities->all();
    }

    public function resources(): array
    {
        return [
            [
                'name' => 'documents.index',
                'method' => 'GET',
                'path' => '/api/v1/documents',
                'ability' => ApiAbilityRegistry::DOCUMENTS_READ,
                'pagination' => 'cursor',
                'max_per_page' => 100,
            ],
            [
                'name' => 'documents.show',
                'method' => 'GET',
                'path' => '/api/v1/documents/{document}',
                'ability' => ApiAbilityRegistry::DOCUMENTS_READ,
                'pagination' => null,
                'max_per_page' => null,
            ],
        ];
    }
}
