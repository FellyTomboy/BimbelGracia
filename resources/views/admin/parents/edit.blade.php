<x-app-layout>
    <x-slot name="title">Edit Parent</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Parent', 'url' => route('admin.parents.index')], ['label' => 'Edit']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Parent</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $parent->name ?? $parent->user?->phone }}</p>
            </div>
            <a href="{{ route('admin.parents.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 hover:bg-slate-200 transition-all">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
             submittingInfo: false,
             submittingPassword: false,
             submittingAddStudent: false,
             errorsInfo: {},
             errorsPassword: {},
             errorsStudent: {},

             async submitInfo(el) {
                 this.submittingInfo = true;
                 this.errorsInfo = {};
                 const form = el.closest('form');
                 const params = new URLSearchParams();
                 form.querySelectorAll('input[name], textarea[name]').forEach(i => { if (i.value) params.append(i.name, i.value); });
                 try {
                     await window.Ajax.put('{{ route('admin.parents.update', $parent->id) }}', params);
                     window.Toast?.success('Parent berhasil diperbarui.');
                     this.errorsInfo = {};
                 } catch (e) {
                     if (e.response?.status === 422) this.errorsInfo = e.response.data.errors || {};
                     else this.errorsInfo = {};
                 } finally {
                     this.submittingInfo = false;
                 }
             },

             async submitPassword(el) {
                 this.submittingPassword = true;
                 this.errorsPassword = {};
                 const form = el.closest('form');
                 const params = new URLSearchParams();
                 form.querySelectorAll('input[name]').forEach(i => { if (i.value) params.append(i.name, i.value); });
                 try {
                     await window.Ajax.post('{{ route('admin.parents.change-password', $parent->id) }}', params);
                     window.Toast?.success('Password berhasil diubah.');
                     form.reset();
                     this.errorsPassword = {};
                 } catch (e) {
                     if (e.response?.status === 422) this.errorsPassword = e.response.data.errors || {};
                     else this.errorsPassword = {};
                 } finally {
                     this.submittingPassword = false;
                 }
             },

             async submitAddStudent(el) {
                 this.submittingAddStudent = true;
                 this.errorsStudent = {};
                 const form = el.closest('form');
                 const params = new URLSearchParams();
                 form.querySelectorAll('input[name], select[name]').forEach(i => { if (i.value) params.append(i.name, i.value); });
                 try {
                     await window.Ajax.post('{{ route('admin.parents.add-student', $parent->id) }}', params);
                     window.Toast?.success('Murid berhasil ditambahkan.');
                     form.reset();
                     window.location.reload();
                 } catch (e) {
                     if (e.response?.status === 422) this.errorsStudent = e.response.data.errors || {};
                     else this.errorsStudent = {};
                 } finally {
                     this.submittingAddStudent = false;
                 }
             },

             async updateStudent(studentId, el) {
                 const form = el.closest('form');
                 const params = new URLSearchParams();
                 form.querySelectorAll('input[name], select[name]').forEach(i => { if (i.value) params.append(i.name, i.value); });
                 try {
                     await window.Ajax.put(`/admin/parents/{{ $parent->id }}/students/${studentId}`, params);
                     window.Toast?.success('Data murid berhasil diperbarui.');
                 } catch (e) {
                     window.Toast?.error('Gagal memperbarui data murid.');
                 }
             },

             async removeStudent(studentId, displayName) {
                 if (!confirm(`Hibernasi murid ${displayName}?`)) return;
                 try {
                     await window.Ajax.delete(`/admin/parents/{{ $parent->id }}/students/${studentId}`);
                     window.Toast?.success('Murid berhasil dihibernasi.');
                     el = document.querySelector(`[data-student-id=\"${studentId}\"]`);
                     if (el) el.remove();
                     const tbody = document.querySelector('#students-tbody');
                     if (tbody && tbody.children.length === 0) {
                         document.querySelector('#students-empty')?.classList.remove('hidden');
                     }
                 } catch (e) {
                     window.Toast?.error('Gagal menghapus murid.');
                 }
             }
         }">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Edit Parent Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form onsubmit="event.preventDefault(); window.Alpine.$data(this.closest('[x-data]')).submitInfo(this.querySelector('button[type=submit]'))">
                    @csrf
                    @method('PUT')
                    <div class="p-6 space-y-4">
                        <h3 class="font-semibold text-lg">Informasi Parent</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Parent <span class="text-xs text-gray-400">(opsional)</span>
                            </label>
                            <input type="text" name="name" value="{{ $parent->name ?? '' }}"
                                   class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errorsInfo.name" x-text="errorsInfo.name" class="mt-1 text-sm text-rose-500"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Alamat <span class="text-xs text-gray-400">(opsional)</span>
                            </label>
                            <textarea name="address" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ $parent->address ?? '' }}</textarea>
                            <p x-show="errorsInfo.address" x-text="errorsInfo.address" class="mt-1 text-sm text-rose-500"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                No HP <span class="text-xs text-gray-400">(untuk login)</span>
                            </label>
                            <input type="text" name="phone" value="{{ $parent->user?->phone ?? '' }}"
                                   class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errorsInfo.phone" x-text="errorsInfo.phone" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 flex items-center gap-3">
                        <button type="submit"
                                :disabled="submittingInfo"
                                class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800 disabled:opacity-50 flex items-center gap-2">
                            <template x-if="submittingInfo">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form onsubmit="event.preventDefault(); window.Alpine.$data(this.closest('[x-data]')).submitPassword(this.querySelector('button[type=submit]'))">
                    @csrf
                    <div class="p-6 space-y-4">
                        <h3 class="font-semibold text-lg">Ubah Password</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input type="password" name="password" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errorsPassword.password" x-text="errorsPassword.password" class="mt-1 text-sm text-rose-500"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <button type="submit"
                                :disabled="submittingPassword"
                                class="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2">
                            <template x-if="submittingPassword">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>

            {{-- Student List --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100" id="students-section">
                <div class="p-6 space-y-4">
                    <h3 class="font-semibold text-lg">Daftar Murid</h3>

                    @if ($parent->students->isNotEmpty())
                        <div class="overflow-x-auto" id="students-empty" style="display:none">
                            <p class="text-gray-400 py-4 text-center">Belum ada murid.</p>
                        </div>
                        <div class="overflow-x-auto" id="students-table-wrapper">
                            <table class="min-w-full text-sm" id="students-tbody">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-4">Nama Murid</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Sekolah &amp; Kelas</th>
                                        <th class="py-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($parent->students as $student)
                                        <tr data-student-id="{{ $student->id }}">
                                            <td class="py-2 pr-4 align-top">
                                                {{ $student->display_name }}
                                                @if ($student->full_name)
                                                    <div class="text-xs text-gray-400">{{ $student->full_name }}</div>
                                                @endif
                                            </td>
                                            <td class="py-2 pr-4 align-top">
                                                <div class="space-y-1">
                                                    <x-student-status-badge :status="$student->status" />
                                                    @if ($student->status === 'lulus')
                                                        <p class="text-xs text-indigo-600">Siswa lulus, silakan hibernasi.</p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-2 pr-4 align-top">
                                                <div class="flex items-center gap-2">
                                                    <input type="text" name="sekolah" value="{{ old('sekolah', $student->sekolah) }}"
                                                           placeholder="Sekolah"
                                                           class="w-40 border-gray-300 rounded-md text-xs" />
                                                    <select name="kelas" class="w-28 border-gray-300 rounded-md text-xs">
                                                        <option value="">—</option>
                                                        @foreach (\App\Helpers\StudentGrade::options() as $value => $label)
                                                            <option value="{{ $value }}" @selected(old('kelas', $student->kelas) === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button"
                                                            onclick="window.Alpine.$data(document.querySelector('#students-section [x-data]')).updateStudent({{ $student->id }}, this)"
                                                            class="px-2 py-1 rounded-md bg-slate-900 text-white text-xs">Simpan</button>
                                                </div>
                                            </td>
                                            <td class="py-2 align-top">
                                                <button type="button"
                                                        onclick="window.Alpine.$data(document.querySelector('#students-section [x-data]')).removeStudent({{ $student->id }}, '{{ $student->display_name }}')"
                                                        class="text-rose-600 hover:text-rose-900 text-sm">Hibernasi</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-400" id="students-empty">Belum ada murid.</p>
                    @endif
                </div>
            </div>

            {{-- Add Student --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form onsubmit="event.preventDefault(); window.Alpine.$data(this.closest('[x-data]')).submitAddStudent(this.querySelector('button[type=submit]'))">
                    @csrf
                    <div class="p-6 space-y-4">
                        <h3 class="font-semibold text-lg">Tambah Murid</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nickname Murid</label>
                            <input type="text" name="nickname" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errorsStudent.nickname" x-text="errorsStudent.nickname" class="mt-1 text-sm text-rose-500"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-xs text-gray-400">(opsional)</span></label>
                            <input type="text" name="full_name" class="w-full border-gray-300 rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sekolah <span class="text-xs text-gray-400">(opsional)</span></label>
                            <input type="text" name="sekolah" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Nama sekolah" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas <span class="text-xs text-gray-400">(opsional)</span></label>
                            <select name="kelas" class="w-full border-gray-300 rounded-lg text-sm">
                                <option value="">— Pilih kelas —</option>
                                @foreach (\App\Helpers\StudentGrade::LEVELS as $level)
                                    <option value="{{ $level }}" @selected(old('kelas') === $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <button type="submit"
                                :disabled="submittingAddStudent"
                                class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800 disabled:opacity-50 flex items-center gap-2">
                            <template x-if="submittingAddStudent">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            Tambah Murid
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
