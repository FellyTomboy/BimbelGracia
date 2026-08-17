<x-app-layout>
    <x-slot name="title">Tambah Program Les</x-slot>
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
                        <select name="type" id="program-type" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih tipe</option>
                            <option value="privat" @selected(old('type') === 'privat')>privat</option>
                            <option value="kelas" @selected(old('type') === 'kelas')>kelas</option>
                        </select>
                        @error('type')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mapel: hanya tampil untuk privat --}}
                    <div id="field-subject">
                        <label class="block text-sm font-medium text-gray-700">Mapel</label>
                        <input name="subject" value="{{ old('subject') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        @error('subject')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Harga untuk privat --}}
                    <div id="section-privat-price" class="hidden">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default Harga Ortu</label>
                                <input type="number" name="default_parent_rate" value="{{ old('default_parent_rate', 0) }}" min="0" step="5000" class="mt-1 w-full border-gray-300 rounded-md" />
                                @error('default_parent_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default Gaji Guru</label>
                                <input type="number" name="default_teacher_rate" value="{{ old('default_teacher_rate', 0) }}" min="0" step="5000" class="mt-1 w-full border-gray-300 rounded-md" />
                                @error('default_teacher_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Harga paket untuk kelas --}}
                    <div id="section-kelas-price" class="hidden">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Paket Kelas</label>
                            <input type="number" name="default_parent_rate" value="{{ old('default_parent_rate', 0) }}" min="0" step="5000" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('default_parent_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ old('description') }}</textarea>
                        @error('description')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>

    <script>
        const typeSelect = document.getElementById('program-type');
        const fieldSubject = document.getElementById('field-subject');
        const sectionPrivat = document.getElementById('section-privat-price');
        const sectionKelas = document.getElementById('section-kelas-price');

        function toggleFields(type) {
            if (type === 'kelas') {
                fieldSubject.classList.add('hidden');
                sectionPrivat.classList.add('hidden');
                sectionKelas.classList.remove('hidden');
                // Disable hidden privat fields so they don't override nilai
                sectionPrivat.querySelectorAll('input').forEach(el => el.disabled = true);
                sectionKelas.querySelectorAll('input').forEach(el => el.disabled = false);
            } else {
                fieldSubject.classList.remove('hidden');
                sectionPrivat.classList.remove('hidden');
                sectionKelas.classList.add('hidden');
                // Enable privat fields, disable hidden kelas fields
                sectionPrivat.querySelectorAll('input').forEach(el => el.disabled = false);
                sectionKelas.querySelectorAll('input').forEach(el => el.disabled = true);
            }
        }

        toggleFields(typeSelect.value);

        typeSelect.addEventListener('change', () => {
            toggleFields(typeSelect.value);
        });
    </script>
</x-app-layout>
