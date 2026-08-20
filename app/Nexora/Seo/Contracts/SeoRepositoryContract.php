<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Contracts;

use App\Models\Document;
use App\Models\SeoEntry;

interface SeoRepositoryContract
{
    public function forDocument(Document $document, string $locale = 'en'): SeoEntry;

    /** @param array<string,mixed> $attributes */
    public function saveForDocument(Document $document, array $attributes, ?int $actorId = null, string $locale = 'en'): SeoEntry;
}
