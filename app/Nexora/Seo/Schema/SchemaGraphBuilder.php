<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Schema;

use App\Models\Document;
use App\Models\SeoEntry;
use App\Models\SeoSchemaNode;
use App\Nexora\Foundation\Contracts\SettingsContract;

final readonly class SchemaGraphBuilder
{
    public function __construct(private SettingsContract $settings)
    {
    }

    public function forDocument(Document $document, SeoEntry $entry): SchemaGraph
    {
        $graph = new SchemaGraph();
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        $siteId = $baseUrl !== '' ? $baseUrl.'/#website' : '#website';
        $orgId = $baseUrl !== '' ? $baseUrl.'/#organization' : '#organization';
        $pageUrl = $this->absoluteUrl((string) ($entry->canonical_url ?: $entry->url_path));
        $resourceUrn = 'urn:nexora:document:'.$document->uuid;
        $pageId = ($pageUrl ?: $resourceUrn).'#webpage';

        $organizationName = trim((string) $this->settings->get('seo.organization_name', ''));
        $siteProperties = [
            'name' => (string) $this->settings->get('seo.site_name', $this->settings->get('app.name', 'Nexora')),
            'url' => $baseUrl !== '' ? $baseUrl.'/' : null,
        ];
        if ($organizationName !== '') $siteProperties['publisher'] = ['@id' => $orgId];
        $graph->add(new SchemaNode($siteId, 'WebSite', array_filter($siteProperties, static fn ($value) => $value !== null && $value !== '')));

        if ($organizationName !== '') {
            $graph->add(new SchemaNode($orgId, 'Organization', array_filter([
                'name' => $organizationName,
                'url' => (string) $this->settings->get('seo.organization_url', $baseUrl),
                'logo' => (string) $this->settings->get('seo.organization_logo', ''),
            ], static fn ($value) => $value !== null && $value !== '')));
        }

        if (in_array($document->type, ['article', 'blog_post'], true)) {
            $this->addArticleGraph($graph, $document, $entry, $siteId, $orgId, $organizationName, $baseUrl, $pageUrl, $pageId, $resourceUrn);
        } else {
            $properties = array_filter([
                'name' => (string) ($entry->seo_title ?: $document->title),
                'description' => $entry->meta_description ?: $document->excerpt,
                'url' => $pageUrl,
                'isPartOf' => ['@id' => $siteId],
                'datePublished' => $document->published_at?->toIso8601String(),
                'dateModified' => $document->updated_at?->toIso8601String(),
            ], static fn ($value) => $value !== null && $value !== '');
            $properties = array_replace_recursive($properties, (array) ($entry->schema_overrides ?? []));
            $graph->add(new SchemaNode($pageId, (string) ($entry->schema_type ?: 'WebPage'), $properties, 'nexora.seo', 200));
        }

        SeoSchemaNode::query()
            ->where('is_enabled', true)
            ->where(function ($query) use ($document): void {
                $query->whereNull('resource_type')
                    ->orWhere(fn ($resource) => $resource->where('resource_type', 'document')->where('resource_id', $document->id));
            })
            ->where('locale', $entry->locale)
            ->orderBy('priority')
            ->get()
            ->each(function (SeoSchemaNode $node) use ($graph): void {
                $graph->add(new SchemaNode($node->node_id, $node->schema_type, (array) $node->properties, $node->source, (int) $node->priority));
            });

        return $graph;
    }

    private function addArticleGraph(
        SchemaGraph $graph,
        Document $document,
        SeoEntry $entry,
        string $siteId,
        string $orgId,
        string $organizationName,
        string $baseUrl,
        ?string $pageUrl,
        string $pageId,
        string $resourceUrn,
    ): void {
        $document->loadMissing(['authorProfiles', 'taxonomyTerms', 'articleMetadata.heroMedia']);
        $articleId = ($pageUrl ?: $resourceUrn).'#article';

        $pageProperties = array_filter([
            'name' => (string) ($entry->seo_title ?: $document->title),
            'description' => $entry->meta_description ?: $document->excerpt,
            'url' => $pageUrl,
            'isPartOf' => ['@id' => $siteId],
            'mainEntity' => ['@id' => $articleId],
            'datePublished' => $document->published_at?->toIso8601String(),
            'dateModified' => $document->updated_at?->toIso8601String(),
        ], static fn ($value) => $value !== null && $value !== '');
        $graph->add(new SchemaNode($pageId, 'WebPage', $pageProperties, 'nexora.seo', 180));

        $authorRefs = [];
        foreach ($document->authorProfiles as $author) {
            $authorUrl = $baseUrl !== '' ? $baseUrl.'/authors/'.rawurlencode((string) $author->slug) : null;
            $authorId = ($authorUrl ?: 'urn:nexora:author:'.$author->uuid).'#person';
            $sameAs = array_values(array_filter(array_map('strval', (array) ($author->social_links ?? [])), static fn (string $url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false));
            $graph->add(new SchemaNode($authorId, 'Person', array_filter([
                'name' => $author->display_name,
                'url' => $authorUrl,
                'description' => $author->bio,
                'image' => $author->avatar_url,
                'sameAs' => $sameAs !== [] ? $sameAs : null,
            ], static fn ($value) => $value !== null && $value !== '' && $value !== [])));
            $authorRefs[] = ['@id' => $authorId];
        }

        $categories = $document->taxonomyTerms->where('taxonomy', 'category')->pluck('name')->values()->all();
        $keywords = $document->taxonomyTerms->whereIn('taxonomy', ['topic', 'tag'])->pluck('name')->values()->all();
        $articleProperties = array_filter([
            'headline' => (string) ($entry->seo_title ?: $document->title),
            'name' => (string) ($entry->seo_title ?: $document->title),
            'description' => $entry->meta_description ?: $document->excerpt,
            'url' => $pageUrl,
            'mainEntityOfPage' => ['@id' => $pageId],
            'author' => $authorRefs !== [] ? $authorRefs : null,
            'publisher' => $organizationName !== '' ? ['@id' => $orgId] : null,
            'datePublished' => $document->published_at?->toIso8601String(),
            'dateModified' => $document->updated_at?->toIso8601String(),
            'articleSection' => $categories !== [] ? implode(', ', $categories) : null,
            'keywords' => $keywords !== [] ? implode(', ', $keywords) : null,
            'image' => $document->articleMetadata?->heroMedia?->publicUrl() ?: $document->articleMetadata?->hero_image_url,
            'isAccessibleForFree' => true,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

        $articleProperties = array_replace_recursive($articleProperties, (array) ($entry->schema_overrides ?? []));
        $defaultType = $document->type === 'blog_post' ? 'BlogPosting' : 'Article';
        $schemaType = in_array((string) $entry->schema_type, ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle'], true)
            ? (string) $entry->schema_type
            : $defaultType;
        $graph->add(new SchemaNode($articleId, $schemaType, $articleProperties, 'nexora.publishing', 220));
    }

    private function absoluteUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') return null;
        if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
        $base = rtrim((string) config('app.url', ''), '/');
        if ($base === '') return null;
        return $base.'/'.ltrim($url, '/');
    }
}
