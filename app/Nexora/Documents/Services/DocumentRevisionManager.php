<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Services;

use App\Models\Document;
use App\Models\DocumentAutosave;
use App\Models\DocumentRevision;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Validation\ValidationException;

final readonly class DocumentRevisionManager
{
    public function __construct(private ConcurrencyGuard $concurrency) {}

    public function record(Document $document, ?int $actorId = null): DocumentRevision
    {
        return $this->concurrency->transaction(function () use ($document, $actorId): DocumentRevision {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $next = ((int) $locked->revisions()->max('revision')) + 1;
            return $locked->revisions()->create([
                'revision' => $next,
                'title' => $locked->title,
                'excerpt' => $locked->excerpt,
                'content' => $locked->content,
                'metadata' => $locked->metadata,
                'schema_version' => $locked->schema_version,
                'created_by' => $actorId,
                'document_status' => $locked->status,
                'workflow_status' => $locked->workflow_status,
            ]);
        });
    }

    public function restore(Document $document, DocumentRevision $revision, ?int $actorId = null, ?int $expectedLockVersion = null): Document
    {
        if ((int) $revision->document_id !== (int) $document->id) abort(404);

        return $this->concurrency->transaction(function () use ($document, $revision, $actorId, $expectedLockVersion): Document {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            if ($expectedLockVersion !== null && $expectedLockVersion !== (int) $locked->lock_version) {
                throw ValidationException::withMessages([
                    'document' => 'This document changed after you opened the revision screen. Reload before restoring.',
                ]);
            }

            $locked->forceFill([
                'title' => $revision->title,
                'excerpt' => $revision->excerpt,
                'content' => $revision->content,
                'metadata' => $revision->metadata,
                'schema_version' => $revision->schema_version,
                'status' => $revision->document_status ?: $locked->status,
                'workflow_status' => $revision->workflow_status ?: $locked->workflow_status,
                'last_edited_by' => $actorId,
                'lock_version' => ((int) $locked->lock_version) + 1,
            ])->save();

            $this->record($locked, $actorId);
            DocumentAutosave::query()->where('document_id', $locked->id)->delete();
            return $locked->refresh();
        });
    }
}
