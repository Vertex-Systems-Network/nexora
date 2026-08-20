<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

use App\Nexora\Foundation\Runtime\ModuleManifest;

interface ModuleContract
{
    public function manifest(): ModuleManifest;

    /** Register bindings, capabilities, navigation and other definitions. */
    public function register(): void;

    /** Boot runtime behavior after all modules have registered. */
    public function boot(): void;
}
