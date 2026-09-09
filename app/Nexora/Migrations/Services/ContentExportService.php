<?php

declare(strict_types=1);

namespace App\Nexora\Migrations\Services;

use App\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ContentExportService
{
    public function documents(): StreamedResponse
    {
        $filename = 'nexora-documents-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function (): void {
            echo '{"schema":"nexora.documents.export.v1","generated_at":';
            echo json_encode(now()->toIso8601String(), JSON_THROW_ON_ERROR);
            echo ',"documents":[';

            $first = true;
            Document::query()
                ->select([
                    'id', 'uuid', 'type', 'status', 'title', 'slug', 'excerpt', 'content', 'metadata',
                    'schema_version', 'workflow_status', 'published_at', 'created_at', 'updated_at',
                ])
                ->orderBy('id')
                ->chunkById(100, function ($documents) use (&$first): void {
                    foreach ($documents as $document) {
                        if (! $first) {
                            echo ',';
                        }
                        $first = false;
                        echo json_encode([
                            'uuid' => $document->uuid,
                            'type' => $document->type,
                            'status' => $document->status,
                            'title' => $document->title,
                            'slug' => $document->slug,
                            'excerpt' => $document->excerpt,
                            'content' => $document->content,
                            'metadata' => $document->metadata,
                            'schema_version' => (int) $document->schema_version,
                            'workflow_status' => $document->workflow_status,
                            'published_at' => $document->published_at?->toIso8601String(),
                            'created_at' => $document->created_at?->toIso8601String(),
                            'updated_at' => $document->updated_at?->toIso8601String(),
                        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    }
                });

            echo ']}';
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
