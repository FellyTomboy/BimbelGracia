<form id="tr-modal-form" @submit.prevent="$dispatch('tr-submit')">
    @csrf
    @method('DELETE')

    <div class="space-y-4">
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-6 text-center">
            <svg class="w-12 h-12 text-rose-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-lg font-semibold text-rose-900 mb-1">Hapus Semua Pendaftar Guru?</p>
            <p class="text-sm text-rose-700">
                <strong>{{ $count }}</strong> data pendaftar guru akan dihapus permanen.
            </p>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
            <p class="text-sm text-amber-700">
                <strong>Perhatian:</strong> Tindakan ini tidak bisa dibatalkan. Semua data pendaftar guru akan dihapus dari database.
            </p>
        </div>
    </div>

    {{-- Actions ─────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button"
            @click="$dispatch('tr-close')"
            class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
            Batal
        </button>
        <button type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menghapus...' : 'Ya, Hapus Semua'">Ya, Hapus Semua</span>
        </button>
    </div>
</form>
