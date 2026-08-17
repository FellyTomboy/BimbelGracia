<x-app-layout>
    <x-slot name="title">Enrollment</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Enrollment']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enrollment</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola pendaftaran murid ke program</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.enrollments.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ tab: '{{ $activeTab }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Tab Buttons --}}
            <div class="flex items-center gap-1 mb-4 bg-white rounded-2xl p-1 shadow-sm border border-gray-100 w-fit">
                <a href="{{ route('admin.enrollments.index', ['type' => 'kelas']) }}"
                   class="px-5 py-2 rounded-xl text-sm font-medium transition-all"
                   :class="tab === 'kelas' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Enrollment Kelas
                </a>
                <a href="{{ route('admin.enrollments.index', ['type' => 'privat']) }}"
                   class="px-5 py-2 rounded-xl text-sm font-medium transition-all"
                   :class="tab === 'privat' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Enrollment Privat
                </a>
            </div>

            {{-- ======= KELAS TAB ======= --}}
            <div x-show="tab === 'kelas'" x-cloak>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                        <form method="GET" action="{{ route('admin.enrollments.index') }}" class="flex-1 max-w-md">
                            <input type="hidden" name="type" value="kelas" />
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="Cari murid, program..."
                                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </form>
                        <div class="flex items-center gap-3">
                            <button onclick="submitBulkDeleteKelas()" id="bulk-delete-btn-kelas" class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                Hibernasi Massal
                            </button>
                            <span class="text-sm text-gray-400">{{ $kelasEnrollments->total() }} enrollment</span>
                            <a href="{{ route('admin.enrollments.create', ['type' => 'kelas']) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Enrollment Kelas
                            </a>
                        </div>
                    </div>
                    <form id="bulk-form-kelas" method="POST" action="{{ route('admin.enrollments.bulk-destroy') }}" onsubmit="return validateBulkDeleteKelas()">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 bg-gray-50/50">
                                        <th class="py-3 px-4 w-10">
                                            <input type="checkbox" onclick="toggleAllKelas(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </th>
                                        <x-sortable-header label="Murid" column="students.name" />
                                        <x-sortable-header label="Program" column="programs.name" />
                                        <x-sortable-header label="Tarif Ortu" column="enrollments.parent_rate" />
                                        <th class="py-3 px-4 font-medium">Sesi/Bulan</th>
                                        <th class="py-3 px-4 font-medium">Status</th>
                                        <x-sortable-header label="Validasi" column="enrollments.validation_status" />
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($kelasEnrollments as $enrollment)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4">
                                                <input type="checkbox" name="ids[]" value="{{ $enrollment->id }}"
                                                       class="row-checkbox-kelas rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                       onclick="updateBulkButtonKelas()" />
                                            </td>
                                            <td class="py-3 px-4 font-medium text-gray-900">
                                                {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}
                                            </td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->program?->name ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-600">Rp {{ number_format($enrollment->parent_rate) }}</td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->agreed_sessions_per_month }}x</td>
                                            <td class="py-3 px-4">
                                                @if ($enrollment->status === 'active')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $enrollment->status }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                @if ($enrollment->validation_status == 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">0 - Belum ada</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">1 - Ada presensi</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('admin.enrollments.edit', $enrollment) }}"
                                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</a>
                                                    <button type="button"
                                                            onclick="submitDelete('/admin/enrollments/{{ $enrollment->id }}', 'Hibernasi enrollment ini?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-12">
                                                <x-empty-state icon="📝" title="Belum ada enrollment kelas" description="Daftarkan murid ke program kelas." action="Tambah Enrollment Kelas" actionUrl="{{ route('admin.enrollments.create', ['type' => 'kelas']) }}" />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                    @if ($kelasEnrollments->hasPages())
                        <div class="p-4 border-t border-gray-100">{{ $kelasEnrollments->withQueryString()->links() }}</div>
                    @endif
                </div>
            </div>

            {{-- ======= PRIVAT TAB ======= --}}
            <div x-show="tab === 'privat'" x-cloak>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                        <form method="GET" action="{{ route('admin.enrollments.index') }}" class="flex-1 max-w-md">
                            <input type="hidden" name="type" value="privat" />
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="Cari murid, guru, program..."
                                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </form>
                        <div class="flex items-center gap-3">
                            <button onclick="submitBulkDeletePrivat()" id="bulk-delete-btn-privat" class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                Hibernasi Massal
                            </button>
                            <span class="text-sm text-gray-400">{{ $privatEnrollments->total() }} enrollment</span>
                            <a href="{{ route('admin.enrollments.create', ['type' => 'privat']) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Enrollment Privat
                            </a>
                        </div>
                    </div>
                    <form id="bulk-form-privat" method="POST" action="{{ route('admin.enrollments.bulk-destroy') }}" onsubmit="return validateBulkDeletePrivat()">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 bg-gray-50/50">
                                        <th class="py-3 px-4 w-10">
                                            <input type="checkbox" onclick="toggleAllPrivat(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </th>
                                        <x-sortable-header label="Murid" column="students.name" />
                                        <x-sortable-header label="Guru" column="teachers.name" />
                                        <x-sortable-header label="Program" column="programs.name" />
                                        <x-sortable-header label="Tarif Ortu" column="enrollments.parent_rate" />
                                        <x-sortable-header label="Tarif Guru" column="enrollments.teacher_rate" />
                                        <th class="py-3 px-4 font-medium">Status</th>
                                        <x-sortable-header label="Validasi" column="enrollments.validation_status" />
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($privatEnrollments as $enrollment)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4">
                                                <input type="checkbox" name="ids[]" value="{{ $enrollment->id }}"
                                                       class="row-checkbox-privat rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                       onclick="updateBulkButtonPrivat()" />
                                            </td>
                                            <td class="py-3 px-4 font-medium text-gray-900">
                                                {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}
                                            </td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->teacher?->name ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->program?->name ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-600">Rp {{ number_format($enrollment->parent_rate) }}</td>
                                            <td class="py-3 px-4 text-gray-600">Rp {{ number_format($enrollment->teacher_rate) }}</td>
                                            <td class="py-3 px-4">
                                                @if ($enrollment->status === 'active')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $enrollment->status }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                @if ($enrollment->validation_status == 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">0 - Belum ada</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">1 - Ada presensi</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('admin.enrollments.edit', $enrollment) }}"
                                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</a>
                                                    <button type="button"
                                                            onclick="submitDelete('/admin/enrollments/{{ $enrollment->id }}', 'Hibernasi enrollment ini?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-12">
                                                <x-empty-state icon="📝" title="Belum ada enrollment privat" description="Daftarkan murid ke program privat." action="Tambah Enrollment Privat" actionUrl="{{ route('admin.enrollments.create', ['type' => 'privat']) }}" />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                    @if ($privatEnrollments->hasPages())
                        <div class="p-4 border-t border-gray-100">{{ $privatEnrollments->withQueryString()->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAllKelas(source) {
            document.querySelectorAll('.row-checkbox-kelas').forEach(cb => cb.checked = source.checked);
            updateBulkButtonKelas();
        }
        function updateBulkButtonKelas() {
            const checked = document.querySelectorAll('.row-checkbox-kelas:checked');
            document.getElementById('bulk-delete-btn-kelas').classList.toggle('hidden', checked.length === 0);
        }
        function validateBulkDeleteKelas() {
            const checked = document.querySelectorAll('.row-checkbox-kelas:checked');
            if (checked.length === 0) { alert('Pilih minimal 1 data untuk dihibernasi.'); return false; }
            return confirm('Hibernasi ' + checked.length + ' enrollment kelas yang dipilih?');
        }
        function submitBulkDeleteKelas() { document.getElementById('bulk-form-kelas').submit(); }

        function toggleAllPrivat(source) {
            document.querySelectorAll('.row-checkbox-privat').forEach(cb => cb.checked = source.checked);
            updateBulkButtonPrivat();
        }
        function updateBulkButtonPrivat() {
            const checked = document.querySelectorAll('.row-checkbox-privat:checked');
            document.getElementById('bulk-delete-btn-privat').classList.toggle('hidden', checked.length === 0);
        }
        function validateBulkDeletePrivat() {
            const checked = document.querySelectorAll('.row-checkbox-privat:checked');
            if (checked.length === 0) { alert('Pilih minimal 1 data untuk dihibernasi.'); return false; }
            return confirm('Hibernasi ' + checked.length + ' enrollment privat yang dipilih?');
        }
        function submitBulkDeletePrivat() { document.getElementById('bulk-form-privat').submit(); }

        function submitDelete(action, message) {
            if (!confirm(message)) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            form.innerHTML = '<input name="_token" value="{{ csrf_token() }}"><input name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }
    </script>

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
