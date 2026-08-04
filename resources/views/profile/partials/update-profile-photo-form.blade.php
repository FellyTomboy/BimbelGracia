<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Foto Profile') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Upload foto profile untuk ditampilkan di halaman Our Teachers. Foto perlu disetujui admin terlebih dahulu.') }}
        </p>
    </header>

    @php $teacher = auth()->user()?->teacher; @endphp

    <div class="mt-6 space-y-4">
        {{-- Upload --}}
        <form method="post" action="{{ route('profile.photo.upload') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg" class="mt-1 w-full rounded-md border-gray-300 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" required />
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                <p class="text-xs text-gray-500 mt-1">Format: JPG/PNG. Maks 2MB.</p>
            </div>
            <x-primary-button>{{ __('Upload') }}</x-primary-button>
        </form>

        {{-- Preview --}}
        @if ($teacher?->profile_photo_url)
            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                <img src="{{ $teacher->profile_photo_url }}" alt="Profile" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                <div>
                    <p class="font-medium text-sm">Foto saat ini</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Status:
                        @if ($teacher->profile_photo_approved)
                            <span class="text-emerald-600 font-medium">Disetujui ✅</span>
                        @else
                            <span class="text-amber-600 font-medium">Menunggu persetujuan admin ⏳</span>
                        @endif
                    </p>
                    <form method="post" action="{{ route('profile.photo.delete') }}" class="mt-2" onsubmit="return confirm('Hapus foto profile?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-rose-600 hover:text-rose-800">Hapus Foto</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</section>