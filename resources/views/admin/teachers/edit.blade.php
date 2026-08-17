<x-app-layout>
    <x-slot name="title">Edit Guru</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Guru</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input name="full_name" value="{{ old('full_name', $teacher->full_name) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nama lengkap" />
                            @error('full_name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Panggilan</label>
                            <input name="nickname" value="{{ old('nickname', $teacher->nickname) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nama panggilan" />
                            @error('nickname')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input name="name" value="{{ old('name', $teacher->name) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Opsional jika nama lengkap sudah diisi" />
                        @error('name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp (untuk login)</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $teacher->user?->phone) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="08XXXXXXXXXX" required />
                        @error('whatsapp')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                            <input name="major" value="{{ old('major', $teacher->major) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('major')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mapel</label>
                            <textarea name="subjects" class="mt-1 w-full border-gray-300 rounded-md">{{ old('subjects', $teacher->subjects) }}</textarea>
                            @error('subjects')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea name="address" class="mt-1 w-full border-gray-300 rounded-md">{{ old('address', $teacher->address) }}</textarea>
                        @error('address')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Bank</label>
                            <input name="bank_name" value="{{ old('bank_name', $teacher->bank_name) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">No Rekening</label>
                            <input name="bank_account" value="{{ old('bank_account', $teacher->bank_account) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Pemilik</label>
                            <input name="bank_owner" value="{{ old('bank_owner', $teacher->bank_owner) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tarif Kelas Bersama</label>
                        <input type="number" name="class_rate" value="{{ old('class_rate', $teacher->class_rate) }}" min="0" step="5000" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('class_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" @selected(old('status', $teacher->status) === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status', $teacher->status) === 'hibernasi')>hibernasi</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="bg-white shadow-sm sm:rounded-lg mt-6">
                <form method="POST" action="{{ route('admin.teachers.change-password', $teacher->id) }}" class="p-6 space-y-4">
                    @csrf

                    <h3 class="font-semibold text-lg">Ubah Password</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password Baru</label>
                        <input type="password" name="password" class="mt-1 w-full border-gray-300 rounded-md" required minlength="6" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="mt-1 w-full border-gray-300 rounded-md" required minlength="6" />
                    </div>

                    <button type="submit" class="px-4 py-2 rounded-md bg-amber-600 text-white">Ubah Password</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
