<x-app-layout>
    <x-slot name="title">Program</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Program']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Program</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola program bimbel</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.programs.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Program
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="crudModal({
             createUrl: '{{ route('admin.programs.create-form') }}',
             storeUrl: '{{ route('admin.programs.store') }}',
             editUrl: (id) => `/admin/programs/${id}/form`,
             updateUrl: (id) => `/admin/programs/${id}`,
             deleteUrl: (id) => `/admin/programs/${id}`,
             listSelector: 'table',
             modalName: 'program-crud',
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
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan'"></span>
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hibernasi Program?</h3>
                <p class="text-sm text-gray-500 mb-6">Data program akan dipindahkan ke daftar tidak aktif.</p>
                <div class="flex justify-end gap-3">
                    <button @click="cancelDelete()"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</button>
                    <button @click="deleteRow(deleteConfirmId)"
                            :disabled="deleteLoading"
                            class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                        <template x-if="deleteLoading">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        Hibernasi
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Page Content ─────────────────────────────────────────────────── -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <x-search-form placeholder="Cari program..." />
                    <div class="flex items-center gap-3">
                        <button id="bulk-delete-btn" onclick="submitBulkDelete()" class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                            Hibernasi Massal
                        </button>
                        <span class="text-sm text-gray-400">{{ $programs->total() }} program</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 w-10">
                                    <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <x-sortable-header label="Nama" column="programs.name" />
                                <th class="py-3 px-4 font-medium">Jenjang</th>
                                <x-sortable-header label="Biaya Ortu" column="default_parent_rate" />
                                <x-sortable-header label="Biaya Guru" column="default_teacher_rate" />
                                <x-sortable-header label="Status" column="programs.status" />
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50" id="programs-table-body">
                            @forelse ($programs as $program)
                                <tr class="hover:bg-gray-50/50 transition-colors" id="program-row-{{ $program->id }}">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" value="{{ $program->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkButton()" />
                                    </td>
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $program->name }}</td>
                                    <td class="py-3 px-4">
                                        @if ($program->division)
                                            @php
                                                $divColors = [
                                                    'TK' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                    'SD' => 'bg-sky-50 text-sky-700 border-sky-200',
                                                    'SMP' => 'bg-teal-50 text-teal-700 border-teal-200',
                                                    'SMA' => 'bg-violet-50 text-violet-700 border-violet-200',
                                                    'mengaji' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                    'mix' => 'bg-gray-100 text-gray-700 border-gray-300',
                                                ];
                                                $colorClass = $divColors[$program->division] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $colorClass }}">{{ strtoupper($program->division) }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">Rp {{ number_format($program->default_parent_rate) }}</td>
                                    <td class="py-3 px-4 text-gray-600">Rp {{ number_format($program->default_teacher_rate) }}</td>
                                    <td class="py-3 px-4">
                                        @if ($program->status === 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $program->status }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="openEdit({{ $program->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</button>
                                            <button type="button"
                                                    @click="confirmDelete({{ $program->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-empty-state icon="📚" title="Belum ada program" description="Tambahkan program baru." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($programs->hasPages())
                    <div class="p-4 border-t border-gray-100">{{ $programs->withQueryString()->links() }}</div>
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
            const btn = document.getElementById('bulk-delete-btn');
            if (checked.length > 0) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
        }
        async function submitBulkDelete() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            if (checked.length === 0) {
                alert('Pilih minimal 1 data untuk dihibernasi.');
                return;
            }
            if (!confirm('Hibernasi ' + checked.length + ' data yang dipilih?')) return;

            const params = new URLSearchParams();
            params.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            checked.forEach(cb => params.append('ids[]', cb.value));

            const btn = document.getElementById('bulk-delete-btn');
            btn.disabled = true;
            try {
                await window.Ajax.post('{{ route('admin.programs.bulk-destroy') }}', params);
                window.Toast?.success(checked.length + ' program berhasil dihibernasi.');
                const crudEl = document.querySelector('[x-data^="crudModal"]');
                if (crudEl && window.Alpine) {
                    await window.Alpine.$data(crudEl).refreshTable();
                } else {
                    window.location.reload();
                }
            } catch (e) {
                btn.disabled = false;
            }
        }

        // Scroll preservation
        (function () {
            var key = 'scroll_' + location.pathname + '?{{ http_build_query(request()->query()) }}';
            window.addEventListener('load', function () {
                var pos = sessionStorage.getItem(key);
                if (pos !== null) { window.scrollTo(0, parseInt(pos, 10)); sessionStorage.removeItem(key); }
            });
            document.querySelectorAll('form[method=POST], a[href*="delete"], a[href*="destroy"]').forEach(function (el) {
                el.addEventListener('click', function () { sessionStorage.setItem(key, window.scrollY); });
            });
            document.querySelectorAll('form[method=GET]').forEach(function (form) {
                form.addEventListener('submit', function () { sessionStorage.setItem(key, 0); });
            });
        })();
    </script>
</x-app-layout>
