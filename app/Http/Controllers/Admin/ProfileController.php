<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passkey;
use App\Models\User;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Passkeys\PasskeyManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use App\Nexora\Security\TwoFactor\TotpAuthenticator;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $sessions = [];

        if (config('session.driver') === 'database') {
            $sessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
                ->map(fn ($session): array => [
                    'id' => $session->id,
                    'ipAddress' => $session->ip_address,
                    'userAgent' => $session->user_agent,
                    'lastActivity' => $session->last_activity,
                    'isCurrent' => $session->id === $request->session()->getId(),
                ])
                ->all();
        }

        return Inertia::render('Admin/Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'timezone' => $user->timezone,
                'locale' => $user->locale,
            ],
            'sessions' => $sessions,
            'sessionDriver' => config('session.driver'),
            'twoFactor' => $this->twoFactorProps($user),
            'recoveryCodes' => $request->session()->get('two_factor_recovery_codes'),
            'passkeys' => $user->passkeys()->latest()->get()->map(static fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'createdAt' => $passkey->created_at?->toIso8601String(),
                'lastUsedAt' => $passkey->last_used_at?->toIso8601String(),
            ])->values()->all(),
            'maxPasskeys' => PasskeyManager::MAX_PER_USER,
            'locales' => array_map(static fn (array $meta, string $code): array => [
                'code' => $code,
                'label' => trim((string) ($meta['name'] ?? $meta['native'] ?? $code).((string) ($meta['country'] ?? '') !== '' ? ' — '.(string) $meta['country'] : '')),
                'flag' => (string) ($meta['flag'] ?? '🌐'),
                'flagUrl' => (string) ($meta['flag_asset'] ?? ''),
            ], (array) config('localization.supported', ['en' => ['native' => 'English']]), array_keys((array) config('localization.supported', ['en' => ['native' => 'English']]))),
        ]);
    }

    /** @return array{enabled:bool,pending:bool,setupKey:?string,otpauthUri:?string,recoveryRemaining:int} */
    private function twoFactorProps(User $user): array
    {
        $manager = app(TwoFactorManager::class);
        $enabled = $manager->enabled($user);
        $pending = ! $enabled && is_string($user->two_factor_secret) && $user->two_factor_secret !== '';

        // The setup key is only ever shown to its owner while enrolment is unconfirmed.
        return [
            'enabled' => $enabled,
            'pending' => $pending,
            'setupKey' => $pending ? $user->two_factor_secret : null,
            'otpauthUri' => $pending ? app(TotpAuthenticator::class)->provisioningUri((string) $user->two_factor_secret, $user->email, (string) config('app.name', 'Nexora')) : null,
            'recoveryRemaining' => $enabled ? $manager->remainingRecoveryCodes($user) : 0,
        ];
    }

    public function update(Request $request, AuditManager $audit): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', Rule::in(array_keys((array) config('localization.supported', ['en' => []])))],
        ]);

        $emailChanged = $data['email'] !== $user->email;
        $user->fill($data);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();
        $audit->record('profile.updated', $user, ['email_changed' => $emailChanged]);

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request, AuditManager $audit, SessionSecurityManager $sessions): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);
        $sessions->rotateRememberToken($user);
        $sessions->revokeOtherSessions($user, $request);
        $sessions->rotateAuthenticatedSession($request);
        $audit->record('profile.password_changed', $user);

        return back()->with('success', 'Password changed.');
    }

    public function destroyOtherSessions(Request $request, AuditManager $audit): RedirectResponse
    {
        abort_unless(config('session.driver') === 'database', 422, 'Session management requires the database session driver.');
        $user = $request->user();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $audit->record('profile.sessions_revoked', $user);

        return back()->with('success', 'Other sessions signed out.');
    }
}
