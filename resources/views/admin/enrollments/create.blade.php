<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Enrollment</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.enrollments.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipe Enrollment</label>
                            <select name="type" id="type-select" class="mt-1 w-full border-gray-300 rounded-md" required>
                                <option value="privat" @selected(old('type', 'privat') === 'privat')>Privat (Per Sesi)</option>
                                <option value="kelas" @selected(old('type') === 'kelas')>Kelas (Paket Bulanan)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Privat: tagihan per sesi. Kelas: paket bulanan dengan guru bisa berganti per sesi.</p>
                            @error('type')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <select name="program_id" id="program-select" class="mt-1 w-full border-gray-300 rounded-md" required>
                                <option value="">Pilih program</option>
                                @foreach ($programs as $program)
                                    <option
                                        value="{{ $program->id }}"
                                        data-default-parent="{{ $program->default_parent_rate }}"
                                        data-default-teacher="{{ $program->default_teacher_rate }}"
                                        @selected(old('program_id') == $program->id)
                                    >
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('program_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div id="teacher-field">
                        <label class="block text-sm font-medium text-gray-700">Guru</label>
                        <select name="teacher_id" id="teacher-select" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">Pilih guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Guru utama untuk privat. Untuk kelas, guru bisa berubah per sesi saat pencatatan kehadiran.</p>
                        @error('teacher_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Pricing Tiers --}}
                    <div class="border rounded-lg p-4 bg-gray-50 space-y-4">
                        <h3 class="font-semibold text-gray-800">Harga Bertingkat (Pricing Tiers)</h3>
                        <p class="text-xs text-gray-500">Atur harga berbeda berdasarkan jumlah murid yang hadir. Kosongi jika harga sama untuk semua jumlah.</p>

                        <div x-data="{ count: {{ old('student_count', 3) }} }">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jumlah Murid di Enrollment Ini</label>
                                <input type="number" name="student_count" x-model="count" min="1" max="10" class="mt-1 w-full sm:w-24 border-gray-300 rounded-md" required />
                            </div>

                            <div class="mt-4 grid md:grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Harga Ortu / Pertemuan</h4>
                                    <table class="w-full text-sm">
                                        <template x-for="i in parseInt(count)" :key="i">
                                            <tr>
                                                <td class="py-1 pr-2 text-gray-600 whitespace-nowrap" x-text="i + ' murid:'"></td>
                                                <td class="py-1">
                                                    <input type="number" name="pricing_tiers_parent[i]" x-bind:name="'pricing_tiers_parent[' + i + ']'" min="0" step="5000" class="w-full border-gray-300 rounded-md text-sm" x-bind:placeholder="'Rp ' + (i * 100000)" />
                                                </td>
                                            </tr>
                                        </template>
                                    </table>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Gaji Guru / Pertemuan</h4>
                                    <table class="w-full text-sm">
                                        <template x-for="i in parseInt(count)" :key="i">
                                            <tr>
                                                <td class="py-1 pr-2 text-gray-600 whitespace-nowrap" x-text="i + ' murid:'"></td>
                                                <td class="py-1">
                                                    <input type="number" name="pricing_tiers_teacher[i]" x-bind:name="'pricing_tiers_teacher[' + i + ']'" min="0" step="5000" class="w-full border-gray-300 rounded-md text-sm" x-bind:placeholder="'Rp ' + (i * 50000)" />
                                                </td>
                                            </tr>
                                        </template>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Ortu Default (1 murid)</label>
                            <input type="number" name="parent_rate" id="parent-rate" value="{{ old('parent_rate') }}" step="5000" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('parent_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gaji Guru Default (1 murid)</label>
                            <input type="number" name="teacher_rate" id="teacher-rate" value="{{ old('teacher_rate') }}" step="5000" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('teacher_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Janji Les per Bulan</label>
                        <input type="number" name="agreed_sessions_per_month" value="{{ old('agreed_sessions_per_month', 4) }}" min="1" max="31" class="mt-1 w-full sm:w-32 border-gray-300 rounded-md" required />
                        <p class="text-xs text-gray-500 mt-1">Jika murid hadir kurang dari setengah jumlah ini, rate akan ditambah Rp 5.000/pertemuan.</p>
                        @error('agreed_sessions_per_month')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
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
                        <label class="block text-sm font-medium text-gray-700">Daftar Murid</label>
                        <div class="mt-2 grid md:grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-md p-3">
                            @foreach ($students as $student)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                        @checked(is_array(old('student_ids')) && in_array($student->id, old('student_ids', []))) />
                                    <span>{{ $student->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('student_ids')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.enrollments.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const typeSelect = document.getElementById('type-select');
        const teacherSelect = document.getElementById('teacher-select');
        const programSelect = document.getElementById('program-select');
        const parentRateInput = document.getElementById('parent-rate');
        const teacherRateInput = document.getElementById('teacher-rate');

        const updateTeacherRequired = () => {
            const isPrivat = typeSelect.value === 'privat';
            teacherSelect.required = isPrivat;
            if (!isPrivat) {
                teacherSelect.value = '';
            }
        };

        const markTouched = (event) => {
            event.target.dataset.touched = 'true';
        };

        const applyDefaults = () => {
            const selected = programSelect.options[programSelect.selectedIndex];
            if (!selected || !selected.value) {
                return;
            }

            const defaultParent = selected.dataset.defaultParent ?? '';
            const defaultTeacher = selected.dataset.defaultTeacher ?? '';

            if (!parentRateInput.dataset.touched && parentRateInput.value === '') {
                parentRateInput.value = defaultParent;
            }

            if (!teacherRateInput.dataset.touched && teacherRateInput.value === '') {
                teacherRateInput.value = defaultTeacher;
            }
        };

        typeSelect.addEventListener('change', updateTeacherRequired);
        parentRateInput.addEventListener('input', markTouched);
        teacherRateInput.addEventListener('input', markTouched);
        programSelect.addEventListener('change', applyDefaults);
        updateTeacherRequired();
        applyDefaults();
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-app-layout>