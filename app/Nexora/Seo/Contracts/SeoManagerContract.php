<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Contracts;

use App\Models\Document;

interface SeoManagerContract
{
    /** @return array{metadata:array<string,mixed>,schema:array<string,mixed>} */
    public function documentPayload(Document $document, string $locale = 'en'): array;
}
