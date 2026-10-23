<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passkey;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Passkeys\PasskeyManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Self-service passkey management (SECURITY-BASELINE-200 / SYS-MFA-PASSKEY). Adding or removing one re-proves the password. */
final class ProfilePasskeyController extends Controller
{
    public function options(Request $request, PasskeyManager $passkeys): JsonResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();
        if ($user->passkeys()->count() >= PasskeyManager::MAX_PER_USER) {
            throw ValidationException::withMessages(['passkey' => 'You have reached the maximum of '.PasskeyManager::MAX_PER_USER.' passkeys. Remove one first.']);
        }

        return response()->json(['publicKey' => $passkeys->registrationOptions($request, $user)]);
    }

    public function store(Request $request, PasskeyManager $passkeys, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'credential' => ['required', 'string', 'max:65535'],
        ]);
        $user = $request->user();
        $passkey = $passkeys->register($request, $user, $data['credential'], trim($data['name']));
        if (! $passkey instanceof Passkey) {
            throw ValidationException::withMessages(['passkey' => 'The passkey could not be registered. Start again and use the same device prompt.']);
        }
        $audit->record('auth.passkey.added', $user, ['passkey_id' => $passkey->id]);

        return back()->with('status', 'Passkey added. You can now sign in with it.');
    }

    public function destroy(Request $request, Passkey $passkey, AuditManager $audit): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();
        abort_unless($passkey->user_id === $user->id, 404);
        $passkey->delete();
        $audit->record('auth.passkey.removed', $user, ['passkey_id' => $passkey->id]);

        return back()->with('status', 'Passkey removed.');
    }
}
