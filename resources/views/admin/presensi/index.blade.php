<x-app-layout>
    <x-slot name="title">Validasi Presensi</x-slot>

    @if($billingMode === 'daily')
    {{-- ═══════════════════════════════════════════════════════════════
         DAILY MODE: AJAX modal flow
         ═══════════════════════════════════════════════════════════════ --}}

    <div x-data="presensiModal({
        createUrl: '{{ route('admin.presensi.create-form') }}',
        storeUrl: '{{ route('admin.presensi.store') }}',
        editUrl: (id) => `/admin/presensi/${id}/form`,
        updateUrl: (id) => `/admin/presensi/${id}`,
        deleteUrl: (id) => `/admin/presensi/${id}`,
        listSelector: 'table',
    })"
    @open-create-modal.window="openCreate()"
    @open-edit-modal.window="openEdit($event.detail)"
    @open-delete-modal.window="confirmDelete($event.detail)">

        {{-- Header ─────────────────────────────────────────────────────── --}}
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Validasi Presensi</h2>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Presensi
                </button>
            </div>
        </x-slot>

        {{-- Create/Edit Modal ─────────────────────────────────────────── --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(0,0,0,.25);backdrop-filter:blur(2px)"
             @keydown.escape.window="close()">

            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-indigo-600 rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base" x-text="modalTitle">Tambah Presensi</h3>
                    <button @click="close()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5 max-h-[70vh] overflow-y-auto">
                    <div x-html="modalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Delete Confirmation ─────────────────────────────────────────── --}}
        <div x-show="deleteConfirmId !== null"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(0,0,0,.25);backdrop-filter:blur(2px)"
             @keydown.escape.window="deleteConfirmId = null">

            <div x-show="deleteConfirmId !== null"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-rose-600 rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base">Hapus Presensi?</h3>
                    <button @click="deleteConfirmId = null" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5">
                    <p class="text-sm text-gray-600 mb-4">Tindakan ini tidak bisa dibatalkan.</p>
                    <div class="flex justify-end gap-3">
                        <button @click="deleteConfirmId = null"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button @click="deleteRow()"
                                :disabled="deleteLoading"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors disabled:opacity-50">
                            <span x-text="deleteLoading ? 'Menghapus...' : 'Ya, Hapus'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Page content ─────────────────────────────────────────────── --}}
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                @include('admin.presensi._table')
            </div>
        </div>

    </div>

    @else
    {{-- ═══════════════════════════════════════════════════════════════
         MONTHLY MODE: standalone flow (unchanged)
         ═══════════════════════════════════════════════════════════════ --}}

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Validasi Presensi</h2>
            <a href="{{ route('admin.presensi.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Presensi
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('admin.presensi._table')
        </div>
    </div>

    @endif

    {{-- Delete Confirmation Modal (vanilla JS — monthly mode only) ─── --}}
    @if($billingMode !== 'daily')
    <div id="delete-attendance-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4"
         style="background:rgba(0,0,0,.25);backdrop-filter:blur(2px)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-4 bg-rose-600 flex items-center justify-between rounded-t-2xl">
                <h3 class="text-white font-semibold text-base">Hapus Presensi?</h3>
                <button onclick="closeDeleteAttendanceModal()" class="text-white/70 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <p class="text-sm text-gray-600">Yakin ingin menghapus presensi berikut?</p>
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-rose-600">Tanggal</span>
                        <span class="font-medium text-rose-900" id="dam-date"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-rose-600">Program</span>
                        <span class="font-medium text-rose-900" id="dam-program"></span>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <p class="text-sm text-amber-700">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button onclick="closeDeleteAttendanceModal()"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button onclick="submitDeleteAttendance()"
                        id="dam-submit-btn"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let __damId = null;

        function openDeleteAttendanceModal(id, program, date) {
            __damId = id;
            document.getElementById('dam-date').textContent = date;
            document.getElementById('dam-program').textContent = program;
            const modal = document.getElementById('delete-attendance-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteAttendanceModal() {
            const modal = document.getElementById('delete-attendance-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            __damId = null;
        }

        async function submitDeleteAttendance() {
            if (!__damId) return;
            const btn = document.getElementById('dam-submit-btn');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';
            try {
                await window.Ajax.delete('/admin/presensi/' + __damId);
                window.Toast?.success('Presensi dihapus.');
                closeDeleteAttendanceModal();
                setTimeout(() => location.reload(), 500);
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
            }
        }
    </script>
    @endif

    {{-- handleQuickValidate: available in both modes (used by inline select in table) --}}
    <script>
        function handleQuickValidate(selectEl, attendanceId) {
            const status = selectEl.value;
            if (!status) return;
            const row = selectEl.closest('tr');
            const badgeEl = row.querySelector('[data-row-status]');
            const labels = { terima: 'Diterima', terlambat: 'Terlambat', ditolak: 'Ditolak' };
            const colors = { terima: 'emerald', terlambat: 'amber', ditolak: 'rose' };
            window.Ajax.post('/admin/presensi/' + attendanceId + '/validate', { status })
                .then(resp => {
                    window.Toast?.success(resp.data?.message ?? 'Presensi divalidasi.');
                    if (badgeEl) {
                        badgeEl.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' + colors[status] + '-50 text-' + colors[status] + '-700 border border-' + colors[status] + '-200';
                        badgeEl.textContent = labels[status];
                    }
                })
                .catch(() => { selectEl.value = ''; });
        }
    </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('attendance-tbody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr[data-lesson-date]'));
            const headers = document.querySelectorAll('th.sortable');

            let currentSort = {
                field: '{{ request('sort', 'lesson_date') }}',
                dir: '{{ request('dir', 'desc') }}'
            };

            function getSortValue(row, field) {
                switch (field) {
                    case 'lesson_date': return row.dataset.lessonDate;
                    case 'created_at': return parseInt(row.dataset.createdAt);
                    case 'program': return row.dataset.program;
                    case 'guru': return row.dataset.guru;
                    case 'murid': return row.dataset.murid;
                    default: return '';
                }
            }

            function sortRows(field) {
                if (currentSort.field === field) {
                    currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.field = field;
                    currentSort.dir = 'asc';
                }
                rows.sort(function(a, b) {
                    const valA = getSortValue(a, currentSort.field);
                    const valB = getSortValue(b, currentSort.field);
                    const isNum = currentSort.field === 'created_at';
                    if (isNum) {
                        return currentSort.dir === 'asc' ? valA - valB : valB - valA;
                    }
                    return currentSort.dir === 'asc'
                        ? valA.localeCompare(valB)
                        : valB.localeCompare(valA);
                });
                rows.forEach(function(row) { tbody.appendChild(row); });
                updateHeaderIcons();
            }

            function updateHeaderIcons() {
                headers.forEach(function(th) {
                    const field = th.dataset.sort;
                    const span = th.querySelector('span');
                    const existingArrow = span.querySelector('.sort-arrow');
                    if (existingArrow) existingArrow.remove();
                    if (field === currentSort.field) {
                        const arrow = document.createElement('span');
                        arrow.className = 'sort-arrow font-bold';
                        arrow.textContent = currentSort.dir === 'asc' ? ' ↑' : ' ↓';
                        span.appendChild(arrow);
                    }
                });
            }

            headers.forEach(function(th) {
                th.style.cursor = 'pointer';
                th.addEventListener('click', function() {
                    sortRows(th.dataset.sort);
                });
            });

            updateHeaderIcons();
        });
    </script>
</x-app-layout>
