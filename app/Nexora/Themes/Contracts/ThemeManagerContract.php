<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Contracts;

use App\Models\Theme;
use App\Models\ThemeVersion;

interface ThemeManagerContract
{
    public function active(): ?ThemeVersion;
    public function activate(ThemeVersion $version, ?int $userId = null, string $action = 'activate', ?string $reason = null): void;
    public function rollback(?int $userId = null): ?ThemeVersion;
    public function createPreviewToken(ThemeVersion $version, int $userId, int $minutes = 20): string;
    public function resolvePreviewToken(string $token, int $userId): ?ThemeVersion;
    /** @return array<string,mixed> */
    public function tokens(ThemeVersion $version): array;
    /** @param array<string,mixed> $values */
    public function updateTokens(Theme $theme, ThemeVersion $version, array $values, ?int $userId = null): void;
}
