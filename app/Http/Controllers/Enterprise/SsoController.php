<?php

declare(strict_types=1);

namespace App\Http\Controllers\Enterprise;

use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseSsoProvider;
use App\Models\User;
use App\Nexora\Enterprise\Services\EnterpriseAuditRecorder;
use App\Nexora\Enterprise\Services\SsoProviderRegistry;
use App\Nexora\Security\Session\SessionSecurityManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

final class SsoController
{
    public function start(
        Request $request,
        EnterpriseOrganization $organization,
        string $provider,
        SsoProviderRegistry $registry,
    ): RedirectResponse {
        abort_unless($organization->status === 'active', 404);

        $record = EnterpriseSsoProvider::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $provider)
            ->where('enabled', true)
            ->firstOrFail();
        $adapter = $registry->get($record->adapter_key);
        abort_unless($adapter && $adapter->protocol() === $record->protocol, 503, 'Identity adapter unavailable.');

        $state = Str::random(48);
        $request->session()->put('nexora.enterprise.sso.'.$record->id, [
            'state_hash' => hash('sha256', $state),
            'organization_id' => $organization->id,
            'provider_id' => $record->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        try {
            $redirectUrl = $adapter->redirectUrl($record, $state);
        } catch (Throwable) {
            abort(503, 'Identity provider redirect is unavailable.');
        }

        $scheme = strtolower((string) parse_url($redirectUrl, PHP_URL_SCHEME));
        $host = (string) parse_url($redirectUrl, PHP_URL_HOST);
        abort_unless(in_array($scheme, ['https', 'http'], true) && $host !== '', 503, 'Identity provider redirect is invalid.');

        return redirect()->away($redirectUrl);
    }

    public function callback(
        Request $request,
        EnterpriseOrganization $organization,
        string $provider,
        SsoProviderRegistry $registry,
        SessionSecurityManager $sessions,
        EnterpriseAuditRecorder $audit,
    ): RedirectResponse {
        abort_unless($organization->status === 'active', 404);

        $record = EnterpriseSsoProvider::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $provider)
            ->where('enabled', true)
            ->firstOrFail();

        $saved = (array) $request->session()->pull('nexora.enterprise.sso.'.$record->id, []);
        abort_if($saved === [] || ($saved['expires_at'] ?? 0) < time(), 419, 'SSO state expired.');
        abort_unless(
            (string) ($saved['organization_id'] ?? '') === (string) $organization->id
            && (string) ($saved['provider_id'] ?? '') === (string) $record->id,
            419,
            'Invalid SSO state.',
        );

        $state = (string) ($request->input('state') ?? $request->input('RelayState', ''));
        abort_if(
            $state === '' || ! hash_equals((string) $saved['state_hash'], hash('sha256', $state)),
            419,
            'Invalid SSO state.',
        );

        $adapter = $registry->get($record->adapter_key);
        abort_unless($adapter && $adapter->protocol() === $record->protocol, 503, 'Identity adapter unavailable.');

        try {
            $identity = $adapter->resolveIdentity($record, $request);
        } catch (Throwable) {
            abort(403, 'Identity provider sign-in failed.');
        }

        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        abort_unless($email !== '' && strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 403, 'Identity provider sign-in failed.');

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
        abort_unless($user !== null && $user->status === 'active', 403, 'Identity provider sign-in failed.');

        $member = EnterpriseOrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        abort_unless($member !== null, 403, 'Identity provider sign-in failed.');

        Auth::login($user, false);
        $sessions->rotateAuthenticatedSession($request);
        $request->session()->put('nexora.enterprise.organization_id', $organization->id);
        $user->forceFill(['last_login_at' => now()])->save();

        $audit->record(
            'enterprise.sso.login',
            $organization->id,
            $user->id,
            'sso_provider',
            $record->id,
            ['provider' => $record->slug, 'protocol' => $record->protocol],
        );

        return redirect('/admin');
    }
}
