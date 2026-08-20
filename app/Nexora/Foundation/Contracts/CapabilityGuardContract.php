<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

interface CapabilityGuardContract
{
    public function allows(string $capability): bool;

    public function authorize(string $capability): void;
}
