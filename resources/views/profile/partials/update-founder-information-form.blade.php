<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informasi Co-Founder') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Kelola data co-founder yang ditampilkan di halaman utama.') }}
        </p>
    </header>

    @if ($founders->isEmpty())
        <div class="mt-6 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl text-sm">
            Belum ada co-founder. Setiap guru dengan status <strong>is_founder</strong> akan muncul di sini.
        </div>
    @else
        @foreach ($founders as $founder)
            <div class="mt-6 p-4 border border-gray-200 rounded-xl">
                <div class="flex items-center gap-4 mb-4">
                    @if ($founder->profile_photo_url)
                        <img src="{{ $founder->profile_photo_url }}" alt="{{ $founder->displayName }}"
                            class="w-14 h-14 rounded-full object-cover border-2 border-gray-200">
                    @else
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 font-bold text-lg">
                            {{ substr($founder->displayName, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $founder->displayName }}</h3>
                        <p class="text-xs text-gray-500">Co-Founder</p>
                    </div>
                </div>

                <form method="post" action="{{ route('profile.founder.update', $founder) }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="founder_name_{{ $founder->id }}" :value="__('Nama')" />
                        <x-text-input id="founder_name_{{ $founder->id }}" name="founder_name" type="text" class="mt-1 block w-full" :value="old('founder_name', $founder->full_name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('founder_name')" />
                    </div>

                    <div>
                        <x-input-label for="founder_major_{{ $founder->id }}" :value="__('Bidang / Keahlian')" />
                        <x-text-input id="founder_major_{{ $founder->id }}" name="founder_major" type="text" class="mt-1 block w-full" :value="old('founder_major', $founder->major)" />
                        <p class="mt-1 text-xs text-gray-500">Contoh: S.Pd., M.Pd. / Pendidikan Matematika</p>
                        <x-input-error class="mt-2" :messages="$errors->get('founder_major')" />
                    </div>

                    <div>
                        <x-input-label for="founder_description_{{ $founder->id }}" :value="__('Deskripsi')" />
                        <textarea id="founder_description_{{ $founder->id }}" name="founder_description" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Deskripsi singkat tentang co-founder">{{ old('founder_description', $founder->founder_description) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('founder_description')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                    </div>
                </form>

                <form method="post" action="{{ route('profile.founder.photo', $founder) }}" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-200">
                    @csrf
                    <div>
                        <x-input-label :value="__('Foto Profil')" />
                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required />
                        <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                    </div>
                    <div class="mt-3">
                        <x-primary-button>{{ __('Upload Foto') }}</x-primary-button>
                    </div>
                </form>
            </div>
        @endforeach

        @if (session('status') === 'founder-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="mt-4 text-sm text-gray-600"
            >{{ __('Tersimpan.') }}</p>
        @endif
    @endif
</section>