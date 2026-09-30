<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DocumentReviewController extends Controller
{
    public function __construct(private AuditManager $audit)
    {
    }

    public function store(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $comment = $document->reviewComments()->create([
            'tenant_id' => $document->tenant_id,
            'revision_id' => $document->revisions()->latest('revision')->value('id'),
            'user_id' => $request->user()?->id,
            'body' => trim((string) $validated['body']),
            'status' => 'open',
        ]);
        $this->audit->record('document.review_comment_added', $document, ['comment_id' => $comment->id]);
        return back()->with('success', 'Review comment added.');
    }

    public function resolve(Request $request, Document $document, DocumentReviewComment $comment): RedirectResponse
    {
        abort_unless((int) $comment->document_id === (int) $document->id, 404);
        $comment->forceFill([
            'status' => 'resolved',
            'resolved_by' => $request->user()?->id,
            'resolved_at' => now(),
        ])->save();
        $this->audit->record('document.review_comment_resolved', $document, ['comment_id' => $comment->id]);
        return back()->with('success', 'Review comment resolved.');
    }
}
