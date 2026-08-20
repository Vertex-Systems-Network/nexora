<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Contracts;

use App\Models\StudioCanvas;
use App\Models\StudioComponent;

interface StudioManagerContract
{
    /** @param array<string,mixed> $data */
    public function create(array $data, ?int $userId): StudioCanvas;

    /** @param array<string,mixed> $data */
    public function update(StudioCanvas $canvas, array $data, ?int $userId): StudioCanvas;

    public function publish(StudioCanvas $canvas, ?int $userId): StudioCanvas;

    public function unpublish(StudioCanvas $canvas, ?int $userId): StudioCanvas;

    /** @param array<string,mixed> $node */
    public function saveComponent(string $name, array $node, ?int $userId): StudioComponent;
}
