<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Pengaturan Denda') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Aktifkan atau nonaktifkan denda keterlambatan dan absensi.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.fine-settings.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Denda Minimal Presensi --}}
        <div class="flex items-start justify-between gap-4 py-4 border-b border-gray-100">
            <div>
                <p class="font-medium text-gray-900">Denda Minimal Presensi</p>
                <p class="mt-1 text-sm text-gray-500">
                    Tambahan Rp 5.000 per pertemuan saat kehadiran murid kurang dari 50% sesi yang disepakati.
                    Mempengaruhi tagihan orang tua.
                </p>
            </div>
            <div class="mt-1">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="attendance_penalty_enabled"
                        value="1"
                        class="sr-only peer"
                        {{ $fineSettings['attendance_penalty_enabled'] ? 'checked' : '' }}
                    />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-600 rounded-full peer peer-checked:after:translate-x-5 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
        </div>

        {{-- Denda Keterlambatan --}}
        <div class="flex items-start justify-between gap-4 py-4">
            <div>
                <p class="font-medium text-gray-900">Denda Keterlambatan</p>
                <p class="mt-1 text-sm text-gray-500">
                    Potongan 10% dari tarif per pertemuan jika presensi diisi setelah waktu pelaksanaan.
                    Mempengaruhi gaji guru.
                </p>
            </div>
            <div class="mt-1">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="late_penalty_enabled"
                        value="1"
                        class="sr-only peer"
                        {{ $fineSettings['late_penalty_enabled'] ? 'checked' : '' }}
                    />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-600 rounded-full peer peer-checked:after:translate-x-5 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>
            <button
                type="submit"
                form="reset-fine-settings-form"
                class="px-4 py-2 rounded-md border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 transition-colors"
            >
                Reset — Matikan Semua
            </button>
        </div>
    </form>

    <form id="reset-fine-settings-form" method="POST" action="{{ route('profile.fine-settings.reset') }}" class="hidden">
        @csrf
    </form>

    @if (session('status') === 'fine-settings-updated')
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
            class="mt-4 text-sm text-gray-600">{{ __('Tersimpan.') }}</p>
    @endif
</section>
