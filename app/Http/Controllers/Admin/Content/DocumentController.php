<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Nexora\Documents\Blocks\BlockRegistry;
use App\Nexora\Documents\Contracts\DocumentRepositoryContract;
use App\Nexora\Documents\Editorial\EditorialWorkflowRegistry;
use App\Nexora\Documents\Services\DocumentAutosaveManager;
use App\Nexora\Documents\Types\DocumentTypeRegistry;
use App\Nexora\Enterprise\Services\TenantMemberDirectory;
use App\Nexora\Enterprise\Validation\TenantMemberExists;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class DocumentController extends Controller
{
    public function __construct(
        private DocumentRepositoryContract $documents,
        private DocumentTypeRegistry $types,
        private BlockRegistry $blocks,
        private EditorialWorkflowRegistry $workflow,
        private DocumentAutosaveManager $autosaves,
        private AuditManager $audit,
        private TenantMemberDirectory $tenantMembers,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'type' => (string) $request->query('type', ''),
        ];
        $paginator = $this->documents->paginate($filters, 20);
        $paginator->through(static fn (Document $document): array => [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'title' => $document->title,
            'slug' => $document->slug,
            'type' => $document->type,
            'status' => $document->status,
            'workflow_status' => $document->workflow_status,
            'author' => $document->author?->name,
            'revisions_count' => $document->revisions_count,
            'published_at' => $document->published_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Documents/Index', [
            'documents' => $paginator,
            'filters' => $filters,
            'types' => array_values(array_map(static fn ($type) => $type->toArray(), $this->types->all())),
            'workflowStates' => $this->workflow->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $requestedType = (string) $request->query('type', '');
        $initialType = in_array($requestedType, $this->types->keys(), true) ? $requestedType : null;
        return Inertia::render('Admin/Documents/Form', [
            'document' => null,
            'initialType' => $initialType,
            'types' => array_values(array_map(static fn ($type) => $type->toArray(), $this->types->all())),
            'blocks' => array_values(array_map(static fn ($block) => $block->toArray(), $this->blocks->all())),
            'workflowStates' => $this->workflow->all(),
            'people' => $this->people(),
            'reviewComments' => [],
            'mediaAssets' => $this->mediaAssets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDocument($request);
        $document = $this->documents->create($validated, $request->user()?->id);
        $this->audit->record('document.created', $document, ['type' => $document->type, 'status' => $document->status, 'workflow_status' => $document->workflow_status]);

        return redirect()->route('admin.documents.edit', $document)->with('success', 'Document created. Revision 1 has been recorded.');
    }

    public function edit(Request $request, Document $document): Response
    {
        $document->load(['assignee:id,name', 'reviewer:id,name']);
        $latestAutosave = $document->autosaves()->where('user_id', $request->user()?->id)->first();
        $comments = $document->reviewComments()->with(['user:id,name', 'resolver:id,name'])->latest()->limit(30)->get();

        return Inertia::render('Admin/Documents/Form', [
            'document' => [
                'id' => $document->id,
                'uuid' => $document->uuid,
                'title' => $document->title,
                'slug' => $document->slug,
                'type' => $document->type,
                'status' => $document->status,
                'workflow_status' => $document->workflow_status,
                'assigned_to' => $document->assigned_to,
                'reviewer_id' => $document->reviewer_id,
                'review_due_at' => $document->review_due_at?->format('Y-m-d\TH:i'),
                'excerpt' => $document->excerpt,
                'content' => $document->content ?? ['version' => 1, 'blocks' => []],
                'revisions_count' => $document->revisions()->count(),
                'lock_version' => (int) $document->lock_version,
                'updated_at' => $document->updated_at?->toIso8601String(),
                'autosaved_at' => $document->autosaved_at?->toIso8601String(),
            ],
            'types' => array_values(array_map(static fn ($type) => $type->toArray(), $this->types->all())),
            'blocks' => array_values(array_map(static fn ($block) => $block->toArray(), $this->blocks->all())),
            'workflowStates' => $this->workflow->availableFrom((string) $document->workflow_status),
            'people' => $this->people(),
            'latestAutosave' => $latestAutosave ? [
                'saved_at' => $latestAutosave->saved_at?->toIso8601String(),
                'base_lock_version' => $latestAutosave->base_lock_version,
                'base_revision' => $latestAutosave->base_revision,
                'title' => $latestAutosave->title,
                'slug' => $latestAutosave->slug,
                'excerpt' => $latestAutosave->excerpt,
                'content' => $latestAutosave->content,
                'workflow_status' => $latestAutosave->workflow_status,
            ] : null,
            'mediaAssets' => $this->mediaAssets(),
            'reviewComments' => $comments->map(static fn ($comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'status' => $comment->status,
                'author' => $comment->user?->name ?? 'Former user',
                'created_at' => $comment->created_at?->toIso8601String(),
                'resolved_by' => $comment->resolver?->name,
                'resolved_at' => $comment->resolved_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $this->validateDocument($request, $document);
        if ((int) ($validated['lock_version'] ?? 0) !== (int) $document->lock_version) {
            return back()->withErrors(['document' => 'This document was updated in another session. Reload before saving to avoid overwriting newer work.']);
        }

        try {
            $this->workflow->assertTransition((string) $document->workflow_status, (string) $validated['workflow_status']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['workflow_status' => $exception->getMessage()]);
        }

        $updated = $this->documents->update($document, $validated, $request->user()?->id);
        if ($request->user()) {
            $this->autosaves->clear($updated, (int) $request->user()->id);
        }
        $this->audit->record('document.updated', $updated, ['type' => $updated->type, 'status' => $updated->status, 'workflow_status' => $updated->workflow_status]);

        return back()->with('success', 'Document saved and a new revision snapshot was recorded.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->audit->record('document.deleted', $document, ['uuid' => $document->uuid, 'title' => $document->title]);
        $this->documents->delete($document);
        return redirect()->route('admin.documents.index')->with('success', 'Document deleted.');
    }

    /** @return array<string,mixed> */
    private function validateDocument(Request $request, ?Document $document = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('nx_documents', 'slug')->ignore($document?->id),
            ],
            'type' => ['required', 'string', Rule::in($this->types->keys())],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'workflow_status' => ['required', 'string', Rule::in($this->workflow->keys())],
            'assigned_to' => ['nullable', 'integer', new TenantMemberExists()],
            'reviewer_id' => ['nullable', 'integer', new TenantMemberExists()],
            'review_due_at' => ['nullable', 'date'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'array'],
            'lock_version' => [$document ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);
    }

    /** @return list<array{id:int,name:string,url:string,alt_text:?string,width:?int,height:?int}> */
    private function mediaAssets(): array
    {
        return MediaAsset::query()->where('media_type', 'image')->latest('id')->limit(300)->get(['id','uuid','title','original_name','alt_text','width','height'])
            ->map(static fn (MediaAsset $asset): array => [
                'id'=>(int) $asset->id,
                'name'=>(string) ($asset->title ?: $asset->original_name),
                'url'=>url('/media/'.$asset->uuid),
                'alt_text'=>$asset->alt_text,
                'width'=>$asset->width,
                'height'=>$asset->height,
            ])->values()->all();
    }

    /** @return list<array{id:number,name:string,email:string}> */
    private function people(): array
    {
        return $this->tenantMembers->activeUsers()
            ->take(250)
            ->map(static fn ($user): array => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])
            ->values()
            ->all();
    }
}
