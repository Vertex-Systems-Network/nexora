<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Nexora\Foundation\Environment\EnvironmentDoctor;
use Tests\TestCase;

final class EnvironmentConfigurationCertificationTest extends TestCase
{
    public function test_production_configuration_can_pass_without_exposing_secret_values(): void
    {
        config()->set('app.env','production');
        config()->set('app.debug',false);
        config()->set('app.key','base64:'.base64_encode(str_repeat('k',32)));
        config()->set('app.url','https://nexora.example');
        config()->set('session.encrypt',true);
        config()->set('session.http_only',true);
        config()->set('session.secure',true);
        config()->set('session.same_site','lax');
        config()->set('session.driver','database');
        config()->set('cache.default','database');
        config()->set('queue.default','database');
        config()->set('database.default','sqlite');
        config()->set('database.connections.sqlite.database',database_path('database.sqlite'));
        config()->set('filesystems.default','local');
        config()->set('nexora-environment.allow_insecure_http',false);

        $result=app(EnvironmentDoctor::class)->inspect(true);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        $json=json_encode($result,JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(str_repeat('k',16),$json);
        self::assertArrayHasKey('app_key_fingerprint',$result['facts']);
    }

    public function test_insecure_production_url_is_rejected_by_default(): void
    {
        config()->set('app.env','production');
        config()->set('app.debug',false);
        config()->set('app.key','base64:'.base64_encode(str_repeat('x',32)));
        config()->set('app.url','http://nexora.example');
        config()->set('session.encrypt',true);
        config()->set('session.http_only',true);
        config()->set('session.secure',false);
        config()->set('session.same_site','lax');
        config()->set('session.driver','database');
        config()->set('cache.default','database');
        config()->set('queue.default','database');
        config()->set('database.default','sqlite');
        config()->set('database.connections.sqlite.database',database_path('database.sqlite'));
        config()->set('nexora-environment.allow_insecure_http',false);

        $result=app(EnvironmentDoctor::class)->inspect(true);
        self::assertSame('fail',$result['status']);
        self::assertTrue(collect($result['errors'])->contains(fn(string $error): bool=>str_contains($error,'must use HTTPS')));
    }
}
