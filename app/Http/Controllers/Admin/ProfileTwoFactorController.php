<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Self-service TOTP enrolment (SECURITY-BASELINE-200 / SYS-MFA-TOTP). Every state change re-proves the password. */
final class ProfileTwoFactorController extends Controller
{
    public function store(Request $request, TwoFactorManager $twoFactor): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();
        if ($twoFactor->enabled($user)) {
            throw ValidationException::withMessages(['current_password' => 'Two-factor authentication is already enabled.']);
        }
        $twoFactor->begin($user);

        return back()->with('status', 'Scan the setup key with your authenticator app, then confirm with a code.');
    }

    public function confirm(Request $request, TwoFactorManager $twoFactor, AuditManager $audit, SessionSecurityManager $sessions): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:16']]);
        $user = $request->user();
        $codes = $twoFactor->confirm($user, $data['code']);
        if ($codes === null) {
            throw ValidationException::withMessages(['code' => 'The code is invalid. Check the time on your device and try again.']);
        }
        // Sessions and remember-me cookies obtained with the password alone must not outlive enrolment.
        $revoked = $sessions->revokeOtherSessions($user, $request);
        $sessions->rotateRememberToken($user);
        $audit->record('auth.two_factor.enabled', $user, ['revoked_sessions' => $revoked]);

        return back()->with('two_factor_recovery_codes', $codes)->with('status', 'Two-factor authentication is on. Save your recovery codes now.');
    }

    public function recoveryCodes(Request $request, TwoFactorManager $twoFactor, AuditManager $audit): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();
        if (! $twoFactor->enabled($user)) {
            throw ValidationException::withMessages(['current_password' => 'Two-factor authentication is not enabled.']);
        }
        $codes = $twoFactor->regenerateRecoveryCodes($user);
        $audit->record('auth.two_factor.recovery_regenerated', $user);

        return back()->with('two_factor_recovery_codes', $codes)->with('status', 'New recovery codes generated. Previous codes no longer work.');
    }

    public function destroy(Request $request, TwoFactorManager $twoFactor, AuditManager $audit): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();
        $wasEnabled = $twoFactor->enabled($user);
        if ($wasEnabled) {
            // A hijacked session plus the password must not be enough to strip the second factor.
            $code = (string) $request->validate(['code' => ['required', 'string', 'max:32']])['code'];
            if ($twoFactor->verifyForLogin($user, $code) === null) {
                throw ValidationException::withMessages(['code' => 'Enter a current authenticator or recovery code to turn off two-factor authentication.']);
            }
        }
        $twoFactor->disable($user);
        if ($wasEnabled) {
            $audit->record('auth.two_factor.disabled', $user);
        }

        return back()->with('status', 'Two-factor authentication is off.');
    }
}
