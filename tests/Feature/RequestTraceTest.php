<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RequestTraceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_id_is_returned_and_reused_by_audit_events(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $requestId = 'test-request-123';
        $response = $this->actingAs($user)
            ->withHeader('X-Request-Id', $requestId)
            ->get('/admin');

        $response->assertForbidden()->assertHeader('X-Request-Id', $requestId);
        $this->assertDatabaseHas('nx_audit_logs', [
            'user_id' => $user->id,
            'event' => 'authorization.admin_denied',
            'request_id' => $requestId,
        ]);
    }
}
