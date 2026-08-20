<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Analytics;

use App\Models\AnalyticsDailyMetric;
use App\Models\AnalyticsEvent;
use App\Models\SearchQueryLog;
use Carbon\CarbonImmutable;

final class AnalyticsAggregator
{
    /** @return array{date:string,metrics:int} */
    public function aggregate(CarbonImmutable $date): array
    {
        $start = $date->startOfDay();
        $end = $date->endOfDay();
        $events = AnalyticsEvent::query()->whereBetween('occurred_at', [$start, $end])->get();
        $searches = SearchQueryLog::query()->whereBetween('searched_at', [$start, $end])->get();
        $groups = ['site:0'=>['resource_type'=>'site','resource_id'=>0,'events'=>collect($events)]];
        foreach ($events->where('event_type', 'page_view')->whereNotNull('resource_type')->whereNotNull('resource_id')->groupBy(fn ($event) => $event->resource_type.':'.$event->resource_id) as $key => $group) {
            $first = $group->first();
            $groups[$key] = ['resource_type'=>(string) $first->resource_type,'resource_id'=>(int) $first->resource_id,'events'=>$group];
        }

        AnalyticsDailyMetric::query()->whereDate('metric_date', $date->toDateString())->delete();
        foreach ($groups as $group) {
            $groupEvents = $group['events'];
            $views = $groupEvents->where('event_type', 'page_view');
            $unique = $views->pluck('visitor_hash')->filter()->unique()->count();
            $referrals = $views->pluck('referrer_host')->filter()->count();
            $engaged = $views->where('duration_ms', '>=', 1000)->count();
            $isSite = $group['resource_type'] === 'site';
            AnalyticsDailyMetric::query()->create([
                'metric_date'=>$date->toDateString(),'resource_type'=>$group['resource_type'],'resource_id'=>$group['resource_id'],
                'page_views'=>$views->count(),'unique_visitors'=>$unique,
                'searches'=>$isSite ? $searches->count() : 0,
                'search_zero_results'=>$isSite ? $searches->where('results_count', 0)->count() : 0,
                'referrals'=>$referrals,'engaged_views'=>$engaged,
            ]);
        }
        return ['date'=>$date->toDateString(),'metrics'=>count($groups)];
    }
}
