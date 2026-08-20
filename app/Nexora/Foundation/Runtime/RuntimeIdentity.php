<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

final readonly class RuntimeIdentity
{
    public function __construct(
        public string $type,
        public string $identifier,
    ) {
    }

    public static function platform(): self
    {
        return new self('platform', 'nexora.platform');
    }

    public static function module(string $identifier): self
    {
        return new self('module', $identifier);
    }
}
