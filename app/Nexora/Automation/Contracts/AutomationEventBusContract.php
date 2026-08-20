<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Contracts;

use App\Models\AutomationEvent;

interface AutomationEventBusContract
{
    public function emit(string $eventKey, array $payload = [], ?string $sourceType = null, string|int|null $sourceId = null, ?string $idempotencyKey = null): ?AutomationEvent;
}
