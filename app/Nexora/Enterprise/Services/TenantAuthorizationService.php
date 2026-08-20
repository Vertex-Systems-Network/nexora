<?php

declare(strict_types=1);
namespace App\Nexora\Enterprise\Services;
use App\Models\EnterpriseOrganizationMember;use App\Models\EnterpriseRole;use App\Models\User;use Illuminate\Support\Facades\Schema;
final class TenantAuthorizationService
{
    public function __construct(private TenantContext $context){}
    public function allows(User $user,string $permission): bool
    {
        if($user->hasRole('super-admin'))return true;
        $org=$this->context->organization();
        if(!$org||!Schema::hasTable('nx_enterprise_organization_members'))return true;
        $member=EnterpriseOrganizationMember::query()->where('organization_id',$org->id)->where('user_id',$user->id)->where('status','active')->first();
        if(!$member)return false;
        $role=EnterpriseRole::query()->where('organization_id',$org->id)->where('slug',$member->role)->first();
        $permissions=(array)($role?->permissions??[]);
        return in_array('*',$permissions,true)||in_array($permission,$permissions,true);
    }
}
