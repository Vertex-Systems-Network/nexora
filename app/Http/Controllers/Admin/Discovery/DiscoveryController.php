<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Discovery;

use App\Http\Controllers\Controller;
use App\Jobs\RunSeoCrawlJob;
use App\Models\AnalyticsDailyMetric;
use App\Models\CrawlIssue;
use App\Models\CrawlRun;
use App\Models\Document;
use App\Models\SearchIndexEntry;
use App\Models\SearchQueryLog;
use App\Nexora\Discovery\Analytics\AnalyticsAggregator;
use App\Nexora\Discovery\Crawler\SeoCrawler;
use App\Nexora\Discovery\Search\SearchIndexer;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Security\Audit\AuditManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryController extends Controller
{
    public function index(Request $request, SettingsContract $settings): Response
    {
        $days = max(7, min(90, (int) $request->integer('days', 30)));
        $from = now()->subDays($days - 1)->startOfDay();
        $metrics = AnalyticsDailyMetric::query()->where('metric_date', '>=', $from->toDateString());
        $siteMetrics = (clone $metrics)->where('resource_type', 'site')->get();
        $latestRun = CrawlRun::query()->latest('id')->first();

        $topContent = AnalyticsDailyMetric::query()
            ->where('metric_date', '>=', $from->toDateString())
            ->where('resource_type', 'document')
            ->select('resource_id', DB::raw('SUM(page_views) as page_views'), DB::raw('SUM(unique_visitors) as unique_visitors'))
            ->groupBy('resource_id')->orderByDesc('page_views')->limit(8)->get();
        $documents = Document::query()->whereIn('id', $topContent->pluck('resource_id'))->get(['id','title','type','slug'])->keyBy('id');

        $topQueries = SearchQueryLog::query()->where('searched_at', '>=', $from)
            ->select('normalized_query', DB::raw('COUNT(*) as searches'), DB::raw('SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) as zero_results'))
            ->groupBy('normalized_query')->orderByDesc('searches')->limit(10)->get();

        $issues = $latestRun
            ? CrawlIssue::query()->where('crawl_run_id', $latestRun->id)->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")->latest('id')->limit(12)->get()
            : collect();

        return Inertia::render('Admin/Discovery/Index', [
            'filters'=>['days'=>$days],
            'summary'=>[
                'indexed'=>SearchIndexEntry::query()->count(),
                'publishedIndexed'=>SearchIndexEntry::query()->where('status','published')->count(),
                'searches'=>(int) $siteMetrics->sum('searches'),
                'zeroResultSearches'=>(int) $siteMetrics->sum('search_zero_results'),
                'pageViews'=>(int) $siteMetrics->sum('page_views'),
                'uniqueVisitors'=>(int) $siteMetrics->sum('unique_visitors'),
                'lastIndexedAt'=>SearchIndexEntry::query()->max('indexed_at'),
                'lastAggregatedAt'=>AnalyticsDailyMetric::query()->max('updated_at'),
            ],
            'topContent'=>$topContent->map(function ($row) use ($documents): array {
                $document = $documents->get((int) $row->resource_id);
                return [
                    'id'=>(int) $row->resource_id,'title'=>$document?->title ?? 'Deleted document','type'=>$document?->type ?? 'document',
                    'pageViews'=>(int) $row->page_views,'uniqueVisitors'=>(int) $row->unique_visitors,
                    'href'=>$document ? route('admin.documents.edit',$document,false) : null,
                ];
            })->values(),
            'topQueries'=>$topQueries->map(fn ($row): array => ['query'=>$row->normalized_query,'searches'=>(int)$row->searches,'zeroResults'=>(int)$row->zero_results])->values(),
            'latestCrawl'=>$latestRun ? [
                'id'=>$latestRun->id,'uuid'=>$latestRun->uuid,'status'=>$latestRun->status,'crawledUrls'=>$latestRun->crawled_urls,
                'failedUrls'=>$latestRun->failed_urls,'issuesCount'=>$latestRun->issues_count,'highIssuesCount'=>$latestRun->high_issues_count,
                'startedAt'=>$latestRun->started_at?->toIso8601String(),'completedAt'=>$latestRun->completed_at?->toIso8601String(),
            ] : null,
            'crawlIssues'=>$issues->map(fn (CrawlIssue $issue): array => [
                'id'=>$issue->id,'severity'=>$issue->severity,'code'=>$issue->code,'category'=>$issue->category,
                'title'=>$issue->title,'description'=>$issue->description,'url'=>$issue->url,
            ])->values(),
            'settings'=>[
                'publicSearch'=>(bool) filter_var($settings->get('search.public_enabled', true), FILTER_VALIDATE_BOOL),
                'analyticsEnabled'=>(bool) filter_var($settings->get('analytics.enabled', true), FILTER_VALIDATE_BOOL),
                'rawRetentionDays'=>(int) $settings->get('analytics.raw_retention_days', 90),
                'searchRetentionDays'=>(int) $settings->get('analytics.search_retention_days', 180),
                'crawlerEnabled'=>(bool) filter_var($settings->get('seo.crawler.enabled', false), FILTER_VALIDATE_BOOL),
                'crawlerMaxUrls'=>(int) $settings->get('seo.crawler.max_urls', 250),
            ],
        ]);
    }

    public function reindex(Request $request, SearchIndexer $indexer, AuditManager $audit): RedirectResponse
    {
        $result = $indexer->rebuild();
        $audit->record('search.index.rebuilt', metadata:$result, request:$request);
        return back()->with('success', "Search index rebuilt: {$result['indexed']} resources indexed.");
    }

    public function aggregate(Request $request, AnalyticsAggregator $aggregator, AuditManager $audit): RedirectResponse
    {
        $today = $aggregator->aggregate(CarbonImmutable::today());
        $yesterday = $aggregator->aggregate(CarbonImmutable::yesterday());
        $audit->record('analytics.aggregated', metadata:['today'=>$today,'yesterday'=>$yesterday], request:$request);
        return back()->with('success', 'Analytics aggregates refreshed for today and yesterday.');
    }

    public function settings(Request $request, SettingsContract $settings, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'publicSearch'=>['required','boolean'],'analyticsEnabled'=>['required','boolean'],
            'rawRetentionDays'=>['required','integer','min:7','max:365'],'searchRetentionDays'=>['required','integer','min:7','max:365'],
            'crawlerEnabled'=>['required','boolean'],'crawlerMaxUrls'=>['required','integer','min:10','max:1000'],
        ]);
        $settings->set('search.public_enabled', (bool) $data['publicSearch']);
        $settings->set('analytics.enabled', (bool) $data['analyticsEnabled']);
        $settings->set('analytics.raw_retention_days', (int) $data['rawRetentionDays']);
        $settings->set('analytics.search_retention_days', (int) $data['searchRetentionDays']);
        $settings->set('seo.crawler.enabled', (bool) $data['crawlerEnabled']);
        $settings->set('seo.crawler.max_urls', (int) $data['crawlerMaxUrls']);
        $audit->record('discovery.settings.updated', metadata:['keys'=>array_keys($data)], request:$request);
        return back()->with('success','Search, analytics and crawler settings saved.');
    }

    public function crawl(Request $request, SeoCrawler $crawler, SettingsContract $settings, AuditManager $audit): RedirectResponse
    {
        $validated = $request->validate(['limit'=>['nullable','integer','min:1','max:1000']]);
        $run = $crawler->createRun((int) ($validated['limit'] ?? $settings->get('seo.crawler.max_urls', 250)), $request->user()?->id);
        RunSeoCrawlJob::dispatch($run->id);
        $audit->record('seo.crawl.queued', $run, ['limit'=>$run->requested_limit], $request);
        return redirect()->route('admin.discovery.crawl.show', $run)->with('success', 'SEO crawl queued. A queue worker will process it; CLI crawl remains available for immediate runs.');
    }

    public function cancel(Request $request, CrawlRun $run, AuditManager $audit): RedirectResponse
    {
        $status = (string) $run->status;
        if ($status === 'queued') {
            $run->forceFill(['status'=>'cancelled','completed_at'=>now(),'error'=>null])->save();
        } elseif ($status === 'running') {
            $run->forceFill(['status'=>'cancel_requested'])->save();
        }
        $audit->record('seo.crawl.cancel_requested', $run, ['previous_status'=>$status], $request);
        return back()->with('success', in_array($status, ['queued','running'], true) ? 'SEO crawl cancellation requested.' : 'SEO crawl is already in a terminal state.');
    }

    public function show(CrawlRun $run): Response
    {
        $issues = $run->issues()->latest('id')->paginate(100)->through(fn (CrawlIssue $issue): array => [
            'id'=>$issue->id,'severity'=>$issue->severity,'code'=>$issue->code,'category'=>$issue->category,
            'title'=>$issue->title,'description'=>$issue->description,'url'=>$issue->url,'metadata'=>$issue->metadata,
        ]);
        $pages = $run->pages()->orderByDesc('duration_ms')->limit(20)->get()->map(fn ($page): array => [
            'id'=>$page->id,'url'=>$page->url,'statusCode'=>$page->status_code,'durationMs'=>$page->duration_ms,
            'title'=>$page->title,'h1Count'=>$page->h1_count,'wordCount'=>$page->word_count,
            'internalLinks'=>$page->internal_links_count,'externalLinks'=>$page->external_links_count,'hasSchema'=>$page->has_schema,
        ]);
        return Inertia::render('Admin/Discovery/Crawl', [
            'run'=>[
                'id'=>$run->id,'uuid'=>$run->uuid,'status'=>$run->status,'baseUrl'=>$run->base_url,'requestedLimit'=>$run->requested_limit,
                'discoveredUrls'=>$run->discovered_urls,'crawledUrls'=>$run->crawled_urls,'failedUrls'=>$run->failed_urls,
                'issuesCount'=>$run->issues_count,'highIssuesCount'=>$run->high_issues_count,'summary'=>$run->summary ?? [],
                'startedAt'=>$run->started_at?->toIso8601String(),'completedAt'=>$run->completed_at?->toIso8601String(),'error'=>$run->error,
            ],
            'issues'=>$issues,'slowestPages'=>$pages,
        ]);
    }
}
