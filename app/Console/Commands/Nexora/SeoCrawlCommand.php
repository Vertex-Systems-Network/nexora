<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Discovery\Crawler\SeoCrawler;
use App\Nexora\Foundation\Contracts\SettingsContract;
use Illuminate\Console\Command;

final class SeoCrawlCommand extends Command
{
    protected $signature = 'nexora:seo:crawl {--limit= : Maximum URLs to crawl; defaults to the Discovery setting}';
    protected $description = 'Crawl Nexora public URLs and persist technical/content SEO observations.';
    public function handle(SeoCrawler $crawler, SettingsContract $settings): int
    {
        $configured = (int) $settings->get('seo.crawler.max_urls', 250);
        $limit = $this->option('limit') !== null && $this->option('limit') !== '' ? (int) $this->option('limit') : $configured;
        $run = $crawler->createRun(max(1, min(1000, $limit)));
        $this->info('Crawl '.$run->uuid.' started.');
        try { $crawler->run($run); } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
        $run->refresh();
        $this->info("Crawled {$run->crawled_urls} URLs; {$run->issues_count} observations ({$run->high_issues_count} high). No synthetic SEO score was generated.");
        return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
