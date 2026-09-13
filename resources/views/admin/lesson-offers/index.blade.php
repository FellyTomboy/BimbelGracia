<x-app-layout>
    <x-slot name="title">Tawaran Les</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Tawaran Les']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tawaran Les</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola tawaran les untuk guru</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.lesson-offers.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Tawaran
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="lessonOfferModal({
             crud: {
                 createUrl: '{{ route('admin.lesson-offers.create-form') }}',
                 storeUrl: '{{ route('admin.lesson-offers.store') }}',
                 editUrl: (id) => `/admin/lesson-offers/${id}/form`,
                 updateUrl: (id) => `/admin/lesson-offers/${id}`,
                 deleteUrl: (id) => `/admin/lesson-offers/${id}`,
                 listSelector: 'table',
             },
             bulk: {
                 bulkHibernateUrl: '{{ route('admin.lesson-offers.bulk-destroy') }}',
                 resource: 'lesson-offers',
                 label: 'tawaran les',
             },
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hibernasi Tawaran Les?</h3>
                <p class="text-sm text-gray-500 mb-6">Data tawaran les akan dipindahkan ke daftar tidak aktif.</p>
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
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <x-search-form placeholder="Cari kode, mapel, tingkat..." />
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-400">{{ $offers->total() }} tawaran</div>
                        <button type="button"
                                @click="submitBulkHibernate()"
                                x-show="selectedIds.length > 0"
                                x-cloak
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-amber-500 hover:bg-amber-600 transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.316 4.954A9.993 9.993 0 0 0 12 3a9.993 9.993 0 0 0-8.316 4.954C1.992 10.169 1 12.565 1 15.125c0 3.375 2.25 6.375 5.5 8.75 3.25 2.375 6.5 2.5 6.5 2.5s3.25-.125 6.5-2.5c3.25-2.375 5.5-5.375 5.5-8.75 0-2.56-.992-4.956-2.684-7.171z"/></svg>
                            Hapus Terpilih (<span x-text="selectedIds.length"></span>)
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 w-8 font-medium">
                                    <input type="checkbox"
                                           class="select-all-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @change="toggleAll($event)" />
                                </th>
                                <th class="py-3 px-4 font-medium">Kode</th>
                                <th class="py-3 px-4 font-medium">Mapel</th>
                                <th class="py-3 px-4 font-medium">Tingkat</th>
                                <th class="py-3 px-4 font-medium">Jadwal</th>
                                <th class="py-3 px-4 font-medium">Catatan</th>
                                <th class="py-3 px-4 font-medium">Kontak WA</th>
                                <th class="py-3 px-4 font-medium">Status</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($offers as $offer)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $offer->id }}"
                                               @change="toggleOne($event)" />
                                    </td>
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $offer->code }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $offer->subject }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $offer->education_level }}</td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($offer->schedules)
                                            @foreach ($offer->schedules as $sch)
                                                <div class="text-xs">{{ ($sch['day'] ?? '') . ' ' . ($sch['time'] ?? '') }}</div>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 max-w-xs truncate">{{ $offer->note ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $offer->contact_whatsapp ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($offer->status === 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $offer->status }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="openEdit({{ $offer->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</button>
                                            <button type="button"
                                                    @click="confirmDelete({{ $offer->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9"><x-empty-state icon="🎯" title="Belum ada tawaran les" description="Buat tawaran les baru." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($offers->hasPages())
                    <div class="p-4 border-t border-gray-100 paging-links">{{ $offers->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>

    <script>
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
