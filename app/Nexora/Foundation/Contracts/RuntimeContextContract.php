<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

use App\Nexora\Foundation\Runtime\RuntimeIdentity;

interface RuntimeContextContract
{
    public function current(): RuntimeIdentity;

    /** @template T @param callable():T $callback @return T */
    public function runAs(RuntimeIdentity $identity, callable $callback): mixed;
}
