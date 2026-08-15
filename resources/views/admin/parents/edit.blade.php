<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Parent: {{ $parent->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-rose-50 text-rose-700 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            {{-- Edit Parent Info --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.parents.update', $parent->id) }}" class="p-6 space-y-4">
                    @csrf @method('PUT')

                    <h3 class="font-semibold text-lg">Informasi Parent</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Parent <span class="text-xs text-gray-400">(opsional)</span></label>
                        <input type="text" name="name" value="{{ old('name', $parent->name) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        @error('name') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat <span class="text-xs text-gray-400">(opsional)</span></label>
                        <textarea name="address" rows="2" class="mt-1 w-full border-gray-300 rounded-md">{{ old('address', $parent->address) }}</textarea>
                        @error('address') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">No HP (untuk login)</label>
                        <input type="text" name="phone" value="{{ old('phone', $parent->user?->phone) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('phone') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan Perubahan</button>
                        <a href="{{ route('admin.parents.index') }}" class="text-sm text-gray-500">Kembali</a>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.parents.change-password', $parent->id) }}" class="p-6 space-y-4">
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

            {{-- Student List --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <h3 class="font-semibold text-lg">Daftar Murid</h3>

                    @if ($parent->students->isNotEmpty())
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Nama Murid</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($parent->students as $student)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $student->display_name }}</td>
                                        <td class="py-2 pr-4">{{ $student->status }}</td>
                                        <td class="py-2">
                                            <form method="POST" action="{{ route('admin.parents.remove-student', [$parent->id, $student->id]) }}" onsubmit="return confirm('Hibernasi murid {{ $student->display_name }}?')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900">Hibernasi</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-400">Belum ada murid.</p>
                    @endif
                </div>
            </div>

            {{-- Add Student --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.parents.add-student', $parent->id) }}" class="p-6 space-y-4">
                    @csrf

                    <h3 class="font-semibold text-lg">Tambah Murid</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nickname Murid</label>
                        <input type="text" name="nickname" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('nickname') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap Murid <span class="text-xs text-gray-400">(opsional)</span></label>
                        <input type="text" name="full_name" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Opsional" />
                        @error('full_name') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Tambah Murid</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>