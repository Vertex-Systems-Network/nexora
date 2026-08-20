<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseAuditEvent;
use App\Models\EnterpriseDomain;
use App\Models\EnterpriseInvitation;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseRole;
use App\Models\EnterpriseScimToken;
use App\Models\EnterpriseSsoProvider;
use App\Models\Permission;
use App\Models\User;
use App\Nexora\Enterprise\Services\EnterpriseAuditRecorder;
use App\Nexora\Enterprise\Services\ImpersonationManager;
use App\Nexora\Enterprise\Services\InvitationManager;
use App\Nexora\Enterprise\Services\OrganizationManager;
use App\Nexora\Enterprise\Services\ScimTokenManager;
use App\Nexora\Enterprise\Services\SsoProviderRegistry;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class EnterpriseController extends Controller
{
    public function index(Request $request, TenantContext $context): Response
    {
        $user=$request->user();
        $organizations=$user->hasRole('super-admin')
            ? EnterpriseOrganization::query()->withCount('members')->orderByDesc('is_default')->orderBy('name')->get()
            : EnterpriseOrganization::query()->withCount('members')->whereHas('members',fn($q)=>$q->where('user_id',$user->id)->where('status','active'))->orderBy('name')->get();
        return Inertia::render('Admin/Enterprise/Index',[
            'current'=>$context->organization()?->only(['id','name','slug','status','timezone','locale']),
            'organizations'=>$organizations->map(fn($o)=>['id'=>$o->id,'name'=>$o->name,'slug'=>$o->slug,'status'=>$o->status,'is_default'=>$o->is_default,'members_count'=>$o->members_count,'owner_user_id'=>$o->owner_user_id]),
            'canManage'=>$user->hasPermission('enterprise.organizations.manage'),
            'canImpersonate'=>$user->hasPermission('enterprise.impersonate'),
        ]);
    }

    public function store(Request $request, OrganizationManager $manager): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'slug'=>['nullable','string','max:190','regex:/^[a-z0-9][a-z0-9-]*$/','unique:nx_enterprise_organizations,slug'],'timezone'=>['nullable','string','max:80'],'locale'=>['nullable','string','max:16']]);
        $org=$manager->create($data,$request->user());
        $request->session()->put('nexora.enterprise.organization_id',$org->id);
        return redirect()->route('admin.enterprise.organizations.show',$org)->with('success','Organization created and selected.');
    }

    public function show(Request $request, EnterpriseOrganization $organization, OrganizationManager $manager, SsoProviderRegistry $registry): Response
    {
        if(!$manager->canAccess($request->user(),$organization))abort(403);
        $members=EnterpriseOrganizationMember::query()->with('user:id,name,email,status')->where('organization_id',$organization->id)->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'member' THEN 3 ELSE 4 END")->get();
        $roles=EnterpriseRole::query()->where('organization_id',$organization->id)->orderBy('name')->get();
        return Inertia::render('Admin/Enterprise/OrganizationShow',[
            'organization'=>['id'=>$organization->id,'name'=>$organization->name,'slug'=>$organization->slug,'status'=>$organization->status,'timezone'=>$organization->timezone,'locale'=>$organization->locale,'is_default'=>$organization->is_default],
            'members'=>$members->map(fn($m)=>['id'=>$m->id,'user_id'=>$m->user_id,'name'=>$m->user?->name,'email'=>$m->user?->email,'user_status'=>$m->user?->status,'role'=>$m->role,'status'=>$m->status,'joined_at'=>$m->joined_at?->toIso8601String()]),
            'roles'=>$roles->map(fn($r)=>['id'=>$r->id,'name'=>$r->name,'slug'=>$r->slug,'permissions'=>$r->permissions??[],'is_system'=>$r->is_system]),
            'domains'=>EnterpriseDomain::query()->where('organization_id',$organization->id)->orderByDesc('is_primary')->orderBy('host')->get()->map(fn($d)=>['id'=>$d->id,'host'=>$d->host,'status'=>$d->status,'is_primary'=>$d->is_primary,'verified_at'=>$d->verified_at?->toIso8601String()]),
            'invitations'=>EnterpriseInvitation::query()->where('organization_id',$organization->id)->where('status','pending')->latest()->limit(30)->get()->map(fn($i)=>['id'=>$i->id,'email'=>$i->email,'role'=>$i->role,'expires_at'=>$i->expires_at?->toIso8601String()]),
            'ssoProviders'=>EnterpriseSsoProvider::query()->where('organization_id',$organization->id)->orderBy('name')->get()->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'slug'=>$p->slug,'protocol'=>$p->protocol,'adapter_key'=>$p->adapter_key,'enabled'=>$p->enabled,'enforce_for_members'=>$p->enforce_for_members,'adapter_available'=>$registry->get($p->adapter_key)!==null]),
            'registeredIdentityAdapters'=>$registry->all(),
            'scimTokens'=>EnterpriseScimToken::query()->where('organization_id',$organization->id)->latest()->get()->map(fn($t)=>['id'=>$t->id,'name'=>$t->name,'enabled'=>$t->enabled,'last_used_at'=>$t->last_used_at?->toIso8601String(),'expires_at'=>$t->expires_at?->toIso8601String(),'revoked_at'=>$t->revoked_at?->toIso8601String()]),
            'audit'=>EnterpriseAuditEvent::query()->where('organization_id',$organization->id)->latest('occurred_at')->limit(50)->get()->map(fn($e)=>['id'=>$e->id,'event_type'=>$e->event_type,'subject_type'=>$e->subject_type,'subject_id'=>$e->subject_id,'actor_user_id'=>$e->actor_user_id,'occurred_at'=>$e->occurred_at?->toIso8601String(),'payload'=>$e->payload]),
            'users'=>User::query()->where('status','!=','deleted')->orderBy('name')->limit(500)->get(['id','name','email'])->map(fn($u)=>['id'=>$u->id,'name'=>$u->name.' · '.$u->email]),
            'availablePermissions'=>Permission::query()->orderBy('group')->orderBy('name')->get(['slug','name','group']),
            'canManageMembers'=>$request->user()->hasPermission('enterprise.members.manage'),
            'canManageDomains'=>$request->user()->hasPermission('enterprise.domains.manage'),
            'canManageIdentity'=>$request->user()->hasPermission('enterprise.identity.manage'),
            'canManageScim'=>$request->user()->hasPermission('enterprise.scim.manage'),
            'canImpersonate'=>$request->user()->hasPermission('enterprise.impersonate'),
            'oneTimeInvitationUrl'=>$request->session()->pull('enterprise.invitation_url'),
            'oneTimeDomainToken'=>$request->session()->pull('enterprise.domain_token'),
            'oneTimeScimToken'=>$request->session()->pull('enterprise.scim_token'),
        ]);
    }

    public function switch(Request $request, OrganizationManager $manager): RedirectResponse
    {
        $data=$request->validate(['organization_id'=>['required','uuid','exists:nx_enterprise_organizations,id']]);$org=EnterpriseOrganization::query()->findOrFail($data['organization_id']);if(!$manager->canAccess($request->user(),$org))abort(403);$request->session()->put('nexora.enterprise.organization_id',$org->id);return back()->with('success','Organization switched to '.$org->name.'.');
    }

    public function member(Request $request, EnterpriseOrganization $organization, OrganizationManager $manager): RedirectResponse
    {
        $data=$request->validate(['user_id'=>['required','integer','exists:users,id'],'role'=>['required',Rule::in(['owner','admin','member','viewer'])]]);$manager->addMember($organization,User::query()->findOrFail($data['user_id']),$data['role'],$request->user());return back()->with('success','Organization member updated.');
    }

    public function role(Request $request, EnterpriseOrganization $organization, EnterpriseRole $role, EnterpriseAuditRecorder $audit): RedirectResponse
    {
        abort_unless($role->organization_id===$organization->id,404);$data=$request->validate(['permissions'=>['array'],'permissions.*'=>['string','exists:nx_permissions,slug']]);if(in_array($role->slug,['owner','admin'],true)&&!$request->user()->hasRole('super-admin'))abort(403);$role->update(['permissions'=>array_values(array_unique($data['permissions']??[]))]);$audit->record('enterprise.role.permissions_updated',$organization->id,$request->user()->id,'enterprise_role',$role->id,['role'=>$role->slug,'permission_count'=>count($data['permissions']??[])]);return back()->with('success','Enterprise role permissions updated.');
    }

    public function invite(Request $request, EnterpriseOrganization $organization, InvitationManager $invitations): RedirectResponse
    {
        $data=$request->validate(['email'=>['required','email:rfc','max:255'],'role'=>['required',Rule::in(['admin','member','viewer'])]]);$created=$invitations->create($organization,$data['email'],$data['role'],$request->user());$request->session()->flash('enterprise.invitation_url',route('enterprise.invitation.accept',['token'=>$created['token']],true));return back()->with('success','Invitation created. Copy the one-time acceptance link now.');
    }

    public function domain(Request $request, EnterpriseOrganization $organization, EnterpriseAuditRecorder $audit): RedirectResponse
    {
        $data=$request->validate(['host'=>['required','string','max:190','regex:/^(?=.{1,190}$)(?!-)[a-z0-9.-]+(?<!-)$/i','unique:nx_enterprise_domains,host'],'is_primary'=>['boolean']]);$host=strtolower(trim($data['host'],'.'));$token='nx-domain-verification='.Str::random(40);$domain=EnterpriseDomain::query()->create(['id'=>(string)Str::uuid(),'organization_id'=>$organization->id,'host'=>$host,'status'=>'pending','is_primary'=>(bool)($data['is_primary']??false),'verification_token_hash'=>hash('sha256',$token)]);if($domain->is_primary)EnterpriseDomain::query()->where('organization_id',$organization->id)->whereKeyNot($domain->id)->update(['is_primary'=>false]);$audit->record('enterprise.domain.created',$organization->id,$request->user()->id,'domain',$domain->id,['host'=>$host]);$request->session()->flash('enterprise.domain_token',['host'=>$host,'dns_name'=>'_nexora-verification.'.$host,'value'=>$token]);return back()->with('success','Domain added. Publish the one-time DNS TXT value before verification.');
    }

    public function verifyDomain(Request $request, EnterpriseOrganization $organization, EnterpriseDomain $domain, EnterpriseAuditRecorder $audit): RedirectResponse
    {
        abort_unless($domain->organization_id === $organization->id, 404);

        if (! function_exists('dns_get_record')) {
            throw ValidationException::withMessages([
                'domain' => 'DNS TXT verification is not available in this PHP runtime. Enable the DNS functions or verify the domain from a compatible server.',
            ]);
        }

        $records = @dns_get_record('_nexora-verification.'.$domain->host, DNS_TXT) ?: [];
        $matched = false;

        foreach ($records as $record) {
            $txt = (string) ($record['txt'] ?? '');
            if ($txt !== '' && hash_equals((string) $domain->verification_token_hash, hash('sha256', $txt))) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            throw ValidationException::withMessages([
                'domain' => 'Verification TXT record was not found yet. DNS propagation may take time.',
            ]);
        }

        DB::transaction(function () use ($domain, $organization): void {
            if ($domain->is_primary) {
                EnterpriseDomain::query()
                    ->where('organization_id', $organization->id)
                    ->whereKeyNot($domain->id)
                    ->update(['is_primary' => false]);
            }

            $domain->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verification_token_hash' => null,
            ]);
        });

        $audit->record(
            'enterprise.domain.verified',
            $organization->id,
            $request->user()->id,
            'domain',
            $domain->id,
            ['host' => $domain->host],
        );

        return back()->with('success', 'Domain ownership verified.');
    }

    public function sso(Request $request, EnterpriseOrganization $organization, SsoProviderRegistry $registry, EnterpriseAuditRecorder $audit): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:160'],'slug'=>['required','string','max:180','regex:/^[a-z0-9][a-z0-9-]*$/'],'protocol'=>['required',Rule::in(['oidc','saml'])],'adapter_key'=>['required','string','max:160'],'configuration'=>['nullable','array'],'secret_payload'=>['nullable','array'],'enabled'=>['boolean'],'enforce_for_members'=>['boolean']]);$adapter=$registry->get($data['adapter_key']);$enabled=(bool)($data['enabled']??false);if($enabled&&(!$adapter||$adapter->protocol()!==$data['protocol']))throw ValidationException::withMessages(['adapter_key'=>'A compatible registered identity adapter is required before this provider can be enabled.']);$provider=EnterpriseSsoProvider::query()->updateOrCreate(['organization_id'=>$organization->id,'slug'=>$data['slug']],['id'=>(string)Str::uuid(),'name'=>$data['name'],'protocol'=>$data['protocol'],'adapter_key'=>$data['adapter_key'],'enabled'=>$enabled,'enforce_for_members'=>(bool)($data['enforce_for_members']??false),'configuration'=>$data['configuration']??[],'secret_payload'=>$data['secret_payload']??[]]);$audit->record('enterprise.sso.saved',$organization->id,$request->user()->id,'sso_provider',$provider->id,['protocol'=>$provider->protocol,'adapter'=>$provider->adapter_key,'enabled'=>$provider->enabled]);return back()->with('success','Enterprise identity provider saved.');
    }

    public function ssoHealth(Request $request, EnterpriseOrganization $organization, EnterpriseSsoProvider $provider, SsoProviderRegistry $registry): RedirectResponse
    {
        abort_unless($provider->organization_id===$organization->id,404);$adapter=$registry->get($provider->adapter_key);if(!$adapter)return back()->with('error','Identity adapter is not registered. Install or enable the verified adapter extension first.');$health=$adapter->health($provider);return back()->with(($health['ok']??false)?'success':'error',(string)($health['message']??'Identity adapter health check completed.'));
    }

    public function scim(Request $request, EnterpriseOrganization $organization, ScimTokenManager $tokens, EnterpriseAuditRecorder $audit): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:160'],'expires_at'=>['nullable','date','after:now']]);$created=$tokens->issue($organization,$data['name'],$data['expires_at']??null);$audit->record('enterprise.scim.token_issued',$organization->id,$request->user()->id,'scim_token',$created['record']->id,['name'=>$created['record']->name]);$request->session()->flash('enterprise.scim_token',$created['token']);return back()->with('success','SCIM token issued. Copy it now; Nexora stores only its hash.');
    }

    public function revokeScim(Request $request, EnterpriseOrganization $organization, EnterpriseScimToken $token, EnterpriseAuditRecorder $audit): RedirectResponse
    {
        abort_unless($token->organization_id===$organization->id,404);$token->update(['enabled'=>false,'revoked_at'=>now()]);$audit->record('enterprise.scim.token_revoked',$organization->id,$request->user()->id,'scim_token',$token->id);return back()->with('success','SCIM token revoked.');
    }

    public function impersonate(Request $request, EnterpriseOrganization $organization, ImpersonationManager $impersonation): RedirectResponse
    {
        $data=$request->validate(['target_user_id'=>['required','integer','exists:users,id'],'reason'=>['required','string','min:10','max:1000']]);$impersonation->start($organization,$request->user(),User::query()->findOrFail($data['target_user_id']),$data['reason'],$request);return redirect('/admin')->with('warning','Impersonation is active. All actions remain attributable to the original administrator.');
    }

    public function stopImpersonation(Request $request, ImpersonationManager $impersonation): RedirectResponse
    {
        $impersonation->stop($request);return redirect('/admin/enterprise')->with('success','Impersonation ended.');
    }
}
