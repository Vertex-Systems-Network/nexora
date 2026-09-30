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

    public function start(
        EnterpriseOrganization $organization,
        User $actor,
        User $target,
        string $reason,
        Request $request,
    ): EnterpriseImpersonationSession {
        if ($organization->status !== 'active') {
            throw ValidationException::withMessages([
                'target_user_id' => 'Impersonation is unavailable for this organization.',
            ]);
        }
        if ($request->session()->has('nexora.enterprise.impersonation_id')
            || $request->session()->has('nexora.enterprise.impersonator_id')) {
            throw ValidationException::withMessages([
                'target_user_id' => 'Nested impersonation is not allowed.',
            ]);
        }
        if ($actor->status !== 'active') {
            throw ValidationException::withMessages([
                'target_user_id' => 'The acting administrator is not active.',
            ]);
        }
        if (! $actor->hasRole('super-admin') && ! $this->isActiveMember($organization, $actor)) {
            throw ValidationException::withMessages([
                'target_user_id' => 'The acting administrator cannot access this organization.',
            ]);
        }
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['target_user_id' => 'Choose another user.']);
        }
        if ($target->status !== 'active') {
            throw ValidationException::withMessages(['target_user_id' => 'Target user is not active.']);
        }
        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'target_user_id' => 'Only a Super Admin can impersonate another Super Admin.',
            ]);
        }
        if (! $this->isActiveMember($organization, $target)) {
            throw ValidationException::withMessages([
                'target_user_id' => 'Target user is not an active member of this organization.',
            ]);
        }

        $session = EnterpriseImpersonationSession::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'actor_user_id' => $actor->id,
            'target_user_id' => $target->id,
            'reason' => trim($reason),
            'request_hash' => hash_hmac(
                'sha256',
                ($request->ip() ?? '').'|'.substr((string) $request->userAgent(), 0, 300),
                (string) config('app.key'),
            ),
            'started_at' => now(),
        ]);

        $request->session()->put('nexora.enterprise.impersonation_id', $session->id);
        $request->session()->put('nexora.enterprise.impersonator_id', $actor->id);
        $request->session()->put('nexora.enterprise.organization_id', $organization->id);
        Auth::login($target, false);
        $this->sessions->rotateAuthenticatedSession($request);
        $this->audit->record(
            'enterprise.impersonation.started',
            $organization->id,
            $actor->id,
            'user',
            (string) $target->id,
            ['reason_hash' => hash('sha256', trim($reason))],
        );

        return $session;
    }

    public function stop(Request $request): void
    {
        $id = (string) $request->session()->get('nexora.enterprise.impersonation_id', '');
        $actorId = (int) $request->session()->get('nexora.enterprise.impersonator_id', 0);
        $currentUserId = (int) ($request->user()?->id ?? 0);

        if ($id === '' || $actorId < 1) {
            return;
        }

        $session = EnterpriseImpersonationSession::query()->find($id);
        if ($session === null
            || (int) $session->actor_user_id !== $actorId
            || (int) $session->target_user_id !== $currentUserId
            || $session->ended_at !== null) {
            $request->session()->forget([
                'nexora.enterprise.impersonation_id',
                'nexora.enterprise.impersonator_id',
            ]);
            Auth::logout();
            $this->sessions->invalidateCurrentSession($request);

            return;
        }

        $session->update(['ended_at' => now()]);
        $actor = User::query()->find($actorId);
        $organization = EnterpriseOrganization::query()
            ->whereKey($session->organization_id)
            ->where('status', 'active')
            ->first();
        $actorCanReturn = $actor !== null
            && $actor->status === 'active'
            && $organization !== null
            && ($actor->hasRole('super-admin') || $this->isActiveMember($organization, $actor));

        $request->session()->forget([
            'nexora.enterprise.impersonation_id',
            'nexora.enterprise.impersonator_id',
        ]);

        if ($actorCanReturn) {
            Auth::login($actor, false);
            $request->session()->put('nexora.enterprise.organization_id', $organization->id);
            $this->sessions->rotateAuthenticatedSession($request);
        } else {
            Auth::logout();
            $this->sessions->invalidateCurrentSession($request);
        }

        $this->audit->record(
            'enterprise.impersonation.ended',
            $session->organization_id,
            $actorId,
            'user',
            (string) $session->target_user_id,
            ['actor_restored' => $actorCanReturn],
        );
    }

    private function isActiveMember(EnterpriseOrganization $organization, User $user): bool
    {
        return EnterpriseOrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
}
