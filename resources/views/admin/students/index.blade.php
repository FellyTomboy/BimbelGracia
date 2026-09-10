<x-app-layout>
    <x-slot name="title">Murid</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Murid']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Murid</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data murid bimbel</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.students.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="crudModal({
             deleteUrl: (id) => `/admin/students/${id}`,
             deleteMethod: 'delete',
             listSelector: 'table',
         })">

        {{-- ── Delete Confirmation ───────────────────────────────────── --}}
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hibernasi Murid?</h3>
                <p class="text-sm text-gray-500 mb-6">Murid akan dipindahkan ke Data Tidak Aktif.</p>
                <div class="flex justify-end gap-3">
                    <button @click="cancelDelete()"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</button>
                    <button @click="deleteRow(deleteConfirmId)"
                            :disabled="deleteLoading"
                            class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                        <template x-if="deleteLoading">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 12 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        Hibernasi
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Toolbar --}}
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <x-search-form placeholder="Cari nama, email, WA..." />
                    <div class="flex items-center gap-3">
                        <button id="bulk-hibernate-btn"
                                onclick="submitBulkHibernate()"
                                class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                            Hibernasi Massal
                        </button>
                        <span class="text-sm text-gray-400">
                            {{ $students->total() }} murid
                        </span>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 w-10">
                                    <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <x-sortable-header label="Nama" column="students.full_name" />
                                <th class="py-3 px-4 font-medium">Nama Panggilan</th>
                                <th class="py-3 px-4 font-medium">Sekolah</th>
                                <th class="py-3 px-4 font-medium">Kelas</th>
                                <th class="py-3 px-4 font-medium">Status</th>
                                <th class="py-3 px-4 font-medium">No. Telepon</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($students as $student)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" value="{{ $student->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkButton()" />
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-gray-900">{{ $student->display_name }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">{{ $student->nickname ?: '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $student->sekolah ?: '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $student->kelas ?: '—' }}</td>
                                    <td class="py-3 px-4">
                                        <x-student-status-badge :status="$student->status" />
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">{{ $student->parent?->user?->phone ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="confirmDelete({{ $student->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <x-empty-state icon="👨‍🎓" title="Belum ada murid" description="Tambahkan murid melalui halaman Parent." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($students->hasPages())
                    <div class="p-4 border-t border-gray-100 paging-links">
                        {{ $students->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
            updateBulkButton();
        }

        function updateBulkButton() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const btn = document.getElementById('bulk-hibernate-btn');
            if (checked.length > 0) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
        }

        async function submitBulkHibernate() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            if (checked.length === 0) { alert('Pilih minimal 1 data untuk dihibernasi.'); return; }
            if (!confirm('Hibernasi ' + checked.length + ' murid yang dipilih?')) return;
            const params = new URLSearchParams();
            params.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            checked.forEach(cb => params.append('ids[]', cb.value));
            const btn = document.getElementById('bulk-hibernate-btn');
            btn.disabled = true;
            try {
                await window.Ajax.post('{{ route('admin.students.bulk-destroy') }}', params);
                window.Toast?.success(checked.length + ' murid berhasil dihibernasi.');
                const crudEl = document.querySelector('[x-data^="crudModal"]');
                if (crudEl && window.Alpine) {
                    await window.Alpine.$data(crudEl).refreshTable();
                } else {
                    window.location.reload();
                }
            } catch (e) { btn.disabled = false; }
        }

        // Scroll preservation
        (function () {
            var key = 'scroll_' + location.pathname + '?{{ http_build_query(request()->query()) }}';
            window.addEventListener('load', function () {
                var pos = sessionStorage.getItem(key);
                if (pos !== null) { window.scrollTo(0, parseInt(pos, 10)); sessionStorage.removeItem(key); }
            });
            document.querySelectorAll('form[method=GET]').forEach(function (form) {
                form.addEventListener('submit', function () { sessionStorage.setItem(key, 0); });
            });
        })();
    </script>
</x-app-layout>
