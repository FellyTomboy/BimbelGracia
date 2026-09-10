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

    <script>
    // Enrollment tab Alpine factory — defined globally so it survives even if Alpine.data() registration throws.
    // Registered as Alpine.data below so Alpine resolves it via provider scope (not just window).
    function enrollmentTab(config) {
        return {
            type: config.type,
            enrollmentCount: config.count,
            search: config.search || '',
            loading: false,

            // ── Delete modal ─────────────────────────────────────────────
            deleteConfirmId: null,
            deleteLoading: false,

            confirmDelete(id) { this.deleteConfirmId = id; },
            cancelDelete() { this.deleteConfirmId = null; },

            async asyncHibernate(enrollmentId) {
                this.deleteLoading = true;
                try {
                    const resp = await window.Ajax.delete(`/admin/enrollments/${enrollmentId}`);
                    window.Toast?.success(resp.data?.message || 'Berhasil dihibernasi.');
                    this.deleteConfirmId = null;
                    this.removeEnrollmentRow(enrollmentId);
                    this.enrollmentCount = Math.max(0, this.enrollmentCount - 1);
                    window._enrollmentSetFlash(resp.data?.message || 'Berhasil dihibernasi.');
                } catch (e) {
                    if (e.response?.status !== 422) window.Toast?.error('Gagal hibernasi enrollment.');
                } finally {
                    this.deleteLoading = false;
                }
            },

            // ── Bulk delete modal ───────────────────────────────────────
            bulkConfirmOpen: false,
            bulkLoading: false,
            bulkCount: 0,

            openBulkConfirm() {
                const checked = document.querySelectorAll(`.row-checkbox-${this.type}:checked`);
                if (checked.length === 0) return;
                this.bulkCount = checked.length;
                this.bulkConfirmOpen = true;
            },
            cancelBulkDelete() { this.bulkConfirmOpen = false; },

            async asyncBulkHibernate() {
                const checked = document.querySelectorAll(`.row-checkbox-${this.type}:checked`);
                const ids = Array.from(checked).map(cb => cb.value);
                this.bulkLoading = true;
                try {
                    const params = new URLSearchParams();
                    ids.forEach(id => params.append('ids[]', id));
                    const resp = await window.Ajax.post('{{ route('admin.enrollments.bulk-destroy') }}', params);
                    window.Toast?.success(resp.data?.message || 'Berhasil dihibernasi.');
                    this.bulkConfirmOpen = false;
                    ids.forEach(id => this.removeEnrollmentRow(id));
                    this.enrollmentCount = Math.max(0, this.enrollmentCount - ids.length);
                    window._enrollmentSetFlash(resp.data?.message || 'Berhasil dihibernasi.');
                } catch (e) {
                    if (e.response?.status !== 422) window.Toast?.error('Gagal hibernasi massal.');
                } finally {
                    this.bulkLoading = false;
                }
            },

            // ── Row fade-out ────────────────────────────────────────────
            removeEnrollmentRow(id) {
                const row = document.querySelector(`tr[data-enrollment-id='${id}']`);
                if (!row) return;
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            },

            // ── Search (debounced) ───────────────────────────────────────
            onSearchSubmit(e) {
                e.preventDefault();
                this.fetchTab(this.searchUrl);
            },
            onSearchInput() {
                clearTimeout(this._searchTimer);
                this._searchTimer = setTimeout(() => this.fetchTab(this.searchUrl), 400);
            },
            get searchUrl() {
                const params = new URLSearchParams({ type: this.type, search: this.search });
                return '{{ route('admin.enrollments.index') }}?' + params.toString();
            },

            // ── Sort ───────────────────────────────────────────────────
            onSort(e) {
                e.preventDefault();
                const col = e.currentTarget.dataset.sortColumn;
                const dir = e.currentTarget.dataset.sortDir;
                const params = new URLSearchParams({ type: this.type, sort: col, direction: dir });
                if (this.search) params.set('search', this.search);
                this.fetchTab('{{ route('admin.enrollments.index') }}?' + params.toString());
            },

            // ── Pagination ──────────────────────────────────────────────
            onPageClick(e) {
                e.preventDefault();
                const url = e.currentTarget.dataset.url;
                if (!url) return;
                this.fetchTab(url);
            },

            // ── Core AJAX fetch ─────────────────────────────────────────
            async fetchTab(url) {
                this.loading = true;
                try {
                    const resp = await window.Ajax.get(url);
                    const html = resp.data;
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTab = doc.querySelector(`[data-tab-type='${this.type}']`);
                    const currentTab = document.querySelector(`[data-tab-type='${this.type}']`);
                    if (!newTab || !currentTab) return;

                    const countEl = newTab.querySelector('.enrollment-count');
                    if (countEl) this.enrollmentCount = parseInt(countEl.dataset.count) || 0;

                    const newInner = newTab.querySelector('.tab-inner-content');
                    const currentInner = currentTab.querySelector('.tab-inner-content');
                    if (newInner && currentInner) currentInner.innerHTML = newInner.innerHTML;

                    this._updateBulkButton();
                } catch (e) {
                    window.Toast?.error('Gagal memuat data.');
                } finally {
                    this.loading = false;
                }
            },

            // ── Bulk checkbox helpers ────────────────────────────────────
            toggleAll(source) {
                document.querySelectorAll(`.row-checkbox-${this.type}`).forEach(cb => cb.checked = source.checked);
                this._updateBulkButton();
            },
            onCheckboxChange() { this._updateBulkButton(); },
            _updateBulkButton() {
                const checked = document.querySelectorAll(`.row-checkbox-${this.type}:checked`);
                const btn = document.getElementById(`bulk-delete-btn-${this.type}`);
                if (btn) btn.classList.toggle('hidden', checked.length === 0);
            },

            // ── Modal stubs (delegate to page-level crudModal via $parent) ─
            // Edit button inside tab div calls $parent.openEdit(id)
            // These stubs prevent "openEdit is not a function" if called before delegation
            openEdit(id) { this.$parent.openEdit && this.$parent.openEdit(id); },
            openCreate() { this.$parent.openCreate && this.$parent.openCreate(); },
        };
    }

    // Global flash setter (bridges tab Alpine → page Alpine)
    window._enrollmentFlashSetter = null;
    window._enrollmentSetFlash = function(msg) {
        if (window._enrollmentFlashSetter) window._enrollmentFlashSetter(msg);
    };

    document.addEventListener('alpine:init', () => {
        try {
            Alpine.data('enrollmentTab', enrollmentTab);
        } catch (e) {
            console.error('[enrollment] Alpine.data("enrollmentTab") failed:', e);
        }
        try {
            Alpine.data('enrollmentModal', () => ({
            ...crudModal({
                createUrl: '{{ route('admin.enrollments.create-form') }}',
                storeUrl: '{{ route('admin.enrollments.store') }}',
                editUrl: (id) => '/admin/enrollments/' + id + '/edit-form',
                updateUrl: (id) => '/admin/enrollments/' + id,
                listSelector: 'table',
            }),
            activeTab: '{{ $activeTab }}',
            flashMessage: {{ \Illuminate\Support\Js::from(session('status') ?? '') }},
            showFlash: {{ \Illuminate\Support\Js::from((bool) session('status')) }},
            flashTimer: null,
            init() {
                // Inline submit listener — find the closest Alpine component with submit().
                // crudModal({}).init adds a document-level listener with [x-data^="crudModal"] selector
                // which never matches because this page uses x-data="enrollmentModal".
                document.addEventListener('submit', (e) => {
                    if (!e.target || e.target.id !== 'crud-form') return;
                    e.preventDefault();
                    if (this.submit) this.submit();
                }, true);
                window._enrollmentFlashSetter = (msg) => { this.setFlash(msg); };
                if (this.showFlash) this._startTimer();
            },
            _startTimer() {
                if (this.flashTimer) clearTimeout(this.flashTimer);
                this.showFlash = true;
                this.flashTimer = setTimeout(() => { this.showFlash = false; }, 4000);
            },
            setFlash(msg) { this.flashMessage = msg; this._startTimer(); },
            async refreshTable() {
                window.location.reload();
            },
            async openCreate(typeQuery) {
                const query = typeQuery || '';
                const url = this.createUrl + query;
                this.isEdit = false;
                this.currentId = null;
                this.errors = {};
                this.loading = true;
                this.modalTitle = 'Memuat...';
                this.modalBody = '<div class="flex justify-center py-8"><svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></div>';
                this.modalOpen = true;
                try {
                    const resp = await window.Ajax.get(url);
                    this.modalTitle = resp.data.title || 'Tambah Enrollment';
                    this.modalBody = resp.data.html || '';
                } catch (e) {
                    this.modalBody = '<div class="text-center py-8"><p class="text-rose-500 font-medium">Gagal memuat form.</p><button onclick="document.querySelector(\'[data-enrollment-page]\').__x.$data.close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button></div>';
                } finally {
                    this.loading = false;
                }
            },
            async submit() {
                const form = document.getElementById('crud-form');
                if (!form) return;
                const isFormEdit = this.isEdit;
                const url = isFormEdit
                    ? (typeof this.updateUrl === 'function' ? this.updateUrl(this.currentId) : this.updateUrl + '/' + this.currentId)
                    : this.storeUrl;
                const method = isFormEdit ? 'put' : 'post';
                this.submitting = true;
                const params = new URLSearchParams();
                for (const el of form.querySelectorAll('input[name], select[name], textarea[name]')) {
                    if (el.disabled) continue;
                    if (el.type === 'checkbox') { if (el.checked) params.append(el.name, el.value || 'on'); }
                    else if (el.type === 'radio') { if (el.checked) params.append(el.name, el.value); }
                    else if (el.tagName === 'SELECT' && el.multiple) { for (const o of el.selectedOptions) params.append(el.name, o.value); }
                    else { params.append(el.name, el.value); }
                }
                form.querySelectorAll('[class^="crud-error-"]').forEach(el => { el.style.display = 'none'; el.textContent = ''; });
                form.querySelectorAll('[class^="crud-field-"]').forEach(el => { el.classList.remove('border-rose-400','ring-1','ring-rose-300'); el.classList.add('border-gray-300'); });
                try {
                    await window.Ajax[method](url, params);
                    window.Toast?.success(isFormEdit ? 'Berhasil diperbarui.' : 'Berhasil disimpan.');
                    this.close();
                    this.refreshTable();
                } catch (e) {
                    if (e.response?.status === 422) {
                        const errors = e.response.data.errors || {};
                        Object.entries(errors).forEach(([field, messages]) => {
                            const errEl = form.querySelector('.crud-error-' + field);
                            if (errEl) { errEl.textContent = Array.isArray(messages) ? messages.join(', ') : messages; errEl.style.display = 'block'; }
                            const fieldEl = form.querySelector('.crud-field-' + field);
                            if (fieldEl) { fieldEl.classList.remove('border-gray-300'); fieldEl.classList.add('border-rose-400','ring-1','ring-rose-300'); }
                        });
                    } else {
                        window.Toast?.error('Gagal menyimpan enrollment.');
                    }
                    this.submitting = false;
                }
            },
        }));
        } catch (e) {
            console.error('[enrollment] Alpine.data("enrollmentModal") failed:', e);
        }
    });
    </script>

    <div class="py-8"
         data-enrollment-page
         x-data="enrollmentModal"
         @open-create-modal.window="openCreate($event.detail)"
         @open-create-kelas-modal.window="openCreate('?type=kelas')">

        {{-- ── Create / Edit Modal Shell ─────────────────────────────── --}}
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
                 class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl z-10">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="modalTitle"></h3>
                    <button @click="close()" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div x-show="loading" class="flex justify-center py-8">
                        <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                    <div x-show="!loading && modalBody" x-html="modalBody"></div>
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

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Banner --}}
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
                <button @click="activeTab = 'privat'"
                        class="px-5 py-2 rounded-xl text-sm font-medium transition-all"
                        :class="activeTab === 'privat' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Enrollment Privat
                </button>
                <button @click="activeTab = 'kelas'"
                        class="px-5 py-2 rounded-xl text-sm font-medium transition-all"
                        :class="activeTab === 'kelas' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Enrollment Kelas
                </button>
            </div>

            {{-- =================== PRIVAT TAB =================== --}}
            <div x-show="activeTab === 'privat'" x-cloak data-tab-type="privat">
                <div class="tab-inner" x-data="enrollmentTab({ type: 'privat', count: {{ $privatEnrollments->total() }}, search: {{ \Illuminate\Support\Js::from(request('search') ?? '') }} })">
                    <div class="tab-inner-content">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">
                            {{-- Loading overlay --}}
                            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 z-10 flex items-center justify-center rounded-2xl pointer-events-none">
                                <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>

                            <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 relative">
                                <form @submit="onSearchSubmit" class="flex-1 max-w-md">
                                    <input type="hidden" name="type" value="privat" />
                                    <div class="relative">
                                        <input type="text" x-model="search" @input="onSearchInput"
                                               placeholder="Cari murid, guru, program..."
                                               class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </form>
                                <div class="flex items-center gap-3">
                                    <button @click="openBulkConfirm()" id="bulk-delete-btn-privat"
                                            class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                        Hibernasi Massal
                                    </button>
                                    <span class="text-sm text-gray-400 enrollment-count" :data-count="enrollmentCount" x-text="enrollmentCount + ' enrollment'">{{ $privatEnrollments->total() }} enrollment</span>
                                    <button type="button"
                                            onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Enrollment Privat
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 bg-gray-50/50">
                                            <th class="py-3 px-4 w-10">
                                                <input type="checkbox" @change="toggleAll($event.target)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            </th>
                                            @php $headers = [
                                                ['Murid', 'students.name'],
                                                ['Guru', 'teachers.full_name'],
                                                ['Program', 'programs.name'],
                                                ['Biaya Ortu', 'enrollments.parent_rate'],
                                                ['Biaya Guru', 'enrollments.teacher_rate'],
                                                ['Validasi', 'enrollments.validation_status'],
                                            ]; @endphp
                                            @foreach ($headers as [$label, $col])
                                                @php
                                                    $isActive = request('sort') === $col;
                                                    $dir = $isActive && request('direction') === 'asc' ? 'desc' : 'asc';
                                                    $icon = $isActive ? (request('direction') === 'asc' ? ' ↑' : ' ↓') : '';
                                                @endphp
                                                <th class="py-3 px-4 font-medium">
                                                    <button type="button"
                                                            data-sort-column="{{ $col }}"
                                                            data-sort-dir="{{ $dir }}"
                                                            @click="onSort"
                                                            class="text-gray-500 hover:text-gray-700 text-left focus:outline-none focus:ring-0">
                                                        {{ $label }}{{ $icon }}
                                                    </button>
                                                </th>
                                            @endforeach
                                            <th class="py-3 px-4 font-medium">Sesi/Bulan</th>
                                            <th class="py-3 px-4 font-medium">Status</th>
                                            <th class="py-3 px-4 font-medium">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @forelse ($privatEnrollments as $enrollment)
                                            <tr data-enrollment-id="{{ $enrollment->id }}" class="hover:bg-gray-50/50 transition-colors">
                                                <td class="py-3 px-4">
                                                    <input type="checkbox" name="ids[]" value="{{ $enrollment->id }}"
                                                           class="row-checkbox-privat rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                           @change="onCheckboxChange" />
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
                                                        <button type="button" @click="$parent.openEdit({{ $enrollment->id }})"
                                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</button>
                                                        <button type="button" @click="confirmDelete({{ $enrollment->id }})"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
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

                            @if ($privatEnrollments->hasPages())
                                @php $p = $privatEnrollments->withQueryString(); @endphp
                                <div class="p-4 border-t border-gray-100 paging-links">
                                    @if ($p->lastPage() > 1)
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm text-gray-500">Menampilkan {{ $p->firstItem() }}–{{ $p->lastItem() }} dari {{ $p->total() }}</p>
                                            <div class="flex items-center gap-1">
                                                @if ($p->currentPage() > 1)
                                                    <button data-url="{{ $p->previousPageUrl() }}" @click="onPageClick" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">←</button>
                                                @endif
                                                @foreach ($p->getUrlRange(max(1, $p->currentPage() - 2), min($p->lastPage(), $p->currentPage() + 2)) as $page => $url)
                                                    <button data-url="{{ $url }}" @click="onPageClick"
                                                            class="w-8 h-8 rounded-lg text-sm transition-colors {{ $page == $p->currentPage() ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                                        {{ $page }}
                                                    </button>
                                                @endforeach
                                                @if ($p->currentPage() < $p->lastPage())
                                                    <button data-url="{{ $p->nextPageUrl() }}" @click="onPageClick" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">→</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Delete Modal --}}
                    <div x-show="deleteConfirmId !== null" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div x-show="deleteConfirmId !== null"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
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
                                    <button @click="cancelDelete()" :disabled="deleteLoading"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">Batal</button>
                                    <button @click="asyncHibernate(deleteConfirmId)" :disabled="deleteLoading"
                                            class="px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                                        <template x-if="deleteLoading"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                                        Hibernasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bulk Delete Modal --}}
                    <div x-show="bulkConfirmOpen" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div x-show="bulkConfirmOpen"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">Hibernasi Massal?</h3>
                                        <p class="text-sm text-gray-500 mt-0.5"><span x-text="bulkCount"></span> enrollment akan dihibernasi.</p>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-5">
                                    <button @click="cancelBulkDelete()" :disabled="bulkLoading"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">Batal</button>
                                    <button @click="asyncBulkHibernate()" :disabled="bulkLoading"
                                            class="px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                                        <template x-if="bulkLoading"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                                        Hibernasi Massal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================== KELAS TAB =================== --}}
            <div x-show="activeTab === 'kelas'" x-cloak data-tab-type="kelas">
                <div class="tab-inner" x-data="enrollmentTab({ type: 'kelas', count: {{ $kelasEnrollments->total() }}, search: {{ \Illuminate\Support\Js::from(request('search') ?? '') }} })">
                    <div class="tab-inner-content">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">
                            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 z-10 flex items-center justify-center rounded-2xl pointer-events-none">
                                <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>

                            <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 relative">
                                <form @submit="onSearchSubmit" class="flex-1 max-w-md">
                                    <input type="hidden" name="type" value="kelas" />
                                    <div class="relative">
                                        <input type="text" x-model="search" @input="onSearchInput"
                                               placeholder="Cari murid, program..."
                                               class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </form>
                                <div class="flex items-center gap-3">
                                    <button @click="openBulkConfirm()" id="bulk-delete-btn-kelas"
                                            class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                        Hibernasi Massal
                                    </button>
                                    <span class="text-sm text-gray-400 enrollment-count" :data-count="enrollmentCount" x-text="enrollmentCount + ' enrollment'">{{ $kelasEnrollments->total() }} enrollment</span>
                                    <button type="button"
                                            onclick="window.dispatchEvent(new CustomEvent('open-create-kelas-modal'))"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Enrollment Kelas
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 bg-gray-50/50">
                                            <th class="py-3 px-4 w-10">
                                                <input type="checkbox" @change="toggleAll($event.target)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            </th>
                                            @php $kheaders = [
                                                ['Murid', 'students.name'],
                                                ['Program', 'programs.name'],
                                                ['Biaya Ortu', 'enrollments.parent_rate'],
                                                ['Validasi', 'enrollments.validation_status'],
                                            ]; @endphp
                                            @foreach ($kheaders as [$label, $col])
                                                @php
                                                    $isActive = request('sort') === $col;
                                                    $dir = $isActive && request('direction') === 'asc' ? 'desc' : 'asc';
                                                    $icon = $isActive ? (request('direction') === 'asc' ? ' ↑' : ' ↓') : '';
                                                @endphp
                                                <th class="py-3 px-4 font-medium">
                                                    <button type="button"
                                                            data-sort-column="{{ $col }}"
                                                            data-sort-dir="{{ $dir }}"
                                                            @click="onSort"
                                                            class="text-gray-500 hover:text-gray-700 text-left focus:outline-none focus:ring-0">
                                                        {{ $label }}{{ $icon }}
                                                    </button>
                                                </th>
                                            @endforeach
                                            <th class="py-3 px-4 font-medium">Sesi/Bulan</th>
                                            <th class="py-3 px-4 font-medium">Status</th>
                                            <th class="py-3 px-4 font-medium">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @forelse ($kelasEnrollments as $enrollment)
                                            <tr data-enrollment-id="{{ $enrollment->id }}" class="hover:bg-gray-50/50 transition-colors">
                                                <td class="py-3 px-4">
                                                    <input type="checkbox" name="ids[]" value="{{ $enrollment->id }}"
                                                           class="row-checkbox-kelas rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                           @change="onCheckboxChange" />
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
                                                        <button type="button" @click="$parent.openEdit({{ $enrollment->id }})"
                                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</button>
                                                        <button type="button" @click="confirmDelete({{ $enrollment->id }})"
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

                            @if ($kelasEnrollments->hasPages())
                                @php $p = $kelasEnrollments->withQueryString(); @endphp
                                <div class="p-4 border-t border-gray-100 paging-links">
                                    @if ($p->lastPage() > 1)
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm text-gray-500">Menampilkan {{ $p->firstItem() }}–{{ $p->lastItem() }} dari {{ $p->total() }}</p>
                                            <div class="flex items-center gap-1">
                                                @if ($p->currentPage() > 1)
                                                    <button data-url="{{ $p->previousPageUrl() }}" @click="onPageClick" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">←</button>
                                                @endif
                                                @foreach ($p->getUrlRange(max(1, $p->currentPage() - 2), min($p->lastPage(), $p->currentPage() + 2)) as $page => $url)
                                                    <button data-url="{{ $url }}" @click="onPageClick"
                                                            class="w-8 h-8 rounded-lg text-sm transition-colors {{ $page == $p->currentPage() ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                                        {{ $page }}
                                                    </button>
                                                @endforeach
                                                @if ($p->currentPage() < $p->lastPage())
                                                    <button data-url="{{ $p->nextPageUrl() }}" @click="onPageClick" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">→</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Delete Modal --}}
                    <div x-show="deleteConfirmId !== null" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div x-show="deleteConfirmId !== null"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
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
                                    <button @click="cancelDelete()" :disabled="deleteLoading"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">Batal</button>
                                    <button @click="asyncHibernate(deleteConfirmId)" :disabled="deleteLoading"
                                            class="px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                                        <template x-if="deleteLoading"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                                        Hibernasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bulk Delete Modal --}}
                    <div x-show="bulkConfirmOpen" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div x-show="bulkConfirmOpen"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">Hibernasi Massal?</h3>
                                        <p class="text-sm text-gray-500 mt-0.5"><span x-text="bulkCount"></span> enrollment akan dihibernasi.</p>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-5">
                                    <button @click="cancelBulkDelete()" :disabled="bulkLoading"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">Batal</button>
                                    <button @click="asyncBulkHibernate()" :disabled="bulkLoading"
                                            class="px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                                        <template x-if="bulkLoading"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                                        Hibernasi Massal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>[x-cloak] { display: none !important; }</style>
    </div>
</x-app-layout>
