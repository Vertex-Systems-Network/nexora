<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

enum CapabilityRisk: string
{
    case Normal = 'normal';
    case Sensitive = 'sensitive';
    case Critical = 'critical';
}
