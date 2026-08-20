<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Services;

use App\Models\DocumentRevision;

final class DocumentRevisionComparator
{
    /** @return array<string,mixed> */
    public function compare(DocumentRevision $from, DocumentRevision $to): array
    {
        $fromBlocks = $this->blocks($from->content);
        $toBlocks = $this->blocks($to->content);
        $ids = array_values(array_unique([...array_keys($fromBlocks), ...array_keys($toBlocks)]));
        $blocks = [];
        foreach ($ids as $id) {
            $before = $fromBlocks[$id] ?? null;
            $after = $toBlocks[$id] ?? null;
            $change = $before === null ? 'added' : ($after === null ? 'removed' : ($before === $after ? 'unchanged' : 'changed'));
            if ($change !== 'unchanged') {
                $blocks[] = ['id' => $id, 'change' => $change, 'before' => $before, 'after' => $after];
            }
        }

        return [
            'from' => $from->revision,
            'to' => $to->revision,
            'fields' => [
                ['field' => 'Title', 'before' => $from->title, 'after' => $to->title, 'changed' => $from->title !== $to->title],
                ['field' => 'Excerpt', 'before' => $from->excerpt, 'after' => $to->excerpt, 'changed' => $from->excerpt !== $to->excerpt],
                ['field' => 'Publication status', 'before' => $from->document_status, 'after' => $to->document_status, 'changed' => $from->document_status !== $to->document_status],
                ['field' => 'Editorial stage', 'before' => $from->workflow_status, 'after' => $to->workflow_status, 'changed' => $from->workflow_status !== $to->workflow_status],
            ],
            'blocks' => $blocks,
            'summary' => [
                'added' => count(array_filter($blocks, fn (array $block): bool => $block['change'] === 'added')),
                'removed' => count(array_filter($blocks, fn (array $block): bool => $block['change'] === 'removed')),
                'changed' => count(array_filter($blocks, fn (array $block): bool => $block['change'] === 'changed')),
            ],
        ];
    }

    /** @param mixed $content @return array<string,array<string,mixed>> */
    private function blocks(mixed $content): array
    {
        $content = is_array($content) ? $content : [];
        $blocks = [];
        foreach ((array) ($content['blocks'] ?? []) as $index => $block) {
            if (! is_array($block)) continue;
            $id = (string) ($block['id'] ?? "position-{$index}");
            $blocks[$id] = $block;
        }
        return $blocks;
    }
}
