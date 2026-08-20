<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Contracts;

interface DistributionAdapterContract
{
    public function key(): string;
    public function name(): string;
    public function description(): string;
    public function available(): bool;
    /** @return array<string,mixed> */
    public function status(): array;
}
