<?php

declare(strict_types=1);
namespace App\Nexora\Enterprise\Services;
use App\Models\EnterpriseInvitation;use App\Models\EnterpriseOrganization;use App\Models\EnterpriseOrganizationMember;use App\Models\User;use Illuminate\Support\Facades\DB;use Illuminate\Support\Str;use Illuminate\Validation\ValidationException;
final class InvitationManager
{
    public function __construct(private EnterpriseAuditRecorder $audit){}
    /** @return array{invitation:EnterpriseInvitation,token:string} */ public function create(EnterpriseOrganization $org,string $email,string $role,User $actor):array
    {
        $email=strtolower(trim($email));$token=Str::random(64);
        $inv=EnterpriseInvitation::query()->create(['id'=>(string)Str::uuid(),'organization_id'=>$org->id,'email'=>$email,'role'=>$role,'token_hash'=>hash('sha256',$token),'status'=>'pending','invited_by'=>$actor->id,'expires_at'=>now()->addDays(7)]);
        $this->audit->record('enterprise.invitation.created',$org->id,$actor->id,'invitation',$inv->id,['email'=>$email,'role'=>$role]);
        return ['invitation'=>$inv,'token'=>$token];
    }
    public function accept(string $token,User $user): EnterpriseOrganizationMember
    {
        return DB::transaction(function()use($token,$user){$inv=EnterpriseInvitation::query()->where('token_hash',hash('sha256',$token))->lockForUpdate()->firstOrFail();if($inv->status!=='pending'||$inv->expires_at?->isPast())throw ValidationException::withMessages(['invitation'=>'This invitation is no longer valid.']);if(strtolower($user->email)!==strtolower($inv->email))throw ValidationException::withMessages(['invitation'=>'This invitation belongs to another email address.']);$member=EnterpriseOrganizationMember::query()->updateOrCreate(['organization_id'=>$inv->organization_id,'user_id'=>$user->id],['id'=>(string)Str::uuid(),'role'=>$inv->role,'status'=>'active','joined_at'=>now()]);$inv->update(['status'=>'accepted','accepted_at'=>now()]);$this->audit->record('enterprise.invitation.accepted',$inv->organization_id,$user->id,'invitation',$inv->id);return $member;});
    }
}
