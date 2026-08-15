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
                        <label class="block text-sm font-medium text-gray-700">Nama Parent <span class="text-xs text-gray-400">(opsional)</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        @error('name') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat <span class="text-xs text-gray-400">(opsional)</span></label>
                        <textarea name="address" rows="2" class="mt-1 w-full border-gray-300 rounded-md">{{ old('address') }}</textarea>
                        @error('address') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">No HP (untuk login)</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="08xxxxxxxxxx" />
                        @error('phone') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <p class="text-xs text-gray-500">Password default: <code class="bg-gray-100 px-1 rounded">{{ config('bimbel.default_password', 'password') }}</code> (akan diminta ganti saat login pertama)</p>

                    <hr class="my-4">

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">Daftar Murid <span class="text-xs text-gray-400">(opsional)</span></label>
                            <button type="button" onclick="addStudentRow()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Tambah Murid</button>
                        </div>
                        <div class="mt-1" id="students-container">
                            @if (old('students'))
                                @foreach (old('students') as $index => $student)
                                <div class="flex items-center gap-2 mt-2 student-row" data-index="{{ $index }}">
                                    <input type="text" name="students[{{ $index }}][nickname]" value="{{ old('students.' . $index . '.nickname', $student['nickname'] ?? '') }}" class="flex-1 border-gray-300 rounded-md" placeholder="Nickname murid" />
                                    <input type="text" name="students[{{ $index }}][full_name]" value="{{ $student['full_name'] ?? '' }}" class="flex-1 border-gray-300 rounded-md" placeholder="Nama lengkap (opsional)" />
                                    <button type="button" onclick="this.closest('.student-row').remove()" class="text-rose-500 hover:text-rose-700 text-sm">&times;</button>
                                </div>
                                @endforeach
                            @endif
                        </div>
                        @error('students.*.nickname') <p class="text-rose-600 text-xs mt-1">Nickname murid tidak boleh kosong jika ditambahkan</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                        <a href="{{ route('admin.parents.index') }}" class="text-sm text-gray-500">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let studentIndex = {{ old('students') ? count(old('students')) : 0 }};

        function addStudentRow() {
            const container = document.getElementById('students-container');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 mt-2 student-row';
            div.dataset.index = studentIndex;
            div.innerHTML = `
                <input type="text" name="students[${studentIndex}][nickname]" class="flex-1 border-gray-300 rounded-md" placeholder="Nickname murid" />
                <input type="text" name="students[${studentIndex}][full_name]" class="flex-1 border-gray-300 rounded-md" placeholder="Nama lengkap (opsional)" />
                <button type="button" onclick="this.closest('.student-row').remove()" class="text-rose-500 hover:text-rose-700 text-sm">&times;</button>
            `;
            container.appendChild(div);
            studentIndex++;
        }
    </script>
    @endpush
</x-app-layout>