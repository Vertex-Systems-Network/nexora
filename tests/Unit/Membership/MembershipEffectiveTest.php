<?php

declare(strict_types=1);

namespace Tests\Unit\Membership;

use App\Models\Membership;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

final class MembershipEffectiveTest extends TestCase
{
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    public function test_effective_membership_respects_status_and_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16 12:00:00'));
        $active=new Membership(['status'=>'active','started_at'=>'2026-08-15 00:00:00','ends_at'=>'2026-08-20 00:00:00']);
        self::assertTrue($active->isEffective());
        $expired=new Membership(['status'=>'active','started_at'=>'2026-08-01 00:00:00','ends_at'=>'2026-08-16 11:59:00']);
        self::assertFalse($expired->isEffective());
        $paused=new Membership(['status'=>'paused','started_at'=>'2026-08-01 00:00:00']);
        self::assertFalse($paused->isEffective());
    }
}
