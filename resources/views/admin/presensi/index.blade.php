<x-app-layout>
    <x-slot name="title">Validasi Presensi</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Validasi Presensi</h2>
            <a href="{{ route('admin.presensi.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm"
               @click.prevent="openCreateModal()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Presensi
            </a>
        </div>
    </x-slot>

    <div class="py-8"
        x-data="presensiCreateModal({})"
        x-init="initFromData()"
        data-late-penalty="{{ $latePenaltyEnabled ? 'true' : 'false' }}"
        data-billing-mode="{{ $billingMode }}">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            {{-- Filter & Search Bar --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <form method="GET" action="{{ route('admin.presensi.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
                        <input type="text" name="search" value="{{ old('search', request('search')) }}"
                            placeholder="Guru, program, atau murid..."
                            class="w-52 rounded-xl border-gray-200 text-sm"
                            {{ request('search') ? 'autofocus' : '' }} />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Semua</option>
                            <option value="terima" @selected(request('status') === 'terima')>Diterima</option>
                            <option value="terlambat" @selected(request('status') === 'terlambat')>Terlambat</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="ditolak" @selected(request('status') === 'ditolak')>Ditolak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Urutkan</label>
                        <select name="sort" class="rounded-xl border-gray-200 text-sm">
                            <option value="lesson_date" @selected(request('sort', 'lesson_date') === 'lesson_date')>Tanggal</option>
                            <option value="created_at" @selected(request('sort') === 'created_at')>Tanggal Input</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Arah</label>
                        <select name="dir" class="rounded-xl border-gray-200 text-sm">
                            <option value="desc" @selected(request('dir', 'desc') === 'desc')>Terbaru</option>
                            <option value="asc" @selected(request('dir') === 'asc')>Terlama</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                        Cari
                    </button>
                    @if (request()->anyFilled(['search', 'status', 'sort', 'dir']))
                        <a href="{{ route('admin.presensi.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-colors">Reset</a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @if (request('search'))
                    <div class="px-4 py-2 bg-indigo-50 border-b border-indigo-100 text-xs text-indigo-700">
                        Menampilkan {{ $attendances->count() }} dari {{ $attendances->total() }} hasil untuk "<strong>{{ request('search') }}</strong>"
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" id="attendance-table">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50 text-xs uppercase tracking-wide">
                                <th class="py-3 px-4 font-medium sortable" data-sort="lesson_date">
                                    <span class="flex items-center gap-1">
                                        Tanggal
                                        @if (request('sort', 'lesson_date') === 'lesson_date')
                                            @if (request('dir', 'desc') === 'desc') ↓ @else ↑ @endif
                                        @endif
                                    </span>
                                </th>
                                <th class="py-3 px-4 font-medium sortable" data-sort="program">
                                    <span class="flex items-center gap-1">
                                        Program
                                        @if (request('sort') === 'program') @if (request('dir') === 'asc') ↑ @else ↓ @endif @endif
                                    </span>
                                </th>
                                <th class="py-3 px-4 font-medium sortable" data-sort="guru">
                                    <span class="flex items-center gap-1">
                                        Guru
                                        @if (request('sort') === 'guru') @if (request('dir') === 'asc') ↑ @else ↓ @endif @endif
                                    </span>
                                </th>
                                <th class="py-3 px-4 font-medium sortable" data-sort="murid">
                                    <span class="flex items-center gap-1">
                                        Murid
                                        @if (request('sort') === 'murid') @if (request('dir') === 'asc') ↑ @else ↓ @endif @endif
                                    </span>
                                </th>
                                <th class="py-3 px-4 font-medium">Enrollment</th>
                                <th class="py-3 px-4 font-medium">Status</th>
                                <th class="py-3 px-4 font-medium">Notifikasi</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50" id="attendance-tbody">
                            @forelse ($attendances as $attendance)
                                <tr class="hover:bg-gray-50/50 transition-colors" data-lesson-date="{{ $attendance->lesson_date?->format('Y-m-d') ?? '' }}" data-created-at="{{ $attendance->created_at?->timestamp ?? 0 }}" data-program="{{ strtolower($attendance->enrollment?->program?->name ?? '') }}" data-guru="{{ strtolower($attendance->enrollment?->teacher?->displayName ?? '') }}" data-murid="{{ strtolower($attendance->students->map(fn($s) => $s->display_name)->join(' ')) }}">
                                    <td class="py-3 px-4 text-gray-900">{{ $attendance->lesson_date?->format('d M Y') ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->displayName ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->students->count() > 0)
                                            @foreach ($attendance->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">#{{ $attendance->enrollment_id }}</td>
                                    <td class="py-3 px-4">
                                        <span data-row-status
                                            @if ($attendance->status_validation === 'terima')
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                                            @elseif ($attendance->status_validation === 'terlambat')
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200"
                                            @elseif ($attendance->status_validation === 'ditolak')
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200"
                                            @else
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200"
                                            @endif>
                                            @if ($attendance->status_validation === 'terima')Diterima
                                            @elseif ($attendance->status_validation === 'terlambat')Terlambat
                                            @elseif ($attendance->status_validation === 'ditolak')Ditolak
                                            @else Pending
                                            @endif
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->parent_review_status === 'pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Menunggu</span>
                                        @elseif ($attendance->parent_review_status === 'rejected')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ditolak</span>
                                        @elseif ($attendance->parent_review_status === 'dismissed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">Batal</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1">
                                            <select name="status"
                                                class="text-xs border-gray-200 rounded-lg py-1 px-2 bg-white"
                                                onchange="handleQuickValidate(this, {{ $attendance->id }})">
                                                <option value="">—</option>
                                                <option value="terima">Terima</option>
                                                <option value="terlambat">Terlambat</option>
                                                <option value="ditolak">Tolak</option>
                                            </select>
                                            @if ($attendance->status_validation !== 'ditolak')
                                                <a href="{{ route('admin.presensi.edit', $attendance) }}"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                    Edit
                                                </a>
                                                <button type="button"
                                                    onclick="openDeleteAttendanceModal({{ $attendance->id }}, '{{ addslashes($attendance->enrollment?->program?->name ?? '-') }}', '{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            <p>Tidak ada data presensi</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($attendances->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $attendances->links('pagination::tailwind') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Create Attendance Modal ─────────────────────────────────────────────── --}}
    <div x-show="modalOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="close()"
        style="background:rgba(0,0,0,.3);backdrop-filter:blur(2px)">

        <div x-show="modalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden">

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-indigo-600 rounded-t-2xl">
                <h3 class="text-white font-semibold text-base">Tambah Presensi Privat</h3>
                <button @click="close()" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto max-h-[80vh]">
                @include('admin.presensi._create-form')
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal ─────────────────────────────────────────── --}}
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

        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('attendance-tbody');
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
