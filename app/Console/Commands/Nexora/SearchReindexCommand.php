<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Discovery\Search\SearchIndexer;
use Illuminate\Console\Command;

final class SearchReindexCommand extends Command
{
    protected $signature = 'nexora:search:reindex';
    protected $description = 'Rebuild the Nexora first-party content search index.';
    public function handle(SearchIndexer $indexer): int
    {
        $result = $indexer->rebuild();
        $this->info("Indexed {$result['indexed']} resources; removed {$result['removed']} stale rows.");
        return self::SUCCESS;
    }
}
