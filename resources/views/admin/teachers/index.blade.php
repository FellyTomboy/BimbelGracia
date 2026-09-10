<x-app-layout>
    <x-slot name="title">Guru</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Guru']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data guru pengajar</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.teachers.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Guru
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         id="teachers-page"
         x-data="Object.assign(crudModal({
             createUrl: '{{ route('admin.teachers.create-form') }}',
             storeUrl: '{{ route('admin.teachers.store') }}',
             editUrl: (id) => `/admin/teachers/${id}/form`,
             updateUrl: (id) => `/admin/teachers/${id}`,
             deleteUrl: (id) => `/admin/teachers/${id}`,
             deleteMethod: 'delete',
             listSelector: 'table',
         }), {
             approveLoading: null,

             async approvePhoto(id) {
                 this.approveLoading = id;
                 try {
                     await window.Ajax.post(`/admin/teachers/${id}/approve-photo`);
                     window.Toast?.success('Foto profil disetujui.');
                     await this.refreshTable();
                 } catch (e) { /* toast handled by ajax.js */ }
                 finally { this.approveLoading = null; }
             },

             passwordModalOpen: false,
             passwordModalTeacherId: null,
             passwordModalTeacherName: '',
             passwordForm: { password: '', password_confirmation: '' },
             passwordSubmitting: false,
             passwordErrors: {},

             openPasswordModal(id, name) {
                 this.passwordModalTeacherId = id;
                 this.passwordModalTeacherName = name;
                 this.passwordForm = { password: '', password_confirmation: '' };
                 this.passwordErrors = {};
                 this.passwordSubmitting = false;
                 this.passwordModalOpen = true;
             },

             async submitPassword() {
                 if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
                     this.passwordErrors = { password: ['Konfirmasi password tidak cocok.'] };
                     return;
                 }
                 this.passwordSubmitting = true;
                 this.passwordErrors = {};
                 const params = new URLSearchParams();
                 Object.entries(this.passwordForm).forEach(([k, v]) => { if (v) params.append(k, v); });
                 try {
                     await window.Ajax.post(`/admin/teachers/${this.passwordModalTeacherId}/change-password`, params);
                     window.Toast?.success('Password berhasil diubah.');
                     this.passwordModalOpen = false;
                     this.passwordForm = { password: '', password_confirmation: '' };
                 } catch (e) {
                     if (e.response?.status === 422) this.passwordErrors = e.response.data.errors || {};
                 } finally {
                     this.passwordSubmitting = false;
                 }
             }
         })"
         @open-create-modal.window="openCreate()">

        {{-- ── Modal: Create / Edit ──────────────────────────────── --}}
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
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl">
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

        {{-- ── Modal: Hibernasi Confirmation ───────────────────────── --}}
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hibernasi Guru?</h3>
                <p class="text-sm text-gray-500 mb-6">Guru akan dipindahkan ke Data Tidak Aktif.</p>
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

        {{-- ── Modal: Ubah Password ───────────────────────────────── --}}
        <div x-show="passwordModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500/60 z-50 flex items-center justify-center p-4"
             @click.self="passwordModalOpen = false"
             @keydown.escape.window="passwordModalOpen = false"
             style="display:none">
            <div x-show="passwordModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-sm max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Ubah Password</h3>
                        <p class="text-sm text-gray-500 mt-0.5" x-text="passwordModalTeacherName"></p>
                    </div>
                    <button @click="passwordModalOpen = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitPassword()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input type="password" x-model="passwordForm.password" class="w-full border-gray-300 rounded-lg text-sm" required minlength="6" />
                        <p x-show="passwordErrors?.password" x-text="passwordErrors.password" class="mt-1 text-sm text-rose-500"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" x-model="passwordForm.password_confirmation" class="w-full border-gray-300 rounded-lg text-sm" required minlength="6" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="passwordModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                        <button type="submit"
                                :disabled="passwordSubmitting"
                                class="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                            <svg x-show="passwordSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Ubah Password
                        </button>
                    </div>
                </form>
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
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <x-search-form placeholder="Cari nama, email, WA..." />
                    <div class="flex items-center gap-3">
                        <button id="bulk-hibernate-btn"
                                onclick="submitBulkHibernate()"
                                class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                            Hibernasi Massal
                        </button>
                        <span class="text-sm text-gray-400">{{ $teachers->total() }} guru</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 w-10">
                                    <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="py-3 px-4 font-medium">Foto</th>
                                <x-sortable-header label="Nama" column="teachers.full_name" />
                                <th class="py-3 px-4 font-medium">Nama Panggilan</th>
                                <th class="py-3 px-4 font-medium">WA</th>
                                <th class="py-3 px-4 font-medium">Jurusan</th>
                                <th class="py-3 px-4 font-medium">Rekening</th>
                                <th class="py-3 px-4 font-medium">Status Foto</th>
                                <x-sortable-header label="Status" column="teachers.status" />
                                <th class="py-3 px-4 font-medium">Presensi Bulan Ini</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($teachers as $teacher)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" value="{{ $teacher->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkButton()" />
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($teacher->profile_photo_url)
                                            <img src="{{ $teacher->profile_photo_url }}" alt="{{ $teacher->displayName }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-xs">-</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-gray-900">{{ $teacher->displayName }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">{{ $teacher->nickname ?: '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $teacher->whatsapp_number ?? $teacher->whatsapp ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $teacher->major ?: '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($teacher->bank_name)
                                            <span class="text-xs">{{ $teacher->bank_name }} {{ $teacher->bank_account }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($teacher->profile_photo_path)
                                            @if ($teacher->profile_photo_approved)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Disetujui</span>
                                            @else
                                                <button type="button"
                                                        @click="approvePhoto({{ $teacher->id }})"
                                                        :disabled="approveLoading === {{ $teacher->id }}"
                                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors disabled:opacity-50">
                                                    <svg x-show="approveLoading === {{ $teacher->id }}" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    Setujui
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($teacher->status === 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $teacher->status }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($teacher->status !== 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-400 border border-gray-200">-</span>
                                        @elseif ($teacher->attendance_count_this_month > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✓ {{ $teacher->attendance_count_this_month }}x
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                ✗ Belum isi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <button type="button"
                                                    @click="openEdit({{ $teacher->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</button>
                                            <button type="button"
                                                    @click="openPasswordModal({{ $teacher->id }}, '{{ addslashes($teacher->displayName) }}')"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">Password</button>
                                            <button type="button"
                                                    @click="confirmDelete({{ $teacher->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11"><x-empty-state icon="👨‍🏫" title="Belum ada guru" description="Tambahkan guru baru." action="Tambah Guru" actionUrl="{{ route('admin.teachers.create') }}" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($teachers->hasPages())
                    <div class="p-4 border-t border-gray-100 paging-links">{{ $teachers->withQueryString()->links() }}</div>
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
            if (!confirm('Hibernasi ' + checked.length + ' guru yang dipilih?')) return;
            const params = new URLSearchParams();
            params.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
            checked.forEach(cb => params.append('ids[]', cb.value));
            const btn = document.getElementById('bulk-hibernate-btn');
            btn.disabled = true;
            try {
                await window.Ajax.post('{{ route('admin.teachers.bulk-destroy') }}', params);
                window.Toast?.success(checked.length + ' guru berhasil dihibernasi.');
                const crudEl = document.getElementById('teachers-page');
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
