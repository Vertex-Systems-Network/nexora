<?php

declare(strict_types=1);

namespace App\Nexora\Admin\Navigation;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;

final class AdminNavigationRegistry implements AdminNavigationContract
{
    /** @var array<string, array<string, mixed>> */
    private array $items = [];

    public function register(array $item): void
    {
        $id = (string) ($item['id'] ?? '');

        if ($id === '') {
            throw new \InvalidArgumentException('Admin navigation items require a stable id.');
        }

        $this->items[$id] = $item;
    }

    public function all(): array
    {
        $items = array_values($this->items);

        usort($items, static fn (array $a, array $b): int => ($a['order'] ?? 1000) <=> ($b['order'] ?? 1000));

        return $items;
    }
}
