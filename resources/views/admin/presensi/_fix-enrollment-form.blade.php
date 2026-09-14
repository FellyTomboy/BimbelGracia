<form id="att-detail-form" x-on:submit.prevent="$dispatch('att-detail-submit')">
    @csrf

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Mengubah enrollment akan mengganti data murid dan mengembalikan status ke <strong>pending</strong>.
        </p>

        <div>
            <label for="enrollment-select" class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
            <select name="enrollment_id" id="enrollment-select" class="w-full border-gray-200 rounded-xl text-sm" required>
                <option value="">Pilih enrollment</option>
                @foreach ($enrollments as $enrollment)
                    <option value="{{ $enrollment->id }}">
                        #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->teacher?->displayName ?? '-' }}
                    </option>
                @endforeach
            </select>
            @if ($isClassPlaceholder)
                <p class="mt-1.5 text-xs text-amber-600">Presensi ini terkait murid kelas bersama, jadi hanya program bertipe kelas yang bisa dipilih.</p>
            @endif
        </div>
    </div>

    {{-- Actions ─────────────────────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button"
            @click="$dispatch('att-detail-close')"
            class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
            Batal
        </button>
        <button type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menyimpan...' : 'Simpan Enrollment'">Simpan Enrollment</span>
        </button>
    </div>
</form>
