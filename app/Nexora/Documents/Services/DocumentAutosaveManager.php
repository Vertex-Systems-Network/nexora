<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Services;

use App\Models\Document;
use App\Models\DocumentAutosave;
use Illuminate\Support\Facades\DB;

final readonly class DocumentAutosaveManager
{
    public function __construct(private DocumentContentValidator $contentValidator)
    {
    }

    /** @param array<string,mixed> $payload */
    public function store(Document $document, int $userId, array $payload): DocumentAutosave
    {
        return DB::transaction(function () use ($document, $userId, $payload): DocumentAutosave {
            $autosave = DocumentAutosave::query()->updateOrCreate(
                ['document_id' => $document->id, 'user_id' => $userId],
                [
                    'base_lock_version' => (int) $payload['base_lock_version'],
                    'base_revision' => (int) $payload['base_revision'],
                    'title' => trim((string) $payload['title']),
                    'slug' => ($payload['slug'] ?? '') !== '' ? trim((string) $payload['slug']) : null,
                    'excerpt' => ($payload['excerpt'] ?? '') !== '' ? trim((string) $payload['excerpt']) : null,
                    'content' => $this->contentValidator->normalize((array) ($payload['content'] ?? [])),
                    'metadata' => (array) ($payload['metadata'] ?? []),
                    'workflow_status' => (string) ($payload['workflow_status'] ?? 'draft'),
                    'saved_at' => now(),
                ],
            );
            return $autosave->refresh();
        });
    }

    public function clear(Document $document, int $userId): void
    {
        DocumentAutosave::query()->where('document_id', $document->id)->where('user_id', $userId)->delete();
    }
}
