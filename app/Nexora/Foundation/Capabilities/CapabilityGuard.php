<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Capabilities;

use App\Nexora\Foundation\Contracts\CapabilityGuardContract;
use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use App\Nexora\Foundation\Contracts\RuntimeContextContract;
use App\Nexora\Foundation\Exceptions\CapabilityDeniedException;

final readonly class CapabilityGuard implements CapabilityGuardContract
{
    public function __construct(
        private RuntimeContextContract $context,
        private ModuleRegistryContract $modules,
        private CapabilityRegistryContract $capabilities,
    ) {
    }

    public function allows(string $capability): bool
    {
        if (! $this->capabilities->has($capability)) {
            return false;
        }

        $identity = $this->context->current();
        if ($identity->type === 'platform') {
            return true;
        }

        if ($identity->type !== 'module') {
            return false;
        }

        $manifest = $this->modules->manifest($identity->identifier);

        return $manifest !== null && in_array($capability, $manifest->capabilities, true);
    }

    public function authorize(string $capability): void
    {
        if (! $this->allows($capability)) {
            $identity = $this->context->current();
            throw new CapabilityDeniedException("Runtime [{$identity->identifier}] is not allowed to use capability [{$capability}].");
        }
    }
}
