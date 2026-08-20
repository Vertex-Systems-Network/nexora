<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Repositories;

use App\Models\Document;
use App\Nexora\Documents\Contracts\DocumentRepositoryContract;
use App\Nexora\Documents\Services\DocumentContentValidator;
use App\Nexora\Documents\Services\DocumentRevisionManager;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class DatabaseDocumentRepository implements DocumentRepositoryContract
{
    public function __construct(
        private DocumentRevisionManager $revisions,
        private DocumentContentValidator $contentValidator,
        private ConcurrencyGuard $concurrency,
    ) {
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Document::query()->with('author:id,name')->withCount('revisions')->latest('updated_at');
        if (($filters['search'] ?? '') !== '') {
            $search = (string) $filters['search'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        }
        if (($filters['status'] ?? '') !== '') $query->where('status', (string) $filters['status']);
        if (($filters['type'] ?? '') !== '') $query->where('type', (string) $filters['type']);
        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $attributes, ?int $actorId = null): Document
    {
        return $this->concurrency->transaction(function () use ($attributes, $actorId): Document {
            $document = Document::query()->create($this->normalize($attributes, $actorId, true));
            $this->revisions->record($document, $actorId);
            return $document->refresh();
        });
    }

    public function update(Document $document, array $attributes, ?int $actorId = null): Document
    {
        return $this->concurrency->transaction(function () use ($document, $attributes, $actorId): Document {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $expected = (int) ($attributes['lock_version'] ?? 0);
            if ($expected !== (int) $locked->lock_version) {
                throw ValidationException::withMessages([
                    'document' => 'This document was updated in another session. Reload before saving to avoid overwriting newer work.',
                ]);
            }

            $normalized = $this->normalize($attributes, $actorId, false);
            $normalized['lock_version'] = ((int) $locked->lock_version) + 1;
            $locked->fill($normalized)->save();
            $this->revisions->record($locked, $actorId);
            return $locked->refresh();
        });
    }

    public function delete(Document $document): void
    {
        $document->delete();
    }

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    private function normalize(array $attributes, ?int $actorId, bool $creating): array
    {
        $status = (string) ($attributes['status'] ?? 'draft');
        $normalized = [
            'type' => (string) ($attributes['type'] ?? 'document'),
            'status' => $status,
            'title' => trim((string) ($attributes['title'] ?? 'Untitled document')),
            'slug' => ($attributes['slug'] ?? '') !== '' ? Str::slug((string) $attributes['slug']) : null,
            'excerpt' => ($attributes['excerpt'] ?? '') !== '' ? trim((string) $attributes['excerpt']) : null,
            'metadata' => (array) ($attributes['metadata'] ?? []),
            'last_edited_by' => $actorId,
            'published_at' => $status === 'published' ? ($attributes['published_at'] ?? now()) : null,
            'workflow_status' => (string) ($attributes['workflow_status'] ?? 'draft'),
            'assigned_to' => $attributes['assigned_to'] ?? null,
            'reviewer_id' => $attributes['reviewer_id'] ?? null,
            'review_due_at' => $attributes['review_due_at'] ?? null,
        ];
        if (array_key_exists('content', $attributes)) {
            $normalized['content'] = $this->contentValidator->normalize((array) $attributes['content']);
        }
        if ($creating) {
            $normalized['uuid'] = (string) Str::uuid();
            $normalized['author_id'] = $actorId;
            $normalized['schema_version'] = 1;
            $normalized['lock_version'] = 1;
            $normalized['content'] ??= ['version' => 1, 'blocks' => []];
        }
        return $normalized;
    }
}
