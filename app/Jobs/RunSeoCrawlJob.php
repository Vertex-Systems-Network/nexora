<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CrawlRun;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use App\Nexora\Discovery\Crawler\SeoCrawler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RunSeoCrawlJob implements ShouldQueue
{
    use Queueable;
    public int $timeout = 1800;
    public bool $failOnTimeout = true;
    public int $tries = 1;
    public function __construct(public int $runId) {}
    public function handle(SeoCrawler $crawler, TenantExecutionScope $tenantScope): void
    {
        $tenantId = CrawlRun::query()
            ->withoutGlobalScope('nexora_tenant')
            ->whereKey($this->runId)
            ->value('tenant_id');

        $tenantScope->runRequired(
            is_string($tenantId) ? $tenantId : null,
            "SEO crawl {$this->runId}",
            function () use ($crawler): void {
                $run = CrawlRun::query()->findOrFail($this->runId);

                if ($run->status === 'queued') {
                    $crawler->run($run);
                }
            },
        );
    }
}
