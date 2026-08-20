<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

final readonly class ModuleDependency
{
    public function __construct(
        public string $identifier,
        public string $constraint = '*',
        public bool $optional = false,
    ) {
    }

    /** @return array{identifier:string,constraint:string,optional:bool} */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'constraint' => $this->constraint,
            'optional' => $this->optional,
        ];
    }
}
