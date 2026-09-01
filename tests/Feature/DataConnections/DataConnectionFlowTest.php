<?php

declare(strict_types=1);

namespace Tests\Feature\DataConnections;

use App\Models\DataConnection;
use App\Models\EnterpriseOrganization;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DataConnectionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_credentials_are_rejected_from_plaintext_endpoint_and_encrypted_at_rest(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post('/admin/data/connections', [
            'name' => 'Unsafe Mongo',
            'driver' => 'mongodb',
            'endpoint' => 'mongodb://operator:plain-secret@127.0.0.1:27017',
            'database' => 'admin',
            'username' => '',
            'password' => '',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
        ])->assertSessionHasErrors(['endpoint']);
        self::assertSame(0, DataConnection::query()->count());

        $this->actingAs($admin)->post('/admin/data/connections', [
            'name' => 'Secure Mongo',
            'driver' => 'mongodb',
            'endpoint' => 'mongodb://127.0.0.1:27017',
            'database' => 'admin',
            'username' => 'operator',
            'password' => 'top-secret-password',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
        ])->assertSessionHasNoErrors();

        $connection = DataConnection::query()->where('name', 'Secure Mongo')->firstOrFail();
        self::assertNotNull($connection->tenant_id);
        self::assertSame('top-secret-password', $connection->secret_payload['password'] ?? null);

        $rawSecret = (string) DB::table('nx_data_connections')
            ->where('id', $connection->id)
            ->value('secret_payload');
        self::assertStringNotContainsString('top-secret-password', $rawSecret);
    }

    public function test_dynamodb_is_endpoint_optional_and_static_keys_must_be_rotated_as_a_pair(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post('/admin/data/connections', [
            'name' => 'Dynamo via IAM role',
            'driver' => 'aws_dynamodb',
            'endpoint' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'region' => 'us-east-1',
            'access_key' => '',
            'secret_key' => '',
        ])->assertSessionHasNoErrors();

        $connection = DataConnection::query()->where('name', 'Dynamo via IAM role')->firstOrFail();
        self::assertSame('aws_dynamodb', $connection->driver);
        self::assertNull($connection->endpoint);
        self::assertSame('us-east-1', $connection->options['region'] ?? null);
        self::assertSame([], (array) $connection->secret_payload);

        $this->actingAs($admin)->post('/admin/data/connections', [
            'name' => 'Dynamo partial key',
            'driver' => 'aws_dynamodb',
            'endpoint' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'region' => 'us-east-1',
            'access_key' => 'AKIAEXAMPLE',
            'secret_key' => '',
        ])->assertSessionHasErrors(['access_key']);

        self::assertSame(1, DataConnection::query()->where('driver', 'aws_dynamodb')->count());
    }

    public function test_non_dynamodb_connectors_still_require_an_endpoint(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post('/admin/data/connections', [
            'name' => 'Missing Redis endpoint',
            'driver' => 'redis',
            'endpoint' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
        ])->assertSessionHasErrors(['endpoint']);

        self::assertSame(0, DataConnection::query()->where('name', 'Missing Redis endpoint')->count());
    }

    public function test_rotating_connectivity_preserves_blank_secret_and_forces_fresh_health_test(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->post('/admin/data/connections', [
            'name' => 'Cache',
            'driver' => 'redis',
            'endpoint' => '127.0.0.1:6379',
            'database' => '',
            'username' => 'cache-user',
            'password' => 'original-password',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
        ])->assertSessionHasNoErrors();

        $connection = DataConnection::query()->where('name', 'Cache')->firstOrFail();
        $connection->forceFill([
            'status' => 'healthy',
            'is_enabled' => true,
            'last_tested_at' => now(),
        ])->save();

        $this->actingAs($admin)->patch('/admin/data/connections/'.$connection->id, [
            'name' => 'Cache',
            'endpoint' => '127.0.0.1:6380',
            'database' => '',
            'username' => 'cache-user',
            'password' => '',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
        ])->assertSessionHasNoErrors();

        $fresh = $connection->fresh();
        self::assertFalse((bool) $fresh?->is_enabled);
        self::assertContains($fresh?->status, ['untested', 'adapter-missing']);
        self::assertNull($fresh?->last_tested_at);
        self::assertSame('original-password', $fresh?->secret_payload['password'] ?? null);
    }

    public function test_enabled_connection_cannot_be_deleted(): void
    {
        $admin = $this->administrator();
        $connection = DataConnection::query()->create([
            'name' => 'Protected cache',
            'provider' => 'redis',
            'driver' => 'redis',
            'purpose' => 'auxiliary',
            'status' => 'healthy',
            'is_enabled' => true,
            'endpoint' => '127.0.0.1:6379',
            'secret_payload' => [],
            'options' => [],
        ]);

        $this->actingAs($admin)
            ->delete('/admin/data/connections/'.$connection->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('nx_data_connections', ['id' => $connection->id]);
    }

    public function test_connection_names_and_records_are_scoped_per_organization(): void
    {
        $context = app(TenantContext::class);
        $default = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
        $second = EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Second Organization',
            'slug' => 'second-organization',
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
            'metadata' => [],
        ]);

        $context->runWith($default, function (): void {
            DataConnection::query()->create([
                'name' => 'Shared Name',
                'provider' => 'redis',
                'driver' => 'redis',
                'purpose' => 'auxiliary',
                'status' => 'untested',
                'is_enabled' => false,
                'endpoint' => 'cache-one.internal:6379',
                'secret_payload' => [],
                'options' => [],
            ]);
            self::assertSame(1, DataConnection::query()->count());
        });

        $context->runWith($second, function (): void {
            self::assertSame(0, DataConnection::query()->count());
            DataConnection::query()->create([
                'name' => 'Shared Name',
                'provider' => 'redis',
                'driver' => 'redis',
                'purpose' => 'auxiliary',
                'status' => 'untested',
                'is_enabled' => false,
                'endpoint' => 'cache-two.internal:6379',
                'secret_payload' => [],
                'options' => [],
            ]);
            self::assertSame(1, DataConnection::query()->count());
        });

        $context->runWith($default, function (): void {
            self::assertSame(1, DataConnection::query()->count());
            self::assertSame(
                'cache-one.internal:6379',
                DataConnection::query()->firstOrFail()->endpoint,
            );
        });
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }
}
