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

    <div class="py-8"
         x-data="{
             tab: '{{ $activeTab }}',
             flashMessage: {{ \Illuminate\Support\Js::from(session('status') ?? '') }},
             showFlash: {{ \Illuminate\Support\Js::from((bool) session('status')) }},
             flashTimer: null,
             init() {
                 if (this.showFlash) {
                     this.flashTimer = setTimeout(() => { this.showFlash = false; }, 4000);
                 }
             },
             setFlash(msg) {
                 if (this.flashTimer) clearTimeout(this.flashTimer);
                 this.flashMessage = msg;
                 this.showFlash = true;
                 this.flashTimer = setTimeout(() => { this.showFlash = false; }, 4000);
             },
             removeEnrollmentRow(enrollmentId) {
                 const row = document.querySelector(`tr[data-enrollment-id='${enrollmentId}']`);
                 if (row) {
                     row.style.transition = 'opacity 0.3s';
                     row.style.opacity = '0';
                     setTimeout(() => row.remove(), 300);
                 }
             },
             removeEnrollmentRows(ids) {
                 ids.forEach(id => this.removeEnrollmentRow(id));
             },
             csrfToken() {
                 return document.querySelector('meta[name=csrf-token]')?.content
                     || document.querySelector('input[name=_token]')?.value;
             },

             // ── Delete confirmation modal ──────────────────────────────────
             deleteConfirmId: null,
             deleteLoading: false,

             confirmDelete(id) {
                 this.deleteConfirmId = id;
             },
             cancelDelete() {
                 this.deleteConfirmId = null;
             },
             asyncHibernate(enrollmentId) {
                 this.deleteLoading = true;
                 fetch(`/admin/enrollments/${enrollmentId}`, {
                     method: 'DELETE',
                     headers: {
                         'X-CSRF-TOKEN': this.csrfToken(),
                         'Accept': 'application/json',
                         'X-Requested-With': 'XMLHttpRequest',
                     },
                 }).then(res => res.json()).then(data => {
                     this.setFlash(data.message);
                     this.deleteConfirmId = null;
                     this.removeEnrollmentRow(enrollmentId);
                     const countEl = document.querySelector('#count-' + this.tab);
                     if (countEl) {
                         const n = parseInt(countEl.textContent.replace(/\D/g, '')) || 0;
                         if (n > 0) countEl.textContent = (n - 1) + ' enrollment';
                     }
                 }).catch(() => {
                     this.setFlash('Gagal hibernasi enrollment.');
                 }).finally(() => {
                     this.deleteLoading = false;
                 });
             },
             asyncBulkHibernate() {
                 const checked = document.querySelectorAll('.row-checkbox-' + this.tab + ':checked');
                 if (checked.length === 0) return;
                 const ids = Array.from(checked).map(cb => cb.value);
                 const btn = document.getElementById('bulk-delete-btn-' + this.tab);
                 if (btn) { btn.disabled = true; }
                 const fd = new FormData();
                 fd.append('_token', this.csrfToken());
                 ids.forEach(id => fd.append('ids[]', id));
                 fetch('{{ route('admin.enrollments.bulk-destroy') }}', {
                     method: 'POST',
                     body: fd,
                     headers: {
                         'X-CSRF-TOKEN': this.csrfToken(),
                         'Accept': 'application/json',
                         'X-Requested-With': 'XMLHttpRequest',
                     },
                 }).then(res => res.json()).then(data => {
                     this.setFlash(data.message);
                     this.removeEnrollmentRows(ids);
                     const countEl = document.getElementById('count-' + this.tab);
                     if (countEl) {
                         const n = parseInt(countEl.textContent.replace(/\D/g, '')) || 0;
                         const newN = Math.max(0, n - ids.length);
                         countEl.textContent = newN + ' enrollment';
                     }
                     if (btn) {
                         btn.classList.add('hidden');
                         btn.disabled = false;
                     }
                     document.querySelectorAll('.row-checkbox-' + this.tab).forEach(cb => cb.checked = false);
                 }).catch(() => {
                     if (btn) { btn.disabled = false; }
                     this.setFlash('Gagal hibernasi massal.');
                 });
             },
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Banner (Alpine, survives across AJAX calls) --}}
            <div x-show="showFlash"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="flashMessage"></span>
                <button @click="showFlash = false" class="ml-auto text-emerald-500 hover:text-emerald-700 font-bold text-lg leading-none">&times;</button>
            </div>

            @if (($mismatchKelasCount ?? 0) > 0 || ($mismatchPrivatCount ?? 0) > 0)
                <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>
                        Ada <strong>{{ ($mismatchKelasCount ?? 0) + ($mismatchPrivatCount ?? 0) }}</strong> enrollment yang program-nya tidak sesuai dengan jenjang kelas siswa
                        ({{ $mismatchKelasCount ?? 0 }} kelas, {{ $mismatchPrivatCount ?? 0 }} privat).
                    </span>
                    <a href="{{ route('admin.enrollments.mismatches') }}" class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-medium transition-colors">
                        Lihat &amp; Review →
                    </a>
                </div>
            @endif

            {{-- Tab Buttons --}}
            <div class="flex items-center gap-1 mb-4 bg-white rounded-2xl p-1 shadow-sm border border-gray-100 w-fit">
                <a href="{{ route('admin.enrollments.index', ['type' => 'privat']) }}"
                   class="px-5 py-2 rounded-xl text-sm font-medium transition-all"
                   :class="tab === 'privat' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Enrollment Privat
                </a>
                <a href="{{ route('admin.enrollments.index', ['type' => 'kelas']) }}"
                   class="px-5 py-2 rounded-xl text-sm font-medium transition-all"
                   :class="tab === 'kelas' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Enrollment Kelas
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
                            <button @click.prevent="asyncBulkHibernate()"
                                    id="bulk-delete-btn-kelas"
                                    class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                Hibernasi Massal
                            </button>
                            <span id="count-kelas" class="text-sm text-gray-400">{{ $kelasEnrollments->total() }} enrollment</span>
                            <a href="{{ route('admin.enrollments.create', ['type' => 'kelas']) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Enrollment Kelas
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 bg-gray-50/50">
                                        <th class="py-3 px-4 w-10">
                                            <input type="checkbox" onclick="toggleAllKelas(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </th>
                                        <x-sortable-header label="Murid" column="students.name" />
                                        <x-sortable-header label="Program" column="programs.name" />
                                        <x-sortable-header label="Biaya Ortu" column="enrollments.parent_rate" />
                                        <th class="py-3 px-4 font-medium">Sesi/Bulan</th>
                                        <th class="py-3 px-4 font-medium">Status</th>
                                        <x-sortable-header label="Validasi" column="enrollments.validation_status" />
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($kelasEnrollments as $enrollment)
                                        <tr data-enrollment-id="{{ $enrollment->id }}" class="hover:bg-gray-50/50 transition-colors">
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
                                                <div class="flex flex-col gap-1 items-start">
                                                    @if ($enrollment->status === 'active')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $enrollment->status }}</span>
                                                    @endif
                                                    @if (\App\Helpers\JenjangMatcher::isMismatch($enrollment))
                                                        <a href="{{ route('admin.enrollments.mismatches') }}" title="Program tidak sesuai jenjang siswa"
                                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            Mismatch
                                                        </a>
                                                    @endif
                                                </div>
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
                                                            @click="confirmDelete({{ $enrollment->id }})"
                                                            class="btn-hibernate inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
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
                    </div>
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
                            <button @click.prevent="asyncBulkHibernate()"
                                    id="bulk-delete-btn-privat"
                                    class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                Hibernasi Massal
                            </button>
                            <span id="count-privat" class="text-sm text-gray-400">{{ $privatEnrollments->total() }} enrollment</span>
                            <a href="{{ route('admin.enrollments.create', ['type' => 'privat']) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Enrollment Privat
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 bg-gray-50/50">
                                        <th class="py-3 px-4 w-10">
                                            <input type="checkbox" onclick="toggleAllPrivat(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </th>
                                        <x-sortable-header label="Murid" column="students.name" />
                                        <x-sortable-header label="Guru" column="teachers.full_name" />
                                        <x-sortable-header label="Program" column="programs.name" />
                                        <x-sortable-header label="Biaya Ortu" column="enrollments.parent_rate" />
                                        <x-sortable-header label="Biaya Guru" column="enrollments.teacher_rate" />
                                        <th class="py-3 px-4 font-medium">Sesi/Bulan</th>
                                        <th class="py-3 px-4 font-medium">Status</th>
                                        <x-sortable-header label="Validasi" column="enrollments.validation_status" />
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($privatEnrollments as $enrollment)
                                        <tr data-enrollment-id="{{ $enrollment->id }}" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4">
                                                <input type="checkbox" name="ids[]" value="{{ $enrollment->id }}"
                                                       class="row-checkbox-privat rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                       onclick="updateBulkButtonPrivat()" />
                                            </td>
                                            <td class="py-3 px-4 font-medium text-gray-900">
                                                {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}
                                            </td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->teacher?->displayName ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->program?->name ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-600">Rp {{ number_format($enrollment->parent_rate) }}</td>
                                            <td class="py-3 px-4 text-gray-600">Rp {{ number_format($enrollment->teacher_rate) }}</td>
                                            <td class="py-3 px-4 text-gray-600">{{ $enrollment->agreed_sessions_per_month }}x</td>
                                            <td class="py-3 px-4">
                                                <div class="flex flex-col gap-1 items-start">
                                                    @if ($enrollment->status === 'active')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $enrollment->status }}</span>
                                                    @endif
                                                    @if (\App\Helpers\JenjangMatcher::isMismatch($enrollment))
                                                        <a href="{{ route('admin.enrollments.mismatches') }}" title="Program tidak sesuai jenjang siswa"
                                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            Mismatch
                                                        </a>
                                                    @endif
                                                </div>
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
                                                            @click="confirmDelete({{ $enrollment->id }})"
                                                            class="btn-hibernate inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-12">
                                                <x-empty-state icon="📝" title="Belum ada enrollment privat" description="Daftarkan murid ke program privat." action="Tambah Enrollment Privat" actionUrl="{{ route('admin.enrollments.create', ['type' => 'privat']) }}" />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($privatEnrollments->hasPages())
                        <div class="p-4 border-t border-gray-100">{{ $privatEnrollments->withQueryString()->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Listen for hibernate events from row buttons --}}
        <div @hibernate.window="asyncHibernate($event.detail.id)"></div>

        {{-- ── Modal: Hibernasi Confirmation ───────────────────────────────── --}}
        <div x-show="deleteConfirmId !== null"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div x-show="deleteConfirmId !== null"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Hibernasi Enrollment?</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Enrollment akan dipindahkan ke data tidak aktif.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-5">
                        <button @click="cancelDelete()"
                                :disabled="deleteLoading"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">
                            Batal
                        </button>
                        <button @click="asyncHibernate(deleteConfirmId)"
                                :disabled="deleteLoading"
                                class="px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                            <template x-if="deleteLoading">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            Hibernasi
                        </button>
                    </div>
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
        function toggleAllPrivat(source) {
            document.querySelectorAll('.row-checkbox-privat').forEach(cb => cb.checked = source.checked);
            updateBulkButtonPrivat();
        }
        function updateBulkButtonPrivat() {
            const checked = document.querySelectorAll('.row-checkbox-privat:checked');
            document.getElementById('bulk-delete-btn-privat').classList.toggle('hidden', checked.length === 0);
        }

        // Scroll preservation (only for non-AJAX page loads)
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

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
