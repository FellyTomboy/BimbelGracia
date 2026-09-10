<x-app-layout>
    <x-slot name="title">Tambah Guru</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Guru', 'url' => route('admin.teachers.index')], ['label' => 'Tambah']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Guru</h2>
            </div>
            <a href="{{ route('admin.teachers.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 hover:bg-slate-200 transition-all">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
             submitting: false,
             errors: {},

             async submitInfo() {
                 this.submitting = true;
                 this.errors = {};
                 const form = this.$root.querySelector('form');
                 const params = new URLSearchParams();
                 params.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                 form.querySelectorAll('input[name], textarea[name], select[name]').forEach(i => {
                     if (i.value !== '') params.append(i.name, i.value);
                 });
                 try {
                     await window.Ajax.post('{{ route('admin.teachers.store') }}', params);
                     window.Toast?.success('Guru berhasil dibuat.');
                     window.location.href = '{{ route('admin.teachers.index') }}';
                 } catch (e) {
                     if (e.response?.status === 422) this.errors = e.response.data.errors || {};
                 } finally {
                     this.submitting = false;
                 }
             }
         }">

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form @submit.prevent="submitInfo()" class="p-6 space-y-4">
                    <h3 class="font-semibold text-lg">Informasi Guru</h3>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-xs text-gray-400">(opsional)</span></label>
                            <input type="text" name="full_name" value="{{ old('full_name') }}" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errors.full_name" x-text="errors.full_name" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Panggilan</label>
                            <input type="text" name="nickname" value="{{ old('nickname') }}" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errors.nickname" x-text="errors.nickname" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp (untuk login)</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" class="w-full border-gray-300 rounded-lg text-sm" placeholder="08XXXXXXXXXX" required />
                        <p x-show="errors.whatsapp" x-text="errors.whatsapp" class="mt-1 text-sm text-rose-500"></p>
                        <p class="text-xs text-gray-500 mt-1">Password default: <code class="bg-gray-100 px-1 rounded">{{ config('bimbel.default_password', 'password') }}</code> (akan diminta ganti saat login pertama)</p>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                            <input type="text" name="major" value="{{ old('major') }}" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errors.major" x-text="errors.major" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mapel</label>
                            <textarea name="subjects" class="w-full border-gray-300 rounded-lg text-sm">{{ old('subjects') }}</textarea>
                            <p x-show="errors.subjects" x-text="errors.subjects" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea name="address" class="w-full border-gray-300 rounded-lg text-sm">{{ old('address') }}</textarea>
                        <p x-show="errors.address" x-text="errors.address" class="mt-1 text-sm text-rose-500"></p>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errors.bank_name" x-text="errors.bank_name" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No Rekening</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account') }}" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errors.bank_account" x-text="errors.bank_account" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik</label>
                            <input type="text" name="bank_owner" value="{{ old('bank_owner') }}" class="w-full border-gray-300 rounded-lg text-sm" />
                            <p x-show="errors.bank_owner" x-text="errors.bank_owner" class="mt-1 text-sm text-rose-500"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Kelas Bersama</label>
                        <input type="number" name="class_rate" value="{{ old('class_rate', 0) }}" min="0" step="1000" class="w-full border-gray-300 rounded-lg text-sm" required />
                        <p x-show="errors.class_rate" x-text="errors.class_rate" class="mt-1 text-sm text-rose-500"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded-lg text-sm" required>
                            <option value="active" @selected(old('status') === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status') === 'hibernasi')>hibernasi</option>
                        </select>
                        <p x-show="errors.status" x-text="errors.status" class="mt-1 text-sm text-rose-500"></p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                        <button type="submit"
                                :disabled="submitting"
                                class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 disabled:opacity-50 transition-colors flex items-center gap-2">
                            <template x-if="submitting">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
