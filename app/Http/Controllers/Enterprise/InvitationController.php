<?php

declare(strict_types=1);

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Nexora\Enterprise\Services\InvitationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class InvitationController extends Controller
{
    public function accept(Request $request, string $token, InvitationManager $invitations): RedirectResponse
    {
        $member = $invitations->accept($token, $request->user());
        $request->session()->put('nexora.enterprise.organization_id', $member->organization_id);

        $destination = $request->user()->canAccessAdmin()
            ? '/admin'
            : route('portal.dashboard');

        return redirect($destination)->with('success', 'Organization invitation accepted.');
    }
}
