<form id="ns-modal-form" @submit.prevent="$dispatch('ns-submit')">
    @csrf
    <input type="hidden" name="_action" value="{{ $newStudent->id }}">
    @method('POST')

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Data pendaftar berikut akan ditambahkan ke sistem sebagai murid baru:
        </p>

        {{-- Parent info ─────────────────────────────────────────── --}}
        <div class="bg-slate-50 rounded-xl p-4 space-y-2">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Data Orang Tua / Wali</h4>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Nama</span>
                <span class="font-medium text-gray-900">{{ $newStudent->parent_name ?? $newStudent->name ?? '-' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">No. WA</span>
                <span class="font-medium text-gray-900">{{ $newStudent->whatsapp ?? '-' }}</span>
            </div>
            @if ($newStudent->address)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Alamat</span>
                    <span class="font-medium text-gray-900 max-w-[200px] text-right">{{ $newStudent->address }}</span>
                </div>
            @endif
        </div>

        {{-- Students info ─────────────────────────────────────── --}}
        <div class="bg-slate-50 rounded-xl p-4 space-y-2">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                Murid yang akan Ditambahkan ({{ count($students) }} anak)
            </h4>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @foreach ($students as $student)
                    <div class="flex items-start gap-2 p-2 bg-white rounded-lg border border-gray-100">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-medium shrink-0 mt-0.5">
                            {{ $loop->iteration }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $student['full_name'] ?? $student['nickname'] ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $student['nickname'] !== $student['full_name'] ? 'Panggilan: ' . ($student['nickname'] ?? '-') . ' · ' : '' }}{{ $student['kelas'] ?? '' }}{{ !empty($student['sekolah']) ? ' · ' . $student['sekolah'] : '' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($newStudent->notes)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <p class="text-xs text-amber-700">
                    <strong>Catatan:</strong> {{ $newStudent->notes }}
                </p>
            </div>
        @endif

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
            <p class="text-sm text-indigo-700">
                Akun orang tua akan dibuat otomatis. Password akan di-generate secara random.
            </p>
        </div>

        <p class="text-sm font-medium text-gray-700">
            Yakin ingin menambahkan data ini ke sistem?
        </p>
    </div>

    {{-- Actions ─────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button"
            @click="$dispatch('ns-close')"
            class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
            Batal
        </button>
        <button type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menyimpan...' : 'Ya, Tambahkan'">Ya, Tambahkan</span>
        </button>
    </div>
</form>
