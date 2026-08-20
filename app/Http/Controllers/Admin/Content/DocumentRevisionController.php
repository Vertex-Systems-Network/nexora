<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Nexora\Documents\Services\DocumentRevisionComparator;
use App\Nexora\Documents\Services\DocumentRevisionManager;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentRevisionController extends Controller
{
    public function __construct(
        private DocumentRevisionComparator $comparator,
        private DocumentRevisionManager $revisions,
        private AuditManager $audit,
    ) {
    }

    public function index(Request $request, Document $document): Response
    {
        $items = $document->revisions()->with('creator:id,name')->latest('revision')->get();
        $selectedId = (int) $request->query('revision', $items->first()?->id ?? 0);
        $selected = $items->firstWhere('id', $selectedId) ?? $items->first();
        $compareId = (int) $request->query('compare', 0);
        $compare = $compareId > 0 ? $items->firstWhere('id', $compareId) : null;
        if ($selected && ! $compare) {
            $compare = $items->first(fn (DocumentRevision $revision): bool => $revision->revision === $selected->revision - 1);
        }

        return Inertia::render('Admin/Documents/Revisions', [
            'document' => ['id' => $document->id, 'title' => $document->title, 'lock_version' => (int) $document->lock_version],
            'revisions' => $items->map(fn (DocumentRevision $revision): array => $this->revisionPayload($revision))->values(),
            'selected' => $selected ? $this->revisionPayload($selected, true) : null,
            'compare' => $selected && $compare ? [
                'revision' => $this->revisionPayload($compare, true),
                'diff' => $this->comparator->compare($compare, $selected),
            ] : null,
        ]);
    }

    public function restore(Request $request, Document $document, DocumentRevision $revision): RedirectResponse
    {
        $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        if ((int) $request->integer('lock_version') !== (int) $document->lock_version) {
            return back()->withErrors(['revision' => 'The document changed before restore. Reload revision history and try again.']);
        }

        $restored = $this->revisions->restore($document, $revision, $request->user()?->id, (int) $request->integer('lock_version'));
        $this->audit->record('document.revision_restored', $restored, ['restored_revision' => $revision->revision]);
        return redirect()->route('admin.documents.edit', $restored)->with('success', "Revision {$revision->revision} restored as a new revision.");
    }

    /** @return array<string,mixed> */
    private function revisionPayload(DocumentRevision $revision, bool $includeContent = false): array
    {
        $payload = [
            'id' => $revision->id,
            'revision' => $revision->revision,
            'title' => $revision->title,
            'excerpt' => $revision->excerpt,
            'document_status' => $revision->document_status,
            'workflow_status' => $revision->workflow_status,
            'creator' => $revision->creator?->name,
            'created_at' => $revision->created_at?->toIso8601String(),
        ];
        if ($includeContent) {
            $payload['content'] = $revision->content;
            $payload['metadata'] = $revision->metadata;
        }
        return $payload;
    }
}
