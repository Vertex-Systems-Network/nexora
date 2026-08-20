<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Schema;

use InvalidArgumentException;

final class SchemaGraph
{
    /** @var array<string,SchemaNode> */
    private array $nodes = [];

    public function add(SchemaNode $node): void
    {
        $existing = $this->nodes[$node->id] ?? null;
        if ($existing && $existing->type !== $node->type) {
            throw new InvalidArgumentException("Schema node [{$node->id}] is already registered as [{$existing->type}] and cannot be silently replaced by [{$node->type}].");
        }
        if (! $existing) {
            $this->nodes[$node->id] = $node;
            return;
        }
        if ($node->priority >= $existing->priority) {
            $this->nodes[$node->id] = new SchemaNode(
                id: $node->id,
                type: $node->type,
                properties: array_replace_recursive($existing->properties, $node->properties),
                source: $node->source,
                priority: $node->priority,
            );
        }
    }

    /** @return list<SchemaNode> */
    public function nodes(): array
    {
        $nodes = array_values($this->nodes);
        usort($nodes, static fn (SchemaNode $a, SchemaNode $b): int => $a->priority <=> $b->priority);
        return $nodes;
    }

    /** @return array{'@context':string,'@graph':list<array<string,mixed>>} */
    public function toArray(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_map(static fn (SchemaNode $node): array => $node->toArray(), $this->nodes()),
        ];
    }
}
