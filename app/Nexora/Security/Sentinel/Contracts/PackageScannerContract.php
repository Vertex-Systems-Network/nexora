<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Contracts;

use App\Nexora\Security\Sentinel\Data\ScanReport;

interface PackageScannerContract
{
    public function scan(string $path): ScanReport;
}
