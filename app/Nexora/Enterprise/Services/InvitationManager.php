<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\EnterpriseInvitation;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InvitationManager
{
    public function __construct(private readonly EnterpriseAuditRecorder $audit) {}

    /** @return array{invitation:EnterpriseInvitation,token:string} */
    public function create(EnterpriseOrganization $organization, string $email, string $role, User $actor): array
    {
        if ($organization->status !== 'active') {
            throw ValidationException::withMessages(['invitation' => 'Invitations cannot be created for an inactive organization.']);
        }
        if (! in_array($role, ['admin', 'member', 'viewer'], true)) {
            throw ValidationException::withMessages(['role' => 'Invalid organization invitation role.']);
        }

        $email = strtolower(trim($email));
        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($organization, $email, $role, $actor, $token): EnterpriseInvitation {
            // Only the newest invitation for an organization/email remains
            // actionable. Old tokens cannot later replay an obsolete role.
            EnterpriseInvitation::query()
                ->where('organization_id', $organization->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', 'pending')
                ->lockForUpdate()
                ->update(['status' => 'superseded']);

            return EnterpriseInvitation::query()->create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'status' => 'pending',
                'invited_by' => $actor->id,
                'expires_at' => now()->addDays(7),
            ]);
        });

        $this->audit->record(
            'enterprise.invitation.created',
            $organization->id,
            $actor->id,
            'invitation',
            $invitation->id,
            ['email_hash' => hash('sha256', $email), 'role' => $role],
        );

        return ['invitation' => $invitation, 'token' => $token];
    }

    public function accept(string $token, User $user): EnterpriseOrganizationMember
    {
        return DB::transaction(function () use ($token, $user): EnterpriseOrganizationMember {
            $invitation = EnterpriseInvitation::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->firstOrFail();

            if ($invitation->status !== 'pending' || $invitation->expires_at?->isPast()) {
                throw ValidationException::withMessages(['invitation' => 'This invitation is no longer valid.']);
            }
            if ($user->status !== 'active') {
                throw ValidationException::withMessages(['invitation' => 'This account cannot accept organization invitations.']);
            }
            if (strtolower($user->email) !== strtolower($invitation->email)) {
                throw ValidationException::withMessages(['invitation' => 'This invitation belongs to another email address.']);
            }
            if (! in_array($invitation->role, ['admin', 'member', 'viewer'], true)) {
                throw ValidationException::withMessages(['invitation' => 'This invitation has an invalid organization role.']);
            }

            $organization = EnterpriseOrganization::query()
                ->whereKey($invitation->organization_id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();
            if ($organization === null) {
                throw ValidationException::withMessages(['invitation' => 'This invitation is no longer valid.']);
            }

            $member = EnterpriseOrganizationMember::query()
                ->where('organization_id', $invitation->organization_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($member === null) {
                $member = EnterpriseOrganizationMember::query()->create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                    'role' => $invitation->role,
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            } else {
                // Invitation acceptance must never demote an existing owner/admin.
                $role = in_array($member->role, ['owner', 'admin'], true)
                    ? $member->role
                    : $invitation->role;
                $member->update([
                    'role' => $role,
                    'status' => 'active',
                    'joined_at' => $member->joined_at ?? now(),
                ]);
            }

            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);
            EnterpriseInvitation::query()
                ->where('organization_id', $invitation->organization_id)
                ->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])
                ->where('status', 'pending')
                ->whereKeyNot($invitation->id)
                ->update(['status' => 'superseded']);

            $this->audit->record(
                'enterprise.invitation.accepted',
                $invitation->organization_id,
                $user->id,
                'invitation',
                $invitation->id,
                ['role' => $member->role],
            );

            return $member;
        });
    }
}
