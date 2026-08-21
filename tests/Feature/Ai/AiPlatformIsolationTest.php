<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\AiConnection;
use App\Models\AiGenerationRun;
use App\Models\EnterpriseOrganization;
use App\Nexora\Ai\Contracts\AiTextProviderContract;
use App\Nexora\Ai\Data\AiTextGenerationRequest;
use App\Nexora\Ai\Data\AiTextGenerationResult;
use App\Nexora\Ai\Services\AiGenerationService;
use App\Nexora\Ai\Services\AiProviderRegistry;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class AiPlatformIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_ai_connections_are_tenant_scoped_and_credentials_are_encrypted_at_rest(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other AI tenant', 'other-ai-tenant');
        $context = app(TenantContext::class);

        $context->set($primary);
        $primaryConnection = $this->createConnection('Primary AI', 'primary-secret');
        self::assertSame(['api_key' => 'primary-secret'], $primaryConnection->fresh()?->credentials);

        $rawCredentials = (string) DB::table('nx_ai_connections')->where('id', $primaryConnection->id)->value('credentials');
        self::assertNotSame('', $rawCredentials);
        self::assertStringNotContainsString('primary-secret', $rawCredentials);

        $context->set($other);
        $otherConnection = $this->createConnection('Other AI', 'other-secret');
        self::assertSame([$otherConnection->id], AiConnection::query()->pluck('id')->all());

        $context->set($primary);
        self::assertSame([$primaryConnection->id], AiConnection::query()->pluck('id')->all());
    }

    public function test_generation_persists_only_bounded_metadata_and_counts_failed_retries_against_daily_admission(): void
    {
        $context = app(TenantContext::class);
        $context->set($this->defaultOrganization());
        app(AiProviderRegistry::class)->register($this->provider('test.echo', 'private output omega'));

        $connection = $this->createConnection('Editorial AI', 'provider-secret', [
            'provider_key' => 'test.echo',
            'enabled' => true,
            'last_health_status' => 'healthy',
            'max_input_chars' => 5000,
            'max_output_tokens' => 64,
            'daily_request_limit' => 1,
        ]);

        $prompt = 'private prompt alpha';
        $result = app(AiGenerationService::class)->generate($connection, $prompt, 32);
        self::assertSame('private output omega', $result->text);

        $run = AiGenerationRun::query()->firstOrFail();
        self::assertSame('succeeded', $run->status);
        self::assertSame(hash('sha256', $prompt), $run->prompt_sha256);
        self::assertSame(hash('sha256', 'private output omega'), $run->output_sha256);
        self::assertFalse(Schema::hasColumn('nx_ai_generation_runs', 'prompt'));
        self::assertFalse(Schema::hasColumn('nx_ai_generation_runs', 'output'));

        $rawRun = DB::table('nx_ai_generation_runs')->where('id', $run->id)->first();
        $encoded = json_encode($rawRun, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($prompt, $encoded);
        self::assertStringNotContainsString('private output omega', $encoded);
        self::assertStringNotContainsString('provider-secret', $encoded);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('daily request limit');
        app(AiGenerationService::class)->generate($connection, 'second request', 16);
    }

    public function test_generation_re_resolves_the_connection_inside_current_tenant(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other execution tenant', 'other-execution-tenant');
        $context = app(TenantContext::class);
        app(AiProviderRegistry::class)->register($this->provider('test.scope', 'scope output'));

        $context->set($primary);
        $primaryConnection = $this->createConnection('Scoped AI', 'scope-secret', [
            'provider_key' => 'test.scope',
            'enabled' => true,
            'last_health_status' => 'healthy',
        ]);

        $context->set($other);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unavailable or disabled');
        app(AiGenerationService::class)->generate($primaryConnection, 'must not cross tenant', 16);
    }

    private function provider(string $key, string $output): AiTextProviderContract
    {
        return new class($key, $output) implements AiTextProviderContract {
            public function __construct(private string $providerKey, private string $output) {}
            public function key(): string { return $this->providerKey; }
            public function label(): string { return 'Test AI Provider'; }
            public function health(array $credentials, array $settings = []): array { return ['ok' => true, 'message' => 'Healthy.']; }
            public function generate(AiTextGenerationRequest $request): AiTextGenerationResult
            {
                return new AiTextGenerationResult(
                    text: $this->output,
                    inputTokens: max(1, intdiv(mb_strlen($request->prompt), 4)),
                    outputTokens: max(1, intdiv(mb_strlen($this->output), 4)),
                    providerRequestId: 'test-request-id',
                );
            }
        };
    }

    /** @param array<string,mixed> $overrides */
    private function createConnection(string $name, string $secret, array $overrides = []): AiConnection
    {
        return AiConnection::query()->create($overrides + [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'provider_key' => 'test.provider',
            'model' => 'test-model',
            'enabled' => false,
            'credentials' => ['api_key' => $secret],
            'settings' => [],
            'max_input_chars' => 20000,
            'max_output_tokens' => 2048,
            'daily_request_limit' => 100,
        ]);
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }

    private function createOrganization(string $name, string $slug): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }
}
