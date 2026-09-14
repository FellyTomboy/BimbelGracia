<form id="discount-modal-form" @submit.prevent="$dispatch('discount-submit')">
    @csrf
    <input type="hidden" name="_action" value="store">

    <div class="space-y-4">
        {{-- Summary ─────────────────────────────────────────────── --}}
        <div class="bg-slate-50 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Periode</span>
                <span class="font-medium text-gray-900">{{ $monthName }} {{ $year }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Jenis Diskon</span>
                <span class="font-medium text-gray-900">
                    @if ($discount_type === 'percent')
                        Diskon %
                    @elseif ($discount_type === 'amount')
                        Nominal Potongan (Rp)
                    @else
                        Harga Final (Rp)
                    @endif
                </span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Nilai</span>
                <span class="font-medium text-gray-900">
                    @if ($discount_type === 'percent')
                        {{ $discount_value }}%
                    @else
                        Rp {{ number_format($discount_value, 0, ',', '.') }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Enrollment dipilih</span>
                <span class="font-medium text-gray-900">{{ count($enrollmentIds) }} enrollment</span>
            </div>
        </div>

        {{-- Enrollment list ─────────────────────────────────────── --}}
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Enrollment yang akan diterapkan:</p>
            <div class="space-y-1 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50">
                @foreach ($enrollments as $enrollment)
                    <div class="flex items-center gap-2 text-sm py-1">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-medium shrink-0">
                            {{ $loop->iteration }}
                        </span>
                        <span class="font-medium text-gray-800">#{{ $enrollment->id }}</span>
                        <span class="text-gray-500">— {{ $enrollment->program?->name ?? '-' }}</span>
                        <span class="text-gray-400">· {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($discount_value == 0)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <p class="text-sm text-amber-700">
                    <strong>Perhatian:</strong> Mengisi nilai <code class="bg-amber-100 px-1 rounded">0</code> berarti
                    <strong>menghapus semua diskon</strong> pada enrollment yang dipilih untuk periode ini.
                </p>
            </div>
        @endif

        <p class="text-sm text-gray-600">
            Yakin ingin menerapkan diskon di atas?
        </p>
    </div>

    {{-- Hidden inputs to re-submit ──────────────────────────────── --}}
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="discount_type" value="{{ $discount_type }}">
    <input type="hidden" name="discount_value" value="{{ $discount_value }}">
    @foreach ($enrollmentIds as $eid)
        <input type="hidden" name="enrollment_ids[]" value="{{ $eid }}">
    @endforeach

    {{-- Actions ──────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button"
            @click="$dispatch('discount-close')"
            class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
            Batal
        </button>
        <button type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menyimpan...' : 'Terapkan Diskon'">Terapkan Diskon</span>
        </button>
    </div>
</form>
