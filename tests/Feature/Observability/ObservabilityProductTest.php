<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Http\Middleware\AssignRequestId;
use App\Models\AuditLog;
use App\Models\EnterpriseOrganization;
use App\Models\ObservabilityIncident;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Observability\Services\ObservabilityRecorder;
use App\Nexora\Observability\Services\ObservabilityRetentionService;
use App\Nexora\Security\Audit\AuditManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class ObservabilityProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_audit_logs_are_tenant_scoped_and_sensitive_metadata_is_redacted(): void
    {
        $first = $this->defaultOrganization();
        $second = $this->organization('Observability Two', 'observability-two');
        $actor = User::factory()->create(['status' => 'active']);
        $request = $this->request('audit-request-one');

        app(TenantContext::class)->set($first);
        $firstLog = app(AuditManager::class)->record(
            'observability.first',
            null,
            [
                'safe' => 'visible',
                'password' => 'must-not-persist',
                'nested' => ['api_token' => 'must-not-persist-either', 'value' => 'ok'],
            ],
            $request,
            $actor->id,
        );

        self::assertSame($first->id, $firstLog->tenant_id);
        self::assertSame('[REDACTED]', $firstLog->metadata['password'] ?? null);
        self::assertSame('[REDACTED]', $firstLog->metadata['nested']['api_token'] ?? null);
        self::assertSame('visible', $firstLog->metadata['safe'] ?? null);
        self::assertSame('audit-request-one', $firstLog->request_id);

        app(TenantContext::class)->set($second);
        app(AuditManager::class)->record('observability.second', null, [], $this->request('audit-request-two'), $actor->id);

        app(TenantContext::class)->set($first);
        self::assertSame(['observability.first'], AuditLog::query()->pluck('event')->all());
        self::assertSame(0, AuditLog::query()->where('event', 'observability.second')->count());
    }

    public function test_incident_recorder_only_retains_failures_or_slow_requests_without_raw_request_or_exception_content(): void
    {
        config(['nexora_observability.slow_request_ms' => 1000]);
        $first = $this->defaultOrganization();
        app(TenantContext::class)->set($first);

        $request = Request::create('/private?token=query-secret', 'POST', ['password' => 'body-secret']);
        $request->attributes->set(AssignRequestId::ATTRIBUTE, 'incident-request-one');
        $request->headers->set('Authorization', 'Bearer header-secret');

        $recorder = app(ObservabilityRecorder::class);
        self::assertNull($recorder->captureHttp($request, 200, 100));

        $slow = $recorder->captureHttp($request, 200, 1250);
        self::assertNotNull($slow);
        self::assertSame('SLOW_REQUEST', $slow->code);
        self::assertSame('http_latency', $slow->category);

        $failure = $recorder->captureHttp(
            $request,
            500,
            25,
            new RuntimeException('database password=exception-secret'),
        );
        self::assertNotNull($failure);
        self::assertSame('HTTP_500', $failure->code);
        self::assertSame('http_failure', $failure->category);
        self::assertSame($first->id, $failure->tenant_id);
        self::assertSame('incident-request-one', $failure->request_id);

        $serialized = json_encode($failure->getAttributes(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('query-secret', $serialized);
        self::assertStringNotContainsString('body-secret', $serialized);
        self::assertStringNotContainsString('header-secret', $serialized);
        self::assertStringNotContainsString('exception-secret', $serialized);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) ($failure->metadata['exception_fingerprint'] ?? ''));
    }

    public function test_incident_reads_are_current_tenant_scoped(): void
    {
        $first = $this->defaultOrganization();
        $second = $this->organization('Observability Other', 'observability-other');

        app(TenantContext::class)->set($first);
        app(ObservabilityRecorder::class)->captureHttp($this->request('first-incident'), 500, 20, new RuntimeException('first'));

        app(TenantContext::class)->set($second);
        app(ObservabilityRecorder::class)->captureHttp($this->request('second-incident'), 500, 20, new RuntimeException('second'));

        app(TenantContext::class)->set($first);
        self::assertSame(['first-incident'], ObservabilityIncident::query()->pluck('request_id')->all());
    }

    public function test_retention_prunes_old_audit_and_incident_rows_but_preserves_recent_rows(): void
    {
        config([
            'nexora_observability.audit_retention_days' => 30,
            'nexora_observability.incident_retention_days' => 7,
        ]);
        $organization = $this->defaultOrganization();
        app(TenantContext::class)->set($organization);

        AuditLog::query()->create([
            'tenant_id' => $organization->id,
            'event' => 'old.audit',
            'request_id' => 'old-audit',
            'created_at' => now()->subDays(31),
        ]);
        AuditLog::query()->create([
            'tenant_id' => $organization->id,
            'event' => 'recent.audit',
            'request_id' => 'recent-audit',
            'created_at' => now()->subDays(2),
        ]);
        $this->incident($organization, 'old-incident', now()->subDays(8));
        $this->incident($organization, 'recent-incident', now()->subDay());

        $deleted = app(ObservabilityRetentionService::class)->prune();
        self::assertSame(1, $deleted['audit_logs']);
        self::assertSame(1, $deleted['incidents']);

        $this->assertDatabaseMissing('nx_audit_logs', ['request_id' => 'old-audit']);
        $this->assertDatabaseHas('nx_audit_logs', ['request_id' => 'recent-audit']);
        $this->assertDatabaseMissing('nx_observability_incidents', ['request_id' => 'old-incident']);
        $this->assertDatabaseHas('nx_observability_incidents', ['request_id' => 'recent-incident']);
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }

    private function organization(string $name, string $slug): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }

    private function request(string $requestId): Request
    {
        $request = Request::create('/observability-test', 'GET');
        $request->attributes->set(AssignRequestId::ATTRIBUTE, $requestId);
        return $request;
    }

    private function incident(EnterpriseOrganization $organization, string $requestId, $occurredAt): void
    {
        ObservabilityIncident::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $organization->id,
            'request_id' => $requestId,
            'category' => 'http_failure',
            'severity' => 'error',
            'code' => 'HTTP_500',
            'status_code' => 500,
            'duration_ms' => 10,
            'occurred_at' => $occurredAt,
        ]);
    }
}
