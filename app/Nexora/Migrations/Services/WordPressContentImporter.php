<?php

declare(strict_types=1);

namespace App\Nexora\Migrations\Services;

use App\Models\ContentMigrationItem;
use App\Models\ContentMigrationRun;
use App\Models\Document;
use App\Nexora\Documents\Contracts\DocumentRepositoryContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final readonly class WordPressContentImporter
{
    public function __construct(private DocumentRepositoryContract $documents) {}

    /** @param array<string,mixed> $source */
    public function import(ContentMigrationRun $run, array $source): string
    {
        $sourceKey = trim((string) ($source['source_key'] ?? ''));
        if ($sourceKey === '') {
            throw new RuntimeException('WordPress migration item is missing its source identity.');
        }

        $canonical = json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $sourceHash = hash('sha256', $canonical);

        return DB::transaction(function () use ($run, $source, $sourceKey, $sourceHash): string {
            $item = ContentMigrationItem::query()->firstOrCreate(
                ['migration_run_id' => $run->id, 'source_key' => $sourceKey],
                [
                    'tenant_id' => $run->tenant_id,
                    'source_kind' => trim((string) ($source['post_type'] ?? '')),
                    'source_hash' => $sourceHash,
                    'status' => 'pending',
                ],
            );
            $item = ContentMigrationItem::query()->lockForUpdate()->findOrFail($item->id);

            if ($item->status === 'imported' && $item->destination_id !== null) {
                return 'skipped';
            }

            try {
                $attributes = $this->mapDocument($source, $run);
                $document = $this->documents->create($attributes, $run->created_by ? (int) $run->created_by : null);

                $item->forceFill([
                    'source_hash' => $sourceHash,
                    'status' => 'imported',
                    'destination_type' => 'document',
                    'destination_id' => (string) $document->id,
                    'metadata' => [
                        'document_uuid' => $document->uuid,
                        'document_type' => $document->type,
                    ],
                    'error_code' => null,
                ])->save();

                return 'imported';
            } catch (Throwable $exception) {
                $item->forceFill([
                    'status' => 'failed',
                    'error_code' => $this->errorCode($exception),
                    'metadata' => ['message' => 'The source item could not be imported.'],
                ])->save();
                throw $exception;
            }
        }, 3);
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private function mapDocument(array $source, ContentMigrationRun $run): array
    {
        $rawContent = (string) ($source['content'] ?? '');
        if (strlen($rawContent) > 2_097_152) {
            throw new RuntimeException('A WordPress item exceeds the 2 MB per-item content limit.');
        }

        $postType = (string) ($source['post_type'] ?? '');
        $type = $postType === 'post' ? 'blog_post' : 'document';
        $wordpressStatus = strtolower(trim((string) ($source['status'] ?? 'draft')));
        $status = match ($wordpressStatus) {
            'publish' => 'published',
            'trash' => 'archived',
            default => 'draft',
        };
        $workflow = $status === 'published' ? 'published' : ($status === 'archived' ? 'archived' : 'draft');

        $title = trim((string) ($source['title'] ?? ''));
        $title = $title !== '' ? mb_substr($title, 0, 255) : 'Imported WordPress content';
        $slug = $this->uniqueSlug((string) ($source['post_name'] ?? ''), $title, (string) ($source['post_id'] ?? ''));
        $excerpt = trim(strip_tags((string) ($source['excerpt'] ?? '')));
        $publishedAt = $this->publishedAt((string) ($source['post_date'] ?? ''));

        return [
            'type' => $type,
            'status' => $status,
            'workflow_status' => $workflow,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt !== '' ? mb_substr($excerpt, 0, 1000) : null,
            'content' => [
                'version' => 1,
                'blocks' => $this->textBlocks($rawContent),
            ],
            'metadata' => [
                'migration' => [
                    'engine' => 'wordpress-wxr-v1',
                    'run_id' => $run->id,
                    'source_key' => (string) ($source['source_key'] ?? ''),
                    'source_post_id' => (string) ($source['post_id'] ?? ''),
                    'source_url' => $this->safeHttpUrl((string) ($source['link'] ?? '')),
                    'source_guid' => mb_substr(trim((string) ($source['guid'] ?? '')), 0, 1000),
                    'source_creator' => mb_substr(trim((string) ($source['creator'] ?? '')), 0, 255),
                    'source_status' => mb_substr($wordpressStatus, 0, 40),
                    'terms' => array_slice((array) ($source['terms'] ?? []), 0, 250),
                    'remote_media_fetch' => false,
                ],
            ],
            'published_at' => $status === 'published' ? $publishedAt : null,
        ];
    }

    /** @return list<array<string,mixed>> */
    private function textBlocks(string $html): array
    {
        $normalized = preg_replace('/<(?:br\s*\/?|\/p|\/div|\/li|\/h[1-6])\s*>/i', "\n\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $parts = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $blocks = [];

        foreach (array_slice($parts, 0, 500) as $part) {
            $part = preg_replace('/[\t ]+/u', ' ', trim($part)) ?? trim($part);
            if ($part === '') {
                continue;
            }
            $blocks[] = [
                'id' => (string) Str::uuid(),
                'type' => 'paragraph',
                'version' => 1,
                'data' => ['text' => mb_substr($part, 0, 20_000)],
                'children' => [],
            ];
        }

        return $blocks;
    }

    private function uniqueSlug(string $sourceSlug, string $title, string $postId): string
    {
        $base = Str::slug($sourceSlug !== '' ? $sourceSlug : $title);
        $base = $base !== '' ? mb_substr($base, 0, 220) : 'wordpress-item';
        $slug = $base;
        $suffix = $postId !== '' ? '-wp'.preg_replace('/\D+/', '', $postId) : '-'.substr(hash('sha256', $title), 0, 8);
        $attempt = 0;

        while (Document::query()->where('slug', $slug)->exists()) {
            $attempt++;
            $slug = mb_substr($base, 0, 220).$suffix.($attempt > 1 ? '-'.$attempt : '');
            if ($attempt >= 100) {
                throw new RuntimeException('Unable to allocate a unique slug for the imported WordPress item.');
            }
        }

        return mb_substr($slug, 0, 255);
    }

    private function publishedAt(string $value): mixed
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return now();
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (Throwable) {
            return now();
        }
    }

    private function safeHttpUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2000 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    private function errorCode(Throwable $exception): string
    {
        return 'import_'.substr(hash('sha256', $exception::class.'|'.$exception->getMessage()), 0, 16);
    }
}
