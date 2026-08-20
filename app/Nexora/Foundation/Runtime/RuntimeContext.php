<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use App\Nexora\Foundation\Contracts\RuntimeContextContract;

final class RuntimeContext implements RuntimeContextContract
{
    /** @var array<int, RuntimeIdentity> */
    private array $stack = [];

    public function current(): RuntimeIdentity
    {
        return $this->stack[array_key_last($this->stack)] ?? RuntimeIdentity::platform();
    }

    public function runAs(RuntimeIdentity $identity, callable $callback): mixed
    {
        $this->stack[] = $identity;

        try {
            return $callback();
        } finally {
            array_pop($this->stack);
        }
    }
}
