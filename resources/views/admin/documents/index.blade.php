<x-app-layout>
    <x-slot name="title">Dokumen Buku Pelajaran</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dokumen Buku Pelajaran</h2>
                <p class="text-sm text-gray-500 mt-0.5">Upload dan atur akses dokumen untuk guru</p>
            </div>
            <button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Upload Dokumen
            </button>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="documentModal({
             createUrl: '{{ route('admin.documents.create-form') }}',
             storeUrl: '{{ route('admin.documents.store') }}',
             editUrl: (id) => `/admin/documents/${id}/form`,
             updateUrl: (id) => `/admin/documents/${id}`,
             deleteUrl: (id) => `/admin/documents/${id}`,
             listSelector: 'table',
         })"
         @open-create-modal.window="openCreate()">

        <!-- ── Modal Overlay ──────────────────────────────────────────────── -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500/60 z-50 flex items-center justify-center p-4"
             @click.self="close()"
             @keydown.escape.window="close()"
             style="display:none">

            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="modalTitle"></h3>
                    <button @click="close()" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Loading spinner -->
                    <div x-show="loading" class="flex justify-center py-8">
                        <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>

                    <!-- Form content -->
                    <div x-show="!loading && modalBody" x-html="modalBody"></div>

                    <!-- Default form actions (shown when form has no submit button) -->
                    <div x-show="!loading && modalBody && modalBody.indexOf('type=\&quot;submit\&quot;') === -1"
                         class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="close()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="button" @click="submit()"
                                :disabled="submitting"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                            <template x-if="submitting">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Upload'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Delete Confirmation ─────────────────────────────────────────── -->
        <div x-show="deleteConfirmId !== null"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/40 z-[60] flex items-center justify-center p-4"
             style="display:none">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hapus Dokumen?</h3>
                <p class="text-sm text-gray-500 mb-6">Dokumen akan dihapus permanen dan tidak dapat dipulihkan.</p>
                <div class="flex justify-end gap-3">
                    <button @click="cancelDelete()"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</button>
                    <button @click="deleteRow(deleteConfirmId)"
                            :disabled="deleteLoading"
                            class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                        <template x-if="deleteLoading">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Page Content ─────────────────────────────────────────────────── -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Judul</th>
                                <th class="py-3 px-4 font-medium">File</th>
                                <th class="py-3 px-4 font-medium">Ukuran</th>
                                <th class="py-3 px-4 font-medium">Akses</th>
                                <th class="py-3 px-4 font-medium">Proteksi</th>
                                <th class="py-3 px-4 font-medium">Password</th>
                                <th class="py-3 px-4 font-medium">Guru</th>
                                <th class="py-3 px-4 font-medium">Upload oleh</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="documents-table-body" class="divide-y divide-gray-50">
                            @forelse ($documents as $doc)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900">
                                        {{ $doc->title }}
                                        @if ($doc->description)
                                            <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($doc->description, 60) }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">{{ $doc->file_name }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $doc->formatted_size }}</td>
                                    <td class="py-3 px-4">
                                        @if ($doc->access_type === 'teacher')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Guru Tertentu</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Password</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if (($doc->protection_level ?? 'standard') === 'strict')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ketat</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">Longgar</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($doc->access_type === 'password')
                                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $doc->access_password_plain ?? '***' }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">
                                        @if ($doc->access_type === 'teacher' && $doc->teachers->isNotEmpty())
                                            {{ $doc->teachers->map->displayName->implode(', ') }}
                                        @elseif ($doc->access_type === 'teacher')
                                            <span class="text-gray-400">Belum dipilih</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">{{ $doc->uploader?->name ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.documents.download', $doc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">Download</a>
                                            <button type="button"
                                                    @click="openEdit({{ $doc->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</button>
                                            <button type="button"
                                                    @click="confirmDelete({{ $doc->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-8 text-center text-gray-400">Belum ada dokumen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($documents->hasPages())
                    <div class="p-4 border-t border-gray-100 paging-links">{{ $documents->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
