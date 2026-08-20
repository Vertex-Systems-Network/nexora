<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Session\SessionSecurityManager;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request, SessionSecurityManager $sessions, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create($data);

        if ($role = Role::query()->where('slug', 'user')->first()) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        event(new Registered($user));
        Auth::login($user);
        $sessions->rotateAuthenticatedSession($request);
        $audit->record('auth.registered', $user);

        return redirect()->route('verification.notice');
    }
}
