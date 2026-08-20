<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PerformanceHeaderCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('installer.bypass', true);
        config()->set('cache.default', 'array');
        config()->set('session.driver', 'array');
    }

    public function test_sensitive_and_health_responses_are_not_cacheable_and_have_security_headers(): void
    {
        $response=$this->withServerVariables(['HTTPS'=>'on'])->get('/login');
        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options','nosniff');
        $response->assertHeader('X-Frame-Options','SAMEORIGIN');
        self::assertStringContainsString('no-store',(string)$response->headers->get('Cache-Control'));
        self::assertNotSame('',(string)$response->headers->get('Strict-Transport-Security'));

        $health=$this->get('/health/live');
        $health->assertOk();
        self::assertStringContainsString('no-store',(string)$health->headers->get('Cache-Control'));
        $health->assertHeader('X-Content-Type-Options','nosniff');
    }

    public function test_critical_smoke_routes_stay_under_query_regression_budgets(): void
    {
        DB::flushQueryLog(); DB::enableQueryLog();
        $this->get('/health/live')->assertOk();
        $healthQueries=count(DB::getQueryLog());
        self::assertLessThanOrEqual((int)config('nexora-performance.http.query_budgets.health_live',15),$healthQueries,"health/live query budget exceeded: {$healthQueries}");

        DB::flushQueryLog();
        $this->get('/login')->assertOk();
        $loginQueries=count(DB::getQueryLog());
        self::assertLessThanOrEqual((int)config('nexora-performance.http.query_budgets.login',20),$loginQueries,"login query budget exceeded: {$loginQueries}");
    }
}
