<x-app-layout>
    <x-slot name="title">Murid (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Murid (Hibernasi)</h2>
            <a href="{{ route('admin.students.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="{
             ...window.forceDeleteActions({
                 resource: 'students',
                 label: 'murid',
                 itemName: 'Murid',
                 bulkForceUrl: '{{ route('admin.students.bulk-force-destroy') }}',
                 forceDestroyUrl: (id) => `/admin/students/${id}/force-destroy`,
                 listSelector: 'table',
             }),

             // ── Bulk Restore ─────────────────────────────────────────────────────
             bulkRestoreLoading: false,
             async bulkRestore() {
                 if (!confirm('Pulihkan {{ $students->count() }} murid?')) return;
                 this.bulkRestoreLoading = true;
                 try {
                     const resp = await window.Ajax.post('/admin/students/bulk-restore');
                     window.Toast?.success(resp.data?.message || 'Berhasil dipulihkan.');
                     window.location.reload();
                 } catch (e) {
                     window.Toast?.error('Gagal memulihkan murid.');
                 } finally {
                     this.bulkRestoreLoading = false;
                 }
             },

             // ── Per-row Restore ──────────────────────────────────────────────────
             restoreLoading: null,
             async restoreRow(studentId) {
                 this.restoreLoading = studentId;
                 try {
                     await window.Ajax.post(`/admin/students/${studentId}/restore`);
                     window.Toast?.success('Murid berhasil dipulihkan.');
                     window.location.reload();
                 } catch (e) {
                     window.Toast?.error('Gagal memulihkan murid.');
                 } finally {
                     this.restoreLoading = null;
                 }
             },
         }">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $students->count() }} murid</div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button"
                                @click="bulkRestore()"
                                :disabled="bulkRestoreLoading"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50 shadow-sm">
                            <svg x-show="bulkRestoreLoading" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg x-show="!bulkRestoreLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Pulihkan Semua
                        </button>
                        <button type="button"
                                @click="openBulkModal()"
                                x-show="selectedIds.length > 0"
                                x-cloak
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Permanen (<span x-text="selectedIds.length"></span>)
                        </button>
                    </div>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 w-8">
                                    <input type="checkbox"
                                           class="select-all-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @change="toggleAll($event)" />
                                </th>
                                <th class="py-2">Nama</th>
                                <th class="py-2">Jenjang</th>
                                <th class="py-2">Sekolah</th>
                                <th class="py-2">Orang Tua</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($students as $student)
                                <tr data-row-id="{{ $student->id }}">
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $student->id }}"
                                               data-name="{{ $student->display_name }}"
                                               data-cascade-count="{{ ($student->teachers_count ?? 0) + ($student->enrollments_count ?? 0) }}"
                                               @change="toggleOne($event)" />
                                    </td>
                                    <td class="py-2">
                                        <div class="font-medium">{{ $student->display_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $student->whatsapp ?? '-' }}</div>
                                    </td>
                                    <td class="py-2">{{ $student->education_level }}</td>
                                    <td class="py-2">{{ $student->school ?? '-' }}</td>
                                    <td class="py-2">
                                        @if ($student->parent?->user)
                                            <div class="flex items-center gap-1.5">
                                                @if ($student->parent->trashed())
                                                    <span class="inline-block w-2 h-2 rounded-full bg-gray-400" title="Orang tua dihibernasi"></span>
                                                @endif
                                                <span>{{ $student->parent->user->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        @if ($student->teachers->count() > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($student->teachers as $teacher)
                                                    <span class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                                        @if ($teacher->trashed())
                                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                                        @endif
                                                        {{ $teacher->displayName }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="restoreRow({{ $student->id }})"
                                                    :disabled="restoreLoading === {{ $student->id }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50">
                                                <svg x-show="restoreLoading === {{ $student->id }}" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                Pulihkan
                                            </button>
                                            <button type="button"
                                                    @click="openPerRowModal({{ $student->id }}, '{{ addslashes($student->display_name) }}', {{ ($student->teachers_count ?? 0) + ($student->enrollments_count ?? 0) }}, [])"
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
            id="fd-modal-students-single"
            title="Hapus Permanen Murid?"
            message="Data murid akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="#"
            :is-bulk="false"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
        <x-force-delete-modal
            id="fd-modal-students-bulk"
            title="Hapus Permanen Murid?"
            message="Murid yang dipilih akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="{{ route('admin.students.bulk-force-destroy') }}"
            :is-bulk="true"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
    </div>
</x-app-layout>
