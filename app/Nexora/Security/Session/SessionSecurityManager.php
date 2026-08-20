<?php

declare(strict_types=1);

namespace App\Nexora\Security\Session;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SessionSecurityManager
{
    public function rotateAuthenticatedSession(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }

    public function invalidateCurrentSession(Request $request): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function rotateRememberToken(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }

    public function revokeOtherSessions(User $user, Request $request): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }

    public function revokeAllSessions(User $user): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
