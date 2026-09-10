<x-app-layout>
    <x-slot name="title">Data Parent</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Parent']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Parent</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data orang tua murid</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.parents.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Parent
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="crudModal({
             createUrl: '{{ route('admin.parents.create-form') }}',
             storeUrl: '{{ route('admin.parents.store') }}',
             editUrl: (id) => `/admin/parents/${id}/edit-form`,
             updateUrl: (id) => `/admin/parents/${id}`,
             deleteUrl: (id) => `/admin/parents/${id}/hibernate`,
             deleteMethod: 'post',
             listSelector: 'table',
             modalName: 'parent-crud',
         })"
         @open-create-modal.window="openCreate()">

        {{-- ── Modal: Parent Create/Edit ─────────────────────────────── --}}
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hibernasi Parent?</h3>
                <p class="text-sm text-gray-500 mb-6">Semua murid di bawahnya juga akan dihibernasi.</p>
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

        {{-- ── Modal: Edit Murid ──────────────────────────────────────── --}}
        <div x-show="studentModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500/60 z-50 flex items-center justify-center p-4"
             @click.self="studentModalOpen = false"
             @keydown.escape.window="studentModalOpen = false"
             style="display:none">
            <div x-show="studentModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Edit Murid</h3>
                        <p class="text-sm text-gray-500 mt-0.5" x-text="studentModalParent"></p>
                    </div>
                    <button @click="studentModalOpen = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">

                    {{-- Student List --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Daftar Murid</h4>
                        <template x-if="studentLoading">
                            <div class="flex justify-center py-6">
                                <svg class="animate-spin w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="!studentLoading && studentList.length === 0">
                            <p class="text-sm text-gray-400 text-center py-4">Belum ada murid.</p>
                        </template>
                        <template x-if="!studentLoading && studentList.length > 0">
                            <div class="space-y-2">
                                <template x-for="student in studentList" :key="student.id">
                                    <div class="flex items-start gap-2 p-3 bg-gray-50 rounded-lg">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="student.nickname"></p>
                                            <p class="text-xs text-gray-500" x-text="student.full_name || '-'"></p>
                                        </div>
                                        <div class="flex items-center gap-2 ml-2">
                                            <button type="button"
                                                    @click="showStudentEdit(student)"
                                                    class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</button>
                                            <button type="button"
                                                    @click="removeStudentRow(student.id, student.nickname)"
                                                    class="text-rose-600 hover:text-rose-800 text-xs font-medium">Hibernasi</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Edit Student Inline --}}
                    <div x-show="studentEditId !== null" class="border-t border-gray-200 pt-5">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Edit Murid</h4>
                        <form @submit.prevent="submitStudentEdit()">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nickname</label>
                                    <input type="text" x-model="studentEditForm.nickname" readonly class="w-full border-gray-300 rounded-lg text-sm bg-gray-100" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nama Lengkap <span class="text-gray-400">(ops)</span></label>
                                    <input type="text" x-model="studentEditForm.full_name" class="w-full border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Sekolah <span class="text-gray-400">(ops)</span></label>
                                    <input type="text" x-model="studentEditForm.sekolah" class="w-full border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Kelas</label>
                                    <select x-model="studentEditForm.kelas" class="w-full border-gray-300 rounded-lg text-sm">
                                        <option value="">— Pilih kelas —</option>
                                        @foreach (\App\Helpers\StudentGrade::LEVELS as $level)
                                            <option value="{{ $level }}">{{ $level }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p x-show="studentEditErrors && Object.keys(studentEditErrors).length > 0"
                               class="text-xs text-rose-500 mb-2">Perbaiki kesalahan di bawah.</p>
                            <div class="flex gap-2">
                                <button type="submit"
                                        :disabled="studentEditSubmitting"
                                        class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 disabled:opacity-50 flex items-center gap-1">
                                    <svg x-show="studentEditSubmitting" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Simpan
                                </button>
                                <button type="button" @click="studentEditId = null"
                                        class="px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                            </div>
                        </form>
                    </div>

                    {{-- Add Student --}}
                    <div class="border-t border-gray-200 pt-5">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Tambah Murid</h4>
                        <form @submit.prevent="submitAddStudent()">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nickname <span class="text-rose-400">*</span></label>
                                    <input type="text" x-model="addStudentForm.nickname" class="w-full border-gray-300 rounded-lg text-sm" />
                                    <p x-show="addStudentErrors?.nickname" x-text="addStudentErrors.nickname" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nama Lengkap <span class="text-gray-400">(ops)</span></label>
                                    <input type="text" x-model="addStudentForm.full_name" class="w-full border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Sekolah <span class="text-gray-400">(ops)</span></label>
                                    <input type="text" x-model="addStudentForm.sekolah" class="w-full border-gray-300 rounded-lg text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Kelas</label>
                                    <select x-model="addStudentForm.kelas" class="w-full border-gray-300 rounded-lg text-sm">
                                        <option value="">— Pilih kelas —</option>
                                        @foreach (\App\Helpers\StudentGrade::LEVELS as $level)
                                            <option value="{{ $level }}">{{ $level }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p x-show="addStudentErrors?.students" x-text="addStudentErrors.students" class="text-xs text-rose-500 mb-2"></p>
                            <button type="submit"
                                    :disabled="addStudentSubmitting"
                                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2">
                                <svg x-show="addStudentSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Tambah Murid
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Modal: Ubah Password ─────────────────────────────────── --}}
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
                    <h3 class="text-lg font-semibold text-gray-900">Ubah Password</h3>
                    <button @click="passwordModalOpen = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitPassword()" class="p-6 space-y-4">
                    <p class="text-sm text-gray-500" x-text="'Ubah password untuk: ' + passwordModalParent"></p>

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
                                class="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="passwordSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Page Content ─────────────────────────────────────────── --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $parents->total() }} parent</div>
                    <form method="GET" action="{{ route('admin.parents.index') }}" class="flex items-center gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, HP, atau nama murid..." class="border-gray-300 rounded-lg text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500" />
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors">Cari</button>
                        @if (request('search'))
                            <a href="{{ route('admin.parents.index') }}" class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 text-sm hover:bg-gray-200 transition-colors">Reset</a>
                        @endif
                    </form>
                    <button id="bulk-hibernate-btn"
                            onclick="submitBulkHibernate()"
                            class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                        Hibernasi Massal
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 w-10">
                                    <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th class="py-3 px-4 font-medium">#</th>
                                <th class="py-3 px-4 font-medium">
                                    <a href="{{ route('admin.parents.index', array_merge(request()->query(), ['sort' => 'name', 'dir' => ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-700">
                                        Nama Parent
                                        @if ($sortBy === 'name')
                                            @if ($sortDir === 'asc') ↑@else ↓@endif
                                        @endif
                                    </a>
                                </th>
                                <th class="py-3 px-4 font-medium">
                                    <a href="{{ route('admin.parents.index', array_merge(request()->query(), ['sort' => 'phone', 'dir' => ($sortBy === 'phone' && $sortDir === 'asc') ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-700">
                                        No HP
                                        @if ($sortBy === 'phone')
                                            @if ($sortDir === 'asc') ↑@else ↓@endif
                                        @endif
                                    </a>
                                </th>
                                <th class="py-3 px-4 font-medium">Alamat</th>
                                <th class="py-3 px-4 font-medium">Murid</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($parents as $index => $parent)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" value="{{ $parent->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkButton()" />
                                    </td>
                                    <td class="py-3 px-4 text-gray-400">{{ $parents->firstItem() + $index }}</td>
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $parent->name ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $parent->user?->phone ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600 text-xs max-w-xs truncate">{{ $parent->address ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">
                                        @if ($parent->students->isNotEmpty())
                                            @foreach ($parent->students as $si => $student)
                                                <div class="flex items-center gap-1 mb-0.5">
                                                    <span class="mr-1">{{ $si + 1 }}.</span>
                                                    <span>{{ $student->display_name }}</span>
                                                    @if ($student->kelas)
                                                        <span class="text-gray-400">·</span>
                                                        <span class="text-gray-500">Kelas {{ $student->kelas }}</span>
                                                    @endif
                                                    @if ($student->status === 'lulus')
                                                        <x-student-status-badge :status="$student->status" />
                                                    @elseif ($student->status === 'hibernasi')
                                                        <x-student-status-badge :status="$student->status" />
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <button type="button"
                                                    @click="openEdit({{ $parent->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                Edit
                                            </button>
                                            <button type="button"
                                                    @click="openStudentModal({{ $parent->id }}, '{{ addslashes($parent->name ?? $parent->user?->phone ?? '') }}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-sky-600 bg-sky-50 hover:bg-sky-100 transition-colors">
                                                Edit Murid
                                            </button>
                                            <button type="button"
                                                    @click="openPasswordModal({{ $parent->id }}, '{{ addslashes($parent->name ?? $parent->user?->phone ?? '') }}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                                Password
                                            </button>
                                            <button type="button"
                                                    @click="confirmDelete({{ $parent->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                Hibernasi
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <x-empty-state icon="👤" title="Belum ada parent" description="Tambahkan parent baru untuk memulai." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($parents->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $parents->withQueryString()->links() }}
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
            if (!confirm('Hibernasi ' + checked.length + ' parent yang dipilih?')) return;
            const params = new URLSearchParams();
            params.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            checked.forEach(cb => params.append('ids[]', cb.value));
            const btn = document.getElementById('bulk-hibernate-btn');
            btn.disabled = true;
            try {
                await window.Ajax.post('{{ route('admin.parents.bulk-destroy') }}', params);
                window.Toast?.success(checked.length + ' parent berhasil dihibernasi.');
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

        // Create-parent form: tambah/hapus baris murid.
        // Harus di sini (bukan di dalam _form.blade.php) karena form di-inject
        // via Alpine x-html, dan <script> di dalam innerHTML tidak dieksekusi browser.
        (function () {
            var niveles = @json(\App\Helpers\StudentGrade::LEVELS);
            var studentIndex = 0;
            function buildKelasOptions() {
                var opt = '<option value="">— Kelas —</option>';
                niveles.forEach(function (n) { opt += '<option value="' + n + '">' + n + '</option>'; });
                return opt;
            }
            function buildRow(idx) {
                var d = document.createElement('div');
                d.className = 'flex flex-wrap items-start gap-2 student-row';
                d.dataset.index = idx;
                d.innerHTML =
                    '<input type="text" name="students[' + idx + '][nickname]" class="flex-1 min-w-[120px] border-gray-300 rounded-lg text-sm" placeholder="Nickname" />' +
                    '<input type="text" name="students[' + idx + '][full_name]" class="flex-1 min-w-[140px] border-gray-300 rounded-lg text-sm" placeholder="Nama lengkap (ops)" />' +
                    '<input type="text" name="students[' + idx + '][sekolah]" class="flex-1 min-w-[120px] border-gray-300 rounded-lg text-sm" placeholder="Sekolah (ops)" />' +
                    '<select name="students[' + idx + '][kelas]" class="w-28 border-gray-300 rounded-lg text-sm">' + buildKelasOptions() + '</select>' +
                    '<button type="button" class="remove-row text-rose-500 hover:text-rose-700 text-lg leading-none px-1 mt-0.5" title="Hapus">&times;</button>';
                return d;
            }
            document.addEventListener('click', function (e) {
                if (e.target.id === 'add-student-row-btn') {
                    var container = document.getElementById('students-container');
                    if (container) container.appendChild(buildRow(studentIndex++));
                }
                if (e.target.classList && e.target.classList.contains('remove-row')) {
                    var row = e.target.closest('.student-row');
                    if (row) row.remove();
                }
            });
        })();
    </script>
</x-app-layout>
