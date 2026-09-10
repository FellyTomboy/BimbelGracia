<x-app-layout>
    <x-slot name="title">Murid (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Murid (Hibernasi)</h2>
            <a href="{{ route('admin.students.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Nama</th>
                                <th class="py-2">Nama Panggilan</th>
                                <th class="py-2">Parent Awal</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi Restore</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($students as $student)
                                <tr x-data="{
                                    submitting: false,
                                    errorMsg: '',
                                    selectedParentId: '{{ (string) ($student->parent_id ?? '') }}',
                                    newParentName: '',
                                    newParentPhone: '',
                                    originalParentId: {{ $student->parent_id !== null ? (int) $student->parent_id : 'null' }},
                                    parentChanged() {
                                        if (this.selectedParentId !== '' && parseInt(this.selectedParentId) !== this.originalParentId) return true;
                                        if (this.newParentName.trim() !== '') return true;
                                        if (this.newParentPhone.trim() !== '') return true;
                                        return false;
                                    },
                                    async submitRestore() {
                                        this.errorMsg = '';
                                        if (!confirm(this.parentChanged()
                                            ? 'Murid ini akan dipindahkan ke parent yang berbeda dari parent awal. Lanjutkan?'
                                            : 'Pulihkan murid ini?')) return;
                                        this.submitting = true;
                                        const params = new URLSearchParams();
                                        params.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                                        if (this.selectedParentId) params.append('parent_id', this.selectedParentId);
                                        if (this.newParentName.trim()) params.append('new_parent_name', this.newParentName);
                                        if (this.newParentPhone.trim()) params.append('new_parent_phone', this.newParentPhone);
                                        try {
                                            const resp = await window.Ajax.post('{{ route('admin.students.restore', $student->id) }}', params);
                                            window.Toast?.success(resp.data?.message || 'Murid berhasil dipulihkan.');
                                            this.$root.remove();
                                        } catch (e) {
                                            if (e.response?.status === 422) {
                                                const errs = e.response.data?.errors || {};
                                                this.errorMsg = Object.values(errs).flat().join(', ');
                                            } else {
                                                this.errorMsg = 'Gagal memulihkan murid.';
                                            }
                                        } finally {
                                            this.submitting = false;
                                        }
                                    }
                                }">
                                    <td class="py-2">{{ $student->display_name }}</td>
                                    <td class="py-2">{{ $student->nickname ?: '—' }}</td>
                                    <td class="py-2">{{ $student->parent?->name ?? ($student->parent_id ? 'Parent dihapus' : 'Tidak ada parent') }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <form @submit.prevent="submitRestore()" class="space-y-2">
                                            <div x-show="parentChanged()" x-cloak class="bg-amber-50 border border-amber-300 text-amber-700 text-xs px-3 py-2 rounded-lg flex items-start gap-2">
                                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <span>Murid akan dipindahkan ke parent yang berbeda dari parent awal.</span>
                                            </div>
                                            <p x-show="errorMsg" x-cloak x-text="errorMsg" class="bg-rose-50 text-rose-700 text-xs px-3 py-2 rounded-lg"></p>
                                            <div>
                                                <label class="block text-xs text-gray-500">Kembalikan ke Parent:</label>
                                                <select x-model="selectedParentId" class="w-full border-gray-300 rounded-md text-xs">
                                                    <option value="">-- Pilih parent (atau buat baru) --</option>
                                                    @foreach ($parents as $parent)
                                                        <option value="{{ $parent->id }}">{{ $parent->name }} ({{ $parent->user?->phone ?? '-' }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="text-xs text-gray-400">Atau buat parent baru:</div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" x-model="newParentName" class="w-full border-gray-300 rounded-md text-xs" placeholder="Nama parent baru" />
                                                <input type="text" x-model="newParentPhone" class="w-full border-gray-300 rounded-md text-xs" placeholder="No HP parent baru" />
                                            </div>
                                            <button type="submit" :disabled="submitting" class="px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50 flex items-center gap-1">
                                                <svg x-show="submitting" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                Restore
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-400">Tidak ada murid yang dihibernasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
