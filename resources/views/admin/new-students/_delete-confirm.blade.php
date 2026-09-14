<form id="ns-modal-form" @submit.prevent="$dispatch('ns-submit')">
    @csrf
    <input type="hidden" name="_action" value="{{ $newStudent->id }}">
    @method('DELETE')

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Yakin ingin menghapus data pendaftar berikut?
        </p>

        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-rose-600">Nama Orang Tua</span>
                <span class="font-medium text-rose-900">{{ $newStudent->parent_name ?? $newStudent->name ?? '-' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-rose-600">No. WA</span>
                <span class="font-medium text-rose-900">{{ $newStudent->whatsapp ?? '-' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-rose-600">Tanggal Daftar</span>
                <span class="font-medium text-rose-900">{{ $newStudent->created_at->format('d M Y H:i') }}</span>
            </div>
            @if ($newStudent->converted)
                <div class="mt-2 pt-2 border-t border-rose-200">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        Sudah dikonversi
                    </span>
                </div>
            @endif
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
            <p class="text-sm text-amber-700">
                <strong>Perhatian:</strong> Data pendaftaran akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
            </p>
        </div>
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
            class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menghapus...' : 'Ya, Hapus'">Ya, Hapus</span>
        </button>
    </div>
</form>
