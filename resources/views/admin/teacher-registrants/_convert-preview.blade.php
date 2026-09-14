<form id="tr-modal-form" @submit.prevent="$dispatch('tr-submit')">
    @csrf
    <input type="hidden" name="_action" value="{{ $registrant->id }}">
    @method('POST')

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Data pendaftar berikut akan ditambahkan ke sistem sebagai guru baru:
        </p>

        {{-- Personal info ────────────────────────────────────────── --}}
        <div class="bg-slate-50 rounded-xl p-4 space-y-2">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Data Diri</h4>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Nama</span>
                <span class="font-medium text-gray-900">{{ $registrant->name }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">No. WA</span>
                <span class="font-medium text-gray-900">{{ $registrant->whatsapp ?? '-' }}</span>
            </div>
            @if ($registrant->major)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Jurusan</span>
                    <span class="font-medium text-gray-900">{{ $registrant->major }}</span>
                </div>
            @endif
            @if ($registrant->subjects)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Mapel</span>
                    <span class="font-medium text-gray-900">{{ $registrant->subjects }}</span>
                </div>
            @endif
            @if ($registrant->address)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Alamat</span>
                    <span class="font-medium text-gray-900 max-w-[200px] text-right">{{ $registrant->address }}</span>
                </div>
            @endif
        </div>

        {{-- Bank info ─────────────────────────────────────────────── --}}
        @if ($registrant->bank_name)
            <div class="bg-slate-50 rounded-xl p-4 space-y-2">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Data Bank</h4>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Bank</span>
                    <span class="font-medium text-gray-900">{{ $registrant->bank_name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">No. Rekening</span>
                    <span class="font-medium text-gray-900">{{ $registrant->bank_account }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Pemilik</span>
                    <span class="font-medium text-gray-900">{{ $registrant->bank_owner }}</span>
                </div>
            </div>
        @endif

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
            <p class="text-sm text-indigo-700">
                Akun guru akan dibuat otomatis dengan password random.
            </p>
        </div>

        <p class="text-sm font-medium text-gray-700">
            Yakin ingin menambahkan data ini ke sistem?
        </p>
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
            class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menyimpan...' : 'Ya, Tambahkan'">Ya, Tambahkan</span>
        </button>
    </div>
</form>
