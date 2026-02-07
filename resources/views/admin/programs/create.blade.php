<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Program Les</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.programs.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Program</label>
                        <input name="name" value="{{ old('name') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipe</label>
                        <select name="type" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih tipe</option>
                            <option value="privat" @selected(old('type') === 'privat')>privat</option>
                            <option value="kelompok" @selected(old('type') === 'kelompok')>kelompok</option>
                            <option value="online" @selected(old('type') === 'online')>online</option>
                        </select>
                        @error('type')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mapel</label>
                        <input name="subject" value="{{ old('subject') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        @error('subject')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ old('description') }}</textarea>
                        @error('description')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Harga Ortu</label>
                            <input type="number" name="default_parent_rate" value="{{ old('default_parent_rate', 0) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('default_parent_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Gaji Guru</label>
                            <input type="number" name="default_teacher_rate" value="{{ old('default_teacher_rate', 0) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('default_teacher_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
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
                        <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
