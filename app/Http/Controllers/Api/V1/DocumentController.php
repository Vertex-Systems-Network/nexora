<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 25)));
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type', ''));

        if ($status !== '' && ! in_array($status, ['draft', 'published', 'archived'], true)) {
            return $this->validationError('status', 'Unsupported document status filter.');
        }
        if (strlen($type) > 80) {
            return $this->validationError('type', 'Document type filter is too long.');
        }

        $query = Document::query()
            ->select([
                'id', 'uuid', 'title', 'slug', 'type', 'status', 'workflow_status', 'excerpt',
                'content', 'metadata', 'published_at', 'created_at', 'updated_at',
            ])
            ->orderBy('id');

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($type !== '') {
            $query->where('type', $type);
        }

        $page = $query->cursorPaginate($perPage);

        return response()->json([
            'api_version' => 'v1',
            'data' => collect($page->items())->map(static fn (Document $document): array => self::resource($document))->values(),
            'pagination' => [
                'per_page' => $perPage,
                'next_cursor' => $page->nextCursor()?->encode(),
                'has_more' => $page->hasMorePages(),
            ],
        ]);
    }

    public function show(string $document): JsonResponse
    {
        // Deliberately re-resolve after API-token middleware has installed the token tenant.
        // Do not use implicit model binding here because route binding may execute before
        // the stateless API tenant/auth context is established.
        $resolved = Document::query()
            ->select([
                'id', 'uuid', 'title', 'slug', 'type', 'status', 'workflow_status', 'excerpt',
                'content', 'metadata', 'published_at', 'created_at', 'updated_at',
            ])
            ->whereKey($document)
            ->firstOrFail();

        return response()->json([
            'api_version' => 'v1',
            'data' => self::resource($resolved),
        ]);
    }

    /** @return array<string,mixed> */
    private static function resource(Document $document): array
    {
        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'title' => $document->title,
            'slug' => $document->slug,
            'type' => $document->type,
            'status' => $document->status,
            'workflow_status' => $document->workflow_status,
            'excerpt' => $document->excerpt,
            'content' => $document->content,
            'metadata' => $document->metadata,
            'published_at' => $document->published_at?->toIso8601String(),
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ];
    }

    private function validationError(string $field, string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'validation_error',
                'message' => 'The request contains an invalid filter.',
                'fields' => [$field => [$message]],
            ],
        ], 422);
    }
}
