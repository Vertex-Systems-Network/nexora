<?php

declare(strict_types=1);

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Enterprise\Services\EnterpriseAuditRecorder;
use App\Nexora\Enterprise\Services\ScimTokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ScimController extends Controller
{
    public function __construct(
        private readonly ScimTokenManager $tokens,
        private readonly EnterpriseAuditRecorder $audit,
    ) {}

    public function users(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $members = EnterpriseOrganizationMember::query()
            ->with('user:id,name,email,status')
            ->where('organization_id', $token->organization_id)
            ->whereIn('status', ['active', 'suspended'])
            ->get();

        $resources = $members
            ->filter(fn (EnterpriseOrganizationMember $member): bool => $member->user !== null)
            ->map(fn (EnterpriseOrganizationMember $member): array => $this->resource($member->user, $member))
            ->values();

        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $resources->count(),
            'startIndex' => 1,
            'itemsPerPage' => $resources->count(),
            'Resources' => $resources,
        ]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $data = $request->validate([
            'userName' => ['required', 'email:rfc', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'name.givenName' => ['nullable', 'string', 'max:120'],
            'name.familyName' => ['nullable', 'string', 'max:120'],
        ]);

        $email = strtolower(trim((string) $data['userName']));
        $active = (bool) ($data['active'] ?? true);
        $name = trim(($data['name']['givenName'] ?? '').' '.($data['name']['familyName'] ?? '')) ?: $email;
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user !== null) {
            $member = EnterpriseOrganizationMember::query()
                ->where('organization_id', $token->organization_id)
                ->where('user_id', $user->id)
                ->first();

            // SCIM is tenant provisioning, not a platform-wide identity attach
            // mechanism. Existing identities must already belong to this tenant.
            abort_unless($member !== null, 409, 'SCIM userName is already in use.');

            if (! $active && in_array($member->role, ['owner', 'admin'], true)) {
                abort(403, 'Privileged organization memberships cannot be deactivated through SCIM.');
            }

            $member->update(['status' => $active ? 'active' : 'suspended']);
        } else {
            // Global identity remains active; SCIM active/inactive state is an
            // organization-membership concern and must not affect other tenants.
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'status' => 'active',
                'locale' => 'en',
            ]);
            $member = EnterpriseOrganizationMember::query()->create([
                'id' => (string) Str::uuid(),
                'organization_id' => $token->organization_id,
                'user_id' => $user->id,
                'role' => 'member',
                'status' => $active ? 'active' : 'suspended',
                'joined_at' => now(),
            ]);
        }

        $this->audit->record(
            'enterprise.scim.user_provisioned',
            $token->organization_id,
            null,
            'user',
            (string) $user->id,
            ['email_hash' => hash('sha256', $email)],
        );

        return response()->json($this->resource($user, $member), 201);
    }

    public function patchUser(Request $request, int $user): JsonResponse
    {
        $token = $this->token($request);
        $member = EnterpriseOrganizationMember::query()
            ->where('organization_id', $token->organization_id)
            ->where('user_id', $user)
            ->firstOrFail();
        $target = User::query()->findOrFail($user);
        $operations = $request->input('Operations', []);

        abort_unless(is_array($operations) && count($operations) <= 50, 422, 'Invalid SCIM operations payload.');

        foreach ($operations as $operation) {
            abort_unless(is_array($operation), 422, 'Invalid SCIM operation.');
            $op = strtolower((string) ($operation['op'] ?? 'replace'));
            $path = strtolower((string) ($operation['path'] ?? ''));
            abort_unless(in_array($op, ['replace', 'add'], true) && $path === 'active', 400, 'Unsupported SCIM operation.');

            $active = filter_var($operation['value'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            abort_unless($active !== null, 422, 'SCIM active value must be boolean.');
            if (! $active && in_array($member->role, ['owner', 'admin'], true)) {
                abort(403, 'Privileged organization memberships cannot be deactivated through SCIM.');
            }

            $member->update(['status' => $active ? 'active' : 'suspended']);
        }

        $this->audit->record(
            'enterprise.scim.user_updated',
            $token->organization_id,
            null,
            'user',
            (string) $target->id,
            ['operation_count' => count($operations)],
        );

        return response()->json($this->resource($target, $member->fresh()));
    }

    private function token(Request $request): \App\Models\EnterpriseScimToken
    {
        $plain = (string) $request->bearerToken();
        $record = $plain !== '' ? $this->tokens->resolve($plain) : null;
        abort_unless($record !== null, 401, 'Invalid SCIM bearer token.');

        return $record;
    }

    private function resource(User $user, EnterpriseOrganizationMember $member): array
    {
        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => (string) $user->id,
            'userName' => $user->email,
            'displayName' => $user->name,
            'active' => $user->status === 'active' && $member->status === 'active',
            'roles' => [[
                'value' => $member->role,
                'display' => ucfirst($member->role),
            ]],
            'meta' => [
                'resourceType' => 'User',
                'location' => url('/scim/v2/Users/'.$user->id),
            ],
        ];
    }
}
