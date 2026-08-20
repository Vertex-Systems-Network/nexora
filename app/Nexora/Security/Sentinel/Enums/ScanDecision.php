<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Enums;

enum ScanDecision: string
{
    case Allow = 'allow';
    case Review = 'review';
    case Block = 'block';
}
