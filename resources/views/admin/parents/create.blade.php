<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Parent</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.parents.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Parent</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('name') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">No HP (untuk login)</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="08xxxxxxxxxx" />
                        @error('phone') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" class="mt-1 w-full border-gray-300 rounded-md" required minlength="6" />
                        @error('password') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                        <a href="{{ route('admin.parents.index') }}" class="text-sm text-gray-500">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>