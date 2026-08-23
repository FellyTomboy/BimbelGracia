<section>
    @php
        $teacher = auth()->user()?->teacher;
        $user = auth()->user();
    @endphp

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informasi Guru') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Perbarui data profil guru Anda.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="full_name" :value="__('Nama Lengkap')" />
            <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name', $teacher?->full_name)" autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
        </div>

        <div>
            <x-input-label for="nickname" :value="__('Nama Panggilan')" />
            <x-text-input id="nickname" name="nickname" type="text" class="mt-1 block w-full" :value="old('nickname', $teacher?->nickname)" autocomplete="nickname" />
            <x-input-error class="mt-2" :messages="$errors->get('nickname')" />
        </div>

        <div>
            <x-input-label for="subjects" :value="__('Mata Pelajaran')" />
            <x-text-input id="subjects" name="subjects" type="text" class="mt-1 block w-full" :value="old('subjects', $teacher?->subjects)" placeholder="Contoh: Matematika, Fisika" />
            <x-input-error class="mt-2" :messages="$errors->get('subjects')" />
        </div>

        <div>
            <x-input-label for="major" :value="__('Jurusan')" />
            <x-text-input id="major" name="major" type="text" class="mt-1 block w-full" :value="old('major', $teacher?->major)" />
            <x-input-error class="mt-2" :messages="$errors->get('major')" />
        </div>

        <div>
            <x-input-label for="address" :value="__('Alamat')" />
            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address', $teacher?->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div>
            <x-input-label for="phone_display" :value="__('Nomor Telepon')" />
            <x-text-input id="phone_display" type="text" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" :value="$user->phone ?? '-'" disabled />
            <p class="mt-1 text-xs text-amber-600">
                <svg class="inline w-3 h-3 mr-1 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Hubungi admin untuk mengubah nomor telepon.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">
                    {{ __('Tersimpan.') }}
                </p>
            @endif
        </div>
    </form>
</section>
