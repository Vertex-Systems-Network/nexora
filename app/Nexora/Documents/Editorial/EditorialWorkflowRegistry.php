<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Editorial;

use InvalidArgumentException;

final class EditorialWorkflowRegistry
{
    /** @var array<string,array{key:string,name:string,description:string,tone:string,terminal:bool}> */
    private array $states = [
        'idea' => ['key' => 'idea', 'name' => 'Idea', 'description' => 'A captured concept that is not yet being drafted.', 'tone' => 'neutral', 'terminal' => false],
        'draft' => ['key' => 'draft', 'name' => 'Draft', 'description' => 'Actively being written or edited.', 'tone' => 'neutral', 'terminal' => false],
        'review' => ['key' => 'review', 'name' => 'In review', 'description' => 'Ready for editorial review and feedback.', 'tone' => 'brand', 'terminal' => false],
        'changes_requested' => ['key' => 'changes_requested', 'name' => 'Changes requested', 'description' => 'Reviewer feedback must be addressed.', 'tone' => 'warning', 'terminal' => false],
        'approved' => ['key' => 'approved', 'name' => 'Approved', 'description' => 'Editorially approved and ready for publishing decisions.', 'tone' => 'success', 'terminal' => false],
        'scheduled' => ['key' => 'scheduled', 'name' => 'Scheduled', 'description' => 'Approved and waiting for a scheduled publishing time.', 'tone' => 'brand', 'terminal' => false],
        'published' => ['key' => 'published', 'name' => 'Published', 'description' => 'Published and visible through its renderer.', 'tone' => 'success', 'terminal' => false],
        'archived' => ['key' => 'archived', 'name' => 'Archived', 'description' => 'No longer active in the editorial pipeline.', 'tone' => 'neutral', 'terminal' => true],
    ];

    /** @var array<string,list<string>> */
    private array $transitions = [
        'idea' => ['draft', 'archived'],
        'draft' => ['idea', 'review', 'archived'],
        'review' => ['draft', 'changes_requested', 'approved', 'archived'],
        'changes_requested' => ['draft', 'review', 'archived'],
        'approved' => ['review', 'scheduled', 'published', 'archived'],
        'scheduled' => ['approved', 'published', 'archived'],
        'published' => ['draft', 'archived'],
        'archived' => ['draft'],
    ];

    /** @return list<array{key:string,name:string,description:string,tone:string,terminal:bool}> */
    public function all(): array
    {
        return array_values($this->states);
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->states);
    }

    /** @return list<array{key:string,name:string,description:string,tone:string,terminal:bool}> */
    public function availableFrom(string $current): array
    {
        $keys = array_values(array_unique([$current, ...($this->transitions[$current] ?? [])]));
        return array_values(array_filter(array_map(fn (string $key): ?array => $this->states[$key] ?? null, $keys)));
    }

    public function assertTransition(string $from, string $to): void
    {
        if ($from === $to) return;
        if (! isset($this->states[$to]) || ! in_array($to, $this->transitions[$from] ?? [], true)) {
            throw new InvalidArgumentException("Editorial transition from {$from} to {$to} is not allowed.");
        }
    }
}
