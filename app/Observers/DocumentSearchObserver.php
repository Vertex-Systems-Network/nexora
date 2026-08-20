<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Document;
use App\Nexora\Discovery\Search\SearchIndexer;
use Illuminate\Support\Facades\Schema;

final readonly class DocumentSearchObserver
{
    public function __construct(private SearchIndexer $indexer) {}
    public function saved(Document $document): void { if (Schema::hasTable('nx_search_index')) $this->indexer->indexDocument($document); }
    public function deleted(Document $document): void { if (Schema::hasTable('nx_search_index')) $this->indexer->removeDocument($document); }
}
