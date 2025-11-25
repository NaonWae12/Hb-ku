<x-guest-layout>
    @php
    $sessionEmail = $resetEmail ?? null;
    $oldReset = old('reset_mode');
    $resetMode = ($forceReset ?? false) || ($sessionEmail !== null) || ($oldReset && (string) $oldReset === '1');
    $existingEmail = $resetMode ? ($sessionEmail ?? old('email')) : null;
    @endphp

    <div class="mb-4 text-sm text-gray-600">
        @if($resetMode && $existingEmail)
        {{ __('Email terverifikasi. Silakan buat password baru untuk melanjutkan.') }}
        @else
        {{ __('Lupa password? Masukkan email Anda terlebih dahulu. Jika email ditemukan, kami akan meminta Anda mengisi password baru tanpa harus membuka email.') }}
        @endif
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <input type="hidden" name="reset_mode" value="{{ $resetMode ? 1 : 0 }}">

        @if($resetMode && $existingEmail)
        <input type="hidden" name="email" value="{{ $existingEmail }}">

        <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-800 mb-4">
            {{ __('Kami akan mengganti password untuk email berikut:') }}
        </div>

        <div class="mb-4">
            <x-input-label for="email-display" :value="__('Email')" />
            <x-text-input id="email-display" class="block mt-1 w-full bg-gray-100" type="email" value="{{ $existingEmail }}" disabled />
            <p class="mt-2 text-xs text-gray-500">
                {{ __('Bukan email kamu?') }}
                <a href="{{ route('password.request', ['fresh' => 1]) }}" class="font-semibold text-red-600 hover:underline">
                    {{ __('Mulai ulang') }}
                </a>
            </p>
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password Baru')" />

            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        @else
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        @endif

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-gray-600 hover:text-red-600" href="{{ url()->previous() === url()->current() ? route('login') : url()->previous() }}">
                &larr; {{ __('Kembali') }}
            </a>

            <x-primary-button>
                {{ $resetMode ? __('Update Password') : __('Lanjutkan') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>