<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\ContentCollection;
use App\Models\Document;
use App\Nexora\Documents\Collections\ContentCollectionSchema;
use App\Nexora\Documents\Types\DocumentTypeRegistry;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ContentCollectionController extends Controller
{
    public function __construct(
        private ContentCollectionSchema $schemas,
        private DocumentTypeRegistry $types,
        private AuditManager $audit,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Collections/Index', [
            'collections' => ContentCollection::query()
                ->withCount('documents')
                ->orderBy('name')
                ->get()
                ->map(fn (ContentCollection $collection): array => $this->collectionPayload($collection))
                ->values(),
            'types' => array_values(array_map(static fn ($type) => $type->toArray(), $this->types->all())),
        ]);
    }

    public function show(ContentCollection $collection): Response
    {
        $collection->load(['documents' => fn ($query) => $query->select('nx_documents.id', 'nx_documents.title', 'nx_documents.slug', 'nx_documents.type', 'nx_documents.status')]);
        $attached = $collection->documents->pluck('id')->all();
        $available = Document::query()
            ->when($collection->document_type, fn ($query, string $type) => $query->where('type', $type))
            ->when($attached !== [], fn ($query) => $query->whereNotIn('id', $attached))
            ->orderBy('title')
            ->limit(250)
            ->get(['id', 'title', 'slug', 'type', 'status'])
            ->map(static fn (Document $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'slug' => $document->slug,
                'type' => $document->type,
                'status' => $document->status,
            ])->values();

        return Inertia::render('Admin/Collections/Show', [
            'collection' => $this->collectionPayload($collection, true),
            'documents' => $collection->documents->map(fn (Document $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'slug' => $document->slug,
                'type' => $document->type,
                'status' => $document->status,
                'position' => (int) $document->pivot->position,
                'data' => $this->pivotData($document->pivot->data),
            ])->values(),
            'availableDocuments' => $available,
            'types' => array_values(array_map(static fn ($type) => $type->toArray(), $this->types->all())),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCollection($request);
        $slug = $this->uniqueSlug((string) (($data['slug'] ?? null) ?: $data['name']));
        $schema = $this->schemas->normalize((array) ($data['schema'] ?? []));

        $collection = ContentCollection::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => trim((string) $data['name']),
            'slug' => $slug,
            'description' => ($data['description'] ?? null) ?: null,
            'status' => (string) $data['status'],
            'document_type' => ($data['document_type'] ?? null) ?: null,
            'schema' => $schema,
            'metadata' => [],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);
        $this->audit->record('content.collection.created', $collection, ['slug' => $collection->slug, 'fields' => count($schema)]);

        return redirect()->route('admin.collections.show', $collection)->with('success', 'Content collection created.');
    }

    public function update(Request $request, ContentCollection $collection): RedirectResponse
    {
        $data = $this->validatedCollection($request, $collection);
        $schema = $this->schemas->normalize((array) ($data['schema'] ?? []));
        $slug = $this->uniqueSlug((string) (($data['slug'] ?? null) ?: $data['name']), $collection);
        $nextType = ($data['document_type'] ?? null) ?: null;

        DB::transaction(function () use ($request, $collection, $data, $schema, $slug, $nextType): void {
            $entries = $collection->documents()->get();
            if ($nextType && $entries->contains(static fn (Document $document): bool => $document->type !== $nextType)) {
                throw ValidationException::withMessages(['document_type' => 'The selected document type is incompatible with one or more existing collection entries. Remove those entries before restricting the collection type.']);
            }
            foreach ($entries as $document) {
                $normalized = $this->schemas->normalizeEntry($this->pivotData($document->pivot->data), $schema);
                $collection->documents()->updateExistingPivot($document->id, ['data' => json_encode($normalized, JSON_THROW_ON_ERROR)]);
            }

            $collection->forceFill([
                'name' => trim((string) $data['name']),
                'slug' => $slug,
                'description' => ($data['description'] ?? null) ?: null,
                'status' => (string) $data['status'],
                'document_type' => $nextType,
                'schema' => $schema,
                'updated_by' => $request->user()?->id,
            ])->save();
        });

        $this->audit->record('content.collection.updated', $collection, ['slug' => $collection->slug, 'fields' => count($schema)]);
        return back()->with('success', 'Content collection updated.');
    }

    public function destroy(ContentCollection $collection): RedirectResponse
    {
        $name = $collection->name;
        $this->audit->record('content.collection.deleted', $collection, ['name' => $name, 'documents' => $collection->documents()->count()]);
        $collection->delete();
        return redirect()->route('admin.collections.index')->with('success', "Collection [{$name}] deleted. Documents were not deleted.");
    }

    public function attach(Request $request, ContentCollection $collection): RedirectResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'integer'],
            'data' => ['nullable', 'array'],
        ]);
        $document = Document::query()->findOrFail((int) $validated['document_id']);
        if ($collection->documents()->whereKey($document->id)->exists()) {
            throw ValidationException::withMessages(['document_id' => 'This document is already part of the collection.']);
        }
        $this->assertDocumentType($collection, $document);
        $data = $this->schemas->normalizeEntry((array) ($validated['data'] ?? []), (array) ($collection->schema ?? []));
        $position = ((int) $collection->documents()->max('nx_content_collection_documents.position')) + 10;

        $collection->documents()->attach($document->id, [
            'position' => $position,
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
        ]);
        $this->audit->record('content.collection.document.attached', $collection, ['document_id' => $document->id]);
        return back()->with('success', 'Document added to the collection.');
    }

    public function updateEntry(Request $request, ContentCollection $collection, Document $document): RedirectResponse
    {
        if (! $collection->documents()->whereKey($document->id)->exists()) {
            abort(404);
        }
        $validated = $request->validate(['data' => ['required', 'array']]);
        $data = $this->schemas->normalizeEntry((array) $validated['data'], (array) ($collection->schema ?? []));
        $collection->documents()->updateExistingPivot($document->id, ['data' => json_encode($data, JSON_THROW_ON_ERROR)]);
        $this->audit->record('content.collection.document.updated', $collection, ['document_id' => $document->id]);
        return back()->with('success', 'Collection entry fields updated.');
    }

    public function detach(ContentCollection $collection, Document $document): RedirectResponse
    {
        if (! $collection->documents()->whereKey($document->id)->exists()) {
            abort(404);
        }
        $collection->documents()->detach($document->id);
        $this->audit->record('content.collection.document.detached', $collection, ['document_id' => $document->id]);
        return back()->with('success', 'Document removed from the collection.');
    }

    /** @return array<string,mixed> */
    private function validatedCollection(Request $request, ?ContentCollection $collection = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'archived'])],
            'document_type' => ['nullable', Rule::in($this->types->keys())],
            'schema' => ['nullable', 'array', 'max:50'],
        ]);
    }

    private function uniqueSlug(string $source, ?ContentCollection $except = null): string
    {
        $slug = Str::slug($source);
        if ($slug === '') {
            $slug = 'collection-'.Str::lower(Str::random(8));
        }
        $query = ContentCollection::query()->where('slug', $slug);
        if ($except) $query->whereKeyNot($except->id);
        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'A content collection with this slug already exists in the current organization.']);
        }
        return $slug;
    }

    private function assertDocumentType(ContentCollection $collection, Document $document): void
    {
        if ($collection->document_type && $document->type !== $collection->document_type) {
            throw ValidationException::withMessages(['document_id' => "This collection only accepts {$collection->document_type} documents."]);
        }
    }

    /** @return array<string,mixed> */
    private function collectionPayload(ContentCollection $collection, bool $includeSchema = false): array
    {
        $payload = [
            'id' => $collection->id,
            'uuid' => $collection->uuid,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'status' => $collection->status,
            'document_type' => $collection->document_type,
            'documents_count' => (int) ($collection->documents_count ?? $collection->documents()->count()),
            'updated_at' => $collection->updated_at?->toIso8601String(),
        ];
        if ($includeSchema) $payload['schema'] = array_values((array) ($collection->schema ?? []));
        return $payload;
    }

    /** @return array<string,mixed> */
    private function pivotData(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (! is_string($value) || $value === '') return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
