<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use InvalidArgumentException;

final readonly class CapabilityDefinition
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $group,
        public CapabilityRisk $risk = CapabilityRisk::Normal,
        public string $description = '',
    ) {
        if (! preg_match('/^[a-z0-9]+(?:[._:-][a-z0-9]+)+$/', $slug)) {
            throw new InvalidArgumentException("Invalid capability slug [{$slug}].");
        }
    }

    /** @return array{slug:string,name:string,group:string,risk_level:string,description:string} */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'group' => $this->group,
            'risk_level' => $this->risk->value,
            'description' => $this->description,
        ];
    }
}
