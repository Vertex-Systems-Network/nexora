<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\EnterpriseImpersonationSession;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Security\Session\SessionSecurityManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ImpersonationManager
{
    public function __construct(
        private readonly EnterpriseAuditRecorder $audit,
        private readonly SessionSecurityManager $sessions,
    ) {}

    public function start(EnterpriseOrganization $org, User $actor, User $target, string $reason, Request $request): EnterpriseImpersonationSession
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['target_user_id' => 'Choose another user.']);
        }
        if ($target->status !== 'active') {
            throw ValidationException::withMessages(['target_user_id' => 'Target user is not active.']);
        }
        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages(['target_user_id' => 'Only a Super Admin can impersonate another Super Admin.']);
        }
        if (! EnterpriseOrganizationMember::query()->where('organization_id', $org->id)->where('user_id', $target->id)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['target_user_id' => 'Target user is not an active member of this organization.']);
        }

        $session = EnterpriseImpersonationSession::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'actor_user_id' => $actor->id,
            'target_user_id' => $target->id,
            'reason' => $reason,
            'request_hash' => hash_hmac('sha256', ($request->ip() ?? '').'|'.substr((string) $request->userAgent(), 0, 300), (string) config('app.key')),
            'started_at' => now(),
        ]);

        $request->session()->put('nexora.enterprise.impersonation_id', $session->id);
        $request->session()->put('nexora.enterprise.impersonator_id', $actor->id);
        Auth::login($target, false);
        $this->sessions->rotateAuthenticatedSession($request);
        $this->audit->record('enterprise.impersonation.started', $org->id, $actor->id, 'user', (string) $target->id, ['reason' => $reason]);

        return $session;
    }

    public function stop(Request $request): void
    {
        $id = (string) $request->session()->pull('nexora.enterprise.impersonation_id', '');
        $actorId = (int) $request->session()->pull('nexora.enterprise.impersonator_id', 0);
        if ($id === '' || $actorId < 1) {
            return;
        }

        $session = EnterpriseImpersonationSession::query()->find($id);
        $actor = User::query()->find($actorId);
        if ($session && ! $session->ended_at) {
            $session->update(['ended_at' => now()]);
        }

        if ($actor && $actor->status === 'active') {
            Auth::login($actor, false);
            $this->sessions->rotateAuthenticatedSession($request);
        } else {
            Auth::logout();
            $this->sessions->invalidateCurrentSession($request);
        }

        if ($session) {
            $this->audit->record('enterprise.impersonation.ended', $session->organization_id, $actorId, 'user', (string) $session->target_user_id);
        }
    }
}
