<?php

declare(strict_types=1);
namespace App\Observers;
use App\Models\MediaAsset;
use App\Nexora\Discovery\Search\SearchIndexer;
use Illuminate\Support\Facades\Schema;
final readonly class MediaAssetSearchObserver
{
    public function __construct(private SearchIndexer $indexer) {}
    public function saved(MediaAsset $asset): void { if (Schema::hasTable('nx_search_index')) $this->indexer->indexMedia($asset); }
    public function deleted(MediaAsset $asset): void { if (Schema::hasTable('nx_search_index')) $this->indexer->removeMedia($asset); }
    public function restored(MediaAsset $asset): void { if (Schema::hasTable('nx_search_index')) $this->indexer->indexMedia($asset); }
}
