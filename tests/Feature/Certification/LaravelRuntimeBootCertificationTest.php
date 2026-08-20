<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Http\Middleware\RuntimeNodeHeartbeat;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use ReflectionMethod;
use Tests\TestCase;

final class LaravelRuntimeBootCertificationTest extends TestCase
{
    public function test_runtime_heartbeat_uses_standard_middleware_pipeline_signature(): void
    {
        $method=new ReflectionMethod(RuntimeNodeHeartbeat::class,'handle');
        self::assertSame(2,$method->getNumberOfRequiredParameters());
        self::assertSame(Request::class,$method->getParameters()[0]->getType()?->getName());
        self::assertSame(Closure::class,$method->getParameters()[1]->getType()?->getName());
    }

    public function test_route_and_schedule_registries_boot_without_runtime_contract_errors(): void
    {
        self::assertSame(0,Artisan::call('route:list'));
        self::assertSame(0,Artisan::call('schedule:list'));
    }
    public function test_upgrade_operator_commands_are_discoverable_by_laravel(): void
    {
        $commands=array_keys(Artisan::all());
        foreach (['nexora:upgrade:preflight','nexora:upgrade:plan','nexora:upgrade:apply','nexora:upgrade:status'] as $command) {
            self::assertContains($command,$commands);
        }
    }

}
