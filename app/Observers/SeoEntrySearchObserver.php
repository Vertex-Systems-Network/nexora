<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Document;
use App\Models\SeoEntry;
use App\Nexora\Discovery\Search\SearchIndexer;
use Illuminate\Support\Facades\Schema;

final readonly class SeoEntrySearchObserver
{
    public function __construct(private SearchIndexer $indexer) {}

    public function saved(SeoEntry $entry): void { $this->refreshDocument($entry); }
    public function deleted(SeoEntry $entry): void { $this->refreshDocument($entry); }

    private function refreshDocument(SeoEntry $entry): void
    {
        if ($entry->resource_type !== 'document' || ! Schema::hasTable('nx_search_index')) return;
        $document = Document::query()->find((int) $entry->resource_id);
        if ($document) $this->indexer->indexDocument($document, (string) $entry->locale);
    }
}
