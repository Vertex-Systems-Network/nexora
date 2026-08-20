<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Data;

use App\Nexora\Security\Sentinel\Enums\FindingSeverity;

final readonly class SecurityFinding
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $ruleId,
        public FindingSeverity $severity,
        public string $category,
        public string $title,
        public string $message,
        public ?string $filePath = null,
        public ?int $lineStart = null,
        public ?int $lineEnd = null,
        public ?string $excerpt = null,
        public bool $hardBlock = false,
        public array $metadata = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rule_id' => $this->ruleId,
            'severity' => $this->severity->value,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'file_path' => $this->filePath,
            'line_start' => $this->lineStart,
            'line_end' => $this->lineEnd,
            'excerpt' => $this->excerpt,
            'hard_block' => $this->hardBlock,
            'metadata' => $this->metadata,
        ];
    }
}
