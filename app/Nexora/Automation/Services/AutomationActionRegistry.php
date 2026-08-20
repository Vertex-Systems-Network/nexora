<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use InvalidArgumentException;

final class AutomationActionRegistry
{
    /** @var array<string,array<string,mixed>> */
    private array $items=[];

    public function __construct()
    {
        foreach ([
            ['key'=>'admin.notification','label'=>'Create Admin notification','group'=>'Admin','description'=>'Creates a notification for a selected Nexora user.'],
            ['key'=>'webhook.send','label'=>'Send signed webhook','group'=>'Integration','description'=>'Queues a signed HMAC webhook delivery to an approved destination.'],
            ['key'=>'audit.record','label'=>'Write audit event','group'=>'System','description'=>'Appends a structured entry to the Nexora audit trail.'],
        ] as $definition) $this->register($definition);
    }

    /** @param array<string,mixed> $definition */
    public function register(array $definition): void
    {
        $key=trim((string)($definition['key']??''));
        if ($key==='' || ! preg_match('/^[a-z0-9][a-z0-9._-]+$/',$key)) throw new InvalidArgumentException('Automation action requires a stable dotted key.');
        if (isset($this->items[$key])) throw new InvalidArgumentException('Automation action already registered: '.$key);
        $this->items[$key]=$definition+['key'=>$key,'label'=>$key,'group'=>'Extension','description'=>''];
    }

    /** @return array<string,array<string,mixed>> */
    public function all(): array { return $this->items; }
    public function has(string $key): bool { return isset($this->items[$key]); }
}
