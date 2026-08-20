<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\SearchQueryLog;
use App\Nexora\Foundation\Contracts\SettingsContract;
use Illuminate\Http\Request;

final readonly class AnalyticsRecorder
{
    public function __construct(private PrivacyIdentity $identity, private SettingsContract $settings) {}

    public function enabled(): bool
    {
        return filter_var($this->settings->get('analytics.enabled', true), FILTER_VALIDATE_BOOL);
    }

    /** @param array<string,mixed> $metadata */
    public function pageView(Request $request, int $status, int $durationMs, ?string $resourceType = null, ?int $resourceId = null, array $metadata = []): void
    {
        if (! $this->enabled() || $request->header('Sec-GPC') === '1' || $request->header('DNT') === '1') return;
        $identity = $this->identity->forRequest($request);
        $referrerHost = null;
        $referrer = (string) $request->headers->get('referer', '');
        if ($referrer !== '') $referrerHost = parse_url($referrer, PHP_URL_HOST) ?: null;
        $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($referrerHost && $ownHost && strcasecmp((string) $referrerHost, (string) $ownHost) === 0) $referrerHost = null;

        AnalyticsEvent::query()->create([
            'event_type'=>'page_view','resource_type'=>$resourceType,'resource_id'=>$resourceId,
            'path'=>'/'.ltrim($request->path(), '/'),'locale'=>app()->getLocale(),
            'visitor_hash'=>$identity['visitor_hash'],'session_hash'=>$identity['session_hash'],
            'referrer_host'=>$referrerHost,
            'utm_source'=>$this->bounded($request->query('utm_source')),
            'utm_medium'=>$this->bounded($request->query('utm_medium')),
            'utm_campaign'=>$this->bounded($request->query('utm_campaign')),
            'response_status'=>$status,'duration_ms'=>max(0, $durationMs),
            'metadata'=>$metadata,'occurred_at'=>now(),
        ]);
    }

    public function search(Request $request, string $query, int $results, string $scope = 'public'): void
    {
        if (! $this->enabled() || $request->header('Sec-GPC') === '1' || $request->header('DNT') === '1') return;
        $identity = $this->identity->forRequest($request);
        SearchQueryLog::query()->create([
            'scope'=>$scope,'query'=>mb_substr(trim($query), 0, 190),
            'normalized_query'=>mb_substr(mb_strtolower(trim(preg_replace('/\s+/u', ' ', $query) ?: $query)), 0, 190),
            'results_count'=>max(0, $results),'locale'=>app()->getLocale(),
            'visitor_hash'=>$identity['visitor_hash'],'session_hash'=>$identity['session_hash'],'searched_at'=>now(),
        ]);
    }

    private function bounded(mixed $value): ?string
    {
        if (! is_scalar($value)) return null;
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, 190);
    }
}
