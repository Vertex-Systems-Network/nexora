<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Upgrade\UpgradePlanStore;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class UpgradePlanStoreTest extends TestCase
{
    #[Test]
    public function active_upgrade_plan_is_sha256_sealed_and_tamper_evident(): void
    {
        $path=storage_path('framework/testing-nexora-upgrade-plan.json');
        @unlink($path);
        config()->set('nexora-upgrade.plan_path',$path);
        $store=app(UpgradePlanStore::class);
        $written=$store->write(['upgrade_id'=>'upgrade-1','status'=>'ready','source_version'=>'1.0.0-rc.12','target_version'=>'1.0.0-rc.13']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/',(string)($written['plan_sha256']??''));
        self::assertSame('ready',$store->read()['status']??null);

        $tampered=(array)json_decode((string)file_get_contents($path),true);
        $tampered['target_version']='9.9.9';
        file_put_contents($path,json_encode($tampered,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->expectException(RuntimeException::class);
        try { $store->read(); } finally { @unlink($path); }
    }
}
