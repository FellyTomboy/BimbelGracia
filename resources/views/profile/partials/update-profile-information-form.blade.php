<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informasi Profil') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Perbarui informasi profil akun Anda.
        </p>
    </header>

    @php
        $parent = auth()->user()?->parent;
        $students = $parent?->students ?? collect();
    @endphp

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nama Orang Tua / Wali')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', auth()->user()?->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Nomor Telepon')" />
            <x-text-input id="phone" type="text" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" :value="auth()->user()?->phone ?? '-'" disabled />
            <p class="mt-1 text-xs text-amber-600">
                <svg class="inline w-3 h-3 mr-1 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Hubungi admin untuk mengubah nomor telepon.
            </p>
        </div>

        <div>
            <x-input-label for="address" :value="__('Alamat')" />
            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address', $parent?->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        {{-- Daftar Murid --}}
        @if ($students->isNotEmpty())
            <div>
                <x-input-label :value="__('Murid Terdaftar')" />
                <div class="mt-2 space-y-2">
                    @foreach ($students as $student)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600 shrink-0">
                                {{ strtoupper(substr($student->display_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $student->full_name ?? $student->nickname ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $student->nickname ? 'Panggilan: ' . $student->nickname : '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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
