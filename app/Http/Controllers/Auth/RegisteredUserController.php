<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        if ($request->boolean('fresh')) {
            session()->forget(['existingEmail', 'showReset']);
        }

        $resetEmail = session()->pull('existingEmail');
        $forceReset = session()->pull('showReset', false);

        return view('auth.register', [
            'resetEmail' => $resetEmail,
            'forceReset' => $forceReset,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower((string) $request->email)]);

        // Always ensure we are dealing with a properly formatted email.
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $existingUser = User::where('email', $request->email)->first();

        // Jika user sudah ada dan belum dalam mode reset, tampilkan form reset password
        if ($existingUser && ! $request->boolean('reset_mode')) {
            return redirect()
                ->route('register')
                ->withInput(['email' => $request->email, 'reset_mode' => 1])
                ->with('existingEmail', $request->email)
                ->with('showReset', true);
        }

        // Validasi untuk user baru (name hanya diperlukan untuk user baru)
        if (! $existingUser) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);
        }

        // Validasi password untuk kedua kasus (user baru atau reset password)
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Jika user sudah ada (mode reset password)
        if ($existingUser) {
            $existingUser->forceFill([
                'password' => Hash::make($request->password),
            ])->save();

            return redirect()->route('login')->with('status', __('Password berhasil diperbarui. Silakan masuk.'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Auto login setelah registrasi
        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
