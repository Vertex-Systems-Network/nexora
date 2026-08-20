<?php

declare(strict_types=1);
namespace App\Nexora\Enterprise\Services;
use App\Models\EnterpriseOrganization;use App\Models\EnterpriseOrganizationMember;use App\Models\EnterpriseRole;use App\Models\User;use Illuminate\Support\Facades\DB;use Illuminate\Support\Str;use Illuminate\Validation\ValidationException;
final class OrganizationManager
{
    public function __construct(private EnterpriseAuditRecorder $audit){}
    public function create(array $data, User $actor): EnterpriseOrganization
    {
        return DB::transaction(function()use($data,$actor){
            $org=EnterpriseOrganization::query()->create(['id'=>(string)Str::uuid(),'name'=>$data['name'],'slug'=>Str::slug($data['slug']??$data['name']),'status'=>'active','is_default'=>false,'owner_user_id'=>$actor->id,'timezone'=>$data['timezone']??$actor->timezone??'UTC','locale'=>$data['locale']??$actor->locale??'en','metadata'=>[]]);
            foreach([['Owner','owner',['*']],['Administrator','admin',['*']],['Member','member',['admin.access','profile.manage','documents.view','documents.create','documents.update','media.view','media.upload','publishing.view','crm.view','helpdesk.view','helpdesk.tickets.manage']],['Viewer','viewer',['admin.access','profile.manage','documents.view','media.view','publishing.view','crm.view','helpdesk.view']]] as [$name,$slug,$permissions]) EnterpriseRole::query()->create(['id'=>(string)Str::uuid(),'organization_id'=>$org->id,'name'=>$name,'slug'=>$slug,'permissions'=>$permissions,'is_system'=>true]);
            EnterpriseOrganizationMember::query()->create(['id'=>(string)Str::uuid(),'organization_id'=>$org->id,'user_id'=>$actor->id,'role'=>'owner','status'=>'active','joined_at'=>now()]);
            $this->audit->record('enterprise.organization.created',$org->id,$actor->id,'organization',$org->id,['name'=>$org->name]);
            return $org;
        });
    }
    public function addMember(EnterpriseOrganization $org,User $user,string $role,User $actor): EnterpriseOrganizationMember
    {
        if(!in_array($role,['owner','admin','member','viewer'],true)) throw ValidationException::withMessages(['role'=>'Invalid organization role.']);
        $member=EnterpriseOrganizationMember::query()->updateOrCreate(['organization_id'=>$org->id,'user_id'=>$user->id],['id'=>(string)Str::uuid(),'role'=>$role,'status'=>'active','joined_at'=>now()]);
        $this->audit->record('enterprise.member.upserted',$org->id,$actor->id,'user',(string)$user->id,['role'=>$role]);
        return $member;
    }
    public function canAccess(User $user,EnterpriseOrganization $org): bool { return $user->hasRole('super-admin') || EnterpriseOrganizationMember::query()->where('organization_id',$org->id)->where('user_id',$user->id)->where('status','active')->exists(); }
}
