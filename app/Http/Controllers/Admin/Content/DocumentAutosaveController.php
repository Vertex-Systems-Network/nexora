<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Nexora\Documents\Editorial\EditorialWorkflowRegistry;
use App\Nexora\Documents\Services\DocumentAutosaveManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DocumentAutosaveController extends Controller
{
    public function __construct(
        private DocumentAutosaveManager $autosaves,
        private EditorialWorkflowRegistry $workflow,
    ) {
    }

    public function __invoke(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'base_lock_version' => ['required', 'integer', 'min:1'],
            'base_revision' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'array'],
            'metadata' => ['nullable', 'array'],
            'workflow_status' => ['required', 'string', Rule::in($this->workflow->keys())],
        ]);

        $serverRevision = (int) $document->revisions()->max('revision');
        if ((int) $validated['base_lock_version'] !== (int) $document->lock_version || (int) $validated['base_revision'] !== $serverRevision) {
            return response()->json([
                'status' => 'conflict',
                'message' => 'This document changed on the server. Reload or save a new revision before continuing.',
                'server' => [
                    'lock_version' => (int) $document->lock_version,
                    'revision' => $serverRevision,
                    'updated_at' => $document->updated_at?->toIso8601String(),
                ],
            ], 409);
        }

        $autosave = $this->autosaves->store($document, (int) $request->user()->id, $validated);
        return response()->json([
            'status' => 'saved',
            'saved_at' => $autosave->saved_at?->toIso8601String(),
            'base_lock_version' => $autosave->base_lock_version,
            'base_revision' => $autosave->base_revision,
        ]);
    }
}
