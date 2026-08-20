<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', ['status' => session('status')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        // Do not disclose whether an email address has an account.
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
