<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Second login step for users with confirmed TOTP (SECURITY-BASELINE-200 / SYS-MFA-TOTP).
 * The pending state is not an authenticated session: the user stays a guest until a code passes.
 */
final class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'nexora.two_factor.pending';

    public const TTL_MINUTES = 5;

    public const MAX_ATTEMPTS = 5;

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->pending($request) === null) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(
        Request $request,
        TwoFactorManager $twoFactor,
        AuditManager $audit,
        InstallationState $installation,
        SessionSecurityManager $sessions,
    ): RedirectResponse {
        $pending = $this->pending($request);
        if ($pending === null) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in expired. Please sign in again.']);
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $user = User::query()->find($pending['user_id']);
        if (! $user instanceof User || $user->status !== 'active' || ! $twoFactor->enabled($user)) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->withErrors(['email' => 'This account is not available for sign in.']);
        }

        if ($twoFactor->tooManyFailures($user)) {
            $request->session()->forget(self::SESSION_KEY);
            $audit->record('auth.two_factor.locked', $user, ['channel' => 'web']);

            return redirect()->route('login')->withErrors(['email' => 'Too many invalid codes for this account. Try again later.']);
        }

        $method = $twoFactor->verifyForLogin($user, $data['code']);
        if ($method === null) {
            $pending['attempts']++;
            $audit->record('auth.two_factor.challenge_failed', $user, ['attempts' => $pending['attempts']]);
            if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
                $request->session()->forget(self::SESSION_KEY);

                return redirect()->route('login')->withErrors(['email' => 'Too many invalid codes. Please sign in again.']);
            }
            $request->session()->put(self::SESSION_KEY, $pending);

            throw ValidationException::withMessages(['code' => 'The code is invalid or has already been used.']);
        }

        $request->session()->forget(self::SESSION_KEY);
        $audit->record($method === 'recovery' ? 'auth.two_factor.recovery_used' : 'auth.two_factor.challenge_passed', $user, [
            'remaining_recovery_codes' => $twoFactor->remainingRecoveryCodes($user),
        ]);

        return app(AuthenticatedSessionController::class)->completeLogin($request, $user, (bool) $pending['remember'], $audit, $installation, $sessions);
    }

    /** @return array{user_id:int,remember:bool,expires_at:int,attempts:int}|null */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || ! isset($pending['user_id'], $pending['expires_at']) || (int) $pending['expires_at'] < now()->getTimestamp()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return [
            'user_id' => (int) $pending['user_id'],
            'remember' => (bool) ($pending['remember'] ?? false),
            'expires_at' => (int) $pending['expires_at'],
            'attempts' => (int) ($pending['attempts'] ?? 0),
        ];
    }
}
