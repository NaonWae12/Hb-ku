<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(Request $request): View
    {
        if ($request->boolean('fresh')) {
            session()->forget(['passwordResetEmail', 'passwordResetForce']);
        }

        $resetEmail = session()->pull('passwordResetEmail');
        $forceReset = session()->pull('passwordResetForce', false);

        return view('auth.forgot-password', [
            'resetEmail' => $resetEmail,
            'forceReset' => $forceReset,
        ]);
    }

    /**
     * Handle the password reset flow without sending email links.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower((string) $request->email)]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Email tidak ditemukan.')]);
        }

        if (! $request->boolean('reset_mode')) {
            return redirect()
                ->route('password.request')
                ->with('passwordResetEmail', $user->email)
                ->with('passwordResetForce', true);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', __('Password berhasil diperbarui. Silakan masuk.'));
    }
}
