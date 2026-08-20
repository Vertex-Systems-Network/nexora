<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Services;

use App\Nexora\Distribution\Contracts\DistributionAdapterContract;

final class RssDistributionAdapter implements DistributionAdapterContract
{
    public function key(): string { return 'rss'; }
    public function name(): string { return 'RSS 2.0'; }
    public function description(): string { return 'Public RSS feed generated from published Articles and Blog posts.'; }
    public function available(): bool { return true; }
    public function status(): array { return ['key'=>$this->key(),'name'=>$this->name(),'description'=>$this->description(),'available'=>true,'endpoint'=>url('/feed.xml')]; }
}
