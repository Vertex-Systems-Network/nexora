<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\EnterpriseDomain;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

final class ResolveEnterpriseOrganization
{
    public function __construct(
        private TenantContext $context,
        private InstallationState $installation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // The installation wizard must remain database-independent until the
        // permanent installation lock exists. Schema::hasTable() opens the
        // configured database connection, which is intentionally not ready yet.
        if (! $this->installation->isInstalled() || $request->routeIs('install.*')) {
            $this->context->clear();
            return $next($request);
        }

        if (! Schema::hasTable('nx_enterprise_organizations')) return $next($request);

        $user=$request->user();
        $organization=null;
        $sessionId=(string)$request->session()->get('nexora.enterprise.organization_id','');
        if($user&&$sessionId!==''){
            $candidate=EnterpriseOrganization::query()->whereKey($sessionId)->where('status','active')->first();
            if($candidate&&($user->hasRole('super-admin')||EnterpriseOrganizationMember::query()->where('organization_id',$candidate->id)->where('user_id',$user->id)->where('status','active')->exists()))$organization=$candidate;
        }

        if(!$organization){
            $host=strtolower($request->getHost());
            $domain=EnterpriseDomain::query()->with('organization')->where('host',$host)->where('status','verified')->first();
            if($domain&&$domain->organization?->status==='active'&&(!$user||$user->hasRole('super-admin')||EnterpriseOrganizationMember::query()->where('organization_id',$domain->organization_id)->where('user_id',$user->id)->where('status','active')->exists()))$organization=$domain->organization;
        }

        if(!$organization&&$user){
            $member=EnterpriseOrganizationMember::query()->with('organization')->where('user_id',$user->id)->where('status','active')->whereHas('organization',fn($q)=>$q->where('status','active'))->orderBy('joined_at')->first();
            $organization=$member?->organization;
        }

        if(!$organization){
            $organization=EnterpriseOrganization::query()->where('is_default',true)->where('status','active')->first();
            // Upgrade compatibility: legacy platform administrators had no
            // tenant membership before N0.33. Attach them to the default tenant
            // once; ordinary users are never auto-promoted.
            if($organization&&$user&&($user->hasRole('administrator')||$user->hasRole('super-admin'))&&!EnterpriseOrganizationMember::query()->where('user_id',$user->id)->exists()){
                EnterpriseOrganizationMember::query()->create(['id'=>(string)\Illuminate\Support\Str::uuid(),'organization_id'=>$organization->id,'user_id'=>$user->id,'role'=>$user->hasRole('super-admin')?'owner':'admin','status'=>'active','joined_at'=>now()]);
            }
        }
        $this->context->set($organization);
        if($organization)$request->session()->put('nexora.enterprise.organization_id',$organization->id);

        return $next($request);
    }
}
