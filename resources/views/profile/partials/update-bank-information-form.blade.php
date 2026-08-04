<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informasi Rekening') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Update nomor rekening untuk pencairan gaji.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.bank.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        @php
            $teacher = auth()->user()?->teacher;
        @endphp

        <div>
            <x-input-label for="bank_name" :value="__('Nama Bank')" />
            <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name', $teacher?->bank_name)" />
            <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
        </div>

        <div>
            <x-input-label for="bank_account" :value="__('No Rekening')" />
            <x-text-input id="bank_account" name="bank_account" type="text" class="mt-1 block w-full" :value="old('bank_account', $teacher?->bank_account)" />
            <x-input-error class="mt-2" :messages="$errors->get('bank_account')" />
        </div>

        <div>
            <x-input-label for="bank_owner" :value="__('Nama Pemilik Rekening')" />
            <x-text-input id="bank_owner" name="bank_owner" type="text" class="mt-1 block w-full" :value="old('bank_owner', $teacher?->bank_owner)" />
            <x-input-error class="mt-2" :messages="$errors->get('bank_owner')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            @if (session('status') === 'bank-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>
</section>