<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Services;

final class StudioBindingRegistry
{
    /** @var array<string,array{key:string,label:string,group:string,description:string}> */
    private array $bindings = [];

    public function register(string $key, string $label, string $group, string $description = ''): void
    {
        if ($key === '' || isset($this->bindings[$key])) {
            throw new \InvalidArgumentException("Invalid or duplicate Studio binding [{$key}].");
        }
        $this->bindings[$key] = compact('key', 'label', 'group', 'description');
    }

    /** @return list<array{key:string,label:string,group:string,description:string}> */
    public function all(): array
    {
        return array_values($this->bindings);
    }

    public function exists(string $key): bool
    {
        return isset($this->bindings[$key]);
    }
}
