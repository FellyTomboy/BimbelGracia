<form id="css-modal-form" x-on:submit.prevent="$dispatch('css-submit')">
    @csrf
    @method('DELETE')

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Yakin ingin menghapus sesi kelas berikut?
        </p>

        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-rose-600">Tanggal</span>
                <span class="font-medium text-rose-900">{{ $session->session_date?->format('d/m/Y') ?? '-' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-rose-600">Program</span>
                <span class="font-medium text-rose-900">{{ $session->program?->name ?? '-' }}</span>
            </div>
            @if ($session->teachers->isNotEmpty())
            <div class="flex justify-between text-sm">
                <span class="text-rose-600">Guru</span>
                <span class="font-medium text-rose-900">{{ $session->teachers->map->displayName->join(', ') }}</span>
            </div>
            @endif
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
            <p class="text-sm text-amber-700">
                <strong>Perhatian:</strong> Semua presensi terkait sesi ini akan ikut dihapus. Tindakan ini tidak bisa dibatalkan.
            </p>
        </div>
    </div>

    {{-- Actions ─────────────────────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button"
            @click="$dispatch('css-close')"
            class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
            Batal
        </button>
        <button type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menghapus...' : 'Ya, Hapus'">Ya, Hapus</span>
        </button>
    </div>
</form>
