<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Services;

use App\Nexora\Documents\Blocks\BlockRegistry;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class DocumentContentValidator
{
    public function __construct(private BlockRegistry $blocks)
    {
    }

    /**
     * Normalize and validate the canonical Nexora document tree.
     *
     * @param array<string,mixed> $content
     * @return array{version:int,blocks:list<array<string,mixed>>}
     */
    public function normalize(array $content): array
    {
        $version = max(1, (int) ($content['version'] ?? 1));
        $rawBlocks = $content['blocks'] ?? [];
        if (! is_array($rawBlocks)) {
            throw new InvalidArgumentException('Document content blocks must be an array.');
        }

        $normalized = [];
        foreach (array_values($rawBlocks) as $index => $block) {
            if (! is_array($block)) {
                throw new InvalidArgumentException("Document block at index {$index} must be an object-like array.");
            }
            $type = trim((string) ($block['type'] ?? ''));
            if ($type === '' || ! $this->blocks->has($type)) {
                throw new InvalidArgumentException("Unknown or missing document block type [{$type}] at index {$index}.");
            }
            $data = $block['data'] ?? [];
            if (! is_array($data)) {
                throw new InvalidArgumentException("Document block data for [{$type}] must be an object-like array.");
            }
            $children = $block['children'] ?? [];
            if (! is_array($children)) {
                throw new InvalidArgumentException("Document block children for [{$type}] must be an array.");
            }

            $normalized[] = [
                'id' => $this->normalizeId((string) ($block['id'] ?? '')),
                'type' => $type,
                'version' => max(1, (int) ($block['version'] ?? 1)),
                'data' => $data,
                'children' => array_values($children),
            ];
        }

        return ['version' => $version, 'blocks' => $normalized];
    }

    private function normalizeId(string $id): string
    {
        $id = trim($id);
        if ($id !== '' && preg_match('/^[A-Za-z0-9_-]{8,80}$/', $id) === 1) {
            return $id;
        }

        return (string) Str::uuid();
    }
}
