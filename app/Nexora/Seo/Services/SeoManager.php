<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Services;

use App\Models\Document;
use App\Nexora\Seo\Contracts\SeoManagerContract;
use App\Nexora\Seo\Contracts\SeoRepositoryContract;
use App\Nexora\Seo\Schema\SchemaGraphBuilder;

final readonly class SeoManager implements SeoManagerContract
{
    public function __construct(
        private SeoRepositoryContract $repository,
        private SeoMetadataFactory $metadata,
        private SchemaGraphBuilder $schema,
    ) {
    }

    public function documentPayload(Document $document, string $locale = 'en'): array
    {
        $entry = $this->repository->forDocument($document, $locale);
        return [
            'metadata' => $this->metadata->forDocument($document, $entry)->toArray(),
            'schema' => $this->schema->forDocument($document, $entry)->toArray(),
        ];
    }
}
