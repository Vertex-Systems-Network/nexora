<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Services;

use App\Nexora\Distribution\Contracts\DistributionAdapterContract;

final class NewsletterDistributionAdapter implements DistributionAdapterContract
{
    public function key(): string { return 'newsletter'; }
    public function name(): string { return 'Nexora Newsletter'; }
    public function description(): string { return 'Queue email campaigns to consented subscribers using the configured Laravel mail transport.'; }
    public function available(): bool { return (string) config('mail.default', '') !== ''; }
    public function status(): array { return ['key'=>$this->key(),'name'=>$this->name(),'description'=>$this->description(),'available'=>$this->available(),'transport'=>(string) config('mail.default', 'not configured')]; }
}
