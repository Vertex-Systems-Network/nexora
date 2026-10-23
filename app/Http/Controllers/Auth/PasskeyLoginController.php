<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Nexora\Enterprise\Services\SsoEnforcementPolicy;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Passkeys\PasskeyManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Passwordless sign-in with a passkey (SECURITY-BASELINE-200 / SYS-MFA-PASSKEY).
 * A user-verified passkey is phishing-resistant multi-factor on its own, so it does not
 * additionally require the TOTP challenge; account status and SSO enforcement still apply.
 */
final class PasskeyLoginController extends Controller
{
    public function options(Request $request, PasskeyManager $passkeys): JsonResponse
    {
        return response()->json(['publicKey' => $passkeys->loginOptions($request)]);
    }

    public function store(
        Request $request,
        PasskeyManager $passkeys,
        AuditManager $audit,
        InstallationState $installation,
        SessionSecurityManager $sessions,
        SsoEnforcementPolicy $ssoPolicy,
    ): JsonResponse {
        $data = $request->validate(['credential' => ['required', 'string', 'max:65535']]);
        $failed = fn (string $message): JsonResponse => response()->json(['message' => $message, 'errors' => ['passkey' => [$message]]], 422);

        $user = $passkeys->authenticate($request, $data['credential']);
        if (! $user instanceof User) {
            $audit->record('auth.passkey.login_failed');

            return $failed('That passkey could not be verified. Try again or sign in with your password.');
        }

        if ($user->status !== 'active') {
            $audit->record('auth.login_blocked', $user, ['reason' => 'inactive-account', 'method' => 'passkey']);

            return $failed('This account is not available for sign in.');
        }

        if ($ssoPolicy->requiresSso($user)) {
            $audit->record('auth.login_blocked', $user, ['reason' => 'enterprise-sso-required', 'method' => 'passkey']);

            return $failed('This organization requires SSO sign-in. Use an organization SSO option below.');
        }

        $audit->record('auth.passkey.login', $user);
        $redirect = app(AuthenticatedSessionController::class)->completeLogin($request, $user, false, $audit, $installation, $sessions);

        return response()->json(['redirect' => $redirect->getTargetUrl()]);
    }
}
