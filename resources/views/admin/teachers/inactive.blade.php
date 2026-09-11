<x-app-layout>
    <x-slot name="title">Guru (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru (Hibernasi)</h2>
            <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="{
             fd: forceDeleteActions({
                 resource: 'teachers',
                 label: 'guru',
                 itemName: 'Guru',
                 modalPrefix: 'fd-modal-teachers',
                 bulkForceUrl: '{{ route('admin.teachers.bulk-force-destroy') }}',
                 forceDestroyUrl: (id) => '/admin/teachers/' + id + '/force-destroy',
                 listSelector: 'table',
             }),
             restoreLoading: null,
             async restoreRow(teacherId) {
                 if (!confirm('Pulihkan guru ini?')) return;
                 this.restoreLoading = teacherId;
                 try {
                     await window.Ajax.post('/admin/teachers/' + teacherId + '/restore');
                     window.Toast?.success('Guru berhasil dipulihkan.');
                     window.location.reload();
                 } catch (e) {
                     window.Toast?.error('Gagal memulihkan guru.');
                 } finally {
                     this.restoreLoading = null;
                 }
             }
         }">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $teachers->count() }} guru</div>
                    <button type="button"
                            @click="fd.openBulkModal()"
                            x-show="fd.selectedIds.length > 0"
                            x-cloak
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Permanen (<span x-text="fd.selectedIds.length"></span>)
                    </button>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 w-8">
                                    <input type="checkbox"
                                           class="select-all-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @change="fd.toggleAll($event)" />
                                </th>
                                <th class="py-2">Nama</th>
                                <th class="py-2">Nama Panggilan</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">WhatsApp</th>
                                <th class="py-2">Biaya Kelas</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($teachers as $teacher)
                                <tr data-row-id="{{ $teacher->id }}">
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $teacher->id }}"
                                               data-name="{{ $teacher->displayName }}"
                                               data-cascade-count="{{ ($teacher->enrollments_count ?? 0) + ($teacher->students_count ?? 0) }}"
                                               @change="fd.toggleOne($event)" />
                                    </td>
                                    <td class="py-2">{{ $teacher->displayName }}</td>
                                    <td class="py-2">{{ $teacher->nickname ?: '—' }}</td>
                                    <td class="py-2">{{ $teacher->user?->email ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->whatsapp_number ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($teacher->class_rate ?? 0) }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="restoreRow({{ $teacher->id }})"
                                                    :disabled="restoreLoading === {{ $teacher->id }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50">
                                                <svg x-show="restoreLoading === {{ $teacher->id }}" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                Pulihkan
                                            </button>
                                            <button type="button"
                                                    @click="fd.openPerRowModal({{ $teacher->id }}, '{{ addslashes($teacher->displayName) }}', {{ ($teacher->enrollments_count ?? 0) + ($teacher->students_count ?? 0) }}, [])"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                Hapus Permanen
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Force Delete Modal --}}
        <x-force-delete-modal
            id="fd-modal-teachers-single"
            title="Hapus Permanen Guru?"
            message="Data guru akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="#"
            :is-bulk="false"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
        <x-force-delete-modal
            id="fd-modal-teachers-bulk"
            title="Hapus Permanen Guru?"
            message="Guru yang dipilih akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="{{ route('admin.teachers.bulk-force-destroy') }}"
            :is-bulk="true"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />

    </div>
</x-app-layout>
