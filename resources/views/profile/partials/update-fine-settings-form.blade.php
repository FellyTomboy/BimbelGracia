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
            <div class="mt-1" x-data="{ value: {{ $fineSettings['attendance_penalty_enabled'] ? 'true' : 'false' }} }">
                <button
                    type="button"
                    role="switch"
                    :aria-checked="value"
                    @click="value = value === 'true' ? 'false' : 'true'"
                    :class="value === 'true' ? 'bg-indigo-600' : 'bg-gray-200'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600"
                >
                    <span
                        :class="value === 'true' ? 'translate-x-5' : 'translate-x-0'"
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    ></span>
                </button>
                <input type="hidden" name="attendance_penalty_enabled" x-model="value" />
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
            <div class="mt-1" x-data="{ value: {{ $fineSettings['late_penalty_enabled'] ? 'true' : 'false' }} }">
                <button
                    type="button"
                    role="switch"
                    :aria-checked="value"
                    @click="value = value === 'true' ? 'false' : 'true'"
                    :class="value === 'true' ? 'bg-indigo-600' : 'bg-gray-200'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600"
                >
                    <span
                        :class="value === 'true' ? 'translate-x-5' : 'translate-x-0'"
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    ></span>
                </button>
                <input type="hidden" name="late_penalty_enabled" x-model="value" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>
        </div>
    </form>

    @if (session('status') === 'fine-settings-updated')
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
            class="mt-4 text-sm text-gray-600">{{ __('Tersimpan.') }}</p>
    @endif
</section>
