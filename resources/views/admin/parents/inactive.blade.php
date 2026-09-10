<x-app-layout>
    <x-slot name="title">Parent Tidak Aktif</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Parent', 'url' => route('admin.parents.index')], ['label' => 'Tidak Aktif']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Parent Tidak Aktif</h2>
                <p class="text-sm text-gray-500 mt-0.5">Data parent yang sudah dihibernasi</p>
            </div>
            <a href="{{ route('admin.parents.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 hover:bg-slate-200 transition-all">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
             restoreLoading: null,
             bulkRestoreLoading: false,

             async restoreRow(parentId) {
                 if (!confirm('Pulihkan parent ini?')) return;
                 this.restoreLoading = parentId;
                 try {
                     await window.Ajax.post(`/admin/parents/${parentId}/restore`);
                     window.Toast?.success('Parent berhasil dipulihkan.');
                     window.location.reload();
                 } catch (e) {
                     window.Toast?.error('Gagal memulihkan parent.');
                 } finally {
                     this.restoreLoading = null;
                 }
             },

             async bulkRestore() {
                 const checked = document.querySelectorAll('.row-checkbox:checked');
                 if (checked.length === 0) { alert('Pilih minimal 1 data untuk dipulihkan.'); return; }
                 if (!confirm('Pulihkan ' + checked.length + ' parent yang dipilih?')) return;
                 this.bulkRestoreLoading = true;
                 const params = new URLSearchParams();
                 params.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                 checked.forEach(cb => params.append('ids[]', cb.value));
                 try {
                     await window.Ajax.post('{{ route('admin.parents.bulk-restore') }}', params);
                     window.Toast?.success(checked.length + ' parent berhasil dipulihkan.');
                     window.location.reload();
                 } catch (e) {
                     window.Toast?.error('Gagal memulihkan parent.');
                     this.bulkRestoreLoading = false;
                 }
             }
         }">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $parents->total() }} parent</div>
                    <button type="button"
                            @click="bulkRestore()"
                            :disabled="bulkRestoreLoading"
                            class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50"
                            id="bulk-restore-btn">
                        <svg x-show="bulkRestoreLoading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Pulihkan Massal
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 w-10">
                                    <input type="checkbox" onclick="toggleAllRestore(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="py-3 px-4 font-medium">Nama</th>
                                <th class="py-3 px-4 font-medium">No. Telepon</th>
                                <th class="py-3 px-4 font-medium">Dihibernasi</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($parents as $parent)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" value="{{ $parent->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkRestoreBtn()" />
                                    </td>
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $parent->name ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $parent->user?->phone ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $parent->deleted_at?->diffForHumans() ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <button type="button"
                                                @click="restoreRow({{ $parent->id }})"
                                                :disabled="restoreLoading === {{ $parent->id }}"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50">
                                            <svg x-show="restoreLoading === {{ $parent->id }}" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            Pulihkan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state icon="👤" title="Tidak ada parent tidak aktif" description="Parent yang dihibernasi akan muncul di sini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($parents->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $parents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleAllRestore(source) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
            updateBulkRestoreBtn();
        }
        function updateBulkRestoreBtn() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const btn = document.getElementById('bulk-restore-btn');
            if (checked.length > 0) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
        }
    </script>
</x-app-layout>
