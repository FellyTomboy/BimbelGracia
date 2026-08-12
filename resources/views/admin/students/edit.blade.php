<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Murid</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.students.update', $student) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nickname Murid</label>
                        <input type="text" name="nickname" value="{{ old('nickname', $student->nickname ?? '') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nickname murid" required />
                        @error('nickname')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap Murid <span class="text-xs text-gray-400">(opsional)</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nama lengkap murid" />
                        @error('full_name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Orang Tua / Wali <span class="text-xs text-gray-400">(opsional)</span></label>
                        <input type="text" name="parent_name" value="{{ old('parent_name', $student->parent?->name) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Kosongkan jika nomor baru" />
                        @error('parent_name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp (untuk login dan WA)</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $student->parent?->user?->phone) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="08XXXXXXXXXX" required />
                        @error('whatsapp')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea name="address" class="mt-1 w-full border-gray-300 rounded-md">{{ old('address', $student->address) }}</textarea>
                        @error('address')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" @selected(old('status', $student->status) === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status', $student->status) === 'hibernasi')>hibernasi</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.students.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>