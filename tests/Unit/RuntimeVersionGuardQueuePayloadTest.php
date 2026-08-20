<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeVersionGuardQueuePayloadTest extends TestCase
{
    #[Test]
    public function queue_payloads_require_schema_thirteen_exact_platform_version_generation_environment_activation_engine_database_storage_service_host_clock_resource_policy_and_process_profile(): void
    {
        $current=(string)config('nexora.version');$generation=app(RuntimeDeploymentIdentity::class)->generation();$environment=app(RuntimeEnvironmentIdentity::class)->fingerprintValue();$activation=app(RuntimeActivationIdentity::class)->current();$engine=app(RuntimeEngineIdentity::class)->fingerprintValue();$database=app(DatabaseDataPlaneIdentity::class)->fingerprintValue();$storage=app(RuntimeStorageDataPlaneIdentity::class)->fingerprintValue();$service=app(RuntimeServiceDataPlaneIdentity::class)->fingerprintValue();$host=app(RuntimeHostClockIdentity::class)->fingerprintValue();$resource=app(RuntimeResourceEnvelopeIdentity::class)->fingerprintValue();$policy=app(RuntimePolicyPlaneIdentity::class)->fingerprintValue();$process=app(RuntimeProcessPlane::class)->fingerprintValue();
        $prior=preg_replace_callback('/rc\.(\d+)$/',static fn(array $m):string=>'rc.'.max(0,(int)$m[1]-1),$current)?:($current.'-prior');
        config()->set('nexora-upgrade.queue_payload_schema',13);config()->set('nexora-upgrade.queue_payload_require_metadata',true);config()->set('nexora-upgrade.queue_payload_require_exact_version',true);config()->set('nexora-upgrade.queue_payload_require_exact_generation',true);config()->set('nexora-upgrade.queue_payload_require_exact_environment',true);
        $guard=app(RuntimeVersionGuard::class);
        self::assertFalse($guard->queuePayload([])['compatible']);
        $base=['payload_schema'=>13,'platform_version'=>$current,'deployment_generation'=>$generation,'runtime_environment_fingerprint'=>$environment,'activation_epoch'=>$activation['activation_epoch'],'runtime_activation_fingerprint'=>$activation['activation_fingerprint'],'runtime_engine_fingerprint'=>$engine,'runtime_database_fingerprint'=>$database,'runtime_storage_fingerprint'=>$storage,'runtime_service_fingerprint'=>$service,'runtime_host_fingerprint'=>$host,'runtime_resource_fingerprint'=>$resource,'runtime_policy_fingerprint'=>$policy,'runtime_process_fingerprint'=>$process,'generated_unix_ms'=>(int)round(microtime(true)*1000)];
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'payload_schema'=>12]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'platform_version'=>$prior]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'deployment_generation'=>str_repeat('f',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_environment_fingerprint'=>str_repeat('e',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'activation_epoch'=>str_repeat('a',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_activation_fingerprint'=>str_repeat('b',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_engine_fingerprint'=>str_repeat('c',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_database_fingerprint'=>str_repeat('d',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_storage_fingerprint'=>str_repeat('9',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_service_fingerprint'=>str_repeat('8',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_host_fingerprint'=>str_repeat('7',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_resource_fingerprint'=>str_repeat('6',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_policy_fingerprint'=>str_repeat('5',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'runtime_process_fingerprint'=>str_repeat('4',64)]])['compatible']);
        self::assertFalse($guard->queuePayload(['nexora'=>[...$base,'generated_unix_ms'=>(int)round(microtime(true)*1000)+3600000]])['compatible']);
        self::assertTrue($guard->queuePayload(['nexora'=>$base])['compatible']);
    }
}
