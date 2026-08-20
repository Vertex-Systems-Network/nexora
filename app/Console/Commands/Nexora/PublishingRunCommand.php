<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Publishing\Services\ArticlePublishingManager;
use Illuminate\Console\Command;

final class PublishingRunCommand extends Command
{
    protected $signature = 'nexora:publishing:run';
    protected $description = 'Publish due Nexora articles and blog posts.';
    public function handle(ArticlePublishingManager $publishing): int
    {
        $count=$publishing->publishScheduled();
        $this->info("Published {$count} scheduled item(s).");
        return self::SUCCESS;
    }
}
