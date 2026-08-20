<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Nexora\Foundation\Database\ConcurrencyDoctor;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ConcurrencyCertificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portable_transaction_mutex_and_runtime_doctor_are_operational(): void
    {
        $guard=app(ConcurrencyGuard::class);
        $result=$guard->mutex('certification.concurrency', static fn (): int => 19);
        self::assertSame(19,$result);
        self::assertDatabaseHas('nx_concurrency_mutexes',['name'=>'certification.concurrency']);

        $doctor=app(ConcurrencyDoctor::class)->inspect();
        self::assertTrue($doctor['ok'],json_encode($doctor,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        self::assertGreaterThanOrEqual(2,$doctor['attempts']);
        self::assertSame(1,DB::table('nx_concurrency_mutexes')->where('name','certification.concurrency')->count());
    }
}
