<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Data;

final readonly class StudioElementDefinition
{
    /**
     * @param array<string,mixed> $defaultProps
     * @param array<string,mixed> $defaultStyles
     * @param list<string> $bindableProps
     */
    public function __construct(
        public string $type,
        public string $name,
        public string $category,
        public string $icon,
        public bool $acceptsChildren = false,
        public array $defaultProps = [],
        public array $defaultStyles = [],
        public array $bindableProps = [],
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'category' => $this->category,
            'icon' => $this->icon,
            'acceptsChildren' => $this->acceptsChildren,
            'defaultProps' => $this->defaultProps,
            'defaultStyles' => $this->defaultStyles,
            'bindableProps' => $this->bindableProps,
        ];
    }
}
