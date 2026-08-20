<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Contracts;

use App\Models\ThemeVersion;

interface ThemeRendererContract
{
    /** @param array<string,mixed> $payload */
    public function render(string $template, array $payload = [], ?ThemeVersion $version = null): string;
}
