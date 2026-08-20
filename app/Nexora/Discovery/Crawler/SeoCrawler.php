<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Crawler;

use App\Models\CrawlIssue;
use App\Models\CrawlPage;
use App\Models\CrawlRun;
use App\Nexora\Seo\Sitemap\SitemapService;
use App\Nexora\Foundation\Network\ApprovedHttpClient;
use Illuminate\Support\Str;
use RuntimeException;

final class SeoCrawler
{
    public function __construct(private SitemapService $sitemap, private PageInspector $inspector, private ApprovedHttpClient $http) {}

    public function createRun(int $limit = 250, ?int $userId = null): CrawlRun
    {
        $base = rtrim((string) config('app.url'), '/');
        if (! filter_var($base, FILTER_VALIDATE_URL)) throw new RuntimeException('APP_URL must be a valid absolute URL before running the SEO crawler.');
        return CrawlRun::query()->create([
            'uuid'=>(string) Str::uuid(),'status'=>'queued','base_url'=>$base,'requested_limit'=>max(1, min(1000, $limit)),'started_by'=>$userId,
        ]);
    }

    public function run(CrawlRun $run): CrawlRun
    {
        $run->forceFill(['status'=>'running','started_at'=>now(),'error'=>null])->save();
        $siteHost = (string) parse_url($run->base_url, PHP_URL_HOST);
        $siteScheme = strtolower((string) parse_url($run->base_url, PHP_URL_SCHEME));
        $sitePort = (int) (parse_url($run->base_url, PHP_URL_PORT) ?: ($siteScheme === 'https' ? 443 : 80));
        if ($siteHost === '' || ! in_array($siteScheme, ['http','https'], true)) throw new RuntimeException('Crawler base URL must use http or https and include a host.');
        $limit = max(1, min(1000, (int) $run->requested_limit));
        $queue = collect($this->sitemap->urls())->pluck('loc')->filter()->map('strval')->prepend($run->base_url.'/')->unique()->values()->all();
        $seen = [];
        $crawled = 0;
        $failed = 0;

        try {
            while ($queue !== [] && $crawled < $limit) {
                if ($this->cancelIfRequested($run)) return $run->refresh();
                $url = array_shift($queue);
                if (! is_string($url) || isset($seen[$url]) || ! $this->isAllowed($url, $siteHost, $siteScheme, $sitePort)) continue;
                $seen[$url] = true;
                $run->forceFill(['discovered_urls'=>count($seen) + count($queue)])->save();
                $started = hrtime(true);
                try {
                    $response = $this->http->sameOrigin($url,$run->base_url)->timeout(12)->connectTimeout(5)->retry(1, 150)->withHeaders([
                        'User-Agent'=>'NexoraSEO/0.26 (+'.rtrim($run->base_url, '/').'/robots.txt)',
                        'Accept'=>'text/html,application/xhtml+xml;q=0.9,*/*;q=0.1',
                    ])->get($url);
                    $duration = (int) round((hrtime(true) - $started) / 1_000_000);
                    $status = $response->status();
                    $redirectLocation = in_array($status, [301,302,303,307,308], true) ? trim((string) $response->header('Location')) : '';
                    $resolvedRedirect = $redirectLocation !== '' ? $this->resolveRedirect($redirectLocation, $url) : null;
                    if ($resolvedRedirect !== null && $this->isAllowed($resolvedRedirect, $siteHost, $siteScheme, $sitePort) && ! isset($seen[$resolvedRedirect])) $queue[] = $resolvedRedirect;
                    $contentType = (string) $response->header('Content-Type');
                    $html = str_contains(strtolower($contentType), 'text/html') ? $response->body() : '';
                    $inspection = $html !== '' ? $this->inspector->inspect($html, $url, $siteHost) : $this->emptyInspection($response->body());
                    $page = CrawlPage::query()->create([
                        'crawl_run_id'=>$run->id,'url'=>$url,'url_hash'=>hash('sha256',$url),'path'=>parse_url($url, PHP_URL_PATH) ?: '/',
                        'status_code'=>$status,'content_type'=>mb_substr($contentType,0,190),'duration_ms'=>$duration,
                        'title'=>$inspection['title'],'meta_description'=>$inspection['meta_description'],'canonical_url'=>$inspection['canonical_url'],
                        'robots'=>$inspection['robots'],'h1_count'=>$inspection['h1_count'],'word_count'=>$inspection['word_count'],
                        'internal_links_count'=>count($inspection['internal_links']),'external_links_count'=>count($inspection['external_links']),
                        'has_schema'=>$inspection['has_schema'],'content_hash'=>$inspection['content_hash'],
                        'metadata'=>['redirect_location'=>$resolvedRedirect],
                    ]);
                    $this->issuesFor($run, $page, $inspection, $siteHost);
                    foreach ($inspection['internal_links'] as $link) {
                        if (count($seen) + count($queue) >= $limit * 2) break;
                        if (! isset($seen[$link])) $queue[] = $link;
                    }
                    if ($status >= 400) $failed++;
                } catch (\Throwable $exception) {
                    $failed++;
                    $page = CrawlPage::query()->create([
                        'crawl_run_id'=>$run->id,'url'=>$url,'url_hash'=>hash('sha256',$url),'path'=>parse_url($url, PHP_URL_PATH) ?: '/','duration_ms'=>(int) round((hrtime(true)-$started)/1_000_000),
                        'metadata'=>['error'=>$exception->getMessage()],
                    ]);
                    $this->issue($run, $page, 'high', 'fetch_failed', 'technical', 'Page could not be fetched', 'The crawler could not retrieve this URL: '.$exception->getMessage());
                }
                $crawled++;
                $run->forceFill(['crawled_urls'=>$crawled,'failed_urls'=>$failed])->save();
            }

            if ($this->cancelIfRequested($run)) return $run->refresh();
            $this->duplicateIssues($run);
            $issues = CrawlIssue::query()->where('crawl_run_id', $run->id)->count();
            $high = CrawlIssue::query()->where('crawl_run_id', $run->id)->whereIn('severity', ['high','critical'])->count();
            $run->forceFill([
                'status'=>'completed','completed_at'=>now(),'discovered_urls'=>max((int)$run->discovered_urls, count($seen)),
                'crawled_urls'=>$crawled,'failed_urls'=>$failed,'issues_count'=>$issues,'high_issues_count'=>$high,
                'summary'=>['high'=>$high,'medium'=>CrawlIssue::query()->where('crawl_run_id',$run->id)->where('severity','medium')->count(),'low'=>CrawlIssue::query()->where('crawl_run_id',$run->id)->where('severity','low')->count()],
            ])->save();
        } catch (\Throwable $exception) {
            $run->forceFill(['status'=>'failed','completed_at'=>now(),'error'=>$exception->getMessage()])->save();
            throw $exception;
        }

        return $run->refresh();
    }

    private function cancelIfRequested(CrawlRun $run): bool
    {
        $status = (string) CrawlRun::query()->whereKey($run->id)->value('status');
        if ($status !== 'cancel_requested') return false;
        $run->forceFill([
            'status'=>'cancelled',
            'completed_at'=>now(),
            'error'=>null,
        ])->save();
        return true;
    }

    /** @param array<string,mixed> $inspection */
    private function issuesFor(CrawlRun $run, CrawlPage $page, array $inspection, string $siteHost): void
    {
        $status = (int) ($page->status_code ?? 0);
        if ($status >= 500) $this->issue($run,$page,'high','http_5xx','availability','Server error response','This URL returned an HTTP '.$status.' response.');
        elseif ($status >= 400) $this->issue($run,$page,'high','http_4xx','availability','Broken or unavailable URL','This URL returned an HTTP '.$status.' response.');
        elseif ($status >= 300) $this->issue($run,$page,'low','http_redirect','technical','Redirected sitemap/internal URL','The crawler received an HTTP '.$status.' redirect. Prefer final canonical URLs in sitemaps and internal links.');
        if ($page->duration_ms !== null && $page->duration_ms > 2500) $this->issue($run,$page,'medium','slow_response','performance','Slow HTML response','The server took more than 2.5 seconds to return this HTML response.', ['duration_ms'=>$page->duration_ms]);
        if (! str_contains(strtolower((string)$page->content_type), 'text/html')) return;
        if (trim((string)$inspection['title']) === '') $this->issue($run,$page,'high','title_missing','metadata','Page title is missing','Add a unique, human-readable <title> to this indexable page.');
        if (trim((string)$inspection['meta_description']) === '') $this->issue($run,$page,'medium','description_missing','metadata','Meta description is missing','Add a useful page description for search/distribution previews.');
        if (trim((string)$inspection['canonical_url']) === '') $this->issue($run,$page,'high','canonical_missing','canonical','Canonical URL is missing','Expose the canonical URL owned by Nexora SEO Core.');
        else {
            $canonicalHost = (string) parse_url((string)$inspection['canonical_url'], PHP_URL_HOST);
            if ($canonicalHost !== '' && strcasecmp($canonicalHost, $siteHost) !== 0) $this->issue($run,$page,'medium','canonical_external','canonical','Canonical points to another host','Review whether this cross-domain canonical is intentional.');
        }
        $robots = mb_strtolower((string) ($inspection['robots'] ?? ''));
        if (str_contains($robots, 'noindex')) $this->issue($run,$page,'high','sitemap_noindex','indexing','Sitemap URL is noindex','This crawled URL came from site discovery but declares noindex. Remove it from indexable discovery or change the indexing policy.');
        if ((int)$inspection['h1_count'] === 0) $this->issue($run,$page,'medium','h1_missing','content','Primary heading is missing','Add one descriptive H1 heading to the page content.');
        elseif ((int)$inspection['h1_count'] > 1) $this->issue($run,$page,'low','h1_multiple','content','Multiple H1 headings detected','Multiple H1 elements can be valid, but review the page hierarchy and main heading intent.',['count'=>$inspection['h1_count']]);
        if (! (bool)$inspection['has_schema']) $this->issue($run,$page,'low','schema_missing','structured-data','No JSON-LD graph detected','Nexora SEO Core can expose structured data when the resource has a semantic schema mapping.');
        if ((int)$inspection['word_count'] > 0 && (int)$inspection['word_count'] < 80) $this->issue($run,$page,'low','thin_visible_content','content','Very little visible text','This is an observation, not an SEO score. Confirm that the page intentionally contains little textual content.',['word_count'=>$inspection['word_count']]);
    }

    private function duplicateIssues(CrawlRun $run): void
    {
        foreach (['title'=>'duplicate_title','canonical_url'=>'duplicate_canonical'] as $column => $code) {
            $duplicates = CrawlPage::query()->where('crawl_run_id',$run->id)->whereNotNull($column)->where($column,'<>','')
                ->selectRaw($column.', COUNT(*) as aggregate_count')->groupBy($column)->havingRaw('COUNT(*) > 1')->get();
            foreach ($duplicates as $duplicate) {
                $value = (string) $duplicate->{$column};
                CrawlPage::query()->where('crawl_run_id',$run->id)->where($column,$value)->each(function (CrawlPage $page) use ($run,$code,$column,$value): void {
                    $this->issue($run,$page,'medium',$code,$column === 'title' ? 'metadata' : 'canonical',
                        $column === 'title' ? 'Duplicate page title detected' : 'Duplicate canonical target detected',
                        $column === 'title' ? 'Multiple crawled pages share this title. Review whether each page is distinct.' : 'Multiple crawled pages declare the same canonical target. Confirm consolidation is intentional.',
                        [$column=>$value]);
                });
            }
        }
    }

    /** @param array<string,mixed> $metadata */
    private function issue(CrawlRun $run, ?CrawlPage $page, string $severity, string $code, string $category, string $title, string $description, array $metadata = []): void
    {
        CrawlIssue::query()->create(['crawl_run_id'=>$run->id,'crawl_page_id'=>$page?->id,'severity'=>$severity,'code'=>$code,'category'=>$category,'title'=>$title,'description'=>$description,'url'=>$page?->url,'metadata'=>$metadata]);
    }

    private function resolveRedirect(string $location, string $base): ?string
    {
        if (filter_var($location, FILTER_VALIDATE_URL)) return preg_replace('/#.*$/', '', $location) ?: $location;
        $parts = parse_url($base);
        if (! is_array($parts) || empty($parts['host'])) return null;
        $origin = ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (str_starts_with($location, '//')) return ($parts['scheme'] ?? 'https').':'.$location;
        if (str_starts_with($location, '/')) return $origin.$location;
        $directory = rtrim(str_replace('\\','/', dirname((string) ($parts['path'] ?? '/'))), '/');
        return $origin.($directory !== '' ? $directory : '').'/'.$location;
    }

    private function isAllowed(string $url, string $siteHost, string $siteScheme, int $sitePort): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) return false;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = (int) (parse_url($url, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80));
        if (! in_array($scheme, ['http','https'], true) || $host === '' || strcasecmp($host, $siteHost) !== 0 || $scheme !== $siteScheme || $port !== $sitePort) return false;
        $path = '/'.ltrim((string) (parse_url($url, PHP_URL_PATH) ?: '/'), '/');
        foreach (['/admin','/install','/login','/register','/forgot-password','/reset-password','/verify-email','/theme-preview','/media/','/newsletter/'] as $blocked) {
            if ($path === rtrim($blocked,'/') || str_starts_with($path, $blocked)) return false;
        }
        return true;
    }

    /** @return array<string,mixed> */
    private function emptyInspection(string $body): array
    {
        return ['title'=>null,'meta_description'=>null,'canonical_url'=>null,'robots'=>null,'h1_count'=>0,'word_count'=>0,'internal_links'=>[],'external_links'=>[],'has_schema'=>false,'content_hash'=>hash('sha256',$body)];
    }
}
