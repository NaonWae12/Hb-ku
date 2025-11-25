<x-guest-layout>
    @php
    $sessionEmail = $resetEmail ?? null;
    $oldReset = old('reset_mode');
    $resetMode = ($forceReset ?? false)
    || ($sessionEmail !== null)
    || ($oldReset && (string) $oldReset === '1');
    $existingEmail = $resetMode ? ($sessionEmail ?? old('email')) : null;
    @endphp

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <input type="hidden" name="reset_mode" value="{{ $resetMode ? 1 : 0 }}">

        @if($resetMode && $existingEmail)
        <input type="hidden" name="email" value="{{ $existingEmail }}">

        <div class="mb-6 rounded-md bg-blue-50 p-4 text-sm text-blue-800">
            {{ __('This email is already registered. Set a new password to continue.') }}
        </div>

        <div>
            <x-input-label for="email-display" :value="__('Email')" />
            <x-text-input id="email-display" class="block mt-1 w-full bg-gray-100" type="email" value="{{ $existingEmail }}" disabled />
            <p class="mt-2 text-xs text-gray-500">
                {{ __('Want to register a different email?') }}
                <a href="{{ route('register', ['fresh' => 1]) }}" class="font-semibold text-red-600 hover:underline">
                    {{ __('Start over') }}
                </a>
            </p>
        </div>
        @else
        <div class="mb-6 rounded-md bg-slate-50 p-4 text-sm text-slate-700">
            {{ __('Daftar akun baru dengan mengisi data Anda.') }}
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nama')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        @endif

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="$resetMode ? __('New Password') : __('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="$resetMode ? __('Confirm New Password') : __('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-red-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            @if($resetMode)
            <x-primary-button class="ms-4">
                {{ __('Update Password') }}
            </x-primary-button>
            @else
            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
            @endif
        </div>
    </form>
</x-guest-layout>