<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

interface AdminNavigationContract
{
    /** @param array<string, mixed> $item */
    public function register(array $item): void;

    /** @return array<int, array<string, mixed>> */
    public function all(): array;
}
