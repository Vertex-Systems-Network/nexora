<?php

declare(strict_types=1);

namespace Tests\Unit\Enterprise;

use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseRole;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantAuthorizationService;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(NexoraCoreSeeder::class); }

    public function test_enterprise_role_restricts_platform_permission_in_current_tenant(): void
    {
        $user=User::factory()->create();$user->roles()->attach(Role::query()->where('slug','administrator')->value('id'));
        $org=EnterpriseOrganization::query()->where('is_default',true)->firstOrFail();
        EnterpriseOrganizationMember::query()->create(['id'=>(string)Str::uuid(),'organization_id'=>$org->id,'user_id'=>$user->id,'role'=>'viewer','status'=>'active','joined_at'=>now()]);
        app(TenantContext::class)->set($org);
        self::assertFalse(app(TenantAuthorizationService::class)->allows($user,'documents.update'));
        self::assertTrue(app(TenantAuthorizationService::class)->allows($user,'documents.view'));
        EnterpriseRole::query()->where('organization_id',$org->id)->where('slug','viewer')->update(['permissions'=>['documents.view','documents.update']]);
        self::assertTrue(app(TenantAuthorizationService::class)->allows($user,'documents.update'));
    }
}
