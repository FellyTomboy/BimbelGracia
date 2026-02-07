<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Guru</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.teachers.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input name="name" value="{{ old('name') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Login</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('email')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                            <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('whatsapp_number')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                            <input name="major" value="{{ old('major') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('major')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mapel</label>
                        <textarea name="subjects" class="mt-1 w-full border-gray-300 rounded-md">{{ old('subjects') }}</textarea>
                        @error('subjects')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea name="address" class="mt-1 w-full border-gray-300 rounded-md">{{ old('address') }}</textarea>
                        @error('address')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Bank</label>
                            <input name="bank_name" value="{{ old('bank_name') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">No Rekening</label>
                            <input name="bank_account" value="{{ old('bank_account') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Pemilik</label>
                            <input name="bank_owner" value="{{ old('bank_owner') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" @selected(old('status') === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status') === 'hibernasi')>hibernasi</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
