<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Pengaturan Denda') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Aktifkan atau nonaktifkan denda keterlambatan dan absensi, serta atur nominalnya.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.fine-settings.update') }}" class="mt-6 space-y-6" id="fine-settings-form">
        @csrf
        @method('patch')

        <input type="hidden" name="attendance_penalty_enabled" value="{{ $fineSettings['attendance_penalty_enabled'] ? '1' : '0' }}">
        <input type="hidden" name="late_penalty_enabled" value="{{ $fineSettings['late_penalty_enabled'] ? '1' : '0' }}">
        <input type="hidden" name="attendance_penalty_type" value="{{ $fineSettings['attendance_penalty_type'] ?? 'fixed' }}">
        <input type="hidden" name="late_penalty_type" value="{{ $fineSettings['late_penalty_type'] ?? 'percent' }}">

        {{-- Denda Minimal Presensi --}}
        <div class="border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <p class="font-medium text-gray-900">Denda Minimal Presensi</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Tambahan biaya saat kehadiran murid kurang dari 50% sesi yang disepakati.
                        Mempengaruhi tagihan orang tua.
                    </p>
                </div>
                <div class="mt-1">
                    <button
                        type="button"
                        id="toggle-attendance"
                        onclick="toggleFine('attendance')"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none"
                        style="background-color: {{ $fineSettings['attendance_penalty_enabled'] ? '#4f46e5' : '#e5e7eb' }};"
                    >
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition-transform duration-200"
                            style="transform: translateX({{ $fineSettings['attendance_penalty_enabled'] ? '20px' : '2px' }});"
                        ></span>
                    </button>
                </div>
            </div>

            {{-- Tipe nominal: Nominal / Persentase --}}
            <div class="mt-3 flex rounded-md shadow-sm">
                <button
                    type="button"
                    id="btn-attendance-type-fixed"
                    onclick="togglePenaltyType('attendance', 'fixed')"
                    class="flex-1 px-3 py-1.5 text-xs rounded-l-md border border-gray-300 transition-colors {{ ($fineSettings['attendance_penalty_type'] ?? 'fixed') === 'fixed' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}"
                >Nominal (Rp)</button>
                <button
                    type="button"
                    id="btn-attendance-type-percent"
                    onclick="togglePenaltyType('attendance', 'percent')"
                    class="flex-1 px-3 py-1.5 text-xs rounded-r-md border border-l-0 border-gray-300 transition-colors {{ ($fineSettings['attendance_penalty_type'] ?? 'fixed') === 'percent' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}"
                >Persentase (%)</button>
            </div>

            {{-- Nominal input --}}
            <div class="mt-4 flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nominal per pertemuan</label>
                    <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600">
                        <input
                            type="number"
                            name="attendance_penalty_value"
                            id="attendance-penalty-value"
                            min="0"
                            step="1"
                            value="{{ $fineSettings['attendance_penalty_value'] ?? 5000 }}"
                            oninput="updateAttendancePreview()"
                            class="block flex-1 border-0 bg-transparent py-1.5 px-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                            style="min-width: 0;"
                        />
                    </div>
                </div>
                <p class="text-sm text-gray-500 pb-2 whitespace-nowrap">per pertemuan</p>
            </div>

            <p class="mt-2 text-xs text-gray-500">
                Preview: <span id="attendance-preview-text">
                    @if(($fineSettings['attendance_penalty_type'] ?? 'fixed') === 'percent')
                        {{ $fineSettings['attendance_penalty_value'] ?? 0 }}% dari tarif per pertemuan
                    @else
                        Rp {{ number_format($fineSettings['attendance_penalty_value'] ?? 5000) }} per pertemuan
                    @endif
                </span>
            </p>
        </div>

        {{-- Denda Keterlambatan --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <p class="font-medium text-gray-900">Denda Keterlambatan</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Potongan dari tarif per pertemuan jika presensi diisi setelah waktu pelaksanaan.
                        Mempengaruhi gaji guru.
                    </p>
                </div>
                <div class="mt-1">
                    <button
                        type="button"
                        id="toggle-late"
                        onclick="toggleFine('late')"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none"
                        style="background-color: {{ $fineSettings['late_penalty_enabled'] ? '#4f46e5' : '#e5e7eb' }};"
                    >
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition-transform duration-200"
                            style="transform: translateX({{ $fineSettings['late_penalty_enabled'] ? '20px' : '2px' }});"
                        ></span>
                    </button>
                </div>
            </div>

            {{-- Tipe nominal: Nominal / Persentase --}}
            <div class="mt-3 flex rounded-md shadow-sm">
                <button
                    type="button"
                    id="btn-late-type-fixed"
                    onclick="togglePenaltyType('late', 'fixed')"
                    class="flex-1 px-3 py-1.5 text-xs rounded-l-md border border-gray-300 transition-colors {{ ($fineSettings['late_penalty_type'] ?? 'percent') === 'fixed' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}"
                >Nominal (Rp)</button>
                <button
                    type="button"
                    id="btn-late-type-percent"
                    onclick="togglePenaltyType('late', 'percent')"
                    class="flex-1 px-3 py-1.5 text-xs rounded-r-md border border-l-0 border-gray-300 transition-colors {{ ($fineSettings['late_penalty_type'] ?? 'percent') === 'percent' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}"
                >Persentase (%)</button>
            </div>

            {{-- Nominal input --}}
            <div class="mt-4 flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nominal per pertemuan</label>
                    <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600">
                        <input
                            type="number"
                            name="late_penalty_value"
                            id="late-penalty-value"
                            min="0"
                            step="1"
                            value="{{ $fineSettings['late_penalty_value'] ?? 10 }}"
                            oninput="updateLatePreview()"
                            class="block flex-1 border-0 bg-transparent py-1.5 px-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                            style="min-width: 0;"
                        />
                    </div>
                </div>
                <p class="text-sm text-gray-500 pb-2 whitespace-nowrap">per pertemuan</p>
            </div>

            <p class="mt-2 text-xs text-gray-500">
                Preview: <span id="late-preview-text">
                    @if(($fineSettings['late_penalty_type'] ?? 'percent') === 'percent')
                        {{ $fineSettings['late_penalty_value'] ?? 10 }}% dari tarif per pertemuan
                    @else
                        Rp {{ number_format($fineSettings['late_penalty_value'] ?? 10) }} per pertemuan
                    @endif
                </span>
            </p>
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

    <script>
        function toggleFine(type) {
            var hiddenInput = document.querySelector('input[name="' + type + '_penalty_enabled"]');
            var button = document.getElementById('toggle-' + type);
            var isCurrentlyOn = hiddenInput.value === '1';
            var newValue = isCurrentlyOn ? '0' : '1';
            hiddenInput.value = newValue;
            button.style.backgroundColor = newValue === '1' ? '#4f46e5' : '#e5e7eb';
            button.querySelector('span').style.transform = newValue === '1' ? 'translateX(20px)' : 'translateX(2px)';
        }

        function formatRupiah(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }

        function togglePenaltyType(type, newType) {
            var hiddenInput = document.querySelector('input[name="' + type + '_penalty_type"]');
            hiddenInput.value = newType;

            document.getElementById('btn-' + type + '-type-fixed').className =
                'flex-1 px-3 py-1.5 text-xs rounded-l-md border border-gray-300 transition-colors ' +
                (newType === 'fixed'
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'bg-white text-gray-600 hover:bg-gray-50');
            document.getElementById('btn-' + type + '-type-percent').className =
                'flex-1 px-3 py-1.5 text-xs rounded-r-md border border-l-0 border-gray-300 transition-colors ' +
                (newType === 'percent'
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'bg-white text-gray-600 hover:bg-gray-50');

            if (type === 'late') updateLatePreview();
            if (type === 'attendance') updateAttendancePreview();
        }

        function updateLatePreview() {
            var value = document.getElementById('late-penalty-value').value;
            var type = document.querySelector('input[name="late_penalty_type"]').value;
            var text = type === 'percent'
                ? (value || '0') + '% dari tarif per pertemuan'
                : formatRupiah(value || '0') + ' per pertemuan';
            document.getElementById('late-preview-text').textContent = text;
        }

        function updateAttendancePreview() {
            var value = document.getElementById('attendance-penalty-value').value;
            var type = document.querySelector('input[name="attendance_penalty_type"]').value;
            var text = type === 'percent'
                ? (value || '0') + '% dari tarif per pertemuan'
                : formatRupiah(value || '0') + ' per pertemuan';
            document.getElementById('attendance-preview-text').textContent = text;
        }
    </script>
</section>
