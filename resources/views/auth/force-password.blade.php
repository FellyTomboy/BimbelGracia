<x-guest-layout>
    <x-slot name="title">Ganti Password</x-slot>
    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('put')

        <div>
            <x-password-input name="current_password" id="current_password" required>
                {{ __('Password Saat Ini') }}
            </x-password-input>
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-password-input name="password" id="password" required>
                {{ __('Password Baru') }}
            </x-password-input>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-password-input name="password_confirmation" id="password_confirmation" required>
                {{ __('Konfirmasi Password Baru') }}
            </x-password-input>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Simpan Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
