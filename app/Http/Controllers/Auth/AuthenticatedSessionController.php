<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Nexora\Enterprise\Services\SsoEnforcementPolicy;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AuthenticatedSessionController extends Controller
{
    public function create(SsoEnforcementPolicy $ssoPolicy): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
            'sso' => $ssoPolicy->loginContext(),
        ]);
    }

    public function store(
        Request $request,
        AuditManager $audit,
        InstallationState $installation,
        SessionSecurityManager $sessions,
        SsoEnforcementPolicy $ssoPolicy,
        TwoFactorManager $twoFactor,
    ): RedirectResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        // Validate without logging in: MFA users must pass the challenge before any session or remember cookie exists.
        if (! Auth::validate(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $audit->record('auth.login_failed', metadata: [
                'email_hash' => hash('sha256', strtolower((string) $credentials['email'])),
            ]);

            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        $user = Auth::getLastAttempted();
        if (! $user instanceof User) {
            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }
        // Auth::validate() skips the rehash Auth::attempt() performed; keep password hashes current.
        if (config('hashing.rehash_on_login', true)) {
            Auth::getProvider()->rehashPasswordIfRequired($user, ['password' => $credentials['password']]);
        }

        if ($user->status !== 'active') {
            $audit->record('auth.login_blocked', $user, ['reason' => 'inactive-account']);

            throw ValidationException::withMessages(['email' => 'This account is not available for sign in.']);
        }

        if ($ssoPolicy->requiresSso($user)) {
            $audit->record('auth.login_blocked', $user, ['reason' => 'enterprise-sso-required']);
            // No session was created, but end any earlier one in this browser before refusing.
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This organization requires SSO sign-in. Use an organization SSO option below.',
            ]);
        }

        $remember = (bool) ($credentials['remember'] ?? false);
        if ($twoFactor->enabled($user)) {
            $sessions->rotateAuthenticatedSession($request);
            $request->session()->put(TwoFactorChallengeController::SESSION_KEY, [
                'user_id' => $user->id,
                'remember' => $remember,
                'expires_at' => now()->addMinutes(TwoFactorChallengeController::TTL_MINUTES)->getTimestamp(),
                'attempts' => 0,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        return $this->completeLogin($request, $user, $remember, $audit, $installation, $sessions);
    }

    /** Final login step shared with TwoFactorChallengeController (SYS-MFA-TOTP); includes installer-owner verification recovery. */
    public function completeLogin(
        Request $request,
        User $user,
        bool $remember,
        AuditManager $audit,
        InstallationState $installation,
        SessionSecurityManager $sessions,
    ): RedirectResponse {
        Auth::login($user, $remember);
        $sessions->rotateAuthenticatedSession($request);

        if (! $user->hasVerifiedEmail()) {
            $metadata = $installation->metadata();
            $isInstallerOwner = (int) ($metadata['admin_user_id'] ?? 0) === (int) $user->id;

            if ($isInstallerOwner && $user->hasRole('super-admin')) {
                $user->forceFill(['email_verified_at' => now()]);
                $audit->record('auth.installer_owner_verified', $user, ['source' => 'installation-lock-recovery']);
            }
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.login', $user);

        if ($user?->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Ordinary customer/member accounts must never inherit an intended Admin
        // URL and land on a permission error after an otherwise successful sign-in.
        $request->session()->forget('url.intended');

        return redirect()->route('portal.dashboard');
    }

    public function destroy(Request $request, AuditManager $audit, SessionSecurityManager $sessions): RedirectResponse
    {
        $user = $request->user();
        $audit->record('auth.logout', $user);
        Auth::guard('web')->logout();
        $sessions->invalidateCurrentSession($request);

        return redirect()->route('login');
    }
}
