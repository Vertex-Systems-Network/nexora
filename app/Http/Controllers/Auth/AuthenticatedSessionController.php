<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]);
    }

    public function store(Request $request, AuditManager $audit, InstallationState $installation, SessionSecurityManager $sessions): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], (bool) ($credentials['remember'] ?? false))) {
            $audit->record('auth.login_failed', metadata: [
                'email_hash' => hash('sha256', strtolower((string) $credentials['email'])),
            ]);

            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        $user = $request->user();
        if ($user !== null && $user->status !== 'active') {
            $audit->record('auth.login_blocked', $user, ['reason' => 'inactive-account']);
            Auth::logout();
            $sessions->invalidateCurrentSession($request);

            throw ValidationException::withMessages(['email' => 'This account is not available for sign in.']);
        }

        $sessions->rotateAuthenticatedSession($request);

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $metadata = $installation->metadata();
            $isInstallerOwner = (int) ($metadata['admin_user_id'] ?? 0) === (int) $user->id;

            if ($isInstallerOwner && $user->hasRole('super-admin')) {
                $user->forceFill(['email_verified_at' => now()]);
                $audit->record('auth.installer_owner_verified', $user, ['source' => 'installation-lock-recovery']);
            }
        }

        $user?->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.login', $user);

        return redirect()->intended(route('admin.dashboard'));
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
