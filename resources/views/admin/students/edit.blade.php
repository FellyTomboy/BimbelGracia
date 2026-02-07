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
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input name="name" value="{{ old('name', $student->name) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Login</label>
                        <input type="email" name="email" value="{{ old('email', $student->user?->email) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('email')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                        <input name="whatsapp" value="{{ old('whatsapp', $student->whatsapp) }}" class="mt-1 w-full border-gray-300 rounded-md" />
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
