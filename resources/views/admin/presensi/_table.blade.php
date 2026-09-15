{{-- Filter & Search Bar ─────────────────────────────────────────────── --}}
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

{{-- Table ─────────────────────────────────────────────────────────── --}}
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
                    <tr class="hover:bg-gray-50/50 transition-colors"
                        data-lesson-date="{{ $attendance->lesson_date?->format('Y-m-d') ?? '' }}"
                        data-created-at="{{ $attendance->created_at?->timestamp ?? 0 }}"
                        data-program="{{ strtolower($attendance->enrollment?->program?->name ?? '') }}"
                        data-guru="{{ strtolower($attendance->enrollment?->teacher?->displayName ?? '') }}"
                        data-murid="{{ strtolower($attendance->students->map(fn($s) => $s->display_name)->join(' ')) }}">
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
                                    @if($billingMode === 'daily')
                                        <button type="button"
                                                @click="openEdit({{ $attendance->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                            Edit
                                        </button>
                                        <button type="button"
                                                onclick="window.dispatchEvent(new CustomEvent('open-delete-modal', {detail: {{ $attendance->id }}}))"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                            Hapus
                                        </button>
                                    @else
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
