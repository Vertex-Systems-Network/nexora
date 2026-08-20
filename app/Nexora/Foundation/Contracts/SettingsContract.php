<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

interface SettingsContract
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;
}
