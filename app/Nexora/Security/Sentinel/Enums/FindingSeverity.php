<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Enums;

enum FindingSeverity: string
{
    case Info = 'info';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function weight(): int
    {
        return match ($this) {
            self::Info => 0,
            self::Low => 4,
            self::Medium => 12,
            self::High => 30,
            self::Critical => 60,
        };
    }
}
