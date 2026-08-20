<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\SeoEntry;
use App\Nexora\Seo\Contracts\SeoRepositoryContract;
use App\Nexora\Seo\Services\SeoAuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SeoDashboardController extends Controller
{
    public function __invoke(Request $request, SeoRepositoryContract $seo, SeoAuditService $audit): Response
    {
        $search = trim((string) $request->query('search', ''));
        $query = Document::query()->latest('updated_at');
        if ($search !== '') $query->where('title', 'like', "%{$search}%");
        $documents = $query->paginate(20)->withQueryString();
        $documents->through(function (Document $document) use ($seo, $audit): array {
            $entry = $seo->forDocument($document);
            $issues = $audit->document($document, $entry);
            return [
                'id' => $document->id,
                'title' => $document->title,
                'slug' => $document->slug,
                'status' => $document->status,
                'seo_title' => $entry->seo_title ?: $document->title,
                'canonical_url' => $entry->canonical_url,
                'url_path' => $entry->url_path,
                'robots_index' => (bool) $entry->robots_index,
                'sitemap_include' => (bool) $entry->sitemap_include,
                'issues_count' => count($issues),
                'high_issues' => count(array_filter($issues, static fn (array $issue): bool => $issue['severity'] === 'high')),
                'updated_at' => $document->updated_at?->toIso8601String(),
            ];
        });

        return Inertia::render('Admin/Seo/Index', [
            'documents' => $documents,
            'filters' => ['search' => $search],
            'summary' => [
                'configured' => SeoEntry::query()->count(),
                'indexable' => SeoEntry::query()->where('robots_index', true)->count(),
                'excluded' => SeoEntry::query()->where('robots_index', false)->count(),
                'sitemap' => SeoEntry::query()->where('robots_index', true)->where('sitemap_include', true)->count(),
            ],
        ]);
    }
}
