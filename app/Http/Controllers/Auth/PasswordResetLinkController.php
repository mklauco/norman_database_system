<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Shown whether or not the address belongs to an account, so the form cannot be used to find registered emails.
     */
    public const LINK_REQUESTED_MESSAGE = 'If an account with that email address exists, we have sent a password reset link to it.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Only active accounts get a link. The response is the same whether the
        // address is unknown, deactivated, throttled or the link was sent.
        Password::sendResetLink(
            $request->only('email') + ['active' => true]
        );

        return back()->with('status', self::LINK_REQUESTED_MESSAGE);
    }
}
