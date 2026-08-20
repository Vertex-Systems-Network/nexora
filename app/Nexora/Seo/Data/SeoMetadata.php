<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Data;

final readonly class SeoMetadata
{
    /** @param list<string> $robotsDirectives */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $canonicalUrl,
        public bool $index,
        public bool $follow,
        public array $robotsDirectives,
        public string $schemaType,
        public bool $sitemapInclude,
        public string $indexingState,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical_url' => $this->canonicalUrl,
            'robots' => [
                'index' => $this->index,
                'follow' => $this->follow,
                'directives' => $this->robotsDirectives,
            ],
            'schema_type' => $this->schemaType,
            'sitemap_include' => $this->sitemapInclude,
            'indexing_state' => $this->indexingState,
        ];
    }
}
